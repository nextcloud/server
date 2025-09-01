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
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
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
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
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
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var query_string__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! query-string */ "./node_modules/query-string/index.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_logger__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger */ "./apps/files_sharing/src/services/logger.ts");






const view = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'view');
const sharingToken = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'sharingToken');
vue__WEBPACK_IMPORTED_MODULE_4__["default"].use(vue_router__WEBPACK_IMPORTED_MODULE_3__["default"]);
// Prevent router from throwing errors when we're already on the page we're trying to go to
const originalPush = vue_router__WEBPACK_IMPORTED_MODULE_3__["default"].prototype.push;
vue_router__WEBPACK_IMPORTED_MODULE_3__["default"].prototype.push = function () {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }
  if (args.length > 1) {
    return originalPush.call(this, ...args);
  }
  return originalPush.call(this, args[0]).catch(ignoreDuplicateNavigation);
};
const originalReplace = vue_router__WEBPACK_IMPORTED_MODULE_3__["default"].prototype.replace;
vue_router__WEBPACK_IMPORTED_MODULE_3__["default"].prototype.replace = function () {
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
  if ((0,vue_router__WEBPACK_IMPORTED_MODULE_3__.isNavigationFailure)(error, vue_router__WEBPACK_IMPORTED_MODULE_3__.NavigationFailureType.duplicated)) {
    _services_logger__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Ignoring duplicated navigation from vue-router', {
      error
    });
  } else {
    throw error;
  }
}
const router = new vue_router__WEBPACK_IMPORTED_MODULE_3__["default"]({
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
    const result = query_string__WEBPACK_IMPORTED_MODULE_2__["default"].stringify(query).replace(/%2F/gmi, '/');
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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"apps_files_src_views_SearchEmptyView_vue":"f4a1d1b5018f4999b20a","apps_files_sharing_src_views_FilesViewFileDropEmptyContent_vue":"d12ea874036daf41221e","node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"2fcef36253529e5f48bc","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"f3a3966faa81f9b81fa8","node_modules_nextcloud_upload_dist_chunks_InvalidFilenameDialog-BYpqWa7P_mjs":"b5048b7dd1c81ffc8fe6","node_modules_nextcloud_upload_dist_chunks_ConflictPicker-BvM7ZujP_mjs":"f457ef4faf127a8a345a","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-CsU6FfAP_mjs":"8bce3ebf3ef868f175e5","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"9fa10a9863e5b78deec8"}[chunkId] + "";
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
//# sourceMappingURL=files_sharing-init-public.js.map?v=20c88c955bfe0b7c5278