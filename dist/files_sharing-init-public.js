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

/***/ "./apps/files/src/services/Files.ts":
/*!******************************************!*\
  !*** ./apps/files/src/services/Files.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   resultToNode: () => (/* binding */ resultToNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");





/**
 * Slim wrapper over `@nextcloud/files` `davResultToNode` to allow using the function with `Array.map`
 * @param stat The result returned by the webdav library
 */
const resultToNode = stat => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davResultToNode)(stat);
const getContents = function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  path = (0,path__WEBPACK_IMPORTED_MODULE_2__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRootPath, path);
  const controller = new AbortController();
  const propfindPayload = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davGetDefaultPropfind)();
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
        _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].debug(`Exepected "${path}" but got filename "${root.filename}" instead.`);
        throw new Error('Root node does not match requested path');
      }
      resolve({
        folder: resultToNode(root),
        contents: contents.map(result => {
          try {
            return resultToNode(result);
          } catch (error) {
            _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].error(`Invalid node detected '${result.basename}'`, {
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
};

/***/ }),

/***/ "./apps/files/src/services/RouterService.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/services/RouterService.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ RouterService)
/* harmony export */ });
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
class RouterService {
  constructor(router) {
    // typescript compiles this to `#router` to make it private even in JS,
    // but in TS it needs to be called without the visibility specifier
    _defineProperty(this, "router", void 0);
    this.router = router;
  }
  get name() {
    return this.router.currentRoute.name;
  }
  get query() {
    return this.router.currentRoute.query || {};
  }
  get params() {
    return this.router.currentRoute.params || {};
  }
  /**
   * This is a protected getter only for internal use
   * @private
   */
  get _router() {
    return this.router;
  }
  /**
   * Trigger a route change on the files app
   *
   * @param path the url path, eg: '/trashbin?dir=/Deleted'
   * @param replace replace the current history
   * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
   */
  goTo(path) {
    let replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    return this.router.push({
      path,
      replace
    });
  }
  /**
   * Trigger a route change on the files App
   *
   * @param name the route name
   * @param params the route parameters
   * @param query the url query parameters
   * @param replace replace the current history
   * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
   */
  goToRoute(name, params, query, replace) {
    return this.router.push({
      name,
      query,
      params,
      replace
    });
  }
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

/***/ "./apps/files_sharing/src/files_views/publicFileDrop.ts":
/*!**************************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicFileDrop.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_cloud_upload_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/cloud-upload.svg?raw */ "./node_modules/@mdi/svg/svg/cloud-upload.svg?raw");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const foldername = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_sharing', 'filename');
  let FilesViewFileDropEmptyContent;
  let fileDropEmptyContentInstance;
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: 'public-file-drop',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'File drop'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'Upload files to {foldername}', {
      foldername
    }),
    icon: _mdi_svg_svg_cloud_upload_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
    order: 1,
    emptyView: async div => {
      if (FilesViewFileDropEmptyContent === undefined) {
        const {
          default: component
        } = await Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_sharing_src_views_FilesViewFileDropEmptyContent_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/FilesViewFileDropEmptyContent.vue */ "./apps/files_sharing/src/views/FilesViewFileDropEmptyContent.vue"));
        FilesViewFileDropEmptyContent = vue__WEBPACK_IMPORTED_MODULE_5__["default"].extend(component);
      }
      if (fileDropEmptyContentInstance) {
        fileDropEmptyContentInstance.$destroy();
      }
      fileDropEmptyContentInstance = new FilesViewFileDropEmptyContent({
        propsData: {
          foldername
        }
      });
      fileDropEmptyContentInstance.$mount(div);
    },
    getContents: async () => {
      return {
        contents: [],
        // Fake a writeonly folder as root
        folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Folder({
          id: 0,
          source: `${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.defaultRemoteURL}${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.defaultRootPath}`,
          root: _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.defaultRootPath,
          owner: null,
          permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.CREATE
        })
      };
    }
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
  Navigation.register(view);
});

/***/ }),

/***/ "./apps/files_sharing/src/files_views/publicFileShare.ts":
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicFileShare.ts ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _files_src_services_WebdavClient__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../files/src/services/WebdavClient */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _services_logger__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger */ "./apps/files_sharing/src/services/logger.ts");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: 'public-file-share',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_sharing', 'Public file share'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_sharing', 'Publicly shared file.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_sharing', 'No file'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_sharing', 'The file shared with you will show up here'),
    icon: _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
    order: 1,
    getContents: () => {
      return new cancelable_promise__WEBPACK_IMPORTED_MODULE_2__.CancelablePromise(async (resolve, reject, onCancel) => {
        const abort = new AbortController();
        onCancel(() => abort.abort());
        try {
          const node = await _files_src_services_WebdavClient__WEBPACK_IMPORTED_MODULE_4__.client.stat(_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRootPath, {
            data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davGetDefaultPropfind)(),
            details: true,
            signal: abort.signal
          });
          resolve({
            // We only have one file as the content
            contents: [(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davResultToNode)(node.data)],
            // Fake a readonly folder as root
            folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Folder({
              id: 0,
              source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRemoteURL}${_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRootPath}`,
              root: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRootPath,
              owner: null,
              permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.READ,
              attributes: {
                // Ensure the share note is set on the root
                note: node.data.props?.note
              }
            })
          });
        } catch (e) {
          _services_logger__WEBPACK_IMPORTED_MODULE_5__["default"].error(e);
          reject(e);
        }
      });
    }
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
  Navigation.register(view);
});

/***/ }),

/***/ "./apps/files_sharing/src/files_views/publicShare.ts":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicShare.ts ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _files_src_services_Files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../files/src/services/Files */ "./apps/files/src/services/Files.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'public-share',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Public share'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Publicly shared files.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No files'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Files and folders shared with you will show up here'),
    icon: _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
    order: 1,
    getContents: _files_src_services_Files__WEBPACK_IMPORTED_MODULE_3__.getContents
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(view);
});

/***/ }),

/***/ "./apps/files_sharing/src/init-public.ts":
/*!***********************************************!*\
  !*** ./apps/files_sharing/src/init-public.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _files_views_publicFileDrop_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./files_views/publicFileDrop.ts */ "./apps/files_sharing/src/files_views/publicFileDrop.ts");
/* harmony import */ var _files_views_publicShare_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./files_views/publicShare.ts */ "./apps/files_sharing/src/files_views/publicShare.ts");
/* harmony import */ var _files_views_publicFileShare_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./files_views/publicFileShare.ts */ "./apps/files_sharing/src/files_views/publicFileShare.ts");
/* harmony import */ var _files_src_services_RouterService_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../files/src/services/RouterService.ts */ "./apps/files/src/services/RouterService.ts");
/* harmony import */ var _router_index_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./router/index.ts */ "./apps/files_sharing/src/router/index.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");









(0,_files_views_publicFileDrop_ts__WEBPACK_IMPORTED_MODULE_3__["default"])();
(0,_files_views_publicShare_ts__WEBPACK_IMPORTED_MODULE_4__["default"])();
(0,_files_views_publicFileShare_ts__WEBPACK_IMPORTED_MODULE_5__["default"])();
// Get the current view from state and set it active
const view = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_sharing', 'view');
const navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
navigation.setActive(navigation.views.find(_ref => {
  let {
    id
  } = _ref;
  return id === view;
}) ?? null);
// Force our own router
window.OCP.Files = window.OCP.Files ?? {};
window.OCP.Files.Router = new _files_src_services_RouterService_ts__WEBPACK_IMPORTED_MODULE_6__["default"](_router_index_ts__WEBPACK_IMPORTED_MODULE_7__["default"]);
// If this is a single file share, so set the fileid as active in the URL
const fileId = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_sharing', 'fileId', null);
const token = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_sharing', 'sharingToken');
if (fileId !== null) {
  window.OCP.Files.Router.goToRoute('filelist', {
    ...window.OCP.Files.Router.params,
    token,
    fileid: String(fileId)
  }, {
    ...window.OCP.Files.Router.query,
    openfile: 'true'
  });
}
// When the file list is loaded we need to apply the "userconfig" setup on the share
(0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:list:updated', loadShareConfig);
/**
 * Event handler to load the view config for the current share.
 * This is done on the `files:list:updated` event to ensure the list and especially the config store was correctly initialized.
 *
 * @param context The event context
 * @param context.folder The current folder
 */
function loadShareConfig(_ref2) {
  let {
    folder
  } = _ref2;
  // Only setup config once
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('files:list:updated', loadShareConfig);
  // Share attributes (the same) are set on all folders of a share
  if (folder.attributes['share-attributes']) {
    const shareAttributes = JSON.parse(folder.attributes['share-attributes'] || '[]');
    const gridViewAttribute = shareAttributes.find(_ref3 => {
      let {
        scope,
        key
      } = _ref3;
      return scope === 'config' && key === 'grid_view';
    });
    if (gridViewAttribute !== undefined) {
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_8__["default"].debug('Loading share attributes', {
        gridViewAttribute
      });
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:config:updated', {
        key: 'grid_view',
        value: gridViewAttribute.value === true
      });
    }
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/router/index.ts":
/*!************************************************!*\
  !*** ./apps/files_sharing/src/router/index.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var query_string__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! query-string */ "./node_modules/query-string/index.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_logger__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/logger */ "./apps/files_sharing/src/services/logger.ts");






const view = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'view');
const sharingToken = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'sharingToken');
vue__WEBPACK_IMPORTED_MODULE_3__["default"].use(vue_router__WEBPACK_IMPORTED_MODULE_4__["default"]);
// Prevent router from throwing errors when we're already on the page we're trying to go to
const originalPush = vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.push;
vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.push = function () {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }
  if (args.length > 1) {
    return originalPush.call(this, ...args);
  }
  return originalPush.call(this, args[0]).catch(ignoreDuplicateNavigation);
};
const originalReplace = vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.replace;
vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.replace = function () {
  for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    args[_key2] = arguments[_key2];
  }
  if (args.length > 1) {
    return originalReplace.call(this, ...args);
  }
  return originalReplace.call(this, args[0]).catch(ignoreDuplicateNavigation);
};
/**
 * Ignore duplicated-navigation error but forward real exceptions
 * @param error The thrown error
 */
function ignoreDuplicateNavigation(error) {
  if ((0,vue_router__WEBPACK_IMPORTED_MODULE_4__.isNavigationFailure)(error, vue_router__WEBPACK_IMPORTED_MODULE_4__.NavigationFailureType.duplicated)) {
    _services_logger__WEBPACK_IMPORTED_MODULE_2__["default"].debug('Ignoring duplicated navigation from vue-router', {
      error
    });
  } else {
    throw error;
  }
}
const router = new vue_router__WEBPACK_IMPORTED_MODULE_4__["default"]({
  mode: 'history',
  // if index.php is in the url AND we got this far, then it's working:
  // let's keep using index.php in the url
  base: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/s'),
  linkActiveClass: 'active',
  routes: [{
    path: '/',
    // Pretending we're using the default view
    redirect: {
      name: 'filelist',
      params: {
        view,
        token: sharingToken
      }
    }
  }, {
    path: '/:token',
    name: 'filelist',
    props: true
  }],
  // Custom stringifyQuery to prevent encoding of slashes in the url
  stringifyQuery(query) {
    const result = query_string__WEBPACK_IMPORTED_MODULE_5__["default"].stringify(query).replace(/%2F/gmi, '/');
    return result ? '?' + result : '';
  }
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (router);

/***/ }),

/***/ "./apps/files_sharing/src/services/logger.ts":
/*!***************************************************!*\
  !*** ./apps/files_sharing/src/services/logger.ts ***!
  \***************************************************/
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

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files_sharing').detectUser().build());

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/cloud-upload.svg?raw":
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/cloud-upload.svg?raw ***!
  \********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-cloud-upload\" viewBox=\"0 0 24 24\"><path d=\"M11 20H6.5Q4.22 20 2.61 18.43 1 16.85 1 14.58 1 12.63 2.17 11.1 3.35 9.57 5.25 9.15 5.88 6.85 7.75 5.43 9.63 4 12 4 14.93 4 16.96 6.04 19 8.07 19 11 20.73 11.2 21.86 12.5 23 13.78 23 15.5 23 17.38 21.69 18.69 20.38 20 18.5 20H13V12.85L14.6 14.4L16 13L12 9L8 13L9.4 14.4L11 12.85Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/link.svg?raw":
/*!************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/link.svg?raw ***!
  \************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-link\" viewBox=\"0 0 24 24\"><path d=\"M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z\" /></svg>";

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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"apps_files_sharing_src_views_FilesViewFileDropEmptyContent_vue":"b933bdec7f8a0f7c9459","node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"0a21f85fb5edb886fad0","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"5414d4143400c9b713c3","node_modules_nextcloud_upload_dist_chunks_InvalidFilenameDialog-BYpqWa7P_mjs":"377aaecad1616d034505","node_modules_nextcloud_upload_dist_chunks_ConflictPicker-BvM7ZujP_mjs":"8bb43b6fcd510001c73d","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e":"87f84948225387ac2eec"}[chunkId] + "";
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
/******/ 			"files_sharing-init-public": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_sharing/src/init-public.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_sharing-init-public.js.map?v=ecf2656c7c041f65834f