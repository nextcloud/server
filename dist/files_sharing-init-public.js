/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

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

/***/ "./apps/files/src/services/RouterService.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/services/RouterService.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ RouterService)
/* harmony export */ });
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
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
   *
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
  goTo(path, replace = false) {
    return this.router.push({
      path,
      replace
    });
  }
  /**
   * Trigger a route change on the files App
   *
   * @param name - The route name or null to keep current route and just update params/query
   * @param params the route parameters
   * @param query the url query parameters
   * @param replace replace the current history
   * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
   */
  goToRoute(name, params, query, replace) {
    name ??= this.router.currentRoute.name;
    const location = {
      name,
      query,
      params
    };
    if (replace) {
      return this._router.replace(location);
    }
    return this._router.push(location);
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

/***/ "./apps/files_sharing/src/files_views/publicFileDrop.ts"
/*!**************************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicFileDrop.ts ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_cloud_upload_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/cloud-upload.svg?raw */ "./node_modules/@mdi/svg/svg/cloud-upload.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const foldername = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files_sharing', 'filename');
  let FilesViewFileDropEmptyContent;
  let fileDropEmptyContentInstance;
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'public-file-drop',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_sharing', 'File drop'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_sharing', 'Upload files to {foldername}', {
      foldername
    }),
    icon: _mdi_svg_svg_cloud_upload_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
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
        folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
          id: 0,
          source: `${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRemoteURL}${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRootPath}`,
          root: _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRootPath,
          owner: null,
          permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.CREATE
        })
      };
    }
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(view);
});

/***/ },

/***/ "./apps/files_sharing/src/files_views/publicFileShare.ts"
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicFileShare.ts ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _files_src_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../files/src/services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'public-file-share',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'Public file share'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'Publicly shared file.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'No file'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'The file shared with you will show up here'),
    icon: _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    order: 1,
    getContents: async (path, {
      signal
    }) => {
      try {
        const node = await _files_src_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__.client.stat((0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)(), {
          data: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getDefaultPropfind)(),
          details: true,
          signal
        });
        return {
          // We only have one file as the content
          contents: [(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.resultToNode)(node.data)],
          // Fake a readonly folder as root
          folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
            id: 0,
            source: `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRemoteURL)()}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)()}`,
            root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getRootPath)(),
            owner: null,
            permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ,
            attributes: {
              // Ensure the share note is set on the root
              note: node.data.props?.note
            }
          })
        };
      } catch (error) {
        if (signal.aborted) {
          _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].info('Fetching contents for public file share was aborted', {
            error
          });
          throw new DOMException('Aborted', 'AbortError');
        }
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error('Failed to get contents for public file share', {
          error
        });
        throw error;
      }
    }
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(view);
});

/***/ },

/***/ "./apps/files_sharing/src/files_views/publicShare.ts"
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/files_views/publicShare.ts ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _files_src_services_Files_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../files/src/services/Files.ts */ "./apps/files/src/services/Files.ts");


/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const view = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'public-share',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Public share'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Publicly shared files.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'No files'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Files and folders shared with you will show up here'),
    icon: _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
    order: 1,
    getContents: _files_src_services_Files_ts__WEBPACK_IMPORTED_MODULE_3__.getContents
  });
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(view);
});

/***/ },

/***/ "./apps/files_sharing/src/init-public.ts"
/*!***********************************************!*\
  !*** ./apps/files_sharing/src/init-public.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _files_src_services_RouterService_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../files/src/services/RouterService.ts */ "./apps/files/src/services/RouterService.ts");
/* harmony import */ var _files_views_publicFileDrop_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./files_views/publicFileDrop.ts */ "./apps/files_sharing/src/files_views/publicFileDrop.ts");
/* harmony import */ var _files_views_publicFileShare_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./files_views/publicFileShare.ts */ "./apps/files_sharing/src/files_views/publicFileShare.ts");
/* harmony import */ var _files_views_publicShare_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./files_views/publicShare.ts */ "./apps/files_sharing/src/files_views/publicShare.ts");
/* harmony import */ var _router_index_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./router/index.ts */ "./apps/files_sharing/src/router/index.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */









(0,_files_views_publicFileDrop_ts__WEBPACK_IMPORTED_MODULE_4__["default"])();
(0,_files_views_publicShare_ts__WEBPACK_IMPORTED_MODULE_6__["default"])();
(0,_files_views_publicFileShare_ts__WEBPACK_IMPORTED_MODULE_5__["default"])();
// Get the current view from state and set it active
const view = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_sharing', 'view');
const navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
try {
  navigation.setActive(view);
} catch {
  // no such view
  navigation.setActive(null);
}
// Force our own router
window.OCP.Files = window.OCP.Files ?? {};
window.OCP.Files.Router = new _files_src_services_RouterService_ts__WEBPACK_IMPORTED_MODULE_3__["default"](_router_index_ts__WEBPACK_IMPORTED_MODULE_7__["default"]);
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
function loadShareConfig({
  folder
}) {
  // Only setup config once
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('files:list:updated', loadShareConfig);
  // Share attributes (the same) are set on all folders of a share
  if (folder.attributes['share-attributes']) {
    const shareAttributes = JSON.parse(folder.attributes['share-attributes'] || '[]');
    const gridViewAttribute = shareAttributes.find(({
      scope,
      key
    }) => scope === 'config' && key === 'grid_view');
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

/***/ },

/***/ "./apps/files_sharing/src/router/index.ts"
/*!************************************************!*\
  !*** ./apps/files_sharing/src/router/index.ts ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var query_string__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! query-string */ "./node_modules/query-string/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");






const view = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'view');
const sharingToken = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'sharingToken');
vue__WEBPACK_IMPORTED_MODULE_3__["default"].use(vue_router__WEBPACK_IMPORTED_MODULE_4__["default"]);
// Prevent router from throwing errors when we're already on the page we're trying to go to
const originalPush = vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.push;
vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.push = function (...args) {
  if (args.length > 1) {
    return originalPush.call(this, ...args);
  }
  return originalPush.call(this, args[0]).catch(ignoreDuplicateNavigation);
};
const originalReplace = vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.replace;
vue_router__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.replace = function (...args) {
  if (args.length > 1) {
    return originalReplace.call(this, ...args);
  }
  return originalReplace.call(this, args[0]).catch(ignoreDuplicateNavigation);
};
/**
 * Ignore duplicated- and redirected-navigation errors but forward real exceptions
 *
 * @param error The thrown error
 */
function ignoreDuplicateNavigation(error) {
  if ((0,vue_router__WEBPACK_IMPORTED_MODULE_4__.isNavigationFailure)(error, vue_router__WEBPACK_IMPORTED_MODULE_4__.NavigationFailureType.duplicated) || (0,vue_router__WEBPACK_IMPORTED_MODULE_4__.isNavigationFailure)(error, vue_router__WEBPACK_IMPORTED_MODULE_4__.NavigationFailureType.redirected)) {
    _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Ignoring duplicated/redirected navigation from vue-router', {
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
    const result = query_string__WEBPACK_IMPORTED_MODULE_2__["default"].stringify(query).replace(/%2F/gmi, '/');
    return result ? '?' + result : '';
  }
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (router);

/***/ },

/***/ "./apps/files_sharing/src/services/logger.ts"
/*!***************************************************!*\
  !*** ./apps/files_sharing/src/services/logger.ts ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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

/***/ },

/***/ "./node_modules/@mdi/svg/svg/cloud-upload.svg?raw"
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/cloud-upload.svg?raw ***!
  \********************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-cloud-upload\" viewBox=\"0 0 24 24\"><path d=\"M11 20H6.5Q4.22 20 2.61 18.43 1 16.85 1 14.58 1 12.63 2.17 11.1 3.35 9.57 5.25 9.15 5.88 6.85 7.75 5.43 9.63 4 12 4 14.93 4 16.96 6.04 19 8.07 19 11 20.73 11.2 21.86 12.5 23 13.78 23 15.5 23 17.38 21.69 18.69 20.38 20 18.5 20H13V12.85L14.6 14.4L16 13L12 9L8 13L9.4 14.4L11 12.85Z\" /></svg>";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/link.svg?raw"
/*!************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/link.svg?raw ***!
  \************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-link\" viewBox=\"0 0 24 24\"><path d=\"M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z\" /></svg>";

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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"apps_files_src_views_SearchEmptyView_vue":"d08bb81cacb7e1624360","apps_files_sharing_src_views_FilesViewFileDropEmptyContent_vue":"c8d7deb75f5088afef76","node_modules_nextcloud_upload_dist_chunks_ConflictPicker-DuPiUBHl_mjs":"da3acfec46893a3999c7","node_modules_nextcloud_upload_dist_chunks_InvalidFilenameDialog-BM2VDeLo_mjs":"1beb4916241413155fba","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_index-BMbtc3xh_mjs":"313d1a40718226ae766e","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-CfO-95d64a":"35af0b481109fcc029fa","node_modules_nextcloud_upload_node_modules_nextcloud_dialogs_dist_chunks_FilePicker-JKNLPCbR_mjs":"32bb6d3d92cda43cf51a","node_modules_nextcloud_vue_dist_Components_NcColorPicker_mjs":"cc9a80a105a480079016","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"21fc91c563f5cd8d04c3"}[chunkId] + "";
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_sharing/src/init-public.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_sharing-init-public.js.map?v=1506e8cb687908f72cb7