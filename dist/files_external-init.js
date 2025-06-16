/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files_external/src/actions/enterCredentialsAction.ts":
/*!*******************************************************************!*\
  !*** ./apps/files_external/src/actions/enterCredentialsAction.ts ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_CREDENTIALS_EXTERNAL_STORAGE: () => (/* binding */ ACTION_CREDENTIALS_EXTERNAL_STORAGE),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_login_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/login.svg?raw */ "./node_modules/@mdi/svg/svg/login.svg?raw");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/credentialsUtils */ "./apps/files_external/src/utils/credentialsUtils.ts");
/* harmony import */ var _utils_externalStorageUtils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/externalStorageUtils */ "./apps/files_external/src/utils/externalStorageUtils.ts");










// Add password confirmation interceptors as
// the backend requires the user to confirm their password
(0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__.addPasswordConfirmationInterceptors)(_nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"]);
/**
 * Set credentials for external storage
 *
 * @param node The node for which to set the credentials
 * @param login The username
 * @param password The password
 */
async function setCredentials(node, login, password) {
  const configResponse = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].request({
    method: 'PUT',
    url: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('apps/files_external/userglobalstorages/{id}', {
      id: node.attributes.id
    }),
    confirmPassword: _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__.PwdConfirmationMode.Strict,
    data: {
      backendOptions: {
        user: login,
        password
      }
    }
  });
  const config = configResponse.data;
  if (config.status !== _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_7__.STORAGE_STATUS.SUCCESS) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_external', 'Unable to update this external storage config. {statusMessage}', {
      statusMessage: config?.statusMessage || ''
    }));
    return null;
  }
  // Success update config attribute
  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_external', 'New configuration successfully saved'));
  vue__WEBPACK_IMPORTED_MODULE_9__["default"].set(node.attributes, 'config', config);
  return true;
}
const ACTION_CREDENTIALS_EXTERNAL_STORAGE = 'credentials-external-storage';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_6__.FileAction({
  id: ACTION_CREDENTIALS_EXTERNAL_STORAGE,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files', 'Enter missing credentials'),
  iconSvgInline: () => _mdi_svg_svg_login_svg_raw__WEBPACK_IMPORTED_MODULE_5__,
  enabled: nodes => {
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes[0];
    if (!(0,_utils_externalStorageUtils__WEBPACK_IMPORTED_MODULE_8__.isNodeExternalStorage)(node)) {
      return false;
    }
    const config = node.attributes?.config || {};
    if ((0,_utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_7__.isMissingAuthConfig)(config)) {
      return true;
    }
    return false;
  },
  async exec(node) {
    const {
      login,
      password
    } = await new Promise(resolve => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.spawnDialog)((0,vue__WEBPACK_IMPORTED_MODULE_9__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_external_src_views_CredentialsDialog_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/CredentialsDialog.vue */ "./apps/files_external/src/views/CredentialsDialog.vue"))), {}, args => {
      resolve(args);
    }));
    if (login && password) {
      try {
        await setCredentials(node, login, password);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_external', 'Credentials successfully set'));
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_external', 'Error while setting credentials: {error}', {
          error: error.message
        }));
      }
    }
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_6__.DefaultType.DEFAULT,
  inline: () => true
});

/***/ }),

/***/ "./apps/files_external/src/actions/inlineStorageCheckAction.ts":
/*!*********************************************************************!*\
  !*** ./apps/files_external/src/actions/inlineStorageCheckAction.ts ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_alert_circle_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/alert-circle.svg?raw */ "./node_modules/@mdi/svg/svg/alert-circle.svg?raw");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _css_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../css/fileEntryStatus.scss */ "./apps/files_external/src/css/fileEntryStatus.scss");
/* harmony import */ var _services_externalStorage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/externalStorage */ "./apps/files_external/src/services/externalStorage.ts");
/* harmony import */ var _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/credentialsUtils */ "./apps/files_external/src/utils/credentialsUtils.ts");
/* harmony import */ var _utils_externalStorageUtils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/externalStorageUtils */ "./apps/files_external/src/utils/externalStorageUtils.ts");









const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'check-external-storage',
  displayName: () => '',
  iconSvgInline: () => '',
  enabled: nodes => {
    return nodes.every(node => (0,_utils_externalStorageUtils__WEBPACK_IMPORTED_MODULE_7__.isNodeExternalStorage)(node) === true);
  },
  exec: async () => null,
  /**
   * Use this function to check the storage availability
   * We then update the node attributes directly.
   *
   * @param node The node to render inline
   */
  async renderInline(node) {
    const span = document.createElement('span');
    span.className = 'files-list__row-status';
    span.innerHTML = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_external', 'Checking storage …');
    let config = null;
    (0,_services_externalStorage__WEBPACK_IMPORTED_MODULE_5__.getStatus)(node.attributes.id, node.attributes.scope === 'system').then(response => {
      config = response.data;
      vue__WEBPACK_IMPORTED_MODULE_8__["default"].set(node.attributes, 'config', config);
      if (config.status !== _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_6__.STORAGE_STATUS.SUCCESS) {
        throw new Error(config?.statusMessage || (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_external', 'There was an error with this external storage.'));
      }
      span.remove();
    }).catch(error => {
      // If axios failed or if something else prevented
      // us from getting the config
      if (error.response && !config) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showWarning)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_external', 'We were unable to check the external storage {basename}', {
          basename: node.basename
        }));
      }
      // Reset inline status
      span.innerHTML = '';
      // Checking if we really have an error
      const isWarning = !config ? false : (0,_utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_6__.isMissingAuthConfig)(config);
      const overlay = document.createElement('span');
      overlay.classList.add(`files-list__row-status--${isWarning ? 'warning' : 'error'}`);
      // Only show an icon for errors, warning like missing credentials
      // have a dedicated inline action button
      if (!isWarning) {
        span.innerHTML = _mdi_svg_svg_alert_circle_svg_raw__WEBPACK_IMPORTED_MODULE_3__;
        span.title = error.message;
      }
      span.prepend(overlay);
    });
    return span;
  },
  order: 10
});

/***/ }),

/***/ "./apps/files_external/src/actions/openInFilesAction.ts":
/*!**************************************************************!*\
  !*** ./apps/files_external/src/actions/openInFilesAction.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/credentialsUtils */ "./apps/files_external/src/utils/credentialsUtils.ts");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");





const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileAction({
  id: 'open-in-files-external-storage',
  displayName: nodes => {
    const config = nodes?.[0]?.attributes?.config || {
      status: _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_3__.STORAGE_STATUS.INDETERMINATE
    };
    if (config.status !== _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_3__.STORAGE_STATUS.SUCCESS) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'Examine this faulty external storage configuration');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Open in Files');
  },
  iconSvgInline: () => '',
  enabled: (nodes, view) => view.id === 'extstoragemounts',
  async exec(node) {
    const config = node.attributes.config;
    if (config?.status !== _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_3__.STORAGE_STATUS.SUCCESS) {
      window.OC.dialogs.confirm((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'There was an error with this external storage. Do you want to review this mount point config in the settings page?'), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'External mount error'), redirect => {
        if (redirect === true) {
          const scope = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__.getCurrentUser)()?.isAdmin ? 'admin' : 'user';
          window.location.href = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(`/settings/${scope}/externalstorages`);
        }
      });
      return null;
    }
    // Do not use fileid as we don't have that information
    // from the external storage api
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files'
    }, {
      dir: node.path
    });
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.DefaultType.HIDDEN
});

/***/ }),

/***/ "./apps/files_external/src/css/fileEntryStatus.scss":
/*!**********************************************************!*\
  !*** ./apps/files_external/src/css/fileEntryStatus.scss ***!
  \**********************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./fileEntryStatus.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_external/src/css/fileEntryStatus.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryStatus_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files_external/src/init.ts":
/*!*****************************************!*\
  !*** ./apps/files_external/src/init.ts ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_folder_network_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/folder-network.svg?raw */ "./node_modules/@mdi/svg/svg/folder-network.svg?raw");
/* harmony import */ var _actions_enterCredentialsAction__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions/enterCredentialsAction */ "./apps/files_external/src/actions/enterCredentialsAction.ts");
/* harmony import */ var _actions_inlineStorageCheckAction__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./actions/inlineStorageCheckAction */ "./apps/files_external/src/actions/inlineStorageCheckAction.ts");
/* harmony import */ var _actions_openInFilesAction__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./actions/openInFilesAction */ "./apps/files_external/src/actions/openInFilesAction.ts");
/* harmony import */ var _services_externalStorage__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./services/externalStorage */ "./apps/files_external/src/services/externalStorage.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








const allowUserMounting = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_external', 'allowUserMounting', false);
// Register view
const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)();
Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.View({
  id: 'extstoragemounts',
  name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'External storage'),
  caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'List of external storage.'),
  emptyCaption: allowUserMounting ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'There is no external storage configured. You can configure them in your Personal settings.') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'There is no external storage configured and you don\'t have the permission to configure them.'),
  emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'No external storage'),
  icon: _mdi_svg_svg_folder_network_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  order: 30,
  columns: [new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Column({
    id: 'storage-type',
    title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'Storage type'),
    render(node) {
      const backend = node.attributes?.backend || (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'Unknown');
      const span = document.createElement('span');
      span.textContent = backend;
      return span;
    }
  }), new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Column({
    id: 'scope',
    title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'Scope'),
    render(node) {
      const span = document.createElement('span');
      let scope = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'Personal');
      if (node.attributes?.scope === 'system') {
        scope = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_external', 'System');
      }
      span.textContent = scope;
      return span;
    }
  })],
  getContents: _services_externalStorage__WEBPACK_IMPORTED_MODULE_7__.getContents
}));
// Register actions
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)(_actions_enterCredentialsAction__WEBPACK_IMPORTED_MODULE_4__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)(_actions_inlineStorageCheckAction__WEBPACK_IMPORTED_MODULE_5__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)(_actions_openInFilesAction__WEBPACK_IMPORTED_MODULE_6__.action);

/***/ }),

/***/ "./apps/files_external/src/services/externalStorage.ts":
/*!*************************************************************!*\
  !*** ./apps/files_external/src/services/externalStorage.ts ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   getStatus: () => (/* binding */ getStatus),
/* harmony export */   rootPath: () => (/* binding */ rootPath)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/credentialsUtils */ "./apps/files_external/src/utils/credentialsUtils.ts");





const rootPath = `/files/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid}`;
const entryToFolder = ocsEntry => {
  const path = (ocsEntry.path + '/' + ocsEntry.name).replace(/^\//gm, '');
  return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Folder({
    id: ocsEntry.id,
    source: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateRemoteUrl)('dav' + rootPath + '/' + path),
    root: rootPath,
    owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid || null,
    permissions: ocsEntry.config.status !== _utils_credentialsUtils__WEBPACK_IMPORTED_MODULE_4__.STORAGE_STATUS.SUCCESS ? _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE : ocsEntry?.permissions || _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.READ,
    attributes: {
      displayName: path,
      ...ocsEntry
    }
  });
};
const getContents = async () => {
  const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_external/api/v1/mounts'));
  const contents = response.data.ocs.data.map(entryToFolder);
  return {
    folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Folder({
      id: 0,
      source: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateRemoteUrl)('dav' + rootPath),
      root: rootPath,
      owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid || null,
      permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.READ
    }),
    contents
  };
};
const getStatus = function (id) {
  let global = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  const type = global ? 'userglobalstorages' : 'userstorages';
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`apps/files_external/${type}/${id}?testOnly=false`));
};

/***/ }),

/***/ "./apps/files_external/src/utils/credentialsUtils.ts":
/*!***********************************************************!*\
  !*** ./apps/files_external/src/utils/credentialsUtils.ts ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   STORAGE_STATUS: () => (/* binding */ STORAGE_STATUS),
/* harmony export */   isMissingAuthConfig: () => (/* binding */ isMissingAuthConfig)
/* harmony export */ });
// @see https://github.com/nextcloud/server/blob/ac2bc2384efe3c15ff987b87a7432bc60d545c67/lib/public/Files/StorageNotAvailableException.php#L41
var STORAGE_STATUS;
(function (STORAGE_STATUS) {
  STORAGE_STATUS[STORAGE_STATUS["SUCCESS"] = 0] = "SUCCESS";
  STORAGE_STATUS[STORAGE_STATUS["ERROR"] = 1] = "ERROR";
  STORAGE_STATUS[STORAGE_STATUS["INDETERMINATE"] = 2] = "INDETERMINATE";
  STORAGE_STATUS[STORAGE_STATUS["INCOMPLETE_CONF"] = 3] = "INCOMPLETE_CONF";
  STORAGE_STATUS[STORAGE_STATUS["UNAUTHORIZED"] = 4] = "UNAUTHORIZED";
  STORAGE_STATUS[STORAGE_STATUS["TIMEOUT"] = 5] = "TIMEOUT";
  STORAGE_STATUS[STORAGE_STATUS["NETWORK_ERROR"] = 6] = "NETWORK_ERROR";
})(STORAGE_STATUS || (STORAGE_STATUS = {}));
const isMissingAuthConfig = function (config) {
  // If we don't know the status, assume it is ok
  if (!config.status || config.status === STORAGE_STATUS.SUCCESS) {
    return false;
  }
  return config.userProvided || config.authMechanism === 'password::global::user';
};

/***/ }),

/***/ "./apps/files_external/src/utils/externalStorageUtils.ts":
/*!***************************************************************!*\
  !*** ./apps/files_external/src/utils/externalStorageUtils.ts ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isNodeExternalStorage: () => (/* binding */ isNodeExternalStorage)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isNodeExternalStorage = function (node) {
  // Not a folder, not a storage
  if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.File) {
    return false;
  }
  // No backend or scope, not a storage
  const attributes = node.attributes;
  if (!attributes.scope || !attributes.backend) {
    return false;
  }
  // Specific markers that we're sure are ext storage only
  return attributes.scope === 'personal' || attributes.scope === 'system';
};

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/alert-circle.svg?raw":
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/alert-circle.svg?raw ***!
  \********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-alert-circle\" viewBox=\"0 0 24 24\"><path d=\"M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/folder-network.svg?raw":
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-network.svg?raw ***!
  \**********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-network\" viewBox=\"0 0 24 24\"><path d=\"M3,15V5A2,2 0 0,1 5,3H11L13,5H19A2,2 0 0,1 21,7V15A2,2 0 0,1 19,17H13V19H14A1,1 0 0,1 15,20H22V22H15A1,1 0 0,1 14,23H10A1,1 0 0,1 9,22H2V20H9A1,1 0 0,1 10,19H11V17H5A2,2 0 0,1 3,15Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/login.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/login.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-login\" viewBox=\"0 0 24 24\"><path d=\"M11 7L9.6 8.4L12.2 11H2V13H12.2L9.6 15.6L11 17L16 12L11 7M20 19H12V21H20C21.1 21 22 20.1 22 19V5C22 3.9 21.1 3 20 3H12V5H20V19Z\" /></svg>";

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_external/src/css/fileEntryStatus.scss":
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_external/src/css/fileEntryStatus.scss ***!
  \***************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.files-list__row-status {
  display: flex;
  min-width: 44px;
  justify-content: center;
  align-items: center;
  height: 100%;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.files-list__row-status svg {
  width: 24px;
  height: 24px;
}
.files-list__row-status svg path {
  fill: currentColor;
}
.files-list__row-status--error, .files-list__row-status--warning {
  position: absolute;
  display: block;
  top: 0;
  inset-inline: 0;
  bottom: 0;
  opacity: 0.1;
  z-index: -1;
}
.files-list__row-status--error {
  background: var(--color-error);
}
.files-list__row-status--warning {
  background: var(--color-warning);
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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"0a21f85fb5edb886fad0","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"5414d4143400c9b713c3","apps_files_external_src_views_CredentialsDialog_vue":"c4ee155f5ebbd41d7c60","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e":"87f84948225387ac2eec"}[chunkId] + "";
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
/******/ 			"files_external-init": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_external/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_external-init.js.map?v=7314fdc43d57e30b105b