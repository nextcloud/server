/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/services/ServiceWorker.js"
/*!**************************************************!*\
  !*** ./apps/files/src/services/ServiceWorker.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
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
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_1__.logger.debug('SW registered: ', {
          registration
        });
      } catch (error) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_1__.logger.error('SW registration failed: ', {
          error
        });
      }
    });
  } else {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_1__.logger.debug('Service Worker is not enabled on this browser.');
  }
});

/***/ },

/***/ "./apps/files/src/actions/convertAction.ts"
/*!*************************************************!*\
  !*** ./apps/files/src/actions/convertAction.ts ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_CONVERT: () => (/* binding */ ACTION_CONVERT),
/* harmony export */   generateIconSvg: () => (/* binding */ generateIconSvg),
/* harmony export */   registerConvertActions: () => (/* binding */ registerConvertActions)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_autorenew_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/autorenew.svg?raw */ "./node_modules/@mdi/svg/svg/autorenew.svg?raw");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var _convertUtils_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./convertUtils.ts */ "./apps/files/src/actions/convertUtils.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const ACTION_CONVERT = 'convert';
/**
 * Registers the convert actions based on the capabilities provided by the server.
 */
function registerConvertActions() {
  // Generate sub actions
  const convertProviders = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)()?.files?.file_conversions ?? [];
  const actions = convertProviders.map(({
    to,
    from,
    displayName
  }) => ({
    id: `convert-${from}-${to}`,
    displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Save as {displayName}', {
      displayName
    }),
    iconSvgInline: () => generateIconSvg(to),
    enabled: ({
      nodes,
      folder
    }) => {
      if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__.isPublicShare)() && !(folder.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.CREATE)) {
        // cannot create the converted file in a public share if we don't have create permissions
        return false;
      }
      // Check that all nodes have the same mime type
      return nodes.every(node => from === node.mime);
    },
    async exec({
      nodes
    }) {
      if (!nodes[0]) {
        return false;
      }
      // If we're here, we know that the node has a fileid
      (0,_convertUtils_ts__WEBPACK_IMPORTED_MODULE_6__.convertFile)(nodes[0].fileid, to);
      // Silently terminate, we'll handle the UI in the background
      return null;
    },
    async execBatch({
      nodes
    }) {
      const fileIds = nodes.map(node => node.fileid).filter(Boolean);
      (0,_convertUtils_ts__WEBPACK_IMPORTED_MODULE_6__.convertFiles)(fileIds, to);
      // Silently terminate, we'll handle the UI in the background
      return Array(nodes.length).fill(null);
    },
    parent: ACTION_CONVERT
  }));
  // Register main action
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)({
    id: ACTION_CONVERT,
    displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Save as …'),
    iconSvgInline: () => _mdi_svg_svg_autorenew_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    enabled: context => {
      return actions.some(action => action.enabled(context));
    },
    async exec() {
      return null;
    },
    order: 25
  });
  // Register sub actions
  actions.forEach(_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction);
}
/**
 * Generates an SVG icon for a given mime type by using the server's mime icon endpoint.
 *
 * @param mime - The mime type to generate the icon for
 */
function generateIconSvg(mime) {
  // Generate icon based on mime type
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/core/mimeicon?mime=' + encodeURIComponent(mime));
  return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg">
		<image href="${url}" height="32" width="32" />
	</svg>`;
}

/***/ },

/***/ "./apps/files/src/actions/convertUtils.ts"
/*!************************************************!*\
  !*** ./apps/files/src/actions/convertUtils.ts ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   convertFile: () => (/* binding */ convertFile),
/* harmony export */   convertFiles: () => (/* binding */ convertFiles)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








const queue = new p_queue__WEBPACK_IMPORTED_MODULE_5__["default"]({
  concurrency: 5
});
/**
 *
 * @param fileId
 * @param targetMimeType
 */
function requestConversion(fileId, targetMimeType) {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('/apps/files/api/v1/convert'), {
    fileId,
    targetMimeType
  });
}
/**
 *
 * @param fileIds
 * @param targetMimeType
 */
async function convertFiles(fileIds, targetMimeType) {
  const conversions = fileIds.map(fileId => queue.add(() => requestConversion(fileId, targetMimeType)));
  // Start conversion
  const toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showLoading)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Converting files …'));
  // Handle results
  try {
    const results = await Promise.allSettled(conversions);
    const failed = results.filter(result => result.status === 'rejected');
    if (failed.length > 0) {
      const messages = failed.map(result => result.reason?.response?.data?.ocs?.meta?.message);
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Failed to convert files', {
        fileIds,
        targetMimeType,
        messages
      });
      // If all failed files have the same error message, show it
      if (new Set(messages).size === 1 && typeof messages[0] === 'string') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert files: {message}', {
          message: messages[0]
        }));
        return;
      }
      if (failed.length === fileIds.length) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'All files failed to be converted'));
        return;
      }
      // A single file failed and if we have a message for the failed file, show it
      if (failed.length === 1 && messages[0]) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'One file could not be converted: {message}', {
          message: messages[0]
        }));
        return;
      }
      // We already check above when all files failed
      // if we're here, we have a mix of failed and successful files
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', '%n file could not be converted', '%n files could not be converted', failed.length));
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', '%n file converted', '%n files converted', fileIds.length - failed.length));
      return;
    }
    // All files converted
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Files converted'));
    // Extract files that are within the current directory
    // in batch mode, you might have files from different directories
    // ⚠️, let's get the actual current dir, as the one from the action
    // might have changed as the user navigated away
    const currentDir = window.OCP.Files.Router.query.dir;
    const newPaths = results.filter(result => result.status === 'fulfilled').map(result => result.value.data.ocs.data.path).filter(path => path.startsWith(currentDir));
    // Fetch the new files
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.debug('Files to fetch', {
      newPaths
    });
    const newFiles = await Promise.all(newPaths.map(path => (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.fetchNode)(path)));
    // Inform the file list about the new files
    newFiles.forEach(file => (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:created', file));
    // Switch to the new files
    const firstSuccess = results[0];
    const newFileId = firstSuccess.value.data.ocs.data.fileId;
    window.OCP.Files.Router.goToRoute(null, {
      ...window.OCP.Files.Router.params,
      fileid: newFileId.toString()
    }, window.OCP.Files.Router.query);
  } catch (error) {
    // Should not happen as we use allSettled and handle errors above
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert files'));
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Failed to convert files', {
      fileIds,
      targetMimeType,
      error
    });
  } finally {
    // Hide loading toast
    toast.hideToast();
  }
}
/**
 *
 * @param fileId
 * @param targetMimeType
 */
async function convertFile(fileId, targetMimeType) {
  const toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showLoading)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Converting file …'));
  try {
    const result = await queue.add(() => requestConversion(fileId, targetMimeType));
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'File successfully converted'));
    // Inform the file list about the new file
    const newFile = await (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.fetchNode)(result.data.ocs.data.path);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:created', newFile);
    // Switch to the new file
    const newFileId = result.data.ocs.data.fileId;
    window.OCP.Files.Router.goToRoute(null, {
      ...window.OCP.Files.Router.params,
      fileid: newFileId.toString()
    }, window.OCP.Files.Router.query);
  } catch (error) {
    // If the server returned an error message, show it
    if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__.isAxiosError)(error) && error.response?.data?.ocs?.meta?.message) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert file: {message}', {
        message: error.response.data.ocs.meta.message
      }));
      return;
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Failed to convert file', {
      fileId,
      targetMimeType,
      error
    });
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert file'));
  } finally {
    // Hide loading toast
    toast.hideToast();
  }
}

/***/ },

/***/ "./apps/files/src/actions/deleteAction.ts"
/*!************************************************!*\
  !*** ./apps/files/src/actions/deleteAction.ts ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_DELETE: () => (/* binding */ ACTION_DELETE),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/close.svg?raw */ "./node_modules/@mdi/svg/svg/close.svg?raw");
/* harmony import */ var _mdi_svg_svg_network_off_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/network-off.svg?raw */ "./node_modules/@mdi/svg/svg/network-off.svg?raw");
/* harmony import */ var _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/trash-can-outline.svg?raw */ "./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./deleteUtils.ts */ "./apps/files/src/actions/deleteUtils.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */









// TODO: once the files app is migrated to the new frontend use the import instead:
// import { TRASHBIN_VIEW_ID } from '../../../files_trashbin/src/files_views/trashbinView.ts'
const TRASHBIN_VIEW_ID = 'trashbin';
const queue = new p_queue__WEBPACK_IMPORTED_MODULE_6__["default"]({
  concurrency: 5
});
const ACTION_DELETE = 'delete';
const action = {
  id: ACTION_DELETE,
  displayName: _deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.displayName,
  iconSvgInline: ({
    nodes
  }) => {
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.canUnshareOnly)(nodes)) {
      return _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_0__;
    }
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.canDisconnectOnly)(nodes)) {
      return _mdi_svg_svg_network_off_svg_raw__WEBPACK_IMPORTED_MODULE_1__;
    }
    return _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_2__;
  },
  enabled({
    nodes,
    view
  }) {
    if (view.id === TRASHBIN_VIEW_ID) {
      const config = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('files_trashbin', 'config', {
        allow_delete: true
      });
      if (config.allow_delete === false) {
        return false;
      }
    }
    return nodes.length > 0 && nodes.map(node => node.permissions).every(permission => (permission & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.DELETE) !== 0);
  },
  async exec({
    nodes,
    view
  }) {
    try {
      let confirm = true;
      // Trick to detect if the action was called from a keyboard event
      // we need to make sure the method calling have its named containing 'keydown'
      // here we use `onKeydown` method from the FileEntryActions component
      const callStack = new Error().stack || '';
      const isCalledFromEventListener = callStack.toLocaleLowerCase().includes('keydown');
      if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.shouldAskForConfirmation)() || isCalledFromEventListener) {
        confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.askConfirmation)([nodes[0]], view);
      }
      // If the user cancels the deletion, we don't want to do anything
      if (confirm === false) {
        return null;
      }
      await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.deleteNode)(nodes[0]);
      return true;
    } catch (error) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Error while deleting a file', {
        error,
        source: nodes[0].source,
        node: nodes[0]
      });
      return false;
    }
  },
  async execBatch({
    nodes,
    view
  }) {
    let confirm = true;
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.shouldAskForConfirmation)()) {
      confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.askConfirmation)(nodes, view);
    } else if (nodes.length >= 5 && !(0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.canUnshareOnly)(nodes) && !(0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.canDisconnectOnly)(nodes)) {
      confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.askConfirmation)(nodes, view);
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
            await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_8__.deleteNode)(node);
            resolve(true);
          } catch (error) {
            _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Error while deleting a file', {
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
  order: 100,
  hotkey: {
    description: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Delete'),
    key: 'Delete'
  }
};

/***/ },

/***/ "./apps/files/src/actions/deleteUtils.ts"
/*!***********************************************!*\
  !*** ./apps/files/src/actions/deleteUtils.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_userconfig_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/userconfig.ts */ "./apps/files/src/store/userconfig.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const isTrashbinEnabled = () => (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)()?.files?.undelete === true;
/**
 * @param nodes
 */
function canUnshareOnly(nodes) {
  return nodes.every(node => node.attributes['is-mount-root'] === true && node.attributes['mount-type'] === 'shared');
}
/**
 *
 * @param nodes
 */
function canDisconnectOnly(nodes) {
  return nodes.every(node => node.attributes['is-mount-root'] === true && node.attributes['mount-type'] === 'external');
}
/**
 *
 * @param nodes
 */
function isMixedUnshareAndDelete(nodes) {
  if (nodes.length === 1) {
    return false;
  }
  const hasSharedItems = nodes.some(node => canUnshareOnly([node]));
  const hasDeleteItems = nodes.some(node => !canUnshareOnly([node]));
  return hasSharedItems && hasDeleteItems;
}
/**
 *
 * @param nodes
 */
function isAllFiles(nodes) {
  return !nodes.some(node => node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.File);
}
/**
 *
 * @param nodes
 */
function isAllFolders(nodes) {
  return !nodes.some(node => node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder);
}
/**
 * Get the display name for the delete action
 *
 * @param context - The context
 * @param context.nodes - The nodes to delete
 * @param context.view - The current view
 */
function displayName({
  nodes,
  view
}) {
  /**
   * If those nodes are all the root node of a
   * share, we can only unshare them.
   */
  if (canUnshareOnly(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Leave this share');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Leave these shares');
  }
  /**
   * If those nodes are all the root node of an
   * external storage, we can only disconnect it.
   */
  if (canDisconnectOnly(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Disconnect storage');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Disconnect storages');
  }
  /**
   * If we're in the trashbin, we can only delete permanently
   */
  if (view.id === 'trashbin' || !isTrashbinEnabled()) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete permanently');
  }
  /**
   * If we're in the sharing view, we can only unshare
   */
  if (isMixedUnshareAndDelete(nodes)) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete and unshare');
  }
  /**
   * If we're only selecting files, use proper wording
   */
  if (isAllFiles(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete file');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete files');
  }
  /**
   * If we're only selecting folders, use proper wording
   */
  if (isAllFolders(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete folder');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete folders');
  }
  return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Delete');
}
/**
 *
 */
function shouldAskForConfirmation() {
  const userConfig = (0,_store_userconfig_ts__WEBPACK_IMPORTED_MODULE_6__.useUserConfigStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_5__.getPinia)());
  return userConfig.userConfig.show_dialog_deletion !== false;
}
/**
 *
 * @param nodes
 * @param view
 */
async function askConfirmation(nodes, view) {
  const message = view.id === 'trashbin' || !isTrashbinEnabled() ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.n)('files', 'You are about to permanently delete {count} item', 'You are about to permanently delete {count} items', nodes.length, {
    count: nodes.length
  }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.n)('files', 'You are about to delete {count} item', 'You are about to delete {count} items', nodes.length, {
    count: nodes.length
  });
  return new Promise(resolve => {
    // TODO: Use the new dialog API
    window.OC.dialogs.confirmDestructive(message, (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Confirm deletion'), {
      type: window.OC.dialogs.YES_NO_BUTTONS,
      confirm: displayName({
        nodes,
        view
      }),
      confirmClasses: 'error',
      cancel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Cancel')
    }, decision => {
      resolve(decision);
    });
  });
}
/**
 *
 * @param node
 */
async function deleteNode(node) {
  await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(node.encodedSource);
  // Let's delete even if it's moved to the trashbin
  // since it has been removed from the current view
  // and changing the view will trigger a reload anyway.
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:deleted', node);
}

/***/ },

/***/ "./apps/files/src/actions/downloadAction.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/actions/downloadAction.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_arrow_down_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/arrow-down.svg?raw */ "./node_modules/@mdi/svg/svg/arrow-down.svg?raw");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _store_files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/files.ts */ "./apps/files/src/store/files.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_paths_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../store/paths.ts */ "./apps/files/src/store/paths.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _utils_permissions_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/permissions.ts */ "./apps/files/src/utils/permissions.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const action = {
  id: 'download',
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.DefaultType.DEFAULT,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Download'),
  iconSvgInline: () => _mdi_svg_svg_arrow_down_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  enabled({
    nodes,
    view
  }) {
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
    return nodes.every(_utils_permissions_ts__WEBPACK_IMPORTED_MODULE_10__.isDownloadable);
  },
  async exec({
    nodes
  }) {
    try {
      await downloadNodes(nodes);
    } catch (error) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'The requested file is not available.'));
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.error('The requested file is not available.', {
        error
      });
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:node:deleted', nodes[0]);
    }
    return null;
  },
  async execBatch({
    nodes,
    view,
    folder
  }) {
    try {
      await downloadNodes(nodes);
    } catch (error) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'The requested files are not available.'));
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.error('The requested files are not available.', {
        error
      });
      // Try to reload the current directory to update the view
      const directory = getCurrentDirectory(view, folder.path);
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:node:updated', directory);
    }
    return new Array(nodes.length).fill(null);
  },
  order: 30
};
/**
 * Trigger downloading a file.
 *
 * @param url The url of the asset to download
 * @param name Optionally the recommended name of the download (browsers might ignore it)
 */
async function triggerDownload(url, name) {
  // try to see if the resource is still available
  await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].head(url);
  const hiddenElement = document.createElement('a');
  hiddenElement.download = name ?? '';
  hiddenElement.href = url;
  hiddenElement.click();
}
/**
 * Find the longest common path prefix of both input paths
 *
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
  if (!nodes[0]) {
    throw new Error('No nodes to download');
  }
  if (nodes.length === 1) {
    if (nodes[0].type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.FileType.File) {
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
  const filesStore = (0,_store_files_ts__WEBPACK_IMPORTED_MODULE_6__.useFilesStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_7__.getPinia)());
  const pathsStore = (0,_store_paths_ts__WEBPACK_IMPORTED_MODULE_8__.usePathsStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_7__.getPinia)());
  if (!view?.id) {
    return null;
  }
  if (directory === '/') {
    return filesStore.getRoot(view.id) || null;
  }
  const fileId = pathsStore.getPath(view.id, directory);
  return filesStore.getNode(fileId) || null;
}

/***/ },

/***/ "./apps/files/src/actions/favoriteAction.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/actions/favoriteAction.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_FAVORITE: () => (/* binding */ ACTION_FAVORITE),
/* harmony export */   action: () => (/* binding */ action),
/* harmony export */   favoriteNode: () => (/* binding */ favoriteNode)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/star-outline.svg?raw */ "./node_modules/@mdi/svg/svg/star-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/star.svg?raw */ "./node_modules/@mdi/svg/svg/star.svg?raw");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/*
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */












const queue = new p_queue__WEBPACK_IMPORTED_MODULE_9__["default"]({
  concurrency: 5
});
const ACTION_FAVORITE = 'favorite';
const action = {
  id: ACTION_FAVORITE,
  displayName({
    nodes
  }) {
    return shouldFavorite(nodes) ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Add to favorites') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Remove from favorites');
  },
  iconSvgInline: ({
    nodes
  }) => {
    return shouldFavorite(nodes) ? _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ : _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_1__;
  },
  enabled({
    nodes
  }) {
    // Not enabled for public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_8__.isPublicShare)()) {
      return false;
    }
    // We can only favorite nodes if they are located in files
    return nodes.every(node => node.root?.startsWith?.('/files'))
    // and we have permissions
    && nodes.every(node => node.permissions !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.Permission.NONE);
  },
  async exec({
    nodes,
    view
  }) {
    const willFavorite = shouldFavorite([nodes[0]]);
    return await favoriteNode(nodes[0], view, willFavorite);
  },
  async execBatch({
    nodes,
    view
  }) {
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
            _utils_logger_ts__WEBPACK_IMPORTED_MODULE_11__.logger.error('Error while adding file to favorite', {
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
  order: -50,
  hotkey: {
    description: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Add or remove favorite'),
    key: 'S'
  }
};
/**
 * Favorite or unfavorite a node
 *
 * @param node - The node to favorite/unfavorite
 * @param view - The current view
 * @param willFavorite - Whether to favorite or unfavorite the node
 */
async function favoriteNode(node, view, willFavorite) {
  try {
    // TODO: migrate to webdav tags plugin
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_7__.generateUrl)('/apps/files/api/v1/files') + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__.encodePath)(node.path);
    await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post(url, {
      tags: willFavorite ? [window.OC.TAG_FAVORITE] : []
    });
    // Let's delete if we are in the favourites view
    // AND if it is removed from the user favorites
    // AND it's in the root of the favorites view
    if (view.id === 'favorites' && !willFavorite && node.dirname === '/') {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:node:deleted', node);
    }
    // Update the node webdav attribute
    vue__WEBPACK_IMPORTED_MODULE_10__["default"].set(node.attributes, 'favorite', willFavorite ? 1 : 0);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:node:updated', node);
    // Dispatch event to whoever is interested
    if (willFavorite) {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:favorites:added', node);
    } else {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:favorites:removed', node);
    }
    return true;
  } catch (error) {
    const action = willFavorite ? 'adding a file to favourites' : 'removing a file from favourites';
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_11__.logger.error('Error while ' + action, {
      error,
      source: node.source,
      node
    });
    return false;
  }
}
/**
 * If any of the nodes is not favored, we display the favorite action.
 *
 * @param nodes - The nodes to check
 */
function shouldFavorite(nodes) {
  return nodes.some(node => node.attributes.favorite !== 1);
}

/***/ },

/***/ "./apps/files/src/actions/moveOrCopyAction.ts"
/*!****************************************************!*\
  !*** ./apps/files/src/actions/moveOrCopyAction.ts ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_COPY_MOVE: () => (/* binding */ ACTION_COPY_MOVE),
/* harmony export */   HintException: () => (/* binding */ HintException),
/* harmony export */   action: () => (/* binding */ action),
/* harmony export */   handleCopyMoveNodesTo: () => (/* binding */ handleCopyMoveNodesTo)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-move-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/folder-multiple-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-multiple-outline.svg?raw");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_upload__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/upload */ "./node_modules/@nextcloud/upload/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_Files_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/Files.ts */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./moveOrCopyActionUtils.ts */ "./apps/files/src/actions/moveOrCopyActionUtils.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */














/**
 * Exception to hint the user about something.
 * The message is intended to be shown to the user.
 */
class HintException extends Error {}
const ACTION_COPY_MOVE = 'move-copy';
const action = {
  id: ACTION_COPY_MOVE,
  order: 15,
  displayName({
    nodes
  }) {
    switch (getActionForNodes(nodes)) {
      case _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Move');
      case _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Copy');
      case _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE_OR_COPY:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Move or copy');
    }
  },
  iconSvgInline: () => _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  enabled({
    nodes,
    view
  }) {
    // We can not copy or move in single file shares
    if (view.id === 'public-file-share') {
      return false;
    }
    // We only support moving/copying files within the user folder
    if (!nodes.every(node => node.root?.startsWith('/files/'))) {
      return false;
    }
    return nodes.length > 0 && ((0,_moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.canMove)(nodes) || (0,_moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.canCopy)(nodes));
  },
  async exec(context) {
    return this.execBatch(context)[0];
  },
  async execBatch({
    nodes,
    folder
  }) {
    const action = getActionForNodes(nodes);
    const target = await openFilePickerForAction(action, folder.path, nodes);
    // Handle cancellation silently
    if (target === false) {
      return nodes.map(() => null);
    }
    try {
      const result = await Array.fromAsync(handleCopyMoveNodesTo(nodes, target.destination, target.action));
      return result.map(() => true);
    } catch (error) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_12__.logger.error(`Failed to ${target.action} node`, {
        nodes,
        error
      });
      if (error instanceof HintException && !!error.message) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(error.message);
        // Silent action as we handle the toast
        return nodes.map(() => null);
      }
      // We need to keep the selection on error!
      // So we do not return null, and for batch action
      return nodes.map(() => false);
    }
  }
};
/**
 * Handle the copy/move of a node to a destination
 * This can be imported and used by other scripts/components on server
 *
 * @param nodes The nodes to copy/move
 * @param destination The destination to copy/move the nodes to
 * @param method The method to use for the copy/move
 * @param overwrite Whether to overwrite the destination if it exists
 * @yields {AsyncGenerator<void, void, never>} A promise that resolves when the copy/move is done
 */
async function* handleCopyMoveNodesTo(nodes, destination, method, overwrite = false) {
  if (!destination) {
    return;
  }
  if (destination.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.FileType.Folder) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Destination is not a folder'));
  }
  // Do not allow to MOVE a node to the same folder it is already located
  if (method === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE && nodes.some(node => node.dirname === destination.path)) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'This file/folder is already in that directory'));
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
  if (nodes.some(node => `${destination.path}/`.startsWith(`${node.path}/`))) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'You cannot move a file/folder onto itself or into a subfolder of itself'));
  }
  const nameMapping = new Map();
  // Check for conflicts if we do not want to overwrite
  if (!overwrite) {
    const otherNodes = (await (0,_services_Files_ts__WEBPACK_IMPORTED_MODULE_11__.getContents)(destination.path)).contents;
    const conflicts = (0,_nextcloud_upload__WEBPACK_IMPORTED_MODULE_8__.getConflicts)(nodes, otherNodes);
    const nodesToRename = [];
    if (conflicts.length > 0) {
      if (method === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE) {
        // Let the user choose what to do with the conflicting files
        const content = otherNodes.filter(n => conflicts.some(c => c.basename === n.basename));
        const result = await (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.openConflictPicker)(destination.path, conflicts, content);
        if (!result) {
          // User cancelled
          return;
        }
        nodes = nodes.filter(n => !result.skipped.includes(n));
        nodesToRename.push(...result.renamed);
      } else {
        // for COPY we always rename conflicting files
        nodesToRename.push(...conflicts);
      }
      const usedNames = [...otherNodes, ...nodes.filter(n => !conflicts.includes(n))].map(n => n.basename);
      for (const node of nodesToRename) {
        const newName = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.getUniqueName)(node.basename, usedNames, {
          ignoreFileExtension: node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.FileType.Folder
        });
        nameMapping.set(node.source, newName);
        usedNames.push(newName); // add the new name to avoid duplicates for following re-namimgs
      }
    }
  }
  const actionFinished = createLoadingNotification(method, nodes.map(node => node.basename), destination.path);
  const queue = (0,_moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.getQueue)();
  try {
    for (const node of nodes) {
      // Set loading state
      vue__WEBPACK_IMPORTED_MODULE_10__["default"].set(node, 'status', _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.NodeStatus.LOADING);
      yield queue.add(async () => {
        try {
          const client = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__.getClient)();
          const currentPath = (0,path__WEBPACK_IMPORTED_MODULE_9__.join)(_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__.defaultRootPath, node.path);
          const destinationPath = (0,path__WEBPACK_IMPORTED_MODULE_9__.join)(_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__.defaultRootPath, destination.path, nameMapping.get(node.source) ?? node.basename);
          if (method === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY) {
            await client.copyFile(currentPath, destinationPath);
            // If the node is copied into current directory the view needs to be updated
            if (node.dirname === destination.path) {
              const {
                data
              } = await client.stat(destinationPath, {
                details: true,
                data: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__.getDefaultPropfind)()
              });
              (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files:node:created', (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_6__.resultToNode)(data));
            }
          } else {
            await client.moveFile(currentPath, destinationPath);
            // Delete the node as it will be fetched again
            // when navigating to the destination folder
            (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files:node:deleted', node);
          }
        } catch (error) {
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_12__.logger.debug(`Error while trying to ${method === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY ? 'copy' : 'move'} node`, {
            node,
            error
          });
          if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__.isAxiosError)(error)) {
            if (error.response?.status === 412) {
              throw new HintException((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'A file or folder with that name already exists in this folder'));
            } else if (error.response?.status === 423) {
              throw new HintException((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'The files are locked'));
            } else if (error.response?.status === 404) {
              throw new HintException((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'The file does not exist anymore'));
            } else if ('response' in error && error.response) {
              const parser = new DOMParser();
              const text = await error.response.text();
              const message = parser.parseFromString(text ?? '', 'text/xml').querySelector('message')?.textContent;
              if (message) {
                throw new HintException(message);
              }
            }
          }
          throw error;
        } finally {
          vue__WEBPACK_IMPORTED_MODULE_10__["default"].set(node, 'status', undefined);
        }
      });
    }
  } finally {
    actionFinished();
  }
}
/**
 * Return the action that is possible for the given nodes
 *
 * @param nodes The nodes to check against
 * @return The action that is possible for the given nodes
 */
function getActionForNodes(nodes) {
  if ((0,_moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.canMove)(nodes)) {
    if ((0,_moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.canCopy)(nodes)) {
      return _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE_OR_COPY;
    }
    return _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE;
  }
  // Assuming we can copy as the enabled checks for copy permissions
  return _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY;
}
/**
 * Create a loading notification toast
 *
 * @param mode The move or copy mode
 * @param sources Names of the nodes that are copied / moved
 * @param destination Destination path
 * @return Function to hide the notification
 */
function createLoadingNotification(mode, sources, destination) {
  const text = mode === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE ? sources.length === 1 ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Moving "{source}" to "{destination}" …', {
    source: sources[0],
    destination
  }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Moving {count} files to "{destination}" …', {
    count: sources.length,
    destination
  }) : sources.length === 1 ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Copying "{source}" to "{destination}" …', {
    source: sources[0],
    destination
  }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Copying {count} files to "{destination}" …', {
    count: sources.length,
    destination
  });
  const toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showLoading)(text);
  return () => toast && toast.hideToast();
}
/**
 * Open a file picker for the given action
 *
 * @param action The action to open the file picker for
 * @param dir The directory to start the file picker in
 * @param nodes The nodes to move/copy
 * @return The picked destination or false if cancelled by user
 */
async function openFilePickerForAction(action, dir = '/', nodes) {
  const {
    resolve,
    reject,
    promise
  } = Promise.withResolvers();
  const fileIDs = nodes.map(node => node.fileid).filter(Boolean);
  const filePicker = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.getFilePickerBuilder)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Choose destination')).allowDirectories(true).setFilter(n => {
    // We don't want to show the current nodes in the file picker
    return !fileIDs.includes(n.fileid);
  }).setCanPick(n => {
    const hasCreatePermissions = (n.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.CREATE) === _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.CREATE;
    return hasCreatePermissions;
  }).setMimeTypeFilter([]).setMultiSelect(false).startAt(dir).setButtonFactory((selection, path) => {
    const buttons = [];
    const target = (0,path__WEBPACK_IMPORTED_MODULE_9__.basename)(path);
    const dirnames = nodes.map(node => node.dirname);
    const paths = nodes.map(node => node.path);
    if (action === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY || action === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE_OR_COPY) {
      buttons.push({
        label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Copy to {target}', {
          target
        }, {
          escape: false,
          sanitize: false
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Copy'),
        variant: 'primary',
        icon: _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__,
        async callback(destination) {
          resolve({
            destination: destination[0],
            action: _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.COPY
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
    if (selection.some(node => (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.CREATE) === 0)) {
      // Missing 'CREATE' permissions for selected destination
      return buttons;
    }
    if (action === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE || action === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE_OR_COPY) {
      buttons.push({
        label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Move to {target}', {
          target
        }, undefined, {
          escape: false,
          sanitize: false
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Move'),
        variant: action === _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE ? 'primary' : 'secondary',
        icon: _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
        async callback(destination) {
          resolve({
            destination: destination[0],
            action: _moveOrCopyActionUtils_ts__WEBPACK_IMPORTED_MODULE_13__.MoveCopyAction.MOVE
          });
        }
      });
    }
    return buttons;
  }).build();
  filePicker.pick().catch(error => {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_12__.logger.debug(error);
    if (error instanceof _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.FilePickerClosed) {
      resolve(false);
    } else {
      reject(new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_7__.t)('files', 'Move or copy operation failed')));
    }
  });
  return promise;
}

/***/ },

/***/ "./apps/files/src/actions/moveOrCopyActionUtils.ts"
/*!*********************************************************!*\
  !*** ./apps/files/src/actions/moveOrCopyActionUtils.ts ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   MoveCopyAction: () => (/* binding */ MoveCopyAction),
/* harmony export */   canCopy: () => (/* binding */ canCopy),
/* harmony export */   canDownload: () => (/* binding */ canDownload),
/* harmony export */   canMove: () => (/* binding */ canMove),
/* harmony export */   getQueue: () => (/* binding */ getQueue)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const sharePermissions = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'sharePermissions', _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE);
// This is the processing queue. We only want to allow 3 concurrent requests
let queue;
// Maximum number of concurrent operations
const MAX_CONCURRENCY = 5;
/**
 * Get the processing queue
 */
function getQueue() {
  if (!queue) {
    queue = new p_queue__WEBPACK_IMPORTED_MODULE_3__["default"]({
      concurrency: MAX_CONCURRENCY
    });
  }
  return queue;
}
var MoveCopyAction;
(function (MoveCopyAction) {
  MoveCopyAction["MOVE"] = "Move";
  MoveCopyAction["COPY"] = "Copy";
  MoveCopyAction["MOVE_OR_COPY"] = "move-or-copy";
})(MoveCopyAction || (MoveCopyAction = {}));
/**
 * Check if the given nodes can be moved
 *
 * @param nodes - The nodes to check
 */
function canMove(nodes) {
  const minPermission = nodes.reduce((min, node) => Math.min(min, node.permissions), _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.ALL);
  return Boolean(minPermission & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.DELETE);
}
/**
 * Check if the given nodes can be downloaded
 *
 * @param nodes - The nodes to check
 */
function canDownload(nodes) {
  return nodes.every(node => {
    const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] ?? '[]');
    return !shareAttributes.some(attribute => attribute.scope === 'permissions' && attribute.value === false && attribute.key === 'download');
  });
}
/**
 * Check if the given nodes can be copied
 *
 * @param nodes - The nodes to check
 */
function canCopy(nodes) {
  // a shared file cannot be copied if the download is disabled
  if (!canDownload(nodes)) {
    return false;
  }
  // it cannot be copied if the user has only view permissions
  if (nodes.some(node => node.permissions === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE)) {
    return false;
  }
  // on public shares all files have the same permission so copy is only possible if write permission is granted
  if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__.isPublicShare)()) {
    return Boolean(sharePermissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.CREATE);
  }
  // otherwise permission is granted
  return true;
}

/***/ },

/***/ "./apps/files/src/actions/openFolderAction.ts"
/*!****************************************************!*\
  !*** ./apps/files/src/actions/openFolderAction.ts ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder.svg?raw */ "./node_modules/@mdi/svg/svg/folder.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const action = {
  id: 'open-folder',
  displayName({
    nodes
  }) {
    if (nodes.length !== 1 || !nodes[0]) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Open folder');
    }
    // Only works on single node
    const displayName = nodes[0].displayname;
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Open folder {displayName}', {
      displayName
    });
  },
  iconSvgInline: () => _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  enabled({
    nodes
  }) {
    // Only works on single node
    if (nodes.length !== 1 || !nodes[0]) {
      return false;
    }
    const node = nodes[0];
    if (!node.isDavResource) {
      return false;
    }
    return node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder && (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ) !== 0;
  },
  async exec({
    nodes,
    view
  }) {
    const node = nodes[0];
    if (!node || node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
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
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.DefaultType.HIDDEN,
  order: -100
};

/***/ },

/***/ "./apps/files/src/actions/openInFilesAction.ts"
/*!*****************************************************!*\
  !*** ./apps/files/src/actions/openInFilesAction.ts ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_search_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../views/search.ts */ "./apps/files/src/views/search.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const action = {
  id: 'open-in-files',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Open in Files'),
  iconSvgInline: () => '',
  enabled({
    view
  }) {
    return view.id === 'recent' || view.id === _views_search_ts__WEBPACK_IMPORTED_MODULE_2__.VIEW_ID;
  },
  async exec({
    nodes
  }) {
    if (!nodes[0]) {
      return false;
    }
    let dir = nodes[0].dirname;
    if (nodes[0].type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder) {
      dir = dir + '/' + nodes[0].basename;
    }
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files',
      fileid: String(nodes[0].fileid)
    }, {
      dir,
      openfile: 'true'
    });
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.DefaultType.HIDDEN
};

/***/ },

/***/ "./apps/files/src/actions/openLocallyAction.ts"
/*!*****************************************************!*\
  !*** ./apps/files/src/actions/openLocallyAction.ts ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_laptop_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/laptop.svg?raw */ "./node_modules/@mdi/svg/svg/laptop.svg?raw");
/* harmony import */ var _mdi_svg_svg_web_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/web.svg?raw */ "./node_modules/@mdi/svg/svg/web.svg?raw");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _utils_permissions_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/permissions.ts */ "./apps/files/src/utils/permissions.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const action = {
  id: 'edit-locally',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open locally'),
  iconSvgInline: () => _mdi_svg_svg_laptop_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  // Only works on single files
  enabled({
    nodes
  }) {
    // Only works on single node
    if (nodes.length !== 1 || !nodes[0]) {
      return false;
    }
    // does not work with shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_8__.isPublicShare)()) {
      return false;
    }
    return (0,_utils_permissions_ts__WEBPACK_IMPORTED_MODULE_10__.isSyncable)(nodes[0]);
  },
  async exec({
    nodes
  }) {
    await attemptOpenLocalClient(nodes[0].path);
    return null;
  },
  order: 25
};
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
 * There is no way to get notified if this action was successful.
 *
 * @param path - Path to open
 */
async function openLocalClient(path) {
  const link = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_7__.generateOcsUrl)('apps/files/api/v1') + '/openlocaleditor?format=json';
  try {
    const result = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].post(link, {
      path
    });
    const uid = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid;
    let url = `nc://open/${uid}@` + window.location.host + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__.encodePath)(path);
    url += '?token=' + result.data.ocs.data.token;
    window.open(url, '_self');
  } catch (error) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Failed to redirect to client'));
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.error('Failed to redirect to client', {
      error
    });
  }
}
/**
 * Open the confirmation dialog.
 */
async function confirmLocalEditDialog() {
  let result = false;
  const dialog = new _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.DialogBuilder().setName((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open file locally')).setText((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'The file should now open on your device. If it doesn\'t, please check that you have the desktop app installed.')).setButtons([{
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Retry and close'),
    variant: 'secondary',
    callback: () => {
      result = 'local';
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open online'),
    icon: _mdi_svg_svg_web_svg_raw__WEBPACK_IMPORTED_MODULE_1__,
    variant: 'primary',
    callback: () => {
      result = 'online';
    }
  }]).build();
  try {
    await dialog.show();
  } catch (error) {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.debug('Open locally dialog closed', {
      error
    });
  }
  return result;
}

/***/ },

/***/ "./apps/files/src/actions/renameAction.ts"
/*!************************************************!*\
  !*** ./apps/files/src/actions/renameAction.ts ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_RENAME: () => (/* binding */ ACTION_RENAME),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/pencil-outline.svg?raw */ "./node_modules/@mdi/svg/svg/pencil-outline.svg?raw");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _store_files_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/files.ts */ "./apps/files/src/store/files.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const ACTION_RENAME = 'rename';
const action = {
  id: ACTION_RENAME,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files', 'Rename'),
  iconSvgInline: () => _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  enabled: ({
    nodes,
    view
  }) => {
    if (nodes.length === 0 || !nodes[0]) {
      return false;
    }
    // Disable for single file shares
    if (view.id === 'public-file-share') {
      return false;
    }
    const node = nodes[0];
    const filesStore = (0,_store_files_ts__WEBPACK_IMPORTED_MODULE_5__.useFilesStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_6__.getPinia)());
    const parentNode = node.dirname === '/' ? filesStore.getRoot(view.id) : filesStore.getNode((0,path__WEBPACK_IMPORTED_MODULE_4__.dirname)(node.source));
    const parentPermissions = parentNode?.permissions || _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.NONE;
    // Enable if the node has update permissions or the node
    // has delete permission and the parent folder allows creating files
    return Boolean(node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.DELETE) && Boolean(parentPermissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.CREATE) || Boolean(node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.UPDATE);
  },
  async exec({
    nodes
  }) {
    // Renaming is a built-in feature of the files app
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:rename', nodes[0]);
    return null;
  },
  order: 10,
  hotkey: {
    description: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files', 'Rename'),
    key: 'F2'
  }
};

/***/ },

/***/ "./apps/files/src/actions/sidebarAction.ts"
/*!*************************************************!*\
  !*** ./apps/files/src/actions/sidebarAction.ts ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_DETAILS: () => (/* binding */ ACTION_DETAILS),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/information-outline.svg?raw */ "./node_modules/@mdi/svg/svg/information-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const ACTION_DETAILS = 'details';
const action = {
  id: ACTION_DETAILS,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Details'),
  iconSvgInline: () => _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  // Sidebar currently supports user folder only, /files/USER
  enabled: ({
    nodes
  }) => {
    const node = nodes[0];
    if (nodes.length !== 1 || !node) {
      return false;
    }
    const sidebar = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getSidebar)();
    if (!sidebar.available) {
      return false;
    }
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__.isPublicShare)()) {
      return false;
    }
    return node.root.startsWith('/files/') && node.permissions !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE;
  },
  async exec({
    nodes
  }) {
    const sidebar = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getSidebar)();
    const [node] = nodes;
    try {
      // If the sidebar is already open for the current file, do nothing
      if (sidebar.node?.source === node.source) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('Sidebar already open for this file', {
          node
        });
        return null;
      }
      sidebar.open(node, 'sharing');
      return null;
    } catch (error) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.error('Error while opening sidebar', {
        error
      });
      return false;
    }
  },
  order: -50,
  hotkey: {
    key: 'D',
    description: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Open the details sidebar')
  }
};

/***/ },

/***/ "./apps/files/src/actions/sidebarFavoriteAction.ts"
/*!*********************************************************!*\
  !*** ./apps/files/src/actions/sidebarFavoriteAction.ts ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerSidebarFavoriteAction: () => (/* binding */ registerSidebarFavoriteAction)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/star-outline.svg?raw */ "./node_modules/@mdi/svg/svg/star-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/star.svg?raw */ "./node_modules/@mdi/svg/svg/star.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _favoriteAction_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./favoriteAction.ts */ "./apps/files/src/actions/favoriteAction.ts");
/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





/**
 * Register the favorite/unfavorite action in the sidebar
 */
function registerSidebarFavoriteAction() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerSidebarAction)({
    id: 'files-favorite',
    order: 0,
    enabled({
      node
    }) {
      return node.isDavResource && node.root.startsWith('/files/');
    },
    displayName({
      node
    }) {
      if (node.attributes.favorite) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Unfavorite');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Favorite');
    },
    iconSvgInline({
      node
    }) {
      if (node.attributes.favorite) {
        return _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_1__;
      }
      return _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__;
    },
    onClick({
      node,
      view
    }) {
      (0,_favoriteAction_ts__WEBPACK_IMPORTED_MODULE_4__.favoriteNode)(node, view, !node.attributes.favorite);
    }
  });
}

/***/ },

/***/ "./apps/files/src/actions/viewInFolderAction.ts"
/*!******************************************************!*\
  !*** ./apps/files/src/actions/viewInFolderAction.ts ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_eye_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-eye-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-eye-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const action = {
  id: 'view-in-folder',
  displayName() {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'View in folder');
  },
  iconSvgInline: () => _mdi_svg_svg_folder_eye_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  enabled({
    nodes,
    view
  }) {
    // Not enabled for public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__.isPublicShare)()) {
      return false;
    }
    // Only works outside of the main files view
    if (view.id === 'files') {
      return false;
    }
    // Only works on single node
    if (nodes.length !== 1 || !nodes[0]) {
      return false;
    }
    const node = nodes[0];
    if (!node.isDavResource) {
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
  async exec({
    nodes
  }) {
    if (!nodes[0] || nodes[0].type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File) {
      return false;
    }
    window.OCP.Files.Router.goToRoute(null, {
      view: 'files',
      fileid: String(nodes[0].fileid)
    }, {
      dir: nodes[0].dirname
    });
    return null;
  },
  order: 10
};

/***/ },

/***/ "./apps/files/src/filters/FilenameFilter.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/filters/FilenameFilter.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
 * Simple file list filter controlled by the Navigation search box
 */
class FilenameFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:filename', 5);
    _defineProperty(this, "searchQuery", '');
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:search:updated', ({
      query,
      scope
    }) => {
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
/**
 * Register the filename filter
 */
function registerFilenameFilter() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new FilenameFilter());
}

/***/ },

/***/ "./apps/files/src/filters/HiddenFilesFilter.ts"
/*!*****************************************************!*\
  !*** ./apps/files/src/filters/HiddenFilesFilter.ts ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerHiddenFilesFilter: () => (/* binding */ registerHiddenFilesFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



class HiddenFilesFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:hidden', 0);
    _defineProperty(this, "showHidden", void 0);
    this.showHidden = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files', 'config', {
      show_hidden: false
    }).show_hidden;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:config:updated', ({
      key,
      value
    }) => {
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
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new HiddenFilesFilter());
}

/***/ },

/***/ "./apps/files/src/filters/ModifiedFilter.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/filters/ModifiedFilter.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerModifiedFilter: () => (/* binding */ registerModifiedFilter)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_calendar_range_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/calendar-range-outline.svg?raw */ "./node_modules/@mdi/svg/svg/calendar-range-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _vue_web_component_wrapper__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @vue/web-component-wrapper */ "./node_modules/@vue/web-component-wrapper/dist/vue-wc-wrapper.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilter_FileListFilterModified_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/FileListFilter/FileListFilterModified.vue */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const tagName = 'files-file-list-filter-modified';
class ModifiedFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:modified', 50);
    _defineProperty(this, "currentInstance", void 0);
    _defineProperty(this, "currentPreset", void 0);
    _defineProperty(this, "displayName", (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Modified'));
    _defineProperty(this, "iconSvgInline", _mdi_svg_svg_calendar_range_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__);
    _defineProperty(this, "tagName", tagName);
  }
  filter(nodes) {
    if (!this.currentPreset) {
      return nodes;
    }
    return nodes.filter(node => node.mtime === undefined || this.currentPreset.filter(node.mtime.getTime()));
  }
  reset() {
    this.dispatchEvent(new CustomEvent('reset'));
  }
  get preset() {
    return this.currentPreset;
  }
  setPreset(preset) {
    this.currentPreset = preset;
    this.filterUpdated();
    const chips = [];
    if (preset) {
      chips.push({
        icon: _mdi_svg_svg_calendar_range_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
        text: preset.label,
        onclick: () => this.reset()
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
  const WrappedComponent = (0,_vue_web_component_wrapper__WEBPACK_IMPORTED_MODULE_3__["default"])(vue__WEBPACK_IMPORTED_MODULE_4__["default"], _components_FileListFilter_FileListFilterModified_vue__WEBPACK_IMPORTED_MODULE_5__["default"]);
  // In Vue 2, wrap doesn't support disabling shadow :(
  // Disable with a hack
  Object.defineProperty(WrappedComponent.prototype, 'attachShadow', {
    value() {
      return this;
    }
  });
  Object.defineProperty(WrappedComponent.prototype, 'shadowRoot', {
    get() {
      return this;
    }
  });
  customElements.define(tagName, WrappedComponent);
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new ModifiedFilter());
}

/***/ },

/***/ "./apps/files/src/filters/TypeFilter.ts"
/*!**********************************************!*\
  !*** ./apps/files/src/filters/TypeFilter.ts ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerTypeFilter: () => (/* binding */ registerTypeFilter)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_file_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/file-outline.svg?raw */ "./node_modules/@mdi/svg/svg/file-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _vue_web_component_wrapper__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @vue/web-component-wrapper */ "./node_modules/@vue/web-component-wrapper/dist/vue-wc-wrapper.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilter_FileListFilterType_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/FileListFilter/FileListFilterType.vue */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const tagName = 'files-file-list-filter-type';
class TypeFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:type', 10);
    _defineProperty(this, "currentInstance", void 0);
    _defineProperty(this, "currentPresets", void 0);
    _defineProperty(this, "displayName", (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Type'));
    _defineProperty(this, "iconSvgInline", _mdi_svg_svg_file_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__);
    _defineProperty(this, "tagName", tagName);
    this.currentPresets = [];
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
    // to be listener by the component
    this.dispatchEvent(new CustomEvent('reset'));
  }
  get presets() {
    return this.currentPresets;
  }
  setPresets(presets) {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__.logger.debug('TypeFilter: setting presets', {
      presets
    });
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
   *
   * @param presetId Id of preset to remove
   */
  removeFilterPreset(presetId) {
    const filtered = this.currentPresets.filter(({
      id
    }) => id !== presetId);
    this.dispatchEvent(new CustomEvent('deselect', {
      detail: presetId
    }));
    this.setPresets(filtered);
  }
}
/**
 * Register the file list filter by file type
 */
function registerTypeFilter() {
  const WrappedComponent = (0,_vue_web_component_wrapper__WEBPACK_IMPORTED_MODULE_3__["default"])(vue__WEBPACK_IMPORTED_MODULE_4__["default"], _components_FileListFilter_FileListFilterType_vue__WEBPACK_IMPORTED_MODULE_5__["default"]);
  // In Vue 2, wrap doesn't support disabling shadow :(
  // Disable with a hack
  Object.defineProperty(WrappedComponent.prototype, 'attachShadow', {
    value() {
      return this;
    }
  });
  Object.defineProperty(WrappedComponent.prototype, 'shadowRoot', {
    get() {
      return this;
    }
  });
  window.customElements.define(tagName, WrappedComponent);
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new TypeFilter());
}

/***/ },

/***/ "./apps/files/src/init.ts"
/*!********************************!*\
  !*** ./apps/files/src/init.ts ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var _actions_convertAction_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./actions/convertAction.ts */ "./apps/files/src/actions/convertAction.ts");
/* harmony import */ var _actions_deleteAction_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions/deleteAction.ts */ "./apps/files/src/actions/deleteAction.ts");
/* harmony import */ var _actions_downloadAction_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./actions/downloadAction.ts */ "./apps/files/src/actions/downloadAction.ts");
/* harmony import */ var _actions_favoriteAction_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./actions/favoriteAction.ts */ "./apps/files/src/actions/favoriteAction.ts");
/* harmony import */ var _actions_moveOrCopyAction_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./actions/moveOrCopyAction.ts */ "./apps/files/src/actions/moveOrCopyAction.ts");
/* harmony import */ var _actions_openFolderAction_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./actions/openFolderAction.ts */ "./apps/files/src/actions/openFolderAction.ts");
/* harmony import */ var _actions_openInFilesAction_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./actions/openInFilesAction.ts */ "./apps/files/src/actions/openInFilesAction.ts");
/* harmony import */ var _actions_openLocallyAction_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./actions/openLocallyAction.ts */ "./apps/files/src/actions/openLocallyAction.ts");
/* harmony import */ var _actions_renameAction_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./actions/renameAction.ts */ "./apps/files/src/actions/renameAction.ts");
/* harmony import */ var _actions_sidebarAction_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./actions/sidebarAction.ts */ "./apps/files/src/actions/sidebarAction.ts");
/* harmony import */ var _actions_sidebarFavoriteAction_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./actions/sidebarFavoriteAction.ts */ "./apps/files/src/actions/sidebarFavoriteAction.ts");
/* harmony import */ var _actions_viewInFolderAction_ts__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./actions/viewInFolderAction.ts */ "./apps/files/src/actions/viewInFolderAction.ts");
/* harmony import */ var _filters_FilenameFilter_ts__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./filters/FilenameFilter.ts */ "./apps/files/src/filters/FilenameFilter.ts");
/* harmony import */ var _filters_HiddenFilesFilter_ts__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./filters/HiddenFilesFilter.ts */ "./apps/files/src/filters/HiddenFilesFilter.ts");
/* harmony import */ var _filters_ModifiedFilter_ts__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./filters/ModifiedFilter.ts */ "./apps/files/src/filters/ModifiedFilter.ts");
/* harmony import */ var _filters_TypeFilter_ts__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./filters/TypeFilter.ts */ "./apps/files/src/filters/TypeFilter.ts");
/* harmony import */ var _newMenu_newFolder_ts__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./newMenu/newFolder.ts */ "./apps/files/src/newMenu/newFolder.ts");
/* harmony import */ var _newMenu_newFromTemplate_ts__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./newMenu/newFromTemplate.ts */ "./apps/files/src/newMenu/newFromTemplate.ts");
/* harmony import */ var _newMenu_newTemplatesFolder_ts__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./newMenu/newTemplatesFolder.ts */ "./apps/files/src/newMenu/newTemplatesFolder.ts");
/* harmony import */ var _services_LivePhotos_ts__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./services/LivePhotos.ts */ "./apps/files/src/services/LivePhotos.ts");
/* harmony import */ var _services_ServiceWorker_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./services/ServiceWorker.js */ "./apps/files/src/services/ServiceWorker.js");
/* harmony import */ var _views_favorites_ts__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./views/favorites.ts */ "./apps/files/src/views/favorites.ts");
/* harmony import */ var _views_files_ts__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./views/files.ts */ "./apps/files/src/views/files.ts");
/* harmony import */ var _views_folderTree_ts__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./views/folderTree.ts */ "./apps/files/src/views/folderTree.ts");
/* harmony import */ var _views_personal_files_ts__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./views/personal-files.ts */ "./apps/files/src/views/personal-files.ts");
/* harmony import */ var _views_recent_ts__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./views/recent.ts */ "./apps/files/src/views/recent.ts");
/* harmony import */ var _views_search_ts__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./views/search.ts */ "./apps/files/src/views/search.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






























// Register file actions
(0,_actions_convertAction_ts__WEBPACK_IMPORTED_MODULE_3__.registerConvertActions)();
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_deleteAction_ts__WEBPACK_IMPORTED_MODULE_4__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_downloadAction_ts__WEBPACK_IMPORTED_MODULE_5__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openLocallyAction_ts__WEBPACK_IMPORTED_MODULE_10__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_favoriteAction_ts__WEBPACK_IMPORTED_MODULE_6__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_moveOrCopyAction_ts__WEBPACK_IMPORTED_MODULE_7__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openFolderAction_ts__WEBPACK_IMPORTED_MODULE_8__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openInFilesAction_ts__WEBPACK_IMPORTED_MODULE_9__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_renameAction_ts__WEBPACK_IMPORTED_MODULE_11__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_sidebarAction_ts__WEBPACK_IMPORTED_MODULE_12__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_viewInFolderAction_ts__WEBPACK_IMPORTED_MODULE_14__.action);
// Register new menu entry
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)(_newMenu_newFolder_ts__WEBPACK_IMPORTED_MODULE_19__.entry);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)(_newMenu_newTemplatesFolder_ts__WEBPACK_IMPORTED_MODULE_21__.entry);
(0,_newMenu_newFromTemplate_ts__WEBPACK_IMPORTED_MODULE_20__.registerTemplateEntries)();
// Register files views when not on public share
if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__.isPublicShare)() === false) {
  (0,_views_favorites_ts__WEBPACK_IMPORTED_MODULE_24__.registerFavoritesView)();
  (0,_views_files_ts__WEBPACK_IMPORTED_MODULE_25__.registerFilesView)();
  (0,_views_personal_files_ts__WEBPACK_IMPORTED_MODULE_27__.registerPersonalFilesView)();
  (0,_views_recent_ts__WEBPACK_IMPORTED_MODULE_28__["default"])();
  (0,_views_search_ts__WEBPACK_IMPORTED_MODULE_29__.registerSearchView)();
  (0,_views_folderTree_ts__WEBPACK_IMPORTED_MODULE_26__.registerFolderTreeView)();
}
// Register file list filters
(0,_filters_HiddenFilesFilter_ts__WEBPACK_IMPORTED_MODULE_16__.registerHiddenFilesFilter)();
(0,_filters_TypeFilter_ts__WEBPACK_IMPORTED_MODULE_18__.registerTypeFilter)();
(0,_filters_ModifiedFilter_ts__WEBPACK_IMPORTED_MODULE_17__.registerModifiedFilter)();
(0,_filters_FilenameFilter_ts__WEBPACK_IMPORTED_MODULE_15__.registerFilenameFilter)();
// Register sidebar action
(0,_actions_sidebarFavoriteAction_ts__WEBPACK_IMPORTED_MODULE_13__.registerSidebarFavoriteAction)();
// Register preview service worker
(0,_services_ServiceWorker_js__WEBPACK_IMPORTED_MODULE_23__["default"])();
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:hidden', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:is-mount-root', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:metadata-blurhash', {
  nc: 'http://nextcloud.org/ns'
});
(0,_services_LivePhotos_ts__WEBPACK_IMPORTED_MODULE_22__.initLivePhotos)();
// TODO: REMOVE THIS ONCE THE UPLOAD LIBRARY IS MIGRATED TO THE NEW FILES LIBRARY
window._nc_newfilemenu = new Proxy((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNewFileMenu)(), {
  get(target, prop) {
    return target[prop];
  },
  set(target, prop, value) {
    target[prop] = value;
    return true;
  }
});

/***/ },

/***/ "./apps/files/src/newMenu/newFolder.ts"
/*!*********************************************!*\
  !*** ./apps/files/src/newMenu/newFolder.ts ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   entry: () => (/* binding */ entry)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-plus-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/newNodeDialog.ts */ "./apps/files/src/utils/newNodeDialog.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */










const entry = {
  id: 'newFolder',
  order: 0,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'New folder'),
  // Make the svg icon color match the primary element color
  iconSvgInline: _mdi_svg_svg_folder_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__.replace(/viewBox/gi, 'style="color: var(--color-primary-element)" viewBox'),
  enabled(context) {
    return Boolean(context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.CREATE) && Boolean(context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.READ);
  },
  async handler(context, content) {
    const name = await (0,_utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_9__.newNodeName)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'New folder'), content);
    if (name === null) {
      return;
    }
    try {
      const {
        fileid,
        source
      } = await createNewFolder(context, name.trim());
      // Create the folder in the store
      const folder = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Folder({
        source,
        id: fileid,
        mtime: new Date(),
        owner: context.owner,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_5__.Permission.ALL,
        root: context?.root || '/files/' + (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)()?.uid,
        // Include mount-type from parent folder as this is inherited
        attributes: {
          'mount-type': context.attributes?.['mount-type'],
          'owner-id': context.attributes?.['owner-id'],
          'owner-display-name': context.attributes?.['owner-display-name']
        }
      });
      // Show success
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files:node:created', folder);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Created new folder "{name}"', {
        name: (0,path__WEBPACK_IMPORTED_MODULE_7__.basename)(source)
      }));
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_8__.logger.debug('Created new folder', {
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
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_8__.logger.error('Creating new folder failed', {
        error
      });
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)('Creating new folder failed');
    }
  }
};
/**
 * Create a new folder in the given root with the given name
 *
 * @param root - The folder in which the new folder should be created
 * @param name - The name of the new folder
 */
async function createNewFolder(root, name) {
  const source = root.source + '/' + name;
  const encodedSource = root.encodedSource + '/' + encodeURIComponent(name);
  const response = await (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"])({
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
}

/***/ },

/***/ "./apps/files/src/newMenu/newFromTemplate.ts"
/*!***************************************************!*\
  !*** ./apps/files/src/newMenu/newFromTemplate.ts ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerTemplateEntries: () => (/* binding */ registerTemplateEntries)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/newNodeDialog.ts */ "./apps/files/src/utils/newNodeDialog.ts");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






// async to reduce bundle size
const TemplatePickerVue = (0,vue__WEBPACK_IMPORTED_MODULE_4__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_src_views_TemplatePicker_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/TemplatePicker.vue */ "./apps/files/src/views/TemplatePicker.vue")));
let TemplatePicker = null;
/**
 *
 * @param context
 */
async function getTemplatePicker(context) {
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
        open(...args) {
          this.$refs.picker.open(...args);
        }
      },
      el: mountingPoint
    });
  }
  return TemplatePicker;
}
/**
 * Register all new-file-menu entries for all template providers
 */
function registerTemplateEntries() {
  let templates;
  if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_3__.isPublicShare)()) {
    templates = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'templates', []);
  } else {
    templates = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files', 'templates', []);
  }
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
        const name = await (0,_utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_5__.newNodeName)(`${provider.label}${provider.extension}`, content, {
          label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'Filename'),
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

/***/ },

/***/ "./apps/files/src/newMenu/newTemplatesFolder.ts"
/*!******************************************************!*\
  !*** ./apps/files/src/newMenu/newTemplatesFolder.ts ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   entry: () => (/* binding */ entry)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_plus_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/plus.svg?raw */ "./node_modules/@mdi/svg/svg/plus.svg?raw");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/newNodeDialog.ts */ "./apps/files/src/utils/newNodeDialog.ts");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const templatesEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('files', 'templates_enabled', true);
let templatesPath = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('files', 'templates_path', false);
_utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.debug('Templates folder enabled', {
  templatesEnabled
});
_utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.debug('Initial templates folder', {
  templatesPath
});
/**
 * Init template folder
 *
 * @param directory Folder where to create the templates folder
 * @param name Name to use or the templates folder
 */
async function initTemplatesFolder(directory, name) {
  const templatePath = (0,path__WEBPACK_IMPORTED_MODULE_8__.join)(directory.path, name);
  try {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.debug('Initializing the templates directory', {
      templatePath
    });
    const {
      data
    } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_7__.generateOcsUrl)('apps/files/api/v1/templates/path'), {
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
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.info('Created new templates folder', {
      ...data.ocs.data
    });
    templatesPath = data.ocs.data.templates_path;
  } catch (error) {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_9__.logger.error('Unable to initialize the templates directory', {
      error
    });
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.translate)('files', 'Unable to initialize the templates directory'));
  }
}
const entry = {
  id: 'template-picker',
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.translate)('files', 'Create templates folder'),
  iconSvgInline: _mdi_svg_svg_plus_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  order: 30,
  enabled(context) {
    // Templates disabled or templates folder already initialized
    if (!templatesEnabled || templatesPath) {
      return false;
    }
    // Allow creation on your own folders only
    if (context.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)()?.uid) {
      return false;
    }
    return (context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.Permission.CREATE) !== 0;
  },
  async handler(context, content) {
    const name = await (0,_utils_newNodeDialog_ts__WEBPACK_IMPORTED_MODULE_10__.newNodeName)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.translate)('files', 'Templates'), content, {
      name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.translate)('files', 'New template folder')
    });
    if (name !== null) {
      // Create the template folder
      initTemplatesFolder(context, name);
      // Remove the menu entry
      (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.removeNewFileMenuEntry)('template-picker');
    }
  }
};

/***/ },

/***/ "./apps/files/src/services/Favorites.ts"
/*!**********************************************!*\
  !*** ./apps/files/src/services/Favorites.ts ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _Files_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Files.ts */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






/**
 * Get the contents for the favorites view
 *
 * @param path - The path to get the contents for
 * @param options - Additional options
 * @param options.signal - Optional AbortSignal to cancel the request
 * @return A promise resolving to the contents with root folder
 */
async function getContents(path = '/', options) {
  // We only filter root files for favorites, for subfolders we can simply reuse the files contents
  if (path && path !== '/') {
    return (0,_Files_ts__WEBPACK_IMPORTED_MODULE_4__.getContents)(path, options);
  }
  try {
    const contents = await (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getFavoriteNodes)({
      client: _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_5__.client,
      signal: options.signal
    });
    return {
      contents,
      folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
        id: 0,
        source: `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRemoteURL)()}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)()}`,
        root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)(),
        owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ
      })
    };
  } catch (error) {
    if (options.signal.aborted) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_3__.logger.debug('Favorite nodes request was aborted');
      throw new DOMException('Aborted', 'AbortError');
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_3__.logger.error('Failed to load favorite nodes via WebDAV', {
      error
    });
    throw error;
  }
}

/***/ },

/***/ "./apps/files/src/services/Files.ts"
/*!******************************************!*\
  !*** ./apps/files/src/services/Files.ts ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultGetContents: () => (/* binding */ defaultGetContents),
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _store_files_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../store/files.ts */ "./apps/files/src/store/files.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/search.ts */ "./apps/files/src/store/search.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./WebDavSearch.ts */ "./apps/files/src/services/WebDavSearch.ts");








/**
 * Get contents implementation for the files view.
 * This also allows to fetch local search results when the user is currently filtering.
 *
 * @param path - The path to query
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 */
async function getContents(path = '/', options) {
  const searchStore = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_4__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_3__.getPinia)());
  if (searchStore.query.length < 3) {
    return await defaultGetContents(path, options);
  }
  return await getLocalSearch(path, searchStore.query, options?.signal);
}
/**
 * Generic `getContents` implementation for the users files.
 *
 * @param path - The path to get the contents
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 */
async function defaultGetContents(path, options) {
  path = (0,path__WEBPACK_IMPORTED_MODULE_1__.join)((0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getRootPath)(), path);
  const propfindPayload = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getDefaultPropfind)();
  const contentsResponse = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.client.getDirectoryContents(path, {
    details: true,
    data: propfindPayload,
    includeSelf: true,
    signal: options?.signal
  });
  const root = contentsResponse.data[0];
  const contents = contentsResponse.data.slice(1);
  if (root?.filename !== path && `${root?.filename}/` !== path) {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.debug(`Exepected "${path}" but got filename "${root.filename}" instead.`);
    throw new Error('Root node does not match requested path');
  }
  return {
    folder: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(root),
    contents: contents.map(result => {
      try {
        return (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(result);
      } catch (error) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.error(`Invalid node detected '${result.basename}'`, {
          error
        });
        return null;
      }
    }).filter(Boolean)
  };
}
/**
 * Get the local search results for the current folder.
 *
 * @param path - The path
 * @param query - The current search query
 * @param signal - The aboort signal
 */
async function getLocalSearch(path, query, signal) {
  const filesStore = (0,_store_files_ts__WEBPACK_IMPORTED_MODULE_2__.useFilesStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_3__.getPinia)());
  let folder = filesStore.getDirectoryByPath('files', path);
  if (!folder) {
    const rootPath = (0,path__WEBPACK_IMPORTED_MODULE_1__.join)((0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getRootPath)(), path);
    const stat = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.client.stat(rootPath, {
      details: true
    });
    folder = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(stat.data);
  }
  const contents = await (0,_WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_7__.searchNodes)(query, {
    dir: path,
    signal
  });
  return {
    folder,
    contents
  };
}

/***/ },

/***/ "./apps/files/src/services/FolderTree.ts"
/*!***********************************************!*\
  !*** ./apps/files/src/services/FolderTree.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   encodeSource: () => (/* binding */ encodeSource),
/* harmony export */   folderTreeId: () => (/* binding */ folderTreeId),
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   getFolderTreeNodes: () => (/* binding */ getFolderTreeNodes),
/* harmony export */   getSourceParent: () => (/* binding */ getSourceParent),
/* harmony export */   sourceRoot: () => (/* binding */ sourceRoot)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _Files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Files.ts */ "./apps/files/src/services/Files.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const folderTreeId = 'folders';
const sourceRoot = `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRemoteURL)()}/files/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid}`;
const collator = Intl.Collator([(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)()], {
  numeric: true,
  usage: 'sort'
});
const compareNodes = (a, b) => collator.compare(a.displayName ?? a.basename, b.displayName ?? b.basename);
/**
 * Get all tree nodes recursively
 *
 * @param tree - The tree to process
 * @param currentPath - The current path
 * @param nodes - The nodes collected so far
 */
function getTreeNodes(tree, currentPath = '/', nodes = []) {
  const sortedTree = tree.toSorted(compareNodes);
  for (const {
    id,
    basename,
    displayName,
    children
  } of sortedTree) {
    const path = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.join)(currentPath, basename);
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
}
/**
 * Get folder tree nodes
 *
 * @param path - The path to get the tree from
 * @param depth - The depth to fetch
 * @param withParents - Whether to include parent folders in the response
 */
async function getFolderTreeNodes(path = '/', depth = 1, withParents = false) {
  const {
    data: tree
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('/apps/files/api/v1/folder-tree'), {
    params: new URLSearchParams({
      path,
      depth: String(depth),
      withParents: String(withParents)
    })
  });
  const nodes = getTreeNodes(tree, withParents ? '/' : path);
  return nodes;
}
const getContents = (path, options) => (0,_Files_ts__WEBPACK_IMPORTED_MODULE_6__.getContents)(path, options);
/**
 * Encode source URL
 *
 * @param source - The source URL
 */
function encodeSource(source) {
  const {
    origin
  } = new URL(source);
  return origin + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.encodePath)(source.slice(origin.length));
}
/**
 * Get parent source URL
 *
 * @param source - The source URL
 */
function getSourceParent(source) {
  const parent = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.dirname)(source);
  if (parent === sourceRoot) {
    return folderTreeId;
  }
  return `${folderTreeId}::${encodeSource(parent)}`;
}

/***/ },

/***/ "./apps/files/src/services/LivePhotos.ts"
/*!***********************************************!*\
  !*** ./apps/files/src/services/LivePhotos.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initLivePhotos: () => (/* binding */ initLivePhotos),
/* harmony export */   isLivePhoto: () => (/* binding */ isLivePhoto)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");

/**
 *
 */
function initLivePhotos() {
  (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:metadata-files-live-photo', {
    nc: 'http://nextcloud.org/ns'
  });
}
/**
 * @param node - The node
 */
function isLivePhoto(node) {
  return node.attributes['metadata-files-live-photo'] !== undefined;
}

/***/ },

/***/ "./apps/files/src/services/PersonalFiles.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/services/PersonalFiles.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   isPersonalFile: () => (/* binding */ isPersonalFile)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _Files_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Files.ts */ "./apps/files/src/services/Files.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const currentUserId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid;
/**
 * Filters each file/folder on its shared status
 *
 * A personal file is considered a file that has all of the following properties:
 * 1. the current user owns
 * 2. the file is not shared with anyone
 * 3. the file is not a group folder
 *
 * @todo Move to `@nextcloud/files`
 * @param node The node to check
 */
function isPersonalFile(node) {
  // the type of mounts that determine whether the file is shared
  const sharedMountTypes = ['group', 'shared'];
  const mountType = node.attributes['mount-type'];
  return currentUserId === node.owner && !sharedMountTypes.includes(mountType);
}
/**
 * Get personal files from a given path
 *
 * @param path - The path to get the personal files from
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 * @return A promise that resolves to the personal files
 */
function getContents(path = '/', options) {
  // get all the files from the current path as a cancellable promise
  // then filter the files that the user does not own, or has shared / is a group folder
  return (0,_Files_ts__WEBPACK_IMPORTED_MODULE_1__.getContents)(path, options).then(content => {
    content.contents = content.contents.filter(isPersonalFile);
    return content;
  });
}

/***/ },

/***/ "./apps/files/src/services/Recent.ts"
/*!*******************************************!*\
  !*** ./apps/files/src/services/Recent.ts ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_userconfig_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/userconfig.ts */ "./apps/files/src/store/userconfig.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");








const lastTwoWeeksTimestamp = Math.round(Date.now() / 1000 - 60 * 60 * 24 * 14);
const recentLimit = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files', 'recent_limit', 100);
/**
 * Get recently changed nodes
 *
 * This takes the users preference about hidden files into account.
 * If hidden files are not shown, then also recently changed files *in* hidden directories are filtered.
 *
 * @param path Path to search for recent changes
 * @param options Options including abort signal
 * @param options.signal Abort signal to cancel the request
 */
async function getContents(path = '/', options) {
  const store = (0,_store_userconfig_ts__WEBPACK_IMPORTED_MODULE_5__.useUserConfigStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_4__.getPinia)());
  /**
   * Filter function that returns only the visible nodes - or hidden if explicitly configured
   *
   * @param node The node to check
   */
  const filterHidden = node => path !== '/' // We need to hide files from hidden directories in the root if not configured to show
  || store.userConfig.show_hidden // If configured to show hidden files we can early return
  || !node.dirname.split('/').some(dir => dir.startsWith('.')); // otherwise only include the file if non of the parent directories is hidden
  try {
    const contentsResponse = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_7__.client.search('/', {
      signal: options.signal,
      details: true,
      data: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRecentSearch)(lastTwoWeeksTimestamp, recentLimit)
    });
    const contents = contentsResponse.data.results.map(stat => {
      // The search endpoint already includes the dav remote URL so we must not include it in the source
      stat.filename = stat.filename.replace('/remote.php/dav', '');
      return (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.resultToNode)(stat);
    }).filter(filterHidden);
    return {
      folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
        id: 0,
        source: `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRemoteURL)()}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)()}`,
        root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)(),
        owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ
      }),
      contents
    };
  } catch (error) {
    if (options.signal.aborted) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__.logger.info('Fetching recent files aborted');
      throw new DOMException('Aborted', 'AbortError');
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__.logger.error('Failed to fetch recent files', {
      error
    });
    throw error;
  }
}

/***/ },

/***/ "./apps/files/src/services/Search.ts"
/*!*******************************************!*\
  !*** ./apps/files/src/services/Search.ts ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/search.ts */ "./apps/files/src/store/search.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./WebDavSearch.ts */ "./apps/files/src/services/WebDavSearch.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







/**
 * Get the contents for a search view
 *
 * @param path - (not used)
 * @param options - Options including abort signal
 * @param options.signal - Abort signal to cancel the request
 */
async function getContents(path, options) {
  const searchStore = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_4__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_3__.getPinia)());
  try {
    const contents = await (0,_WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_6__.searchNodes)(searchStore.query, {
      signal: options.signal
    });
    return {
      contents,
      folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
        id: 0,
        source: `${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRemoteURL}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)()}}#search`,
        owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ,
        root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)()
      })
    };
  } catch (error) {
    if (options.signal.aborted) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.info('Fetching search results aborted');
      throw new DOMException('Aborted', 'AbortError');
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.error('Failed to fetch search results', {
      error
    });
    throw error;
  }
}

/***/ },

/***/ "./apps/files/src/services/WebDavSearch.ts"
/*!*************************************************!*\
  !*** ./apps/files/src/services/WebDavSearch.ts ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   searchNodes: () => (/* binding */ searchNodes)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
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
async function searchNodes(query, {
  dir,
  signal
}) {
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
  _utils_logger_ts__WEBPACK_IMPORTED_MODULE_3__.logger.debug('Searching for nodes', {
    query,
    dir
  });
  const {
    data
  } = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__.client.search('/', {
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

/***/ },

/***/ "./apps/files/src/services/WebdavClient.ts"
/*!*************************************************!*\
  !*** ./apps/files/src/services/WebdavClient.ts ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   client: () => (/* binding */ client),
/* harmony export */   fetchNode: () => (/* binding */ fetchNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const client = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getClient)();
/**
 * Fetches a node from the given path
 *
 * @param path - The path to fetch the node from
 */
async function fetchNode(path) {
  const propfindPayload = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getDefaultPropfind)();
  const result = await client.stat(`${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getRootPath)()}${path}`, {
    details: true,
    data: propfindPayload
  });
  return (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(result.data);
}

/***/ },

/***/ "./apps/files/src/store/active.ts"
/*!****************************************!*\
  !*** ./apps/files/src/store/active.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useActiveStore: () => (/* binding */ useActiveStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







// Temporary fake folder to use until we have the first valid folder
// fetched and cached. This allow us to mount the FilesListVirtual
// at all time and avoid unmount/mount and undesired rendering issues.
const dummyFolder = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Folder({
  id: 0,
  source: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRemoteURL)() + (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)(),
  root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)(),
  owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
  permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.NONE
});
const useActiveStore = (0,pinia__WEBPACK_IMPORTED_MODULE_4__.defineStore)('active', () => {
  /**
   * The currently active action
   */
  const activeAction = (0,vue__WEBPACK_IMPORTED_MODULE_5__.shallowRef)();
  /**
   * The current active node within the folder
   */
  const activeNode = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)();
  /**
   * The current active view
   */
  const activeView = (0,vue__WEBPACK_IMPORTED_MODULE_5__.shallowRef)();
  /**
   * The currently active folder
   */
  const activeFolder = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)(dummyFolder);
  // Set the active node on the router params
  (0,vue__WEBPACK_IMPORTED_MODULE_5__.watch)(activeNode, () => {
    if (typeof activeNode.value?.fileid !== 'number' || activeNode.value.fileid === activeFolder.value?.fileid) {
      return;
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__.logger.debug('Updating active fileid in URL query', {
      fileid: activeNode.value.fileid
    });
    window.OCP.Files.Router.goToRoute(null, {
      ...window.OCP.Files.Router.params,
      fileid: String(activeNode.value.fileid)
    }, {
      ...window.OCP.Files.Router.query
    }, true);
  });
  initialize();
  /**
   * Unset the active node if deleted
   *
   * @param node - The node thats deleted
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
   */
  function onChangedView(view = null) {
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_6__.logger.debug('Setting active view', {
      view
    });
    activeView.value = view ?? undefined;
    activeNode.value = undefined;
  }
  /**
   * Initalize the store - connect all event listeners.
   *
   */
  function initialize() {
    const navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)();
    onChangedView(navigation.active);
    // Make sure we only register the listeners once
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:node:deleted', onDeletedNode);
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

/***/ },

/***/ "./apps/files/src/store/files.ts"
/*!***************************************!*\
  !*** ./apps/files/src/store/files.ts ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useFilesStore: () => (/* binding */ useFilesStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _paths_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./paths.ts */ "./apps/files/src/store/paths.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






/**
 * Store for files and folders in the files app.
 */
const useFilesStore = (0,pinia__WEBPACK_IMPORTED_MODULE_1__.defineStore)('files', () => {
  const files = (0,vue__WEBPACK_IMPORTED_MODULE_2__.ref)({});
  const roots = (0,vue__WEBPACK_IMPORTED_MODULE_2__.ref)({});
  // initialize the store once its used first time
  initalizeStore();
  /**
   * Get a file or folder by its source
   *
   * @param source - The file source
   */
  function getNode(source) {
    return files.value[source];
  }
  /**
   * Get a list of files or folders by their IDs
   * Note: does not return undefined values
   *
   * @param sources - The file sources
   */
  function getNodes(sources) {
    return sources.map(source => files.value[source]).filter(Boolean);
  }
  /**
   * Get files or folders by their ID
   * Multiple nodes can have the same ID but different sources
   * (e.g. in a shared context)
   *
   * @param id - The file ID
      */
  function getNodesById(id) {
    return Object.values(files.value).filter(node => node.id === id);
  }
  /**
   * Get the root folder of a service
   *
   * @param service - The service (files view)
   * @return The root folder if set
   */
  function getRoot(service) {
    return roots.value[service];
  }
  /**
   * Get cached directory matching a given path
   *
   * @param service - The service (files view)
   * @param path - The path relative within the service
   * @return The folder if found
   */
  function getDirectoryByPath(service, path) {
    const pathsStore = (0,_paths_ts__WEBPACK_IMPORTED_MODULE_5__.usePathsStore)();
    let folder;
    // Get the containing folder from path store
    if (!path || path === '/') {
      folder = getRoot(service);
    } else {
      const source = pathsStore.getPath(service, path);
      if (source) {
        folder = getNode(source);
      }
    }
    return folder;
  }
  /**
   * Get cached child nodes within a given path
   *
   * @param service - The service (files view)
   * @param path - The path relative within the service
   * @return Array of cached nodes within the path
   */
  function getNodesByPath(service, path) {
    const folder = getDirectoryByPath(service, path);
    // If we found a cache entry and the cache entry was already loaded (has children) then use it
    return (folder?._children ?? []).map(source => getNode(source)).filter(Boolean);
  }
  /**
   * Update or set nodes in the store
   *
   * @param nodes - The nodes to update or set
   */
  function updateNodes(nodes) {
    // Update the store all at once
    const newNodes = nodes.reduce((acc, node) => {
      if (files.value[node.source]?.id && !node.id) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.error('Trying to update/set a node without id', {
          node
        });
        return acc;
      }
      acc[node.source] = node;
      return acc;
    }, {});
    files.value = {
      ...files.value,
      ...newNodes
    };
  }
  /**
   * Delete nodes from the store
   *
   * @param nodes - The nodes to delete
   */
  function deleteNodes(nodes) {
    const entries = Object.entries(files.value).filter(([, node]) => !nodes.some(n => n.source === node.source));
    files.value = Object.fromEntries(entries);
  }
  /**
   * Set the root folder for a service
   *
   * @param options - The options for setting the root
   * @param options.service - The service (files view)
   * @param options.root - The root folder
   */
  function setRoot({
    service,
    root
  }) {
    roots.value = {
      ...roots.value,
      [service]: root
    };
  }
  return {
    files,
    roots,
    deleteNodes,
    getDirectoryByPath,
    getNode,
    getNodes,
    getNodesById,
    getNodesByPath,
    getRoot,
    setRoot,
    updateNodes
  };
  // Internal helper functions
  /**
   * Initialize the store by subscribing to events
   */
  function initalizeStore() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:created', onCreatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:deleted', onDeletedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:updated', onUpdatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:moved', onMovedNode);
    // legacy sidebar
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:favorites:added', onAddFavorite);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:favorites:removed', onRemoveFavorite);
  }
  /**
   * Called when a node is deleted, removes the node from the store
   *
   * @param node - The deleted node
   */
  function onDeletedNode(node) {
    deleteNodes([node]);
  }
  /**
   * Handler for when a node is created
   *
   * @param node - The created node
   */
  function onCreatedNode(node) {
    updateNodes([node]);
  }
  /**
   * Handler for when a node is moved, updates the path of the node in the store
   *
   * @param context - The context of the moved node
   * @param context.node - The moved node
   * @param context.oldSource - The old source of the node before it was moved
   */
  function onMovedNode({
    node,
    oldSource
  }) {
    // Update the path of the node
    delete files.value[oldSource];
    updateNodes([node]);
  }
  /**
   * Handler for when a node is updated, updates the node in the store
   *
   * @param node - The updated node
   */
  async function onUpdatedNode(node) {
    // If we have multiple nodes with the same file ID, we need to update all of them
    const nodes = node.id ? getNodesById(node.id) : getNodes([node.source]);
    if (nodes.length > 1) {
      await Promise.all(nodes.map(node => (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__.fetchNode)(node.path))).then(updateNodes);
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug(nodes.length + ' nodes updated in store', {
        fileid: node.id,
        source: node.source
      });
      return;
    }
    // If we have only one node with the file ID, we can update it directly
    if (nodes.length === 1 && node.source === nodes[0].source) {
      updateNodes([node]);
      return;
    }
    // Otherwise, it means we receive an event for a node that is not in the store
    (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__.fetchNode)(node.path).then(n => updateNodes([n]));
  }
  /**
   * Handlers for legacy sidebar (no real nodes support)
   *
   * @param node - The node that was added to favorites
   */
  function onAddFavorite(node) {
    const ourNode = getNode(node.source);
    if (ourNode) {
      vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(ourNode.attributes, 'favorite', 1);
    }
  }
  /**
   * Handler for when a node is removed from favorites
   *
   * @param node - The removed favorite
   */
  function onRemoveFavorite(node) {
    const ourNode = getNode(node.source);
    if (ourNode) {
      vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(ourNode.attributes, 'favorite', 0);
    }
  }
});

/***/ },

/***/ "./apps/files/src/store/index.ts"
/*!***************************************!*\
  !*** ./apps/files/src/store/index.ts ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getPinia: () => (/* binding */ getPinia)
/* harmony export */ });
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Get the Pinia instance for the Files app.
 */
function getPinia() {
  if (window._nc_files_pinia) {
    return window._nc_files_pinia;
  }
  window._nc_files_pinia = (0,pinia__WEBPACK_IMPORTED_MODULE_0__.createPinia)();
  return window._nc_files_pinia;
}

/***/ },

/***/ "./apps/files/src/store/paths.ts"
/*!***************************************!*\
  !*** ./apps/files/src/store/paths.ts ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   usePathsStore: () => (/* binding */ usePathsStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./files.ts */ "./apps/files/src/store/files.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







/**
 *
 * @param args
 */
function usePathsStore(...args) {
  const files = (0,_files_ts__WEBPACK_IMPORTED_MODULE_6__.useFilesStore)(...args);
  const store = (0,pinia__WEBPACK_IMPORTED_MODULE_3__.defineStore)('paths', {
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
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)()?.active?.id || 'files';
        if (!node.fileid) {
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.error('Node has no fileid', {
            node
          });
          return;
        }
        // Only add path if it's a folder
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
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
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)()?.active?.id || 'files';
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
          // Delete the path
          this.deletePath(service, node.path);
        }
        this.deleteNodeFromParentChildren(node);
      },
      onMovedNode({
        node,
        oldSource
      }) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)()?.active?.id || 'files';
        // Update the path of the node
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
          // Delete the old path if it exists
          const oldPath = Object.entries(this.paths[service]).find(([, source]) => source === oldSource);
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
        const oldNode = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.File({
          source: oldSource,
          owner: node.owner,
          mime: node.mime,
          root: node.root
        });
        this.deleteNodeFromParentChildren(oldNode);
        this.addNodeToParentChildren(node);
      },
      deleteNodeFromParentChildren(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)()?.active?.id || 'files';
        // Update children of a root folder
        const parentSource = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.dirname)(node.source);
        const folder = node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource);
        if (folder) {
          // ensure sources are unique
          const children = new Set(folder._children ?? []);
          children.delete(node.source);
          vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(folder, '_children', [...children.values()]);
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.debug('Children updated', {
            parent: folder,
            node,
            children: folder._children
          });
          return;
        }
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.debug('Parent path does not exists, skipping children update', {
          node
        });
      },
      addNodeToParentChildren(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)()?.active?.id || 'files';
        // Update children of a root folder
        const parentSource = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.dirname)(node.source);
        const folder = node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource);
        if (folder) {
          // ensure sources are unique
          const children = new Set(folder._children ?? []);
          children.add(node.source);
          vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(folder, '_children', [...children.values()]);
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.debug('Children updated', {
            parent: folder,
            node,
            children: folder._children
          });
          return;
        }
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.debug('Parent path does not exists, skipping children update', {
          node
        });
      }
    }
  });
  const pathsStore = store(...args);
  // Make sure we only register the listeners once
  if (!pathsStore._initialized) {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:created', pathsStore.onCreatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:deleted', pathsStore.onDeletedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:moved', pathsStore.onMovedNode);
    pathsStore._initialized = true;
  }
  return pathsStore;
}

/***/ },

/***/ "./apps/files/src/store/search.ts"
/*!****************************************!*\
  !*** ./apps/files/src/store/search.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useSearchStore: () => (/* binding */ useSearchStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _views_search_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../views/search.ts */ "./apps/files/src/views/search.ts");
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
   *
   */
  const updateRouter = (0,debounce__WEBPACK_IMPORTED_MODULE_1__["default"])(isSearch => {
    const router = window.OCP.Files.Router;
    router.goToRoute(undefined, {
      view: _views_search_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID
    }, {
      query: query.value
    }, isSearch);
  });
  /**
   * Handle updating the filter if needed.
   * Also update the search view by updating the current route if needed.
   *
   */
  function updateSearch() {
    // emit the search event to update the filter
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:search:updated', {
      query: query.value,
      scope: scope.value
    });
    const router = window.OCP.Files.Router;
    // if we are on the search view and the query was unset or scope was set to 'filter' we need to move back to the files view
    if (router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID && (query.value === '' || scope.value === 'filter')) {
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
    const isSearch = router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID;
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('Update route for updated search query', {
      query: query.value,
      isSearch
    });
    updateRouter(isSearch);
  }
  /**
   * Event handler that resets the store if the file list view was changed.
   *
   * @param view - The new view that is active
   */
  function onViewChanged(view) {
    if (view.id !== _views_search_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID) {
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
    if (router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID) {
      query.value = [router.query.query].flat()[0] ?? '';
      if (query.value) {
        scope.value = 'globally';
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('Directly navigated to search view', {
          query: query.value
        });
      } else {
        // we do not have any query so we need to move to the files list
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.info('Directly navigated to search view without any query, redirect to files view.');
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

/***/ },

/***/ "./apps/files/src/store/userconfig.ts"
/*!********************************************!*\
  !*** ./apps/files/src/store/userconfig.ts ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useUserConfigStore: () => (/* binding */ useUserConfigStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const initialUserConfig = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files', 'config', {
  crop_image_previews: true,
  default_view: 'files',
  folder_tree: true,
  grid_view: false,
  show_files_extensions: true,
  show_hidden: false,
  show_mime_column: true,
  sort_favorites_first: true,
  sort_folders_first: true,
  show_dialog_deletion: false,
  show_dialog_file_extension: true
});
const useUserConfigStore = (0,pinia__WEBPACK_IMPORTED_MODULE_5__.defineStore)('userconfig', () => {
  const userConfig = (0,vue__WEBPACK_IMPORTED_MODULE_6__.ref)({
    ...initialUserConfig
  });
  /**
   * Update the user config local store
   *
   * @param key The config key
   * @param value The new value
   */
  function onUpdate(key, value) {
    (0,vue__WEBPACK_IMPORTED_MODULE_6__.set)(userConfig.value, key, value);
  }
  /**
   * Update the user config local store AND on server side
   *
   * @param key The config key
   * @param value The new value
   */
  async function update(key, value) {
    // only update if a user is logged in (not the case for public shares)
    if ((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)() !== null) {
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/apps/files/api/v1/config/{key}', {
        key
      }), {
        value
      });
    }
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:config:updated', {
      key,
      value
    });
  }
  // Register the event listener
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:config:updated', ({
    key,
    value
  }) => onUpdate(key, value));
  return {
    userConfig,
    update
  };
});

/***/ },

/***/ "./apps/files/src/utils/filenameValidity.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/utils/filenameValidity.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
 *
 * @param name The filename
 * @param escape Escape the matched string in the error (only set when used in HTML)
 */
function getFilenameValidity(name, escape = false) {
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

/***/ },

/***/ "./apps/files/src/utils/filesViews.ts"
/*!********************************************!*\
  !*** ./apps/files/src/utils/filesViews.ts ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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

/***/ },

/***/ "./apps/files/src/utils/hashUtils.ts"
/*!*******************************************!*\
  !*** ./apps/files/src/utils/hashUtils.ts ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
 *
 * @param str The string to hash
 * @return a non secure hash of the string
 */
function hashCode(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = (hash << 5) - hash + str.charCodeAt(i) | 0;
  }
  return hash >>> 0;
}

/***/ },

/***/ "./apps/files/src/utils/logger.ts"
/*!****************************************!*\
  !*** ./apps/files/src/utils/logger.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: () => (/* binding */ logger)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files').detectUser().build();

/***/ },

/***/ "./apps/files/src/utils/newNodeDialog.ts"
/*!***********************************************!*\
  !*** ./apps/files/src/utils/newNodeDialog.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   newNodeName: () => (/* binding */ newNodeName)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_functions_dialog__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/functions/dialog */ "./node_modules/@nextcloud/vue/dist/Functions/dialog.mjs");
/* harmony import */ var _components_NewNodeDialog_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/NewNodeDialog.vue */ "./apps/files/src/components/NewNodeDialog.vue");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 * Ask user for file or folder name
 *
 * @param defaultName Default name to use
 * @param folderContent Nodes with in the current folder to check for unique name
 * @param labels Labels to set on the dialog
 * @return string if successful otherwise null if aborted
 */
function newNodeName(defaultName, folderContent, labels = {}) {
  const contentNames = folderContent.map(node => node.basename);
  return new Promise(resolve => {
    (0,_nextcloud_vue_functions_dialog__WEBPACK_IMPORTED_MODULE_0__.spawnDialog)(_components_NewNodeDialog_vue__WEBPACK_IMPORTED_MODULE_1__["default"], {
      ...labels,
      defaultName,
      otherNames: contentNames
    }, folderName => {
      resolve(folderName);
    });
  });
}

/***/ },

/***/ "./apps/files/src/utils/permissions.ts"
/*!*********************************************!*\
  !*** ./apps/files/src/utils/permissions.ts ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isDownloadable: () => (/* binding */ isDownloadable),
/* harmony export */   isSyncable: () => (/* binding */ isSyncable)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Check permissions on the node if it can be downloaded
 *
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
    const downloadAttribute = shareAttributes.find(({
      scope,
      key
    }) => scope === 'permissions' && key === 'download');
    if (downloadAttribute !== undefined) {
      return downloadAttribute.value === true;
    }
  }
  return true;
}
/**
 * Check permissions on the node if it can be synced/open locally
 *
 * @param node The node to check
 * @return True if syncable, false otherwise
 */
function isSyncable(node) {
  if (!node.isDavResource) {
    return false;
  }
  if ((node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.WRITE) === 0) {
    return false;
  }
  // Syncable has the same permissions as downloadable for now
  return isDownloadable(node);
}

/***/ },

/***/ "./apps/files/src/views/favorites.ts"
/*!*******************************************!*\
  !*** ./apps/files/src/views/favorites.ts ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFavoritesView: () => (/* binding */ registerFavoritesView)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/star-outline.svg?raw */ "./node_modules/@mdi/svg/svg/star-outline.svg?raw");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_Favorites_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/Favorites.ts */ "./apps/files/src/services/Favorites.ts");
/* harmony import */ var _utils_hashUtils_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/hashUtils.ts */ "./apps/files/src/utils/hashUtils.ts");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








/**
 * Generate a favorite folder view
 *
 * @param folder - The folder to generate the view for
 * @param index - The order index
 */
function generateFavoriteFolderView(folder, index = 0) {
  return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.View({
    id: generateIdFromPath(folder.path),
    name: folder.displayname,
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    order: index,
    params: {
      dir: folder.path,
      fileid: String(folder.fileid),
      view: 'favorites'
    },
    parent: 'favorites',
    columns: [],
    getContents: _services_Favorites_ts__WEBPACK_IMPORTED_MODULE_5__.getContents
  });
}
/**
 * Generate a unique id from the folder path
 *
 * @param path - The folder path
 */
function generateIdFromPath(path) {
  return `favorite-${(0,_utils_hashUtils_ts__WEBPACK_IMPORTED_MODULE_6__.hashCode)(path)}`;
}
/**
 * Register the favorites view and setup event listeners to update it
 */
async function registerFavoritesView() {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.View({
    id: 'favorites',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Favorites'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'List of favorite files and folders.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'No favorites yet'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files', 'Files and folders you mark as favorite will show up here'),
    icon: _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__,
    order: 15,
    columns: [],
    getContents: _services_Favorites_ts__WEBPACK_IMPORTED_MODULE_5__.getContents
  }));
  const controller = new AbortController();
  const favoriteFolders = (await (0,_services_Favorites_ts__WEBPACK_IMPORTED_MODULE_5__.getContents)('', {
    signal: controller.signal
  })).contents.filter(node => node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder);
  const favoriteFoldersViews = favoriteFolders.map((folder, index) => generateFavoriteFolderView(folder, index));
  _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.debug('Generating favorites view', {
    favoriteFolders
  });
  favoriteFoldersViews.forEach(view => Navigation.register(view));
  /**
   * Update favorites navigation when a new folder is added
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:favorites:added', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
      return;
    }
    // Sanity check
    if (node.path === null || !node.root?.startsWith('/files')) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Favorite folder is not within user files root', {
        node
      });
      return;
    }
    addToFavorites(node);
  });
  /**
   * Remove favorites navigation when a folder is removed
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:favorites:removed', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
      return;
    }
    // Sanity check
    if (node.path === null || !node.root?.startsWith('/files')) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_7__.logger.error('Favorite folder is not within user files root', {
        node
      });
      return;
    }
    removePathFromFavorites(node.path);
  });
  /**
   * Update favorites navigation when a folder is renamed
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:node:renamed', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
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
    favoriteFolders.sort((a, b) => a.basename.localeCompare(b.basename, [(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.getCanonicalLocale)()], {
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
  /**
   * Add a folder to the favorites paths array and update the views
   *
   * @param node - The folder node
   */
  function addToFavorites(node) {
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
  }
  /**
   * Remove a folder from the favorites paths array and update the views
   *
   * @param path - The folder path
   */
  function removePathFromFavorites(path) {
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
  }
  /**
   * Update a folder from the favorites paths array and update the views
   *
   * @param node - The updated folder node
   */
  function updateNodeFromFavorites(node) {
    const favoriteFolder = favoriteFolders.find(folder => folder.fileid === node.fileid);
    // Skip if it does not exists
    if (favoriteFolder === undefined) {
      return;
    }
    removePathFromFavorites(favoriteFolder.path);
    addToFavorites(node);
  }
  updateAndSortViews();
}

/***/ },

/***/ "./apps/files/src/views/files.ts"
/*!***************************************!*\
  !*** ./apps/files/src/views/files.ts ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerFilesView: () => (/* binding */ registerFilesView)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_Files_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/Files.ts */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _store_active_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/active.ts */ "./apps/files/src/store/active.ts");
/* harmony import */ var _utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/filesViews.ts */ "./apps/files/src/utils/filesViews.ts");
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
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'All files'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'List of your files and folders.'),
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    // if this is the default view we set it at the top of the list - otherwise below it
    order: (0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_6__.defaultView)() === VIEW_ID ? 0 : 5,
    getContents: _services_Files_ts__WEBPACK_IMPORTED_MODULE_4__.getContents
  }));
  // when the search is updated
  // and we are in the files view
  // and there is already a folder fetched
  // then we "update" it to trigger a new `getContents` call to search for the query while the filelist is filtered
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:search:updated', ({
    scope,
    query
  }) => {
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
    const store = (0,_store_active_ts__WEBPACK_IMPORTED_MODULE_5__.useActiveStore)();
    if (!store.activeFolder) {
      return;
    }
    oldQuery = query;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:updated', store.activeFolder);
  });
}

/***/ },

/***/ "./apps/files/src/views/folderTree.ts"
/*!********************************************!*\
  !*** ./apps/files/src/views/folderTree.ts ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFolderTreeView: () => (/* binding */ registerFolderTreeView)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-multiple-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-multiple-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../services/FolderTree.ts */ "./apps/files/src/services/FolderTree.ts");
/* harmony import */ var _store_files_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../store/files.ts */ "./apps/files/src/store/files.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.getNavigation)();
const queue = new p_queue__WEBPACK_IMPORTED_MODULE_7__["default"]({
  concurrency: 5,
  intervalCap: 5,
  interval: 200
});
const isFolderTreeEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('files', 'config', {
  folder_tree: true
}).folder_tree;
let showHiddenFiles = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('files', 'config', {
  show_hidden: false
}).show_hidden;
const folderTreeView = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.View({
  id: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId,
  name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'Folder tree'),
  caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files', 'List of your files and folders.'),
  icon: _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  order: 50,
  // Below all other views
  getContents: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getContents,
  async loadChildViews(view) {
    const treeView = view;
    if (treeView.loading || treeView.loaded) {
      return;
    }
    treeView.loading = true;
    try {
      const dir = new URLSearchParams(window.location.search).get('dir') ?? '/';
      const tree = await (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getFolderTreeNodes)(dir, 1, true);
      registerNodeViews(tree, dir);
      treeView.loaded = true;
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:node:created', onCreateNode);
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:node:deleted', onDeleteNode);
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:node:moved', onMoveNode);
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('files:config:updated', onUserConfigUpdated);
    } finally {
      treeView.loading = false;
    }
  }
});
/**
 * Register the folder tree feature
 */
async function registerFolderTreeView() {
  if (!isFolderTreeEnabled) {
    return;
  }
  Navigation.register(folderTreeView);
}
/**
 * Helper to register node views in the navigation.
 *
 * @param nodes - The nodes to register
 * @param path - The path to expand by default, if any
 */
async function registerNodeViews(nodes, path) {
  const views = [];
  for (const node of nodes) {
    const isRegistered = Navigation.views.some(view => view.id === `${_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId}::${node.encodedSource}`);
    // skip hidden files if the setting is disabled
    if (!showHiddenFiles && node.basename.startsWith('.')) {
      if (isRegistered) {
        // and also remove any existing views for hidden files if the setting was toggled
        Navigation.remove(`${_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId}::${node.encodedSource}`);
      }
      continue;
    }
    // skip already registered views to avoid duplicates when loading multiple levels
    if (isRegistered) {
      continue;
    }
    views.push(generateNodeView(node, path === node.path || path?.startsWith(node.path + '/') ? true : undefined));
  }
  Navigation.register(...views);
}
/**
 * Generates a navigation view for a given folder tree node or folder.
 *
 * @param node - The folder tree node or folder for which to generate the view.
 * @param expanded - Whether the view should be expanded by default.
 */
function generateNodeView(node, expanded) {
  return {
    id: `${_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId}::${node.encodedSource}`,
    parent: (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getSourceParent)(node.source),
    expanded,
    loaded: expanded,
    // @ts-expect-error Casing differences
    name: node.displayName ?? node.displayname ?? node.basename,
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__,
    getContents: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getContents,
    loadChildViews: getLoadChildViews(node),
    params: {
      view: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId,
      fileid: String(node.fileid),
      // Needed for matching exact routes
      dir: node.path
    }
  };
}
/**
 * Generates a function to load child views for a given folder tree node or folder.
 * This function is used as the `loadChildViews` callback in the navigation view.
 *
 * @param node - The folder tree node or folder for which to generate the child view loader function.
 */
function getLoadChildViews(node) {
  return async view => {
    const treeView = view;
    if (treeView.loading || treeView.loaded) {
      return;
    }
    treeView.loading = true;
    try {
      await updateTreeChildren(node.path);
      treeView.loaded = true;
    } finally {
      treeView.loading = false;
    }
  };
}
/**
 * Registers child views for the given path. If no path is provided, it registers the root nodes.
 *
 * @param path - The path for which to register child views. Defaults to '/' for root nodes.
 */
async function updateTreeChildren(path = '/') {
  await queue.add(async () => {
    const filesStore = (0,_store_files_ts__WEBPACK_IMPORTED_MODULE_9__.useFilesStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_10__.getPinia)());
    const cachedNodes = filesStore.getNodesByPath(Navigation.active.id, path);
    if (cachedNodes.length > 0) {
      // if there are nodes loaded in the path we dont need to fetch from API
      const folders = cachedNodes.filter(node => node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder);
      registerNodeViews(folders, path);
    } else {
      // otherwise we need to fetch the tree nodes for the path
      const nodes = await (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getFolderTreeNodes)(path, 2);
      registerNodeViews(nodes);
    }
  });
}
/**
 * Remove a folder view from the navigation.
 *
 * @param folder - The folder for which to remove the view
 */
function removeFolderView(folder) {
  const viewId = folder.encodedSource;
  Navigation.remove(viewId);
}
/**
 * Remove a folder view from the navigation by its source URL.
 *
 * @param source - The source URL of the folder for which to remove the view
 */
function removeFolderViewSource(source) {
  Navigation.remove(source);
}
/**
 * Handle node creation events to add new folder tree views to the navigation.
 *
 * @param node - The node that was created
 */
function onCreateNode(node) {
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
    return;
  }
  registerNodeViews([node]);
}
/**
 * Handle node deletion events to remove the corresponding folder tree views from the navigation.
 *
 * @param node - The node that was deleted
 */
function onDeleteNode(node) {
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
    return;
  }
  removeFolderView(node);
}
/**
 * Handle node move events to update the folder tree views accordingly.
 *
 * @param context - the event context
 * @param context.node - The node that was moved
 * @param context.oldSource - the old source URL of the moved node
 */
function onMoveNode({
  node,
  oldSource
}) {
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
    return;
  }
  removeFolderViewSource(oldSource);
  registerNodeViews([node]);
  const newPath = node.source.replace(_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.sourceRoot, '');
  const oldPath = oldSource.replace(_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.sourceRoot, '');
  const childViews = Navigation.views.filter(view => {
    if (!view.params?.dir) {
      return false;
    }
    if ((0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_6__.isSamePath)(view.params.dir, oldPath)) {
      return false;
    }
    return view.params.dir.startsWith(oldPath);
  });
  for (const view of childViews) {
    view.parent = (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getSourceParent)(node.source);
    view.params.dir = view.params.dir.replace(oldPath, newPath);
  }
}
/**
 * Handle user config updates, specifically for the "show hidden files" setting,
 * to show hidden folders in the folder tree when enabled and hide them when disabled.
 *
 * @param context - the event context
 * @param context.key - the key of the updated config
 * @param context.value - the new value of the updated config
 */
async function onUserConfigUpdated({
  key,
  value
}) {
  if (key === 'show_hidden') {
    showHiddenFiles = value;
    await updateTreeChildren();
  }
}

/***/ },

/***/ "./apps/files/src/views/personal-files.ts"
/*!************************************************!*\
  !*** ./apps/files/src/views/personal-files.ts ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerPersonalFilesView: () => (/* binding */ registerPersonalFilesView)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/account-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-outline.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_PersonalFiles_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/PersonalFiles.ts */ "./apps/files/src/services/PersonalFiles.ts");
/* harmony import */ var _utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/filesViews.ts */ "./apps/files/src/utils/filesViews.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const VIEW_ID = 'personal';
/**
 * Register the personal files view if allowed
 */
function registerPersonalFilesView() {
  if (!(0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_4__.hasPersonalFilesView)()) {
    return;
  }
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Personal files'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'List of your files and folders that are not shared.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'No personal files found'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Files that are not shared will show up here.'),
    icon: _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    // if this is the default view we set it at the top of the list - otherwise default position of fifth
    order: (0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_4__.defaultView)() === VIEW_ID ? 0 : 5,
    getContents: _services_PersonalFiles_ts__WEBPACK_IMPORTED_MODULE_3__.getContents
  }));
}

/***/ },

/***/ "./apps/files/src/views/recent.ts"
/*!****************************************!*\
  !*** ./apps/files/src/views/recent.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/history.svg?raw */ "./node_modules/@mdi/svg/svg/history.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_Recent_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/Recent.ts */ "./apps/files/src/services/Recent.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'recent',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Recent'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'List of recently modified files and folders.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'No recently modified files'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Files and folders you recently modified will show up here.'),
    icon: _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    order: 10,
    defaultSortKey: 'mtime',
    getContents: _services_Recent_ts__WEBPACK_IMPORTED_MODULE_3__.getContents
  }));
});

/***/ },

/***/ "./apps/files/src/views/search.ts"
/*!****************************************!*\
  !*** ./apps/files/src/views/search.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerSearchView: () => (/* binding */ registerSearchView)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_magnify_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/magnify.svg?raw */ "./node_modules/@mdi/svg/svg/magnify.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_Search_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/Search.ts */ "./apps/files/src/services/Search.ts");
/* harmony import */ var _files_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./files.ts */ "./apps/files/src/views/files.ts");
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
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Search'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Search results within your files.'),
    async emptyView(el) {
      if (!view) {
        view = (await Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_src_views_SearchEmptyView_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./SearchEmptyView.vue */ "./apps/files/src/views/SearchEmptyView.vue"))).default;
      } else {
        instance.$destroy();
      }
      instance = new vue__WEBPACK_IMPORTED_MODULE_3__["default"](view);
      instance.$mount(el);
    },
    icon: _mdi_svg_svg_magnify_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    order: 10,
    parent: _files_ts__WEBPACK_IMPORTED_MODULE_5__.VIEW_ID,
    // it should be shown expanded
    expanded: true,
    // this view is hidden by default and only shown when active
    hidden: true,
    getContents: _services_Search_ts__WEBPACK_IMPORTED_MODULE_4__.getContents
  }));
}

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts"
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");

const startOfToday = () => new Date().setHours(0, 0, 0, 0);
const startOfLastWeek = () => startOfToday() - 7 * 24 * 60 * 60 * 1000;
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
  filter: time => time > startOfLastWeek()
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
}, {
  id: 'custom',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Custom range'),
  timeRange: [new Date(startOfLastWeek()), new Date(startOfToday())],
  filter(time) {
    if (!this.timeRange) {
      return true;
    }
    const timeValue = new Date(time).getTime();
    return timeValue >= this.timeRange[0].getTime() && timeValue <= this.timeRange[1].getTime();
  }
}];




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilterModified',
  props: {
    filter: {
      type: null,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const selectedOption = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(selectedOption, preset => {
      if (selectedOption.value) {
        if (selectedOption.value.id === 'custom' && !timeRange.value) {
          timeRange.value = [new Date(startOfLastWeek()), new Date(startOfToday())];
          selectedOption.value.timeRange = [...timeRange.value];
        }
        props.filter.setPreset(selectedOption.value);
      } else {
        props.filter.setPreset();
      }
    });
    const timeRange = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(timeRange, () => {
      if (timeRange.value) {
        selectedOption.value.timeRange = [...timeRange.value];
        props.filter.setPreset(selectedOption.value);
      }
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(() => {
      selectedOption.value = props.filter.preset && timePresets.find(f => f.id === props.filter.preset.id);
      props.filter.addEventListener('reset', onReset);
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted)(() => {
      props.filter.removeEventListener('reset', onReset);
    });
    /**
     * Handler for resetting the filter
     */
    function onReset() {
      selectedOption.value = undefined;
      timeRange.value = undefined;
    }
    return {
      __sfc: true,
      startOfToday,
      startOfLastWeek,
      timePresets,
      props,
      selectedOption,
      timeRange,
      onReset,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
      NcDateTimePicker: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcDateTimePicker,
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts"
/*!**************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_svg_svg_file_document_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/file-document.svg?raw */ "./node_modules/@mdi/svg/svg/file-document.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_pdf_box_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/file-pdf-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_presentation_box_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/file-presentation-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_table_box_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/file-table-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-table-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/folder.svg?raw */ "./node_modules/@mdi/svg/svg/folder.svg?raw");
/* harmony import */ var _mdi_svg_svg_image_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/image.svg?raw */ "./node_modules/@mdi/svg/svg/image.svg?raw");
/* harmony import */ var _mdi_svg_svg_movie_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/movie.svg?raw */ "./node_modules/@mdi/svg/svg/movie.svg?raw");
/* harmony import */ var _mdi_svg_svg_music_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/music.svg?raw */ "./node_modules/@mdi/svg/svg/music.svg?raw");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");

/**
 * Available presets
 */
const typePresets = [{
  id: 'document',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Documents'),
  icon: colorize(_mdi_svg_svg_file_document_svg_raw__WEBPACK_IMPORTED_MODULE_1__, '#49abea'),
  mime: ['x-office/document']
}, {
  id: 'spreadsheet',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Spreadsheets'),
  icon: colorize(_mdi_svg_svg_file_table_box_svg_raw__WEBPACK_IMPORTED_MODULE_4__, '#9abd4e'),
  mime: ['x-office/spreadsheet']
}, {
  id: 'presentation',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Presentations'),
  icon: colorize(_mdi_svg_svg_file_presentation_box_svg_raw__WEBPACK_IMPORTED_MODULE_3__, '#f0965f'),
  mime: ['x-office/presentation']
}, {
  id: 'pdf',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'PDFs'),
  icon: colorize(_mdi_svg_svg_file_pdf_box_svg_raw__WEBPACK_IMPORTED_MODULE_2__, '#dc5047'),
  mime: ['application/pdf']
}, {
  id: 'folder',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Folders'),
  icon: colorize(_mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_5__, window.getComputedStyle(document.body).getPropertyValue('--color-primary-element')),
  mime: ['httpd/unix-directory']
}, {
  id: 'audio',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Audio'),
  icon: _mdi_svg_svg_music_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
  mime: ['audio']
}, {
  id: 'image',
  // TRANSLATORS: This is for filtering files, e.g. PNG or JPEG, so photos, drawings, or images in general
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Images'),
  icon: _mdi_svg_svg_image_svg_raw__WEBPACK_IMPORTED_MODULE_6__,
  mime: ['image']
}, {
  id: 'video',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_9__.t)('files', 'Videos'),
  icon: _mdi_svg_svg_movie_svg_raw__WEBPACK_IMPORTED_MODULE_7__,
  mime: ['video']
}];
/**
 * Helper to colorize an svg icon
 *
 * @param svg - the svg content
 * @param color - the color to apply
 */
function colorize(svg, color) {
  return svg.replace('<path ', `<path fill="${color}" `);
}












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilterType',
  props: {
    filter: {
      type: null,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const selectedOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(selectedOptions, () => {
      props.filter.setPresets([...selectedOptions.value]);
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(() => {
      props.filter.addEventListener('reset', resetFilter);
      props.filter.addEventListener('deselect', onDeselect);
      selectedOptions.value = typePresets.filter(({
        id
      }) => props.filter.presets.some(preset => preset.id === id));
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted)(() => {
      props.filter.removeEventListener('reset', resetFilter);
      props.filter.removeEventListener('deselect', onDeselect);
    });
    /**
     * Handler for reset event from filter
     */
    function resetFilter() {
      selectedOptions.value = [];
    }
    /**
     * Handle deselect event from filter
     *
     * @param event - The custom event
     */
    function onDeselect(event) {
      const option = typePresets.find(preset => preset.id === event.detail);
      if (option) {
        toggleOption(option, false);
      }
    }
    /**
     * Toggle option from selected option
     *
     * @param option The option to toggle
     * @param selected Whether the option is selected or not
     */
    function toggleOption(option, selected) {
      selectedOptions.value = selectedOptions.value.filter(o => o.id !== option.id);
      if (selected) {
        selectedOptions.value.push(option);
      }
    }
    return {
      __sfc: true,
      typePresets,
      colorize,
      props,
      selectedOptions,
      resetFilter,
      onDeselect,
      toggleOption,
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__["default"],
      NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_11__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts"
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _utils_filenameValidity_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/filenameValidity.ts */ "./apps/files/src/utils/filenameValidity.ts");










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
  setup(__props, {
    emit
  }) {
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
        validity.value = (0,_utils_filenameValidity_ts__WEBPACK_IMPORTED_MODULE_8__.getFilenameValidity)(localDefaultName.value.trim());
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
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("div", [_vm._l(_setup.timePresets, function (preset) {
    return _c(_setup.NcButton, {
      key: preset.id,
      attrs: {
        alignment: "start",
        pressed: preset === _setup.selectedOption,
        variant: "tertiary",
        wide: ""
      },
      on: {
        "update:pressed": function ($event) {
          $event ? _setup.selectedOption = preset : _setup.onReset();
        }
      }
    }, [_vm._v("\n\t\t" + _vm._s(preset.label) + "\n\t")]);
  }), _vm._v(" "), _setup.selectedOption?.id === "custom" ? _c(_setup.NcDateTimePicker, {
    attrs: {
      "append-to-body": "",
      "aria-label": _setup.t("files", "Custom date range"),
      type: "date-range"
    },
    model: {
      value: _setup.timeRange,
      callback: function ($$v) {
        _setup.timeRange = $$v;
      },
      expression: "timeRange"
    }
  }) : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2"
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2 ***!
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
  return _c("div", {
    class: _vm.$style.fileListFilterType
  }, _vm._l(_setup.typePresets, function (fileType) {
    return _c(_setup.NcButton, {
      key: fileType.id,
      attrs: {
        pressed: _setup.selectedOptions.includes(fileType),
        variant: "tertiary",
        alignment: "start",
        wide: ""
      },
      on: {
        "update:pressed": function ($event) {
          return _setup.toggleOption(fileType, $event);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c(_setup.NcIconSvgWrapper, {
            attrs: {
              svg: fileType.icon
            }
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(fileType.label) + "\n\t")]);
  }), 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true ***!
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
            variant: "primary",
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
      label: _vm.label
    },
    model: {
      value: _setup.localDefaultName,
      callback: function ($$v) {
        _setup.localDefaultName = $$v;
      },
      expression: "localDefaultName"
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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
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
.new-node-dialog__form[data-v-e6b9c05a] {
	/* Ensure the dialog does not jump when there is a validity error */
	min-height: calc(2 * var(--default-clickable-area));
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
._fileListFilterType_Aeab3 {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	width: 100%;
}
`, ""]);
// Exports
___CSS_LOADER_EXPORT___.locals = {
	"fileListFilterType": `_fileListFilterType_Aeab3`
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css */ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue"
/*!*****************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true");
/* harmony import */ var _FileListFilterModified_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilterModified_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
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

/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue"
/*!*************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue ***!
  \*************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=template&id=6c0e6dd2 */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2");
/* harmony import */ var _FileListFilterType_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
/* module decorator */ module = __webpack_require__.hmd(module);



;

var cssModules = {}
var disposed = false

function injectStyles (context) {
  if (disposed) return
  
        cssModules["$style"] = (_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"].locals || _FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"])
        Object.defineProperty(this, "$style", {
          configurable: true,
          get: function () {
            return cssModules["$style"]
          }
        })
      
}


  module.hot && 0



        module.hot && 0

/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilterType_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  injectStyles,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilterType.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files/src/components/NewNodeDialog.vue"
/*!*****************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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

/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts"
/*!****************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts"
/*!************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts"
/*!****************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true"
/*!***********************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true");


/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2"
/*!*******************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2 ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=template&id=6c0e6dd2 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2");


/***/ },

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true"
/*!***********************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true");


/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss"
/*!**************************************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss");


/***/ },

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css"
/*!*************************************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \*************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css");


/***/ },

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css"
/*!*********************************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css ***!
  \*********************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&module=true&lang=css");
 

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

"use strict";
module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

"use strict";
module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

"use strict";
module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

"use strict";
module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/account-outline.svg?raw"
/*!***********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-outline.svg?raw ***!
  \***********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-outline\" viewBox=\"0 0 24 24\"><path d=\"M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,6A2,2 0 0,0 10,8A2,2 0 0,0 12,10A2,2 0 0,0 14,8A2,2 0 0,0 12,6M12,13C14.67,13 20,14.33 20,17V20H4V17C4,14.33 9.33,13 12,13M12,14.9C9.03,14.9 5.9,16.36 5.9,17V18.1H18.1V17C18.1,16.36 14.97,14.9 12,14.9Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/arrow-down.svg?raw"
/*!******************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/arrow-down.svg?raw ***!
  \******************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-arrow-down\" viewBox=\"0 0 24 24\"><path d=\"M11,4H13V16L18.5,10.5L19.92,11.92L12,19.84L4.08,11.92L5.5,10.5L11,16V4Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/autorenew.svg?raw"
/*!*****************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/autorenew.svg?raw ***!
  \*****************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-autorenew\" viewBox=\"0 0 24 24\"><path d=\"M12,6V9L16,5L12,1V4A8,8 0 0,0 4,12C4,13.57 4.46,15.03 5.24,16.26L6.7,14.8C6.25,13.97 6,13 6,12A6,6 0 0,1 12,6M18.76,7.74L17.3,9.2C17.74,10.04 18,11 18,12A6,6 0 0,1 12,18V15L8,19L12,23V20A8,8 0 0,0 20,12C20,10.43 19.54,8.97 18.76,7.74Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/calendar-range-outline.svg?raw"
/*!******************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/calendar-range-outline.svg?raw ***!
  \******************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-calendar-range-outline\" viewBox=\"0 0 24 24\"><path d=\"M7 11H9V13H7V11M21 5V19C21 20.11 20.11 21 19 21H5C3.89 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H6V1H8V3H16V1H18V3H19C20.11 3 21 3.9 21 5M5 7H19V5H5V7M19 19V9H5V19H19M15 13H17V11H15V13M11 13H13V11H11V13Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/close.svg?raw"
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/close.svg?raw ***!
  \*************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-close\" viewBox=\"0 0 24 24\"><path d=\"M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/file-document.svg?raw"
/*!*********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-document.svg?raw ***!
  \*********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-document\" viewBox=\"0 0 24 24\"><path d=\"M13,9H18.5L13,3.5V9M6,2H14L20,8V20A2,2 0 0,1 18,22H6C4.89,22 4,21.1 4,20V4C4,2.89 4.89,2 6,2M15,18V16H6V18H15M18,14V12H6V14H18Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/file-outline.svg?raw"
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-outline.svg?raw ***!
  \********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-outline\" viewBox=\"0 0 24 24\"><path d=\"M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw"
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw ***!
  \********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-pdf-box\" viewBox=\"0 0 24 24\"><path d=\"M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M9.5 11.5C9.5 12.3 8.8 13 8 13H7V15H5.5V9H8C8.8 9 9.5 9.7 9.5 10.5V11.5M14.5 13.5C14.5 14.3 13.8 15 13 15H10.5V9H13C13.8 9 14.5 9.7 14.5 10.5V13.5M18.5 10.5H17V11.5H18.5V13H17V15H15.5V9H18.5V10.5M12 10.5H13V13.5H12V10.5M7 10.5H8V11.5H7V10.5Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw"
/*!*****************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw ***!
  \*****************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-presentation-box\" viewBox=\"0 0 24 24\"><path d=\"M19,16H5V8H19M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/file-table-box.svg?raw"
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-table-box.svg?raw ***!
  \**********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-table-box\" viewBox=\"0 0 24 24\"><path d=\"M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3M9 18H6V16H9V18M9 15H6V13H9V15M9 12H6V10H9V12M13 18H10V16H13V18M13 15H10V13H13V15M13 12H10V10H13V12Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/folder-eye-outline.svg?raw"
/*!**************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-eye-outline.svg?raw ***!
  \**************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-eye-outline\" viewBox=\"0 0 24 24\"><path d=\"M9.3 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4H10L12 6H20C21.1 6 22 6.9 22 8V14.6C21.4 14.2 20.7 13.8 20 13.5V8H4V18H9.3C9.3 18.1 9.2 18.2 9.2 18.3L8.8 19L9.1 19.7C9.2 19.8 9.2 19.9 9.3 20M23 19C22.1 21.3 19.7 23 17 23S11.9 21.3 11 19C11.9 16.7 14.3 15 17 15S22.1 16.7 23 19M19.5 19C19.5 17.6 18.4 16.5 17 16.5S14.5 17.6 14.5 19 15.6 21.5 17 21.5 19.5 20.4 19.5 19M17 18C16.4 18 16 18.4 16 19S16.4 20 17 20 18 19.6 18 19 17.6 18 17 18\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw"
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw ***!
  \***************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-move-outline\" viewBox=\"0 0 24 24\"><path d=\"M20 18H4V8H20V18M12 6L10 4H4C2.9 4 2 4.89 2 6V18C2 19.11 2.9 20 4 20H20C21.11 20 22 19.11 22 18V8C22 6.9 21.11 6 20 6H12M11 14V12H15V9L19 13L15 17V14H11Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw"
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw ***!
  \***************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-plus-outline\" viewBox=\"0 0 24 24\"><path d=\"M13 19C13 19.34 13.04 19.67 13.09 20H4C2.9 20 2 19.11 2 18V6C2 4.89 2.89 4 4 4H10L12 6H20C21.1 6 22 6.89 22 8V13.81C21.39 13.46 20.72 13.22 20 13.09V8H4V18H13.09C13.04 18.33 13 18.66 13 19M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/folder.svg?raw"
/*!**************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder.svg?raw ***!
  \**************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder\" viewBox=\"0 0 24 24\"><path d=\"M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/history.svg?raw"
/*!***************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/history.svg?raw ***!
  \***************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-history\" viewBox=\"0 0 24 24\"><path d=\"M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/image.svg?raw"
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/image.svg?raw ***!
  \*************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-image\" viewBox=\"0 0 24 24\"><path d=\"M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/information-outline.svg?raw"
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/information-outline.svg?raw ***!
  \***************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-information-outline\" viewBox=\"0 0 24 24\"><path d=\"M11,9H13V7H11M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,17H13V11H11V17Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/laptop.svg?raw"
/*!**************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/laptop.svg?raw ***!
  \**************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-laptop\" viewBox=\"0 0 24 24\"><path d=\"M4,6H20V16H4M20,18A2,2 0 0,0 22,16V6C22,4.89 21.1,4 20,4H4C2.89,4 2,4.89 2,6V16A2,2 0 0,0 4,18H0V20H24V18H20Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/movie.svg?raw"
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/movie.svg?raw ***!
  \*************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-movie\" viewBox=\"0 0 24 24\"><path d=\"M18,4L20,8H17L15,4H13L15,8H12L10,4H8L10,8H7L5,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V4H18Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/music.svg?raw"
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/music.svg?raw ***!
  \*************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-music\" viewBox=\"0 0 24 24\"><path d=\"M21,3V15.5A3.5,3.5 0 0,1 17.5,19A3.5,3.5 0 0,1 14,15.5A3.5,3.5 0 0,1 17.5,12C18.04,12 18.55,12.12 19,12.34V6.47L9,8.6V17.5A3.5,3.5 0 0,1 5.5,21A3.5,3.5 0 0,1 2,17.5A3.5,3.5 0 0,1 5.5,14C6.04,14 6.55,14.12 7,14.34V6L21,3Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/network-off.svg?raw"
/*!*******************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/network-off.svg?raw ***!
  \*******************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-network-off\" viewBox=\"0 0 24 24\"><path d=\"M1,5.27L5,9.27V15A2,2 0 0,0 7,17H11V19H10A1,1 0 0,0 9,20H2V22H9A1,1 0 0,0 10,23H14A1,1 0 0,0 15,22H17.73L19.73,24L21,22.72L2.28,4L1,5.27M15,20A1,1 0 0,0 14,19H13V17.27L15.73,20H15M17.69,16.87L5.13,4.31C5.41,3.55 6.14,3 7,3H17A2,2 0 0,1 19,5V15C19,15.86 18.45,16.59 17.69,16.87M22,20V21.18L20.82,20H22Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/pencil-outline.svg?raw"
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/pencil-outline.svg?raw ***!
  \**********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-pencil-outline\" viewBox=\"0 0 24 24\"><path d=\"M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/plus.svg?raw"
/*!************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/plus.svg?raw ***!
  \************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-plus\" viewBox=\"0 0 24 24\"><path d=\"M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/star-outline.svg?raw"
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/star-outline.svg?raw ***!
  \********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-star-outline\" viewBox=\"0 0 24 24\"><path d=\"M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw"
/*!*************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw ***!
  \*************************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-trash-can-outline\" viewBox=\"0 0 24 24\"><path d=\"M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/web.svg?raw"
/*!***********************************************!*\
  !*** ./node_modules/@mdi/svg/svg/web.svg?raw ***!
  \***********************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-web\" viewBox=\"0 0 24 24\"><path d=\"M16.36,14C16.44,13.34 16.5,12.68 16.5,12C16.5,11.32 16.44,10.66 16.36,10H19.74C19.9,10.64 20,11.31 20,12C20,12.69 19.9,13.36 19.74,14M14.59,19.56C15.19,18.45 15.65,17.25 15.97,16H18.92C17.96,17.65 16.43,18.93 14.59,19.56M14.34,14H9.66C9.56,13.34 9.5,12.68 9.5,12C9.5,11.32 9.56,10.65 9.66,10H14.34C14.43,10.65 14.5,11.32 14.5,12C14.5,12.68 14.43,13.34 14.34,14M12,19.96C11.17,18.76 10.5,17.43 10.09,16H13.91C13.5,17.43 12.83,18.76 12,19.96M8,8H5.08C6.03,6.34 7.57,5.06 9.4,4.44C8.8,5.55 8.35,6.75 8,8M5.08,16H8C8.35,17.25 8.8,18.45 9.4,19.56C7.57,18.93 6.03,17.65 5.08,16M4.26,14C4.1,13.36 4,12.69 4,12C4,11.31 4.1,10.64 4.26,10H7.64C7.56,10.66 7.5,11.32 7.5,12C7.5,12.68 7.56,13.34 7.64,14M12,4.03C12.83,5.23 13.5,6.57 13.91,8H10.09C10.5,6.57 11.17,5.23 12,4.03M18.92,8H15.97C15.65,6.75 15.19,5.55 14.59,4.44C16.43,5.07 17.96,6.34 18.92,8M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z\" /></svg>";

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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_preview-BIbJGxXF_mjs-node_modules_nextcloud_dialog-7546cc":"dce7060a8340cecf2ca5","node_modules_nextcloud_dialogs_dist_chunks_ConflictPicker-CWBf0soh_mjs":"01a2e7bc2c49db839239","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcTextField_index_mjs":"50270bc67122ae47dfd0","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-C1yRZfLt_mjs":"a3985d66705012167d75","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-7_GNN76e_mjs":"b2479474dfc9749ea9d0","node_modules_nextcloud_vue_dist_Components_NcColorPicker_mjs":"cc9a80a105a480079016","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"21fc91c563f5cd8d04c3","node_modules_rehype-highlight_index_js":"625f8818e33c457c3744","node_modules_nextcloud_upload_dist_chunks_ConflictPicker-DuPiUBHl_mjs":"da3acfec46893a3999c7","node_modules_nextcloud_upload_dist_chunks_InvalidFilenameDialog-BM2VDeLo_mjs":"1beb4916241413155fba","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_index-BMbtc3xh_mjs":"313d1a40718226ae766e","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-CfO-95d64a":"35af0b481109fcc029fa","apps_files_src_views_SearchEmptyView_vue":"d08bb81cacb7e1624360","apps_files_src_views_TemplatePicker_vue":"769b4483c34398f99b17","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcColorPicker_index_mjs":"63766ea64d27a6d8d6cc","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcDateTimePicker_in-952ddb":"bd2fc411cc830fe12f0b","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_FilePicker-JKNLPCbR_mjs":"32bb6d3d92cda43cf51a"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/harmony module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.hmd = (module) => {
/******/ 			module = Object.create(module);
/******/ 			if (!module.children) module.children = [];
/******/ 			Object.defineProperty(module, 'exports', {
/******/ 				enumerable: true,
/******/ 				set: () => {
/******/ 					throw new Error('ES Modules may not assign module.exports or exports.*, Use ESM export syntax, instead: ' + module.id);
/******/ 				}
/******/ 			});
/******/ 			return module;
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-init.js.map?v=bae1533a1d32f20e8ffb