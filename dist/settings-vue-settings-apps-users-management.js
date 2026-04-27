/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/settings/src/store/api.js"
/*!****************************************!*\
  !*** ./apps/settings/src/store/api.js ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * @param {string} url - The url to sanitize
 */
function sanitize(url) {
  return url.replace(/\/$/, ''); // Remove last url slash
}
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
  post(url, data, options) {
    return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(sanitize(url), data, options);
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

/***/ },

/***/ "./apps/settings/src/store/apps.js"
/*!*****************************************!*\
  !*** ./apps/settings/src/store/apps.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */









const state = {
  apps: [],
  bundles: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('settings', 'appstoreBundles', []),
  categories: [],
  updateCount: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('settings', 'appstoreUpdateCount', 0),
  loading: {},
  gettingCategoriesPromise: null,
  appApiEnabled: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('settings', 'appApiEnabled', false)
};
const mutations = {
  APPS_API_FAILURE(state, error) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, {
      isHTML: true
    });
    _logger_ts__WEBPACK_IMPORTED_MODULE_6__["default"].error('An error occurred during the request. Unable to proceed.', {
      state,
      error
    });
  },
  initCategories(state, {
    categories,
    updateCount
  }) {
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
  setError(state, {
    appId,
    error
  }) {
    if (!Array.isArray(appId)) {
      appId = [appId];
    }
    appId.forEach(_id => {
      const app = state.apps.find(app => app.id === _id);
      app.error = error;
    });
  },
  clearError(state, {
    appId
  }) {
    const app = state.apps.find(app => app.id === appId);
    app.error = null;
  },
  enableApp(state, {
    appId,
    groups
  }) {
    const app = state.apps.find(app => app.id === appId);
    app.active = true;
    vue__WEBPACK_IMPORTED_MODULE_5__["default"].set(app, 'groups', [...groups]);
    if (app.id === 'app_api') {
      state.appApiEnabled = true;
    }
  },
  setInstallState(state, {
    appId,
    canInstall
  }) {
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
        vue__WEBPACK_IMPORTED_MODULE_5__["default"].set(state.loading, _id, true);
      });
    } else {
      vue__WEBPACK_IMPORTED_MODULE_5__["default"].set(state.loading, id, true);
    }
  },
  stopLoading(state, id) {
    if (Array.isArray(id)) {
      id.forEach(_id => {
        vue__WEBPACK_IMPORTED_MODULE_5__["default"].set(state.loading, _id, false);
      });
    } else {
      vue__WEBPACK_IMPORTED_MODULE_5__["default"].set(state.loading, id, false);
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
  enableApp(context, {
    appId,
    groups
  }) {
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    context.commit('startLoading', apps);
    context.commit('startLoading', 'install');
    const previousState = {};
    apps.forEach(_appId => {
      const app = context.state.apps.find(app => app.id === _appId);
      if (app) {
        previousState[_appId] = {
          active: app.active,
          groups: [...(app.groups || [])]
        };
        context.commit('enableApp', {
          appId: _appId,
          groups
        });
      }
    });
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('settings/apps/enable'), {
      appIds: apps,
      groups
    }, {
      confirmPassword: _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.PwdConfirmationMode.Strict
    }).then(response => {
      context.commit('stopLoading', apps);
      context.commit('stopLoading', 'install');

      // check for server health
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('apps/files/')).then(() => {
        if (response.data.update_required) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showInfo)(t('settings', 'The app has been enabled but needs to be updated. You will be redirected to the update page in 5 seconds.'), {
            onClick: () => window.location.reload(),
            close: false
          });
          setTimeout(function () {
            location.reload();
          }, 5000);
        }
      }).catch(() => {
        if (!Array.isArray(appId)) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('settings', 'Error: This app cannot be enabled because it makes the server unstable'));
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
      apps.forEach(_appId => {
        if (previousState[_appId]) {
          context.commit('enableApp', {
            appId: _appId,
            groups: previousState[_appId].groups
          });
          if (!previousState[_appId].active) {
            context.commit('disableApp', _appId);
          }
        }
      });
      const message = error.response?.data?.data?.message;
      if (message) {
        context.commit('setError', {
          appId: apps,
          error: message
        });
        context.commit('APPS_API_FAILURE', {
          appId,
          error
        });
      }
    });
  },
  forceEnableApp(context, {
    appId
  }) {
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].requireAdmin().then(() => {
      context.commit('startLoading', apps);
      context.commit('startLoading', 'install');
      return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('settings/apps/force'), {
        appId
      }).then(() => {
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
  disableApp(context, {
    appId
  }) {
    let apps;
    if (Array.isArray(appId)) {
      apps = appId;
    } else {
      apps = [appId];
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].requireAdmin().then(() => {
      context.commit('startLoading', apps);
      return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('settings/apps/disable'), {
        appIds: apps
      }).then(() => {
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
  uninstallApp(context, {
    appId
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].requireAdmin().then(() => {
      context.commit('startLoading', appId);
      return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)(`settings/apps/uninstall/${appId}`)).then(() => {
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
  updateApp(context, {
    appId
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].requireAdmin().then(() => {
      context.commit('startLoading', appId);
      context.commit('startLoading', 'install');
      return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)(`settings/apps/update/${appId}`)).then(() => {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('settings/apps/list')).then(response => {
      context.commit('setAllApps', response.data.apps);
      context.commit('stopLoading', 'list');
      return true;
    }).catch(error => context.commit('API_FAILURE', error));
  },
  async getCategories(context, {
    shouldRefetchCategories = false
  } = {}) {
    if (shouldRefetchCategories || !context.state.gettingCategoriesPromise) {
      context.commit('startLoading', 'categories');
      try {
        const categoriesPromise = _api_js__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('settings/apps/categories'));
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

/***/ },

/***/ "./apps/settings/src/store/index.js"
/*!******************************************!*\
  !*** ./apps/settings/src/store/index.js ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useStore: () => (/* binding */ useStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var vuex__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vuex */ "./node_modules/vuex/dist/vuex.esm.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../logger.js */ "./apps/settings/src/logger.ts");
/* harmony import */ var _apps_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./apps.js */ "./apps/settings/src/store/apps.js");
/* harmony import */ var _oc_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./oc.js */ "./apps/settings/src/store/oc.js");
/* harmony import */ var _users_settings_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./users-settings.js */ "./apps/settings/src/store/users-settings.js");
/* harmony import */ var _users_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./users.js */ "./apps/settings/src/store/users.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








const mutations = {
  API_FAILURE(state, error) {
    try {
      const message = error.error.response.data.ocs.meta.message;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + message, {
        isHTML: true
      });
    } catch {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('settings', 'An error occurred during the request. Unable to proceed.'));
    }
    _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].error('An error occurred during the request.', {
      state,
      error
    });
  }
};
let store = null;

/**
 *
 */
function useStore() {
  if (store === null) {
    store = new vuex__WEBPACK_IMPORTED_MODULE_1__.Store({
      modules: {
        users: _users_js__WEBPACK_IMPORTED_MODULE_6__["default"],
        apps: _apps_js__WEBPACK_IMPORTED_MODULE_3__["default"],
        settings: _users_settings_js__WEBPACK_IMPORTED_MODULE_5__["default"],
        oc: _oc_js__WEBPACK_IMPORTED_MODULE_4__["default"]
      },
      strict: !false,
      mutations
    });
  }
  return store;
}

/***/ },

/***/ "./apps/settings/src/store/oc.js"
/*!***************************************!*\
  !*** ./apps/settings/src/store/oc.js ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
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
  setAppConfig(context, {
    app,
    key,
    value
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_1__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_1__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/provisioning_api/api/v1/config/apps/{app}/{key}', {
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

/***/ },

/***/ "./apps/settings/src/store/users-settings.js"
/*!***************************************************!*\
  !*** ./apps/settings/src/store/users-settings.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./apps/settings/src/store/users.js"
/*!******************************************!*\
  !*** ./apps/settings/src/store/users.js ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../constants/GroupManagement.ts */ "./apps/settings/src/constants/GroupManagement.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _utils_sorting_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/sorting.ts */ "./apps/settings/src/utils/sorting.ts");
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./api.js */ "./apps/settings/src/store/api.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const usersSettings = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('settings', 'usersSettings', {});
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
  orderBy: usersSettings.sortGroups ?? _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_6__.GroupSorting.UserCount,
  minPasswordLength: 0,
  usersOffset: 0,
  usersLimit: 25,
  disabledUsersOffset: 0,
  disabledUsersLimit: 25,
  searchQuery: '',
  userCount: usersSettings.userCount ?? 0,
  showConfig: {
    showStoragePath: usersSettings.showConfig?.user_list_show_storage_path,
    showUserBackend: usersSettings.showConfig?.user_list_show_user_backend,
    showFirstLogin: usersSettings.showConfig?.user_list_show_first_login,
    showLastLogin: usersSettings.showConfig?.user_list_show_last_login,
    showNewUserForm: usersSettings.showConfig?.user_list_show_new_user_form,
    showLanguages: usersSettings.showConfig?.user_list_show_languages
  }
};
const mutations = {
  appendUsers(state, usersObj) {
    const existingUsers = state.users.map(({
      id
    }) => id);
    const newUsers = Object.values(usersObj).filter(({
      id
    }) => !existingUsers.includes(id));
    const users = state.users.concat(newUsers);
    state.usersOffset += state.usersLimit;
    state.users = users;
  },
  updateDisabledUsers(state) {
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
      const group = {
        ...defaults.group,
        ...newGroup
      };
      state.groups.unshift(group);
    } catch (error) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].error('Cannt create group', {
        error
      });
    }
  },
  renameGroup(state, {
    gid,
    displayName
  }) {
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
  addUserGroup(state, {
    userid,
    gid
  }) {
    const group = state.groups.find(groupSearch => groupSearch.id === gid);
    const user = state.users.find(user => user.id === userid);
    // increase count if user is enabled
    if (group && user.enabled && state.userCount > 0) {
      group.usercount++;
    }
    const groups = user.groups;
    groups.push(gid);
  },
  removeUserGroup(state, {
    userid,
    gid
  }) {
    const group = state.groups.find(groupSearch => groupSearch.id === gid);
    const user = state.users.find(user => user.id === userid);
    // lower count if user is enabled
    if (group && user.enabled && state.userCount > 0) {
      group.usercount--;
    }
    const groups = user.groups;
    groups.splice(groups.indexOf(gid), 1);
  },
  addUserSubAdmin(state, {
    userid,
    gid
  }) {
    const groups = state.users.find(user => user.id === userid).subadmin;
    groups.push(gid);
  },
  removeUserSubAdmin(state, {
    userid,
    gid
  }) {
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
  enableDisableUser(state, {
    userid,
    enabled
  }) {
    const user = state.users.find(user => user.id === userid);
    user.enabled = enabled;
    this.commit('updateUserCounts', {
      user,
      actionType: enabled ? 'enable' : 'disable'
    });
  },
  // update active/disabled counts, groups counts
  updateUserCounts(state, {
    user,
    actionType
  }) {
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
              _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].warn('User group ' + userGroup + ' does not exist during user removal');
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
        _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].error(`Unknown action type in updateUserCounts: '${actionType}'`);
      // not throwing error to interrupt execution as this is not fatal
    }
  },
  setUserData(state, {
    userid,
    key,
    value
  }) {
    if (key === 'quota') {
      const humanValue = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.parseFileSize)(value, true);
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
  setSearchQuery(state, query) {
    state.searchQuery = query;
  },
  setShowConfig(state, {
    key,
    value
  }) {
    state.showConfig[key] = value;
  },
  setGroupSorting(state, sorting) {
    const oldValue = state.orderBy;
    state.orderBy = sorting;

    // Persist the value on the server
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)('/settings/users/preferences/group.sortBy'), {
      value: String(sorting)
    }).catch(error => {
      state.orderBy = oldValue;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('settings', 'Could not set group sorting'));
      _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].error(error);
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
  getSearchQuery(state) {
    return state.searchQuery;
  },
  getSubAdminGroups() {
    return usersSettings.subAdminGroups ?? [];
  },
  getSortedGroups(state) {
    const groups = [...state.groups];
    if (state.orderBy === _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_6__.GroupSorting.UserCount) {
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
const CancelToken = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].CancelToken;
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
  searchUsers(context, {
    offset,
    limit,
    search
  }) {
    search = typeof search === 'string' ? search : '';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
      offset,
      limit,
      search
    })).catch(error => {
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].isCancel(error)) {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)(`cloud/users/${userId}`)).catch(error => {
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].isCancel(error)) {
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
  getUsers(context, {
    offset,
    limit,
    search,
    group
  }) {
    if (searchRequestCancelSource) {
      searchRequestCancelSource.cancel('Operation canceled by another search request.');
    }
    searchRequestCancelSource = CancelToken.source();
    search = typeof search === 'string' ? search : '';
    group = typeof group === 'string' ? group : '';
    if (group !== '') {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/groups/{group}/users/details?offset={offset}&limit={limit}&search={search}', {
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
        if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].isCancel(error)) {
          context.commit('API_FAILURE', error);
        }
      });
    }
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
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
      if (!_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].isCancel(error)) {
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
  async getRecentUsers(context, {
    offset,
    limit,
    search
  }) {
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/recent?offset={offset}&limit={limit}&search={search}', {
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
  async getDisabledUsers(context, {
    offset,
    limit,
    search
  }) {
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/disabled?offset={offset}&limit={limit}&search={search}', {
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
  getGroups(context, {
    offset,
    limit,
    search
  }) {
    search = typeof search === 'string' ? search : '';
    const limitParam = limit === -1 ? '' : `&limit=${limit}`;
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/groups?offset={offset}&search={search}', {
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
  getUsersFromList(context, {
    offset,
    limit,
    search
  }) {
    search = typeof search === 'string' ? search : '';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/details?offset={offset}&limit={limit}&search={search}', {
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
  getUsersFromGroup(context, {
    groupid,
    offset,
    limit
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{groupId}/details?offset={offset}&limit={limit}', {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/groups'), {
        groupid: gid
      }).then(() => {
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
  renameGroup(context, {
    groupid,
    displayName
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/groups/{groupId}', {
        groupId: encodeURIComponent(groupid)
      }), {
        key: 'displayname',
        value: displayName
      }).then(() => {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/groups/{groupId}', {
        groupId: encodeURIComponent(gid)
      })).then(() => context.commit('removeGroup', gid)).catch(error => {
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
  addUserGroup(context, {
    userid,
    gid
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/groups', {
        userid
      }), {
        groupid: gid
      }).then(() => context.commit('addUserGroup', {
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
  removeUserGroup(context, {
    userid,
    gid
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/groups', {
        userid
      }), {
        groupid: gid
      }).then(() => context.commit('removeUserGroup', {
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
  addUserSubAdmin(context, {
    userid,
    gid
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/subadmins', {
        userid
      }), {
        groupid: gid
      }).then(() => context.commit('addUserSubAdmin', {
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
  removeUserSubAdmin(context, {
    userid,
    gid
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/subadmins', {
        userid
      }), {
        groupid: gid
      }).then(() => context.commit('removeUserSubAdmin', {
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
      return await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/wipe', {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}', {
        userid
      })).then(() => context.commit('deleteUser', userid)).catch(error => {
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
  addUser({
    commit,
    dispatch
  }, {
    userid,
    password,
    displayName,
    email,
    groups,
    subadmin,
    quota,
    language,
    manager
  }) {
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users'), {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}', {
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
  enableDisableUser(context, {
    userid,
    enabled = true
  }) {
    const userStatus = enabled ? 'enable' : 'disable';
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/{userStatus}', {
        userid,
        userStatus
      })).then(() => context.commit('enableDisableUser', {
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
  async setUserData(context, {
    userid,
    key,
    value
  }) {
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
      await _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}', {
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
    return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].requireAdmin().then(() => {
      return _api_js__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('cloud/users/{userid}/welcome', {
        userid
      })).then(() => true).catch(error => {
        throw error;
      });
    }).catch(error => context.commit('API_FAILURE', {
      userid,
      error
    }));
  },
  /**
   * Migrate local storage keys to database
   *
   * @param {object} context store context
   * @param context.commit
   */
  migrateLocalStorage({
    commit
  }) {
    const preferences = {
      showStoragePath: 'user_list_show_storage_path',
      showUserBackend: 'user_list_show_user_backend',
      showFirstLogin: 'user_list_show_first_login',
      showLastLogin: 'user_list_show_last_login',
      showNewUserForm: 'user_list_show_new_user_form',
      showLanguages: 'user_list_show_languages'
    };
    for (const [key, dbKey] of Object.entries(preferences)) {
      const localKey = `account_settings__${key}`;
      const localValue = window.localStorage.getItem(localKey);
      if (localValue === null) {
        continue;
      }
      const value = localValue === 'true';
      commit('setShowConfig', {
        key,
        value
      });
      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)(`/settings/users/preferences/${dbKey}`), {
        value: value ? 'true' : 'false'
      }).then(() => {
        window.localStorage.removeItem(localKey);
      }).catch(error => {
        _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].error(`Failed to migrate preference ${key}`, {
          error
        });
      });
    }
  },
  /**
   * Set show config
   *
   * @param {object} context store context
   * @param {object} options destructuring object
   * @param {string} options.key Key to set
   * @param {boolean} options.value Value to set
   */
  setShowConfig(context, {
    key,
    value
  }) {
    context.commit('setShowConfig', {
      key,
      value
    });
    const keyMap = {
      showStoragePath: 'user_list_show_storage_path',
      showUserBackend: 'user_list_show_user_backend',
      showFirstLogin: 'user_list_show_first_login',
      showLastLogin: 'user_list_show_last_login',
      showNewUserForm: 'user_list_show_new_user_form',
      showLanguages: 'user_list_show_languages'
    };
    _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)(`settings/users/preferences/${keyMap[key]}`), {
      value: value ? 'true' : 'false'
    }).catch(error => _logger_ts__WEBPACK_IMPORTED_MODULE_7__["default"].error(`Could not update ${key} preference`, {
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

/***/ },

/***/ "./apps/settings/src/constants/GroupManagement.ts"
/*!********************************************************!*\
  !*** ./apps/settings/src/constants/GroupManagement.ts ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./apps/settings/src/logger.ts"
/*!*************************************!*\
  !*** ./apps/settings/src/logger.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./apps/settings/src/main-apps-users-management.ts"
/*!*********************************************************!*\
  !*** ./apps/settings/src/main-apps-users-management.ts ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vuex__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vuex */ "./node_modules/vuex/dist/vuex.esm.js");
/* harmony import */ var vuex_router_sync__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vuex-router-sync */ "./node_modules/vuex-router-sync/index.js");
/* harmony import */ var _views_SettingsApp_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./views/SettingsApp.vue */ "./apps/settings/src/views/SettingsApp.vue");
/* harmony import */ var _router_index_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./router/index.ts */ "./apps/settings/src/router/index.ts");
/* harmony import */ var _store_index_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./store/index.js */ "./apps/settings/src/store/index.js");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */












(0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.addPasswordConfirmationInterceptors)(_nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"]);
// CSP config for webpack dynamic chunk loading
__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
// bind to window
vue__WEBPACK_IMPORTED_MODULE_6__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t;
vue__WEBPACK_IMPORTED_MODULE_6__["default"].prototype.n = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.n;
vue__WEBPACK_IMPORTED_MODULE_6__["default"].use(pinia__WEBPACK_IMPORTED_MODULE_4__.PiniaVuePlugin);
vue__WEBPACK_IMPORTED_MODULE_6__["default"].use(v_tooltip__WEBPACK_IMPORTED_MODULE_5__["default"], {
  defaultHtml: false
});
vue__WEBPACK_IMPORTED_MODULE_6__["default"].use(vuex__WEBPACK_IMPORTED_MODULE_7__["default"]);
const store = (0,_store_index_js__WEBPACK_IMPORTED_MODULE_11__.useStore)();
(0,vuex_router_sync__WEBPACK_IMPORTED_MODULE_8__.sync)(store, _router_index_ts__WEBPACK_IMPORTED_MODULE_10__["default"]);
const pinia = (0,pinia__WEBPACK_IMPORTED_MODULE_4__.createPinia)();
// Migrate legacy local storage settings to the database
store.dispatch('migrateLocalStorage');
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (new vue__WEBPACK_IMPORTED_MODULE_6__["default"]({
  router: _router_index_ts__WEBPACK_IMPORTED_MODULE_10__["default"],
  store,
  pinia,
  render: h => h(_views_SettingsApp_vue__WEBPACK_IMPORTED_MODULE_9__["default"]),
  el: '#content'
}));

/***/ },

/***/ "./apps/settings/src/router/index.ts"
/*!*******************************************!*\
  !*** ./apps/settings/src/router/index.ts ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");
/* harmony import */ var _routes_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./routes.ts */ "./apps/settings/src/router/routes.ts");
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




vue__WEBPACK_IMPORTED_MODULE_1__["default"].use(vue_router__WEBPACK_IMPORTED_MODULE_2__["default"]);
const router = new vue_router__WEBPACK_IMPORTED_MODULE_2__["default"]({
  mode: 'history',
  // if index.php is in the url AND we got this far, then it's working:
  // let's keep using index.php in the url
  base: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(''),
  linkActiveClass: 'active',
  routes: _routes_ts__WEBPACK_IMPORTED_MODULE_3__["default"]
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (router);

/***/ },

/***/ "./apps/settings/src/router/routes.ts"
/*!********************************************!*\
  !*** ./apps/settings/src/router/routes.ts ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./apps/settings/src/utils/sorting.ts"
/*!********************************************!*\
  !*** ./apps/settings/src/utils/sorting.ts ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts"
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./node_modules/v-tooltip/dist/v-tooltip.esm.js"
/*!******************************************************!*\
  !*** ./node_modules/v-tooltip/dist/v-tooltip.esm.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VClosePopover: () => (/* binding */ VClosePopover),
/* harmony export */   VPopover: () => (/* binding */ VPopover),
/* harmony export */   VTooltip: () => (/* binding */ VTooltip),
/* harmony export */   createTooltip: () => (/* binding */ createTooltip),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   destroyTooltip: () => (/* binding */ destroyTooltip),
/* harmony export */   install: () => (/* binding */ install)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var popper_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! popper.js */ "./node_modules/popper.js/dist/esm/popper.js");
/* harmony import */ var lodash_isEqual__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lodash/isEqual */ "./node_modules/lodash/isEqual.js");
/* harmony import */ var lodash_isEqual__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lodash_isEqual__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var vue_resize__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-resize */ "./node_modules/vue-resize/dist/vue-resize.esm.js");
/* harmony import */ var lodash_merge__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! lodash/merge */ "./node_modules/lodash/merge.js");
/* harmony import */ var lodash_merge__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash_merge__WEBPACK_IMPORTED_MODULE_7__);








var SVGAnimatedString = function SVGAnimatedString() {};
if (typeof window !== 'undefined') {
  SVGAnimatedString = window.SVGAnimatedString;
}
function convertToArray(value) {
  if (typeof value === 'string') {
    value = value.split(' ');
  }
  return value;
}
/**
 * Add classes to an element.
 * This method checks to ensure that the classes don't already exist before adding them.
 * It uses el.className rather than classList in order to be IE friendly.
 * @param {object} el - The element to add the classes to.
 * @param {classes} string - List of space separated classes to be added to the element.
 */

function addClasses(el, classes) {
  var newClasses = convertToArray(classes);
  var classList;
  if (el.className instanceof SVGAnimatedString) {
    classList = convertToArray(el.className.baseVal);
  } else {
    classList = convertToArray(el.className);
  }
  newClasses.forEach(function (newClass) {
    if (classList.indexOf(newClass) === -1) {
      classList.push(newClass);
    }
  });
  if (el instanceof SVGElement) {
    el.setAttribute('class', classList.join(' '));
  } else {
    el.className = classList.join(' ');
  }
}
/**
 * Remove classes from an element.
 * It uses el.className rather than classList in order to be IE friendly.
 * @export
 * @param {any} el The element to remove the classes from.
 * @param {any} classes List of space separated classes to be removed from the element.
 */

function removeClasses(el, classes) {
  var newClasses = convertToArray(classes);
  var classList;
  if (el.className instanceof SVGAnimatedString) {
    classList = convertToArray(el.className.baseVal);
  } else {
    classList = convertToArray(el.className);
  }
  newClasses.forEach(function (newClass) {
    var index = classList.indexOf(newClass);
    if (index !== -1) {
      classList.splice(index, 1);
    }
  });
  if (el instanceof SVGElement) {
    el.setAttribute('class', classList.join(' '));
  } else {
    el.className = classList.join(' ');
  }
}
var supportsPassive = false;
if (typeof window !== 'undefined') {
  supportsPassive = false;
  try {
    var opts = Object.defineProperty({}, 'passive', {
      get: function get() {
        supportsPassive = true;
      }
    });
    window.addEventListener('test', null, opts);
  } catch (e) {}
}
function ownKeys$2(object, enumerableOnly) {
  var keys = Object.keys(object);
  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }
  return keys;
}
function _objectSpread$2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};
    if (i % 2) {
      ownKeys$2(Object(source), true).forEach(function (key) {
        (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys$2(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }
  return target;
}
var DEFAULT_OPTIONS = {
  container: false,
  delay: 0,
  html: false,
  placement: 'top',
  title: '',
  template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
  trigger: 'hover focus',
  offset: 0
};
var openTooltips = [];
var Tooltip = /*#__PURE__*/function () {
  /**
   * Create a new Tooltip.js instance
   * @class Tooltip
   * @param {HTMLElement} reference - The DOM node used as reference of the tooltip (it can be a jQuery element).
   * @param {Object} options
   * @param {String} options.placement=bottom
   *      Placement of the popper accepted values: `top(-start, -end), right(-start, -end), bottom(-start, -end),
   *      left(-start, -end)`
   * @param {HTMLElement|String|false} options.container=false - Append the tooltip to a specific element.
   * @param {Number|Object} options.delay=0
   *      Delay showing and hiding the tooltip (ms) - does not apply to manual trigger type.
   *      If a number is supplied, delay is applied to both hide/show.
   *      Object structure is: `{ show: 500, hide: 100 }`
   * @param {Boolean} options.html=false - Insert HTML into the tooltip. If false, the content will inserted with `innerText`.
   * @param {String|PlacementFunction} options.placement='top' - One of the allowed placements, or a function returning one of them.
   * @param {String} [options.template='<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>']
   *      Base HTML to used when creating the tooltip.
   *      The tooltip's `title` will be injected into the `.tooltip-inner` or `.tooltip__inner`.
   *      `.tooltip-arrow` or `.tooltip__arrow` will become the tooltip's arrow.
   *      The outermost wrapper element should have the `.tooltip` class.
   * @param {String|HTMLElement|TitleFunction} options.title='' - Default title value if `title` attribute isn't present.
   * @param {String} [options.trigger='hover focus']
   *      How tooltip is triggered - click, hover, focus, manual.
   *      You may pass multiple triggers; separate them with a space. `manual` cannot be combined with any other trigger.
   * @param {HTMLElement} options.boundariesElement
   *      The element used as boundaries for the tooltip. For more information refer to Popper.js'
   *      [boundariesElement docs](https://popper.js.org/popper-documentation.html)
   * @param {Number|String} options.offset=0 - Offset of the tooltip relative to its reference. For more information refer to Popper.js'
   *      [offset docs](https://popper.js.org/popper-documentation.html)
   * @param {Object} options.popperOptions={} - Popper options, will be passed directly to popper instance. For more information refer to Popper.js'
   *      [options docs](https://popper.js.org/popper-documentation.html)
   * @param {string} [options.ariaId] Id used for accessibility
   * @return {Object} instance - The generated tooltip instance
   */
  function Tooltip(_reference, _options) {
    var _this = this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__["default"])(this, Tooltip);
    (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])(this, "_events", []);
    (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])(this, "_setTooltipNodeEvent", function (evt, reference, delay, options) {
      var relatedreference = evt.relatedreference || evt.toElement || evt.relatedTarget;
      var callback = function callback(evt2) {
        var relatedreference2 = evt2.relatedreference || evt2.toElement || evt2.relatedTarget; // Remove event listener after call

        _this._tooltipNode.removeEventListener(evt.type, callback); // If the new reference is not the reference element

        if (!reference.contains(relatedreference2)) {
          // Schedule to hide tooltip
          _this._scheduleHide(reference, options.delay, options, evt2);
        }
      };
      if (_this._tooltipNode.contains(relatedreference)) {
        // listen to mouseleave on the tooltip element to be able to hide the tooltip
        _this._tooltipNode.addEventListener(evt.type, callback);
        return true;
      }
      return false;
    });

    // apply user options over default ones
    _options = _objectSpread$2(_objectSpread$2({}, DEFAULT_OPTIONS), _options);
    _reference.jquery && (_reference = _reference[0]);
    this.show = this.show.bind(this);
    this.hide = this.hide.bind(this); // cache reference and options

    this.reference = _reference;
    this.options = _options; // set initial state

    this._isOpen = false;
    this._init();
  } //
  // Public methods
  //

  /**
   * Reveals an element's tooltip. This is considered a "manual" triggering of the tooltip.
   * Tooltips with zero-length titles are never displayed.
   * @method Tooltip#show
   * @memberof Tooltip
   */

  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__["default"])(Tooltip, [{
    key: "show",
    value: function show() {
      this._show(this.reference, this.options);
    }
    /**
     * Hides an element’s tooltip. This is considered a “manual” triggering of the tooltip.
     * @method Tooltip#hide
     * @memberof Tooltip
     */
  }, {
    key: "hide",
    value: function hide() {
      this._hide();
    }
    /**
     * Hides and destroys an element’s tooltip.
     * @method Tooltip#dispose
     * @memberof Tooltip
     */
  }, {
    key: "dispose",
    value: function dispose() {
      this._dispose();
    }
    /**
     * Toggles an element’s tooltip. This is considered a “manual” triggering of the tooltip.
     * @method Tooltip#toggle
     * @memberof Tooltip
     */
  }, {
    key: "toggle",
    value: function toggle() {
      if (this._isOpen) {
        return this.hide();
      } else {
        return this.show();
      }
    }
  }, {
    key: "setClasses",
    value: function setClasses(classes) {
      this._classes = classes;
    }
  }, {
    key: "setContent",
    value: function setContent(content) {
      this.options.title = content;
      if (this._tooltipNode) {
        this._setContent(content, this.options);
      }
    }
  }, {
    key: "setOptions",
    value: function setOptions(options) {
      var classesUpdated = false;
      var classes = options && options.classes || directive.options.defaultClass;
      if (!lodash_isEqual__WEBPACK_IMPORTED_MODULE_5___default()(this._classes, classes)) {
        this.setClasses(classes);
        classesUpdated = true;
      }
      options = getOptions(options);
      var needPopperUpdate = false;
      var needRestart = false;
      if (this.options.offset !== options.offset || this.options.placement !== options.placement) {
        needPopperUpdate = true;
      }
      if (this.options.template !== options.template || this.options.trigger !== options.trigger || this.options.container !== options.container || classesUpdated) {
        needRestart = true;
      }
      for (var key in options) {
        this.options[key] = options[key];
      }
      if (this._tooltipNode) {
        if (needRestart) {
          var isOpen = this._isOpen;
          this.dispose();
          this._init();
          if (isOpen) {
            this.show();
          }
        } else if (needPopperUpdate) {
          this.popperInstance.update();
        }
      }
    } //
    // Private methods
    //
  }, {
    key: "_init",
    value: function _init() {
      // get events list
      var events = typeof this.options.trigger === 'string' ? this.options.trigger.split(' ') : [];
      this._isDisposed = false;
      this._enableDocumentTouch = events.indexOf('manual') === -1;
      events = events.filter(function (trigger) {
        return ['click', 'hover', 'focus'].indexOf(trigger) !== -1;
      }); // set event listeners

      this._setEventListeners(this.reference, events, this.options); // title attribute

      this.$_originalTitle = this.reference.getAttribute('title');
      this.reference.removeAttribute('title');
      this.reference.setAttribute('data-original-title', this.$_originalTitle);
    }
    /**
     * Creates a new tooltip node
     * @memberof Tooltip
     * @private
     * @param {HTMLElement} reference
     * @param {String} template
     * @param {String|HTMLElement|TitleFunction} title
     * @param {Boolean} allowHtml
     * @return {HTMLelement} tooltipNode
     */
  }, {
    key: "_create",
    value: function _create(reference, template) {
      var _this2 = this;

      // create tooltip element
      var tooltipGenerator = window.document.createElement('div');
      tooltipGenerator.innerHTML = template.trim();
      var tooltipNode = tooltipGenerator.childNodes[0]; // add unique ID to our tooltip (needed for accessibility reasons)

      tooltipNode.id = this.options.ariaId || "tooltip_".concat(Math.random().toString(36).substr(2, 10)); // Initially hide the tooltip
      // The attribute will be switched in a next frame so
      // CSS transitions can play

      tooltipNode.setAttribute('aria-hidden', 'true');
      if (this.options.autoHide && this.options.trigger.indexOf('hover') !== -1) {
        tooltipNode.addEventListener('mouseenter', function (evt) {
          return _this2._scheduleHide(reference, _this2.options.delay, _this2.options, evt);
        });
        tooltipNode.addEventListener('click', function (evt) {
          return _this2._scheduleHide(reference, _this2.options.delay, _this2.options, evt);
        });
      } // return the generated tooltip node

      return tooltipNode;
    }
  }, {
    key: "_setContent",
    value: function _setContent(content, options) {
      var _this3 = this;
      this.asyncContent = false;
      this._applyContent(content, options).then(function () {
        if (!_this3.popperInstance) return;
        _this3.popperInstance.update();
      });
    }
  }, {
    key: "_applyContent",
    value: function _applyContent(title, options) {
      var _this4 = this;
      return new Promise(function (resolve, reject) {
        var allowHtml = options.html;
        var rootNode = _this4._tooltipNode;
        if (!rootNode) return;
        var titleNode = rootNode.querySelector(_this4.options.innerSelector);
        if (title.nodeType === 1) {
          // if title is a node, append it only if allowHtml is true
          if (allowHtml) {
            while (titleNode.firstChild) {
              titleNode.removeChild(titleNode.firstChild);
            }
            titleNode.appendChild(title);
          }
        } else if (typeof title === 'function') {
          // if title is a function, call it and set innerText or innerHtml depending by `allowHtml` value
          var result = title();
          if (result && typeof result.then === 'function') {
            _this4.asyncContent = true;
            options.loadingClass && addClasses(rootNode, options.loadingClass);
            if (options.loadingContent) {
              _this4._applyContent(options.loadingContent, options);
            }
            result.then(function (asyncResult) {
              options.loadingClass && removeClasses(rootNode, options.loadingClass);
              return _this4._applyContent(asyncResult, options);
            }).then(resolve).catch(reject);
          } else {
            _this4._applyContent(result, options).then(resolve).catch(reject);
          }
          return;
        } else {
          // if it's just a simple text, set innerText or innerHtml depending by `allowHtml` value
          allowHtml ? titleNode.innerHTML = title : titleNode.innerText = title;
        }
        resolve();
      });
    }
  }, {
    key: "_show",
    value: function _show(reference, options) {
      if (options && typeof options.container === 'string') {
        var container = document.querySelector(options.container);
        if (!container) return;
      }
      clearTimeout(this._disposeTimer);
      options = Object.assign({}, options);
      delete options.offset;
      var updateClasses = true;
      if (this._tooltipNode) {
        addClasses(this._tooltipNode, this._classes);
        updateClasses = false;
      }
      var result = this._ensureShown(reference, options);
      if (updateClasses && this._tooltipNode) {
        addClasses(this._tooltipNode, this._classes);
      }
      addClasses(reference, ['v-tooltip-open']);
      return result;
    }
  }, {
    key: "_ensureShown",
    value: function _ensureShown(reference, options) {
      var _this5 = this;

      // don't show if it's already visible
      if (this._isOpen) {
        return this;
      }
      this._isOpen = true;
      openTooltips.push(this); // if the tooltipNode already exists, just show it

      if (this._tooltipNode) {
        this._tooltipNode.style.display = '';
        this._tooltipNode.setAttribute('aria-hidden', 'false');
        this.popperInstance.enableEventListeners();
        this.popperInstance.update();
        if (this.asyncContent) {
          this._setContent(options.title, options);
        }
        return this;
      } // get title

      var title = reference.getAttribute('title') || options.title; // don't show tooltip if no title is defined

      if (!title) {
        return this;
      } // create tooltip node

      var tooltipNode = this._create(reference, options.template);
      this._tooltipNode = tooltipNode; // Add `aria-describedby` to our reference element for accessibility reasons

      reference.setAttribute('aria-describedby', tooltipNode.id); // append tooltip to container

      var container = this._findContainer(options.container, reference);
      this._append(tooltipNode, container);
      var popperOptions = _objectSpread$2(_objectSpread$2({}, options.popperOptions), {}, {
        placement: options.placement
      });
      popperOptions.modifiers = _objectSpread$2(_objectSpread$2({}, popperOptions.modifiers), {}, {
        arrow: {
          element: this.options.arrowSelector
        }
      });
      if (options.boundariesElement) {
        popperOptions.modifiers.preventOverflow = {
          boundariesElement: options.boundariesElement
        };
      }
      this.popperInstance = new popper_js__WEBPACK_IMPORTED_MODULE_4__["default"](reference, tooltipNode, popperOptions);
      this._setContent(title, options); // Fix position

      requestAnimationFrame(function () {
        if (!_this5._isDisposed && _this5.popperInstance) {
          _this5.popperInstance.update(); // Show the tooltip

          requestAnimationFrame(function () {
            if (!_this5._isDisposed) {
              _this5._isOpen && tooltipNode.setAttribute('aria-hidden', 'false');
            } else {
              _this5.dispose();
            }
          });
        } else {
          _this5.dispose();
        }
      });
      return this;
    }
  }, {
    key: "_noLongerOpen",
    value: function _noLongerOpen() {
      var index = openTooltips.indexOf(this);
      if (index !== -1) {
        openTooltips.splice(index, 1);
      }
    }
  }, {
    key: "_hide",
    value: function _hide() /* reference, options */
    {
      var _this6 = this;

      // don't hide if it's already hidden
      if (!this._isOpen) {
        return this;
      }
      this._isOpen = false;
      this._noLongerOpen(); // hide tooltipNode

      this._tooltipNode.style.display = 'none';
      this._tooltipNode.setAttribute('aria-hidden', 'true');
      if (this.popperInstance) {
        this.popperInstance.disableEventListeners();
      }
      clearTimeout(this._disposeTimer);
      var disposeTime = directive.options.disposeTimeout;
      if (disposeTime !== null) {
        this._disposeTimer = setTimeout(function () {
          if (_this6._tooltipNode) {
            _this6._tooltipNode.removeEventListener('mouseenter', _this6.hide);
            _this6._tooltipNode.removeEventListener('click', _this6.hide); // Don't remove popper instance, just the HTML element

            _this6._removeTooltipNode();
          }
        }, disposeTime);
      }
      removeClasses(this.reference, ['v-tooltip-open']);
      return this;
    }
  }, {
    key: "_removeTooltipNode",
    value: function _removeTooltipNode() {
      if (!this._tooltipNode) return;
      var parentNode = this._tooltipNode.parentNode;
      if (parentNode) {
        parentNode.removeChild(this._tooltipNode);
        this.reference.removeAttribute('aria-describedby');
      }
      this._tooltipNode = null;
    }
  }, {
    key: "_dispose",
    value: function _dispose() {
      var _this7 = this;
      this._isDisposed = true;
      this.reference.removeAttribute('data-original-title');
      if (this.$_originalTitle) {
        this.reference.setAttribute('title', this.$_originalTitle);
      } // remove event listeners first to prevent any unexpected behaviour

      this._events.forEach(function (_ref) {
        var func = _ref.func,
          event = _ref.event;
        _this7.reference.removeEventListener(event, func);
      });
      this._events = [];
      if (this._tooltipNode) {
        this._hide();
        this._tooltipNode.removeEventListener('mouseenter', this.hide);
        this._tooltipNode.removeEventListener('click', this.hide); // destroy instance

        this.popperInstance.destroy(); // destroy tooltipNode if removeOnDestroy is not set, as popperInstance.destroy() already removes the element

        if (!this.popperInstance.options.removeOnDestroy) {
          this._removeTooltipNode();
        }
      } else {
        this._noLongerOpen();
      }
      return this;
    }
  }, {
    key: "_findContainer",
    value: function _findContainer(container, reference) {
      // if container is a query, get the relative element
      if (typeof container === 'string') {
        container = window.document.querySelector(container);
      } else if (container === false) {
        // if container is `false`, set it to reference parent
        container = reference.parentNode;
      }
      return container;
    }
    /**
     * Append tooltip to container
     * @memberof Tooltip
     * @private
     * @param {HTMLElement} tooltip
     * @param {HTMLElement|String|false} container
     */
  }, {
    key: "_append",
    value: function _append(tooltipNode, container) {
      container.appendChild(tooltipNode);
    }
  }, {
    key: "_setEventListeners",
    value: function _setEventListeners(reference, events, options) {
      var _this8 = this;
      var directEvents = [];
      var oppositeEvents = [];
      events.forEach(function (event) {
        switch (event) {
          case 'hover':
            directEvents.push('mouseenter');
            oppositeEvents.push('mouseleave');
            if (_this8.options.hideOnTargetClick) oppositeEvents.push('click');
            break;
          case 'focus':
            directEvents.push('focus');
            oppositeEvents.push('blur');
            if (_this8.options.hideOnTargetClick) oppositeEvents.push('click');
            break;
          case 'click':
            directEvents.push('click');
            oppositeEvents.push('click');
            break;
        }
      }); // schedule show tooltip

      directEvents.forEach(function (event) {
        var func = function func(evt) {
          if (_this8._isOpen === true) {
            return;
          }
          evt.usedByTooltip = true;
          _this8._scheduleShow(reference, options.delay, options, evt);
        };
        _this8._events.push({
          event: event,
          func: func
        });
        reference.addEventListener(event, func);
      }); // schedule hide tooltip

      oppositeEvents.forEach(function (event) {
        var func = function func(evt) {
          if (evt.usedByTooltip === true) {
            return;
          }
          _this8._scheduleHide(reference, options.delay, options, evt);
        };
        _this8._events.push({
          event: event,
          func: func
        });
        reference.addEventListener(event, func);
      });
    }
  }, {
    key: "_onDocumentTouch",
    value: function _onDocumentTouch(event) {
      if (this._enableDocumentTouch) {
        this._scheduleHide(this.reference, this.options.delay, this.options, event);
      }
    }
  }, {
    key: "_scheduleShow",
    value: function _scheduleShow(reference, delay, options
    /*, evt */) {
      var _this9 = this;

      // defaults to 0
      var computedDelay = delay && delay.show || delay || 0;
      clearTimeout(this._scheduleTimer);
      this._scheduleTimer = window.setTimeout(function () {
        return _this9._show(reference, options);
      }, computedDelay);
    }
  }, {
    key: "_scheduleHide",
    value: function _scheduleHide(reference, delay, options, evt) {
      var _this10 = this;

      // defaults to 0
      var computedDelay = delay && delay.hide || delay || 0;
      clearTimeout(this._scheduleTimer);
      this._scheduleTimer = window.setTimeout(function () {
        if (_this10._isOpen === false) {
          return;
        }
        if (!_this10._tooltipNode.ownerDocument.body.contains(_this10._tooltipNode)) {
          return;
        } // if we are hiding because of a mouseleave, we must check that the new
        // reference isn't the tooltip, because in this case we don't want to hide it

        if (evt.type === 'mouseleave') {
          var isSet = _this10._setTooltipNodeEvent(evt, reference, delay, options); // if we set the new event, don't hide the tooltip yet
          // the new event will take care to hide it if necessary

          if (isSet) {
            return;
          }
        }
        _this10._hide(reference, options);
      }, computedDelay);
    }
  }]);
  return Tooltip;
}(); // Hide tooltips on touch devices

if (typeof document !== 'undefined') {
  document.addEventListener('touchstart', function (event) {
    for (var i = 0; i < openTooltips.length; i++) {
      openTooltips[i]._onDocumentTouch(event);
    }
  }, supportsPassive ? {
    passive: true,
    capture: true
  } : true);
}
/**
 * Placement function, its context is the Tooltip instance.
 * @memberof Tooltip
 * @callback PlacementFunction
 * @param {HTMLElement} tooltip - tooltip DOM node.
 * @param {HTMLElement} reference - reference DOM node.
 * @return {String} placement - One of the allowed placement options.
 */

/**
 * Title function, its context is the Tooltip instance.
 * @memberof Tooltip
 * @callback TitleFunction
 * @return {String} placement - The desired title.
 */

function ownKeys$1(object, enumerableOnly) {
  var keys = Object.keys(object);
  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }
  return keys;
}
function _objectSpread$1(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};
    if (i % 2) {
      ownKeys$1(Object(source), true).forEach(function (key) {
        (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys$1(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }
  return target;
}
var state = {
  enabled: true
};
var positions = ['top', 'top-start', 'top-end', 'right', 'right-start', 'right-end', 'bottom', 'bottom-start', 'bottom-end', 'left', 'left-start', 'left-end'];
var defaultOptions = {
  // Default tooltip placement relative to target element
  defaultPlacement: 'top',
  // Default CSS classes applied to the tooltip element
  defaultClass: 'vue-tooltip-theme',
  // Default CSS classes applied to the target element of the tooltip
  defaultTargetClass: 'has-tooltip',
  // Is the content HTML by default?
  defaultHtml: true,
  // Default HTML template of the tooltip element
  // It must include `tooltip-arrow` & `tooltip-inner` CSS classes (can be configured, see below)
  // Change if the classes conflict with other libraries (for example bootstrap)
  defaultTemplate: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
  // Selector used to get the arrow element in the tooltip template
  defaultArrowSelector: '.tooltip-arrow, .tooltip__arrow',
  // Selector used to get the inner content element in the tooltip template
  defaultInnerSelector: '.tooltip-inner, .tooltip__inner',
  // Delay (ms)
  defaultDelay: 0,
  // Default events that trigger the tooltip
  defaultTrigger: 'hover focus',
  // Default position offset (px)
  defaultOffset: 0,
  // Default container where the tooltip will be appended
  defaultContainer: 'body',
  defaultBoundariesElement: undefined,
  defaultPopperOptions: {},
  // Class added when content is loading
  defaultLoadingClass: 'tooltip-loading',
  // Displayed when tooltip content is loading
  defaultLoadingContent: '...',
  // Hide on mouseover tooltip
  autoHide: true,
  // Close tooltip on click on tooltip target?
  defaultHideOnTargetClick: true,
  // Auto destroy tooltip DOM nodes (ms)
  disposeTimeout: 5000,
  // Options for popover
  popover: {
    defaultPlacement: 'bottom',
    // Use the `popoverClass` prop for theming
    defaultClass: 'vue-popover-theme',
    // Base class (change if conflicts with other libraries)
    defaultBaseClass: 'tooltip popover',
    // Wrapper class (contains arrow and inner)
    defaultWrapperClass: 'wrapper',
    // Inner content class
    defaultInnerClass: 'tooltip-inner popover-inner',
    // Arrow class
    defaultArrowClass: 'tooltip-arrow popover-arrow',
    // Class added when popover is open
    defaultOpenClass: 'open',
    defaultDelay: 0,
    defaultTrigger: 'click',
    defaultOffset: 0,
    defaultContainer: 'body',
    defaultBoundariesElement: undefined,
    defaultPopperOptions: {},
    // Hides if clicked outside of popover
    defaultAutoHide: true,
    // Update popper on content resize
    defaultHandleResize: true
  }
};
function getOptions(options) {
  var result = {
    placement: typeof options.placement !== 'undefined' ? options.placement : directive.options.defaultPlacement,
    delay: typeof options.delay !== 'undefined' ? options.delay : directive.options.defaultDelay,
    html: typeof options.html !== 'undefined' ? options.html : directive.options.defaultHtml,
    template: typeof options.template !== 'undefined' ? options.template : directive.options.defaultTemplate,
    arrowSelector: typeof options.arrowSelector !== 'undefined' ? options.arrowSelector : directive.options.defaultArrowSelector,
    innerSelector: typeof options.innerSelector !== 'undefined' ? options.innerSelector : directive.options.defaultInnerSelector,
    trigger: typeof options.trigger !== 'undefined' ? options.trigger : directive.options.defaultTrigger,
    offset: typeof options.offset !== 'undefined' ? options.offset : directive.options.defaultOffset,
    container: typeof options.container !== 'undefined' ? options.container : directive.options.defaultContainer,
    boundariesElement: typeof options.boundariesElement !== 'undefined' ? options.boundariesElement : directive.options.defaultBoundariesElement,
    autoHide: typeof options.autoHide !== 'undefined' ? options.autoHide : directive.options.autoHide,
    hideOnTargetClick: typeof options.hideOnTargetClick !== 'undefined' ? options.hideOnTargetClick : directive.options.defaultHideOnTargetClick,
    loadingClass: typeof options.loadingClass !== 'undefined' ? options.loadingClass : directive.options.defaultLoadingClass,
    loadingContent: typeof options.loadingContent !== 'undefined' ? options.loadingContent : directive.options.defaultLoadingContent,
    popperOptions: _objectSpread$1({}, typeof options.popperOptions !== 'undefined' ? options.popperOptions : directive.options.defaultPopperOptions)
  };
  if (result.offset) {
    var typeofOffset = (0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(result.offset);
    var offset = result.offset; // One value -> switch

    if (typeofOffset === 'number' || typeofOffset === 'string' && offset.indexOf(',') === -1) {
      offset = "0, ".concat(offset);
    }
    if (!result.popperOptions.modifiers) {
      result.popperOptions.modifiers = {};
    }
    result.popperOptions.modifiers.offset = {
      offset: offset
    };
  }
  if (result.trigger && result.trigger.indexOf('click') !== -1) {
    result.hideOnTargetClick = false;
  }
  return result;
}
function getPlacement(value, modifiers) {
  var placement = value.placement;
  for (var i = 0; i < positions.length; i++) {
    var pos = positions[i];
    if (modifiers[pos]) {
      placement = pos;
    }
  }
  return placement;
}
function getContent(value) {
  var type = (0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(value);
  if (type === 'string') {
    return value;
  } else if (value && type === 'object') {
    return value.content;
  } else {
    return false;
  }
}
function createTooltip(el, value) {
  var modifiers = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var content = getContent(value);
  var classes = typeof value.classes !== 'undefined' ? value.classes : directive.options.defaultClass;
  var opts = _objectSpread$1({
    title: content
  }, getOptions(_objectSpread$1(_objectSpread$1({}, (0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(value) === 'object' ? value : {}), {}, {
    placement: getPlacement(value, modifiers)
  })));
  var tooltip = el._tooltip = new Tooltip(el, opts);
  tooltip.setClasses(classes);
  tooltip._vueEl = el; // Class on target

  var targetClasses = typeof value.targetClasses !== 'undefined' ? value.targetClasses : directive.options.defaultTargetClass;
  el._tooltipTargetClasses = targetClasses;
  addClasses(el, targetClasses);
  return tooltip;
}
function destroyTooltip(el) {
  if (el._tooltip) {
    el._tooltip.dispose();
    delete el._tooltip;
    delete el._tooltipOldShow;
  }
  if (el._tooltipTargetClasses) {
    removeClasses(el, el._tooltipTargetClasses);
    delete el._tooltipTargetClasses;
  }
}
function bind(el, _ref) {
  var value = _ref.value;
  _ref.oldValue;
  var modifiers = _ref.modifiers;
  var content = getContent(value);
  if (!content || !state.enabled) {
    destroyTooltip(el);
  } else {
    var tooltip;
    if (el._tooltip) {
      tooltip = el._tooltip; // Content

      tooltip.setContent(content); // Options

      tooltip.setOptions(_objectSpread$1(_objectSpread$1({}, value), {}, {
        placement: getPlacement(value, modifiers)
      }));
    } else {
      tooltip = createTooltip(el, value, modifiers);
    } // Manual show

    if (typeof value.show !== 'undefined' && value.show !== el._tooltipOldShow) {
      el._tooltipOldShow = value.show;
      value.show ? tooltip.show() : tooltip.hide();
    }
  }
}
var directive = {
  options: defaultOptions,
  bind: bind,
  update: bind,
  unbind: function unbind(el) {
    destroyTooltip(el);
  }
};
function addListeners(el) {
  el.addEventListener('click', onClick);
  el.addEventListener('touchstart', onTouchStart, supportsPassive ? {
    passive: true
  } : false);
}
function removeListeners(el) {
  el.removeEventListener('click', onClick);
  el.removeEventListener('touchstart', onTouchStart);
  el.removeEventListener('touchend', onTouchEnd);
  el.removeEventListener('touchcancel', onTouchCancel);
}
function onClick(event) {
  var el = event.currentTarget;
  event.closePopover = !el.$_vclosepopover_touch;
  event.closeAllPopover = el.$_closePopoverModifiers && !!el.$_closePopoverModifiers.all;
}
function onTouchStart(event) {
  if (event.changedTouches.length === 1) {
    var el = event.currentTarget;
    el.$_vclosepopover_touch = true;
    var touch = event.changedTouches[0];
    el.$_vclosepopover_touchPoint = touch;
    el.addEventListener('touchend', onTouchEnd);
    el.addEventListener('touchcancel', onTouchCancel);
  }
}
function onTouchEnd(event) {
  var el = event.currentTarget;
  el.$_vclosepopover_touch = false;
  if (event.changedTouches.length === 1) {
    var touch = event.changedTouches[0];
    var firstTouch = el.$_vclosepopover_touchPoint;
    event.closePopover = Math.abs(touch.screenY - firstTouch.screenY) < 20 && Math.abs(touch.screenX - firstTouch.screenX) < 20;
    event.closeAllPopover = el.$_closePopoverModifiers && !!el.$_closePopoverModifiers.all;
  }
}
function onTouchCancel(event) {
  var el = event.currentTarget;
  el.$_vclosepopover_touch = false;
}
var vclosepopover = {
  bind: function bind(el, _ref) {
    var value = _ref.value,
      modifiers = _ref.modifiers;
    el.$_closePopoverModifiers = modifiers;
    if (typeof value === 'undefined' || value) {
      addListeners(el);
    }
  },
  update: function update(el, _ref2) {
    var value = _ref2.value,
      oldValue = _ref2.oldValue,
      modifiers = _ref2.modifiers;
    el.$_closePopoverModifiers = modifiers;
    if (value !== oldValue) {
      if (typeof value === 'undefined' || value) {
        addListeners(el);
      } else {
        removeListeners(el);
      }
    }
  },
  unbind: function unbind(el) {
    removeListeners(el);
  }
};
function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);
  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }
  return keys;
}
function _objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};
    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }
  return target;
}
function getDefault(key) {
  var value = directive.options.popover[key];
  if (typeof value === 'undefined') {
    return directive.options[key];
  }
  return value;
}
var isIOS = false;
if (typeof window !== 'undefined' && typeof navigator !== 'undefined') {
  isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}
var openPopovers = [];
var Element = function Element() {};
if (typeof window !== 'undefined') {
  Element = window.Element;
}
var script = {
  name: 'VPopover',
  components: {
    ResizeObserver: vue_resize__WEBPACK_IMPORTED_MODULE_6__.ResizeObserver
  },
  props: {
    open: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    placement: {
      type: String,
      default: function _default() {
        return getDefault('defaultPlacement');
      }
    },
    delay: {
      type: [String, Number, Object],
      default: function _default() {
        return getDefault('defaultDelay');
      }
    },
    offset: {
      type: [String, Number],
      default: function _default() {
        return getDefault('defaultOffset');
      }
    },
    trigger: {
      type: String,
      default: function _default() {
        return getDefault('defaultTrigger');
      }
    },
    container: {
      type: [String, Object, Element, Boolean],
      default: function _default() {
        return getDefault('defaultContainer');
      }
    },
    boundariesElement: {
      type: [String, Element],
      default: function _default() {
        return getDefault('defaultBoundariesElement');
      }
    },
    popperOptions: {
      type: Object,
      default: function _default() {
        return getDefault('defaultPopperOptions');
      }
    },
    popoverClass: {
      type: [String, Array],
      default: function _default() {
        return getDefault('defaultClass');
      }
    },
    popoverBaseClass: {
      type: [String, Array],
      default: function _default() {
        return directive.options.popover.defaultBaseClass;
      }
    },
    popoverInnerClass: {
      type: [String, Array],
      default: function _default() {
        return directive.options.popover.defaultInnerClass;
      }
    },
    popoverWrapperClass: {
      type: [String, Array],
      default: function _default() {
        return directive.options.popover.defaultWrapperClass;
      }
    },
    popoverArrowClass: {
      type: [String, Array],
      default: function _default() {
        return directive.options.popover.defaultArrowClass;
      }
    },
    autoHide: {
      type: Boolean,
      default: function _default() {
        return directive.options.popover.defaultAutoHide;
      }
    },
    handleResize: {
      type: Boolean,
      default: function _default() {
        return directive.options.popover.defaultHandleResize;
      }
    },
    openGroup: {
      type: String,
      default: null
    },
    openClass: {
      type: [String, Array],
      default: function _default() {
        return directive.options.popover.defaultOpenClass;
      }
    },
    ariaId: {
      default: null
    }
  },
  data: function data() {
    return {
      isOpen: false,
      id: Math.random().toString(36).substr(2, 10)
    };
  },
  computed: {
    cssClass: function cssClass() {
      return (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__["default"])({}, this.openClass, this.isOpen);
    },
    popoverId: function popoverId() {
      return "popover_".concat(this.ariaId != null ? this.ariaId : this.id);
    }
  },
  watch: {
    open: function open(val) {
      if (val) {
        this.show();
      } else {
        this.hide();
      }
    },
    disabled: function disabled(val, oldVal) {
      if (val !== oldVal) {
        if (val) {
          this.hide();
        } else if (this.open) {
          this.show();
        }
      }
    },
    container: function container(val) {
      if (this.isOpen && this.popperInstance) {
        var popoverNode = this.$refs.popover;
        var reference = this.$refs.trigger;
        var container = this.$_findContainer(this.container, reference);
        if (!container) {
          console.warn('No container for popover', this);
          return;
        }
        container.appendChild(popoverNode);
        this.popperInstance.scheduleUpdate();
      }
    },
    trigger: function trigger(val) {
      this.$_removeEventListeners();
      this.$_addEventListeners();
    },
    placement: function placement(val) {
      var _this = this;
      this.$_updatePopper(function () {
        _this.popperInstance.options.placement = val;
      });
    },
    offset: '$_restartPopper',
    boundariesElement: '$_restartPopper',
    popperOptions: {
      handler: '$_restartPopper',
      deep: true
    }
  },
  created: function created() {
    this.$_isDisposed = false;
    this.$_mounted = false;
    this.$_events = [];
    this.$_preventOpen = false;
  },
  mounted: function mounted() {
    var popoverNode = this.$refs.popover;
    popoverNode.parentNode && popoverNode.parentNode.removeChild(popoverNode);
    this.$_init();
    if (this.open) {
      this.show();
    }
  },
  deactivated: function deactivated() {
    this.hide();
  },
  beforeDestroy: function beforeDestroy() {
    this.dispose();
  },
  methods: {
    show: function show() {
      var _this2 = this;
      var _ref2 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        event = _ref2.event;
      _ref2.skipDelay;
      var _ref2$force = _ref2.force,
        force = _ref2$force === void 0 ? false : _ref2$force;
      if (force || !this.disabled) {
        this.$_scheduleShow(event);
        this.$emit('show');
      }
      this.$emit('update:open', true);
      this.$_beingShowed = true;
      requestAnimationFrame(function () {
        _this2.$_beingShowed = false;
      });
    },
    hide: function hide() {
      var _ref3 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        event = _ref3.event;
      _ref3.skipDelay;
      this.$_scheduleHide(event);
      this.$emit('hide');
      this.$emit('update:open', false);
    },
    dispose: function dispose() {
      this.$_isDisposed = true;
      this.$_removeEventListeners();
      this.hide({
        skipDelay: true
      });
      if (this.popperInstance) {
        this.popperInstance.destroy(); // destroy tooltipNode if removeOnDestroy is not set, as popperInstance.destroy() already removes the element

        if (!this.popperInstance.options.removeOnDestroy) {
          var popoverNode = this.$refs.popover;
          popoverNode.parentNode && popoverNode.parentNode.removeChild(popoverNode);
        }
      }
      this.$_mounted = false;
      this.popperInstance = null;
      this.isOpen = false;
      this.$emit('dispose');
    },
    $_init: function $_init() {
      if (this.trigger.indexOf('manual') === -1) {
        this.$_addEventListeners();
      }
    },
    $_show: function $_show() {
      var _this3 = this;
      var reference = this.$refs.trigger;
      var popoverNode = this.$refs.popover;
      clearTimeout(this.$_disposeTimer); // Already open

      if (this.isOpen) {
        return;
      } // Popper is already initialized

      if (this.popperInstance) {
        this.isOpen = true;
        this.popperInstance.enableEventListeners();
        this.popperInstance.scheduleUpdate();
      }
      if (!this.$_mounted) {
        var container = this.$_findContainer(this.container, reference);
        if (!container) {
          console.warn('No container for popover', this);
          return;
        }
        container.appendChild(popoverNode);
        this.$_mounted = true;
        this.isOpen = false;
        if (this.popperInstance) {
          requestAnimationFrame(function () {
            if (!_this3.hidden) {
              _this3.isOpen = true;
            }
          });
        }
      }
      if (!this.popperInstance) {
        var popperOptions = _objectSpread(_objectSpread({}, this.popperOptions), {}, {
          placement: this.placement
        });
        popperOptions.modifiers = _objectSpread(_objectSpread({}, popperOptions.modifiers), {}, {
          arrow: _objectSpread(_objectSpread({}, popperOptions.modifiers && popperOptions.modifiers.arrow), {}, {
            element: this.$refs.arrow
          })
        });
        if (this.offset) {
          var offset = this.$_getOffset();
          popperOptions.modifiers.offset = _objectSpread(_objectSpread({}, popperOptions.modifiers && popperOptions.modifiers.offset), {}, {
            offset: offset
          });
        }
        if (this.boundariesElement) {
          popperOptions.modifiers.preventOverflow = _objectSpread(_objectSpread({}, popperOptions.modifiers && popperOptions.modifiers.preventOverflow), {}, {
            boundariesElement: this.boundariesElement
          });
        }
        this.popperInstance = new popper_js__WEBPACK_IMPORTED_MODULE_4__["default"](reference, popoverNode, popperOptions); // Fix position

        requestAnimationFrame(function () {
          if (_this3.hidden) {
            _this3.hidden = false;
            _this3.$_hide();
            return;
          }
          if (!_this3.$_isDisposed && _this3.popperInstance) {
            _this3.popperInstance.scheduleUpdate(); // Show the tooltip

            requestAnimationFrame(function () {
              if (_this3.hidden) {
                _this3.hidden = false;
                _this3.$_hide();
                return;
              }
              if (!_this3.$_isDisposed) {
                _this3.isOpen = true;
              } else {
                _this3.dispose();
              }
            });
          } else {
            _this3.dispose();
          }
        });
      }
      var openGroup = this.openGroup;
      if (openGroup) {
        var popover;
        for (var i = 0; i < openPopovers.length; i++) {
          popover = openPopovers[i];
          if (popover.openGroup !== openGroup) {
            popover.hide();
            popover.$emit('close-group');
          }
        }
      }
      openPopovers.push(this);
      this.$emit('apply-show');
    },
    $_hide: function $_hide() {
      var _this4 = this;

      // Already hidden
      if (!this.isOpen) {
        return;
      }
      var index = openPopovers.indexOf(this);
      if (index !== -1) {
        openPopovers.splice(index, 1);
      }
      this.isOpen = false;
      if (this.popperInstance) {
        this.popperInstance.disableEventListeners();
      }
      clearTimeout(this.$_disposeTimer);
      var disposeTime = directive.options.popover.disposeTimeout || directive.options.disposeTimeout;
      if (disposeTime !== null) {
        this.$_disposeTimer = setTimeout(function () {
          var popoverNode = _this4.$refs.popover;
          if (popoverNode) {
            // Don't remove popper instance, just the HTML element
            popoverNode.parentNode && popoverNode.parentNode.removeChild(popoverNode);
            _this4.$_mounted = false;
          }
        }, disposeTime);
      }
      this.$emit('apply-hide');
    },
    $_findContainer: function $_findContainer(container, reference) {
      // if container is a query, get the relative element
      if (typeof container === 'string') {
        container = window.document.querySelector(container);
      } else if (container === false) {
        // if container is `false`, set it to reference parent
        container = reference.parentNode;
      }
      return container;
    },
    $_getOffset: function $_getOffset() {
      var typeofOffset = (0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(this.offset);
      var offset = this.offset; // One value -> switch

      if (typeofOffset === 'number' || typeofOffset === 'string' && offset.indexOf(',') === -1) {
        offset = "0, ".concat(offset);
      }
      return offset;
    },
    $_addEventListeners: function $_addEventListeners() {
      var _this5 = this;
      var reference = this.$refs.trigger;
      var directEvents = [];
      var oppositeEvents = [];
      var events = typeof this.trigger === 'string' ? this.trigger.split(' ').filter(function (trigger) {
        return ['click', 'hover', 'focus'].indexOf(trigger) !== -1;
      }) : [];
      events.forEach(function (event) {
        switch (event) {
          case 'hover':
            directEvents.push('mouseenter');
            oppositeEvents.push('mouseleave');
            break;
          case 'focus':
            directEvents.push('focus');
            oppositeEvents.push('blur');
            break;
          case 'click':
            directEvents.push('click');
            oppositeEvents.push('click');
            break;
        }
      }); // schedule show tooltip

      directEvents.forEach(function (event) {
        var func = function func(event) {
          if (_this5.isOpen) {
            return;
          }
          event.usedByTooltip = true;
          !_this5.$_preventOpen && _this5.show({
            event: event
          });
          _this5.hidden = false;
        };
        _this5.$_events.push({
          event: event,
          func: func
        });
        reference.addEventListener(event, func);
      }); // schedule hide tooltip

      oppositeEvents.forEach(function (event) {
        var func = function func(event) {
          if (event.usedByTooltip) {
            return;
          }
          _this5.hide({
            event: event
          });
          _this5.hidden = true;
        };
        _this5.$_events.push({
          event: event,
          func: func
        });
        reference.addEventListener(event, func);
      });
    },
    $_scheduleShow: function $_scheduleShow() {
      var skipDelay = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      clearTimeout(this.$_scheduleTimer);
      if (skipDelay) {
        this.$_show();
      } else {
        // defaults to 0
        var computedDelay = parseInt(this.delay && this.delay.show || this.delay || 0);
        this.$_scheduleTimer = setTimeout(this.$_show.bind(this), computedDelay);
      }
    },
    $_scheduleHide: function $_scheduleHide() {
      var _this6 = this;
      var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      var skipDelay = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      clearTimeout(this.$_scheduleTimer);
      if (skipDelay) {
        this.$_hide();
      } else {
        // defaults to 0
        var computedDelay = parseInt(this.delay && this.delay.hide || this.delay || 0);
        this.$_scheduleTimer = setTimeout(function () {
          if (!_this6.isOpen) {
            return;
          } // if we are hiding because of a mouseleave, we must check that the new
          // reference isn't the tooltip, because in this case we don't want to hide it

          if (event && event.type === 'mouseleave') {
            var isSet = _this6.$_setTooltipNodeEvent(event); // if we set the new event, don't hide the tooltip yet
            // the new event will take care to hide it if necessary

            if (isSet) {
              return;
            }
          }
          _this6.$_hide();
        }, computedDelay);
      }
    },
    $_setTooltipNodeEvent: function $_setTooltipNodeEvent(event) {
      var _this7 = this;
      var reference = this.$refs.trigger;
      var popoverNode = this.$refs.popover;
      var relatedreference = event.relatedreference || event.toElement || event.relatedTarget;
      var callback = function callback(event2) {
        var relatedreference2 = event2.relatedreference || event2.toElement || event2.relatedTarget; // Remove event listener after call

        popoverNode.removeEventListener(event.type, callback); // If the new reference is not the reference element

        if (!reference.contains(relatedreference2)) {
          // Schedule to hide tooltip
          _this7.hide({
            event: event2
          });
        }
      };
      if (popoverNode.contains(relatedreference)) {
        // listen to mouseleave on the tooltip element to be able to hide the tooltip
        popoverNode.addEventListener(event.type, callback);
        return true;
      }
      return false;
    },
    $_removeEventListeners: function $_removeEventListeners() {
      var reference = this.$refs.trigger;
      this.$_events.forEach(function (_ref4) {
        var func = _ref4.func,
          event = _ref4.event;
        reference.removeEventListener(event, func);
      });
      this.$_events = [];
    },
    $_updatePopper: function $_updatePopper(cb) {
      if (this.popperInstance) {
        cb();
        if (this.isOpen) this.popperInstance.scheduleUpdate();
      }
    },
    $_restartPopper: function $_restartPopper() {
      if (this.popperInstance) {
        var isOpen = this.isOpen;
        this.dispose();
        this.$_isDisposed = false;
        this.$_init();
        if (isOpen) {
          this.show({
            skipDelay: true,
            force: true
          });
        }
      }
    },
    $_handleGlobalClose: function $_handleGlobalClose(event) {
      var _this8 = this;
      var touch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      if (this.$_beingShowed) return;
      this.hide({
        event: event
      });
      if (event.closePopover) {
        this.$emit('close-directive');
      } else {
        this.$emit('auto-hide');
      }
      if (touch) {
        this.$_preventOpen = true;
        setTimeout(function () {
          _this8.$_preventOpen = false;
        }, 300);
      }
    },
    $_handleResize: function $_handleResize() {
      if (this.isOpen && this.popperInstance) {
        this.popperInstance.scheduleUpdate();
        this.$emit('resize');
      }
    }
  }
};
if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  if (isIOS) {
    document.addEventListener('touchend', handleGlobalTouchend, supportsPassive ? {
      passive: true,
      capture: true
    } : true);
  } else {
    window.addEventListener('click', handleGlobalClick, true);
  }
}
function handleGlobalClick(event) {
  handleGlobalClose(event);
}
function handleGlobalTouchend(event) {
  handleGlobalClose(event, true);
}
function handleGlobalClose(event) {
  var touch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var _loop = function _loop(i) {
    var popover = openPopovers[i];
    if (popover.$refs.popover) {
      var contains = popover.$refs.popover.contains(event.target);
      requestAnimationFrame(function () {
        if (event.closeAllPopover || event.closePopover && contains || popover.autoHide && !contains) {
          popover.$_handleGlobalClose(event, touch);
        }
      });
    }
  };

  // Delay so that close directive has time to set values
  for (var i = 0; i < openPopovers.length; i++) {
    _loop(i);
  }
}
function normalizeComponent(template, style, script, scopeId, isFunctionalTemplate, moduleIdentifier /* server only */, shadowMode, createInjector, createInjectorSSR, createInjectorShadow) {
  if (typeof shadowMode !== 'boolean') {
    createInjectorSSR = createInjector;
    createInjector = shadowMode;
    shadowMode = false;
  }
  // Vue.extend constructor export interop.
  const options = typeof script === 'function' ? script.options : script;
  // render functions
  if (template && template.render) {
    options.render = template.render;
    options.staticRenderFns = template.staticRenderFns;
    options._compiled = true;
    // functional template
    if (isFunctionalTemplate) {
      options.functional = true;
    }
  }
  // scopedId
  if (scopeId) {
    options._scopeId = scopeId;
  }
  let hook;
  if (moduleIdentifier) {
    // server build
    hook = function (context) {
      // 2.3 injection
      context = context ||
      // cached call
      this.$vnode && this.$vnode.ssrContext ||
      // stateful
      this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext; // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__;
      }
      // inject component styles
      if (style) {
        style.call(this, createInjectorSSR(context));
      }
      // register component module identifier for async chunk inference
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier);
      }
    };
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook;
  } else if (style) {
    hook = shadowMode ? function (context) {
      style.call(this, createInjectorShadow(context, this.$root.$options.shadowRoot));
    } : function (context) {
      style.call(this, createInjector(context));
    };
  }
  if (hook) {
    if (options.functional) {
      // register for functional component in vue file
      const originalRender = options.render;
      options.render = function renderWithStyleInjection(h, context) {
        hook.call(context);
        return originalRender(h, context);
      };
    } else {
      // inject component registration as beforeCreate hook
      const existing = options.beforeCreate;
      options.beforeCreate = existing ? [].concat(existing, hook) : [hook];
    }
  }
  return script;
}

/* script */
var __vue_script__ = script;
/* template */

var __vue_render__ = function __vue_render__() {
  var _vm = this;
  var _h = _vm.$createElement;
  var _c = _vm._self._c || _h;
  return _c("div", {
    staticClass: "v-popover",
    class: _vm.cssClass
  }, [_c("div", {
    ref: "trigger",
    staticClass: "trigger",
    staticStyle: {
      display: "inline-block"
    },
    attrs: {
      "aria-describedby": _vm.isOpen ? _vm.popoverId : undefined,
      tabindex: _vm.trigger.indexOf("focus") !== -1 ? 0 : undefined
    }
  }, [_vm._t("default")], 2), _vm._v(" "), _c("div", {
    ref: "popover",
    class: [_vm.popoverBaseClass, _vm.popoverClass, _vm.cssClass],
    style: {
      visibility: _vm.isOpen ? "visible" : "hidden"
    },
    attrs: {
      id: _vm.popoverId,
      "aria-hidden": _vm.isOpen ? "false" : "true",
      tabindex: _vm.autoHide ? 0 : undefined
    },
    on: {
      keyup: function keyup($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "esc", 27, $event.key, ["Esc", "Escape"])) {
          return null;
        }
        _vm.autoHide && _vm.hide();
      }
    }
  }, [_c("div", {
    class: _vm.popoverWrapperClass
  }, [_c("div", {
    ref: "inner",
    class: _vm.popoverInnerClass,
    staticStyle: {
      position: "relative"
    }
  }, [_c("div", [_vm._t("popover", null, {
    isOpen: _vm.isOpen
  })], 2), _vm._v(" "), _vm.handleResize ? _c("ResizeObserver", {
    on: {
      notify: _vm.$_handleResize
    }
  }) : _vm._e()], 1), _vm._v(" "), _c("div", {
    ref: "arrow",
    class: _vm.popoverArrowClass
  })])])]);
};
var __vue_staticRenderFns__ = [];
__vue_render__._withStripped = true;
/* style */

var __vue_inject_styles__ = undefined;
/* scoped */

var __vue_scope_id__ = undefined;
/* module identifier */

var __vue_module_identifier__ = undefined;
/* functional template */

var __vue_is_functional_template__ = false;
/* style inject */

/* style inject SSR */

/* style inject shadow dom */

var __vue_component__ = /*#__PURE__*/normalizeComponent({
  render: __vue_render__,
  staticRenderFns: __vue_staticRenderFns__
}, __vue_inject_styles__, __vue_script__, __vue_scope_id__, __vue_is_functional_template__, __vue_module_identifier__, false, undefined, undefined, undefined);
function styleInject(css, ref) {
  if (ref === void 0) ref = {};
  var insertAt = ref.insertAt;
  if (!css || typeof document === 'undefined') {
    return;
  }
  var head = document.head || document.getElementsByTagName('head')[0];
  var style = document.createElement('style');
  style.type = 'text/css';
  if (insertAt === 'top') {
    if (head.firstChild) {
      head.insertBefore(style, head.firstChild);
    } else {
      head.appendChild(style);
    }
  } else {
    head.appendChild(style);
  }
  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    style.appendChild(document.createTextNode(css));
  }
}
var css_248z = ".resize-observer[data-v-8859cc6c]{position:absolute;top:0;left:0;z-index:-1;width:100%;height:100%;border:none;background-color:transparent;pointer-events:none;display:block;overflow:hidden;opacity:0}.resize-observer[data-v-8859cc6c] object{display:block;position:absolute;top:0;left:0;height:100%;width:100%;overflow:hidden;pointer-events:none;z-index:-1}";
styleInject(css_248z);
function install(Vue) {
  var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  if (install.installed) return;
  install.installed = true;
  var finalOptions = {};
  lodash_merge__WEBPACK_IMPORTED_MODULE_7___default()(finalOptions, defaultOptions, options);
  plugin.options = finalOptions;
  directive.options = finalOptions;
  Vue.directive('tooltip', directive);
  Vue.directive('close-popover', vclosepopover);
  Vue.component('VPopover', __vue_component__);
}
var VTooltip = directive;
var VClosePopover = vclosepopover;
var VPopover = __vue_component__;
var plugin = {
  install: install,
  get enabled() {
    return state.enabled;
  },
  set enabled(value) {
    state.enabled = value;
  }
}; // Auto-install

var GlobalVue = null;
if (typeof window !== 'undefined') {
  GlobalVue = window.Vue;
} else if (typeof globalThis !== 'undefined') {
  GlobalVue = globalThis.Vue;
}
if (GlobalVue) {
  GlobalVue.use(plugin);
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (plugin);


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05"
/*!****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05 ***!
  \****************************************************************************************************************************************************************************************************************************************************************/
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


/***/ },

/***/ "./node_modules/lodash/_assignMergeValue.js"
/*!**************************************************!*\
  !*** ./node_modules/lodash/_assignMergeValue.js ***!
  \**************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var baseAssignValue = __webpack_require__(/*! ./_baseAssignValue */ "./node_modules/lodash/_baseAssignValue.js"),
    eq = __webpack_require__(/*! ./eq */ "./node_modules/lodash/eq.js");

/**
 * This function is like `assignValue` except that it doesn't assign
 * `undefined` values.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {string} key The key of the property to assign.
 * @param {*} value The value to assign.
 */
function assignMergeValue(object, key, value) {
  if ((value !== undefined && !eq(object[key], value)) ||
      (value === undefined && !(key in object))) {
    baseAssignValue(object, key, value);
  }
}

module.exports = assignMergeValue;


/***/ },

/***/ "./node_modules/lodash/_baseMerge.js"
/*!*******************************************!*\
  !*** ./node_modules/lodash/_baseMerge.js ***!
  \*******************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var Stack = __webpack_require__(/*! ./_Stack */ "./node_modules/lodash/_Stack.js"),
    assignMergeValue = __webpack_require__(/*! ./_assignMergeValue */ "./node_modules/lodash/_assignMergeValue.js"),
    baseFor = __webpack_require__(/*! ./_baseFor */ "./node_modules/lodash/_baseFor.js"),
    baseMergeDeep = __webpack_require__(/*! ./_baseMergeDeep */ "./node_modules/lodash/_baseMergeDeep.js"),
    isObject = __webpack_require__(/*! ./isObject */ "./node_modules/lodash/isObject.js"),
    keysIn = __webpack_require__(/*! ./keysIn */ "./node_modules/lodash/keysIn.js"),
    safeGet = __webpack_require__(/*! ./_safeGet */ "./node_modules/lodash/_safeGet.js");

/**
 * The base implementation of `_.merge` without support for multiple sources.
 *
 * @private
 * @param {Object} object The destination object.
 * @param {Object} source The source object.
 * @param {number} srcIndex The index of `source`.
 * @param {Function} [customizer] The function to customize merged values.
 * @param {Object} [stack] Tracks traversed source values and their merged
 *  counterparts.
 */
function baseMerge(object, source, srcIndex, customizer, stack) {
  if (object === source) {
    return;
  }
  baseFor(source, function(srcValue, key) {
    stack || (stack = new Stack);
    if (isObject(srcValue)) {
      baseMergeDeep(object, source, key, srcIndex, baseMerge, customizer, stack);
    }
    else {
      var newValue = customizer
        ? customizer(safeGet(object, key), srcValue, (key + ''), object, source, stack)
        : undefined;

      if (newValue === undefined) {
        newValue = srcValue;
      }
      assignMergeValue(object, key, newValue);
    }
  }, keysIn);
}

module.exports = baseMerge;


/***/ },

/***/ "./node_modules/lodash/_baseMergeDeep.js"
/*!***********************************************!*\
  !*** ./node_modules/lodash/_baseMergeDeep.js ***!
  \***********************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var assignMergeValue = __webpack_require__(/*! ./_assignMergeValue */ "./node_modules/lodash/_assignMergeValue.js"),
    cloneBuffer = __webpack_require__(/*! ./_cloneBuffer */ "./node_modules/lodash/_cloneBuffer.js"),
    cloneTypedArray = __webpack_require__(/*! ./_cloneTypedArray */ "./node_modules/lodash/_cloneTypedArray.js"),
    copyArray = __webpack_require__(/*! ./_copyArray */ "./node_modules/lodash/_copyArray.js"),
    initCloneObject = __webpack_require__(/*! ./_initCloneObject */ "./node_modules/lodash/_initCloneObject.js"),
    isArguments = __webpack_require__(/*! ./isArguments */ "./node_modules/lodash/isArguments.js"),
    isArray = __webpack_require__(/*! ./isArray */ "./node_modules/lodash/isArray.js"),
    isArrayLikeObject = __webpack_require__(/*! ./isArrayLikeObject */ "./node_modules/lodash/isArrayLikeObject.js"),
    isBuffer = __webpack_require__(/*! ./isBuffer */ "./node_modules/lodash/isBuffer.js"),
    isFunction = __webpack_require__(/*! ./isFunction */ "./node_modules/lodash/isFunction.js"),
    isObject = __webpack_require__(/*! ./isObject */ "./node_modules/lodash/isObject.js"),
    isPlainObject = __webpack_require__(/*! ./isPlainObject */ "./node_modules/lodash/isPlainObject.js"),
    isTypedArray = __webpack_require__(/*! ./isTypedArray */ "./node_modules/lodash/isTypedArray.js"),
    safeGet = __webpack_require__(/*! ./_safeGet */ "./node_modules/lodash/_safeGet.js"),
    toPlainObject = __webpack_require__(/*! ./toPlainObject */ "./node_modules/lodash/toPlainObject.js");

/**
 * A specialized version of `baseMerge` for arrays and objects which performs
 * deep merges and tracks traversed objects enabling objects with circular
 * references to be merged.
 *
 * @private
 * @param {Object} object The destination object.
 * @param {Object} source The source object.
 * @param {string} key The key of the value to merge.
 * @param {number} srcIndex The index of `source`.
 * @param {Function} mergeFunc The function to merge values.
 * @param {Function} [customizer] The function to customize assigned values.
 * @param {Object} [stack] Tracks traversed source values and their merged
 *  counterparts.
 */
function baseMergeDeep(object, source, key, srcIndex, mergeFunc, customizer, stack) {
  var objValue = safeGet(object, key),
      srcValue = safeGet(source, key),
      stacked = stack.get(srcValue);

  if (stacked) {
    assignMergeValue(object, key, stacked);
    return;
  }
  var newValue = customizer
    ? customizer(objValue, srcValue, (key + ''), object, source, stack)
    : undefined;

  var isCommon = newValue === undefined;

  if (isCommon) {
    var isArr = isArray(srcValue),
        isBuff = !isArr && isBuffer(srcValue),
        isTyped = !isArr && !isBuff && isTypedArray(srcValue);

    newValue = srcValue;
    if (isArr || isBuff || isTyped) {
      if (isArray(objValue)) {
        newValue = objValue;
      }
      else if (isArrayLikeObject(objValue)) {
        newValue = copyArray(objValue);
      }
      else if (isBuff) {
        isCommon = false;
        newValue = cloneBuffer(srcValue, true);
      }
      else if (isTyped) {
        isCommon = false;
        newValue = cloneTypedArray(srcValue, true);
      }
      else {
        newValue = [];
      }
    }
    else if (isPlainObject(srcValue) || isArguments(srcValue)) {
      newValue = objValue;
      if (isArguments(objValue)) {
        newValue = toPlainObject(objValue);
      }
      else if (!isObject(objValue) || isFunction(objValue)) {
        newValue = initCloneObject(srcValue);
      }
    }
    else {
      isCommon = false;
    }
  }
  if (isCommon) {
    // Recursively merge objects and arrays (susceptible to call stack limits).
    stack.set(srcValue, newValue);
    mergeFunc(newValue, srcValue, srcIndex, customizer, stack);
    stack['delete'](srcValue);
  }
  assignMergeValue(object, key, newValue);
}

module.exports = baseMergeDeep;


/***/ },

/***/ "./node_modules/lodash/_createAssigner.js"
/*!************************************************!*\
  !*** ./node_modules/lodash/_createAssigner.js ***!
  \************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var baseRest = __webpack_require__(/*! ./_baseRest */ "./node_modules/lodash/_baseRest.js"),
    isIterateeCall = __webpack_require__(/*! ./_isIterateeCall */ "./node_modules/lodash/_isIterateeCall.js");

/**
 * Creates a function like `_.assign`.
 *
 * @private
 * @param {Function} assigner The function to assign values.
 * @returns {Function} Returns the new assigner function.
 */
function createAssigner(assigner) {
  return baseRest(function(object, sources) {
    var index = -1,
        length = sources.length,
        customizer = length > 1 ? sources[length - 1] : undefined,
        guard = length > 2 ? sources[2] : undefined;

    customizer = (assigner.length > 3 && typeof customizer == 'function')
      ? (length--, customizer)
      : undefined;

    if (guard && isIterateeCall(sources[0], sources[1], guard)) {
      customizer = length < 3 ? undefined : customizer;
      length = 1;
    }
    object = Object(object);
    while (++index < length) {
      var source = sources[index];
      if (source) {
        assigner(object, source, index, customizer);
      }
    }
    return object;
  });
}

module.exports = createAssigner;


/***/ },

/***/ "./node_modules/lodash/_safeGet.js"
/*!*****************************************!*\
  !*** ./node_modules/lodash/_safeGet.js ***!
  \*****************************************/
(module) {

/**
 * Gets the value at `key`, unless `key` is "__proto__" or "constructor".
 *
 * @private
 * @param {Object} object The object to query.
 * @param {string} key The key of the property to get.
 * @returns {*} Returns the property value.
 */
function safeGet(object, key) {
  if (key === 'constructor' && typeof object[key] === 'function') {
    return;
  }

  if (key == '__proto__') {
    return;
  }

  return object[key];
}

module.exports = safeGet;


/***/ },

/***/ "./node_modules/lodash/isArrayLikeObject.js"
/*!**************************************************!*\
  !*** ./node_modules/lodash/isArrayLikeObject.js ***!
  \**************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var isArrayLike = __webpack_require__(/*! ./isArrayLike */ "./node_modules/lodash/isArrayLike.js"),
    isObjectLike = __webpack_require__(/*! ./isObjectLike */ "./node_modules/lodash/isObjectLike.js");

/**
 * This method is like `_.isArrayLike` except that it also checks if `value`
 * is an object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an array-like object,
 *  else `false`.
 * @example
 *
 * _.isArrayLikeObject([1, 2, 3]);
 * // => true
 *
 * _.isArrayLikeObject(document.body.children);
 * // => true
 *
 * _.isArrayLikeObject('abc');
 * // => false
 *
 * _.isArrayLikeObject(_.noop);
 * // => false
 */
function isArrayLikeObject(value) {
  return isObjectLike(value) && isArrayLike(value);
}

module.exports = isArrayLikeObject;


/***/ },

/***/ "./node_modules/lodash/isEqual.js"
/*!****************************************!*\
  !*** ./node_modules/lodash/isEqual.js ***!
  \****************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var baseIsEqual = __webpack_require__(/*! ./_baseIsEqual */ "./node_modules/lodash/_baseIsEqual.js");

/**
 * Performs a deep comparison between two values to determine if they are
 * equivalent.
 *
 * **Note:** This method supports comparing arrays, array buffers, booleans,
 * date objects, error objects, maps, numbers, `Object` objects, regexes,
 * sets, strings, symbols, and typed arrays. `Object` objects are compared
 * by their own, not inherited, enumerable properties. Functions and DOM
 * nodes are compared by strict equality, i.e. `===`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to compare.
 * @param {*} other The other value to compare.
 * @returns {boolean} Returns `true` if the values are equivalent, else `false`.
 * @example
 *
 * var object = { 'a': 1 };
 * var other = { 'a': 1 };
 *
 * _.isEqual(object, other);
 * // => true
 *
 * object === other;
 * // => false
 */
function isEqual(value, other) {
  return baseIsEqual(value, other);
}

module.exports = isEqual;


/***/ },

/***/ "./node_modules/lodash/merge.js"
/*!**************************************!*\
  !*** ./node_modules/lodash/merge.js ***!
  \**************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var baseMerge = __webpack_require__(/*! ./_baseMerge */ "./node_modules/lodash/_baseMerge.js"),
    createAssigner = __webpack_require__(/*! ./_createAssigner */ "./node_modules/lodash/_createAssigner.js");

/**
 * This method is like `_.assign` except that it recursively merges own and
 * inherited enumerable string keyed properties of source objects into the
 * destination object. Source properties that resolve to `undefined` are
 * skipped if a destination value exists. Array and plain object properties
 * are merged recursively. Other objects and value types are overridden by
 * assignment. Source objects are applied from left to right. Subsequent
 * sources overwrite property assignments of previous sources.
 *
 * **Note:** This method mutates `object`.
 *
 * @static
 * @memberOf _
 * @since 0.5.0
 * @category Object
 * @param {Object} object The destination object.
 * @param {...Object} [sources] The source objects.
 * @returns {Object} Returns `object`.
 * @example
 *
 * var object = {
 *   'a': [{ 'b': 2 }, { 'd': 4 }]
 * };
 *
 * var other = {
 *   'a': [{ 'c': 3 }, { 'e': 5 }]
 * };
 *
 * _.merge(object, other);
 * // => { 'a': [{ 'b': 2, 'c': 3 }, { 'd': 4, 'e': 5 }] }
 */
var merge = createAssigner(function(object, source, srcIndex) {
  baseMerge(object, source, srcIndex);
});

module.exports = merge;


/***/ },

/***/ "./node_modules/lodash/toPlainObject.js"
/*!**********************************************!*\
  !*** ./node_modules/lodash/toPlainObject.js ***!
  \**********************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var copyObject = __webpack_require__(/*! ./_copyObject */ "./node_modules/lodash/_copyObject.js"),
    keysIn = __webpack_require__(/*! ./keysIn */ "./node_modules/lodash/keysIn.js");

/**
 * Converts `value` to a plain object flattening inherited enumerable string
 * keyed properties of `value` to own properties of the plain object.
 *
 * @static
 * @memberOf _
 * @since 3.0.0
 * @category Lang
 * @param {*} value The value to convert.
 * @returns {Object} Returns the converted plain object.
 * @example
 *
 * function Foo() {
 *   this.b = 2;
 * }
 *
 * Foo.prototype.c = 3;
 *
 * _.assign({ 'a': 1 }, new Foo);
 * // => { 'a': 1, 'b': 2 }
 *
 * _.assign({ 'a': 1 }, _.toPlainObject(new Foo));
 * // => { 'a': 1, 'b': 2, 'c': 3 }
 */
function toPlainObject(value) {
  return copyObject(value, keysIn(value));
}

module.exports = toPlainObject;


/***/ },

/***/ "./node_modules/popper.js/dist/esm/popper.js"
/*!***************************************************!*\
  !*** ./node_modules/popper.js/dist/esm/popper.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**!
 * @fileOverview Kickass library to create and place poppers near their reference elements.
 * @version 1.16.1
 * @license
 * Copyright (c) 2016 Federico Zivolo and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
var isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined' && typeof navigator !== 'undefined';

var timeoutDuration = function () {
  var longerTimeoutBrowsers = ['Edge', 'Trident', 'Firefox'];
  for (var i = 0; i < longerTimeoutBrowsers.length; i += 1) {
    if (isBrowser && navigator.userAgent.indexOf(longerTimeoutBrowsers[i]) >= 0) {
      return 1;
    }
  }
  return 0;
}();

function microtaskDebounce(fn) {
  var called = false;
  return function () {
    if (called) {
      return;
    }
    called = true;
    window.Promise.resolve().then(function () {
      called = false;
      fn();
    });
  };
}

function taskDebounce(fn) {
  var scheduled = false;
  return function () {
    if (!scheduled) {
      scheduled = true;
      setTimeout(function () {
        scheduled = false;
        fn();
      }, timeoutDuration);
    }
  };
}

var supportsMicroTasks = isBrowser && window.Promise;

/**
* Create a debounced version of a method, that's asynchronously deferred
* but called in the minimum time possible.
*
* @method
* @memberof Popper.Utils
* @argument {Function} fn
* @returns {Function}
*/
var debounce = supportsMicroTasks ? microtaskDebounce : taskDebounce;

/**
 * Check if the given variable is a function
 * @method
 * @memberof Popper.Utils
 * @argument {Any} functionToCheck - variable to check
 * @returns {Boolean} answer to: is a function?
 */
function isFunction(functionToCheck) {
  var getType = {};
  return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
}

/**
 * Get CSS computed property of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Eement} element
 * @argument {String} property
 */
function getStyleComputedProperty(element, property) {
  if (element.nodeType !== 1) {
    return [];
  }
  // NOTE: 1 DOM access here
  var window = element.ownerDocument.defaultView;
  var css = window.getComputedStyle(element, null);
  return property ? css[property] : css;
}

/**
 * Returns the parentNode or the host of the element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} parent
 */
function getParentNode(element) {
  if (element.nodeName === 'HTML') {
    return element;
  }
  return element.parentNode || element.host;
}

/**
 * Returns the scrolling parent of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} scroll parent
 */
function getScrollParent(element) {
  // Return body, `getScroll` will take care to get the correct `scrollTop` from it
  if (!element) {
    return document.body;
  }

  switch (element.nodeName) {
    case 'HTML':
    case 'BODY':
      return element.ownerDocument.body;
    case '#document':
      return element.body;
  }

  // Firefox want us to check `-x` and `-y` variations as well

  var _getStyleComputedProp = getStyleComputedProperty(element),
      overflow = _getStyleComputedProp.overflow,
      overflowX = _getStyleComputedProp.overflowX,
      overflowY = _getStyleComputedProp.overflowY;

  if (/(auto|scroll|overlay)/.test(overflow + overflowY + overflowX)) {
    return element;
  }

  return getScrollParent(getParentNode(element));
}

/**
 * Returns the reference node of the reference object, or the reference object itself.
 * @method
 * @memberof Popper.Utils
 * @param {Element|Object} reference - the reference element (the popper will be relative to this)
 * @returns {Element} parent
 */
function getReferenceNode(reference) {
  return reference && reference.referenceNode ? reference.referenceNode : reference;
}

var isIE11 = isBrowser && !!(window.MSInputMethodContext && document.documentMode);
var isIE10 = isBrowser && /MSIE 10/.test(navigator.userAgent);

/**
 * Determines if the browser is Internet Explorer
 * @method
 * @memberof Popper.Utils
 * @param {Number} version to check
 * @returns {Boolean} isIE
 */
function isIE(version) {
  if (version === 11) {
    return isIE11;
  }
  if (version === 10) {
    return isIE10;
  }
  return isIE11 || isIE10;
}

/**
 * Returns the offset parent of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} offset parent
 */
function getOffsetParent(element) {
  if (!element) {
    return document.documentElement;
  }

  var noOffsetParent = isIE(10) ? document.body : null;

  // NOTE: 1 DOM access here
  var offsetParent = element.offsetParent || null;
  // Skip hidden elements which don't have an offsetParent
  while (offsetParent === noOffsetParent && element.nextElementSibling) {
    offsetParent = (element = element.nextElementSibling).offsetParent;
  }

  var nodeName = offsetParent && offsetParent.nodeName;

  if (!nodeName || nodeName === 'BODY' || nodeName === 'HTML') {
    return element ? element.ownerDocument.documentElement : document.documentElement;
  }

  // .offsetParent will return the closest TH, TD or TABLE in case
  // no offsetParent is present, I hate this job...
  if (['TH', 'TD', 'TABLE'].indexOf(offsetParent.nodeName) !== -1 && getStyleComputedProperty(offsetParent, 'position') === 'static') {
    return getOffsetParent(offsetParent);
  }

  return offsetParent;
}

function isOffsetContainer(element) {
  var nodeName = element.nodeName;

  if (nodeName === 'BODY') {
    return false;
  }
  return nodeName === 'HTML' || getOffsetParent(element.firstElementChild) === element;
}

/**
 * Finds the root node (document, shadowDOM root) of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} node
 * @returns {Element} root node
 */
function getRoot(node) {
  if (node.parentNode !== null) {
    return getRoot(node.parentNode);
  }

  return node;
}

/**
 * Finds the offset parent common to the two provided nodes
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element1
 * @argument {Element} element2
 * @returns {Element} common offset parent
 */
function findCommonOffsetParent(element1, element2) {
  // This check is needed to avoid errors in case one of the elements isn't defined for any reason
  if (!element1 || !element1.nodeType || !element2 || !element2.nodeType) {
    return document.documentElement;
  }

  // Here we make sure to give as "start" the element that comes first in the DOM
  var order = element1.compareDocumentPosition(element2) & Node.DOCUMENT_POSITION_FOLLOWING;
  var start = order ? element1 : element2;
  var end = order ? element2 : element1;

  // Get common ancestor container
  var range = document.createRange();
  range.setStart(start, 0);
  range.setEnd(end, 0);
  var commonAncestorContainer = range.commonAncestorContainer;

  // Both nodes are inside #document

  if (element1 !== commonAncestorContainer && element2 !== commonAncestorContainer || start.contains(end)) {
    if (isOffsetContainer(commonAncestorContainer)) {
      return commonAncestorContainer;
    }

    return getOffsetParent(commonAncestorContainer);
  }

  // one of the nodes is inside shadowDOM, find which one
  var element1root = getRoot(element1);
  if (element1root.host) {
    return findCommonOffsetParent(element1root.host, element2);
  } else {
    return findCommonOffsetParent(element1, getRoot(element2).host);
  }
}

/**
 * Gets the scroll value of the given element in the given side (top and left)
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @argument {String} side `top` or `left`
 * @returns {number} amount of scrolled pixels
 */
function getScroll(element) {
  var side = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'top';

  var upperSide = side === 'top' ? 'scrollTop' : 'scrollLeft';
  var nodeName = element.nodeName;

  if (nodeName === 'BODY' || nodeName === 'HTML') {
    var html = element.ownerDocument.documentElement;
    var scrollingElement = element.ownerDocument.scrollingElement || html;
    return scrollingElement[upperSide];
  }

  return element[upperSide];
}

/*
 * Sum or subtract the element scroll values (left and top) from a given rect object
 * @method
 * @memberof Popper.Utils
 * @param {Object} rect - Rect object you want to change
 * @param {HTMLElement} element - The element from the function reads the scroll values
 * @param {Boolean} subtract - set to true if you want to subtract the scroll values
 * @return {Object} rect - The modifier rect object
 */
function includeScroll(rect, element) {
  var subtract = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

  var scrollTop = getScroll(element, 'top');
  var scrollLeft = getScroll(element, 'left');
  var modifier = subtract ? -1 : 1;
  rect.top += scrollTop * modifier;
  rect.bottom += scrollTop * modifier;
  rect.left += scrollLeft * modifier;
  rect.right += scrollLeft * modifier;
  return rect;
}

/*
 * Helper to detect borders of a given element
 * @method
 * @memberof Popper.Utils
 * @param {CSSStyleDeclaration} styles
 * Result of `getStyleComputedProperty` on the given element
 * @param {String} axis - `x` or `y`
 * @return {number} borders - The borders size of the given axis
 */

function getBordersSize(styles, axis) {
  var sideA = axis === 'x' ? 'Left' : 'Top';
  var sideB = sideA === 'Left' ? 'Right' : 'Bottom';

  return parseFloat(styles['border' + sideA + 'Width']) + parseFloat(styles['border' + sideB + 'Width']);
}

function getSize(axis, body, html, computedStyle) {
  return Math.max(body['offset' + axis], body['scroll' + axis], html['client' + axis], html['offset' + axis], html['scroll' + axis], isIE(10) ? parseInt(html['offset' + axis]) + parseInt(computedStyle['margin' + (axis === 'Height' ? 'Top' : 'Left')]) + parseInt(computedStyle['margin' + (axis === 'Height' ? 'Bottom' : 'Right')]) : 0);
}

function getWindowSizes(document) {
  var body = document.body;
  var html = document.documentElement;
  var computedStyle = isIE(10) && getComputedStyle(html);

  return {
    height: getSize('Height', body, html, computedStyle),
    width: getSize('Width', body, html, computedStyle)
  };
}

var classCallCheck = function (instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
};

var createClass = function () {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  return function (Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();





var defineProperty = function (obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
};

var _extends = Object.assign || function (target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i];

    for (var key in source) {
      if (Object.prototype.hasOwnProperty.call(source, key)) {
        target[key] = source[key];
      }
    }
  }

  return target;
};

/**
 * Given element offsets, generate an output similar to getBoundingClientRect
 * @method
 * @memberof Popper.Utils
 * @argument {Object} offsets
 * @returns {Object} ClientRect like output
 */
function getClientRect(offsets) {
  return _extends({}, offsets, {
    right: offsets.left + offsets.width,
    bottom: offsets.top + offsets.height
  });
}

/**
 * Get bounding client rect of given element
 * @method
 * @memberof Popper.Utils
 * @param {HTMLElement} element
 * @return {Object} client rect
 */
function getBoundingClientRect(element) {
  var rect = {};

  // IE10 10 FIX: Please, don't ask, the element isn't
  // considered in DOM in some circumstances...
  // This isn't reproducible in IE10 compatibility mode of IE11
  try {
    if (isIE(10)) {
      rect = element.getBoundingClientRect();
      var scrollTop = getScroll(element, 'top');
      var scrollLeft = getScroll(element, 'left');
      rect.top += scrollTop;
      rect.left += scrollLeft;
      rect.bottom += scrollTop;
      rect.right += scrollLeft;
    } else {
      rect = element.getBoundingClientRect();
    }
  } catch (e) {}

  var result = {
    left: rect.left,
    top: rect.top,
    width: rect.right - rect.left,
    height: rect.bottom - rect.top
  };

  // subtract scrollbar size from sizes
  var sizes = element.nodeName === 'HTML' ? getWindowSizes(element.ownerDocument) : {};
  var width = sizes.width || element.clientWidth || result.width;
  var height = sizes.height || element.clientHeight || result.height;

  var horizScrollbar = element.offsetWidth - width;
  var vertScrollbar = element.offsetHeight - height;

  // if an hypothetical scrollbar is detected, we must be sure it's not a `border`
  // we make this check conditional for performance reasons
  if (horizScrollbar || vertScrollbar) {
    var styles = getStyleComputedProperty(element);
    horizScrollbar -= getBordersSize(styles, 'x');
    vertScrollbar -= getBordersSize(styles, 'y');

    result.width -= horizScrollbar;
    result.height -= vertScrollbar;
  }

  return getClientRect(result);
}

function getOffsetRectRelativeToArbitraryNode(children, parent) {
  var fixedPosition = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

  var isIE10 = isIE(10);
  var isHTML = parent.nodeName === 'HTML';
  var childrenRect = getBoundingClientRect(children);
  var parentRect = getBoundingClientRect(parent);
  var scrollParent = getScrollParent(children);

  var styles = getStyleComputedProperty(parent);
  var borderTopWidth = parseFloat(styles.borderTopWidth);
  var borderLeftWidth = parseFloat(styles.borderLeftWidth);

  // In cases where the parent is fixed, we must ignore negative scroll in offset calc
  if (fixedPosition && isHTML) {
    parentRect.top = Math.max(parentRect.top, 0);
    parentRect.left = Math.max(parentRect.left, 0);
  }
  var offsets = getClientRect({
    top: childrenRect.top - parentRect.top - borderTopWidth,
    left: childrenRect.left - parentRect.left - borderLeftWidth,
    width: childrenRect.width,
    height: childrenRect.height
  });
  offsets.marginTop = 0;
  offsets.marginLeft = 0;

  // Subtract margins of documentElement in case it's being used as parent
  // we do this only on HTML because it's the only element that behaves
  // differently when margins are applied to it. The margins are included in
  // the box of the documentElement, in the other cases not.
  if (!isIE10 && isHTML) {
    var marginTop = parseFloat(styles.marginTop);
    var marginLeft = parseFloat(styles.marginLeft);

    offsets.top -= borderTopWidth - marginTop;
    offsets.bottom -= borderTopWidth - marginTop;
    offsets.left -= borderLeftWidth - marginLeft;
    offsets.right -= borderLeftWidth - marginLeft;

    // Attach marginTop and marginLeft because in some circumstances we may need them
    offsets.marginTop = marginTop;
    offsets.marginLeft = marginLeft;
  }

  if (isIE10 && !fixedPosition ? parent.contains(scrollParent) : parent === scrollParent && scrollParent.nodeName !== 'BODY') {
    offsets = includeScroll(offsets, parent);
  }

  return offsets;
}

function getViewportOffsetRectRelativeToArtbitraryNode(element) {
  var excludeScroll = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  var html = element.ownerDocument.documentElement;
  var relativeOffset = getOffsetRectRelativeToArbitraryNode(element, html);
  var width = Math.max(html.clientWidth, window.innerWidth || 0);
  var height = Math.max(html.clientHeight, window.innerHeight || 0);

  var scrollTop = !excludeScroll ? getScroll(html) : 0;
  var scrollLeft = !excludeScroll ? getScroll(html, 'left') : 0;

  var offset = {
    top: scrollTop - relativeOffset.top + relativeOffset.marginTop,
    left: scrollLeft - relativeOffset.left + relativeOffset.marginLeft,
    width: width,
    height: height
  };

  return getClientRect(offset);
}

/**
 * Check if the given element is fixed or is inside a fixed parent
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @argument {Element} customContainer
 * @returns {Boolean} answer to "isFixed?"
 */
function isFixed(element) {
  var nodeName = element.nodeName;
  if (nodeName === 'BODY' || nodeName === 'HTML') {
    return false;
  }
  if (getStyleComputedProperty(element, 'position') === 'fixed') {
    return true;
  }
  var parentNode = getParentNode(element);
  if (!parentNode) {
    return false;
  }
  return isFixed(parentNode);
}

/**
 * Finds the first parent of an element that has a transformed property defined
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} first transformed parent or documentElement
 */

function getFixedPositionOffsetParent(element) {
  // This check is needed to avoid errors in case one of the elements isn't defined for any reason
  if (!element || !element.parentElement || isIE()) {
    return document.documentElement;
  }
  var el = element.parentElement;
  while (el && getStyleComputedProperty(el, 'transform') === 'none') {
    el = el.parentElement;
  }
  return el || document.documentElement;
}

/**
 * Computed the boundaries limits and return them
 * @method
 * @memberof Popper.Utils
 * @param {HTMLElement} popper
 * @param {HTMLElement} reference
 * @param {number} padding
 * @param {HTMLElement} boundariesElement - Element used to define the boundaries
 * @param {Boolean} fixedPosition - Is in fixed position mode
 * @returns {Object} Coordinates of the boundaries
 */
function getBoundaries(popper, reference, padding, boundariesElement) {
  var fixedPosition = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : false;

  // NOTE: 1 DOM access here

  var boundaries = { top: 0, left: 0 };
  var offsetParent = fixedPosition ? getFixedPositionOffsetParent(popper) : findCommonOffsetParent(popper, getReferenceNode(reference));

  // Handle viewport case
  if (boundariesElement === 'viewport') {
    boundaries = getViewportOffsetRectRelativeToArtbitraryNode(offsetParent, fixedPosition);
  } else {
    // Handle other cases based on DOM element used as boundaries
    var boundariesNode = void 0;
    if (boundariesElement === 'scrollParent') {
      boundariesNode = getScrollParent(getParentNode(reference));
      if (boundariesNode.nodeName === 'BODY') {
        boundariesNode = popper.ownerDocument.documentElement;
      }
    } else if (boundariesElement === 'window') {
      boundariesNode = popper.ownerDocument.documentElement;
    } else {
      boundariesNode = boundariesElement;
    }

    var offsets = getOffsetRectRelativeToArbitraryNode(boundariesNode, offsetParent, fixedPosition);

    // In case of HTML, we need a different computation
    if (boundariesNode.nodeName === 'HTML' && !isFixed(offsetParent)) {
      var _getWindowSizes = getWindowSizes(popper.ownerDocument),
          height = _getWindowSizes.height,
          width = _getWindowSizes.width;

      boundaries.top += offsets.top - offsets.marginTop;
      boundaries.bottom = height + offsets.top;
      boundaries.left += offsets.left - offsets.marginLeft;
      boundaries.right = width + offsets.left;
    } else {
      // for all the other DOM elements, this one is good
      boundaries = offsets;
    }
  }

  // Add paddings
  padding = padding || 0;
  var isPaddingNumber = typeof padding === 'number';
  boundaries.left += isPaddingNumber ? padding : padding.left || 0;
  boundaries.top += isPaddingNumber ? padding : padding.top || 0;
  boundaries.right -= isPaddingNumber ? padding : padding.right || 0;
  boundaries.bottom -= isPaddingNumber ? padding : padding.bottom || 0;

  return boundaries;
}

function getArea(_ref) {
  var width = _ref.width,
      height = _ref.height;

  return width * height;
}

/**
 * Utility used to transform the `auto` placement to the placement with more
 * available space.
 * @method
 * @memberof Popper.Utils
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function computeAutoPlacement(placement, refRect, popper, reference, boundariesElement) {
  var padding = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : 0;

  if (placement.indexOf('auto') === -1) {
    return placement;
  }

  var boundaries = getBoundaries(popper, reference, padding, boundariesElement);

  var rects = {
    top: {
      width: boundaries.width,
      height: refRect.top - boundaries.top
    },
    right: {
      width: boundaries.right - refRect.right,
      height: boundaries.height
    },
    bottom: {
      width: boundaries.width,
      height: boundaries.bottom - refRect.bottom
    },
    left: {
      width: refRect.left - boundaries.left,
      height: boundaries.height
    }
  };

  var sortedAreas = Object.keys(rects).map(function (key) {
    return _extends({
      key: key
    }, rects[key], {
      area: getArea(rects[key])
    });
  }).sort(function (a, b) {
    return b.area - a.area;
  });

  var filteredAreas = sortedAreas.filter(function (_ref2) {
    var width = _ref2.width,
        height = _ref2.height;
    return width >= popper.clientWidth && height >= popper.clientHeight;
  });

  var computedPlacement = filteredAreas.length > 0 ? filteredAreas[0].key : sortedAreas[0].key;

  var variation = placement.split('-')[1];

  return computedPlacement + (variation ? '-' + variation : '');
}

/**
 * Get offsets to the reference element
 * @method
 * @memberof Popper.Utils
 * @param {Object} state
 * @param {Element} popper - the popper element
 * @param {Element} reference - the reference element (the popper will be relative to this)
 * @param {Element} fixedPosition - is in fixed position mode
 * @returns {Object} An object containing the offsets which will be applied to the popper
 */
function getReferenceOffsets(state, popper, reference) {
  var fixedPosition = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;

  var commonOffsetParent = fixedPosition ? getFixedPositionOffsetParent(popper) : findCommonOffsetParent(popper, getReferenceNode(reference));
  return getOffsetRectRelativeToArbitraryNode(reference, commonOffsetParent, fixedPosition);
}

/**
 * Get the outer sizes of the given element (offset size + margins)
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Object} object containing width and height properties
 */
function getOuterSizes(element) {
  var window = element.ownerDocument.defaultView;
  var styles = window.getComputedStyle(element);
  var x = parseFloat(styles.marginTop || 0) + parseFloat(styles.marginBottom || 0);
  var y = parseFloat(styles.marginLeft || 0) + parseFloat(styles.marginRight || 0);
  var result = {
    width: element.offsetWidth + y,
    height: element.offsetHeight + x
  };
  return result;
}

/**
 * Get the opposite placement of the given one
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement
 * @returns {String} flipped placement
 */
function getOppositePlacement(placement) {
  var hash = { left: 'right', right: 'left', bottom: 'top', top: 'bottom' };
  return placement.replace(/left|right|bottom|top/g, function (matched) {
    return hash[matched];
  });
}

/**
 * Get offsets to the popper
 * @method
 * @memberof Popper.Utils
 * @param {Object} position - CSS position the Popper will get applied
 * @param {HTMLElement} popper - the popper element
 * @param {Object} referenceOffsets - the reference offsets (the popper will be relative to this)
 * @param {String} placement - one of the valid placement options
 * @returns {Object} popperOffsets - An object containing the offsets which will be applied to the popper
 */
function getPopperOffsets(popper, referenceOffsets, placement) {
  placement = placement.split('-')[0];

  // Get popper node sizes
  var popperRect = getOuterSizes(popper);

  // Add position, width and height to our offsets object
  var popperOffsets = {
    width: popperRect.width,
    height: popperRect.height
  };

  // depending by the popper placement we have to compute its offsets slightly differently
  var isHoriz = ['right', 'left'].indexOf(placement) !== -1;
  var mainSide = isHoriz ? 'top' : 'left';
  var secondarySide = isHoriz ? 'left' : 'top';
  var measurement = isHoriz ? 'height' : 'width';
  var secondaryMeasurement = !isHoriz ? 'height' : 'width';

  popperOffsets[mainSide] = referenceOffsets[mainSide] + referenceOffsets[measurement] / 2 - popperRect[measurement] / 2;
  if (placement === secondarySide) {
    popperOffsets[secondarySide] = referenceOffsets[secondarySide] - popperRect[secondaryMeasurement];
  } else {
    popperOffsets[secondarySide] = referenceOffsets[getOppositePlacement(secondarySide)];
  }

  return popperOffsets;
}

/**
 * Mimics the `find` method of Array
 * @method
 * @memberof Popper.Utils
 * @argument {Array} arr
 * @argument prop
 * @argument value
 * @returns index or -1
 */
function find(arr, check) {
  // use native find if supported
  if (Array.prototype.find) {
    return arr.find(check);
  }

  // use `filter` to obtain the same behavior of `find`
  return arr.filter(check)[0];
}

/**
 * Return the index of the matching object
 * @method
 * @memberof Popper.Utils
 * @argument {Array} arr
 * @argument prop
 * @argument value
 * @returns index or -1
 */
function findIndex(arr, prop, value) {
  // use native findIndex if supported
  if (Array.prototype.findIndex) {
    return arr.findIndex(function (cur) {
      return cur[prop] === value;
    });
  }

  // use `find` + `indexOf` if `findIndex` isn't supported
  var match = find(arr, function (obj) {
    return obj[prop] === value;
  });
  return arr.indexOf(match);
}

/**
 * Loop trough the list of modifiers and run them in order,
 * each of them will then edit the data object.
 * @method
 * @memberof Popper.Utils
 * @param {dataObject} data
 * @param {Array} modifiers
 * @param {String} ends - Optional modifier name used as stopper
 * @returns {dataObject}
 */
function runModifiers(modifiers, data, ends) {
  var modifiersToRun = ends === undefined ? modifiers : modifiers.slice(0, findIndex(modifiers, 'name', ends));

  modifiersToRun.forEach(function (modifier) {
    if (modifier['function']) {
      // eslint-disable-line dot-notation
      console.warn('`modifier.function` is deprecated, use `modifier.fn`!');
    }
    var fn = modifier['function'] || modifier.fn; // eslint-disable-line dot-notation
    if (modifier.enabled && isFunction(fn)) {
      // Add properties to offsets to make them a complete clientRect object
      // we do this before each modifier to make sure the previous one doesn't
      // mess with these values
      data.offsets.popper = getClientRect(data.offsets.popper);
      data.offsets.reference = getClientRect(data.offsets.reference);

      data = fn(data, modifier);
    }
  });

  return data;
}

/**
 * Updates the position of the popper, computing the new offsets and applying
 * the new style.<br />
 * Prefer `scheduleUpdate` over `update` because of performance reasons.
 * @method
 * @memberof Popper
 */
function update() {
  // if popper is destroyed, don't perform any further update
  if (this.state.isDestroyed) {
    return;
  }

  var data = {
    instance: this,
    styles: {},
    arrowStyles: {},
    attributes: {},
    flipped: false,
    offsets: {}
  };

  // compute reference element offsets
  data.offsets.reference = getReferenceOffsets(this.state, this.popper, this.reference, this.options.positionFixed);

  // compute auto placement, store placement inside the data object,
  // modifiers will be able to edit `placement` if needed
  // and refer to originalPlacement to know the original value
  data.placement = computeAutoPlacement(this.options.placement, data.offsets.reference, this.popper, this.reference, this.options.modifiers.flip.boundariesElement, this.options.modifiers.flip.padding);

  // store the computed placement inside `originalPlacement`
  data.originalPlacement = data.placement;

  data.positionFixed = this.options.positionFixed;

  // compute the popper offsets
  data.offsets.popper = getPopperOffsets(this.popper, data.offsets.reference, data.placement);

  data.offsets.popper.position = this.options.positionFixed ? 'fixed' : 'absolute';

  // run the modifiers
  data = runModifiers(this.modifiers, data);

  // the first `update` will call `onCreate` callback
  // the other ones will call `onUpdate` callback
  if (!this.state.isCreated) {
    this.state.isCreated = true;
    this.options.onCreate(data);
  } else {
    this.options.onUpdate(data);
  }
}

/**
 * Helper used to know if the given modifier is enabled.
 * @method
 * @memberof Popper.Utils
 * @returns {Boolean}
 */
function isModifierEnabled(modifiers, modifierName) {
  return modifiers.some(function (_ref) {
    var name = _ref.name,
        enabled = _ref.enabled;
    return enabled && name === modifierName;
  });
}

/**
 * Get the prefixed supported property name
 * @method
 * @memberof Popper.Utils
 * @argument {String} property (camelCase)
 * @returns {String} prefixed property (camelCase or PascalCase, depending on the vendor prefix)
 */
function getSupportedPropertyName(property) {
  var prefixes = [false, 'ms', 'Webkit', 'Moz', 'O'];
  var upperProp = property.charAt(0).toUpperCase() + property.slice(1);

  for (var i = 0; i < prefixes.length; i++) {
    var prefix = prefixes[i];
    var toCheck = prefix ? '' + prefix + upperProp : property;
    if (typeof document.body.style[toCheck] !== 'undefined') {
      return toCheck;
    }
  }
  return null;
}

/**
 * Destroys the popper.
 * @method
 * @memberof Popper
 */
function destroy() {
  this.state.isDestroyed = true;

  // touch DOM only if `applyStyle` modifier is enabled
  if (isModifierEnabled(this.modifiers, 'applyStyle')) {
    this.popper.removeAttribute('x-placement');
    this.popper.style.position = '';
    this.popper.style.top = '';
    this.popper.style.left = '';
    this.popper.style.right = '';
    this.popper.style.bottom = '';
    this.popper.style.willChange = '';
    this.popper.style[getSupportedPropertyName('transform')] = '';
  }

  this.disableEventListeners();

  // remove the popper if user explicitly asked for the deletion on destroy
  // do not use `remove` because IE11 doesn't support it
  if (this.options.removeOnDestroy) {
    this.popper.parentNode.removeChild(this.popper);
  }
  return this;
}

/**
 * Get the window associated with the element
 * @argument {Element} element
 * @returns {Window}
 */
function getWindow(element) {
  var ownerDocument = element.ownerDocument;
  return ownerDocument ? ownerDocument.defaultView : window;
}

function attachToScrollParents(scrollParent, event, callback, scrollParents) {
  var isBody = scrollParent.nodeName === 'BODY';
  var target = isBody ? scrollParent.ownerDocument.defaultView : scrollParent;
  target.addEventListener(event, callback, { passive: true });

  if (!isBody) {
    attachToScrollParents(getScrollParent(target.parentNode), event, callback, scrollParents);
  }
  scrollParents.push(target);
}

/**
 * Setup needed event listeners used to update the popper position
 * @method
 * @memberof Popper.Utils
 * @private
 */
function setupEventListeners(reference, options, state, updateBound) {
  // Resize event listener on window
  state.updateBound = updateBound;
  getWindow(reference).addEventListener('resize', state.updateBound, { passive: true });

  // Scroll event listener on scroll parents
  var scrollElement = getScrollParent(reference);
  attachToScrollParents(scrollElement, 'scroll', state.updateBound, state.scrollParents);
  state.scrollElement = scrollElement;
  state.eventsEnabled = true;

  return state;
}

/**
 * It will add resize/scroll events and start recalculating
 * position of the popper element when they are triggered.
 * @method
 * @memberof Popper
 */
function enableEventListeners() {
  if (!this.state.eventsEnabled) {
    this.state = setupEventListeners(this.reference, this.options, this.state, this.scheduleUpdate);
  }
}

/**
 * Remove event listeners used to update the popper position
 * @method
 * @memberof Popper.Utils
 * @private
 */
function removeEventListeners(reference, state) {
  // Remove resize event listener on window
  getWindow(reference).removeEventListener('resize', state.updateBound);

  // Remove scroll event listener on scroll parents
  state.scrollParents.forEach(function (target) {
    target.removeEventListener('scroll', state.updateBound);
  });

  // Reset state
  state.updateBound = null;
  state.scrollParents = [];
  state.scrollElement = null;
  state.eventsEnabled = false;
  return state;
}

/**
 * It will remove resize/scroll events and won't recalculate popper position
 * when they are triggered. It also won't trigger `onUpdate` callback anymore,
 * unless you call `update` method manually.
 * @method
 * @memberof Popper
 */
function disableEventListeners() {
  if (this.state.eventsEnabled) {
    cancelAnimationFrame(this.scheduleUpdate);
    this.state = removeEventListeners(this.reference, this.state);
  }
}

/**
 * Tells if a given input is a number
 * @method
 * @memberof Popper.Utils
 * @param {*} input to check
 * @return {Boolean}
 */
function isNumeric(n) {
  return n !== '' && !isNaN(parseFloat(n)) && isFinite(n);
}

/**
 * Set the style to the given popper
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element - Element to apply the style to
 * @argument {Object} styles
 * Object with a list of properties and values which will be applied to the element
 */
function setStyles(element, styles) {
  Object.keys(styles).forEach(function (prop) {
    var unit = '';
    // add unit if the value is numeric and is one of the following
    if (['width', 'height', 'top', 'right', 'bottom', 'left'].indexOf(prop) !== -1 && isNumeric(styles[prop])) {
      unit = 'px';
    }
    element.style[prop] = styles[prop] + unit;
  });
}

/**
 * Set the attributes to the given popper
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element - Element to apply the attributes to
 * @argument {Object} styles
 * Object with a list of properties and values which will be applied to the element
 */
function setAttributes(element, attributes) {
  Object.keys(attributes).forEach(function (prop) {
    var value = attributes[prop];
    if (value !== false) {
      element.setAttribute(prop, attributes[prop]);
    } else {
      element.removeAttribute(prop);
    }
  });
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} data.styles - List of style properties - values to apply to popper element
 * @argument {Object} data.attributes - List of attribute properties - values to apply to popper element
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The same data object
 */
function applyStyle(data) {
  // any property present in `data.styles` will be applied to the popper,
  // in this way we can make the 3rd party modifiers add custom styles to it
  // Be aware, modifiers could override the properties defined in the previous
  // lines of this modifier!
  setStyles(data.instance.popper, data.styles);

  // any property present in `data.attributes` will be applied to the popper,
  // they will be set as HTML attributes of the element
  setAttributes(data.instance.popper, data.attributes);

  // if arrowElement is defined and arrowStyles has some properties
  if (data.arrowElement && Object.keys(data.arrowStyles).length) {
    setStyles(data.arrowElement, data.arrowStyles);
  }

  return data;
}

/**
 * Set the x-placement attribute before everything else because it could be used
 * to add margins to the popper margins needs to be calculated to get the
 * correct popper offsets.
 * @method
 * @memberof Popper.modifiers
 * @param {HTMLElement} reference - The reference element used to position the popper
 * @param {HTMLElement} popper - The HTML element used as popper
 * @param {Object} options - Popper.js options
 */
function applyStyleOnLoad(reference, popper, options, modifierOptions, state) {
  // compute reference element offsets
  var referenceOffsets = getReferenceOffsets(state, popper, reference, options.positionFixed);

  // compute auto placement, store placement inside the data object,
  // modifiers will be able to edit `placement` if needed
  // and refer to originalPlacement to know the original value
  var placement = computeAutoPlacement(options.placement, referenceOffsets, popper, reference, options.modifiers.flip.boundariesElement, options.modifiers.flip.padding);

  popper.setAttribute('x-placement', placement);

  // Apply `position` to popper before anything else because
  // without the position applied we can't guarantee correct computations
  setStyles(popper, { position: options.positionFixed ? 'fixed' : 'absolute' });

  return options;
}

/**
 * @function
 * @memberof Popper.Utils
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Boolean} shouldRound - If the offsets should be rounded at all
 * @returns {Object} The popper's position offsets rounded
 *
 * The tale of pixel-perfect positioning. It's still not 100% perfect, but as
 * good as it can be within reason.
 * Discussion here: https://github.com/FezVrasta/popper.js/pull/715
 *
 * Low DPI screens cause a popper to be blurry if not using full pixels (Safari
 * as well on High DPI screens).
 *
 * Firefox prefers no rounding for positioning and does not have blurriness on
 * high DPI screens.
 *
 * Only horizontal placement and left/right values need to be considered.
 */
function getRoundedOffsets(data, shouldRound) {
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;
  var round = Math.round,
      floor = Math.floor;

  var noRound = function noRound(v) {
    return v;
  };

  var referenceWidth = round(reference.width);
  var popperWidth = round(popper.width);

  var isVertical = ['left', 'right'].indexOf(data.placement) !== -1;
  var isVariation = data.placement.indexOf('-') !== -1;
  var sameWidthParity = referenceWidth % 2 === popperWidth % 2;
  var bothOddWidth = referenceWidth % 2 === 1 && popperWidth % 2 === 1;

  var horizontalToInteger = !shouldRound ? noRound : isVertical || isVariation || sameWidthParity ? round : floor;
  var verticalToInteger = !shouldRound ? noRound : round;

  return {
    left: horizontalToInteger(bothOddWidth && !isVariation && shouldRound ? popper.left - 1 : popper.left),
    top: verticalToInteger(popper.top),
    bottom: verticalToInteger(popper.bottom),
    right: horizontalToInteger(popper.right)
  };
}

var isFirefox = isBrowser && /Firefox/i.test(navigator.userAgent);

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function computeStyle(data, options) {
  var x = options.x,
      y = options.y;
  var popper = data.offsets.popper;

  // Remove this legacy support in Popper.js v2

  var legacyGpuAccelerationOption = find(data.instance.modifiers, function (modifier) {
    return modifier.name === 'applyStyle';
  }).gpuAcceleration;
  if (legacyGpuAccelerationOption !== undefined) {
    console.warn('WARNING: `gpuAcceleration` option moved to `computeStyle` modifier and will not be supported in future versions of Popper.js!');
  }
  var gpuAcceleration = legacyGpuAccelerationOption !== undefined ? legacyGpuAccelerationOption : options.gpuAcceleration;

  var offsetParent = getOffsetParent(data.instance.popper);
  var offsetParentRect = getBoundingClientRect(offsetParent);

  // Styles
  var styles = {
    position: popper.position
  };

  var offsets = getRoundedOffsets(data, window.devicePixelRatio < 2 || !isFirefox);

  var sideA = x === 'bottom' ? 'top' : 'bottom';
  var sideB = y === 'right' ? 'left' : 'right';

  // if gpuAcceleration is set to `true` and transform is supported,
  //  we use `translate3d` to apply the position to the popper we
  // automatically use the supported prefixed version if needed
  var prefixedProperty = getSupportedPropertyName('transform');

  // now, let's make a step back and look at this code closely (wtf?)
  // If the content of the popper grows once it's been positioned, it
  // may happen that the popper gets misplaced because of the new content
  // overflowing its reference element
  // To avoid this problem, we provide two options (x and y), which allow
  // the consumer to define the offset origin.
  // If we position a popper on top of a reference element, we can set
  // `x` to `top` to make the popper grow towards its top instead of
  // its bottom.
  var left = void 0,
      top = void 0;
  if (sideA === 'bottom') {
    // when offsetParent is <html> the positioning is relative to the bottom of the screen (excluding the scrollbar)
    // and not the bottom of the html element
    if (offsetParent.nodeName === 'HTML') {
      top = -offsetParent.clientHeight + offsets.bottom;
    } else {
      top = -offsetParentRect.height + offsets.bottom;
    }
  } else {
    top = offsets.top;
  }
  if (sideB === 'right') {
    if (offsetParent.nodeName === 'HTML') {
      left = -offsetParent.clientWidth + offsets.right;
    } else {
      left = -offsetParentRect.width + offsets.right;
    }
  } else {
    left = offsets.left;
  }
  if (gpuAcceleration && prefixedProperty) {
    styles[prefixedProperty] = 'translate3d(' + left + 'px, ' + top + 'px, 0)';
    styles[sideA] = 0;
    styles[sideB] = 0;
    styles.willChange = 'transform';
  } else {
    // othwerise, we use the standard `top`, `left`, `bottom` and `right` properties
    var invertTop = sideA === 'bottom' ? -1 : 1;
    var invertLeft = sideB === 'right' ? -1 : 1;
    styles[sideA] = top * invertTop;
    styles[sideB] = left * invertLeft;
    styles.willChange = sideA + ', ' + sideB;
  }

  // Attributes
  var attributes = {
    'x-placement': data.placement
  };

  // Update `data` attributes, styles and arrowStyles
  data.attributes = _extends({}, attributes, data.attributes);
  data.styles = _extends({}, styles, data.styles);
  data.arrowStyles = _extends({}, data.offsets.arrow, data.arrowStyles);

  return data;
}

/**
 * Helper used to know if the given modifier depends from another one.<br />
 * It checks if the needed modifier is listed and enabled.
 * @method
 * @memberof Popper.Utils
 * @param {Array} modifiers - list of modifiers
 * @param {String} requestingName - name of requesting modifier
 * @param {String} requestedName - name of requested modifier
 * @returns {Boolean}
 */
function isModifierRequired(modifiers, requestingName, requestedName) {
  var requesting = find(modifiers, function (_ref) {
    var name = _ref.name;
    return name === requestingName;
  });

  var isRequired = !!requesting && modifiers.some(function (modifier) {
    return modifier.name === requestedName && modifier.enabled && modifier.order < requesting.order;
  });

  if (!isRequired) {
    var _requesting = '`' + requestingName + '`';
    var requested = '`' + requestedName + '`';
    console.warn(requested + ' modifier is required by ' + _requesting + ' modifier in order to work, be sure to include it before ' + _requesting + '!');
  }
  return isRequired;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function arrow(data, options) {
  var _data$offsets$arrow;

  // arrow depends on keepTogether in order to work
  if (!isModifierRequired(data.instance.modifiers, 'arrow', 'keepTogether')) {
    return data;
  }

  var arrowElement = options.element;

  // if arrowElement is a string, suppose it's a CSS selector
  if (typeof arrowElement === 'string') {
    arrowElement = data.instance.popper.querySelector(arrowElement);

    // if arrowElement is not found, don't run the modifier
    if (!arrowElement) {
      return data;
    }
  } else {
    // if the arrowElement isn't a query selector we must check that the
    // provided DOM node is child of its popper node
    if (!data.instance.popper.contains(arrowElement)) {
      console.warn('WARNING: `arrow.element` must be child of its popper element!');
      return data;
    }
  }

  var placement = data.placement.split('-')[0];
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var isVertical = ['left', 'right'].indexOf(placement) !== -1;

  var len = isVertical ? 'height' : 'width';
  var sideCapitalized = isVertical ? 'Top' : 'Left';
  var side = sideCapitalized.toLowerCase();
  var altSide = isVertical ? 'left' : 'top';
  var opSide = isVertical ? 'bottom' : 'right';
  var arrowElementSize = getOuterSizes(arrowElement)[len];

  //
  // extends keepTogether behavior making sure the popper and its
  // reference have enough pixels in conjunction
  //

  // top/left side
  if (reference[opSide] - arrowElementSize < popper[side]) {
    data.offsets.popper[side] -= popper[side] - (reference[opSide] - arrowElementSize);
  }
  // bottom/right side
  if (reference[side] + arrowElementSize > popper[opSide]) {
    data.offsets.popper[side] += reference[side] + arrowElementSize - popper[opSide];
  }
  data.offsets.popper = getClientRect(data.offsets.popper);

  // compute center of the popper
  var center = reference[side] + reference[len] / 2 - arrowElementSize / 2;

  // Compute the sideValue using the updated popper offsets
  // take popper margin in account because we don't have this info available
  var css = getStyleComputedProperty(data.instance.popper);
  var popperMarginSide = parseFloat(css['margin' + sideCapitalized]);
  var popperBorderSide = parseFloat(css['border' + sideCapitalized + 'Width']);
  var sideValue = center - data.offsets.popper[side] - popperMarginSide - popperBorderSide;

  // prevent arrowElement from being placed not contiguously to its popper
  sideValue = Math.max(Math.min(popper[len] - arrowElementSize, sideValue), 0);

  data.arrowElement = arrowElement;
  data.offsets.arrow = (_data$offsets$arrow = {}, defineProperty(_data$offsets$arrow, side, Math.round(sideValue)), defineProperty(_data$offsets$arrow, altSide, ''), _data$offsets$arrow);

  return data;
}

/**
 * Get the opposite placement variation of the given one
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement variation
 * @returns {String} flipped placement variation
 */
function getOppositeVariation(variation) {
  if (variation === 'end') {
    return 'start';
  } else if (variation === 'start') {
    return 'end';
  }
  return variation;
}

/**
 * List of accepted placements to use as values of the `placement` option.<br />
 * Valid placements are:
 * - `auto`
 * - `top`
 * - `right`
 * - `bottom`
 * - `left`
 *
 * Each placement can have a variation from this list:
 * - `-start`
 * - `-end`
 *
 * Variations are interpreted easily if you think of them as the left to right
 * written languages. Horizontally (`top` and `bottom`), `start` is left and `end`
 * is right.<br />
 * Vertically (`left` and `right`), `start` is top and `end` is bottom.
 *
 * Some valid examples are:
 * - `top-end` (on top of reference, right aligned)
 * - `right-start` (on right of reference, top aligned)
 * - `bottom` (on bottom, centered)
 * - `auto-end` (on the side with more space available, alignment depends by placement)
 *
 * @static
 * @type {Array}
 * @enum {String}
 * @readonly
 * @method placements
 * @memberof Popper
 */
var placements = ['auto-start', 'auto', 'auto-end', 'top-start', 'top', 'top-end', 'right-start', 'right', 'right-end', 'bottom-end', 'bottom', 'bottom-start', 'left-end', 'left', 'left-start'];

// Get rid of `auto` `auto-start` and `auto-end`
var validPlacements = placements.slice(3);

/**
 * Given an initial placement, returns all the subsequent placements
 * clockwise (or counter-clockwise).
 *
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement - A valid placement (it accepts variations)
 * @argument {Boolean} counter - Set to true to walk the placements counterclockwise
 * @returns {Array} placements including their variations
 */
function clockwise(placement) {
  var counter = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  var index = validPlacements.indexOf(placement);
  var arr = validPlacements.slice(index + 1).concat(validPlacements.slice(0, index));
  return counter ? arr.reverse() : arr;
}

var BEHAVIORS = {
  FLIP: 'flip',
  CLOCKWISE: 'clockwise',
  COUNTERCLOCKWISE: 'counterclockwise'
};

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function flip(data, options) {
  // if `inner` modifier is enabled, we can't use the `flip` modifier
  if (isModifierEnabled(data.instance.modifiers, 'inner')) {
    return data;
  }

  if (data.flipped && data.placement === data.originalPlacement) {
    // seems like flip is trying to loop, probably there's not enough space on any of the flippable sides
    return data;
  }

  var boundaries = getBoundaries(data.instance.popper, data.instance.reference, options.padding, options.boundariesElement, data.positionFixed);

  var placement = data.placement.split('-')[0];
  var placementOpposite = getOppositePlacement(placement);
  var variation = data.placement.split('-')[1] || '';

  var flipOrder = [];

  switch (options.behavior) {
    case BEHAVIORS.FLIP:
      flipOrder = [placement, placementOpposite];
      break;
    case BEHAVIORS.CLOCKWISE:
      flipOrder = clockwise(placement);
      break;
    case BEHAVIORS.COUNTERCLOCKWISE:
      flipOrder = clockwise(placement, true);
      break;
    default:
      flipOrder = options.behavior;
  }

  flipOrder.forEach(function (step, index) {
    if (placement !== step || flipOrder.length === index + 1) {
      return data;
    }

    placement = data.placement.split('-')[0];
    placementOpposite = getOppositePlacement(placement);

    var popperOffsets = data.offsets.popper;
    var refOffsets = data.offsets.reference;

    // using floor because the reference offsets may contain decimals we are not going to consider here
    var floor = Math.floor;
    var overlapsRef = placement === 'left' && floor(popperOffsets.right) > floor(refOffsets.left) || placement === 'right' && floor(popperOffsets.left) < floor(refOffsets.right) || placement === 'top' && floor(popperOffsets.bottom) > floor(refOffsets.top) || placement === 'bottom' && floor(popperOffsets.top) < floor(refOffsets.bottom);

    var overflowsLeft = floor(popperOffsets.left) < floor(boundaries.left);
    var overflowsRight = floor(popperOffsets.right) > floor(boundaries.right);
    var overflowsTop = floor(popperOffsets.top) < floor(boundaries.top);
    var overflowsBottom = floor(popperOffsets.bottom) > floor(boundaries.bottom);

    var overflowsBoundaries = placement === 'left' && overflowsLeft || placement === 'right' && overflowsRight || placement === 'top' && overflowsTop || placement === 'bottom' && overflowsBottom;

    // flip the variation if required
    var isVertical = ['top', 'bottom'].indexOf(placement) !== -1;

    // flips variation if reference element overflows boundaries
    var flippedVariationByRef = !!options.flipVariations && (isVertical && variation === 'start' && overflowsLeft || isVertical && variation === 'end' && overflowsRight || !isVertical && variation === 'start' && overflowsTop || !isVertical && variation === 'end' && overflowsBottom);

    // flips variation if popper content overflows boundaries
    var flippedVariationByContent = !!options.flipVariationsByContent && (isVertical && variation === 'start' && overflowsRight || isVertical && variation === 'end' && overflowsLeft || !isVertical && variation === 'start' && overflowsBottom || !isVertical && variation === 'end' && overflowsTop);

    var flippedVariation = flippedVariationByRef || flippedVariationByContent;

    if (overlapsRef || overflowsBoundaries || flippedVariation) {
      // this boolean to detect any flip loop
      data.flipped = true;

      if (overlapsRef || overflowsBoundaries) {
        placement = flipOrder[index + 1];
      }

      if (flippedVariation) {
        variation = getOppositeVariation(variation);
      }

      data.placement = placement + (variation ? '-' + variation : '');

      // this object contains `position`, we want to preserve it along with
      // any additional property we may add in the future
      data.offsets.popper = _extends({}, data.offsets.popper, getPopperOffsets(data.instance.popper, data.offsets.reference, data.placement));

      data = runModifiers(data.instance.modifiers, data, 'flip');
    }
  });
  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function keepTogether(data) {
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var placement = data.placement.split('-')[0];
  var floor = Math.floor;
  var isVertical = ['top', 'bottom'].indexOf(placement) !== -1;
  var side = isVertical ? 'right' : 'bottom';
  var opSide = isVertical ? 'left' : 'top';
  var measurement = isVertical ? 'width' : 'height';

  if (popper[side] < floor(reference[opSide])) {
    data.offsets.popper[opSide] = floor(reference[opSide]) - popper[measurement];
  }
  if (popper[opSide] > floor(reference[side])) {
    data.offsets.popper[opSide] = floor(reference[side]);
  }

  return data;
}

/**
 * Converts a string containing value + unit into a px value number
 * @function
 * @memberof {modifiers~offset}
 * @private
 * @argument {String} str - Value + unit string
 * @argument {String} measurement - `height` or `width`
 * @argument {Object} popperOffsets
 * @argument {Object} referenceOffsets
 * @returns {Number|String}
 * Value in pixels, or original string if no values were extracted
 */
function toValue(str, measurement, popperOffsets, referenceOffsets) {
  // separate value from unit
  var split = str.match(/((?:\-|\+)?\d*\.?\d*)(.*)/);
  var value = +split[1];
  var unit = split[2];

  // If it's not a number it's an operator, I guess
  if (!value) {
    return str;
  }

  if (unit.indexOf('%') === 0) {
    var element = void 0;
    switch (unit) {
      case '%p':
        element = popperOffsets;
        break;
      case '%':
      case '%r':
      default:
        element = referenceOffsets;
    }

    var rect = getClientRect(element);
    return rect[measurement] / 100 * value;
  } else if (unit === 'vh' || unit === 'vw') {
    // if is a vh or vw, we calculate the size based on the viewport
    var size = void 0;
    if (unit === 'vh') {
      size = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    } else {
      size = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    }
    return size / 100 * value;
  } else {
    // if is an explicit pixel unit, we get rid of the unit and keep the value
    // if is an implicit unit, it's px, and we return just the value
    return value;
  }
}

/**
 * Parse an `offset` string to extrapolate `x` and `y` numeric offsets.
 * @function
 * @memberof {modifiers~offset}
 * @private
 * @argument {String} offset
 * @argument {Object} popperOffsets
 * @argument {Object} referenceOffsets
 * @argument {String} basePlacement
 * @returns {Array} a two cells array with x and y offsets in numbers
 */
function parseOffset(offset, popperOffsets, referenceOffsets, basePlacement) {
  var offsets = [0, 0];

  // Use height if placement is left or right and index is 0 otherwise use width
  // in this way the first offset will use an axis and the second one
  // will use the other one
  var useHeight = ['right', 'left'].indexOf(basePlacement) !== -1;

  // Split the offset string to obtain a list of values and operands
  // The regex addresses values with the plus or minus sign in front (+10, -20, etc)
  var fragments = offset.split(/(\+|\-)/).map(function (frag) {
    return frag.trim();
  });

  // Detect if the offset string contains a pair of values or a single one
  // they could be separated by comma or space
  var divider = fragments.indexOf(find(fragments, function (frag) {
    return frag.search(/,|\s/) !== -1;
  }));

  if (fragments[divider] && fragments[divider].indexOf(',') === -1) {
    console.warn('Offsets separated by white space(s) are deprecated, use a comma (,) instead.');
  }

  // If divider is found, we divide the list of values and operands to divide
  // them by ofset X and Y.
  var splitRegex = /\s*,\s*|\s+/;
  var ops = divider !== -1 ? [fragments.slice(0, divider).concat([fragments[divider].split(splitRegex)[0]]), [fragments[divider].split(splitRegex)[1]].concat(fragments.slice(divider + 1))] : [fragments];

  // Convert the values with units to absolute pixels to allow our computations
  ops = ops.map(function (op, index) {
    // Most of the units rely on the orientation of the popper
    var measurement = (index === 1 ? !useHeight : useHeight) ? 'height' : 'width';
    var mergeWithPrevious = false;
    return op
    // This aggregates any `+` or `-` sign that aren't considered operators
    // e.g.: 10 + +5 => [10, +, +5]
    .reduce(function (a, b) {
      if (a[a.length - 1] === '' && ['+', '-'].indexOf(b) !== -1) {
        a[a.length - 1] = b;
        mergeWithPrevious = true;
        return a;
      } else if (mergeWithPrevious) {
        a[a.length - 1] += b;
        mergeWithPrevious = false;
        return a;
      } else {
        return a.concat(b);
      }
    }, [])
    // Here we convert the string values into number values (in px)
    .map(function (str) {
      return toValue(str, measurement, popperOffsets, referenceOffsets);
    });
  });

  // Loop trough the offsets arrays and execute the operations
  ops.forEach(function (op, index) {
    op.forEach(function (frag, index2) {
      if (isNumeric(frag)) {
        offsets[index] += frag * (op[index2 - 1] === '-' ? -1 : 1);
      }
    });
  });
  return offsets;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @argument {Number|String} options.offset=0
 * The offset value as described in the modifier description
 * @returns {Object} The data object, properly modified
 */
function offset(data, _ref) {
  var offset = _ref.offset;
  var placement = data.placement,
      _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var basePlacement = placement.split('-')[0];

  var offsets = void 0;
  if (isNumeric(+offset)) {
    offsets = [+offset, 0];
  } else {
    offsets = parseOffset(offset, popper, reference, basePlacement);
  }

  if (basePlacement === 'left') {
    popper.top += offsets[0];
    popper.left -= offsets[1];
  } else if (basePlacement === 'right') {
    popper.top += offsets[0];
    popper.left += offsets[1];
  } else if (basePlacement === 'top') {
    popper.left += offsets[0];
    popper.top -= offsets[1];
  } else if (basePlacement === 'bottom') {
    popper.left += offsets[0];
    popper.top += offsets[1];
  }

  data.popper = popper;
  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function preventOverflow(data, options) {
  var boundariesElement = options.boundariesElement || getOffsetParent(data.instance.popper);

  // If offsetParent is the reference element, we really want to
  // go one step up and use the next offsetParent as reference to
  // avoid to make this modifier completely useless and look like broken
  if (data.instance.reference === boundariesElement) {
    boundariesElement = getOffsetParent(boundariesElement);
  }

  // NOTE: DOM access here
  // resets the popper's position so that the document size can be calculated excluding
  // the size of the popper element itself
  var transformProp = getSupportedPropertyName('transform');
  var popperStyles = data.instance.popper.style; // assignment to help minification
  var top = popperStyles.top,
      left = popperStyles.left,
      transform = popperStyles[transformProp];

  popperStyles.top = '';
  popperStyles.left = '';
  popperStyles[transformProp] = '';

  var boundaries = getBoundaries(data.instance.popper, data.instance.reference, options.padding, boundariesElement, data.positionFixed);

  // NOTE: DOM access here
  // restores the original style properties after the offsets have been computed
  popperStyles.top = top;
  popperStyles.left = left;
  popperStyles[transformProp] = transform;

  options.boundaries = boundaries;

  var order = options.priority;
  var popper = data.offsets.popper;

  var check = {
    primary: function primary(placement) {
      var value = popper[placement];
      if (popper[placement] < boundaries[placement] && !options.escapeWithReference) {
        value = Math.max(popper[placement], boundaries[placement]);
      }
      return defineProperty({}, placement, value);
    },
    secondary: function secondary(placement) {
      var mainSide = placement === 'right' ? 'left' : 'top';
      var value = popper[mainSide];
      if (popper[placement] > boundaries[placement] && !options.escapeWithReference) {
        value = Math.min(popper[mainSide], boundaries[placement] - (placement === 'right' ? popper.width : popper.height));
      }
      return defineProperty({}, mainSide, value);
    }
  };

  order.forEach(function (placement) {
    var side = ['left', 'top'].indexOf(placement) !== -1 ? 'primary' : 'secondary';
    popper = _extends({}, popper, check[side](placement));
  });

  data.offsets.popper = popper;

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function shift(data) {
  var placement = data.placement;
  var basePlacement = placement.split('-')[0];
  var shiftvariation = placement.split('-')[1];

  // if shift shiftvariation is specified, run the modifier
  if (shiftvariation) {
    var _data$offsets = data.offsets,
        reference = _data$offsets.reference,
        popper = _data$offsets.popper;

    var isVertical = ['bottom', 'top'].indexOf(basePlacement) !== -1;
    var side = isVertical ? 'left' : 'top';
    var measurement = isVertical ? 'width' : 'height';

    var shiftOffsets = {
      start: defineProperty({}, side, reference[side]),
      end: defineProperty({}, side, reference[side] + reference[measurement] - popper[measurement])
    };

    data.offsets.popper = _extends({}, popper, shiftOffsets[shiftvariation]);
  }

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function hide(data) {
  if (!isModifierRequired(data.instance.modifiers, 'hide', 'preventOverflow')) {
    return data;
  }

  var refRect = data.offsets.reference;
  var bound = find(data.instance.modifiers, function (modifier) {
    return modifier.name === 'preventOverflow';
  }).boundaries;

  if (refRect.bottom < bound.top || refRect.left > bound.right || refRect.top > bound.bottom || refRect.right < bound.left) {
    // Avoid unnecessary DOM access if visibility hasn't changed
    if (data.hide === true) {
      return data;
    }

    data.hide = true;
    data.attributes['x-out-of-boundaries'] = '';
  } else {
    // Avoid unnecessary DOM access if visibility hasn't changed
    if (data.hide === false) {
      return data;
    }

    data.hide = false;
    data.attributes['x-out-of-boundaries'] = false;
  }

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function inner(data) {
  var placement = data.placement;
  var basePlacement = placement.split('-')[0];
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var isHoriz = ['left', 'right'].indexOf(basePlacement) !== -1;

  var subtractLength = ['top', 'left'].indexOf(basePlacement) === -1;

  popper[isHoriz ? 'left' : 'top'] = reference[basePlacement] - (subtractLength ? popper[isHoriz ? 'width' : 'height'] : 0);

  data.placement = getOppositePlacement(placement);
  data.offsets.popper = getClientRect(popper);

  return data;
}

/**
 * Modifier function, each modifier can have a function of this type assigned
 * to its `fn` property.<br />
 * These functions will be called on each update, this means that you must
 * make sure they are performant enough to avoid performance bottlenecks.
 *
 * @function ModifierFn
 * @argument {dataObject} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {dataObject} The data object, properly modified
 */

/**
 * Modifiers are plugins used to alter the behavior of your poppers.<br />
 * Popper.js uses a set of 9 modifiers to provide all the basic functionalities
 * needed by the library.
 *
 * Usually you don't want to override the `order`, `fn` and `onLoad` props.
 * All the other properties are configurations that could be tweaked.
 * @namespace modifiers
 */
var modifiers = {
  /**
   * Modifier used to shift the popper on the start or end of its reference
   * element.<br />
   * It will read the variation of the `placement` property.<br />
   * It can be one either `-end` or `-start`.
   * @memberof modifiers
   * @inner
   */
  shift: {
    /** @prop {number} order=100 - Index used to define the order of execution */
    order: 100,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: shift
  },

  /**
   * The `offset` modifier can shift your popper on both its axis.
   *
   * It accepts the following units:
   * - `px` or unit-less, interpreted as pixels
   * - `%` or `%r`, percentage relative to the length of the reference element
   * - `%p`, percentage relative to the length of the popper element
   * - `vw`, CSS viewport width unit
   * - `vh`, CSS viewport height unit
   *
   * For length is intended the main axis relative to the placement of the popper.<br />
   * This means that if the placement is `top` or `bottom`, the length will be the
   * `width`. In case of `left` or `right`, it will be the `height`.
   *
   * You can provide a single value (as `Number` or `String`), or a pair of values
   * as `String` divided by a comma or one (or more) white spaces.<br />
   * The latter is a deprecated method because it leads to confusion and will be
   * removed in v2.<br />
   * Additionally, it accepts additions and subtractions between different units.
   * Note that multiplications and divisions aren't supported.
   *
   * Valid examples are:
   * ```
   * 10
   * '10%'
   * '10, 10'
   * '10%, 10'
   * '10 + 10%'
   * '10 - 5vh + 3%'
   * '-10px + 5vh, 5px - 6%'
   * ```
   * > **NB**: If you desire to apply offsets to your poppers in a way that may make them overlap
   * > with their reference element, unfortunately, you will have to disable the `flip` modifier.
   * > You can read more on this at this [issue](https://github.com/FezVrasta/popper.js/issues/373).
   *
   * @memberof modifiers
   * @inner
   */
  offset: {
    /** @prop {number} order=200 - Index used to define the order of execution */
    order: 200,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: offset,
    /** @prop {Number|String} offset=0
     * The offset value as described in the modifier description
     */
    offset: 0
  },

  /**
   * Modifier used to prevent the popper from being positioned outside the boundary.
   *
   * A scenario exists where the reference itself is not within the boundaries.<br />
   * We can say it has "escaped the boundaries" — or just "escaped".<br />
   * In this case we need to decide whether the popper should either:
   *
   * - detach from the reference and remain "trapped" in the boundaries, or
   * - if it should ignore the boundary and "escape with its reference"
   *
   * When `escapeWithReference` is set to`true` and reference is completely
   * outside its boundaries, the popper will overflow (or completely leave)
   * the boundaries in order to remain attached to the edge of the reference.
   *
   * @memberof modifiers
   * @inner
   */
  preventOverflow: {
    /** @prop {number} order=300 - Index used to define the order of execution */
    order: 300,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: preventOverflow,
    /**
     * @prop {Array} [priority=['left','right','top','bottom']]
     * Popper will try to prevent overflow following these priorities by default,
     * then, it could overflow on the left and on top of the `boundariesElement`
     */
    priority: ['left', 'right', 'top', 'bottom'],
    /**
     * @prop {number} padding=5
     * Amount of pixel used to define a minimum distance between the boundaries
     * and the popper. This makes sure the popper always has a little padding
     * between the edges of its container
     */
    padding: 5,
    /**
     * @prop {String|HTMLElement} boundariesElement='scrollParent'
     * Boundaries used by the modifier. Can be `scrollParent`, `window`,
     * `viewport` or any DOM element.
     */
    boundariesElement: 'scrollParent'
  },

  /**
   * Modifier used to make sure the reference and its popper stay near each other
   * without leaving any gap between the two. Especially useful when the arrow is
   * enabled and you want to ensure that it points to its reference element.
   * It cares only about the first axis. You can still have poppers with margin
   * between the popper and its reference element.
   * @memberof modifiers
   * @inner
   */
  keepTogether: {
    /** @prop {number} order=400 - Index used to define the order of execution */
    order: 400,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: keepTogether
  },

  /**
   * This modifier is used to move the `arrowElement` of the popper to make
   * sure it is positioned between the reference element and its popper element.
   * It will read the outer size of the `arrowElement` node to detect how many
   * pixels of conjunction are needed.
   *
   * It has no effect if no `arrowElement` is provided.
   * @memberof modifiers
   * @inner
   */
  arrow: {
    /** @prop {number} order=500 - Index used to define the order of execution */
    order: 500,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: arrow,
    /** @prop {String|HTMLElement} element='[x-arrow]' - Selector or node used as arrow */
    element: '[x-arrow]'
  },

  /**
   * Modifier used to flip the popper's placement when it starts to overlap its
   * reference element.
   *
   * Requires the `preventOverflow` modifier before it in order to work.
   *
   * **NOTE:** this modifier will interrupt the current update cycle and will
   * restart it if it detects the need to flip the placement.
   * @memberof modifiers
   * @inner
   */
  flip: {
    /** @prop {number} order=600 - Index used to define the order of execution */
    order: 600,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: flip,
    /**
     * @prop {String|Array} behavior='flip'
     * The behavior used to change the popper's placement. It can be one of
     * `flip`, `clockwise`, `counterclockwise` or an array with a list of valid
     * placements (with optional variations)
     */
    behavior: 'flip',
    /**
     * @prop {number} padding=5
     * The popper will flip if it hits the edges of the `boundariesElement`
     */
    padding: 5,
    /**
     * @prop {String|HTMLElement} boundariesElement='viewport'
     * The element which will define the boundaries of the popper position.
     * The popper will never be placed outside of the defined boundaries
     * (except if `keepTogether` is enabled)
     */
    boundariesElement: 'viewport',
    /**
     * @prop {Boolean} flipVariations=false
     * The popper will switch placement variation between `-start` and `-end` when
     * the reference element overlaps its boundaries.
     *
     * The original placement should have a set variation.
     */
    flipVariations: false,
    /**
     * @prop {Boolean} flipVariationsByContent=false
     * The popper will switch placement variation between `-start` and `-end` when
     * the popper element overlaps its reference boundaries.
     *
     * The original placement should have a set variation.
     */
    flipVariationsByContent: false
  },

  /**
   * Modifier used to make the popper flow toward the inner of the reference element.
   * By default, when this modifier is disabled, the popper will be placed outside
   * the reference element.
   * @memberof modifiers
   * @inner
   */
  inner: {
    /** @prop {number} order=700 - Index used to define the order of execution */
    order: 700,
    /** @prop {Boolean} enabled=false - Whether the modifier is enabled or not */
    enabled: false,
    /** @prop {ModifierFn} */
    fn: inner
  },

  /**
   * Modifier used to hide the popper when its reference element is outside of the
   * popper boundaries. It will set a `x-out-of-boundaries` attribute which can
   * be used to hide with a CSS selector the popper when its reference is
   * out of boundaries.
   *
   * Requires the `preventOverflow` modifier before it in order to work.
   * @memberof modifiers
   * @inner
   */
  hide: {
    /** @prop {number} order=800 - Index used to define the order of execution */
    order: 800,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: hide
  },

  /**
   * Computes the style that will be applied to the popper element to gets
   * properly positioned.
   *
   * Note that this modifier will not touch the DOM, it just prepares the styles
   * so that `applyStyle` modifier can apply it. This separation is useful
   * in case you need to replace `applyStyle` with a custom implementation.
   *
   * This modifier has `850` as `order` value to maintain backward compatibility
   * with previous versions of Popper.js. Expect the modifiers ordering method
   * to change in future major versions of the library.
   *
   * @memberof modifiers
   * @inner
   */
  computeStyle: {
    /** @prop {number} order=850 - Index used to define the order of execution */
    order: 850,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: computeStyle,
    /**
     * @prop {Boolean} gpuAcceleration=true
     * If true, it uses the CSS 3D transformation to position the popper.
     * Otherwise, it will use the `top` and `left` properties
     */
    gpuAcceleration: true,
    /**
     * @prop {string} [x='bottom']
     * Where to anchor the X axis (`bottom` or `top`). AKA X offset origin.
     * Change this if your popper should grow in a direction different from `bottom`
     */
    x: 'bottom',
    /**
     * @prop {string} [x='left']
     * Where to anchor the Y axis (`left` or `right`). AKA Y offset origin.
     * Change this if your popper should grow in a direction different from `right`
     */
    y: 'right'
  },

  /**
   * Applies the computed styles to the popper element.
   *
   * All the DOM manipulations are limited to this modifier. This is useful in case
   * you want to integrate Popper.js inside a framework or view library and you
   * want to delegate all the DOM manipulations to it.
   *
   * Note that if you disable this modifier, you must make sure the popper element
   * has its position set to `absolute` before Popper.js can do its work!
   *
   * Just disable this modifier and define your own to achieve the desired effect.
   *
   * @memberof modifiers
   * @inner
   */
  applyStyle: {
    /** @prop {number} order=900 - Index used to define the order of execution */
    order: 900,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: applyStyle,
    /** @prop {Function} */
    onLoad: applyStyleOnLoad,
    /**
     * @deprecated since version 1.10.0, the property moved to `computeStyle` modifier
     * @prop {Boolean} gpuAcceleration=true
     * If true, it uses the CSS 3D transformation to position the popper.
     * Otherwise, it will use the `top` and `left` properties
     */
    gpuAcceleration: undefined
  }
};

/**
 * The `dataObject` is an object containing all the information used by Popper.js.
 * This object is passed to modifiers and to the `onCreate` and `onUpdate` callbacks.
 * @name dataObject
 * @property {Object} data.instance The Popper.js instance
 * @property {String} data.placement Placement applied to popper
 * @property {String} data.originalPlacement Placement originally defined on init
 * @property {Boolean} data.flipped True if popper has been flipped by flip modifier
 * @property {Boolean} data.hide True if the reference element is out of boundaries, useful to know when to hide the popper
 * @property {HTMLElement} data.arrowElement Node used as arrow by arrow modifier
 * @property {Object} data.styles Any CSS property defined here will be applied to the popper. It expects the JavaScript nomenclature (eg. `marginBottom`)
 * @property {Object} data.arrowStyles Any CSS property defined here will be applied to the popper arrow. It expects the JavaScript nomenclature (eg. `marginBottom`)
 * @property {Object} data.boundaries Offsets of the popper boundaries
 * @property {Object} data.offsets The measurements of popper, reference and arrow elements
 * @property {Object} data.offsets.popper `top`, `left`, `width`, `height` values
 * @property {Object} data.offsets.reference `top`, `left`, `width`, `height` values
 * @property {Object} data.offsets.arrow] `top` and `left` offsets, only one of them will be different from 0
 */

/**
 * Default options provided to Popper.js constructor.<br />
 * These can be overridden using the `options` argument of Popper.js.<br />
 * To override an option, simply pass an object with the same
 * structure of the `options` object, as the 3rd argument. For example:
 * ```
 * new Popper(ref, pop, {
 *   modifiers: {
 *     preventOverflow: { enabled: false }
 *   }
 * })
 * ```
 * @type {Object}
 * @static
 * @memberof Popper
 */
var Defaults = {
  /**
   * Popper's placement.
   * @prop {Popper.placements} placement='bottom'
   */
  placement: 'bottom',

  /**
   * Set this to true if you want popper to position it self in 'fixed' mode
   * @prop {Boolean} positionFixed=false
   */
  positionFixed: false,

  /**
   * Whether events (resize, scroll) are initially enabled.
   * @prop {Boolean} eventsEnabled=true
   */
  eventsEnabled: true,

  /**
   * Set to true if you want to automatically remove the popper when
   * you call the `destroy` method.
   * @prop {Boolean} removeOnDestroy=false
   */
  removeOnDestroy: false,

  /**
   * Callback called when the popper is created.<br />
   * By default, it is set to no-op.<br />
   * Access Popper.js instance with `data.instance`.
   * @prop {onCreate}
   */
  onCreate: function onCreate() {},

  /**
   * Callback called when the popper is updated. This callback is not called
   * on the initialization/creation of the popper, but only on subsequent
   * updates.<br />
   * By default, it is set to no-op.<br />
   * Access Popper.js instance with `data.instance`.
   * @prop {onUpdate}
   */
  onUpdate: function onUpdate() {},

  /**
   * List of modifiers used to modify the offsets before they are applied to the popper.
   * They provide most of the functionalities of Popper.js.
   * @prop {modifiers}
   */
  modifiers: modifiers
};

/**
 * @callback onCreate
 * @param {dataObject} data
 */

/**
 * @callback onUpdate
 * @param {dataObject} data
 */

// Utils
// Methods
var Popper = function () {
  /**
   * Creates a new Popper.js instance.
   * @class Popper
   * @param {Element|referenceObject} reference - The reference element used to position the popper
   * @param {Element} popper - The HTML / XML element used as the popper
   * @param {Object} options - Your custom options to override the ones defined in [Defaults](#defaults)
   * @return {Object} instance - The generated Popper.js instance
   */
  function Popper(reference, popper) {
    var _this = this;

    var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    classCallCheck(this, Popper);

    this.scheduleUpdate = function () {
      return requestAnimationFrame(_this.update);
    };

    // make update() debounced, so that it only runs at most once-per-tick
    this.update = debounce(this.update.bind(this));

    // with {} we create a new object with the options inside it
    this.options = _extends({}, Popper.Defaults, options);

    // init state
    this.state = {
      isDestroyed: false,
      isCreated: false,
      scrollParents: []
    };

    // get reference and popper elements (allow jQuery wrappers)
    this.reference = reference && reference.jquery ? reference[0] : reference;
    this.popper = popper && popper.jquery ? popper[0] : popper;

    // Deep merge modifiers options
    this.options.modifiers = {};
    Object.keys(_extends({}, Popper.Defaults.modifiers, options.modifiers)).forEach(function (name) {
      _this.options.modifiers[name] = _extends({}, Popper.Defaults.modifiers[name] || {}, options.modifiers ? options.modifiers[name] : {});
    });

    // Refactoring modifiers' list (Object => Array)
    this.modifiers = Object.keys(this.options.modifiers).map(function (name) {
      return _extends({
        name: name
      }, _this.options.modifiers[name]);
    })
    // sort the modifiers by order
    .sort(function (a, b) {
      return a.order - b.order;
    });

    // modifiers have the ability to execute arbitrary code when Popper.js get inited
    // such code is executed in the same order of its modifier
    // they could add new properties to their options configuration
    // BE AWARE: don't add options to `options.modifiers.name` but to `modifierOptions`!
    this.modifiers.forEach(function (modifierOptions) {
      if (modifierOptions.enabled && isFunction(modifierOptions.onLoad)) {
        modifierOptions.onLoad(_this.reference, _this.popper, _this.options, modifierOptions, _this.state);
      }
    });

    // fire the first update to position the popper in the right place
    this.update();

    var eventsEnabled = this.options.eventsEnabled;
    if (eventsEnabled) {
      // setup event listeners, they will take care of update the position in specific situations
      this.enableEventListeners();
    }

    this.state.eventsEnabled = eventsEnabled;
  }

  // We can't use class properties because they don't get listed in the
  // class prototype and break stuff like Sinon stubs


  createClass(Popper, [{
    key: 'update',
    value: function update$$1() {
      return update.call(this);
    }
  }, {
    key: 'destroy',
    value: function destroy$$1() {
      return destroy.call(this);
    }
  }, {
    key: 'enableEventListeners',
    value: function enableEventListeners$$1() {
      return enableEventListeners.call(this);
    }
  }, {
    key: 'disableEventListeners',
    value: function disableEventListeners$$1() {
      return disableEventListeners.call(this);
    }

    /**
     * Schedules an update. It will run on the next UI update available.
     * @method scheduleUpdate
     * @memberof Popper
     */


    /**
     * Collection of utilities useful when writing custom modifiers.
     * Starting from version 1.7, this method is available only if you
     * include `popper-utils.js` before `popper.js`.
     *
     * **DEPRECATION**: This way to access PopperUtils is deprecated
     * and will be removed in v2! Use the PopperUtils module directly instead.
     * Due to the high instability of the methods contained in Utils, we can't
     * guarantee them to follow semver. Use them at your own risk!
     * @static
     * @private
     * @type {Object}
     * @deprecated since version 1.8
     * @member Utils
     * @memberof Popper
     */

  }]);
  return Popper;
}();

/**
 * The `referenceObject` is an object that provides an interface compatible with Popper.js
 * and lets you use it as replacement of a real DOM node.<br />
 * You can use this method to position a popper relatively to a set of coordinates
 * in case you don't have a DOM node to use as reference.
 *
 * ```
 * new Popper(referenceObject, popperNode);
 * ```
 *
 * NB: This feature isn't supported in Internet Explorer 10.
 * @name referenceObject
 * @property {Function} data.getBoundingClientRect
 * A function that returns a set of coordinates compatible with the native `getBoundingClientRect` method.
 * @property {number} data.clientWidth
 * An ES6 getter that will return the width of the virtual reference element.
 * @property {number} data.clientHeight
 * An ES6 getter that will return the height of the virtual reference element.
 */


Popper.Utils = (typeof window !== 'undefined' ? window : globalThis).PopperUtils;
Popper.placements = placements;
Popper.Defaults = Defaults;

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Popper);
//# sourceMappingURL=popper.js.map


/***/ },

/***/ "./apps/settings/src/views/SettingsApp.vue"
/*!*************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/***/ },

/***/ "./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts"
/*!************************************************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SettingsApp.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05"
/*!*******************************************************************************!*\
  !*** ./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05 ***!
  \*******************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SettingsApp_vue_vue_type_template_id_21177c05__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SettingsApp.vue?vue&type=template&id=21177c05 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/SettingsApp.vue?vue&type=template&id=21177c05");


/***/ },

/***/ "./node_modules/vue-resize/dist/vue-resize.esm.js"
/*!********************************************************!*\
  !*** ./node_modules/vue-resize/dist/vue-resize.esm.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ResizeObserver: () => (/* binding */ __vue_component__),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   install: () => (/* binding */ install)
/* harmony export */ });
function getInternetExplorerVersion() {
  var ua = window.navigator.userAgent;
  var msie = ua.indexOf('MSIE ');

  if (msie > 0) {
    // IE 10 or older => return version number
    return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
  }

  var trident = ua.indexOf('Trident/');

  if (trident > 0) {
    // IE 11 => return version number
    var rv = ua.indexOf('rv:');
    return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
  }

  var edge = ua.indexOf('Edge/');

  if (edge > 0) {
    // Edge (IE 12+) => return version number
    return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
  } // other browser


  return -1;
}

//
var isIE;

function initCompat() {
  if (!initCompat.init) {
    initCompat.init = true;
    isIE = getInternetExplorerVersion() !== -1;
  }
}

var script = {
  name: 'ResizeObserver',
  props: {
    emitOnMount: {
      type: Boolean,
      default: false
    },
    ignoreWidth: {
      type: Boolean,
      default: false
    },
    ignoreHeight: {
      type: Boolean,
      default: false
    }
  },
  mounted: function mounted() {
    var _this = this;

    initCompat();
    this.$nextTick(function () {
      _this._w = _this.$el.offsetWidth;
      _this._h = _this.$el.offsetHeight;

      if (_this.emitOnMount) {
        _this.emitSize();
      }
    });
    var object = document.createElement('object');
    this._resizeObject = object;
    object.setAttribute('aria-hidden', 'true');
    object.setAttribute('tabindex', -1);
    object.onload = this.addResizeHandlers;
    object.type = 'text/html';

    if (isIE) {
      this.$el.appendChild(object);
    }

    object.data = 'about:blank';

    if (!isIE) {
      this.$el.appendChild(object);
    }
  },
  beforeDestroy: function beforeDestroy() {
    this.removeResizeHandlers();
  },
  methods: {
    compareAndNotify: function compareAndNotify() {
      if (!this.ignoreWidth && this._w !== this.$el.offsetWidth || !this.ignoreHeight && this._h !== this.$el.offsetHeight) {
        this._w = this.$el.offsetWidth;
        this._h = this.$el.offsetHeight;
        this.emitSize();
      }
    },
    emitSize: function emitSize() {
      this.$emit('notify', {
        width: this._w,
        height: this._h
      });
    },
    addResizeHandlers: function addResizeHandlers() {
      this._resizeObject.contentDocument.defaultView.addEventListener('resize', this.compareAndNotify);

      this.compareAndNotify();
    },
    removeResizeHandlers: function removeResizeHandlers() {
      if (this._resizeObject && this._resizeObject.onload) {
        if (!isIE && this._resizeObject.contentDocument) {
          this._resizeObject.contentDocument.defaultView.removeEventListener('resize', this.compareAndNotify);
        }

        this.$el.removeChild(this._resizeObject);
        this._resizeObject.onload = null;
        this._resizeObject = null;
      }
    }
  }
};

function normalizeComponent(template, style, script, scopeId, isFunctionalTemplate, moduleIdentifier
/* server only */
, shadowMode, createInjector, createInjectorSSR, createInjectorShadow) {
  if (typeof shadowMode !== 'boolean') {
    createInjectorSSR = createInjector;
    createInjector = shadowMode;
    shadowMode = false;
  } // Vue.extend constructor export interop.


  var options = typeof script === 'function' ? script.options : script; // render functions

  if (template && template.render) {
    options.render = template.render;
    options.staticRenderFns = template.staticRenderFns;
    options._compiled = true; // functional template

    if (isFunctionalTemplate) {
      options.functional = true;
    }
  } // scopedId


  if (scopeId) {
    options._scopeId = scopeId;
  }

  var hook;

  if (moduleIdentifier) {
    // server build
    hook = function hook(context) {
      // 2.3 injection
      context = context || // cached call
      this.$vnode && this.$vnode.ssrContext || // stateful
      this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext; // functional
      // 2.2 with runInNewContext: true

      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__;
      } // inject component styles


      if (style) {
        style.call(this, createInjectorSSR(context));
      } // register component module identifier for async chunk inference


      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier);
      }
    }; // used by ssr in case component is cached and beforeCreate
    // never gets called


    options._ssrRegister = hook;
  } else if (style) {
    hook = shadowMode ? function (context) {
      style.call(this, createInjectorShadow(context, this.$root.$options.shadowRoot));
    } : function (context) {
      style.call(this, createInjector(context));
    };
  }

  if (hook) {
    if (options.functional) {
      // register for functional component in vue file
      var originalRender = options.render;

      options.render = function renderWithStyleInjection(h, context) {
        hook.call(context);
        return originalRender(h, context);
      };
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate;
      options.beforeCreate = existing ? [].concat(existing, hook) : [hook];
    }
  }

  return script;
}

/* script */
var __vue_script__ = script;
/* template */

var __vue_render__ = function __vue_render__() {
  var _vm = this;

  var _h = _vm.$createElement;

  var _c = _vm._self._c || _h;

  return _c("div", {
    staticClass: "resize-observer",
    attrs: {
      tabindex: "-1"
    }
  });
};

var __vue_staticRenderFns__ = [];
__vue_render__._withStripped = true;
/* style */

var __vue_inject_styles__ = undefined;
/* scoped */

var __vue_scope_id__ = "data-v-8859cc6c";
/* module identifier */

var __vue_module_identifier__ = undefined;
/* functional template */

var __vue_is_functional_template__ = false;
/* style inject */

/* style inject SSR */

/* style inject shadow dom */

var __vue_component__ = /*#__PURE__*/normalizeComponent({
  render: __vue_render__,
  staticRenderFns: __vue_staticRenderFns__
}, __vue_inject_styles__, __vue_script__, __vue_scope_id__, __vue_is_functional_template__, __vue_module_identifier__, false, undefined, undefined, undefined);

function install(Vue) {
  // eslint-disable-next-line vue/component-definition-name-casing
  Vue.component('resize-observer', __vue_component__);
  Vue.component('ResizeObserver', __vue_component__);
}

var plugin = {
  // eslint-disable-next-line no-undef
  version: "1.0.1",
  install: install
};

var GlobalVue = null;

if (typeof window !== 'undefined') {
  GlobalVue = window.Vue;
} else if (typeof globalThis !== 'undefined') {
  GlobalVue = globalThis.Vue;
}

if (GlobalVue) {
  GlobalVue.use(plugin);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (plugin);

//# sourceMappingURL=vue-resize.esm.js.map


/***/ },

/***/ "./node_modules/vuex-router-sync/index.js"
/*!************************************************!*\
  !*** ./node_modules/vuex-router-sync/index.js ***!
  \************************************************/
(__unused_webpack_module, exports) {

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

/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js ***!
  \*******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _classCallCheck)
/* harmony export */ });
function _classCallCheck(a, n) {
  if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/createClass.js"
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/createClass.js ***!
  \****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _createClass)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperties(e, r) {
  for (var t = 0; t < r.length; t++) {
    var o = r[t];
    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(o.key), o);
  }
}
function _createClass(e, r, t) {
  return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", {
    writable: !1
  }), e;
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(e, r, t) {
  return (r = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js"
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPrimitive)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js"
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPropertyKey)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js"
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}


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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_preview-BIbJGxXF_mjs-node_modules_nextcloud_dialog-7546cc":"dce7060a8340cecf2ca5","node_modules_nextcloud_dialogs_dist_chunks_ConflictPicker-CWBf0soh_mjs":"01a2e7bc2c49db839239","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcTextField_index_mjs":"50270bc67122ae47dfd0","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-C1yRZfLt_mjs":"a3985d66705012167d75","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-7_GNN76e_mjs":"b2479474dfc9749ea9d0","settings-apps-view":"3e4b6bf35555f38f1ee6","settings-users":"c23f193552914eef4c20","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcColorPicker_index_mjs":"63766ea64d27a6d8d6cc","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcDateTimePicker_in-952ddb":"bd2fc411cc830fe12f0b","apps_settings_src_components_AppStoreDiscover_PostType_vue":"d17bdc94139cd3912646","apps_settings_src_components_AppStoreDiscover_CarouselType_vue":"66dc6e901147d5631965","apps_settings_src_components_AppStoreDiscover_ShowcaseType_vue":"25a5a8e5255340c48d1c","node_modules_nextcloud_vue_dist_Components_NcColorPicker_mjs":"cc9a80a105a480079016","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"21fc91c563f5cd8d04c3","node_modules_rehype-highlight_index_js":"625f8818e33c457c3744"}[chunkId] + "";
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/settings/src/main-apps-users-management.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=settings-vue-settings-apps-users-management.js.map?v=0f54f39ea3a3c857c733