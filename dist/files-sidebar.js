/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

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

/***/ "./apps/files/src/sidebar.ts"
/*!***********************************!*\
  !*** ./apps/files/src/sidebar.ts ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_sidebar_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./store/sidebar.ts */ "./apps/files/src/store/sidebar.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


// Provide sidebar implementation which is proxied by the `@nextcloud/files` library for app usage.
window.OCA.Files ??= {};
window.OCA.Files._sidebar = () => (0,_store_sidebar_ts__WEBPACK_IMPORTED_MODULE_1__.useSidebarStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_0__.getPinia)());

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

/***/ "./apps/files/src/store/sidebar.ts"
/*!*****************************************!*\
  !*** ./apps/files/src/store/sidebar.ts ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useSidebarStore: () => (/* binding */ useSidebarStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");
/* harmony import */ var _active_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./active.ts */ "./apps/files/src/store/active.ts");
/* harmony import */ var _files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./files.ts */ "./apps/files/src/store/files.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const useSidebarStore = (0,pinia__WEBPACK_IMPORTED_MODULE_2__.defineStore)('sidebar', () => {
  const activeTab = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)();
  const isOpen = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)(false);
  const activeStore = (0,_active_ts__WEBPACK_IMPORTED_MODULE_5__.useActiveStore)();
  const currentNode = (0,vue__WEBPACK_IMPORTED_MODULE_3__.computed)(() => isOpen.value ? activeStore.activeNode : undefined);
  const hasContext = (0,vue__WEBPACK_IMPORTED_MODULE_3__.computed)(() => !!(currentNode.value && activeStore.activeFolder && activeStore.activeView));
  const currentContext = (0,vue__WEBPACK_IMPORTED_MODULE_3__.computed)(() => {
    if (!hasContext.value) {
      return;
    }
    return {
      node: currentNode.value,
      folder: activeStore.activeFolder,
      view: activeStore.activeView
    };
  });
  const currentActions = (0,vue__WEBPACK_IMPORTED_MODULE_3__.computed)(() => currentContext.value ? getActions(currentContext.value) : []);
  const currentTabs = (0,vue__WEBPACK_IMPORTED_MODULE_3__.computed)(() => currentContext.value ? getTabs(currentContext.value) : []);
  /**
   * Open the sidebar for a given node and optional tab ID.
   *
   * @param node - The node to display in the sidebar.
   * @param tabId - Optional ID of the tab to activate.
   */
  function open(node, tabId) {
    if (!(node && activeStore.activeFolder && activeStore.activeView)) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('sidebar: cannot open sidebar because the active folder or view is not set.', {
        node,
        activeFolder: activeStore.activeFolder,
        activeView: activeStore.activeView
      });
      throw new Error('Cannot open sidebar because the active folder or view is not set.');
    }
    if (isOpen.value && currentNode.value?.source === node.source) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('sidebar: already open for current node');
      if (tabId) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('sidebar: already open for current node - switching tab', {
          tabId
        });
        setActiveTab(tabId);
      }
      return;
    }
    const newTabs = getTabs({
      node,
      folder: activeStore.activeFolder,
      view: activeStore.activeView
    });
    if (tabId && !newTabs.find(({
      id
    }) => id === tabId)) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.warn(`sidebar: cannot open tab '${tabId}' because it is not available for the current context.`);
      activeTab.value = newTabs[0]?.id;
    } else {
      activeTab.value = tabId ?? newTabs[0]?.id;
    }
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug(`sidebar: opening for ${node.displayname}`, {
      node
    });
    activeStore.activeNode = node;
    isOpen.value = true;
  }
  /**
   * Close the sidebar.
   */
  function close() {
    isOpen.value = false;
  }
  /**
   * Get the available tabs for the sidebar.
   * If a context is provided, only tabs enabled for that context are returned.
   *
   * @param context - Optional context to filter the available tabs.
   */
  function getTabs(context) {
    let tabs = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getSidebarTabs)();
    if (context) {
      tabs = tabs.filter(tab => tab.enabled === undefined || tab.enabled(context));
    }
    return tabs.sort((a, b) => a.order - b.order);
  }
  /**
   * Get the available actions for the sidebar.
   * If a context is provided, only actions enabled for that context are returned.
   *
   * @param context - Optional context to filter the available actions.
   */
  function getActions(context) {
    let actions = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getSidebarActions)();
    if (context) {
      actions = actions.filter(action => action.enabled === undefined || action.enabled(context));
    }
    return actions.sort((a, b) => a.order - b.order);
  }
  /**
   * Set the active tab in the sidebar.
   *
   * @param tabId - The ID of the tab to activate.
   */
  function setActiveTab(tabId) {
    if (!currentTabs.value.find(({
      id
    }) => id === tabId)) {
      throw new Error(`Cannot set sidebar tab '${tabId}' because it is not available for the current context.`);
    }
    activeTab.value = tabId;
  }
  // update the current node if updated
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:updated', node => {
    if (node.source === currentNode.value?.source) {
      activeStore.activeNode = node;
    }
  });
  // close the sidebar if the current node is deleted
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:deleted', node => {
    if (node.fileid === currentNode.value?.fileid) {
      close();
    }
  });
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('viewer:sidebar:open', ({
    source
  }) => {
    const filesStore = (0,_files_ts__WEBPACK_IMPORTED_MODULE_6__.useFilesStore)();
    const node = filesStore.getNode(source);
    if (node) {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('sidebar: opening for node from Viewer.', {
        node
      });
      open(node);
    } else {
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.error(`sidebar: cannot open for node '${source}' because it was not found in the current view.`);
    }
  });
  let initialized = false;
  // close sidebar when parameter is removed from url
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:list:updated', () => {
    if (!initialized) {
      initialized = true;
      window.OCP.Files.Router._router.afterEach((to, from) => {
        if (from.query && 'opendetails' in from.query && to.query && !('opendetails' in to.query)) {
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug('sidebar: closing because "opendetails" query parameter was removed from URL.');
          close();
        }
      });
    }
  });
  // watch open state and update URL query parameters
  (0,vue__WEBPACK_IMPORTED_MODULE_3__.watch)(isOpen, isOpen => {
    const params = {
      ...(window.OCP?.Files?.Router?.params ?? {})
    };
    const query = {
      ...(window.OCP?.Files?.Router?.query ?? {})
    };
    _utils_logger_ts__WEBPACK_IMPORTED_MODULE_4__.logger.debug(`sidebar: current node changed: ${isOpen ? 'open' : 'closed'}`, {
      query,
      params,
      node: activeStore.activeNode
    });
    if (!isOpen && 'opendetails' in query) {
      delete query.opendetails;
      window.OCP.Files.Router.goToRoute(null, params, query, true);
    }
    if (isOpen && !('opendetails' in query)) {
      window.OCP.Files.Router.goToRoute(null, params, {
        ...query,
        opendetails: 'true'
      }, true);
    }
  });
  return {
    activeTab,
    currentActions,
    currentContext,
    currentNode,
    currentTabs,
    hasContext,
    isOpen: (0,vue__WEBPACK_IMPORTED_MODULE_3__.readonly)(isOpen),
    open,
    close,
    getActions,
    getTabs,
    setActiveTab
  };
});

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
/******/ 		// The chunk loading function for additional chunks
/******/ 		// Since all referenced chunks are already included
/******/ 		// in this file, this function is empty here.
/******/ 		__webpack_require__.e = () => (Promise.resolve());
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
/******/ 		__webpack_require__.b = (typeof document !== 'undefined' && document.baseURI) || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files-sidebar": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files/src/sidebar.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-sidebar.js.map?v=5c79afcef6bb1e765a76