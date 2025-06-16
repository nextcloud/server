/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/settings/src/admin.js":
/*!************************************!*\
  !*** ./apps/settings/src/admin.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




window.addEventListener('DOMContentLoaded', () => {
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#loglevel').change(function () {
    jquery__WEBPACK_IMPORTED_MODULE_1___default().post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/admin/log/level'), {
      level: jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).val()
    }, () => {
      OC.Log.reload();
    });
  });
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpauth').change(function () {
    if (!this.checked) {
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials').addClass('hidden');
    } else {
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials').removeClass('hidden');
    }
  });
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpmode').change(function () {
    if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).val() !== 'smtp') {
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#setting_smtpauth').addClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#setting_smtphost').addClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpsecure_label').addClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpsecure').addClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials').addClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_sendmailmode_label, #mail_sendmailmode').removeClass('hidden');
    } else {
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#setting_smtpauth').removeClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#setting_smtphost').removeClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpsecure_label').removeClass('hidden');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpsecure').removeClass('hidden');
      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtpauth').is(':checked')) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials').removeClass('hidden');
      }
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_sendmailmode_label, #mail_sendmailmode').addClass('hidden');
    }
  });
  const changeEmailSettings = function () {
    if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
      OC.PasswordConfirmation.requirePasswordConfirmation(changeEmailSettings);
      return;
    }
    OC.msg.startSaving('#mail_settings_msg');
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/admin/mailsettings'), jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_general_settings_form').serialize()).then(() => {
      OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'));
    }).catch(error => {
      OC.msg.finishedError('#mail_settings_msg', error);
    });
  };
  const toggleEmailCredentials = function () {
    if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
      OC.PasswordConfirmation.requirePasswordConfirmation(toggleEmailCredentials);
      return;
    }
    OC.msg.startSaving('#mail_settings_msg');
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/admin/mailsettings/credentials'), jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials_settings').serialize()).then(() => {
      OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'));
    }).catch(error => {
      OC.msg.finishedError('#mail_settings_msg', error);
    });
  };
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_general_settings_form').change(changeEmailSettings);
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_credentials_settings_submit').click(toggleEmailCredentials);
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#mail_smtppassword').click(() => {
    if (undefined.type === 'text' && undefined.value === '********') {
      undefined.type = 'password';
      undefined.value = '';
    }
  });
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('#sendtestemail').click(event => {
    event.preventDefault();
    OC.msg.startAction('#sendtestmail_msg', t('settings', 'Sending…'));
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/admin/mailtest')).then(() => {
      OC.msg.finishedSuccess('#sendtestmail_msg', t('settings', 'Email sent'));
    }).catch(error => {
      OC.msg.finishedError('#sendtestmail_msg', error);
    });
  });
  const setupChecks = () => {
    // run setup checks then gather error messages
    jquery__WEBPACK_IMPORTED_MODULE_1___default().when(OC.SetupChecks.checkSetup()).then(messages => {
      const $el = jquery__WEBPACK_IMPORTED_MODULE_1___default()('#postsetupchecks');
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('#security-warning-state-loading').addClass('hidden');
      const $errorsEl = $el.find('.errors');
      const $warningsEl = $el.find('.warnings');
      const $infoEl = $el.find('.info');
      for (let i = 0; i < messages.length; i++) {
        switch (messages[i].type) {
          case OC.SetupChecks.MESSAGE_TYPE_INFO:
            $infoEl.append('<li>' + messages[i].msg + '</li>');
            break;
          case OC.SetupChecks.MESSAGE_TYPE_WARNING:
            $warningsEl.append('<li>' + messages[i].msg + '</li>');
            break;
          case OC.SetupChecks.MESSAGE_TYPE_ERROR:
          default:
            $errorsEl.append('<li>' + messages[i].msg + '</li>');
        }
      }
      let hasErrors = false;
      let hasWarnings = false;
      if ($errorsEl.find('li').length > 0) {
        $errorsEl.removeClass('hidden');
        hasErrors = true;
      }
      if ($warningsEl.find('li').length > 0) {
        $warningsEl.removeClass('hidden');
        hasWarnings = true;
      }
      if ($infoEl.find('li').length > 0) {
        $infoEl.removeClass('hidden');
      }
      if (hasErrors || hasWarnings) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()('#postsetupchecks-hint').removeClass('hidden');
        if (hasErrors) {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()('#security-warning-state-failure').removeClass('hidden');
        } else {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()('#security-warning-state-warning').removeClass('hidden');
        }
      } else {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()('#security-warning-state-ok').removeClass('hidden');
      }
    });
  };
  if (document.getElementById('security-warning') !== null) {
    setupChecks();
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
/******/ 			"settings-legacy-admin": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/settings/src/admin.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=settings-legacy-admin.js.map?v=4c5765fdefe82f54f89b