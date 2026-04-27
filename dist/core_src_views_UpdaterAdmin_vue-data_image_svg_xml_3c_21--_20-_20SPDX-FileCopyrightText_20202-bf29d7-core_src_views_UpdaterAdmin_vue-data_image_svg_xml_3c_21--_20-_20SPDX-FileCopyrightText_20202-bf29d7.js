"use strict";
(globalThis["webpackChunknextcloud_ui_legacy"] = globalThis["webpackChunknextcloud_ui_legacy"] || []).push([["core_src_views_UpdaterAdmin_vue-data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_20202-bf29d7"],{

/***/ "./core/src/OC/eventsource.js"
/*!************************************!*\
  !*** ./core/src/OC/eventsource.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ "./core/src/OC/requesttoken.ts"
/*!*************************************!*\
  !*** ./core/src/OC/requesttoken.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts"
/*!******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcGuestContent__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcGuestContent */ "./node_modules/@nextcloud/vue/dist/Components/NcGuestContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _OC_eventsource_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../OC/eventsource.js */ "./core/src/OC/eventsource.js");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'UpdaterAdmin',
  setup(__props) {
    const updateInfo = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('core', 'updateInfo');
    const isShowingDetails = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    const isUpdateRunning = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    const isUpdateDone = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    const messages = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    const wasSuccessfull = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => messages.value.every(msg => msg.type === 'success' || msg.type === 'notice'));
    const hasErrors = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => messages.value.some(msg => msg.type === 'error' || msg.type === 'failure'));
    const resultIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => wasSuccessfull.value ? _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCheckCircleOutline : hasErrors.value ? _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCloseCircleOutline : _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiAlertCircleOutline);
    const statusMessage = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      if (isUpdateDone.value) {
        if (!wasSuccessfull.value) {
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'The update completed with warnings. Please check the details for more information.');
        } else {
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'The update completed successfully.');
        }
      }
      return messages.value.at(-1)?.message || (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Preparing update…');
    });
    const redirectCountdown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(6);
    const redirectMessage = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      if (!isUpdateDone.value || !wasSuccessfull.value) {
        return '';
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'You will be redirected to {productName} in {count} seconds.', {
        productName: updateInfo.productName,
        count: redirectCountdown.value
      });
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(() => window.addEventListener('beforeunload', onUnload));
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted)(() => window.removeEventListener('beforeunload', onUnload));
    /**
     * Get the status icon for a given severity
     *
     * @param type - The severity
     */
    function getSeverityIcon(type) {
      switch (type) {
        case 'success':
          return _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCheckCircleOutline;
        case 'notice':
          return _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiInformationOutline;
        case 'warning':
          return _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiAlertCircleOutline;
        case 'error':
        case 'failure':
          return _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCloseCircleOutline;
        default:
          return _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiInformationOutline;
      }
    }
    /**
     * Start the update process
     */
    async function onStartUpdate() {
      if (isUpdateRunning.value || isUpdateDone.value) {
        return;
      }
      isUpdateRunning.value = true;
      const eventSource = new _OC_eventsource_js__WEBPACK_IMPORTED_MODULE_8__["default"]((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateFilePath)('core', '', 'ajax/update.php'));
      eventSource.listen('success', message => {
        messages.value.push({
          message,
          type: 'success'
        });
      });
      eventSource.listen('notice', message => {
        messages.value.push({
          message,
          type: 'notice'
        });
      });
      eventSource.listen('error', message => {
        messages.value.push({
          message,
          type: 'error'
        });
        isUpdateRunning.value = false;
        isUpdateDone.value = true;
        eventSource.close();
      });
      eventSource.listen('failure', message => {
        messages.value.push({
          message,
          type: 'failure'
        });
      });
      eventSource.listen('done', () => {
        isUpdateRunning.value = false;
        isUpdateDone.value = true;
        eventSource.close();
        updateCountdown();
      });
    }
    /**
     * Update the countdown for the redirect
     */
    function updateCountdown() {
      if (hasErrors.value || !wasSuccessfull.value) {
        return;
      }
      if (--redirectCountdown.value > 0) {
        window.setTimeout(updateCountdown, 1000);
      } else {
        reloadPage();
      }
    }
    /**
     * Handle the beforeunload event to warn the user if an update is running.
     *
     * @param event - The beforeunload event object.
     */
    function onUnload(event) {
      if (isUpdateRunning.value) {
        event.preventDefault();
        event.returnValue = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'The update is in progress, leaving this page might interrupt the process in some environments.');
      }
    }
    /**
     * Reload the page
     */
    function reloadPage() {
      window.location.reload();
    }
    return {
      __sfc: true,
      updateInfo,
      isShowingDetails,
      isUpdateRunning,
      isUpdateDone,
      messages,
      wasSuccessfull,
      hasErrors,
      resultIcon,
      statusMessage,
      redirectCountdown,
      redirectMessage,
      getSeverityIcon,
      onStartUpdate,
      updateCountdown,
      onUnload,
      reloadPage,
      mdiChevronDown: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiChevronDown,
      mdiChevronUp: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiChevronUp,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t,
      NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_5__.NcButton,
      NcIconSvgWrapper: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_5__.NcIconSvgWrapper,
      NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_5__.NcLoadingIcon,
      NcGuestContent: _nextcloud_vue_components_NcGuestContent__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_7__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84"
/*!********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84 ***!
  \********************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.NcGuestContent, [_c("h2", [_vm._v("\n\t\t" + _vm._s(_setup.updateInfo.isAppsOnlyUpgrade ? _setup.t("core", "App update required") : _setup.t("core", "{productName} will be updated to version {version}", {
    productName: _setup.updateInfo.productName,
    version: _setup.updateInfo.version
  })) + "\n\t")]), _vm._v(" "), !!_setup.updateInfo.oldTheme ? _c(_setup.NcNoteCard, {
    attrs: {
      type: "info"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("core", "The theme {oldTheme} has been disabled.", {
    oldTheme: _setup.updateInfo.oldTheme
  })) + "\n\t")]) : _vm._e(), _vm._v(" "), _setup.updateInfo.incompatibleAppsList.length ? _c(_setup.NcNoteCard, {
    attrs: {
      type: "warning"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("core", "These incompatible apps will be disabled:")) + "\n\t\t"), _c("ul", {
    class: _vm.$style.updater__appsList,
    attrs: {
      "aria-label": _setup.t("core", "Incompatible apps")
    }
  }, _vm._l(_setup.updateInfo.incompatibleAppsList, function (app) {
    return _c("li", {
      key: "app-disable-" + app.id
    }, [_vm._v("\n\t\t\t\t" + _vm._s(app.name) + " (" + _vm._s(app.id) + ")\n\t\t\t")]);
  }), 0)]) : _vm._e(), _vm._v(" "), _setup.updateInfo.incompatibleAppsList.length ? _c(_setup.NcNoteCard, {
    attrs: {
      type: "info"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("core", "These apps will be updated:")) + "\n\t\t"), _c("ul", {
    class: _vm.$style.updater__appsList,
    attrs: {
      "aria-label": _setup.t("core", "Apps to update")
    }
  }, _vm._l(_setup.updateInfo.appsToUpgrade, function (app) {
    return _c("li", {
      key: "app-update-" + app.id
    }, [_vm._v("\n\t\t\t\t" + _vm._s(_setup.t("core", "{app} from {oldVersion} to {version}", {
      app: `${app.name} (${app.id})`,
      oldVersion: app.oldVersion,
      version: app.version
    })) + "\n\t\t\t")]);
  }), 0)]) : _vm._e(), _vm._v(" "), _c("p", [_c("strong", [_vm._v(_vm._s(_setup.t("core", "Please make sure that the database, the config folder and the data folder have been backed up before proceeding.")))]), _vm._v(" "), _c("br"), _vm._v("\n\t\t" + _vm._s(_setup.t("core", "To avoid timeouts with larger installations, you can instead run the following command from your installation directory:")) + "\n\t\t"), _c("pre", [_vm._v("./occ upgrade")])]), _vm._v(" "), !_setup.isUpdateRunning && !_setup.isUpdateDone ? _c(_setup.NcButton, {
    class: _vm.$style.updater__updateButton,
    attrs: {
      variant: "primary"
    },
    on: {
      click: _setup.onStartUpdate
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("core", "Start update")) + "\n\t")]) : _c(_setup.NcButton, {
    class: _vm.$style.updater__updateButton,
    attrs: {
      disabled: _setup.isUpdateRunning,
      variant: "primary"
    },
    on: {
      click: _setup.reloadPage
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("core", "Continue to {productName}", {
    productName: _setup.updateInfo.productName
  })) + "\n\t")]), _vm._v(" "), _setup.isUpdateRunning || _setup.isUpdateDone ? _c("div", [_c("h2", [_vm._v(_vm._s(_setup.t("core", "Update to {version}", {
    version: _setup.updateInfo.version
  })))]), _vm._v(" "), _setup.isUpdateRunning ? _c(_setup.NcLoadingIcon) : _c(_setup.NcIconSvgWrapper, {
    class: {
      [_vm.$style.updater__messageIcon_success]: _setup.wasSuccessfull,
      [_vm.$style.updater__messageIcon_error]: _setup.hasErrors && !_setup.wasSuccessfull,
      [_vm.$style.updater__messageIcon_warning]: !_setup.hasErrors && !_setup.wasSuccessfull
    },
    attrs: {
      path: _setup.resultIcon
    }
  }), _vm._v(" "), _c("div", {
    attrs: {
      "aria-live": "polite"
    }
  }, [_c("em", [_vm._v(_vm._s(_setup.statusMessage))]), _c("br"), _vm._v(" "), _setup.redirectMessage ? _c("span", [_vm._v(_vm._s(_setup.redirectMessage))]) : _vm._e()]), _vm._v(" "), _c(_setup.NcButton, {
    attrs: {
      "aria-controlls": "core-update-details",
      "aria-expanded": _setup.isShowingDetails,
      variant: "tertiary"
    },
    on: {
      click: function ($event) {
        _setup.isShowingDetails = !_setup.isShowingDetails;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.isShowingDetails ? _setup.mdiChevronUp : _setup.mdiChevronDown
          }
        })];
      },
      proxy: true
    }], null, false, 793381276)
  }, [_vm._v("\n\t\t\t" + _vm._s(_setup.isShowingDetails ? _setup.t("core", "Hide details") : _setup.t("core", "Show details")) + "\n\t\t")]), _vm._v(" "), _c("Transition", {
    attrs: {
      "enter-active-class": _vm.$style.updater__transition_active,
      "leave-active-class": _vm.$style.updater__transition_active,
      "leave-to-class": _vm.$style.updater__transition_collapsed,
      "enter-class": _vm.$style.updater__transition_collapsed
    }
  }, [_c("ul", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _setup.isShowingDetails,
      expression: "isShowingDetails"
    }],
    class: _vm.$style.updater__messageList,
    attrs: {
      id: "core-update-details",
      "aria-label": _setup.t("core", "Update details")
    }
  }, _vm._l(_setup.messages, function ({
    message,
    type
  }) {
    return _c("li", {
      key: message,
      class: _vm.$style.updater__message
    }, [_c(_setup.NcIconSvgWrapper, {
      class: {
        [_vm.$style.updater__messageIcon_error]: type === "error" || type === "failure",
        [_vm.$style.updater__messageIcon_info]: type === "notice",
        [_vm.$style.updater__messageIcon_success]: type === "success",
        [_vm.$style.updater__messageIcon_warning]: type === "warning"
      },
      attrs: {
        path: _setup.getSeverityIcon(type)
      }
    }), _vm._v(" "), _c("span", {
      class: _vm.$style.updater__messageText
    }, [_vm._v(_vm._s(message))])], 1);
  }), 0)])], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
___CSS_LOADER_EXPORT___.push([module.id, `
._updater__appsList_Yz6nW {
	list-style-type: disc;
	margin-inline-start: var(--default-clickable-area);
}
._updater__updateButton_Mwnuk {
	margin-inline: auto;
	margin-block: 1rem;
}
._updater__messageList_qo5F5 {
	max-height: 50vh;
	overflow: visible scroll;
	padding-inline-start: var(--default-grid-baseline);
}
._updater__message_ySGY4 {
	display: flex;
	align-items: center;
	justify-content: start;
	gap: var(--default-grid-baseline);
}
._updater__messageText_woqJ3 {
	text-align: start;
}
._updater__messageIcon_success_DY21y {
	color: var(--color-element-success);
}
._updater__messageIcon_info_Rx8RF {
	color: var(--color-element-info);
}
._updater__messageIcon_error_Pu5Pl {
	color: var(--color-element-error);
}
._updater__messageIcon_warning_t7k_k {
	color: var(--color-element-warning);
}
._updater__transition_active_VFUFS {
	transition: all var(--animation-slow);
}
._updater__transition_collapsed_N3hdT {
	opacity: 0;
	max-height: 0px;
}
`, ""]);
// Exports
___CSS_LOADER_EXPORT___.locals = {
	"updater__appsList": `_updater__appsList_Yz6nW`,
	"updater__updateButton": `_updater__updateButton_Mwnuk`,
	"updater__messageList": `_updater__messageList_qo5F5`,
	"updater__message": `_updater__message_ySGY4`,
	"updater__messageText": `_updater__messageText_woqJ3`,
	"updater__messageIcon_success": `_updater__messageIcon_success_DY21y`,
	"updater__messageIcon_info": `_updater__messageIcon_info_Rx8RF`,
	"updater__messageIcon_error": `_updater__messageIcon_error_Pu5Pl`,
	"updater__messageIcon_warning": `_updater__messageIcon_warning_t7k_k`,
	"updater__transition_active": `_updater__transition_active_VFUFS`,
	"updater__transition_collapsed": `_updater__transition_collapsed_N3hdT`
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css */ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./core/src/views/UpdaterAdmin.vue"
/*!*****************************************!*\
  !*** ./core/src/views/UpdaterAdmin.vue ***!
  \*****************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UpdaterAdmin.vue?vue&type=template&id=0746ef84 */ "./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84");
/* harmony import */ var _UpdaterAdmin_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts */ "./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css */ "./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
/* module decorator */ module = __webpack_require__.hmd(module);



;

var cssModules = {}
var disposed = false

function injectStyles (context) {
  if (disposed) return
  
        cssModules["$style"] = (_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"].locals || _UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"])
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
  _UpdaterAdmin_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__.render,
  _UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  injectStyles,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/views/UpdaterAdmin.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts"
/*!****************************************************************************!*\
  !*** ./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84"
/*!***********************************************************************!*\
  !*** ./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84 ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_template_id_0746ef84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdaterAdmin.vue?vue&type=template&id=0746ef84 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=template&id=0746ef84");


/***/ },

/***/ "./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css"
/*!*************************************************************************************************!*\
  !*** ./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdaterAdmin_vue_vue_type_style_index_0_id_0746ef84_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UpdaterAdmin.vue?vue&type=style&index=0&id=0746ef84&module=true&lang=css");
 

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ }

}]);
//# sourceMappingURL=core_src_views_UpdaterAdmin_vue-data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_20202-bf29d7-core_src_views_UpdaterAdmin_vue-data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_20202-bf29d7.js.map?v=930869b31b2a102414f3