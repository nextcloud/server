"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["settings-apps-view"],{

/***/ "./apps/settings/src/constants/AppsConstants.js":
/*!******************************************************!*\
  !*** ./apps/settings/src/constants/AppsConstants.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   APPS_SECTION_ENUM: () => (/* binding */ APPS_SECTION_ENUM)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * @copyright 2022, Julia Kirschenheuter <julia.kirschenheuter@nextcloud.com>
 *
 * @author Julia Kirschenheuter <julia.kirschenheuter@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



/** Enum of verification constants, according to Apps */
const APPS_SECTION_ENUM = Object.freeze({
  discover: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Discover'),
  installed: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Your apps'),
  enabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Active apps'),
  disabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Disabled apps'),
  updates: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Updates'),
  'app-bundles': (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'App bundles'),
  featured: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Featured apps'),
  supported: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Supported apps') // From subscription
});

/***/ }),

/***/ "./apps/settings/src/mixins/AppManagement.js":
/*!***************************************************!*\
  !*** ./apps/settings/src/mixins/AppManagement.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../service/rebuild-navigation.js */ "./apps/settings/src/service/rebuild-navigation.js");
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  computed: {
    appGroups() {
      return this.app.groups.map(group => {
        return {
          id: group,
          name: group
        };
      });
    },
    installing() {
      return this.$store.getters.loading('install');
    },
    isLoading() {
      return this.app && this.$store.getters.loading(this.app.id);
    },
    enableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Download and enable');
      }
      return t('settings', 'Enable');
    },
    forceEnableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Allow untested app');
      }
      return t('settings', 'Allow untested app');
    },
    enableButtonTooltip() {
      if (this.app.needsDownload) {
        return t('settings', 'The app will be downloaded from the App Store');
      }
      return null;
    },
    forceEnableButtonTooltip() {
      const base = t('settings', 'This app is not marked as compatible with your Nextcloud version. If you continue you will still be able to install the app. Note that the app might not work as expected.');
      if (this.app.needsDownload) {
        return base + ' ' + t('settings', 'The app will be downloaded from the App Store');
      }
      return base;
    }
  },
  data() {
    return {
      groupCheckedAppsData: false
    };
  },
  mounted() {
    if (this.app && this.app.groups && this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  },
  methods: {
    asyncFindGroup(query) {
      return this.$store.dispatch('getGroups', {
        search: query,
        limit: 5,
        offset: 0
      });
    },
    isLimitedToGroups(app) {
      if (this.app.groups.length || this.groupCheckedAppsData) {
        return true;
      }
      return false;
    },
    setGroupLimit() {
      if (!this.groupCheckedAppsData) {
        this.$store.dispatch('enableApp', {
          appId: this.app.id,
          groups: []
        });
      }
    },
    canLimitToGroups(app) {
      if (app.types && app.types.includes('filesystem') || app.types.includes('prelogin') || app.types.includes('authentication') || app.types.includes('logging') || app.types.includes('prevent_group_restriction')) {
        return false;
      }
      return true;
    },
    addGroupLimitation(groupArray) {
      const group = groupArray.pop();
      const groups = this.app.groups.concat([]).concat([group.id]);
      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups
      });
    },
    removeGroupLimitation(group) {
      const currentGroups = this.app.groups.concat([]);
      const index = currentGroups.indexOf(group.id);
      if (index > -1) {
        currentGroups.splice(index, 1);
      }
      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups: currentGroups
      });
    },
    forceEnable(appId) {
      this.$store.dispatch('forceEnableApp', {
        appId,
        groups: []
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    enable(appId) {
      this.$store.dispatch('enableApp', {
        appId,
        groups: []
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    disable(appId) {
      this.$store.dispatch('disableApp', {
        appId
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    remove(appId) {
      this.$store.dispatch('uninstallApp', {
        appId
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    install(appId) {
      this.$store.dispatch('enableApp', {
        appId
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    update(appId) {
      this.$store.dispatch('updateApp', {
        appId
      }).then(response => {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(error => {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    }
  }
});

/***/ }),

/***/ "./apps/settings/src/service/rebuild-navigation.js":
/*!*********************************************************!*\
  !*** ./apps/settings/src/service/rebuild-navigation.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('core/navigation', 2) + '/apps?format=json').then(_ref => {
    let {
      data
    } = _ref;
    if (data.ocs.meta.statuscode !== 200) {
      return;
    }
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('nextcloud:app-menu.refresh', {
      apps: data.ocs.data
    });
    window.dispatchEvent(new Event('resize'));
  });
});

/***/ }),

/***/ "./apps/settings/src/composables/useAppIcon.ts":
/*!*****************************************************!*\
  !*** ./apps/settings/src/composables/useAppIcon.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useAppIcon: () => (/* binding */ useAppIcon)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../constants/AppstoreCategoryIcons.ts */ "./apps/settings/src/constants/AppstoreCategoryIcons.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");




/**
 * Get the app icon raw SVG for use with `NcIconSvgWrapper` (do never use without sanitizing)
 * It has a fallback to the categroy icon.
 *
 * @param app The app to get the icon for
 */
function useAppIcon(app) {
  const appIcon = (0,vue__WEBPACK_IMPORTED_MODULE_2__.ref)(null);
  /**
   * Fallback value if no app icon available
   */
  const categoryIcon = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => {
    var _flat$map$filter$at, _app$value$category, _app$value;
    const path = (_flat$map$filter$at = [(_app$value$category = (_app$value = app.value) === null || _app$value === void 0 ? void 0 : _app$value.category) !== null && _app$value$category !== void 0 ? _app$value$category : []].flat().map(name => _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_0__["default"][name]).filter(icon => !!icon).at(0)) !== null && _flat$map$filter$at !== void 0 ? _flat$map$filter$at : _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiCog;
    return path ? "<svg viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"".concat(path, "\" /></svg>") : null;
  });
  (0,vue__WEBPACK_IMPORTED_MODULE_2__.watchEffect)(async () => {
    var _app$value2;
    // Note: Only variables until the first `await` will be watched!
    if (!((_app$value2 = app.value) !== null && _app$value2 !== void 0 && _app$value2.preview)) {
      appIcon.value = categoryIcon.value;
    } else {
      appIcon.value = null;
      // Now try to load the real app icon
      try {
        const response = await window.fetch(app.value.preview);
        const blob = await response.blob();
        const rawSvg = await blob.text();
        appIcon.value = rawSvg.replaceAll(/fill="#(fff|ffffff)([a-z0-9]{1,2})?"/ig, 'fill="currentColor"');
      } catch (error) {
        appIcon.value = categoryIcon.value;
        _logger_ts__WEBPACK_IMPORTED_MODULE_1__["default"].error('Could not load app icon', {
          error
        });
      }
    }
  });
  return {
    appIcon
  };
}

/***/ }),

/***/ "./apps/settings/src/constants/AppstoreCategoryIcons.ts":
/*!**************************************************************!*\
  !*** ./apps/settings/src/constants/AppstoreCategoryIcons.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * SVG paths used for appstore category icons
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Object.freeze({
  // system special categories
  discover: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiStarCircleOutline,
  installed: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiAccount,
  enabled: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiCheck,
  disabled: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiClose,
  bundles: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiArchive,
  supported: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiStarShooting,
  featured: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiStar,
  updates: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiDownload,
  // generic categories
  auth: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiKey,
  customization: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiCog,
  dashboard: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiViewDashboard,
  files: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiFolder,
  games: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiControllerClassic,
  integration: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiOpenInApp,
  monitoring: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiMonitorEye,
  multimedia: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiMultimedia,
  office: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiFileDocumentEdit,
  organization: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiOfficeBuilding,
  search: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiMagnify,
  security: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiSecurity,
  social: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiAccountMultiple,
  tools: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiTools,
  workflow: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiClipboardFlow
}));

/***/ }),

/***/ "./apps/settings/src/store/apps-store.ts":
/*!***********************************************!*\
  !*** ./apps/settings/src/store/apps-store.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useAppsStore: () => (/* binding */ useAppsStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger */ "./apps/settings/src/logger.ts");
/* harmony import */ var _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../constants/AppstoreCategoryIcons.ts */ "./apps/settings/src/constants/AppstoreCategoryIcons.ts");
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */








const showApiError = () => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('settings', 'An error occurred during the request. Unable to proceed.'));
const useAppsStore = (0,pinia__WEBPACK_IMPORTED_MODULE_7__.defineStore)('settings-apps', {
  state: () => ({
    apps: [],
    categories: [],
    updateCount: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'appstoreUpdateCount', 0),
    loading: {
      apps: false,
      categories: false
    },
    loadingList: false,
    gettingCategoriesPromise: null
  }),
  actions: {
    async loadCategories() {
      let force = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      if (this.categories.length > 0 && !force) {
        return;
      }
      try {
        this.loading.categories = true;
        const {
          data: categories
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/categories'));
        for (const category of categories) {
          var _APPSTORE_CATEGORY_IC;
          category.icon = (_APPSTORE_CATEGORY_IC = _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_6__["default"][category.id]) !== null && _APPSTORE_CATEGORY_IC !== void 0 ? _APPSTORE_CATEGORY_IC : '';
        }
        this.$patch({
          categories
        });
      } catch (error) {
        _logger__WEBPACK_IMPORTED_MODULE_5__["default"].error(error);
        showApiError();
      } finally {
        this.loading.categories = false;
      }
    },
    async loadApps() {
      let force = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      if (this.apps.length > 0 && !force) {
        return;
      }
      try {
        this.loading.apps = true;
        const {
          data
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('settings/apps/list'));
        this.$patch({
          apps: data.apps
        });
      } catch (error) {
        _logger__WEBPACK_IMPORTED_MODULE_5__["default"].error(error);
        showApiError();
      } finally {
        this.loading.apps = false;
      }
    },
    getCategoryById(categoryId) {
      var _this$categories$find;
      return (_this$categories$find = this.categories.find(_ref => {
        let {
          id
        } = _ref;
        return id === categoryId;
      })) !== null && _this$categories$find !== void 0 ? _this$categories$find : null;
    },
    getAppById(appId) {
      var _this$apps$find;
      return (_this$apps$find = this.apps.find(_ref2 => {
        let {
          id
        } = _ref2;
        return id === appId;
      })) !== null && _this$apps$find !== void 0 ? _this$apps$find : null;
    }
  }
});

/***/ }),

/***/ "./apps/settings/src/utils/appDiscoverParser.ts":
/*!******************************************************!*\
  !*** ./apps/settings/src/utils/appDiscoverParser.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   filterElements: () => (/* binding */ filterElements),
/* harmony export */   parseApiResponse: () => (/* binding */ parseApiResponse)
/* harmony export */ });
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 * Helper to transform the JSON API results to proper frontend objects (app discover section elements)
 *
 * @param element The JSON API element to transform
 */
const parseApiResponse = element => {
  var _element$type;
  const appElement = {
    ...element
  };
  if (appElement.date) {
    appElement.date = Date.parse(appElement.date);
  }
  if (appElement.expiryDate) {
    appElement.expiryDate = Date.parse(appElement.expiryDate);
  }
  if (appElement.type === 'post') {
    return appElement;
  } else if (appElement.type === 'showcase') {
    return appElement;
  } else if (appElement.type === 'carousel') {
    return appElement;
  }
  throw new Error("Invalid argument, app discover element with type ".concat((_element$type = element.type) !== null && _element$type !== void 0 ? _element$type : 'unknown', " is unknown"));
};
/**
 * Filter outdated or upcoming elements
 * @param element Element to check
 */
const filterElements = element => {
  const now = Date.now();
  // Element not yet published
  if (element.date && element.date > now) {
    return false;
  }
  // Element expired
  if (element.expiryDate && element.expiryDate < now) {
    return false;
  }
  return true;
};

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts":
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  __name: 'AppLevelBadge',
  props: {
    level: {
      type: Number,
      required: false
    }
  },
  setup(__props) {
    const props = __props;
    const isSupported = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => props.level === 300);
    const isFeatured = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => props.level === 200);
    const badgeIcon = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => isSupported.value ? _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiStarShooting : _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiCheck);
    const badgeText = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => isSupported.value ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Supported') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Featured'));
    const badgeTitle = (0,vue__WEBPACK_IMPORTED_MODULE_2__.computed)(() => isSupported.value ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'This app is supported via your current Nextcloud subscription.') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.'));
    return {
      __sfc: true,
      props,
      isSupported,
      isFeatured,
      badgeIcon,
      badgeText,
      badgeTitle,
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_0__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts":
/*!*************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts ***!
  \*************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  name: 'AppScore',
  components: {
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    score: {
      type: Number,
      required: true
    }
  },
  setup() {
    return {
      mdiStar: _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiStar,
      mdiStarHalfFull: _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiStarHalfFull,
      mdiStarOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_3__.mdiStarOutline
    };
  },
  computed: {
    title() {
      const appScore = (this.score * 5).toFixed(1);
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Community rating: {score}/5', {
        score: appScore
      });
    },
    fullStars() {
      return Math.floor(this.score * 5 + 0.25);
    },
    emptyStars() {
      return Math.min(Math.floor((1 - this.score) * 5 + 0.25), 5 - this.fullStars);
    },
    hasHalfStar() {
      return this.fullStars + this.emptyStars < 5;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts":
/*!************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts ***!
  \************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../logger */ "./apps/settings/src/logger.ts");
/* harmony import */ var _utils_appDiscoverParser_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../utils/appDiscoverParser.ts */ "./apps/settings/src/utils/appDiscoverParser.ts");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_9__.defineComponent)({
  __name: 'AppStoreDiscoverSection',
  setup(__props) {
    const PostType = (0,vue__WEBPACK_IMPORTED_MODULE_9__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "apps_settings_src_components_AppStoreDiscover_PostType_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./PostType.vue */ "./apps/settings/src/components/AppStoreDiscover/PostType.vue")));
    const CarouselType = (0,vue__WEBPACK_IMPORTED_MODULE_9__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("apps_settings_src_components_AppStoreDiscover_PostType_vue"), __webpack_require__.e("apps_settings_src_components_AppStoreDiscover_CarouselType_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./CarouselType.vue */ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue")));
    const ShowcaseType = (0,vue__WEBPACK_IMPORTED_MODULE_9__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("apps_settings_src_components_AppStoreDiscover_PostType_vue"), __webpack_require__.e("apps_settings_src_components_AppStoreDiscover_ShowcaseType_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./ShowcaseType.vue */ "./apps/settings/src/components/AppStoreDiscover/ShowcaseType.vue")));
    const hasError = (0,vue__WEBPACK_IMPORTED_MODULE_9__.ref)(false);
    const elements = (0,vue__WEBPACK_IMPORTED_MODULE_9__.ref)([]);
    /**
     * Shuffle using the Fisher-Yates algorithm
     * @param array The array to shuffle (in place)
     */
    const shuffleArray = array => {
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
      }
      return array;
    };
    /**
     * Load the app discover section information
     */
    (0,vue__WEBPACK_IMPORTED_MODULE_9__.onBeforeMount)(async () => {
      try {
        const {
          data
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/settings/api/apps/discover'));
        if (data.length === 0) {
          _logger__WEBPACK_IMPORTED_MODULE_7__["default"].info('No app discover elements available (empty response)');
          hasError.value = true;
          return;
        }
        // Parse data to ensure dates are useable and then filter out expired or future elements
        const parsedElements = data.map(_utils_appDiscoverParser_ts__WEBPACK_IMPORTED_MODULE_8__.parseApiResponse).filter(_utils_appDiscoverParser_ts__WEBPACK_IMPORTED_MODULE_8__.filterElements);
        // Shuffle elements to make it looks more interesting
        const shuffledElements = shuffleArray(parsedElements);
        // Sort pinned elements first
        shuffledElements.sort((a, b) => {
          var _a$order, _b$order;
          return ((_a$order = a.order) !== null && _a$order !== void 0 ? _a$order : Infinity) < ((_b$order = b.order) !== null && _b$order !== void 0 ? _b$order : Infinity) ? -1 : 1;
        });
        // Set the elements to the UI
        elements.value = shuffledElements;
      } catch (error) {
        hasError.value = true;
        _logger__WEBPACK_IMPORTED_MODULE_7__["default"].error(error);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Could not load app discover section'));
      }
    });
    const getComponent = type => {
      if (type === 'post') {
        return PostType;
      } else if (type === 'carousel') {
        return CarouselType;
      } else if (type === 'showcase') {
        return ShowcaseType;
      }
      return (0,vue__WEBPACK_IMPORTED_MODULE_9__.defineComponent)({
        mounted: () => _logger__WEBPACK_IMPORTED_MODULE_7__["default"].error('Unknown component requested ', type),
        render: h => h('div', (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Could not render element'))
      });
    };
    return {
      __sfc: true,
      PostType,
      CarouselType,
      ShowcaseType,
      hasError,
      elements,
      shuffleArray,
      getComponent,
      mdiEyeOff: _mdi_js__WEBPACK_IMPORTED_MODULE_10__.mdiEyeOff,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
      NcEmptyContent: _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_6__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Markdown.vue */ "./apps/settings/src/components/Markdown.vue");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_4__.defineComponent)({
  __name: 'AppDescriptionTab',
  props: {
    app: {
      type: null,
      required: true
    }
  },
  setup(__props) {
    return {
      __sfc: true,
      mdiTextShort: _mdi_js__WEBPACK_IMPORTED_MODULE_5__.mdiTextShort,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
      NcAppSidebarTab: _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_1__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__["default"],
      Markdown: _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts":
/*!**************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Markdown.vue */ "./apps/settings/src/components/Markdown.vue");







// eslint-disable-next-line @typescript-eslint/no-unused-vars
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_4__.defineComponent)({
  __name: 'AppReleasesTab',
  props: {
    app: {
      type: null,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const hasChangelog = (0,vue__WEBPACK_IMPORTED_MODULE_4__.computed)(() => {
      var _props$app$releases$, _props$app$releases;
      return Object.values((_props$app$releases$ = (_props$app$releases = props.app.releases) === null || _props$app$releases === void 0 || (_props$app$releases = _props$app$releases[0]) === null || _props$app$releases === void 0 ? void 0 : _props$app$releases.translations) !== null && _props$app$releases$ !== void 0 ? _props$app$releases$ : {}).some(_ref => {
        let {
          changelog
        } = _ref;
        return !!changelog;
      });
    });
    const createChangelogFromRelease = release => {
      var _ref2, _release$translations, _release$translations2, _release$translations3;
      return (_ref2 = (_release$translations = (_release$translations2 = release.translations) === null || _release$translations2 === void 0 || (_release$translations2 = _release$translations2[(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getLanguage)()]) === null || _release$translations2 === void 0 ? void 0 : _release$translations2.changelog) !== null && _release$translations !== void 0 ? _release$translations : (_release$translations3 = release.translations) === null || _release$translations3 === void 0 || (_release$translations3 = _release$translations3.en) === null || _release$translations3 === void 0 ? void 0 : _release$translations3.changelog) !== null && _ref2 !== void 0 ? _ref2 : '';
    };
    return {
      __sfc: true,
      props,
      hasChangelog,
      createChangelogFromRelease,
      mdiClockFast: _mdi_js__WEBPACK_IMPORTED_MODULE_5__.mdiClockFast,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
      NcAppSidebarTab: _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_1__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__["default"],
      Markdown: _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts":
/*!***********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts ***!
  \***********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue_router_composables__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-router/composables */ "./node_modules/vue-router/composables.mjs");
/* harmony import */ var _store_apps_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../store/apps-store */ "./apps/settings/src/store/apps-store.ts");
/* harmony import */ var _constants_AppsConstants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../constants/AppsConstants */ "./apps/settings/src/constants/AppsConstants.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _components_AppList_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/AppList.vue */ "./apps/settings/src/components/AppList.vue");
/* harmony import */ var _components_AppStoreDiscover_AppStoreDiscoverSection_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../components/AppStoreDiscover/AppStoreDiscoverSection.vue */ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_8__.defineComponent)({
  __name: 'AppStore',
  setup(__props) {
    const route = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_9__.useRoute)();
    const store = (0,_store_apps_store__WEBPACK_IMPORTED_MODULE_1__.useAppsStore)();
    /**
     * ID of the current active category, default is `discover`
     */
    const currentCategory = (0,vue__WEBPACK_IMPORTED_MODULE_8__.computed)(() => {
      var _route$params$categor, _route$params;
      return (_route$params$categor = (_route$params = route.params) === null || _route$params === void 0 ? void 0 : _route$params.category) !== null && _route$params$categor !== void 0 ? _route$params$categor : 'discover';
    });
    const appStoreLabel = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'App Store');
    const viewLabel = (0,vue__WEBPACK_IMPORTED_MODULE_8__.computed)(() => {
      var _ref, _APPS_SECTION_ENUM$cu, _store$getCategoryByI;
      return (_ref = (_APPS_SECTION_ENUM$cu = _constants_AppsConstants__WEBPACK_IMPORTED_MODULE_2__.APPS_SECTION_ENUM[currentCategory.value]) !== null && _APPS_SECTION_ENUM$cu !== void 0 ? _APPS_SECTION_ENUM$cu : (_store$getCategoryByI = store.getCategoryById(currentCategory.value)) === null || _store$getCategoryByI === void 0 ? void 0 : _store$getCategoryByI.displayName) !== null && _ref !== void 0 ? _ref : appStoreLabel;
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_8__.watchEffect)(() => {
      window.document.title = "".concat(viewLabel.value, " - ").concat(appStoreLabel, " - Nextcloud");
    });
    // TODO this part should be migrated to pinia
    const instance = (0,vue__WEBPACK_IMPORTED_MODULE_8__.getCurrentInstance)();
    /** Is the app list loading */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const isLoading = (0,vue__WEBPACK_IMPORTED_MODULE_8__.computed)(() => (instance === null || instance === void 0 ? void 0 : instance.proxy).$store.getters.loading('list'));
    (0,vue__WEBPACK_IMPORTED_MODULE_8__.onBeforeMount)(() => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (instance === null || instance === void 0 ? void 0 : instance.proxy).$store.dispatch('getCategories', {
        shouldRefetchCategories: true
      });
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (instance === null || instance === void 0 ? void 0 : instance.proxy).$store.dispatch('getAllApps');
    });
    return {
      __sfc: true,
      route,
      store,
      currentCategory,
      appStoreLabel,
      viewLabel,
      instance,
      isLoading,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
      NcAppContent: _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_3__["default"],
      NcEmptyContent: _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__["default"],
      AppList: _components_AppList_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
      AppStoreDiscoverSection: _components_AppStoreDiscover_AppStoreDiscoverSection_vue__WEBPACK_IMPORTED_MODULE_7__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts":
/*!*********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \*********************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _constants_AppsConstants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../constants/AppsConstants */ "./apps/settings/src/constants/AppsConstants.js");
/* harmony import */ var _store_apps_store__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/apps-store */ "./apps/settings/src/store/apps-store.ts");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationSpacer.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSpacer.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../constants/AppstoreCategoryIcons.ts */ "./apps/settings/src/constants/AppstoreCategoryIcons.ts");













/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_11__.defineComponent)({
  __name: 'AppStoreNavigation',
  setup(__props) {
    const updateCount = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'appstoreUpdateCount', 0);
    const appstoreEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'appstoreEnabled', true);
    const developerDocsUrl = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'appstoreDeveloperDocs', '');
    const store = (0,_store_apps_store__WEBPACK_IMPORTED_MODULE_3__.useAppsStore)();
    const categories = (0,vue__WEBPACK_IMPORTED_MODULE_11__.computed)(() => store.categories);
    const categoriesLoading = (0,vue__WEBPACK_IMPORTED_MODULE_11__.computed)(() => store.loading.categories);
    /**
     * Check if the current instance has a support subscription from the Nextcloud GmbH
     *
     * For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
     */
    const isSubscribed = (0,vue__WEBPACK_IMPORTED_MODULE_11__.computed)(() => store.apps.find(_ref => {
      let {
        level
      } = _ref;
      return level === 300;
    }) !== undefined);
    // load categories when component is mounted
    (0,vue__WEBPACK_IMPORTED_MODULE_11__.onBeforeMount)(() => {
      store.loadCategories();
      store.loadApps();
    });
    return {
      __sfc: true,
      updateCount,
      appstoreEnabled,
      developerDocsUrl,
      store,
      categories,
      categoriesLoading,
      isSubscribed,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
      APPS_SECTION_ENUM: _constants_AppsConstants__WEBPACK_IMPORTED_MODULE_2__.APPS_SECTION_ENUM,
      NcAppNavigation: _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcAppNavigationItem: _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcAppNavigationSpacer: _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcCounterBubble: _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_7__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_8__["default"],
      NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_9__["default"],
      APPSTORE_CATEGORY_ICONS: _constants_AppstoreCategoryIcons_ts__WEBPACK_IMPORTED_MODULE_10__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue_router_composables__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-router/composables */ "./node_modules/vue-router/composables.mjs");
/* harmony import */ var _store_apps_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../store/apps-store */ "./apps/settings/src/store/apps-store.ts");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/AppList/AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _components_AppStoreSidebar_AppDescriptionTab_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/AppStoreSidebar/AppDescriptionTab.vue */ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue");
/* harmony import */ var _components_AppStoreSidebar_AppDetailsTab_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/AppStoreSidebar/AppDetailsTab.vue */ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue");
/* harmony import */ var _components_AppStoreSidebar_AppReleasesTab_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../components/AppStoreSidebar/AppReleasesTab.vue */ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue");
/* harmony import */ var _components_AppList_AppLevelBadge_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/AppList/AppLevelBadge.vue */ "./apps/settings/src/components/AppList/AppLevelBadge.vue");
/* harmony import */ var _composables_useAppIcon_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../composables/useAppIcon.ts */ "./apps/settings/src/composables/useAppIcon.ts");













/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_10__.defineComponent)({
  __name: 'AppStoreSidebar',
  setup(__props) {
    const route = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_11__.useRoute)();
    const router = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_11__.useRouter)();
    const store = (0,_store_apps_store__WEBPACK_IMPORTED_MODULE_1__.useAppsStore)();
    const appId = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => {
      var _route$params$id;
      return (_route$params$id = route.params.id) !== null && _route$params$id !== void 0 ? _route$params$id : '';
    });
    const app = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => store.getAppById(appId.value));
    const hasRating = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => {
      var _app$value$appstoreDa;
      return ((_app$value$appstoreDa = app.value.appstoreData) === null || _app$value$appstoreDa === void 0 ? void 0 : _app$value$appstoreDa.ratingNumOverall) > 5;
    });
    const rating = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => {
      var _app$value$appstoreDa2, _app$value$appstoreDa3, _app$value$appstoreDa4;
      return ((_app$value$appstoreDa2 = app.value.appstoreData) === null || _app$value$appstoreDa2 === void 0 ? void 0 : _app$value$appstoreDa2.ratingNumRecent) > 5 ? app.value.appstoreData.ratingRecent : (_app$value$appstoreDa3 = (_app$value$appstoreDa4 = app.value.appstoreData) === null || _app$value$appstoreDa4 === void 0 ? void 0 : _app$value$appstoreDa4.ratingOverall) !== null && _app$value$appstoreDa3 !== void 0 ? _app$value$appstoreDa3 : 0.5;
    });
    const showSidebar = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => app.value !== null);
    const {
      appIcon
    } = (0,_composables_useAppIcon_ts__WEBPACK_IMPORTED_MODULE_9__.useAppIcon)(app);
    /**
     * The second text line shown on the sidebar
     */
    const licenseText = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => app.value ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Version {version}, {license}-licensed', {
      version: app.value.version,
      license: app.value.licence.toString().toUpperCase()
    }) : '');
    const activeTab = (0,vue__WEBPACK_IMPORTED_MODULE_10__.ref)('details');
    (0,vue__WEBPACK_IMPORTED_MODULE_10__.watch)([app], () => {
      activeTab.value = 'details';
    });
    /**
     * Hide the details sidebar by pushing a new route
     */
    const hideAppDetails = () => {
      router.push({
        name: 'apps-category',
        params: {
          category: route.params.category
        }
      });
    };
    /**
     * Whether the app screenshot is loaded
     */
    const screenshotLoaded = (0,vue__WEBPACK_IMPORTED_MODULE_10__.ref)(false);
    const hasScreenshot = (0,vue__WEBPACK_IMPORTED_MODULE_10__.computed)(() => {
      var _app$value;
      return ((_app$value = app.value) === null || _app$value === void 0 ? void 0 : _app$value.screenshot) && screenshotLoaded.value;
    });
    /**
     * Preload the app screenshot
     */
    const loadScreenshot = () => {
      var _app$value2, _app$value3;
      if ((_app$value2 = app.value) !== null && _app$value2 !== void 0 && _app$value2.releases && (_app$value3 = app.value) !== null && _app$value3 !== void 0 && _app$value3.screenshot) {
        const image = new Image();
        image.onload = () => {
          screenshotLoaded.value = true;
        };
        image.src = app.value.screenshot;
      }
    };
    // Watch app and set screenshot loaded when
    (0,vue__WEBPACK_IMPORTED_MODULE_10__.watch)([app], loadScreenshot);
    (0,vue__WEBPACK_IMPORTED_MODULE_10__.onMounted)(loadScreenshot);
    return {
      __sfc: true,
      route,
      router,
      store,
      appId,
      app,
      hasRating,
      rating,
      showSidebar,
      appIcon,
      licenseText,
      activeTab,
      hideAppDetails,
      screenshotLoaded,
      hasScreenshot,
      loadScreenshot,
      NcAppSidebar: _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_2__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__["default"],
      AppScore: _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
      AppDescriptionTab: _components_AppStoreSidebar_AppDescriptionTab_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
      AppDetailsTab: _components_AppStoreSidebar_AppDetailsTab_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
      AppReleasesTab: _components_AppStoreSidebar_AppReleasesTab_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
      AppLevelBadge: _components_AppList_AppLevelBadge_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppList/AppItem.vue */ "./apps/settings/src/components/AppList/AppItem.vue");
/* harmony import */ var p_limit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-limit */ "./node_modules/p-limit/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppList',
  components: {
    AppItem: _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    category: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      search: ''
    };
  },
  computed: {
    counter() {
      return this.apps.filter(app => app.update).length;
    },
    loading() {
      return this.$store.getters.loading('list');
    },
    hasPendingUpdate() {
      return this.apps.filter(app => app.update).length > 0;
    },
    showUpdateAll() {
      return this.hasPendingUpdate && this.useListView;
    },
    apps() {
      const apps = this.$store.getters.getAllApps.filter(app => app.name.toLowerCase().search(this.search.toLowerCase()) !== -1).sort(function (a, b) {
        const sortStringA = '' + (a.active ? 0 : 1) + (a.update ? 0 : 1) + a.name;
        const sortStringB = '' + (b.active ? 0 : 1) + (b.update ? 0 : 1) + b.name;
        return OC.Util.naturalSortCompare(sortStringA, sortStringB);
      });
      if (this.category === 'installed') {
        return apps.filter(app => app.installed);
      }
      if (this.category === 'enabled') {
        return apps.filter(app => app.active && app.installed);
      }
      if (this.category === 'disabled') {
        return apps.filter(app => !app.active && app.installed);
      }
      if (this.category === 'app-bundles') {
        return apps.filter(app => app.bundles);
      }
      if (this.category === 'updates') {
        return apps.filter(app => app.update);
      }
      if (this.category === 'supported') {
        // For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
        return apps.filter(app => app.level === 300);
      }
      if (this.category === 'featured') {
        // An app level of `200` will be set for apps featured on the app store
        return apps.filter(app => app.level === 200);
      }

      // filter app store categories
      return apps.filter(app => {
        return app.appstore && app.category !== undefined && (app.category === this.category || app.category.indexOf(this.category) > -1);
      });
    },
    bundles() {
      return this.$store.getters.getAppBundles.filter(bundle => this.bundleApps(bundle.id).length > 0);
    },
    bundleApps() {
      return function (bundle) {
        return this.$store.getters.getAllApps.filter(app => {
          return app.bundleIds !== undefined && app.bundleIds.includes(bundle);
        });
      };
    },
    searchApps() {
      if (this.search === '') {
        return [];
      }
      return this.$store.getters.getAllApps.filter(app => {
        if (app.name.toLowerCase().search(this.search.toLowerCase()) !== -1) {
          return !this.apps.find(_app => _app.id === app.id);
        }
        return false;
      });
    },
    useAppStoreView() {
      return !this.useListView && !this.useBundleView;
    },
    useListView() {
      return this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates' || this.category === 'featured' || this.category === 'supported';
    },
    useBundleView() {
      return this.category === 'app-bundles';
    },
    allBundlesEnabled() {
      return id => {
        return this.bundleApps(id).filter(app => !app.active).length === 0;
      };
    },
    bundleToggleText() {
      return id => {
        if (this.allBundlesEnabled(id)) {
          return t('settings', 'Disable all');
        }
        return t('settings', 'Download and enable all');
      };
    }
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    setSearch(_ref) {
      let {
        query
      } = _ref;
      this.search = query;
    },
    resetSearch() {
      this.search = '';
    },
    toggleBundle(id) {
      if (this.allBundlesEnabled(id)) {
        return this.disableBundle(id);
      }
      return this.enableBundle(id);
    },
    enableBundle(id) {
      const apps = this.bundleApps(id).map(app => app.id);
      this.$store.dispatch('enableApp', {
        appId: apps,
        groups: []
      }).catch(error => {
        console.error(error);
        OC.Notification.show(error);
      });
    },
    disableBundle(id) {
      const apps = this.bundleApps(id).map(app => app.id);
      this.$store.dispatch('disableApp', {
        appId: apps,
        groups: []
      }).catch(error => {
        OC.Notification.show(error);
      });
    },
    updateAll() {
      const limit = (0,p_limit__WEBPACK_IMPORTED_MODULE_2__["default"])(1);
      this.apps.filter(app => app.update).map(app => limit(() => this.$store.dispatch('updateApp', {
        appId: app.id
      })));
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js":
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _AppLevelBadge_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppLevelBadge.vue */ "./apps/settings/src/components/AppList/AppLevelBadge.vue");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../SvgFilterMixin.vue */ "./apps/settings/src/components/SvgFilterMixin.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppItem',
  components: {
    AppLevelBadge: _AppLevelBadge_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    AppScore: _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_2__["default"], _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    app: {
      type: Object,
      required: true
    },
    category: {
      type: String,
      required: true
    },
    listView: {
      type: Boolean,
      default: true
    },
    useBundleView: {
      type: Boolean,
      default: false
    },
    headers: {
      type: String,
      default: null
    },
    inline: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isSelected: false,
      scrolled: false,
      screenshotLoaded: false
    };
  },
  computed: {
    hasRating() {
      return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
    },
    dataItemTag() {
      return this.listView ? 'td' : 'div';
    },
    withSidebar() {
      return !!this.$route.params.id;
    }
  },
  watch: {
    '$route.params.id'(id) {
      this.isSelected = this.app.id === id;
    }
  },
  mounted() {
    this.isSelected = this.app.id === this.$route.params.id;
    if (this.app.releases && this.app.screenshot) {
      const image = new Image();
      image.onload = () => {
        this.screenshotLoaded = true;
      };
      image.src = this.app.screenshot;
    }
  },
  watchers: {},
  methods: {
    prefix(prefix, content) {
      return prefix + '_' + content;
    },
    getDataItemHeaders(columnName) {
      return this.useBundleView ? [this.headers, columnName].join(' ') : null;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTime.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _store_apps_store__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../store/apps-store */ "./apps/settings/src/store/apps-store.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppDetailsTab',
  components: {
    NcAppSidebarTab: _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcDateTime: _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_5__["default"]],
  props: {
    app: {
      type: Object,
      required: true
    }
  },
  setup() {
    const store = (0,_store_apps_store__WEBPACK_IMPORTED_MODULE_6__.useAppsStore)();
    return {
      store,
      mdiBug: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiBug,
      mdiFeatureSearch: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiFeatureSearch,
      mdiStar: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiStar,
      mdiTextBox: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiTextBox,
      mdiTooltipQuestion: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiTooltipQuestion
    };
  },
  data() {
    return {
      groupCheckedAppsData: false
    };
  },
  computed: {
    lastModified() {
      var _map$sort$at, _this$app$appstoreDat, _this$app$appstoreDat2;
      return (_map$sort$at = ((_this$app$appstoreDat = (_this$app$appstoreDat2 = this.app.appstoreData) === null || _this$app$appstoreDat2 === void 0 ? void 0 : _this$app$appstoreDat2.releases) !== null && _this$app$appstoreDat !== void 0 ? _this$app$appstoreDat : []).map(_ref => {
        let {
          lastModified
        } = _ref;
        return Date.parse(lastModified);
      }).sort().at(0)) !== null && _map$sort$at !== void 0 ? _map$sort$at : null;
    },
    /**
     * App authors as comma separated string
     */
    appAuthors() {
      console.warn(this.app);
      if (!this.app) {
        return '';
      }
      const authorName = xmlNode => {
        if (xmlNode['@value']) {
          // Complex node (with email or homepage attribute)
          return xmlNode['@value'];
        }
        // Simple text node
        return xmlNode;
      };
      const authors = Array.isArray(this.app.author) ? this.app.author.map(authorName) : [authorName(this.app.author)];
      return authors.sort((a, b) => a.split(' ').at(-1).localeCompare(b.split(' ').at(-1))).join(', ');
    },
    appstoreUrl() {
      return "https://apps.nextcloud.com/apps/".concat(this.app.id);
    },
    /**
     * Further external resources (e.g. website)
     */
    externalResources() {
      const resources = [];
      if (!this.app.internal) {
        resources.push({
          id: 'appstore',
          href: this.appstoreUrl,
          label: t('settings', 'View in store')
        });
      }
      if (this.app.website) {
        resources.push({
          id: 'website',
          href: this.app.website,
          label: t('settings', 'Visit website')
        });
      }
      if (this.app.documentation) {
        if (this.app.documentation.user) {
          resources.push({
            id: 'doc-user',
            href: this.app.documentation.user,
            label: t('settings', 'Usage documentation')
          });
        }
        if (this.app.documentation.admin) {
          resources.push({
            id: 'doc-admin',
            href: this.app.documentation.admin,
            label: t('settings', 'Admin documentation')
          });
        }
        if (this.app.documentation.developer) {
          resources.push({
            id: 'doc-developer',
            href: this.app.documentation.developer,
            label: t('settings', 'Developer documentation')
          });
        }
      }
      return resources;
    },
    appCategories() {
      return [this.app.category].flat().map(id => {
        var _this$store$getCatego, _this$store$getCatego2;
        return (_this$store$getCatego = (_this$store$getCatego2 = this.store.getCategoryById(id)) === null || _this$store$getCatego2 === void 0 ? void 0 : _this$store$getCatego2.displayName) !== null && _this$store$getCatego !== void 0 ? _this$store$getCatego : id;
      }).join(', ');
    },
    rateAppUrl() {
      return "".concat(this.appstoreUrl, "#comments");
    },
    appGroups() {
      return this.app.groups.map(group => {
        return {
          id: group,
          name: group
        };
      });
    },
    groups() {
      return this.$store.getters.getGroups.filter(group => group.id !== 'disabled').sort((a, b) => a.name.localeCompare(b.name));
    }
  },
  mounted() {
    if (this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! marked */ "./node_modules/marked/lib/marked.esm.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_1__);


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Markdown',
  props: {
    text: {
      type: String,
      default: ''
    },
    minHeading: {
      type: Number,
      default: 1
    }
  },
  computed: {
    renderMarkdown() {
      const renderer = new marked__WEBPACK_IMPORTED_MODULE_0__.marked.Renderer();
      renderer.link = function (href, title, text) {
        let prot;
        try {
          prot = decodeURIComponent(unescape(href)).replace(/[^\w:]/g, '').toLowerCase();
        } catch (e) {
          return '';
        }
        if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
          return '';
        }
        let out = '<a href="' + href + '" rel="noreferrer noopener"';
        if (title) {
          out += ' title="' + title + '"';
        }
        out += '>' + text + '</a>';
        return out;
      };
      renderer.heading = (text, level) => {
        level = Math.min(6, level + (this.minHeading - 1));
        return "<h".concat(level, ">").concat(text, "</h").concat(level, ">");
      };
      renderer.image = function (href, title, text) {
        if (text) {
          return text;
        }
        return title;
      };
      renderer.blockquote = function (quote) {
        return quote;
      };
      return dompurify__WEBPACK_IMPORTED_MODULE_1___default().sanitize((0,marked__WEBPACK_IMPORTED_MODULE_0__.marked)(this.text.trim(), {
        renderer,
        gfm: false,
        highlight: false,
        tables: false,
        breaks: false,
        pedantic: false,
        sanitize: true,
        smartLists: true,
        smartypants: false
      }), {
        SAFE_FOR_JQUERY: true,
        ALLOWED_TAGS: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'p', 'a', 'ul', 'ol', 'li', 'em', 'del', 'blockquote']
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SvgFilterMixin',
  data() {
    return {
      filterId: ''
    };
  },
  computed: {
    filterUrl() {
      return "url(#".concat(this.filterId, ")");
    }
  },
  mounted() {
    this.filterId = 'invertIconApps-' + Math.random().toString(36).substring(2);
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    attrs: {
      id: "app-content-inner"
    }
  }, [_c("div", {
    staticClass: "apps-list",
    class: {
      "apps-list--list-view": _vm.useBundleView || _vm.useListView,
      "apps-list--store-view": _vm.useAppStoreView
    },
    attrs: {
      id: "apps-list"
    }
  }, [_vm.useListView ? [_vm.showUpdateAll ? _c("div", {
    staticClass: "apps-list__toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.n("settings", "%n app has an update available", "%n apps have an update available", _vm.counter)) + "\n\t\t\t\t"), _vm.showUpdateAll ? _c("NcButton", {
    attrs: {
      id: "app-list-update-all",
      type: "primary"
    },
    on: {
      click: _vm.updateAll
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.n("settings", "Update", "Update all", _vm.counter)) + "\n\t\t\t\t")]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), !_vm.showUpdateAll ? _c("div", {
    staticClass: "apps-list__toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "All apps are up-to-date.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("TransitionGroup", {
    staticClass: "apps-list__list-container",
    attrs: {
      name: "apps-list",
      tag: "table"
    }
  }, [_c("tr", {
    key: "app-list-view-header"
  }, [_c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Icon")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Name")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Version")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Level")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Actions")))])])]), _vm._v(" "), _vm._l(_vm.apps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category
      }
    });
  })], 2)] : _vm._e(), _vm._v(" "), _vm.useBundleView ? _c("table", {
    staticClass: "apps-list__list-container"
  }, [_c("tr", {
    key: "app-list-view-header"
  }, [_c("th", {
    attrs: {
      id: "app-table-col-icon"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Icon")))])]), _vm._v(" "), _c("th", {
    attrs: {
      id: "app-table-col-name"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Name")))])]), _vm._v(" "), _c("th", {
    attrs: {
      id: "app-table-col-version"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Version")))])]), _vm._v(" "), _c("th", {
    attrs: {
      id: "app-table-col-level"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Level")))])]), _vm._v(" "), _c("th", {
    attrs: {
      id: "app-table-col-actions"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Actions")))])])]), _vm._v(" "), _vm._l(_vm.bundles, function (bundle) {
    return [_c("tr", {
      key: bundle.id
    }, [_c("th", {
      attrs: {
        id: "app-table-rowgroup-".concat(bundle.id),
        colspan: "5",
        scope: "rowgroup"
      }
    }, [_c("div", {
      staticClass: "apps-list__bundle-heading"
    }, [_c("span", {
      staticClass: "apps-list__bundle-header"
    }, [_vm._v("\n\t\t\t\t\t\t\t\t" + _vm._s(bundle.name) + "\n\t\t\t\t\t\t\t")]), _vm._v(" "), _c("NcButton", {
      attrs: {
        type: "secondary"
      },
      on: {
        click: function ($event) {
          return _vm.toggleBundle(bundle.id);
        }
      }
    }, [_vm._v("\n\t\t\t\t\t\t\t\t" + _vm._s(_vm.t("settings", _vm.bundleToggleText(bundle.id))) + "\n\t\t\t\t\t\t\t")])], 1)])]), _vm._v(" "), _vm._l(_vm.bundleApps(bundle.id), function (app) {
      return _c("AppItem", {
        key: bundle.id + app.id,
        attrs: {
          "use-bundle-view": true,
          headers: "app-table-rowgroup-".concat(bundle.id),
          app: app,
          category: _vm.category
        }
      });
    })];
  })], 2) : _vm._e(), _vm._v(" "), _vm.useAppStoreView ? _c("ul", {
    staticClass: "apps-list__store-container"
  }, _vm._l(_vm.apps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category,
        "list-view": false
      }
    });
  }), 1) : _vm._e()], 2), _vm._v(" "), _c("div", {
    staticClass: "apps-list apps-list--list-view",
    attrs: {
      id: "apps-list-search"
    }
  }, [_c("div", {
    staticClass: "apps-list__list-container"
  }, [_vm.search !== "" && _vm.searchApps.length > 0 ? _c("table", {
    staticClass: "apps-list__list-container"
  }, [_c("caption", {
    staticClass: "apps-list__bundle-header"
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Results from other categories")) + "\n\t\t\t\t")]), _vm._v(" "), _c("tr", {
    key: "app-list-view-header"
  }, [_c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Icon")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Name")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Version")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Level")))])]), _vm._v(" "), _c("th", [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Actions")))])])]), _vm._v(" "), _vm._l(_vm.searchApps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category
      }
    });
  })], 2) : _vm._e()])]), _vm._v(" "), _vm.search !== "" && !_vm.loading && _vm.searchApps.length === 0 && _vm.apps.length === 0 ? _c("div", {
    staticClass: "emptycontent emptycontent-search",
    attrs: {
      id: "apps-list-empty"
    }
  }, [_c("div", {
    staticClass: "icon-settings-dark",
    attrs: {
      id: "app-list-empty-icon"
    }
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No apps found for your version")))])]) : _vm._e()]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.listView ? "tr" : _vm.inline ? "article" : "li", {
    tag: "component",
    staticClass: "app-item",
    class: {
      "app-item--list-view": _vm.listView,
      "app-item--store-view": !_vm.listView,
      "app-item--selected": _vm.isSelected,
      "app-item--with-sidebar": _vm.withSidebar
    }
  }, [_c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-image app-image-icon",
    attrs: {
      headers: _vm.getDataItemHeaders("app-table-col-icon")
    }
  }, [_vm.listView && !_vm.app.preview || !_vm.listView && !_vm.screenshotLoaded ? _c("div", {
    staticClass: "icon-settings-dark"
  }) : _vm.listView && _vm.app.preview ? _c("svg", {
    attrs: {
      width: "32",
      height: "32",
      viewBox: "0 0 32 32"
    }
  }, [_c("image", {
    staticClass: "app-icon",
    attrs: {
      x: "0",
      y: "0",
      width: "32",
      height: "32",
      preserveAspectRatio: "xMinYMin meet",
      "xlink:href": _vm.app.preview
    }
  })]) : _vm._e(), _vm._v(" "), !_vm.listView && _vm.app.screenshot && _vm.screenshotLoaded ? _c("img", {
    attrs: {
      src: _vm.app.screenshot,
      alt: ""
    }
  }) : _vm._e()]), _vm._v(" "), _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-name",
    attrs: {
      headers: _vm.getDataItemHeaders("app-table-col-name")
    }
  }, [_c("router-link", {
    staticClass: "app-name--link",
    attrs: {
      to: {
        name: "apps-details",
        params: {
          category: _vm.category,
          id: _vm.app.id
        }
      },
      "aria-label": _vm.t("settings", "Show details for {appName} app", {
        appName: _vm.app.name
      })
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.app.name) + "\n\t\t")])], 1), _vm._v(" "), !_vm.listView ? _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-summary",
    attrs: {
      headers: _vm.getDataItemHeaders("app-version")
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.app.summary) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.listView ? _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-version",
    attrs: {
      headers: _vm.getDataItemHeaders("app-table-col-version")
    }
  }, [_vm.app.version ? _c("span", [_vm._v(_vm._s(_vm.app.version))]) : _vm.app.appstoreData.releases[0].version ? _c("span", [_vm._v(_vm._s(_vm.app.appstoreData.releases[0].version))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-level",
    attrs: {
      headers: _vm.getDataItemHeaders("app-table-col-level")
    }
  }, [_c("AppLevelBadge", {
    attrs: {
      level: _vm.app.level
    }
  }), _vm._v(" "), _vm.hasRating && !_vm.listView ? _c("AppScore", {
    attrs: {
      score: _vm.app.score
    }
  }) : _vm._e()], 1), _vm._v(" "), !_vm.inline ? _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-actions",
    attrs: {
      headers: _vm.getDataItemHeaders("app-table-col-actions")
    }
  }, [_vm.app.error ? _c("div", {
    staticClass: "warning"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.app.error) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.isLoading ? _c("div", {
    staticClass: "icon icon-loading-small"
  }) : _vm._e(), _vm._v(" "), _vm.app.update ? _c("NcButton", {
    attrs: {
      type: "primary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        return _vm.update(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Update to {update}", {
    update: _vm.app.update
  })) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.canUnInstall ? _c("NcButton", {
    staticClass: "uninstall",
    attrs: {
      type: "tertiary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        return _vm.remove(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Remove")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.active ? _c("NcButton", {
    attrs: {
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        return _vm.disable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Disable")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible) ? _c("NcButton", {
    attrs: {
      title: _vm.enableButtonTooltip,
      "aria-label": _vm.enableButtonTooltip,
      type: "primary",
      disabled: !_vm.app.canInstall || _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        return _vm.enable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.enableButtonText) + "\n\t\t")]) : !_vm.app.active ? _c("NcButton", {
    attrs: {
      title: _vm.forceEnableButtonTooltip,
      "aria-label": _vm.forceEnableButtonTooltip,
      type: "secondary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        return _vm.forceEnable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.forceEnableButtonText) + "\n\t\t")]) : _vm._e()], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _setup.isSupported || _setup.isFeatured ? _c("span", {
    staticClass: "app-level-badge",
    class: {
      "app-level-badge--supported": _setup.isSupported
    },
    attrs: {
      title: _setup.badgeTitle
    }
  }, [_c(_setup.NcIconSvgWrapper, {
    attrs: {
      path: _setup.badgeIcon,
      size: 20,
      inline: ""
    }
  }), _vm._v("\n\t" + _vm._s(_setup.badgeText) + "\n")], 1) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("span", {
    staticClass: "app-score__wrapper",
    attrs: {
      role: "img",
      "aria-label": _vm.title,
      title: _vm.title
    }
  }, [_vm._l(_vm.fullStars, function (index) {
    return _c("NcIconSvgWrapper", {
      key: "full-star-".concat(index),
      attrs: {
        path: _vm.mdiStar,
        inline: ""
      }
    });
  }), _vm._v(" "), _vm.hasHalfStar ? _c("NcIconSvgWrapper", {
    attrs: {
      path: _vm.mdiStarHalfFull,
      inline: ""
    }
  }) : _vm._e(), _vm._v(" "), _vm._l(_vm.emptyStars, function (index) {
    return _c("NcIconSvgWrapper", {
      key: "empty-star-".concat(index),
      attrs: {
        path: _vm.mdiStarOutline,
        inline: ""
      }
    });
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", {
    staticClass: "app-discover"
  }, [_setup.hasError ? _c(_setup.NcEmptyContent, {
    attrs: {
      name: _setup.t("settings", "Nothing to show"),
      description: _setup.t("settings", "Could not load section content from app store.")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiEyeOff,
            size: 64
          }
        })];
      },
      proxy: true
    }], null, false, 638098482)
  }) : _setup.elements.length === 0 ? _c(_setup.NcEmptyContent, {
    attrs: {
      name: _setup.t("settings", "Loading"),
      description: _setup.t("settings", "Fetching the latest news…")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcLoadingIcon, {
          attrs: {
            size: 64
          }
        })];
      },
      proxy: true
    }])
  }) : _vm._l(_setup.elements, function (entry, index) {
    var _entry$id;
    return _c(_setup.getComponent(entry.type), _vm._b({
      key: (_entry$id = entry.id) !== null && _entry$id !== void 0 ? _entry$id : index,
      tag: "component"
    }, "component", entry, false));
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.NcAppSidebarTab, {
    attrs: {
      id: "desc",
      name: _setup.t("settings", "Description"),
      order: 0
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiTextShort
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("div", {
    staticClass: "app-description"
  }, [_c(_setup.Markdown, {
    attrs: {
      text: _vm.app.description,
      "min-heading": 4
    }
  })], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm$app$bugs, _vm$app$bugs2, _vm$app$appstoreData;
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppSidebarTab", {
    attrs: {
      id: "details",
      name: _vm.t("settings", "Details"),
      order: 1
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiTextBox
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("div", {
    staticClass: "app-details"
  }, [_c("div", {
    staticClass: "app-details__actions"
  }, [_vm.app.active && _vm.canLimitToGroups(_vm.app) ? _c("div", {
    staticClass: "app-details__actions-groups"
  }, [_c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.groupCheckedAppsData,
      expression: "groupCheckedAppsData"
    }],
    staticClass: "groups-enable__checkbox checkbox",
    attrs: {
      id: "groups_enable_".concat(_vm.app.id),
      type: "checkbox"
    },
    domProps: {
      value: _vm.app.id,
      checked: Array.isArray(_vm.groupCheckedAppsData) ? _vm._i(_vm.groupCheckedAppsData, _vm.app.id) > -1 : _vm.groupCheckedAppsData
    },
    on: {
      change: [function ($event) {
        var $$a = _vm.groupCheckedAppsData,
          $$el = $event.target,
          $$c = $$el.checked ? true : false;
        if (Array.isArray($$a)) {
          var $$v = _vm.app.id,
            $$i = _vm._i($$a, $$v);
          if ($$el.checked) {
            $$i < 0 && (_vm.groupCheckedAppsData = $$a.concat([$$v]));
          } else {
            $$i > -1 && (_vm.groupCheckedAppsData = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
          }
        } else {
          _vm.groupCheckedAppsData = $$c;
        }
      }, _vm.setGroupLimit]
    }
  }), _vm._v(" "), _c("label", {
    attrs: {
      for: "groups_enable_".concat(_vm.app.id)
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Limit to groups")))]), _vm._v(" "), _c("input", {
    staticClass: "group_select",
    attrs: {
      type: "hidden",
      title: _vm.t("settings", "All"),
      value: ""
    }
  }), _vm._v(" "), _c("br"), _vm._v(" "), _c("label", {
    attrs: {
      for: "limitToGroups"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Limit app usage to groups")))])]), _vm._v(" "), _vm.isLimitedToGroups(_vm.app) ? _c("NcSelect", {
    attrs: {
      "input-id": "limitToGroups",
      options: _vm.groups,
      value: _vm.appGroups,
      limit: 5,
      label: "name",
      multiple: true,
      "close-on-select": false
    },
    on: {
      "option:selected": _vm.addGroupLimitation,
      "option:deselected": _vm.removeGroupLimitation,
      search: _vm.asyncFindGroup
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-details__actions-manage"
  }, [_vm.app.update ? _c("input", {
    staticClass: "update primary",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Update to {version}", {
        version: _vm.app.update
      }),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        return _vm.update(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.app.canUnInstall ? _c("input", {
    staticClass: "uninstall",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Remove"),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        return _vm.remove(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.app.active ? _c("input", {
    staticClass: "enable",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Disable"),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        return _vm.disable(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible) ? _c("input", {
    staticClass: "enable primary",
    attrs: {
      title: _vm.enableButtonTooltip,
      "aria-label": _vm.enableButtonTooltip,
      type: "button",
      value: _vm.enableButtonText,
      disabled: !_vm.app.canInstall || _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        return _vm.enable(_vm.app.id);
      }
    }
  }) : !_vm.app.active && !_vm.app.canInstall ? _c("input", {
    staticClass: "enable force",
    attrs: {
      title: _vm.forceEnableButtonTooltip,
      "aria-label": _vm.forceEnableButtonTooltip,
      type: "button",
      value: _vm.forceEnableButtonText,
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function ($event) {
        return _vm.forceEnable(_vm.app.id);
      }
    }
  }) : _vm._e()])]), _vm._v(" "), _c("ul", {
    staticClass: "app-details__dependencies"
  }, [_vm.app.missingMinOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "This app has no minimum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.missingMaxOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "This app has no maximum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), !_vm.app.canInstall ? _c("li", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "This app cannot be installed because the following dependencies are not fulfilled:")) + "\n\t\t\t\t"), _c("ul", {
    staticClass: "missing-dependencies"
  }, _vm._l(_vm.app.missingDependencies, function (dep, index) {
    return _c("li", {
      key: index
    }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(dep) + "\n\t\t\t\t\t")]);
  }), 0)]) : _vm._e()]), _vm._v(" "), _vm.lastModified ? _c("div", {
    staticClass: "app-details__section"
  }, [_c("h4", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Latest updated")) + "\n\t\t\t")]), _vm._v(" "), _c("NcDateTime", {
    attrs: {
      timestamp: _vm.lastModified
    }
  })], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-details__section"
  }, [_c("h4", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Author")) + "\n\t\t\t")]), _vm._v(" "), _c("p", {
    staticClass: "app-details__authors"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.appAuthors) + "\n\t\t\t")])]), _vm._v(" "), _c("div", {
    staticClass: "app-details__section"
  }, [_c("h4", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Categories")) + "\n\t\t\t")]), _vm._v(" "), _c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.appCategories) + "\n\t\t\t")])]), _vm._v(" "), _vm.externalResources.length > 0 ? _c("div", {
    staticClass: "app-details__section"
  }, [_c("h4", [_vm._v(_vm._s(_vm.t("settings", "Resources")))]), _vm._v(" "), _c("ul", {
    staticClass: "app-details__documentation",
    attrs: {
      "aria-label": _vm.t("settings", "Documentation")
    }
  }, _vm._l(_vm.externalResources, function (resource) {
    return _c("li", {
      key: resource.id
    }, [_c("a", {
      staticClass: "appslink",
      attrs: {
        href: resource.href,
        target: "_blank",
        rel: "noreferrer noopener"
      }
    }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(resource.label) + " ↗\n\t\t\t\t\t")])]);
  }), 0)]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-details__section"
  }, [_c("h4", [_vm._v(_vm._s(_vm.t("settings", "Interact")))]), _vm._v(" "), _c("div", {
    staticClass: "app-details__interact"
  }, [_c("NcButton", {
    attrs: {
      disabled: !_vm.app.bugs,
      href: (_vm$app$bugs = _vm.app.bugs) !== null && _vm$app$bugs !== void 0 ? _vm$app$bugs : "#",
      "aria-label": _vm.t("settings", "Report a bug"),
      title: _vm.t("settings", "Report a bug")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiBug
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      disabled: !_vm.app.bugs,
      href: (_vm$app$bugs2 = _vm.app.bugs) !== null && _vm$app$bugs2 !== void 0 ? _vm$app$bugs2 : "#",
      "aria-label": _vm.t("settings", "Request feature"),
      title: _vm.t("settings", "Request feature")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiFeatureSearch
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), (_vm$app$appstoreData = _vm.app.appstoreData) !== null && _vm$app$appstoreData !== void 0 && _vm$app$appstoreData.discussion ? _c("NcButton", {
    attrs: {
      href: _vm.app.appstoreData.discussion,
      "aria-label": _vm.t("settings", "Ask questions or discuss"),
      title: _vm.t("settings", "Ask questions or discuss")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiTooltipQuestion
          }
        })];
      },
      proxy: true
    }], null, false, 1288192462)
  }) : _vm._e(), _vm._v(" "), !_vm.app.internal ? _c("NcButton", {
    attrs: {
      href: _vm.rateAppUrl,
      "aria-label": _vm.t("settings", "Rate the app"),
      title: _vm.t("settings", "Rate")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiStar
          }
        })];
      },
      proxy: true
    }], null, false, 422450625)
  }) : _vm._e()], 1)])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _setup.hasChangelog ? _c(_setup.NcAppSidebarTab, {
    attrs: {
      id: "changelog",
      name: _setup.t("settings", "Changelog"),
      order: 2
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiClockFast,
            size: 24
          }
        })];
      },
      proxy: true
    }], null, false, 1849836872)
  }, [_vm._v(" "), _vm._l(_vm.app.releases, function (release) {
    return _c("div", {
      key: release.version,
      staticClass: "app-sidebar-tabs__release"
    }, [_c("h2", [_vm._v(_vm._s(release.version))]), _vm._v(" "), _c(_setup.Markdown, {
      staticClass: "app-sidebar-tabs__release-text",
      attrs: {
        text: _setup.createChangelogFromRelease(release)
      }
    })], 1);
  })], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "settings-markdown",
    domProps: {
      innerHTML: _vm._s(_vm.renderMarkdown)
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.NcAppContent, {
    staticClass: "app-settings-content",
    attrs: {
      "page-heading": _setup.appStoreLabel
    }
  }, [_c("h2", {
    staticClass: "app-settings-content__label",
    domProps: {
      textContent: _vm._s(_setup.viewLabel)
    }
  }), _vm._v(" "), _setup.currentCategory === "discover" ? _c(_setup.AppStoreDiscoverSection) : _setup.isLoading ? _c(_setup.NcEmptyContent, {
    staticClass: "empty-content__loading",
    attrs: {
      name: _setup.t("settings", "Loading app list")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcLoadingIcon, {
          attrs: {
            size: 64
          }
        })];
      },
      proxy: true
    }])
  }) : _c(_setup.AppList, {
    attrs: {
      category: _setup.currentCategory
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.NcAppNavigation, {
    attrs: {
      "aria-label": _setup.t("settings", "Apps")
    },
    scopedSlots: _vm._u([{
      key: "list",
      fn: function () {
        return [_c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-discover",
            to: {
              name: "apps-category",
              params: {
                category: "discover"
              }
            },
            name: _setup.APPS_SECTION_ENUM.discover
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.discover
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-installed",
            to: {
              name: "apps-category",
              params: {
                category: "installed"
              }
            },
            name: _setup.APPS_SECTION_ENUM.installed
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.installed
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-enabled",
            to: {
              name: "apps-category",
              params: {
                category: "enabled"
              }
            },
            name: _setup.APPS_SECTION_ENUM.enabled
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.enabled
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-disabled",
            to: {
              name: "apps-category",
              params: {
                category: "disabled"
              }
            },
            name: _setup.APPS_SECTION_ENUM.disabled
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.disabled
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _setup.updateCount > 0 ? _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-updates",
            to: {
              name: "apps-category",
              params: {
                category: "updates"
              }
            },
            name: _setup.APPS_SECTION_ENUM.updates
          },
          scopedSlots: _vm._u([{
            key: "counter",
            fn: function () {
              return [_c(_setup.NcCounterBubble, [_vm._v(_vm._s(_setup.updateCount))])];
            },
            proxy: true
          }, {
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.updates
                }
              })];
            },
            proxy: true
          }], null, false, 2824895104)
        }) : _vm._e(), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-your-bundles",
            to: {
              name: "apps-category",
              params: {
                category: "app-bundles"
              }
            },
            name: _setup.APPS_SECTION_ENUM["app-bundles"]
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.bundles
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _c(_setup.NcAppNavigationSpacer), _vm._v(" "), _setup.appstoreEnabled && _setup.categoriesLoading ? _c("li", {
          staticClass: "categories--loading"
        }, [_c(_setup.NcLoadingIcon, {
          attrs: {
            size: 20,
            "aria-label": _setup.t("settings", "Loading categories")
          }
        })], 1) : _setup.appstoreEnabled && !_setup.categoriesLoading ? [_setup.isSubscribed ? _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-supported",
            to: {
              name: "apps-category",
              params: {
                category: "supported"
              }
            },
            name: _setup.APPS_SECTION_ENUM.supported
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.supported
                }
              })];
            },
            proxy: true
          }], null, false, 613663011)
        }) : _vm._e(), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-category-featured",
            to: {
              name: "apps-category",
              params: {
                category: "featured"
              }
            },
            name: _setup.APPS_SECTION_ENUM.featured
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.APPSTORE_CATEGORY_ICONS.featured
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _vm._l(_setup.categories, function (category) {
          return _c(_setup.NcAppNavigationItem, {
            key: category.id,
            attrs: {
              id: "app-category-".concat(category.id),
              name: category.displayName,
              to: {
                name: "apps-category",
                params: {
                  category: category.id
                }
              }
            },
            scopedSlots: _vm._u([{
              key: "icon",
              fn: function () {
                return [_c(_setup.NcIconSvgWrapper, {
                  attrs: {
                    path: category.icon
                  }
                })];
              },
              proxy: true
            }], null, true)
          });
        })] : _vm._e(), _vm._v(" "), _c(_setup.NcAppNavigationItem, {
          attrs: {
            id: "app-developer-docs",
            name: _setup.t("settings", "Developer documentation ↗"),
            href: _setup.developerDocsUrl
          }
        })];
      },
      proxy: true
    }])
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _setup.showSidebar ? _c(_setup.NcAppSidebar, {
    staticClass: "app-sidebar",
    class: {
      "app-sidebar--with-screenshot": _setup.hasScreenshot
    },
    attrs: {
      active: _setup.activeTab,
      background: _setup.hasScreenshot ? _setup.app.screenshot : undefined,
      compact: !_setup.hasScreenshot,
      name: _setup.app.name,
      title: _setup.app.name,
      subname: _setup.licenseText,
      subtitle: _setup.licenseText
    },
    on: {
      "update:active": function ($event) {
        _setup.activeTab = $event;
      },
      close: _setup.hideAppDetails
    },
    scopedSlots: _vm._u([!_setup.hasScreenshot ? {
      key: "header",
      fn: function () {
        var _setup$appIcon;
        return [_c(_setup.NcIconSvgWrapper, {
          staticClass: "app-sidebar__fallback-icon",
          attrs: {
            svg: (_setup$appIcon = _setup.appIcon) !== null && _setup$appIcon !== void 0 ? _setup$appIcon : "",
            size: 64
          }
        })];
      },
      proxy: true
    } : null, {
      key: "description",
      fn: function () {
        return [_c("div", {
          staticClass: "app-sidebar__badges"
        }, [_c(_setup.AppLevelBadge, {
          attrs: {
            level: _setup.app.level
          }
        }), _vm._v(" "), _setup.hasRating ? _c(_setup.AppScore, {
          attrs: {
            score: _setup.rating
          }
        }) : _vm._e()], 1)];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm._v(" "), _c(_setup.AppDescriptionTab, {
    attrs: {
      app: _setup.app
    }
  }), _vm._v(" "), _c(_setup.AppDetailsTab, {
    attrs: {
      app: _setup.app
    }
  }), _vm._v(" "), _c(_setup.AppReleasesTab, {
    attrs: {
      app: _setup.app
    }
  })], 1) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.apps-list[data-v-6d1e92a4] {
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
}
.apps-list--move[data-v-6d1e92a4] {
  transition: transform 1s;
}
.apps-list #app-list-update-all[data-v-6d1e92a4] {
  margin-left: 10px;
}
.apps-list__toolbar[data-v-6d1e92a4] {
  height: 60px;
  padding: 8px;
  padding-left: 60px;
  width: 100%;
  background-color: var(--color-main-background);
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  align-items: center;
}
.apps-list--list-view[data-v-6d1e92a4] {
  margin-bottom: 100px;
  position: relative;
}
.apps-list__list-container[data-v-6d1e92a4] {
  width: 100%;
}
.apps-list__store-container[data-v-6d1e92a4] {
  display: flex;
  flex-wrap: wrap;
}
.apps-list__bundle-heading[data-v-6d1e92a4] {
  display: flex;
  align-items: center;
  margin: 20px 10px 20px 0;
}
.apps-list__bundle-header[data-v-6d1e92a4] {
  margin: 0 10px 0 50px;
  font-weight: bold;
  font-size: 20px;
  line-height: 30px;
  color: var(--color-text-light);
}
#apps-list-search .app-item h2[data-v-6d1e92a4] {
  margin-bottom: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.app-item[data-v-429da85a] {
  position: relative;
}
.app-item[data-v-429da85a]:hover {
  background-color: var(--color-background-dark);
}
.app-item--list-view[data-v-429da85a] {
  --app-item-padding: calc(var(--default-grid-baseline) * 2);
  --app-item-height: calc(var(--default-clickable-area) + var(--app-item-padding) * 2);
  /* hide app version and level on narrower screens */
  /* Hide actions on a small screen. Click on app opens fill-screen sidebar with the buttons */
}
.app-item--list-view.app-item--selected[data-v-429da85a] {
  background-color: var(--color-background-dark);
}
.app-item--list-view > *[data-v-429da85a] {
  vertical-align: middle;
  border-bottom: 1px solid var(--color-border);
  padding: var(--app-item-padding);
  height: var(--app-item-height);
}
.app-item--list-view .app-image[data-v-429da85a] {
  width: var(--default-clickable-area);
  height: auto;
  text-align: right;
}
.app-item--list-view .app-image-icon svg[data-v-429da85a],
.app-item--list-view .app-image-icon .icon-settings-dark[data-v-429da85a] {
  margin-top: 5px;
  width: 20px;
  height: 20px;
  opacity: 0.5;
  background-size: cover;
  display: inline-block;
}
.app-item--list-view .app-name[data-v-429da85a] {
  padding: 0 var(--app-item-padding);
}
.app-item--list-view .app-name--link[data-v-429da85a] {
  height: var(--app-item-height);
  display: flex;
  align-items: center;
}
.app-item--list-view .app-name--link[data-v-429da85a]::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  height: var(--app-item-height);
}
.app-item--list-view .app-actions[data-v-429da85a] {
  display: flex;
  gap: var(--app-item-padding);
  flex-wrap: wrap;
  justify-content: end;
}
.app-item--list-view .app-actions .icon-loading-small[data-v-429da85a] {
  display: inline-block;
  top: 4px;
  margin-right: 10px;
}
@media only screen and (max-width: 900px) {
.app-item--list-view .app-version[data-v-429da85a],
  .app-item--list-view .app-level[data-v-429da85a] {
    display: none;
}
}
@media only screen and (max-width: 512px) {
.app-item--list-view .app-actions[data-v-429da85a] {
    display: none;
}
}
.app-item--store-view[data-v-429da85a] {
  padding: 30px;
}
.app-item--store-view .app-image-icon .icon-settings-dark[data-v-429da85a] {
  width: 100%;
  height: 150px;
  background-size: 45px;
  opacity: 0.5;
}
.app-item--store-view .app-image-icon svg[data-v-429da85a] {
  position: absolute;
  bottom: 43px;
  /* position halfway vertically */
  width: 64px;
  height: 64px;
  opacity: 0.1;
}
.app-item--store-view .app-name[data-v-429da85a] {
  margin: 5px 0;
}
.app-item--store-view .app-name--link[data-v-429da85a]::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}
.app-item--store-view .app-actions[data-v-429da85a] {
  margin: 10px 0;
}
@media only screen and (min-width: 1601px) {
.app-item--store-view[data-v-429da85a] {
    width: 25%;
}
.app-item--store-view.app-item--with-sidebar[data-v-429da85a] {
    width: 33%;
}
}
@media only screen and (max-width: 1600px) {
.app-item--store-view[data-v-429da85a] {
    width: 25%;
}
.app-item--store-view.app-item--with-sidebar[data-v-429da85a] {
    width: 33%;
}
}
@media only screen and (max-width: 1400px) {
.app-item--store-view[data-v-429da85a] {
    width: 33%;
}
.app-item--store-view.app-item--with-sidebar[data-v-429da85a] {
    width: 50%;
}
}
@media only screen and (max-width: 900px) {
.app-item--store-view[data-v-429da85a] {
    width: 50%;
}
.app-item--store-view.app-item--with-sidebar[data-v-429da85a] {
    width: 100%;
}
}
@media only screen and (max-width: 1024px) {
.app-item--store-view[data-v-429da85a] {
    width: 50%;
}
}
@media only screen and (max-width: 480px) {
.app-item--store-view[data-v-429da85a] {
    width: 100%;
}
}
.app-icon[data-v-429da85a] {
  filter: var(--background-invert-if-bright);
}
.app-image[data-v-429da85a] {
  position: relative;
  height: 150px;
  opacity: 1;
  overflow: hidden;
}
.app-image img[data-v-429da85a] {
  width: 100%;
}
.app-version[data-v-429da85a] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-level-badge[data-v-dbef4182] {
  color: var(--color-text-maxcontrast);
  background-color: transparent;
  border: 1px solid var(--color-text-maxcontrast);
  border-radius: var(--border-radius);
  display: flex;
  flex-direction: row;
  gap: 6px;
  padding: 3px 6px;
  width: fit-content;
}
.app-level-badge--supported[data-v-dbef4182] {
  border-color: var(--color-success);
  color: var(--color-success);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-discover[data-v-2c2ea092] {
  max-width: 1008px; /* 900px + 2x 54px padding for the carousel controls */
  margin-inline: auto;
  padding-inline: 54px;
  /* Padding required to make last element not bound to the bottom */
  padding-block-end: var(--default-clickable-area, 44px);
  display: flex;
  flex-direction: column;
  gap: var(--default-clickable-area, 44px);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-description[data-v-645c86d4] {
  padding: 12px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-details[data-v-564443e0] {
  padding: 20px;
}
.app-details__actions-manage[data-v-564443e0] {
  display: flex;
}
.app-details__actions-manage input[data-v-564443e0] {
  flex: 0 1 auto;
  min-width: 0;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.app-details__authors[data-v-564443e0] {
  color: var(--color-text-maxcontrast);
}
.app-details__section[data-v-564443e0] {
  margin-top: 15px;
}
.app-details__section h4[data-v-564443e0] {
  font-size: 16px;
  font-weight: bold;
  margin-block-end: 5px;
}
.app-details__interact[data-v-564443e0] {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 12px;
}
.app-details__documentation a[data-v-564443e0] {
  text-decoration: underline;
}
.app-details__documentation li[data-v-564443e0] {
  padding-inline-start: 20px;
}
.app-details__documentation li[data-v-564443e0]::before {
  width: 5px;
  height: 5px;
  border-radius: 100%;
  background-color: var(--color-main-text);
  content: "";
  float: inline-start;
  margin-inline-start: -13px;
  position: relative;
  top: 10px;
}
.force[data-v-564443e0] {
  color: var(--color-error);
  border-color: var(--color-error);
  background: var(--color-main-background);
}
.force[data-v-564443e0]:hover,
.force[data-v-564443e0]:active {
  color: var(--color-main-background);
  border-color: var(--color-error) !important;
  background: var(--color-error);
}
.missing-dependencies[data-v-564443e0] {
  list-style: initial;
  list-style-type: initial;
  list-style-position: inside;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-sidebar-tabs__release h2[data-v-2c452a5c] {
  border-bottom: 1px solid var(--color-border);
  font-size: 24px;
}
.app-sidebar-tabs__release-text[data-v-2c452a5c] h3 {
  font-size: 20px;
}
.app-sidebar-tabs__release-text[data-v-2c452a5c] h4 {
  font-size: 17px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.settings-markdown[data-v-11f4a1b0] h1,
.settings-markdown[data-v-11f4a1b0] h2,
.settings-markdown[data-v-11f4a1b0] h3,
.settings-markdown[data-v-11f4a1b0] h4,
.settings-markdown[data-v-11f4a1b0] h5,
.settings-markdown[data-v-11f4a1b0] h6 {
  font-weight: 600;
  line-height: 120%;
  margin-top: 24px;
  margin-bottom: 12px;
  color: var(--color-main-text);
}
.settings-markdown[data-v-11f4a1b0] h1 {
  font-size: 36px;
  margin-top: 48px;
}
.settings-markdown[data-v-11f4a1b0] h2 {
  font-size: 28px;
  margin-top: 48px;
}
.settings-markdown[data-v-11f4a1b0] h3 {
  font-size: 24px;
}
.settings-markdown[data-v-11f4a1b0] h4 {
  font-size: 21px;
}
.settings-markdown[data-v-11f4a1b0] h5 {
  font-size: 17px;
}
.settings-markdown[data-v-11f4a1b0] h6 {
  font-size: var(--default-font-size);
}
.settings-markdown[data-v-11f4a1b0] pre {
  white-space: pre;
  overflow-x: auto;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  padding: 1em 1.3em;
  margin-bottom: 1em;
}
.settings-markdown[data-v-11f4a1b0] p code {
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  padding: 0.1em 0.3em;
}
.settings-markdown[data-v-11f4a1b0] li {
  position: relative;
}
.settings-markdown[data-v-11f4a1b0] ul, .settings-markdown[data-v-11f4a1b0] ol {
  padding-left: 10px;
  margin-left: 10px;
}
.settings-markdown[data-v-11f4a1b0] ul li {
  list-style-type: disc;
}
.settings-markdown[data-v-11f4a1b0] ul > li > ul > li {
  list-style-type: circle;
}
.settings-markdown[data-v-11f4a1b0] ul > li > ul > li ul li {
  list-style-type: square;
}
.settings-markdown[data-v-11f4a1b0] blockquote {
  padding-left: 1em;
  border-left: 4px solid var(--color-primary-element);
  color: var(--color-text-maxcontrast);
  margin-left: 0;
  margin-right: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-sidebar--with-screenshot[data-v-a38ee2fa] .app-sidebar-header__figure {
  background-size: cover;
}
.app-sidebar__fallback-icon[data-v-a38ee2fa] {
  width: 100%;
  height: 100%;
}
.app-sidebar__badges[data-v-a38ee2fa] {
  display: flex;
  flex-direction: row;
  gap: 12px;
}
.app-sidebar__version[data-v-a38ee2fa] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.app-score__wrapper[data-v-0ecce4fc] {
	display: inline-flex;
	color: var(--color-favorite, #a08b00);
> *[data-v-0ecce4fc] {
		vertical-align: text-bottom;
}
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css":
/*!****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `
.empty-content__loading[data-v-6f6912c9] {
	height: 100%;
}
.app-settings-content__label[data-v-6f6912c9] {
	margin-block-start: var(--app-navigation-padding);
	margin-inline-start: calc(var(--default-clickable-area) + var(--app-navigation-padding) * 2);
	min-height: var(--default-clickable-area);
	line-height: var(--default-clickable-area);
	vertical-align: center;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css":
/*!**************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css ***!
  \**************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `
/* The categories-loading indicator */
.categories--loading[data-v-0208f5bd] {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AppList.vue":
/*!**************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppList.vue?vue&type=template&id=6d1e92a4&scoped=true */ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true");
/* harmony import */ var _AppList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppList.vue?vue&type=script&lang=js */ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js");
/* harmony import */ var _AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true */ "./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6d1e92a4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue":
/*!**********************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppItem.vue?vue&type=template&id=429da85a&scoped=true */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true");
/* harmony import */ var _AppItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppItem.vue?vue&type=script&lang=js */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js");
/* harmony import */ var _AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "429da85a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppItem.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppLevelBadge.vue":
/*!****************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppLevelBadge.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true */ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true");
/* harmony import */ var _AppLevelBadge_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppLevelBadge.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss */ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppLevelBadge_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "dbef4182",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppLevelBadge.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true");
/* harmony import */ var _AppScore_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppScore.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts");
/* harmony import */ var _AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppScore_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0ecce4fc",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppScore.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue":
/*!***********************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true */ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true");
/* harmony import */ var _AppStoreDiscoverSection_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppStoreDiscoverSection_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2c2ea092",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true */ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true");
/* harmony import */ var _AppDescriptionTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppDescriptionTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "645c86d4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue":
/*!************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true */ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true");
/* harmony import */ var _AppDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppDetailsTab.vue?vue&type=script&lang=js */ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js");
/* harmony import */ var _AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "564443e0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue":
/*!*************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true */ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true");
/* harmony import */ var _AppReleasesTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppReleasesTab.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppReleasesTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2c452a5c",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue":
/*!***************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true */ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true");
/* harmony import */ var _Markdown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Markdown.vue?vue&type=script&lang=js */ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js");
/* harmony import */ var _Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss */ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Markdown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "11f4a1b0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Markdown.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/SvgFilterMixin.vue":
/*!*********************************************************!*\
  !*** ./apps/settings/src/components/SvgFilterMixin.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SvgFilterMixin_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SvgFilterMixin.vue?vue&type=script&lang=js */ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns
;



/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _SvgFilterMixin_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/SvgFilterMixin.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/AppStore.vue":
/*!**********************************************!*\
  !*** ./apps/settings/src/views/AppStore.vue ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppStore.vue?vue&type=template&id=6f6912c9&scoped=true */ "./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true");
/* harmony import */ var _AppStore_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppStore.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css */ "./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppStore_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6f6912c9",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/AppStore.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/AppStoreNavigation.vue":
/*!********************************************************!*\
  !*** ./apps/settings/src/views/AppStoreNavigation.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true */ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true");
/* harmony import */ var _AppStoreNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css */ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppStoreNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0208f5bd",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/AppStoreNavigation.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/AppStoreSidebar.vue":
/*!*****************************************************!*\
  !*** ./apps/settings/src/views/AppStoreSidebar.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true */ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true");
/* harmony import */ var _AppStoreSidebar_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss */ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppStoreSidebar_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "a38ee2fa",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/AppStoreSidebar.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts ***!
  \***************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLevelBadge.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts":
/*!***********************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts":
/*!**********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppReleasesTab.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStore.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts":
/*!*******************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts":
/*!****************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js":
/*!**************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js":
/*!**********************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetailsTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js":
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SvgFilterMixin.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true":
/*!********************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=template&id=6d1e92a4&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true":
/*!****************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=template&id=429da85a&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true":
/*!**********************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_template_id_dbef4182_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=template&id=dbef4182&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_template_id_2c2ea092_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=template&id=2c2ea092&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true":
/*!**********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_template_id_645c86d4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=template&id=645c86d4&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true":
/*!******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_template_id_564443e0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=template&id=564443e0&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true ***!
  \*******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_template_id_2c452a5c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=template&id=2c452a5c&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true":
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true":
/*!****************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_template_id_6f6912c9_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStore.vue?vue&type=template&id=6f6912c9&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=template&id=6f6912c9&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true":
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_template_id_0208f5bd_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=template&id=0208f5bd&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true":
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_template_id_a38ee2fa_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=template&id=a38ee2fa&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true":
/*!***********************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss ***!
  \*******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss":
/*!*************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss ***!
  \*************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLevelBadge_vue_vue_type_style_index_0_id_dbef4182_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppLevelBadge.vue?vue&type=style&index=0&id=dbef4182&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss":
/*!********************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss ***!
  \********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreDiscoverSection_vue_vue_type_style_index_0_id_2c2ea092_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppStoreDiscoverSection.vue?vue&type=style&index=0&id=2c2ea092&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss":
/*!*************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss ***!
  \*************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDescriptionTab_vue_vue_type_style_index_0_id_645c86d4_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDescriptionTab.vue?vue&type=style&index=0&id=645c86d4&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss":
/*!*********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetailsTab_vue_vue_type_style_index_0_id_564443e0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppDetailsTab.vue?vue&type=style&index=0&id=564443e0&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss":
/*!**********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss ***!
  \**********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppReleasesTab_vue_vue_type_style_index_0_id_2c452a5c_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreSidebar/AppReleasesTab.vue?vue&type=style&index=0&id=2c452a5c&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss ***!
  \**************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreSidebar_vue_vue_type_style_index_0_id_a38ee2fa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreSidebar.vue?vue&type=style&index=0&id=a38ee2fa&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css ***!
  \*******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_style_index_0_id_0ecce4fc_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=style&index=0&id=0ecce4fc&scoped=true&lang=css");


/***/ }),

/***/ "./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStore_vue_vue_type_style_index_0_id_6f6912c9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStore.vue?vue&type=style&index=0&id=6f6912c9&scoped=true&lang=css");


/***/ }),

/***/ "./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css":
/*!****************************************************************************************************************!*\
  !*** ./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css ***!
  \****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppStoreNavigation_vue_vue_type_style_index_0_id_0208f5bd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/AppStoreNavigation.vue?vue&type=style&index=0&id=0208f5bd&scoped=true&lang=css");


/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs":
/*!**********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSidebar_CqER6_dl_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSidebar_CqER6_dl_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSidebar-CqER6-dl.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSidebar-CqER6-dl.mjs");




/***/ }),

/***/ "./node_modules/p-limit/index.js":
/*!***************************************!*\
  !*** ./node_modules/p-limit/index.js ***!
  \***************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ pLimit)
/* harmony export */ });
/* harmony import */ var yocto_queue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! yocto-queue */ "./node_modules/yocto-queue/index.js");

function pLimit(concurrency) {
  if (!((Number.isInteger(concurrency) || concurrency === Number.POSITIVE_INFINITY) && concurrency > 0)) {
    throw new TypeError('Expected `concurrency` to be a number from 1 and up');
  }
  const queue = new yocto_queue__WEBPACK_IMPORTED_MODULE_0__["default"]();
  let activeCount = 0;
  const next = () => {
    activeCount--;
    if (queue.size > 0) {
      queue.dequeue()();
    }
  };
  const run = async (fn, resolve, args) => {
    activeCount++;
    const result = (async () => fn(...args))();
    resolve(result);
    try {
      await result;
    } catch {}
    next();
  };
  const enqueue = (fn, resolve, args) => {
    queue.enqueue(run.bind(undefined, fn, resolve, args));
    (async () => {
      // This function needs to wait until the next microtask before comparing
      // `activeCount` to `concurrency`, because `activeCount` is updated asynchronously
      // when the run function is dequeued and called. The comparison in the if-statement
      // needs to happen asynchronously as well to get an up-to-date value for `activeCount`.
      await Promise.resolve();
      if (activeCount < concurrency && queue.size > 0) {
        queue.dequeue()();
      }
    })();
  };
  const generator = function (fn) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }
    return new Promise(resolve => {
      enqueue(fn, resolve, args);
    });
  };
  Object.defineProperties(generator, {
    activeCount: {
      get: () => activeCount
    },
    pendingCount: {
      get: () => queue.size
    },
    clearQueue: {
      value: () => {
        queue.clear();
      }
    }
  });
  return generator;
}

/***/ }),

/***/ "./node_modules/yocto-queue/index.js":
/*!*******************************************!*\
  !*** ./node_modules/yocto-queue/index.js ***!
  \*******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Queue)
/* harmony export */ });
let _Symbol$iterator;
function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
function _classPrivateFieldSet(receiver, privateMap, value) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
function _classPrivateFieldGet(receiver, privateMap) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
function _classExtractFieldDescriptor(receiver, privateMap, action) { if (!privateMap.has(receiver)) { throw new TypeError("attempted to " + action + " private field on non-instance"); } return privateMap.get(receiver); }
function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*
How it works:
`this.#head` is an instance of `Node` which keeps track of its current value and nests another instance of `Node` that keeps the value that comes after it. When a value is provided to `.enqueue()`, the code needs to iterate through `this.#head`, going deeper and deeper to find the last value. However, iterating through every single item is slow. This problem is solved by saving a reference to the last value as `this.#tail` so that it can reference it to add a new value.
*/

class Node {
  constructor(value) {
    _defineProperty(this, "value", void 0);
    _defineProperty(this, "next", void 0);
    this.value = value;
  }
}
var _head = /*#__PURE__*/new WeakMap();
var _tail = /*#__PURE__*/new WeakMap();
var _size = /*#__PURE__*/new WeakMap();
_Symbol$iterator = Symbol.iterator;
class Queue {
  constructor() {
    _classPrivateFieldInitSpec(this, _head, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(this, _tail, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(this, _size, {
      writable: true,
      value: void 0
    });
    this.clear();
  }
  enqueue(value) {
    var _this$size, _this$size2;
    const node = new Node(value);
    if (_classPrivateFieldGet(this, _head)) {
      _classPrivateFieldGet(this, _tail).next = node;
      _classPrivateFieldSet(this, _tail, node);
    } else {
      _classPrivateFieldSet(this, _head, node);
      _classPrivateFieldSet(this, _tail, node);
    }
    _classPrivateFieldSet(this, _size, (_this$size = _classPrivateFieldGet(this, _size), _this$size2 = _this$size++, _this$size)), _this$size2;
  }
  dequeue() {
    var _this$size3, _this$size4;
    const current = _classPrivateFieldGet(this, _head);
    if (!current) {
      return;
    }
    _classPrivateFieldSet(this, _head, _classPrivateFieldGet(this, _head).next);
    _classPrivateFieldSet(this, _size, (_this$size3 = _classPrivateFieldGet(this, _size), _this$size4 = _this$size3--, _this$size3)), _this$size4;
    return current.value;
  }
  clear() {
    _classPrivateFieldSet(this, _head, undefined);
    _classPrivateFieldSet(this, _tail, undefined);
    _classPrivateFieldSet(this, _size, 0);
  }
  get size() {
    return _classPrivateFieldGet(this, _size);
  }
  *[_Symbol$iterator]() {
    let current = _classPrivateFieldGet(this, _head);
    while (current) {
      yield current.value;
      current = current.next;
    }
  }
}

/***/ }),

/***/ "./node_modules/vue-router/composables.mjs":
/*!*************************************************!*\
  !*** ./node_modules/vue-router/composables.mjs ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isSameRouteLocationParams: () => (/* binding */ isSameRouteLocationParams),
/* harmony export */   onBeforeRouteLeave: () => (/* binding */ onBeforeRouteLeave),
/* harmony export */   onBeforeRouteUpdate: () => (/* binding */ onBeforeRouteUpdate),
/* harmony export */   useLink: () => (/* binding */ useLink),
/* harmony export */   useRoute: () => (/* binding */ useRoute),
/* harmony export */   useRouter: () => (/* binding */ useRouter)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/*!
  * vue-router v3.6.5
  * (c) 2022 Evan You
  * @license MIT
  */


// dev only warn if no current instance

function throwNoCurrentInstance (method) {
  if (!(0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)()) {
    throw new Error(
      ("[vue-router]: Missing current instance. " + method + "() must be called inside <script setup> or setup().")
    )
  }
}

function useRouter () {
  if (true) {
    throwNoCurrentInstance('useRouter');
  }

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)().proxy.$root.$router
}

function useRoute () {
  if (true) {
    throwNoCurrentInstance('useRoute');
  }

  var root = (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)().proxy.$root;
  if (!root._$route) {
    var route = (0,vue__WEBPACK_IMPORTED_MODULE_0__.effectScope)(true).run(function () { return (0,vue__WEBPACK_IMPORTED_MODULE_0__.shallowReactive)(Object.assign({}, root.$router.currentRoute)); }
    );
    root._$route = route;

    root.$router.afterEach(function (to) {
      Object.assign(route, to);
    });
  }

  return root._$route
}

function onBeforeRouteUpdate (guard) {
  if (true) {
    throwNoCurrentInstance('onBeforeRouteUpdate');
  }

  return useFilteredGuard(guard, isUpdateNavigation)
}
function isUpdateNavigation (to, from, depth) {
  var toMatched = to.matched;
  var fromMatched = from.matched;
  return (
    toMatched.length >= depth &&
    toMatched
      .slice(0, depth + 1)
      .every(function (record, i) { return record === fromMatched[i]; })
  )
}

function isLeaveNavigation (to, from, depth) {
  var toMatched = to.matched;
  var fromMatched = from.matched;
  return toMatched.length < depth || toMatched[depth] !== fromMatched[depth]
}

function onBeforeRouteLeave (guard) {
  if (true) {
    throwNoCurrentInstance('onBeforeRouteLeave');
  }

  return useFilteredGuard(guard, isLeaveNavigation)
}

var noop = function () {};
function useFilteredGuard (guard, fn) {
  var instance = (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)();
  var router = useRouter();

  var target = instance.proxy;
  // find the nearest RouterView to know the depth
  while (
    target &&
    target.$vnode &&
    target.$vnode.data &&
    target.$vnode.data.routerViewDepth == null
  ) {
    target = target.$parent;
  }

  var depth =
    target && target.$vnode && target.$vnode.data
      ? target.$vnode.data.routerViewDepth
      : null;

  if (depth != null) {
    var removeGuard = router.beforeEach(function (to, from, next) {
      return fn(to, from, depth) ? guard(to, from, next) : next()
    });

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted)(removeGuard);
    return removeGuard
  }

  return noop
}

/*  */

function guardEvent (e) {
  // don't redirect with control keys
  if (e.metaKey || e.altKey || e.ctrlKey || e.shiftKey) { return }
  // don't redirect when preventDefault called
  if (e.defaultPrevented) { return }
  // don't redirect on right click
  if (e.button !== undefined && e.button !== 0) { return }
  // don't redirect if `target="_blank"`
  if (e.currentTarget && e.currentTarget.getAttribute) {
    var target = e.currentTarget.getAttribute('target');
    if (/\b_blank\b/i.test(target)) { return }
  }
  // this may be a Weex event which doesn't have this method
  if (e.preventDefault) {
    e.preventDefault();
  }
  return true
}

function includesParams (outer, inner) {
  var loop = function ( key ) {
    var innerValue = inner[key];
    var outerValue = outer[key];
    if (typeof innerValue === 'string') {
      if (innerValue !== outerValue) { return { v: false } }
    } else {
      if (
        !Array.isArray(outerValue) ||
        outerValue.length !== innerValue.length ||
        innerValue.some(function (value, i) { return value !== outerValue[i]; })
      ) {
        return { v: false }
      }
    }
  };

  for (var key in inner) {
    var returned = loop( key );

    if ( returned ) return returned.v;
  }

  return true
}

// helpers from vue router 4

function isSameRouteLocationParamsValue (a, b) {
  return Array.isArray(a)
    ? isEquivalentArray(a, b)
    : Array.isArray(b)
      ? isEquivalentArray(b, a)
      : a === b
}

function isEquivalentArray (a, b) {
  return Array.isArray(b)
    ? a.length === b.length && a.every(function (value, i) { return value === b[i]; })
    : a.length === 1 && a[0] === b
}

function isSameRouteLocationParams (a, b) {
  if (Object.keys(a).length !== Object.keys(b).length) { return false }

  for (var key in a) {
    if (!isSameRouteLocationParamsValue(a[key], b[key])) { return false }
  }

  return true
}

function useLink (props) {
  if (true) {
    throwNoCurrentInstance('useLink');
  }

  var router = useRouter();
  var currentRoute = useRoute();

  var resolvedRoute = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return router.resolve((0,vue__WEBPACK_IMPORTED_MODULE_0__.unref)(props.to), currentRoute); });

  var activeRecordIndex = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
    var route = resolvedRoute.value.route;
    var matched = route.matched;
    var length = matched.length;
    var routeMatched = matched[length - 1];
    var currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) { return -1 }
    var index = currentMatched.indexOf(routeMatched);
    if (index > -1) { return index }
    // possible parent record
    var parentRecord = currentMatched[currentMatched.length - 2];

    return (
      // we are dealing with nested routes
      length > 1 &&
        // if the parent and matched route have the same path, this link is
        // referring to the empty child. Or we currently are on a different
        // child of the same parent
        parentRecord && parentRecord === routeMatched.parent
    )
  });

  var isActive = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(
    function () { return activeRecordIndex.value > -1 &&
      includesParams(currentRoute.params, resolvedRoute.value.route.params); }
  );
  var isExactActive = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(
    function () { return activeRecordIndex.value > -1 &&
      activeRecordIndex.value === currentRoute.matched.length - 1 &&
      isSameRouteLocationParams(currentRoute.params, resolvedRoute.value.route.params); }
  );

  var navigate = function (e) {
    var href = resolvedRoute.value.route;
    if (guardEvent(e)) {
      return props.replace
        ? router.replace(href)
        : router.push(href)
    }
    return Promise.resolve()
  };

  return {
    href: (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return resolvedRoute.value.href; }),
    route: (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return resolvedRoute.value.route; }),
    isExactActive: isExactActive,
    isActive: isActive,
    navigate: navigate
  }
}




/***/ })

}]);
//# sourceMappingURL=settings-apps-view-settings-apps-view.js.map?v=b5d303f73eecf2ad87ca