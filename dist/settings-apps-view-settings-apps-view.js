"use strict";
(globalThis["webpackChunknextcloud"] = globalThis["webpackChunknextcloud"] || []).push([["settings-apps-view"],{

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
      return false;
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
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('core/navigation', 2) + '/apps?format=json').then(({
    data
  }) => {
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PrefixMixin.vue */ "./apps/settings/src/components/PrefixMixin.vue");
/* harmony import */ var _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Markdown.vue */ "./apps/settings/src/components/Markdown.vue");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppDetails',
  components: {
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0___default()),
    Markdown: _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__["default"], _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    app: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      groupCheckedAppsData: false
    };
  },
  computed: {
    appstoreUrl() {
      return `https://apps.nextcloud.com/apps/${this.app.id}`;
    },
    licence() {
      if (this.app.licence) {
        return t('settings', '{license}-licensed', {
          license: ('' + this.app.licence).toUpperCase()
        });
      }
      return null;
    },
    author() {
      if (typeof this.app.author === 'string') {
        return [{
          '@value': this.app.author
        }];
      }
      if (this.app.author['@value']) {
        return [this.app.author];
      }
      return this.app.author;
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppList/AppItem.vue */ "./apps/settings/src/components/AppList/AppItem.vue");
/* harmony import */ var _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PrefixMixin.vue */ "./apps/settings/src/components/PrefixMixin.vue");
/* harmony import */ var p_limit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-limit */ "./node_modules/p-limit/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppList',
  components: {
    AppItem: _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_1__["default"]],
  props: ['category', 'app', 'search'],
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
      return this.$store.getters.getServerData.bundles.filter(bundle => this.bundleApps(bundle.id).length > 0);
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
  methods: {
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../SvgFilterMixin.vue */ "./apps/settings/src/components/SvgFilterMixin.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__);




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppItem',
  components: {
    AppScore: _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__["default"], _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    app: {},
    category: {},
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
      image.onload = e => {
        this.screenshotLoaded = true;
      };
      image.src = this.app.screenshot;
    }
  },
  watchers: {},
  methods: {
    async showAppDetails(event) {
      if (event.currentTarget.tagName === 'INPUT' || event.currentTarget.tagName === 'A') {
        return;
      }
      try {
        await this.$router.push({
          name: 'apps-details',
          params: {
            category: this.category,
            id: this.app.id
          }
        });
      } catch (e) {
        // we already view this app
      }
    },
    prefix(prefix, content) {
      return prefix + '_' + content;
    },
    getDataItemHeaders(columnName) {
      return this.useBundleView ? [this.headers, columnName].join(' ') : null;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AppScore',
  props: ['score'],
  computed: {
    scoreImage() {
      const score = Math.round(this.score * 10);
      const imageName = 'rating/s' + score + '.svg';
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.imagePath)('core', imageName);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************/
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PrefixMixin',
  methods: {
    prefix(prefix, content) {
      return prefix + '_' + content;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************/
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
      return `url(#${this.filterId})`;
    }
  },
  mounted() {
    this.filterId = 'invertIconApps-' + Math.random().toString(36).substring(2);
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationSpacer.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSpacer.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var vue_material_design_icons_StarShooting_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/StarShooting.vue */ "./node_modules/vue-material-design-icons/StarShooting.vue");
/* harmony import */ var _components_AppList_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/AppList.vue */ "./apps/settings/src/components/AppList.vue");
/* harmony import */ var _components_AppDetails_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../components/AppDetails.vue */ "./apps/settings/src/components/AppDetails.vue");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../components/AppList/AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _components_Markdown_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../components/Markdown.vue */ "./apps/settings/src/components/Markdown.vue");
/* harmony import */ var _constants_AppsConstants_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./../constants/AppsConstants.js */ "./apps/settings/src/constants/AppsConstants.js");


















vue__WEBPACK_IMPORTED_MODULE_17__["default"].use((vue_localstorage__WEBPACK_IMPORTED_MODULE_1___default()));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Apps',
  APPS_SECTION_ENUM: _constants_AppsConstants_js__WEBPACK_IMPORTED_MODULE_16__.APPS_SECTION_ENUM,
  components: {
    NcAppContent: (_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default()),
    AppDetails: _components_AppDetails_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    AppList: _components_AppList_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    IconStarShooting: vue_material_design_icons_StarShooting_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcAppNavigation: (_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcAppNavigationSpacer: (_nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcCounterBubble: (_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8___default()),
    AppScore: _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    NcAppSidebar: (_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcAppSidebarTab: (_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7___default()),
    NcContent: (_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9___default()),
    Markdown: _components_Markdown_vue__WEBPACK_IMPORTED_MODULE_15__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_13__["default"]],
  props: {
    category: {
      type: String,
      default: 'installed'
    },
    id: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      searchQuery: '',
      screenshotLoaded: false
    };
  },
  computed: {
    loading() {
      return this.$store.getters.loading('categories');
    },
    loadingList() {
      return this.$store.getters.loading('list');
    },
    app() {
      return this.apps.find(app => app.id === this.id);
    },
    categories() {
      return this.$store.getters.getCategories;
    },
    apps() {
      return this.$store.getters.getAllApps;
    },
    updateCount() {
      return this.$store.getters.getUpdateCount;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    hasRating() {
      return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
    },
    // sidebar app binding
    appSidebar() {
      const authorName = xmlNode => {
        if (xmlNode['@value']) {
          // Complex node (with email or homepage attribute)
          return xmlNode['@value'];
        }

        // Simple text node
        return xmlNode;
      };
      const author = Array.isArray(this.app.author) ? this.app.author.map(authorName).join(', ') : authorName(this.app.author);
      const license = t('settings', '{license}-licensed', {
        license: ('' + this.app.licence).toUpperCase()
      });
      const subname = t('settings', 'by {author}\n{license}', {
        author,
        license
      });
      return {
        background: this.app.screenshot && this.screenshotLoaded ? this.app.screenshot : this.app.preview,
        compact: !(this.app.screenshot && this.screenshotLoaded),
        name: this.app.name,
        subname
      };
    },
    changelog() {
      return release => release.translations.en.changelog;
    },
    /**
     * Check if the current instance has a support subscription from the Nextcloud GmbH
     */
    isSubscribed() {
      // For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
      return this.apps.some(app => app.level === 300);
    }
  },
  watch: {
    category() {
      this.searchQuery = '';
    },
    app() {
      this.screenshotLoaded = false;
      if (this.app?.releases && this.app?.screenshot) {
        const image = new Image();
        image.onload = e => {
          this.screenshotLoaded = true;
        };
        image.src = this.app.screenshot;
      }
    }
  },
  beforeMount() {
    this.$store.dispatch('getCategories', {
      shouldRefetchCategories: true
    });
    this.$store.dispatch('getAllApps');
    this.$store.dispatch('getGroups', {
      offset: 0,
      limit: 5
    });
    this.$store.commit('setUpdateCount', this.$store.getters.getServerData.updateCount);
  },
  mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    setSearch({
      query
    }) {
      this.searchQuery = query;
    },
    resetSearch() {
      this.searchQuery = '';
    },
    hideAppDetails() {
      this.$router.push({
        name: 'apps-category',
        params: {
          category: this.category
        }
      });
    },
    openDeveloperDocumentation() {
      window.open(this.settings.developerDocumentation);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
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
      id: _vm.prefix("groups_enable", _vm.app.id),
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
      for: _vm.prefix("groups_enable", _vm.app.id)
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
  }, [_vm.app.missingMinOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app has no minimum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.missingMaxOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app has no maximum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), !_vm.app.canInstall ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app cannot be installed because the following dependencies are not fulfilled:")) + "\n\t\t\t"), _c("ul", {
    staticClass: "missing-dependencies"
  }, _vm._l(_vm.app.missingDependencies, function (dep, index) {
    return _c("li", {
      key: index
    }, [_vm._v("\n\t\t\t\t\t" + _vm._s(dep) + "\n\t\t\t\t")]);
  }), 0)]) : _vm._e()]), _vm._v(" "), _c("p", {
    staticClass: "app-details__documentation"
  }, [!_vm.app.internal ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.appstoreUrl,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "View in store")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.website ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.website,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Visit website")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.bugs ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.bugs,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Report a bug")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.user ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.user,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "User documentation")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.admin ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.admin,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Admin documentation")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.developer ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.developer,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Developer documentation")) + " ↗")]) : _vm._e()]), _vm._v(" "), _c("Markdown", {
    staticClass: "app-details__description",
    attrs: {
      text: _vm.app.description
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true& ***!
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
    attrs: {
      id: "app-content-inner"
    }
  }, [_c("div", {
    staticClass: "apps-list",
    class: {
      installed: _vm.useBundleView || _vm.useListView,
      store: _vm.useAppStoreView
    },
    attrs: {
      id: "apps-list"
    }
  }, [_vm.useListView ? [_vm.showUpdateAll ? _c("div", {
    staticClass: "toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.n("settings", "%n app has an update available", "%n apps have an update available", _vm.counter)) + "\n\t\t\t\t"), _vm.showUpdateAll ? _c("NcButton", {
    attrs: {
      id: "app-list-update-all",
      type: "primary"
    },
    on: {
      click: _vm.updateAll
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.n("settings", "Update", "Update all", _vm.counter)) + "\n\t\t\t\t")]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), !_vm.showUpdateAll ? _c("div", {
    staticClass: "toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "All apps are up-to-date.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("transition-group", {
    staticClass: "apps-list-container",
    attrs: {
      name: "app-list",
      tag: "table"
    }
  }, [_c("tr", {
    key: "app-list-view-header",
    staticClass: "apps-header"
  }, [_c("th", {
    staticClass: "app-image"
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Icon")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-name"
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Name")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-version"
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Version")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-level"
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Level")))])]), _vm._v(" "), _c("th", {
    staticClass: "actions"
  }, [_c("span", {
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
    staticClass: "apps-list-container"
  }, [_c("tr", {
    key: "app-list-view-header",
    staticClass: "apps-header"
  }, [_c("th", {
    staticClass: "app-image",
    attrs: {
      id: "app-table-col-icon"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Icon")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-name",
    attrs: {
      id: "app-table-col-name"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Name")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-version",
    attrs: {
      id: "app-table-col-version"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Version")))])]), _vm._v(" "), _c("th", {
    staticClass: "app-level",
    attrs: {
      id: "app-table-col-level"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Level")))])]), _vm._v(" "), _c("th", {
    staticClass: "actions",
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
        id: `app-table-rowgroup-${bundle.id}`,
        colspan: "5",
        scope: "rowgroup"
      }
    }, [_c("div", {
      staticClass: "app-bundle-heading"
    }, [_c("span", {
      staticClass: "app-bundle-header"
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
          headers: `app-table-rowgroup-${bundle.id}`,
          app: app,
          category: _vm.category
        }
      });
    })];
  })], 2) : _vm._e(), _vm._v(" "), _vm.useAppStoreView ? _c("ul", {
    staticClass: "apps-store-view"
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
    staticClass: "apps-list installed",
    attrs: {
      id: "apps-list-search"
    }
  }, [_c("div", {
    staticClass: "apps-list-container"
  }, [_vm.search !== "" && _vm.searchApps.length > 0 ? [_c("div", {
    staticClass: "section"
  }, [_c("div"), _vm._v(" "), _c("td", {
    attrs: {
      colspan: "5"
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("settings", "Results from other categories")))])])]), _vm._v(" "), _vm._l(_vm.searchApps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category
      }
    });
  })] : _vm._e()], 2)]), _vm._v(" "), _vm.search !== "" && !_vm.loading && _vm.searchApps.length === 0 && _vm.apps.length === 0 ? _c("div", {
    staticClass: "emptycontent emptycontent-search",
    attrs: {
      id: "apps-list-empty"
    }
  }, [_c("div", {
    staticClass: "icon-settings-dark",
    attrs: {
      id: "app-list-empty-icon"
    }
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No apps found for your version")))])]) : _vm._e(), _vm._v(" "), _c("div", {
    attrs: {
      id: "searchresults"
    }
  })]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.listView ? `tr` : `li`, {
    tag: "component",
    staticClass: "section",
    class: {
      selected: _vm.isSelected
    },
    on: {
      click: _vm.showAppDetails
    }
  }, [_c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-image app-image-icon",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-table-col-icon`)
    },
    on: {
      click: _vm.showAppDetails
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
      width: "100%"
    }
  }) : _vm._e()]), _vm._v(" "), _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-name",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-table-col-name`)
    },
    on: {
      click: _vm.showAppDetails
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.app.name) + "\n\t")]), _vm._v(" "), !_vm.listView ? _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-summary",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-version`)
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.app.summary) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.listView ? _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-version",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-table-col-version`)
    }
  }, [_vm.app.version ? _c("span", [_vm._v(_vm._s(_vm.app.version))]) : _vm.app.appstoreData.releases[0].version ? _c("span", [_vm._v(_vm._s(_vm.app.appstoreData.releases[0].version))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "app-level",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-table-col-level`)
    }
  }, [_vm.app.level === 300 ? _c("span", {
    staticClass: "supported icon-checkmark-color",
    attrs: {
      title: _vm.t("settings", "This app is supported via your current Nextcloud subscription."),
      "aria-label": _vm.t("settings", "This app is supported via your current Nextcloud subscription.")
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Supported")))]) : _vm._e(), _vm._v(" "), _vm.app.level === 200 ? _c("span", {
    staticClass: "official icon-checkmark",
    attrs: {
      title: _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use."),
      "aria-label": _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use.")
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Featured")))]) : _vm._e(), _vm._v(" "), _vm.hasRating && !_vm.listView ? _c("AppScore", {
    attrs: {
      score: _vm.app.score
    }
  }) : _vm._e()], 1), _vm._v(" "), _c(_vm.dataItemTag, {
    tag: "component",
    staticClass: "actions",
    attrs: {
      headers: _vm.getDataItemHeaders(`app-table-col-actions`)
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.forceEnableButtonText) + "\n\t\t")]) : _vm._e()], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&":
/*!***************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("img", {
    staticClass: "app-score-image",
    attrs: {
      src: _vm.scoreImage
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcContent", {
    class: {
      "with-app-sidebar": _vm.app
    },
    attrs: {
      "app-name": "settings",
      "content-class": {
        "icon-loading": _vm.loadingList
      },
      "navigation-class": {
        "icon-loading": _vm.loading
      }
    }
  }, [_c("NcAppNavigation", {
    scopedSlots: _vm._u([{
      key: "list",
      fn: function () {
        return [_c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-your-apps",
            to: {
              name: "apps"
            },
            exact: true,
            icon: "icon-category-installed",
            name: _vm.t("settings", "Your apps")
          }
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-enabled",
            to: {
              name: "apps-category",
              params: {
                category: "enabled"
              }
            },
            icon: "icon-category-enabled",
            name: _vm.$options.APPS_SECTION_ENUM.enabled
          }
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-disabled",
            to: {
              name: "apps-category",
              params: {
                category: "disabled"
              }
            },
            icon: "icon-category-disabled",
            name: _vm.$options.APPS_SECTION_ENUM.disabled
          }
        }), _vm._v(" "), _vm.updateCount > 0 ? _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-updates",
            to: {
              name: "apps-category",
              params: {
                category: "updates"
              }
            },
            icon: "icon-download",
            name: _vm.$options.APPS_SECTION_ENUM.updates
          },
          scopedSlots: _vm._u([{
            key: "counter",
            fn: function () {
              return [_c("NcCounterBubble", [_vm._v(_vm._s(_vm.updateCount))])];
            },
            proxy: true
          }], null, false, 54487302)
        }) : _vm._e(), _vm._v(" "), _vm.isSubscribed ? _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-supported",
            to: {
              name: "apps-category",
              params: {
                category: "supported"
              }
            },
            name: _vm.$options.APPS_SECTION_ENUM.supported
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("IconStarShooting", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 704374136)
        }) : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-your-bundles",
            to: {
              name: "apps-category",
              params: {
                category: "app-bundles"
              }
            },
            icon: "icon-category-app-bundles",
            name: _vm.$options.APPS_SECTION_ENUM["app-bundles"]
          }
        }), _vm._v(" "), _c("NcAppNavigationSpacer"), _vm._v(" "), _vm.settings.appstoreEnabled ? [_c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-featured",
            to: {
              name: "apps-category",
              params: {
                category: "featured"
              }
            },
            icon: "icon-favorite",
            name: _vm.$options.APPS_SECTION_ENUM.featured
          }
        }), _vm._v(" "), _vm._l(_vm.categories, function (cat) {
          return _c("NcAppNavigationItem", {
            key: "icon-category-" + cat.ident,
            attrs: {
              icon: "icon-category-" + cat.ident,
              to: {
                name: "apps-category",
                params: {
                  category: cat.ident
                }
              },
              name: cat.displayName
            }
          });
        })] : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-developer-docs",
            name: _vm.t("settings", "Developer documentation") + " ↗"
          },
          on: {
            click: _vm.openDeveloperDocumentation
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _c("NcAppContent", {
    staticClass: "app-settings-content",
    class: {
      "icon-loading": _vm.loadingList
    }
  }, [_c("AppList", {
    attrs: {
      category: _vm.category,
      app: _vm.app,
      search: _vm.searchQuery
    }
  })], 1), _vm._v(" "), _vm.id && _vm.app ? _c("NcAppSidebar", _vm._b({
    class: {
      "app-sidebar--without-background": !_vm.appSidebar.background
    },
    on: {
      close: _vm.hideAppDetails
    },
    scopedSlots: _vm._u([!_vm.appSidebar.background ? {
      key: "header",
      fn: function () {
        return [_c("div", {
          staticClass: "app-sidebar-header__figure--default-app-icon icon-settings-dark"
        })];
      },
      proxy: true
    } : null, {
      key: "description",
      fn: function () {
        return [_vm.app.level === 300 || _vm.app.level === 200 || _vm.hasRating ? _c("div", {
          staticClass: "app-level"
        }, [_vm.app.level === 300 ? _c("span", {
          staticClass: "supported icon-checkmark-color",
          attrs: {
            title: _vm.t("settings", "This app is supported via your current Nextcloud subscription.")
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Supported")))]) : _vm._e(), _vm._v(" "), _vm.app.level === 200 ? _c("span", {
          staticClass: "official icon-checkmark",
          attrs: {
            title: _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use.")
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Featured")))]) : _vm._e(), _vm._v(" "), _vm.hasRating ? _c("AppScore", {
          attrs: {
            score: _vm.app.appstoreData.ratingOverall
          }
        }) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _c("div", {
          staticClass: "app-version"
        }, [_c("p", [_vm._v(_vm._s(_vm.app.version))])])];
      },
      proxy: true
    }], null, true)
  }, "NcAppSidebar", _vm.appSidebar, false), [_vm._v(" "), _vm._v(" "), _c("NcAppSidebarTab", {
    attrs: {
      id: "desc",
      icon: "icon-category-office",
      name: _vm.t("settings", "Details"),
      order: 0
    }
  }, [_c("AppDetails", {
    attrs: {
      app: _vm.app
    }
  })], 1), _vm._v(" "), _vm.app.appstoreData && _vm.app.releases[0].translations.en.changelog ? _c("NcAppSidebarTab", {
    attrs: {
      id: "desca",
      icon: "icon-category-organization",
      name: _vm.t("settings", "Changelog"),
      order: 1
    }
  }, _vm._l(_vm.app.releases, function (release) {
    return _c("div", {
      key: release.version,
      staticClass: "app-sidebar-tabs__release"
    }, [_c("h2", [_vm._v(_vm._s(release.version))]), _vm._v(" "), _vm.changelog(release) ? _c("Markdown", {
      attrs: {
        text: _vm.changelog(release)
      }
    }) : _vm._e()], 1);
  }), 0) : _vm._e()], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-details[data-v-59a92e62] {
  padding: 20px;
}
.app-details__actions-manage[data-v-59a92e62] {
  display: flex;
}
.app-details__actions-manage input[data-v-59a92e62] {
  flex: 0 1 auto;
  min-width: 0;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.app-details__dependencies[data-v-59a92e62] {
  opacity: 0.7;
}
.app-details__documentation[data-v-59a92e62] {
  padding-top: 20px;
}
.app-details__documentation a.appslink[data-v-59a92e62] {
  display: block;
}
.app-details__description[data-v-59a92e62] {
  padding-top: 20px;
}
.force[data-v-59a92e62] {
  color: var(--color-error);
  border-color: var(--color-error);
  background: var(--color-main-background);
}
.force[data-v-59a92e62]:hover,
.force[data-v-59a92e62]:active {
  color: var(--color-main-background);
  border-color: var(--color-error) !important;
  background: var(--color-error);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& ***!
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-bundle-heading[data-v-6d1e92a4] {
  display: flex;
  align-items: center;
  margin: 20px 10px 20px 0;
}
.app-bundle-header[data-v-6d1e92a4] {
  margin: 0 10px 0 50px;
  font-weight: bold;
  font-size: 20px;
  line-height: 30px;
  color: var(--color-text-light);
}
.apps-store-view[data-v-6d1e92a4] {
  display: flex;
  flex-wrap: wrap;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-sidebar[data-v-d3714d0a]:not(.app-sidebar--without-background) :not(.app-sidebar-header--compact) .app-sidebar-header__figure {
  background-size: cover;
}
.app-sidebar[data-v-d3714d0a]:not(.app-sidebar--without-background) .app-sidebar-header--compact .app-sidebar-header__figure {
  background-size: 32px;
  filter: var(--background-invert-if-bright);
}
.app-sidebar[data-v-d3714d0a] .app-sidebar-header__description .app-version {
  padding-left: 10px;
}
.app-sidebar[data-v-d3714d0a].app-sidebar--without-background .app-sidebar-header__figure {
  display: flex;
  align-items: center;
  justify-content: center;
}
.app-sidebar[data-v-d3714d0a].app-sidebar--without-background .app-sidebar-header__figure--default-app-icon {
  width: 32px;
  height: 32px;
  background-size: 32px;
}
.app-sidebar[data-v-d3714d0a] .app-sidebar-header__desc .app-sidebar-header__subtitle {
  overflow: visible !important;
  height: auto;
  white-space: normal !important;
  line-height: 16px;
}
.app-sidebar[data-v-d3714d0a] .app-sidebar-header__action {
  margin: 0 20px;
}
.app-sidebar[data-v-d3714d0a] .app-sidebar-header__action input {
  margin: 3px;
}
.app-navigation[data-v-d3714d0a] button.app-navigation-toggle {
  top: 8px;
  right: -8px;
}
.app-sidebar-tabs__release h2[data-v-d3714d0a] {
  border-bottom: 1px solid var(--color-border);
}
.app-sidebar-tabs__release[data-v-d3714d0a] h3 {
  font-size: 20px;
}
.app-sidebar-tabs__release[data-v-d3714d0a] h4 {
  font-size: 17px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
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
.app-icon[data-v-429da85a] {
	filter: var(--background-invert-if-bright);
}
.actions[data-v-429da85a] {
	display: flex !important;
	gap: 8px;
	flex-wrap: wrap;
	justify-content: end;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue":
/*!*****************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& */ "./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&");
/* harmony import */ var _AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "59a92e62",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppDetails.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

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
/* harmony import */ var _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppList.vue?vue&type=template&id=6d1e92a4&scoped=true& */ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true&");
/* harmony import */ var _AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppList.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& */ "./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppItem.vue?vue&type=template&id=429da85a&scoped=true& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&");
/* harmony import */ var _AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppItem.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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

/***/ "./apps/settings/src/components/AppList/AppScore.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue?vue&type=template&id=0ecce4fc& */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&");
/* harmony import */ var _AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppScore.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppScore.vue"
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
/* harmony import */ var _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& */ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&");
/* harmony import */ var _Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Markdown.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&");
/* harmony import */ var _Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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

/***/ "./apps/settings/src/components/PrefixMixin.vue":
/*!******************************************************!*\
  !*** ./apps/settings/src/components/PrefixMixin.vue ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PrefixMixin.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns
;



/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PrefixMixin.vue"
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
/* harmony import */ var _SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SvgFilterMixin.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns
;



/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
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

/***/ "./apps/settings/src/views/Apps.vue":
/*!******************************************!*\
  !*** ./apps/settings/src/views/Apps.vue ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Apps.vue?vue&type=template&id=d3714d0a&scoped=true& */ "./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&");
/* harmony import */ var _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Apps.vue?vue&type=script&lang=js& */ "./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&");
/* harmony import */ var _Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "d3714d0a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/Apps.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&":
/*!***********************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PrefixMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&":
/*!**********************************************************************************!*\
  !*** ./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SvgFilterMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true&":
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true& ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=template&id=6d1e92a4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true& ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=template&id=429da85a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&":
/*!******************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc& ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=template&id=0ecce4fc& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true& ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=template&id=d3714d0a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_style_index_0_id_6d1e92a4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=style&index=0&id=6d1e92a4&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
  \*******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");


/***/ })

}]);
//# sourceMappingURL=settings-apps-view-settings-apps-view.js.map?v=560eade62bdb5e42d578