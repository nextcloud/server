/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/settings/src/constants/GroupManagement.ts":
/*!********************************************************!*\
  !*** ./apps/settings/src/constants/GroupManagement.ts ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GroupSorting: () => (/* binding */ GroupSorting)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/**
 * https://github.com/nextcloud/server/blob/208e38e84e1a07a49699aa90dc5b7272d24489f0/lib/private/Group/MetaData.php#L34
 */
var GroupSorting;
(function (GroupSorting) {
  GroupSorting[GroupSorting["UserCount"] = 1] = "UserCount";
  GroupSorting[GroupSorting["GroupName"] = 2] = "GroupName";
})(GroupSorting || (GroupSorting = {}));

/***/ }),

/***/ "./apps/settings/src/logger.ts":
/*!*************************************!*\
  !*** ./apps/settings/src/logger.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('settings').detectUser().build());

/***/ }),

/***/ "./apps/settings/src/main-apps-users-management.ts":
/*!*********************************************************!*\
  !*** ./apps/settings/src/main-apps-users-management.ts ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vuex__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vuex */ "./node_modules/vuex/dist/vuex.esm.js");
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var vuex_router_sync__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vuex-router-sync */ "./node_modules/vuex-router-sync/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_SettingsApp_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./views/SettingsApp.vue */ "./apps/settings/src/views/SettingsApp.vue");
/* harmony import */ var _router_index_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./router/index.ts */ "./apps/settings/src/router/index.ts");
/* harmony import */ var _store_index_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./store/index.js */ "./apps/settings/src/store/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */










// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line camelcase
__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_8__.getCSPNonce)();
// bind to window
vue__WEBPACK_IMPORTED_MODULE_0__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t;
vue__WEBPACK_IMPORTED_MODULE_0__["default"].prototype.n = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.n;
vue__WEBPACK_IMPORTED_MODULE_0__["default"].use(pinia__WEBPACK_IMPORTED_MODULE_9__.PiniaVuePlugin);
vue__WEBPACK_IMPORTED_MODULE_0__["default"].use(v_tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
  defaultHtml: false
});
vue__WEBPACK_IMPORTED_MODULE_0__["default"].use(vuex__WEBPACK_IMPORTED_MODULE_1__["default"]);
const store = (0,_store_index_js__WEBPACK_IMPORTED_MODULE_7__.useStore)();
(0,vuex_router_sync__WEBPACK_IMPORTED_MODULE_3__.sync)(store, _router_index_ts__WEBPACK_IMPORTED_MODULE_6__["default"]);
const pinia = (0,pinia__WEBPACK_IMPORTED_MODULE_9__.createPinia)();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (new vue__WEBPACK_IMPORTED_MODULE_0__["default"]({
  router: _router_index_ts__WEBPACK_IMPORTED_MODULE_6__["default"],
  store,
  pinia,
  render: h => h(_views_SettingsApp_vue__WEBPACK_IMPORTED_MODULE_5__["default"]),
  el: '#content'
}));

/***/ }),

/***/ "./apps/settings/src/router/index.ts":
/*!*******************************************!*\
  !*** ./apps/settings/src/router/index.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _routes_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./routes.ts */ "./apps/settings/src/router/routes.ts");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




vue__WEBPACK_IMPORTED_MODULE_0__["default"].use(vue_router__WEBPACK_IMPORTED_MODULE_1__["default"]);
const router = new vue_router__WEBPACK_IMPORTED_MODULE_1__["default"]({
  mode: 'history',
  // if index.php is in the url AND we got this far, then it's working:
  // let's keep using index.php in the url
  base: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(''),
  linkActiveClass: 'active',
  routes: _routes_ts__WEBPACK_IMPORTED_MODULE_3__["default"]
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (router);

/***/ }),

/***/ "./apps/settings/src/router/routes.ts":
/*!********************************************!*\
  !*** ./apps/settings/src/router/routes.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");

const appstoreEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'appstoreEnabled', true);
// Dynamic loading
const AppStore = () => Promise.all(/*! import() | settings-apps-view */[__webpack_require__.e("core-common"), __webpack_require__.e("settings-apps-view")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/AppStore.vue */ "./apps/settings/src/views/AppStore.vue"));
const AppStoreNavigation = () => Promise.all(/*! import() | settings-apps-view */[__webpack_require__.e("core-common"), __webpack_require__.e("settings-apps-view")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/AppStoreNavigation.vue */ "./apps/settings/src/views/AppStoreNavigation.vue"));
const AppStoreSidebar = () => Promise.all(/*! import() | settings-apps-view */[__webpack_require__.e("core-common"), __webpack_require__.e("settings-apps-view")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/AppStoreSidebar.vue */ "./apps/settings/src/views/AppStoreSidebar.vue"));
const UserManagement = () => Promise.all(/*! import() | settings-users */[__webpack_require__.e("core-common"), __webpack_require__.e("settings-users")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/UserManagement.vue */ "./apps/settings/src/views/UserManagement.vue"));
const UserManagementNavigation = () => Promise.all(/*! import() | settings-users */[__webpack_require__.e("core-common"), __webpack_require__.e("settings-users")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/UserManagementNavigation.vue */ "./apps/settings/src/views/UserManagementNavigation.vue"));
const routes = [{
  name: 'users',
  path: '/:index(index.php/)?settings/users',
  components: {
    default: UserManagement,
    navigation: UserManagementNavigation
  },
  props: true,
  children: [{
    path: ':selectedGroup',
    name: 'group'
  }]
}, {
  path: '/:index(index.php/)?settings/apps',
  name: 'apps',
  redirect: {
    name: 'apps-category',
    params: {
      category: appstoreEnabled ? 'discover' : 'installed'
    }
  },
  components: {
    default: AppStore,
    navigation: AppStoreNavigation,
    sidebar: AppStoreSidebar
  },
  children: [{
    path: ':category',
    name: 'apps-category',
    children: [{
      path: ':id',
      name: 'apps-details'
    }]
  }]
}];
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (routes);

/***/ }),

/***/ "./apps/settings/src/store/api.js":
/*!****************************************!*\
  !*** ./apps/settings/src/store/api.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const sanitize = function (url) {
  return url.replace(/\/$/, ''); // Remove last url slash
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  /**
   * This Promise is used to chain a request that require an admin password confirmation
   * Since chaining Promise have a very precise behavior concerning catch and then,
   * you'll need to be careful when using it.
   * e.g
   * // store
   * action(context) {
   *   return api.requireAdmin().then((response) => {
   *     return api.get('url')
   *       .then((response) => {API success})
   *       .catch((error) => {API failure});
   *   }).catch((error) => {requireAdmin failure});
   * }
   * // vue
   * this.$store.dispatch('action').then(() => {always executed})
   *
   * Since Promise.then().catch().then() will always execute the last then
   * this.$store.dispatch('action').then will always be executed
   *
   * If you want requireAdmin failure to also catch the API request failure
   * you will need to throw a new error in the api.get.catch()
   *
   * e.g
   * api.requireAdmin().then((response) => {
   *   api.get('url')
   *     .then((response) => {API success})
   *     .catch((error) => {throw error;});
   * }).catch((error) => {requireAdmin OR API failure});
   *
   * @return {Promise}
   */
  requireAdmin() {
    return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__.confirmPassword)();
  },
  get(url, options) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(sanitize(url), options);
  },
  post(url, data) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(sanitize(url), data);
  },
  patch(url, data) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].patch(sanitize(url), data);
  },
  put(url, data) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(sanitize(url), data);
  },
  delete(url, data) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(sanitize(url), {
      params: data
    });
  }
});

/***/ }),

/***/ "./apps/settings/src/store/apps.js":
/*!*****************************************!*\
  !*** ./apps/settings/src/store/apps.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const state = {
  apps: [],
  bundles: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('settings', 'appstoreBundles', []),
  categories: [],
  updateCount: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('settings', 'appstoreUpdateCount', 0),
  loading: {},
  gettingCategoriesPromise: null,
  appApiEnabled: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('settings', 'appApiEnabled', false)
};
const mutations = {
  APPS_API_FAILURE(state, error) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, {
      isHTML: true
    });
    console.error(state, error);
  },
  initCategories(state, _ref) {
    let {
      categories,
      updateCount
    } = _ref;
    state.categories = categories;
    state.updateCount = updateCount;
  },
  updateCategories(state, categoriesPromise) {
    state.gettingCategoriesPromise = categoriesPromise;
  },
  setUpdateCount(state, updateCount) {
    state.updateCount = updateCount;
  },
  addCategory(state, category) {
    state.categories.push(category);
  },
  appendCategories(state, categoriesArray) {
    // convert obj to array
    state.categories = categoriesArray;
  },
  setAllApps(state, apps) {
    state.apps = apps;
  },
  setError(state, _ref2) {
    let {
      appId,
      error
    } = _ref2;
    if (!Array.isArray(appId)) {
      appId = [appId];
    }
    appId.forEach(_id => {
      const app = state.apps.find(app => app.id === _id);
      app.error = error;
    });
  },
  clearError(state, _ref3) {
    let {
      appId,
      error
    } = _ref3;
    const app = state.apps.find(app => app.id === appId);
    app.error = null;
  },
  enableApp(state, _ref4) {
    let {
      appId,
      groups
    } = _ref4;
    const app = state.apps.find(app => app.id === appId);
    app.active = true;
    app.groups = groups;
    if (app.id === 'app_api') {
      state.appApiEnabled = true;
    }
  },
  setInstallState(state, _ref5) {
    let {
      appId,
      canInstall
    } = _ref5;
    const app = state.apps.find(app => app.id === appId);
    if (app) {
      app.canInstall = canInstall === true;
    }
  },
  disableApp(state, appId) {
    const app = state.apps.find(app => app.id === appId);
    app.active = false;
    app.groups = [];
    if (app.removable) {
      app.canUnInstall = true;
    }
    if (app.id === 'app_api') {
      state.appApiEnabled = false;
    }
  },
  uninstallApp(state, appId) {
    state.apps.find(app => app.id === appId).active = false;
    state.apps.find(app => app.id === appId).groups = [];
    state.apps.find(app => app.id === appId).needsDownload = true;
    state.apps.find(app => app.id === appId).installed = false;
    state.apps.find(app => app.id === appId).canUnInstall = false;
    state.apps.find(app => app.id === appId).canInstall = true;
    if (appId === 'app_api') {
      state.appApiEnabled = false;
    }
  },
  updateApp(state, appId) {
    const app = state.apps.find(app => app.id === appId);
    const version = app.update;
    app.update = null;
    app.version = version;
    state.updateCount--;
  },
  resetApps(state) {
    state.apps = [];
  },
  reset(state) {
    state.apps = [];
    state.categories = [];
    state.updateCount = 0;
  },
  startLoading(state, id) {
    if (Array.isArray(id)) {
      id.forEach(_id => {
        vue__WEBPACK_IMPORTED_MODULE_1__["default"].set(state.loading, _id, true);
      });
    } else {
      vue__WEBPACK_IMPORTED_MODULE_1__["default"].set(state.loading, id, true);
    }
  },
  stopLoading(state, id) {
    if (Array.isArray(id)) {
      id.forEach(_id => {
        vue__WEBPACK_IMPORTED_MODULE_1__["default"].set(state.loading, _id, false);
      });
    } else {
      vue__WEBPACK_IMPORTED_MODULE_1__["default"].set(state.loading, id, false);
    }
  }
};
const getters = {
  isAppApiEnabled(state) {
    return state.appApiEnabled;
  },
  loading(state) {
    return function (id) {
      return state.loading[id];
    };
  },
  getCategories(state) {
    return state.categories;
  },
  getAllApps(state) {
    return state.apps;
  },
  getAppBundles(state) {
    return state.bundles;
  },
  getUpdateCount(state) {
    return state.updateCount;
  },
  getCategoryById: state => selectedCategoryId => {
    return state.categories.find(category => category.id === selectedCategoryId);
  }
};
const actions = {
  enableApp(context, _ref6) {
    let {
      appId,
      groups
    } = _ref6;
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(response => {
      context.commit('startLoading', apps);
      context.commit('startLoading', 'install');
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/enable'), {
        appIds: apps,
        groups
      }).then(response => {
        context.commit('stopLoading', apps);
        context.commit('stopLoading', 'install');
        apps.forEach(_appId => {
          context.commit('enableApp', {
            appId: _appId,
            groups
          });
        });

        // check for server health
        return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('apps/files/')).then(() => {
          if (response.data.update_required) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showInfo)(t('settings', 'The app has been enabled but needs to be updated. You will be redirected to the update page in 5 seconds.'), {
              onClick: () => window.location.reload(),
              close: false
            });
            setTimeout(function () {
              location.reload();
            }, 5000);
          }
        }).catch(() => {
          if (!Array.isArray(appId)) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Error: This app cannot be enabled because it makes the server unstable'));
            context.commit('setError', {
              appId: apps,
              error: t('settings', 'Error: This app cannot be enabled because it makes the server unstable')
            });
            context.dispatch('disableApp', {
              appId
            });
          }
        });
      }).catch(error => {
        context.commit('stopLoading', apps);
        context.commit('stopLoading', 'install');
        context.commit('setError', {
          appId: apps,
          error: error.response.data.data.message
        });
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      });
    }).catch(error => context.commit('API_FAILURE', {
      appId,
      error
    }));
  },
  forceEnableApp(context, _ref7) {
    let {
      appId,
      groups
    } = _ref7;
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(() => {
      context.commit('startLoading', apps);
      context.commit('startLoading', 'install');
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/force'), {
        appId
      }).then(response => {
        context.commit('setInstallState', {
          appId,
          canInstall: true
        });
      }).catch(error => {
        context.commit('stopLoading', apps);
        context.commit('stopLoading', 'install');
        context.commit('setError', {
          appId: apps,
          error: error.response.data.data.message
        });
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      }).finally(() => {
        context.commit('stopLoading', apps);
        context.commit('stopLoading', 'install');
      });
    }).catch(error => context.commit('API_FAILURE', {
      appId,
      error
    }));
  },
  disableApp(context, _ref8) {
    let {
      appId
    } = _ref8;
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(response => {
      context.commit('startLoading', apps);
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/disable'), {
        appIds: apps
      }).then(response => {
        context.commit('stopLoading', apps);
        apps.forEach(_appId => {
          context.commit('disableApp', _appId);
        });
        return true;
      }).catch(error => {
        context.commit('stopLoading', apps);
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      });
    }).catch(error => context.commit('API_FAILURE', {
      appId,
      error
    }));
  },
  uninstallApp(context, _ref9) {
    let {
      appId
    } = _ref9;
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(response => {
      context.commit('startLoading', appId);
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)(`settings/apps/uninstall/${appId}`)).then(response => {
        context.commit('stopLoading', appId);
        context.commit('uninstallApp', appId);
        return true;
      }).catch(error => {
        context.commit('stopLoading', appId);
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      });
    }).catch(error => context.commit('API_FAILURE', {
      appId,
      error
    }));
  },
  updateApp(context, _ref0) {
    let {
      appId
    } = _ref0;
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(response => {
      context.commit('startLoading', appId);
      context.commit('startLoading', 'install');
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)(`settings/apps/update/${appId}`)).then(response => {
        context.commit('stopLoading', 'install');
        context.commit('stopLoading', appId);
        context.commit('updateApp', appId);
        return true;
      }).catch(error => {
        context.commit('stopLoading', appId);
        context.commit('stopLoading', 'install');
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      });
    }).catch(error => context.commit('API_FAILURE', {
      appId,
      error
    }));
  },
  getAllApps(context) {
    context.commit('startLoading', 'list');
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/list')).then(response => {
      context.commit('setAllApps', response.data.apps);
      context.commit('stopLoading', 'list');
      return true;
    }).catch(error => context.commit('API_FAILURE', error));
  },
  async getCategories(context) {
    let {
      shouldRefetchCategories = false
    } = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    if (shouldRefetchCategories || !context.state.gettingCategoriesPromise) {
      context.commit('startLoading', 'categories');
      try {
        const categoriesPromise = _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/categories'));
        context.commit('updateCategories', categoriesPromise);
        const categoriesPromiseResponse = await categoriesPromise;
        if (categoriesPromiseResponse.data.length > 0) {
          context.commit('appendCategories', categoriesPromiseResponse.data);
          context.commit('stopLoading', 'categories');
          return true;
        }
        context.commit('stopLoading', 'categories');
        return false;
      } catch (error) {
        context.commit('API_FAILURE', error);
      }
    }
    return context.state.gettingCategoriesPromise;
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  state,
  mutations,
  getters,
  actions
});

/***/ }),

/***/ "./apps/settings/src/store/index.js":
/*!******************************************!*\
  !*** ./apps/settings/src/store/index.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useStore: () => (/* binding */ useStore)
/* harmony export */ });
/* harmony import */ var vuex__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vuex */ "./node_modules/vuex/dist/vuex.esm.js");
/* harmony import */ var _users_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./users.js */ "./apps/settings/src/store/users.js");
/* harmony import */ var _apps_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./apps.js */ "./apps/settings/src/store/apps.js");
/* harmony import */ var _users_settings_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./users-settings.js */ "./apps/settings/src/store/users-settings.js");
/* harmony import */ var _oc_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./oc.js */ "./apps/settings/src/store/oc.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const debug = "development" !== 'production';
const mutations = {
  API_FAILURE(state, error) {
    try {
      const message = error.error.response.data.ocs.meta.message;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + message, {
        isHTML: true
      });
    } catch (e) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.'));
    }
    console.error(state, error);
  }
};
let store = null;
const useStore = () => {
  if (store === null) {
    store = new vuex__WEBPACK_IMPORTED_MODULE_0__.Store({
      modules: {
        users: _users_js__WEBPACK_IMPORTED_MODULE_1__["default"],
        apps: _apps_js__WEBPACK_IMPORTED_MODULE_2__["default"],
        settings: _users_settings_js__WEBPACK_IMPORTED_MODULE_3__["default"],
        oc: _oc_js__WEBPACK_IMPORTED_MODULE_4__["default"]
      },
      strict: debug,
      mutations
    });
  }
  return store;
};

/***/ }),

/***/ "./apps/settings/src/store/oc.js":
/*!***************************************!*\
  !*** ./apps/settings/src/store/oc.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const state = {};
const mutations = {};
const getters = {};
const actions = {
  /**
   * Set application config in database
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.app Application name
   * @param {boolean} options.key Config key
   * @param {boolean} options.value Value to set
   * @return {Promise}
   */
  setAppConfig(context, _ref) {
    let {
      app,
      key,
      value
    } = _ref;
    return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/provisioning_api/api/v1/config/apps/{app}/{key}', {
        app,
        key
      }), {
        value
      }).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      app,
      key,
      value,
      error
    }));
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  state,
  mutations,
  getters,
  actions
});

/***/ }),

/***/ "./apps/settings/src/store/users-settings.js":
/*!***************************************************!*\
  !*** ./apps/settings/src/store/users-settings.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const state = {
  serverData: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'usersSettings', {})
};
const mutations = {
  setServerData(state, data) {
    state.serverData = data;
  }
};
const getters = {
  getServerData(state) {
    return state.serverData;
  }
};
const actions = {};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  state,
  mutations,
  getters,
  actions
});

/***/ }),

/***/ "./apps/settings/src/store/users.js":
/*!******************************************!*\
  !*** ./apps/settings/src/store/users.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_browser_storage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/browser-storage */ "./node_modules/@nextcloud/browser-storage/dist/index.js");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../constants/GroupManagement.ts */ "./apps/settings/src/constants/GroupManagement.ts");
/* harmony import */ var _utils_sorting_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/sorting.ts */ "./apps/settings/src/utils/sorting.ts");
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */












const usersSettings = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('settings', 'usersSettings', {});
const localStorage = (0,_nextcloud_browser_storage__WEBPACK_IMPORTED_MODULE_0__.getBuilder)('settings').persist(true).build();
const defaults = {
  /**
   * @type {import('../views/user-types').IGroup}
   */
  group: {
    id: '',
    name: '',
    usercount: 0,
    disabled: 0,
    canAdd: true,
    canRemove: true
  }
};
const state = {
  users: [],
  groups: [...(usersSettings.getSubAdminGroups ?? []), ...(usersSettings.systemGroups ?? [])],
  orderBy: usersSettings.sortGroups ?? _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_7__.GroupSorting.UserCount,
  minPasswordLength: 0,
  usersOffset: 0,
  usersLimit: 25,
  disabledUsersOffset: 0,
  disabledUsersLimit: 25,
  userCount: usersSettings.userCount ?? 0,
  showConfig: {
    showStoragePath: localStorage.getItem('account_settings__showStoragePath') === 'true',
    showUserBackend: localStorage.getItem('account_settings__showUserBackend') === 'true',
    showFirstLogin: localStorage.getItem('account_settings__showFirstLogin') === 'true',
    showLastLogin: localStorage.getItem('account_settings__showLastLogin') === 'true',
    showNewUserForm: localStorage.getItem('account_settings__showNewUserForm') === 'true',
    showLanguages: localStorage.getItem('account_settings__showLanguages') === 'true'
  }
};
const mutations = {
  appendUsers(state, usersObj) {
    const existingUsers = state.users.map(_ref => {
      let {
        id
      } = _ref;
      return id;
    });
    const newUsers = Object.values(usersObj).filter(_ref2 => {
      let {
        id
      } = _ref2;
      return !existingUsers.includes(id);
    });
    const users = state.users.concat(newUsers);
    state.usersOffset += state.usersLimit;
    state.users = users;
  },
  updateDisabledUsers(state, _usersObj) {
    state.disabledUsersOffset += state.disabledUsersLimit;
  },
  setPasswordPolicyMinLength(state, length) {
    state.minPasswordLength = length !== '' ? length : 0;
  },
  /**
   * @param {object} state store state
   * @param {import('../views/user-types.js').IGroup} newGroup new group
   */
  addGroup(state, newGroup) {
    try {
      if (typeof state.groups.find(group => group.id === newGroup.id) !== 'undefined') {
        return;
      }
      // extend group to default values
      const group = Object.assign({}, defaults.group, newGroup);
      state.groups.unshift(group);
    } catch (e) {
      console.error('Can\'t create group', e);
    }
  },
  renameGroup(state, _ref3) {
    let {
      gid,
      displayName
    } = _ref3;
    const groupIndex = state.groups.findIndex(groupSearch => groupSearch.id === gid);
    if (groupIndex >= 0) {
      const updatedGroup = state.groups[groupIndex];
      updatedGroup.name = displayName;
      state.groups.splice(groupIndex, 1, updatedGroup);
    }
  },
  removeGroup(state, gid) {
    const groupIndex = state.groups.findIndex(groupSearch => groupSearch.id === gid);
    if (groupIndex >= 0) {
      state.groups.splice(groupIndex, 1);
    }
  },
  addUserGroup(state, _ref4) {
    let {
      userid,
      gid
    } = _ref4;
    const group = state.groups.find(groupSearch => groupSearch.id === gid);
    const user = state.users.find(user => user.id === userid);
    // increase count if user is enabled
    if (group && user.enabled && state.userCount > 0) {
      group.usercount++;
    }
    const groups = user.groups;
    groups.push(gid);
  },
  removeUserGroup(state, _ref5) {
    let {
      userid,
      gid
    } = _ref5;
    const group = state.groups.find(groupSearch => groupSearch.id === gid);
    const user = state.users.find(user => user.id === userid);
    // lower count if user is enabled
    if (group && user.enabled && state.userCount > 0) {
      group.usercount--;
    }
    const groups = user.groups;
    groups.splice(groups.indexOf(gid), 1);
  },
  addUserSubAdmin(state, _ref6) {
    let {
      userid,
      gid
    } = _ref6;
    const groups = state.users.find(user => user.id === userid).subadmin;
    groups.push(gid);
  },
  removeUserSubAdmin(state, _ref7) {
    let {
      userid,
      gid
    } = _ref7;
    const groups = state.users.find(user => user.id === userid).subadmin;
    groups.splice(groups.indexOf(gid), 1);
  },
  deleteUser(state, userid) {
    const userIndex = state.users.findIndex(user => user.id === userid);
    this.commit('updateUserCounts', {
      user: state.users[userIndex],
      actionType: 'remove'
    });
    state.users.splice(userIndex, 1);
  },
  addUserData(state, response) {
    const user = response.data.ocs.data;
    state.users.unshift(user);
    this.commit('updateUserCounts', {
      user,
      actionType: 'create'
    });
  },
  enableDisableUser(state, _ref8) {
    let {
      userid,
      enabled
    } = _ref8;
    const user = state.users.find(user => user.id === userid);
    user.enabled = enabled;
    this.commit('updateUserCounts', {
      user,
      actionType: enabled ? 'enable' : 'disable'
    });
  },
  // update active/disabled counts, groups counts
  updateUserCounts(state, _ref9) {
    let {
      user,
      actionType
    } = _ref9;
    // 0 is a special value
    if (state.userCount === 0) {
      return;
    }
    const recentGroup = state.groups.find(group => group.id === '__nc_internal_recent');
    const disabledGroup = state.groups.find(group => group.id === 'disabled');
    switch (actionType) {
      case 'enable':
      case 'disable':
        disabledGroup.usercount += user.enabled ? -1 : 1; // update Disabled Users count
        recentGroup.usercount += user.enabled ? 1 : -1;
        state.userCount += user.enabled ? 1 : -1; // update Active Users count
        user.groups.forEach(userGroup => {
          const group = state.groups.find(groupSearch => groupSearch.id === userGroup);
          if (!group) {
            return;
          }
          group.disabled += user.enabled ? -1 : 1; // update group disabled count
        });
        break;
      case 'create':
        recentGroup.usercount++;
        state.userCount++; // increment Active Users count

        user.groups.forEach(userGroup => {
          const group = state.groups.find(groupSearch => groupSearch.id === userGroup);
          if (!group) {
            return;
          }
          group.usercount++; // increment group total count
        });
        break;
      case 'remove':
        if (user.enabled) {
          recentGroup.usercount--;
          state.userCount--; // decrement Active Users count
          user.groups.forEach(userGroup => {
            const group = state.groups.find(groupSearch => groupSearch.id === userGroup);
            if (!group) {
              console.warn('User group ' + userGroup + ' does not exist during user removal');
              return;
            }
            group.usercount--; // decrement group total count
          });
        } else {
          disabledGroup.usercount--; // decrement Disabled Users count
          user.groups.forEach(userGroup => {
            const group = state.groups.find(groupSearch => groupSearch.id === userGroup);
            if (!group) {
              return;
            }
            group.disabled--; // decrement group disabled count
          });
        }
        break;
      default:
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(`Unknown action type in updateUserCounts: '${actionType}'`);
      // not throwing error to interrupt execution as this is not fatal
    }
  },
  setUserData(state, _ref0) {
    let {
      userid,
      key,
      value
    } = _ref0;
    if (key === 'quota') {
      const humanValue = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(value, true);
      state.users.find(user => user.id === userid)[key][key] = humanValue !== null ? humanValue : value;
    } else {
      state.users.find(user => user.id === userid)[key] = value;
    }
  },
  /**
   * Reset users list
   *
   * @param {object} state the store state
   */
  resetUsers(state) {
    state.users = [];
    state.usersOffset = 0;
    state.disabledUsersOffset = 0;
  },
  /**
   * Reset group list
   *
   * @param {object} state the store state
   */
  resetGroups(state) {
    state.groups = [...(usersSettings.getSubAdminGroups ?? []), ...(usersSettings.systemGroups ?? [])];
  },
  setShowConfig(state, _ref1) {
    let {
      key,
      value
    } = _ref1;
    localStorage.setItem(`account_settings__${key}`, JSON.stringify(value));
    state.showConfig[key] = value;
  },
  setGroupSorting(state, sorting) {
    const oldValue = state.orderBy;
    state.orderBy = sorting;

    // Persist the value on the server
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/settings/users/preferences/group.sortBy'), {
      value: String(sorting)
    }).catch(error => {
      state.orderBy = oldValue;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(t('settings', 'Could not set group sorting'));
      _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
    });
  }
};
const getters = {
  getUsers(state) {
    return state.users;
  },
  getGroups(state) {
    return state.groups;
  },
  getSubAdminGroups() {
    return usersSettings.subAdminGroups ?? [];
  },
  getSortedGroups(state) {
    const groups = [...state.groups];
    if (state.orderBy === _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_7__.GroupSorting.UserCount) {
      return groups.sort((a, b) => {
        const numA = a.usercount - a.disabled;
        const numB = b.usercount - b.disabled;
        return numA < numB ? 1 : numB < numA ? -1 : _utils_sorting_ts__WEBPACK_IMPORTED_MODULE_8__.naturalCollator.compare(a.name, b.name);
      });
    } else {
      return groups.sort((a, b) => _utils_sorting_ts__WEBPACK_IMPORTED_MODULE_8__.naturalCollator.compare(a.name, b.name));
    }
  },
  getGroupSorting(state) {
    return state.orderBy;
  },
  getPasswordPolicyMinLength(state) {
    return state.minPasswordLength;
  },
  getUsersOffset(state) {
    return state.usersOffset;
  },
  getUsersLimit(state) {
    return state.usersLimit;
  },
  getDisabledUsersOffset(state) {
    return state.disabledUsersOffset;
  },
  getDisabledUsersLimit(state) {
    return state.disabledUsersLimit;
  },
  getUserCount(state) {
    return state.userCount;
  },
  getShowConfig(state) {
    return state.showConfig;
  }
};
const CancelToken = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].CancelToken;
let searchRequestCancelSource = null;
const actions = {
  /**
   * search users
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param {string} options.search Search amongst users
   * @return {Promise}
   */
  searchUsers(context, _ref10) {
    let {
      offset,
      limit,
      search
    } = _ref10;
    search = typeof search === 'string' ? search : '';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    })).catch(error => {
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].isCancel(error)) {
        context.commit('API_FAILURE', error);
      }
    });
  },
  /**
   * Get user details
   *
   * @param {object} context store context
   * @param {string} userId user id
   * @return {Promise}
   */
  getUser(context, userId) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)(`cloud/users/${userId}`)).catch(error => {
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].isCancel(error)) {
        context.commit('API_FAILURE', error);
      }
    });
  },
  /**
   * Get all users with full details
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param {string} options.search Search amongst users
   * @param {string} options.group Get users from group
   * @return {Promise}
   */
  getUsers(context, _ref11) {
    let {
      offset,
      limit,
      search,
      group
    } = _ref11;
    if (searchRequestCancelSource) {
      searchRequestCancelSource.cancel('Operation canceled by another search request.');
    }
    searchRequestCancelSource = CancelToken.source();
    search = typeof search === 'string' ? search : '';

    /**
     * Adding filters in the search bar such as in:files, in:users, etc.
     * collides with this particular search, so we need to remove them
     * here and leave only the original search query
     */
    search = search.replace(/in:[^\s]+/g, '').trim();
    group = typeof group === 'string' ? group : '';
    if (group !== '') {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/groups/{group}/users/details?offset={offset}&limit={limit}&search={search}', {
        group: encodeURIComponent(group),
        offset,
        limit,
        search
      }), {
        cancelToken: searchRequestCancelSource.token
      }).then(response => {
        const usersCount = Object.keys(response.data.ocs.data.users).length;
        if (usersCount > 0) {
          context.commit('appendUsers', response.data.ocs.data.users);
        }
        return usersCount;
      }).catch(error => {
        if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].isCancel(error)) {
          context.commit('API_FAILURE', error);
        }
      });
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    }), {
      cancelToken: searchRequestCancelSource.token
    }).then(response => {
      const usersCount = Object.keys(response.data.ocs.data.users).length;
      if (usersCount > 0) {
        context.commit('appendUsers', response.data.ocs.data.users);
      }
      return usersCount;
    }).catch(error => {
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].isCancel(error)) {
        context.commit('API_FAILURE', error);
      }
    });
  },
  /**
   * Get recent users with full details
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param {string} options.search Search query
   * @return {Promise<number>}
   */
  async getRecentUsers(context, _ref12) {
    let {
      offset,
      limit,
      search
    } = _ref12;
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/recent?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    });
    try {
      const response = await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get(url);
      const usersCount = Object.keys(response.data.ocs.data.users).length;
      if (usersCount > 0) {
        context.commit('appendUsers', response.data.ocs.data.users);
      }
      return usersCount;
    } catch (error) {
      context.commit('API_FAILURE', error);
    }
  },
  /**
   * Get disabled users with full details
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param options.search
   * @return {Promise<number>}
   */
  async getDisabledUsers(context, _ref13) {
    let {
      offset,
      limit,
      search
    } = _ref13;
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/disabled?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    });
    try {
      const response = await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get(url);
      const usersCount = Object.keys(response.data.ocs.data.users).length;
      if (usersCount > 0) {
        context.commit('appendUsers', response.data.ocs.data.users);
        context.commit('updateDisabledUsers', response.data.ocs.data.users);
      }
      return usersCount;
    } catch (error) {
      context.commit('API_FAILURE', error);
    }
  },
  getGroups(context, _ref14) {
    let {
      offset,
      limit,
      search
    } = _ref14;
    search = typeof search === 'string' ? search : '';
    const limitParam = limit === -1 ? '' : `&limit=${limit}`;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/groups?offset={offset}&search={search}', {
      offset,
      search
    }) + limitParam).then(response => {
      if (Object.keys(response.data.ocs.data.groups).length > 0) {
        response.data.ocs.data.groups.forEach(function (group) {
          context.commit('addGroup', {
            id: group,
            name: group
          });
        });
        return true;
      }
      return false;
    }).catch(error => context.commit('API_FAILURE', error));
  },
  /**
   * Get all users with full details
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param {string} options.search -
   * @return {Promise}
   */
  getUsersFromList(context, _ref15) {
    let {
      offset,
      limit,
      search
    } = _ref15;
    search = typeof search === 'string' ? search : '';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    })).then(response => {
      if (Object.keys(response.data.ocs.data.users).length > 0) {
        context.commit('appendUsers', response.data.ocs.data.users);
        return true;
      }
      return false;
    }).catch(error => context.commit('API_FAILURE', error));
  },
  /**
   * Get all users with full details from a groupid
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {number} options.offset List offset to request
   * @param {number} options.limit List number to return from offset
   * @param {string} options.groupid -
   * @return {Promise}
   */
  getUsersFromGroup(context, _ref16) {
    let {
      groupid,
      offset,
      limit
    } = _ref16;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{groupId}/details?offset={offset}&limit={limit}', {
      groupId: encodeURIComponent(groupid),
      offset,
      limit
    })).then(response => context.commit('getUsersFromList', response.data.ocs.data.users)).catch(error => context.commit('API_FAILURE', error));
  },
  getPasswordPolicyMinLength(context) {
    if ((0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)().password_policy && (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)().password_policy.minLength) {
      context.commit('setPasswordPolicyMinLength', (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)().password_policy.minLength);
      return (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)().password_policy.minLength;
    }
    return false;
  },
  /**
   * Add group
   *
   * @param {object} context store context
   * @param {string} gid Group id
   * @return {Promise}
   */
  addGroup(context, gid) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/groups'), {
        groupid: gid
      }).then(response => {
        context.commit('addGroup', {
          id: gid,
          name: gid
        });
        return {
          gid,
          displayName: gid
        };
      }).catch(error => {
        throw error;
      });
    }).catch(error => {
      context.commit('API_FAILURE', {
        gid,
        error
      });
      // let's throw one more time to prevent the view
      // from adding the user to a group that doesn't exists
      throw error;
    });
  },
  /**
   * Rename group
   *
   * @param {object} context store context
   * @param {string} groupid Group id
   * @param {string} displayName Group display name
   * @return {Promise}
   */
  renameGroup(context, _ref17) {
    let {
      groupid,
      displayName
    } = _ref17;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/groups/{groupId}', {
        groupId: encodeURIComponent(groupid)
      }), {
        key: 'displayname',
        value: displayName
      }).then(response => {
        context.commit('renameGroup', {
          gid: groupid,
          displayName
        });
        return {
          groupid,
          displayName
        };
      }).catch(error => {
        throw error;
      });
    }).catch(error => {
      context.commit('API_FAILURE', {
        groupid,
        error
      });
      // let's throw one more time to prevent the view
      // from renaming the group
      throw error;
    });
  },
  /**
   * Remove group
   *
   * @param {object} context store context
   * @param {string} gid Group id
   * @return {Promise}
   */
  removeGroup(context, gid) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/groups/{groupId}', {
        groupId: encodeURIComponent(gid)
      })).then(response => context.commit('removeGroup', gid)).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      gid,
      error
    }));
  },
  /**
   * Add user to group
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.gid Group id
   * @return {Promise}
   */
  addUserGroup(context, _ref18) {
    let {
      userid,
      gid
    } = _ref18;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/groups', {
        userid
      }), {
        groupid: gid
      }).then(response => context.commit('addUserGroup', {
        userid,
        gid
      })).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Remove user from group
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.gid Group id
   * @return {Promise}
   */
  removeUserGroup(context, _ref19) {
    let {
      userid,
      gid
    } = _ref19;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/groups', {
        userid
      }), {
        groupid: gid
      }).then(response => context.commit('removeUserGroup', {
        userid,
        gid
      })).catch(error => {
        throw error;
      });
    }).catch(error => {
      context.commit('API_FAILURE', {
        userid,
        error
      });
      // let's throw one more time to prevent
      // the view from removing the user row on failure
      throw error;
    });
  },
  /**
   * Add user to group admin
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.gid Group id
   * @return {Promise}
   */
  addUserSubAdmin(context, _ref20) {
    let {
      userid,
      gid
    } = _ref20;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/subadmins', {
        userid
      }), {
        groupid: gid
      }).then(response => context.commit('addUserSubAdmin', {
        userid,
        gid
      })).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Remove user from group admin
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.gid Group id
   * @return {Promise}
   */
  removeUserSubAdmin(context, _ref21) {
    let {
      userid,
      gid
    } = _ref21;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/subadmins', {
        userid
      }), {
        groupid: gid
      }).then(response => context.commit('removeUserSubAdmin', {
        userid,
        gid
      })).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Mark all user devices for remote wipe
   *
   * @param {object} context store context
   * @param {string} userid User id
   * @return {Promise}
   */
  async wipeUserDevices(context, userid) {
    try {
      await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin();
      return await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/wipe', {
        userid
      }));
    } catch (error) {
      context.commit('API_FAILURE', {
        userid,
        error
      });
      return Promise.reject(new Error('Failed to wipe user devices'));
    }
  },
  /**
   * Delete a user
   *
   * @param {object} context store context
   * @param {string} userid User id
   * @return {Promise}
   */
  deleteUser(context, userid) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}', {
        userid
      })).then(response => context.commit('deleteUser', userid)).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Add a user
   *
   * @param {object} context store context
   * @param {Function} context.commit -
   * @param {Function} context.dispatch -
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.password User password
   * @param {string} options.displayName User display name
   * @param {string} options.email User email
   * @param {string} options.groups User groups
   * @param {string} options.subadmin User subadmin groups
   * @param {string} options.quota User email
   * @param {string} options.language User language
   * @param {string} options.manager User manager
   * @return {Promise}
   */
  addUser(_ref22, _ref23) {
    let {
      commit,
      dispatch
    } = _ref22;
    let {
      userid,
      password,
      displayName,
      email,
      groups,
      subadmin,
      quota,
      language,
      manager
    } = _ref23;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users'), {
        userid,
        password,
        displayName,
        email,
        groups,
        subadmin,
        quota,
        language,
        manager
      }).then(response => dispatch('addUserData', userid || response.data.ocs.data.id)).catch(error => {
        throw error;
      });
    }).catch(error => {
      commit('API_FAILURE', {
        userid,
        error
      });
      throw error;
    });
  },
  /**
   * Get user data and commit addition
   *
   * @param {object} context store context
   * @param {string} userid User id
   * @return {Promise}
   */
  addUserData(context, userid) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}', {
        userid
      })).then(response => context.commit('addUserData', response)).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Enable or disable user
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {boolean} options.enabled User enablement status
   * @return {Promise}
   */
  enableDisableUser(context, _ref24) {
    let {
      userid,
      enabled = true
    } = _ref24;
    const userStatus = enabled ? 'enable' : 'disable';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/{userStatus}', {
        userid,
        userStatus
      })).then(response => context.commit('enableDisableUser', {
        userid,
        enabled
      })).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Edit user data
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.userid User id
   * @param {string} options.key User field to edit
   * @param {string} options.value Value of the change
   * @return {Promise}
   */
  async setUserData(context, _ref25) {
    let {
      userid,
      key,
      value
    } = _ref25;
    const allowedEmpty = ['email', 'displayname', 'manager'];
    const validKeys = ['email', 'language', 'quota', 'displayname', 'password', 'manager'];
    if (!validKeys.includes(key)) {
      throw new Error('Invalid request data');
    }

    // If value is empty and the key doesn't allow empty values, throw error
    if (value === '' && !allowedEmpty.includes(key)) {
      throw new Error('Value cannot be empty for this field');
    }
    try {
      await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin();
      await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}', {
        userid
      }), {
        key,
        value
      });
      return context.commit('setUserData', {
        userid,
        key,
        value
      });
    } catch (error) {
      context.commit('API_FAILURE', {
        userid,
        error
      });
      throw error;
    }
  },
  /**
   * Send welcome mail
   *
   * @param {object} context store context
   * @param {string} userid User id
   * @return {Promise}
   */
  sendWelcomeMail(context, userid) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(response => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('cloud/users/{userid}/welcome', {
        userid
      })).then(response => true).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  state,
  mutations,
  getters,
  actions
});

/***/ }),

/***/ "./apps/settings/src/utils/sorting.ts":
/*!********************************************!*\
  !*** ./apps/settings/src/utils/sorting.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   naturalCollator: () => (/* binding */ naturalCollator)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const naturalCollator = Intl.Collator([(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getCanonicalLocale)()], {
  numeric: true,
  usage: 'sort'
});

/***/ }),

/***/ "./apps/settings/src/views/SettingsApp.vue":
/*!*************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SettingsApp.vue?vue&type=template&id=21177c05 */ "./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05");
/* harmony import */ var _SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SettingsApp.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.render,
  _SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/views/SettingsApp.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SettingsApp.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05":
/*!*******************************************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05 ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SettingsApp.vue?vue&type=template&id=21177c05 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05");


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts":
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcContent__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcContent */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.mjs");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'SettingsApp',
  setup(__props) {
    return {
      __sfc: true,
      NcContent: _nextcloud_vue_components_NcContent__WEBPACK_IMPORTED_MODULE_1__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05":
/*!****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05 ***!
  \****************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
  return _c(_setup.NcContent, {
    attrs: {
      "app-name": "settings"
    }
  }, [_c("router-view", {
    attrs: {
      name: "navigation"
    }
  }), _vm._v(" "), _c("router-view"), _vm._v(" "), _c("router-view", {
    attrs: {
      name: "sidebar"
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/vuex-router-sync/index.js":
/*!************************************************!*\
  !*** ./node_modules/vuex-router-sync/index.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, exports) => {

exports.sync = function (store, router, options) {
  var moduleName = (options || {}).moduleName || 'route'

  store.registerModule(moduleName, {
    namespaced: true,
    state: cloneRoute(router.currentRoute),
    mutations: {
      'ROUTE_CHANGED': function ROUTE_CHANGED (state, transition) {
        store.state[moduleName] = cloneRoute(transition.to, transition.from)
      }
    }
  })

  var isTimeTraveling = false
  var currentPath

  // sync router on store change
  var storeUnwatch = store.watch(
    function (state) { return state[moduleName]; },
    function (route) {
      var fullPath = route.fullPath;
      if (fullPath === currentPath) {
        return
      }
      if (currentPath != null) {
        isTimeTraveling = true
        router.push(route)
      }
      currentPath = fullPath
    },
    { sync: true }
  )

  // sync store on router navigation
  var afterEachUnHook = router.afterEach(function (to, from) {
    if (isTimeTraveling) {
      isTimeTraveling = false
      return
    }
    currentPath = to.fullPath
    store.commit(moduleName + '/ROUTE_CHANGED', { to: to, from: from })
  })

  return function unsync () {
    // On unsync, remove router hook
    if (afterEachUnHook != null) {
      afterEachUnHook()
    }

    // On unsync, remove store watch
    if (storeUnwatch != null) {
      storeUnwatch()
    }

    // On unsync, unregister Module with store
    store.unregisterModule(moduleName)
  }
}

function cloneRoute (to, from) {
  var clone = {
    name: to.name,
    path: to.path,
    hash: to.hash,
    query: to.query,
    params: to.params,
    fullPath: to.fullPath,
    meta: to.meta
  }
  if (from) {
    clone.from = cloneRoute(from)
  }
  return Object.freeze(clone)
}



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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"2fcef36253529e5f48bc","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"f3a3966faa81f9b81fa8","settings-apps-view":"5d55e8b7cb009965db98","settings-users":"7226ef2fae24c850d6a9","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-CsU6FfAP_mjs":"8bce3ebf3ef868f175e5","apps_settings_src_components_AppStoreDiscover_PostType_vue":"a8feaf90b68e5da3b62a","apps_settings_src_components_AppStoreDiscover_CarouselType_vue":"8aa7472a1f0cce7af2b1","apps_settings_src_components_AppStoreDiscover_ShowcaseType_vue":"70c2b387ec1981da065e","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"9fa10a9863e5b78deec8","node_modules_rehype-highlight_index_js":"3c5c32c691780bf457a0"}[chunkId] + "";
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
/******/ 			"settings-vue-settings-apps-users-management": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/settings/src/main-apps-users-management.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=settings-vue-settings-apps-users-management.js.map?v=6085debb647bef5456ee