/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/federatedfilesharing/src/external.js":
/*!***************************************************!*\
  !*** ./apps/federatedfilesharing/src/external.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


window.OCA.Sharing = window.OCA.Sharing || {};

/**
 * Shows "add external share" dialog.
 *
 * @param {object} share the share
 * @param {string} share.remote remote server URL
 * @param {string} share.owner owner name
 * @param {string} share.name name of the shared folder
 * @param {string} share.token authentication token
 * @param {boolean} passwordProtected true if the share is password protected
 * @param {Function} callback the callback
 */
window.OCA.Sharing.showAddExternalDialog = function (share, passwordProtected, callback) {
  const remote = share.remote;
  const owner = share.ownerDisplayName || share.owner;
  const name = share.name;

  // Clean up the remote URL for display
  const remoteClean = remote.replace(/^https?:\/\//, '') // remove http:// or https://
  .replace(/\/$/, ''); // remove trailing slash

  if (!passwordProtected) {
    window.OC.dialogs.confirm(t('files_sharing', 'Do you want to add the remote share {name} from {owner}@{remote}?', {
      name,
      owner,
      remote: remoteClean
    }), t('files_sharing', 'Remote share'), function (result) {
      callback(result, share);
    }, true).then(this._adjustDialog);
  } else {
    window.OC.dialogs.prompt(t('files_sharing', 'Do you want to add the remote share {name} from {owner}@{remote}?', {
      name,
      owner,
      remote: remoteClean
    }), t('files_sharing', 'Remote share'), function (result, password) {
      share.password = password;
      callback(result, share);
    }, true, t('files_sharing', 'Remote share password'), true).then(this._adjustDialog);
  }
};
window.OCA.Sharing._adjustDialog = function () {
  const $dialog = $('.oc-dialog:visible');
  const $buttons = $dialog.find('button');
  // hack the buttons
  $dialog.find('.ui-icon').remove();
  $buttons.eq(1).text(t('core', 'Cancel'));
  $buttons.eq(2).text(t('files_sharing', 'Add remote share'));
};
const reloadFilesList = function () {
  if (!window?.OCP?.Files?.Router?.goToRoute) {
    // No router, just reload the page
    window.location.reload();
    return;
  }

  // Let's redirect to the root as any accepted share would be there
  window.OCP.Files.Router.goToRoute(null, {
    ...window.OCP.Files.Router.params,
    fileid: undefined
  }, {
    ...window.OCP.Files.Router.query,
    dir: '/',
    openfile: undefined
  });
};

/**
 * Process incoming remote share that might have been passed
 * through the URL
 */
const processIncomingShareFromUrl = function () {
  const params = window.OC.Util.History.parseUrlQuery();

  // manually add server-to-server share
  if (params.remote && params.token && params.name) {
    const callbackAddShare = function (result, share) {
      const password = share.password || '';
      if (result) {
        $.post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('apps/federatedfilesharing/askForFederatedShare'), {
          remote: share.remote,
          token: share.token,
          owner: share.owner,
          ownerDisplayName: share.ownerDisplayName || share.owner,
          name: share.name,
          password
        }).done(function (data) {
          if (Object.hasOwn(data, 'legacyMount')) {
            reloadFilesList();
          } else {
            window.OC.Notification.showTemporary(data.message);
          }
        }).fail(function (data) {
          window.OC.Notification.showTemporary(JSON.parse(data.responseText).message);
        });
      }
    };

    // clear hash, it is unlikely that it contain any extra parameters
    location.hash = '';
    params.passwordProtected = parseInt(params.protected, 10) === 1;
    window.OCA.Sharing.showAddExternalDialog(params, params.passwordProtected, callbackAddShare);
  }
};

/**
 * Retrieve a list of remote shares that need to be approved
 */
const processSharesToConfirm = function () {
  // check for new server-to-server shares which need to be approved
  $.get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files_sharing/api/externalShares'), {}, function (shares) {
    let index;
    for (index = 0; index < shares.length; ++index) {
      window.OCA.Sharing.showAddExternalDialog(shares[index], false, function (result, share) {
        if (result) {
          // Accept
          $.post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files_sharing/api/externalShares'), {
            id: share.id
          }).then(function () {
            reloadFilesList();
          });
        } else {
          // Delete
          $.ajax({
            url: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files_sharing/api/externalShares/' + share.id),
            type: 'DELETE'
          });
        }
      });
    }
  });
};
processIncomingShareFromUrl();
if ((0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('federatedfilesharing', 'notificationsEnabled', true) !== true) {
  // No notification app, display the modal
  processSharesToConfirm();
}
$('body').on('window.OCA.Notification.Action', function (e) {
  if (e.notification.app === 'files_sharing' && e.notification.object_type === 'remote_share' && e.action.type === 'POST') {
    // User accepted a remote share reload
    reloadFilesList();
  }
});

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
/******/ 		// The chunk loading function for additional chunks
/******/ 		// Since all referenced chunks are already included
/******/ 		// in this file, this function is empty here.
/******/ 		__webpack_require__.e = () => (Promise.resolve());
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
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"federatedfilesharing-external": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/federatedfilesharing/src/external.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=federatedfilesharing-external.js.map?v=8207f144b96e09ae5552