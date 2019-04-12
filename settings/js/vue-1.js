(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[1],{

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appDetails.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js");
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_multiselect__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! marked */ "./node_modules/marked/lib/marked.js");
/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(marked__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _appList_appScore__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./appList/appScore */ "./settings/src/components/appList/appScore.vue");
/* harmony import */ var _appManagement__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./appManagement */ "./settings/src/components/appManagement.vue");
/* harmony import */ var _prefixMixin__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./prefixMixin */ "./settings/src/components/prefixMixin.vue");
/* harmony import */ var _svgFilterMixin__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./svgFilterMixin */ "./settings/src/components/svgFilterMixin.vue");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//







/* harmony default export */ __webpack_exports__["default"] = ({
  mixins: [_appManagement__WEBPACK_IMPORTED_MODULE_4__["default"], _prefixMixin__WEBPACK_IMPORTED_MODULE_5__["default"], _svgFilterMixin__WEBPACK_IMPORTED_MODULE_6__["default"]],
  name: 'appDetails',
  props: ['category', 'app'],
  components: {
    Multiselect: vue_multiselect__WEBPACK_IMPORTED_MODULE_0___default.a,
    AppScore: _appList_appScore__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  data: function data() {
    return {
      groupCheckedAppsData: false
    };
  },
  mounted: function mounted() {
    if (this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  },
  methods: {
    hideAppDetails: function hideAppDetails() {
      this.$router.push({
        name: 'apps-category',
        params: {
          category: this.category
        }
      });
    }
  },
  computed: {
    appstoreUrl: function appstoreUrl() {
      return "https://apps.nextcloud.com/apps/".concat(this.app.id);
    },
    licence: function licence() {
      if (this.app.licence) {
        return t('settings', '{license}-licensed', {
          license: ('' + this.app.licence).toUpperCase()
        });
      }

      return null;
    },
    hasRating: function hasRating() {
      return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
    },
    author: function author() {
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
    appGroups: function appGroups() {
      return this.app.groups.map(function (group) {
        return {
          id: group,
          name: group
        };
      });
    },
    groups: function groups() {
      return this.$store.getters.getGroups.filter(function (group) {
        return group.id !== 'disabled';
      }).sort(function (a, b) {
        return a.name.localeCompare(b.name);
      });
    },
    renderMarkdown: function renderMarkdown() {
      var renderer = new marked__WEBPACK_IMPORTED_MODULE_1___default.a.Renderer();

      renderer.link = function (href, title, text) {
        try {
          var prot = decodeURIComponent(unescape(href)).replace(/[^\w:]/g, '').toLowerCase();
        } catch (e) {
          return '';
        }

        if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
          return '';
        }

        var out = '<a href="' + href + '" rel="noreferrer noopener"';

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

      return dompurify__WEBPACK_IMPORTED_MODULE_2___default.a.sanitize(marked__WEBPACK_IMPORTED_MODULE_1___default()(this.app.description.trim(), {
        renderer: renderer,
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
        ALLOWED_TAGS: ['strong', 'p', 'a', 'ul', 'ol', 'li', 'em', 'del', 'blockquote']
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appList_appItem__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appList/appItem */ "./settings/src/components/appList/appItem.vue");
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js");
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_multiselect__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _prefixMixin__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./prefixMixin */ "./settings/src/components/prefixMixin.vue");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'appList',
  mixins: [_prefixMixin__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: ['category', 'app', 'search'],
  components: {
    Multiselect: vue_multiselect__WEBPACK_IMPORTED_MODULE_1___default.a,
    appItem: _appList_appItem__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  computed: {
    loading: function loading() {
      return this.$store.getters.loading('list');
    },
    apps: function apps() {
      var _this = this;

      var apps = this.$store.getters.getAllApps.filter(function (app) {
        return app.name.toLowerCase().search(_this.search.toLowerCase()) !== -1;
      }).sort(function (a, b) {
        var sortStringA = '' + (a.active ? 0 : 1) + (a.update ? 0 : 1) + a.name;
        var sortStringB = '' + (b.active ? 0 : 1) + (b.update ? 0 : 1) + b.name;
        return OC.Util.naturalSortCompare(sortStringA, sortStringB);
      });

      if (this.category === 'installed') {
        return apps.filter(function (app) {
          return app.installed;
        });
      }

      if (this.category === 'enabled') {
        return apps.filter(function (app) {
          return app.active && app.installed;
        });
      }

      if (this.category === 'disabled') {
        return apps.filter(function (app) {
          return !app.active && app.installed;
        });
      }

      if (this.category === 'app-bundles') {
        return apps.filter(function (app) {
          return app.bundles;
        });
      }

      if (this.category === 'updates') {
        return apps.filter(function (app) {
          return app.update;
        });
      } // filter app store categories


      return apps.filter(function (app) {
        return app.appstore && app.category !== undefined && (app.category === _this.category || app.category.indexOf(_this.category) > -1);
      });
    },
    bundles: function bundles() {
      return this.$store.getters.getServerData.bundles;
    },
    bundleApps: function bundleApps() {
      return function (bundle) {
        return this.$store.getters.getAllApps.filter(function (app) {
          return app.bundleId === bundle;
        });
      };
    },
    searchApps: function searchApps() {
      var _this2 = this;

      if (this.search === '') {
        return [];
      }

      return this.$store.getters.getAllApps.filter(function (app) {
        if (app.name.toLowerCase().search(_this2.search.toLowerCase()) !== -1) {
          return !_this2.apps.find(function (_app) {
            return _app.id === app.id;
          });
        }

        return false;
      });
    },
    useAppStoreView: function useAppStoreView() {
      return !this.useListView && !this.useBundleView;
    },
    useListView: function useListView() {
      return this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates';
    },
    useBundleView: function useBundleView() {
      return this.category === 'app-bundles';
    },
    allBundlesEnabled: function allBundlesEnabled() {
      var self = this;
      return function (id) {
        return self.bundleApps(id).filter(function (app) {
          return !app.active;
        }).length === 0;
      };
    },
    bundleToggleText: function bundleToggleText() {
      var self = this;
      return function (id) {
        if (self.allBundlesEnabled(id)) {
          return t('settings', 'Disable all');
        }

        return t('settings', 'Enable all');
      };
    }
  },
  methods: {
    toggleBundle: function toggleBundle(id) {
      if (this.allBundlesEnabled(id)) {
        return this.disableBundle(id);
      }

      return this.enableBundle(id);
    },
    enableBundle: function enableBundle(id) {
      var apps = this.bundleApps(id).map(function (app) {
        return app.id;
      });
      this.$store.dispatch('enableApp', {
        appId: apps,
        groups: []
      }).catch(function (error) {
        console.log(error);
        OC.Notification.show(error);
      });
    },
    disableBundle: function disableBundle(id) {
      var apps = this.bundleApps(id).map(function (app) {
        return app.id;
      });
      this.$store.dispatch('disableApp', {
        appId: apps,
        groups: []
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appItem.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js");
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_multiselect__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _appScore__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appScore */ "./settings/src/components/appList/appScore.vue");
/* harmony import */ var _appManagement__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../appManagement */ "./settings/src/components/appManagement.vue");
/* harmony import */ var _svgFilterMixin__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../svgFilterMixin */ "./settings/src/components/svgFilterMixin.vue");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'appItem',
  mixins: [_appManagement__WEBPACK_IMPORTED_MODULE_2__["default"], _svgFilterMixin__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    app: {},
    category: {},
    listView: {
      type: Boolean,
      default: true
    }
  },
  watch: {
    '$route.params.id': function $routeParamsId(id) {
      this.isSelected = this.app.id === id;
    }
  },
  components: {
    Multiselect: vue_multiselect__WEBPACK_IMPORTED_MODULE_0___default.a,
    AppScore: _appScore__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      isSelected: false,
      scrolled: false
    };
  },
  mounted: function mounted() {
    this.isSelected = this.app.id === this.$route.params.id;
  },
  computed: {},
  watchers: {},
  methods: {
    showAppDetails: function showAppDetails(event) {
      if (event.currentTarget.tagName === 'INPUT' || event.currentTarget.tagName === 'A') {
        return;
      }

      this.$router.push({
        name: 'apps-details',
        params: {
          category: this.category,
          id: this.app.id
        }
      });
    },
    prefix: function prefix(_prefix, content) {
      return _prefix + '_' + content;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appScore.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appScore.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'appScore',
  props: ['score'],
  computed: {
    scoreImage: function scoreImage() {
      var score = Math.round(this.score * 10);
      var imageName = 'rating/s' + score + '.svg';
      return OC.imagePath('core', imageName);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appManagement.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appManagement.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  mounted: function mounted() {
    if (this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  },
  computed: {
    appGroups: function appGroups() {
      return this.app.groups.map(function (group) {
        return {
          id: group,
          name: group
        };
      });
    },
    loading: function loading() {
      var self = this;
      return function (id) {
        return self.$store.getters.loading(id);
      };
    },
    installing: function installing() {
      return this.$store.getters.loading('install');
    },
    enableButtonText: function enableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Download and enable');
      }

      return t('settings', 'Enable');
    },
    forceEnableButtonText: function forceEnableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Enable untested app');
      }

      return t('settings', 'Enable untested app');
    },
    enableButtonTooltip: function enableButtonTooltip() {
      if (this.app.needsDownload) {
        return t('settings', 'The app will be downloaded from the app store');
      }

      return false;
    },
    forceEnableButtonTooltip: function forceEnableButtonTooltip() {
      var base = t('settings', 'This app is not marked as compatible with your Nextcloud version. If you continue you will still be able to install the app. Note that the app might not work as expected.');

      if (this.app.needsDownload) {
        return base + ' ' + t('settings', 'The app will be downloaded from the app store');
      }

      return base;
    }
  },
  methods: {
    asyncFindGroup: function asyncFindGroup(query) {
      return this.$store.dispatch('getGroups', {
        search: query,
        limit: 5,
        offset: 0
      });
    },
    isLimitedToGroups: function isLimitedToGroups(app) {
      if (this.app.groups.length || this.groupCheckedAppsData) {
        return true;
      }

      return false;
    },
    setGroupLimit: function setGroupLimit() {
      if (!this.groupCheckedAppsData) {
        this.$store.dispatch('enableApp', {
          appId: this.app.id,
          groups: []
        });
      }
    },
    canLimitToGroups: function canLimitToGroups(app) {
      if (app.types && app.types.includes('filesystem') || app.types.includes('prelogin') || app.types.includes('authentication') || app.types.includes('logging') || app.types.includes('prevent_group_restriction')) {
        return false;
      }

      return true;
    },
    addGroupLimitation: function addGroupLimitation(group) {
      var groups = this.app.groups.concat([]).concat([group.id]);
      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups: groups
      });
    },
    removeGroupLimitation: function removeGroupLimitation(group) {
      var currentGroups = this.app.groups.concat([]);
      var index = currentGroups.indexOf(group.id);

      if (index > -1) {
        currentGroups.splice(index, 1);
      }

      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups: currentGroups
      });
    },
    forceEnable: function forceEnable(appId) {
      this.$store.dispatch('forceEnableApp', {
        appId: appId,
        groups: []
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    enable: function enable(appId) {
      this.$store.dispatch('enableApp', {
        appId: appId,
        groups: []
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    disable: function disable(appId) {
      this.$store.dispatch('disableApp', {
        appId: appId
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    remove: function remove(appId) {
      this.$store.dispatch('uninstallApp', {
        appId: appId
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    install: function install(appId) {
      this.$store.dispatch('enableApp', {
        appId: appId
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    update: function update(appId) {
      this.$store.dispatch('updateApp', {
        appId: appId
      }).then(function (response) {
        OC.Settings.Apps.rebuildNavigation();
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/prefixMixin.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/prefixMixin.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'prefixMixin',
  methods: {
    prefix: function prefix(_prefix, content) {
      return _prefix + '_' + content;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'svgFilterMixin',
  mounted: function mounted() {
    this.filterId = 'invertIconApps' + Math.floor(Math.random() * 100) + new Date().getSeconds() + new Date().getMilliseconds();
  },
  computed: {
    filterUrl: function filterUrl() {
      return "url(#".concat(this.filterId, ")");
    }
  },
  data: function data() {
    return {
      filterId: ''
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! nextcloud-vue */ "./node_modules/nextcloud-vue/dist/ncvuecomponents.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_appList__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/appList */ "./settings/src/components/appList.vue");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_appDetails__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/appDetails */ "./settings/src/components/appDetails.vue");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//





vue__WEBPACK_IMPORTED_MODULE_2__["default"].use(vue_localstorage__WEBPACK_IMPORTED_MODULE_3___default.a);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Apps',
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
  components: {
    AppContent: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppContent"],
    AppDetails: _components_appDetails__WEBPACK_IMPORTED_MODULE_4__["default"],
    appList: _components_appList__WEBPACK_IMPORTED_MODULE_1__["default"],
    AppNavigationItem: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppNavigationItem"]
  },
  methods: {
    setSearch: function setSearch(query) {
      this.searchQuery = query;
    },
    resetSearch: function resetSearch() {
      this.setSearch('');
    }
  },
  beforeMount: function beforeMount() {
    this.$store.dispatch('getCategories');
    this.$store.dispatch('getAllApps');
    this.$store.dispatch('getGroups', {
      offset: 0,
      limit: 5
    });
    this.$store.commit('setUpdateCount', this.$store.getters.getServerData.updateCount);
  },
  mounted: function mounted() {
    /** 
     * Register search
     */
    this.appSearch = new OCA.Search(this.setSearch, this.resetSearch);
  },
  data: function data() {
    return {
      searchQuery: ''
    };
  },
  watch: {
    category: function category(val, old) {
      this.setSearch('');
    }
  },
  computed: {
    loading: function loading() {
      return this.$store.getters.loading('categories');
    },
    loadingList: function loadingList() {
      return this.$store.getters.loading('list');
    },
    currentApp: function currentApp() {
      var _this = this;

      return this.apps.find(function (app) {
        return app.id === _this.id;
      });
    },
    categories: function categories() {
      return this.$store.getters.getCategories;
    },
    apps: function apps() {
      return this.$store.getters.getAllApps;
    },
    updateCount: function updateCount() {
      return this.$store.getters.getUpdateCount;
    },
    settings: function settings() {
      return this.$store.getters.getServerData;
    },
    // BUILD APP NAVIGATION MENU OBJECT
    menu: function menu() {
      var _this2 = this;

      // Data provided php side
      var categories = this.$store.getters.getCategories;
      categories = Array.isArray(categories) ? categories : []; // Map groups

      categories = categories.map(function (category) {
        var item = {};
        item.id = 'app-category-' + category.ident;
        item.icon = 'icon-category-' + category.ident;
        item.classes = []; // empty classes, active will be set later

        item.router = {
          // router link to
          name: 'apps-category',
          params: {
            category: category.ident
          }
        };
        item.text = category.displayName;
        return item;
      }); // Add everyone group

      var defaultCategories = [{
        id: 'app-category-your-apps',
        classes: [],
        router: {
          name: 'apps'
        },
        icon: 'icon-category-installed',
        text: t('settings', 'Your apps')
      }, {
        id: 'app-category-enabled',
        classes: [],
        icon: 'icon-category-enabled',
        router: {
          name: 'apps-category',
          params: {
            category: 'enabled'
          }
        },
        text: t('settings', 'Active apps')
      }, {
        id: 'app-category-disabled',
        classes: [],
        icon: 'icon-category-disabled',
        router: {
          name: 'apps-category',
          params: {
            category: 'disabled'
          }
        },
        text: t('settings', 'Disabled apps')
      }];

      if (!this.settings.appstoreEnabled) {
        return defaultCategories;
      }

      if (this.$store.getters.getUpdateCount > 0) {
        defaultCategories.push({
          id: 'app-category-updates',
          classes: [],
          icon: 'icon-download',
          router: {
            name: 'apps-category',
            params: {
              category: 'updates'
            }
          },
          text: t('settings', 'Updates'),
          utils: {
            counter: this.$store.getters.getUpdateCount
          }
        });
      }

      defaultCategories.push({
        id: 'app-category-app-bundles',
        classes: [],
        icon: 'icon-category-app-bundles',
        router: {
          name: 'apps-category',
          params: {
            category: 'app-bundles'
          }
        },
        text: t('settings', 'App bundles')
      });
      categories = defaultCategories.concat(categories); // Set current group as active

      var activeGroup = categories.findIndex(function (group) {
        return group.id === 'app-category-' + _this2.category;
      });

      if (activeGroup >= 0) {
        categories[activeGroup].classes.push('active');
      } else {
        categories[0].classes.push('active');
      }

      categories.push({
        id: 'app-developer-docs',
        classes: [],
        href: this.settings.developerDocumentation,
        text: t('settings', 'Developer documentation') + ' ↗'
      }); // Return

      return categories;
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&":
/*!**********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& ***!
  \**********************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "\n.force[data-v-02f2d131] {\n\tbackground: var(--color-main-background);\n\tborder-color: var(--color-error);\n\tcolor: var(--color-error);\n}\n.force[data-v-02f2d131]:hover,\n.force[data-v-02f2d131]:active {\n\tbackground: var(--color-error);\n\tborder-color: var(--color-error) !important;\n\tcolor: var(--color-main-background);\n}\n", ""]);



/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&":
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "\n.force[data-v-09ee4a84] {\n\tbackground: var(--color-main-background);\n\tborder-color: var(--color-error);\n\tcolor: var(--color-error);\n}\n.force[data-v-09ee4a84]:hover,\n.force[data-v-09ee4a84]:active {\n\tbackground: var(--color-error);\n\tborder-color: var(--color-error) !important;\n\tcolor: var(--color-main-background);\n}\n", ""]);



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticStyle: { padding: "20px" }, attrs: { id: "app-details-view" } },
    [
      _c(
        "a",
        {
          staticClass: "close icon-close",
          attrs: { href: "#" },
          on: { click: _vm.hideAppDetails }
        },
        [_c("span", { staticClass: "hidden-visually" }, [_vm._v("Close")])]
      ),
      _vm._v(" "),
      _c("h2", [
        !_vm.app.preview
          ? _c("div", { staticClass: "icon-settings-dark" })
          : _vm._e(),
        _vm._v(" "),
        _vm.app.previewAsIcon && _vm.app.preview
          ? _c(
              "svg",
              { attrs: { width: "32", height: "32", viewBox: "0 0 32 32" } },
              [
                _c("defs", [
                  _c(
                    "filter",
                    { attrs: { id: _vm.filterId } },
                    [
                      _c("feColorMatrix", {
                        attrs: {
                          in: "SourceGraphic",
                          type: "matrix",
                          values: "-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"
                        }
                      })
                    ],
                    1
                  )
                ]),
                _vm._v(" "),
                _c("image", {
                  staticClass: "app-icon",
                  attrs: {
                    x: "0",
                    y: "0",
                    width: "32",
                    height: "32",
                    preserveAspectRatio: "xMinYMin meet",
                    filter: _vm.filterUrl,
                    "xlink:href": _vm.app.preview
                  }
                })
              ]
            )
          : _vm._e(),
        _vm._v("\n\t\t" + _vm._s(_vm.app.name))
      ]),
      _vm._v(" "),
      _vm.app.screenshot
        ? _c("img", { attrs: { src: _vm.app.screenshot, width: "100%" } })
        : _vm._e(),
      _vm._v(" "),
      _vm.app.level === 200 || _vm.hasRating
        ? _c(
            "div",
            { staticClass: "app-level" },
            [
              _vm.app.level === 200
                ? _c(
                    "span",
                    {
                      directives: [
                        {
                          name: "tooltip",
                          rawName: "v-tooltip.auto",
                          value: _vm.t(
                            "settings",
                            "Official apps are developed by and within the community. They offer central functionality and are ready for production use."
                          ),
                          expression:
                            "t('settings', 'Official apps are developed by and within the community. They offer central functionality and are ready for production use.')",
                          modifiers: { auto: true }
                        }
                      ],
                      staticClass: "official icon-checkmark"
                    },
                    [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Official")))]
                  )
                : _vm._e(),
              _vm._v(" "),
              _vm.hasRating
                ? _c("app-score", {
                    attrs: { score: _vm.app.appstoreData.ratingOverall }
                  })
                : _vm._e()
            ],
            1
          )
        : _vm._e(),
      _vm._v(" "),
      _vm.author
        ? _c(
            "div",
            { staticClass: "app-author" },
            [
              _vm._v("\n\t\t" + _vm._s(_vm.t("settings", "by")) + "\n\t\t"),
              _vm._l(_vm.author, function(a, index) {
                return _c("span", [
                  a["@attributes"] && a["@attributes"]["homepage"]
                    ? _c(
                        "a",
                        { attrs: { href: a["@attributes"]["homepage"] } },
                        [_vm._v(_vm._s(a["@value"]))]
                      )
                    : a["@value"]
                    ? _c("span", [_vm._v(_vm._s(a["@value"]))])
                    : _c("span", [_vm._v(_vm._s(a))]),
                  index + 1 < _vm.author.length
                    ? _c("span", [_vm._v(", ")])
                    : _vm._e()
                ])
              })
            ],
            2
          )
        : _vm._e(),
      _vm._v(" "),
      _vm.licence
        ? _c("div", { staticClass: "app-licence" }, [
            _vm._v(_vm._s(_vm.licence))
          ])
        : _vm._e(),
      _vm._v(" "),
      _c("div", { staticClass: "actions" }, [
        _c("div", { staticClass: "actions-buttons" }, [
          _vm.app.update
            ? _c("input", {
                staticClass: "update primary",
                attrs: {
                  type: "button",
                  value: _vm.t("settings", "Update to {version}", {
                    version: _vm.app.update
                  }),
                  disabled: _vm.installing || _vm.loading(_vm.app.id)
                },
                on: {
                  click: function($event) {
                    return _vm.update(_vm.app.id)
                  }
                }
              })
            : _vm._e(),
          _vm._v(" "),
          _vm.app.canUnInstall
            ? _c("input", {
                staticClass: "uninstall",
                attrs: {
                  type: "button",
                  value: _vm.t("settings", "Remove"),
                  disabled: _vm.installing || _vm.loading(_vm.app.id)
                },
                on: {
                  click: function($event) {
                    return _vm.remove(_vm.app.id)
                  }
                }
              })
            : _vm._e(),
          _vm._v(" "),
          _vm.app.active
            ? _c("input", {
                staticClass: "enable",
                attrs: {
                  type: "button",
                  value: _vm.t("settings", "Disable"),
                  disabled: _vm.installing || _vm.loading(_vm.app.id)
                },
                on: {
                  click: function($event) {
                    return _vm.disable(_vm.app.id)
                  }
                }
              })
            : _vm._e(),
          _vm._v(" "),
          !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible)
            ? _c("input", {
                directives: [
                  {
                    name: "tooltip",
                    rawName: "v-tooltip.auto",
                    value: _vm.enableButtonTooltip,
                    expression: "enableButtonTooltip",
                    modifiers: { auto: true }
                  }
                ],
                staticClass: "enable primary",
                attrs: {
                  type: "button",
                  value: _vm.enableButtonText,
                  disabled:
                    !_vm.app.canInstall ||
                    _vm.installing ||
                    _vm.loading(_vm.app.id)
                },
                on: {
                  click: function($event) {
                    return _vm.enable(_vm.app.id)
                  }
                }
              })
            : !_vm.app.active
            ? _c("input", {
                directives: [
                  {
                    name: "tooltip",
                    rawName: "v-tooltip.auto",
                    value: _vm.forceEnableButtonTooltip,
                    expression: "forceEnableButtonTooltip",
                    modifiers: { auto: true }
                  }
                ],
                staticClass: "enable force",
                attrs: {
                  type: "button",
                  value: _vm.forceEnableButtonText,
                  disabled: _vm.installing || _vm.loading(_vm.app.id)
                },
                on: {
                  click: function($event) {
                    return _vm.forceEnable(_vm.app.id)
                  }
                }
              })
            : _vm._e()
        ]),
        _vm._v(" "),
        _c("div", { staticClass: "app-groups" }, [
          _vm.app.active && _vm.canLimitToGroups(_vm.app)
            ? _c(
                "div",
                { staticClass: "groups-enable" },
                [
                  _c("input", {
                    directives: [
                      {
                        name: "model",
                        rawName: "v-model",
                        value: _vm.groupCheckedAppsData,
                        expression: "groupCheckedAppsData"
                      }
                    ],
                    staticClass: "groups-enable__checkbox checkbox",
                    attrs: {
                      type: "checkbox",
                      id: _vm.prefix("groups_enable", _vm.app.id)
                    },
                    domProps: {
                      value: _vm.app.id,
                      checked: Array.isArray(_vm.groupCheckedAppsData)
                        ? _vm._i(_vm.groupCheckedAppsData, _vm.app.id) > -1
                        : _vm.groupCheckedAppsData
                    },
                    on: {
                      change: [
                        function($event) {
                          var $$a = _vm.groupCheckedAppsData,
                            $$el = $event.target,
                            $$c = $$el.checked ? true : false
                          if (Array.isArray($$a)) {
                            var $$v = _vm.app.id,
                              $$i = _vm._i($$a, $$v)
                            if ($$el.checked) {
                              $$i < 0 &&
                                (_vm.groupCheckedAppsData = $$a.concat([$$v]))
                            } else {
                              $$i > -1 &&
                                (_vm.groupCheckedAppsData = $$a
                                  .slice(0, $$i)
                                  .concat($$a.slice($$i + 1)))
                            }
                          } else {
                            _vm.groupCheckedAppsData = $$c
                          }
                        },
                        _vm.setGroupLimit
                      ]
                    }
                  }),
                  _vm._v(" "),
                  _c(
                    "label",
                    { attrs: { for: _vm.prefix("groups_enable", _vm.app.id) } },
                    [_vm._v(_vm._s(_vm.t("settings", "Limit to groups")))]
                  ),
                  _vm._v(" "),
                  _c("input", {
                    staticClass: "group_select",
                    attrs: {
                      type: "hidden",
                      title: _vm.t("settings", "All"),
                      value: ""
                    }
                  }),
                  _vm._v(" "),
                  _vm.isLimitedToGroups(_vm.app)
                    ? _c(
                        "multiselect",
                        {
                          staticClass: "multiselect-vue",
                          attrs: {
                            options: _vm.groups,
                            value: _vm.appGroups,
                            "options-limit": 5,
                            placeholder: _vm.t(
                              "settings",
                              "Limit app usage to groups"
                            ),
                            label: "name",
                            "track-by": "id",
                            multiple: true,
                            "close-on-select": false
                          },
                          on: {
                            select: _vm.addGroupLimitation,
                            remove: _vm.removeGroupLimitation,
                            "search-change": _vm.asyncFindGroup
                          }
                        },
                        [
                          _c(
                            "span",
                            { attrs: { slot: "noResult" }, slot: "noResult" },
                            [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                          )
                        ]
                      )
                    : _vm._e()
                ],
                1
              )
            : _vm._e()
        ])
      ]),
      _vm._v(" "),
      _c("p", { staticClass: "documentation" }, [
        !_vm.app.internal
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.appstoreUrl,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [_vm._v(_vm._s(_vm.t("settings", "View in store")) + " ↗")]
            )
          : _vm._e(),
        _vm._v(" "),
        _vm.app.website
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.app.website,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [_vm._v(_vm._s(_vm.t("settings", "Visit website")) + " ↗")]
            )
          : _vm._e(),
        _vm._v(" "),
        _vm.app.bugs
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.app.bugs,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [_vm._v(_vm._s(_vm.t("settings", "Report a bug")) + " ↗")]
            )
          : _vm._e(),
        _vm._v(" "),
        _vm.app.documentation && _vm.app.documentation.user
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.app.documentation.user,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [_vm._v(_vm._s(_vm.t("settings", "User documentation")) + " ↗")]
            )
          : _vm._e(),
        _vm._v(" "),
        _vm.app.documentation && _vm.app.documentation.admin
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.app.documentation.admin,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [_vm._v(_vm._s(_vm.t("settings", "Admin documentation")) + " ↗")]
            )
          : _vm._e(),
        _vm._v(" "),
        _vm.app.documentation && _vm.app.documentation.developer
          ? _c(
              "a",
              {
                staticClass: "appslink",
                attrs: {
                  href: _vm.app.documentation.developer,
                  target: "_blank",
                  rel: "noreferrer noopener"
                }
              },
              [
                _vm._v(
                  _vm._s(_vm.t("settings", "Developer documentation")) + " ↗"
                )
              ]
            )
          : _vm._e()
      ]),
      _vm._v(" "),
      _c("ul", { staticClass: "app-dependencies" }, [
        _vm.app.missingMinOwnCloudVersion
          ? _c("li", [
              _vm._v(
                _vm._s(
                  _vm.t(
                    "settings",
                    "This app has no minimum Nextcloud version assigned. This will be an error in the future."
                  )
                )
              )
            ])
          : _vm._e(),
        _vm._v(" "),
        _vm.app.missingMaxOwnCloudVersion
          ? _c("li", [
              _vm._v(
                _vm._s(
                  _vm.t(
                    "settings",
                    "This app has no maximum Nextcloud version assigned. This will be an error in the future."
                  )
                )
              )
            ])
          : _vm._e(),
        _vm._v(" "),
        !_vm.app.canInstall
          ? _c("li", [
              _vm._v(
                "\n\t\t\t" +
                  _vm._s(
                    _vm.t(
                      "settings",
                      "This app cannot be installed because the following dependencies are not fulfilled:"
                    )
                  ) +
                  "\n\t\t\t"
              ),
              _c(
                "ul",
                { staticClass: "missing-dependencies" },
                _vm._l(_vm.app.missingDependencies, function(dep) {
                  return _c("li", [_vm._v(_vm._s(dep))])
                }),
                0
              )
            ])
          : _vm._e()
      ]),
      _vm._v(" "),
      _c("div", {
        staticClass: "app-description",
        domProps: { innerHTML: _vm._s(_vm.renderMarkdown) }
      })
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList.vue?vue&type=template&id=11a8c382&":
/*!**********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList.vue?vue&type=template&id=11a8c382& ***!
  \**********************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { attrs: { id: "app-content-inner" } }, [
    _c(
      "div",
      {
        staticClass: "apps-list",
        class: {
          installed: _vm.useBundleView || _vm.useListView,
          store: _vm.useAppStoreView
        },
        attrs: { id: "apps-list" }
      },
      [
        _vm.useListView
          ? [
              _c(
                "transition-group",
                {
                  staticClass: "apps-list-container",
                  attrs: { name: "app-list", tag: "div" }
                },
                _vm._l(_vm.apps, function(app) {
                  return _c("app-item", {
                    key: app.id,
                    attrs: { app: app, category: _vm.category }
                  })
                }),
                1
              )
            ]
          : _vm._e(),
        _vm._v(" "),
        _vm._l(_vm.bundles, function(bundle) {
          return _vm.useBundleView && _vm.bundleApps(bundle.id).length > 0
            ? [
                _c(
                  "transition-group",
                  {
                    staticClass: "apps-list-container",
                    attrs: { name: "app-list", tag: "div" }
                  },
                  [
                    _c("div", { key: bundle.id, staticClass: "apps-header" }, [
                      _c("div", { staticClass: "app-image" }),
                      _vm._v(" "),
                      _c("h2", [
                        _vm._v(_vm._s(bundle.name) + " "),
                        _c("input", {
                          attrs: {
                            type: "button",
                            value: _vm.bundleToggleText(bundle.id)
                          },
                          on: {
                            click: function($event) {
                              return _vm.toggleBundle(bundle.id)
                            }
                          }
                        })
                      ]),
                      _vm._v(" "),
                      _c("div", { staticClass: "app-version" }),
                      _vm._v(" "),
                      _c("div", { staticClass: "app-level" }),
                      _vm._v(" "),
                      _c("div", { staticClass: "app-groups" }),
                      _vm._v(" "),
                      _c("div", { staticClass: "actions" }, [_vm._v(" ")])
                    ]),
                    _vm._v(" "),
                    _vm._l(_vm.bundleApps(bundle.id), function(app) {
                      return _c("app-item", {
                        key: bundle.id + app.id,
                        attrs: { app: app, category: _vm.category }
                      })
                    })
                  ],
                  2
                )
              ]
            : _vm._e()
        }),
        _vm._v(" "),
        _vm.useAppStoreView
          ? _vm._l(_vm.apps, function(app) {
              return _c("app-item", {
                key: app.id,
                attrs: { app: app, category: _vm.category, "list-view": false }
              })
            })
          : _vm._e()
      ],
      2
    ),
    _vm._v(" "),
    _c(
      "div",
      { staticClass: "apps-list installed", attrs: { id: "apps-list-search" } },
      [
        _c(
          "div",
          { staticClass: "apps-list-container" },
          [
            _vm.search !== "" && _vm.searchApps.length > 0
              ? [
                  _c("div", { staticClass: "section" }, [
                    _c("div"),
                    _vm._v(" "),
                    _c("td", { attrs: { colspan: "5" } }, [
                      _c("h2", [
                        _vm._v(
                          _vm._s(
                            _vm.t("settings", "Results from other categories")
                          )
                        )
                      ])
                    ])
                  ]),
                  _vm._v(" "),
                  _vm._l(_vm.searchApps, function(app) {
                    return _c("app-item", {
                      key: app.id,
                      attrs: {
                        app: app,
                        category: _vm.category,
                        "list-view": true
                      }
                    })
                  })
                ]
              : _vm._e()
          ],
          2
        )
      ]
    ),
    _vm._v(" "),
    !_vm.loading && _vm.searchApps.length === 0 && _vm.apps.length === 0
      ? _c(
          "div",
          {
            staticClass: "emptycontent emptycontent-search",
            attrs: { id: "apps-list-empty" }
          },
          [
            _c("div", {
              staticClass: "icon-settings-dark",
              attrs: { id: "app-list-empty-icon" }
            }),
            _vm._v(" "),
            _c("h2", [
              _vm._v(
                _vm._s(_vm.t("settings", "No apps found for your version"))
              )
            ])
          ]
        )
      : _vm._e(),
    _vm._v(" "),
    _c("div", { attrs: { id: "searchresults" } })
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    {
      staticClass: "section",
      class: { selected: _vm.isSelected },
      on: { click: _vm.showAppDetails }
    },
    [
      _c(
        "div",
        {
          staticClass: "app-image app-image-icon",
          on: { click: _vm.showAppDetails }
        },
        [
          (_vm.listView && !_vm.app.preview) ||
          (!_vm.listView && !_vm.app.screenshot)
            ? _c("div", { staticClass: "icon-settings-dark" })
            : _vm._e(),
          _vm._v(" "),
          _vm.listView && _vm.app.preview
            ? _c(
                "svg",
                { attrs: { width: "32", height: "32", viewBox: "0 0 32 32" } },
                [
                  _c("defs", [
                    _c(
                      "filter",
                      { attrs: { id: _vm.filterId } },
                      [
                        _c("feColorMatrix", {
                          attrs: {
                            in: "SourceGraphic",
                            type: "matrix",
                            values: "-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"
                          }
                        })
                      ],
                      1
                    )
                  ]),
                  _vm._v(" "),
                  _c("image", {
                    staticClass: "app-icon",
                    attrs: {
                      x: "0",
                      y: "0",
                      width: "32",
                      height: "32",
                      preserveAspectRatio: "xMinYMin meet",
                      filter: _vm.filterUrl,
                      "xlink:href": _vm.app.preview
                    }
                  })
                ]
              )
            : _vm._e(),
          _vm._v(" "),
          !_vm.listView && _vm.app.screenshot
            ? _c("img", { attrs: { src: _vm.app.screenshot, width: "100%" } })
            : _vm._e()
        ]
      ),
      _vm._v(" "),
      _c(
        "div",
        { staticClass: "app-name", on: { click: _vm.showAppDetails } },
        [_vm._v("\n\t\t" + _vm._s(_vm.app.name) + "\n\t")]
      ),
      _vm._v(" "),
      !_vm.listView
        ? _c("div", { staticClass: "app-summary" }, [
            _vm._v(_vm._s(_vm.app.summary))
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.listView
        ? _c("div", { staticClass: "app-version" }, [
            _vm.app.version
              ? _c("span", [_vm._v(_vm._s(_vm.app.version))])
              : _vm.app.appstoreData.releases[0].version
              ? _c("span", [
                  _vm._v(_vm._s(_vm.app.appstoreData.releases[0].version))
                ])
              : _vm._e()
          ])
        : _vm._e(),
      _vm._v(" "),
      _c(
        "div",
        { staticClass: "app-level" },
        [
          _vm.app.level === 200
            ? _c(
                "span",
                {
                  directives: [
                    {
                      name: "tooltip",
                      rawName: "v-tooltip.auto",
                      value: _vm.t(
                        "settings",
                        "Official apps are developed by and within the community. They offer central functionality and are ready for production use."
                      ),
                      expression:
                        "t('settings', 'Official apps are developed by and within the community. They offer central functionality and are ready for production use.')",
                      modifiers: { auto: true }
                    }
                  ],
                  staticClass: "official icon-checkmark"
                },
                [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Official")))]
              )
            : _vm._e(),
          _vm._v(" "),
          !_vm.listView
            ? _c("app-score", { attrs: { score: _vm.app.score } })
            : _vm._e()
        ],
        1
      ),
      _vm._v(" "),
      _c("div", { staticClass: "actions" }, [
        _vm.app.error
          ? _c("div", { staticClass: "warning" }, [
              _vm._v(_vm._s(_vm.app.error))
            ])
          : _vm._e(),
        _vm._v(" "),
        _vm.loading(_vm.app.id)
          ? _c("div", { staticClass: "icon icon-loading-small" })
          : _vm._e(),
        _vm._v(" "),
        _vm.app.update
          ? _c("input", {
              staticClass: "update primary",
              attrs: {
                type: "button",
                value: _vm.t("settings", "Update to {update}", {
                  update: _vm.app.update
                }),
                disabled: _vm.installing || _vm.loading(_vm.app.id)
              },
              on: {
                click: function($event) {
                  $event.stopPropagation()
                  return _vm.update(_vm.app.id)
                }
              }
            })
          : _vm._e(),
        _vm._v(" "),
        _vm.app.canUnInstall
          ? _c("input", {
              staticClass: "uninstall",
              attrs: {
                type: "button",
                value: _vm.t("settings", "Remove"),
                disabled: _vm.installing || _vm.loading(_vm.app.id)
              },
              on: {
                click: function($event) {
                  $event.stopPropagation()
                  return _vm.remove(_vm.app.id)
                }
              }
            })
          : _vm._e(),
        _vm._v(" "),
        _vm.app.active
          ? _c("input", {
              staticClass: "enable",
              attrs: {
                type: "button",
                value: _vm.t("settings", "Disable"),
                disabled: _vm.installing || _vm.loading(_vm.app.id)
              },
              on: {
                click: function($event) {
                  $event.stopPropagation()
                  return _vm.disable(_vm.app.id)
                }
              }
            })
          : _vm._e(),
        _vm._v(" "),
        !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible)
          ? _c("input", {
              directives: [
                {
                  name: "tooltip",
                  rawName: "v-tooltip.auto",
                  value: _vm.enableButtonTooltip,
                  expression: "enableButtonTooltip",
                  modifiers: { auto: true }
                }
              ],
              staticClass: "enable",
              attrs: {
                type: "button",
                value: _vm.enableButtonText,
                disabled:
                  !_vm.app.canInstall ||
                  _vm.installing ||
                  _vm.loading(_vm.app.id)
              },
              on: {
                click: function($event) {
                  $event.stopPropagation()
                  return _vm.enable(_vm.app.id)
                }
              }
            })
          : !_vm.app.active
          ? _c("input", {
              directives: [
                {
                  name: "tooltip",
                  rawName: "v-tooltip.auto",
                  value: _vm.forceEnableButtonTooltip,
                  expression: "forceEnableButtonTooltip",
                  modifiers: { auto: true }
                }
              ],
              staticClass: "enable force",
              attrs: {
                type: "button",
                value: _vm.forceEnableButtonText,
                disabled: _vm.installing || _vm.loading(_vm.app.id)
              },
              on: {
                click: function($event) {
                  $event.stopPropagation()
                  return _vm.forceEnable(_vm.app.id)
                }
              }
            })
          : _vm._e()
      ])
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1&":
/*!*******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1& ***!
  \*******************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("img", {
    staticClass: "app-score-image",
    attrs: { src: _vm.scoreImage }
  })
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec&":
/*!**************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec& ***!
  \**************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("AppContent", {
    class: { "with-app-sidebar": _vm.currentApp },
    attrs: {
      "app-name": "settings",
      "content-class": { "icon-loading": _vm.loadingList },
      "navigation-class": { "icon-loading": _vm.loading }
    },
    scopedSlots: _vm._u(
      [
        {
          key: "navigation",
          fn: function() {
            return [
              _c(
                "ul",
                { attrs: { id: "appscategories" } },
                _vm._l(_vm.menu, function(item) {
                  return _c("AppNavigationItem", {
                    key: item.key,
                    attrs: { item: item }
                  })
                }),
                1
              )
            ]
          },
          proxy: true
        },
        {
          key: "content",
          fn: function() {
            return [
              _c("app-list", {
                attrs: {
                  category: _vm.category,
                  app: _vm.currentApp,
                  search: _vm.searchQuery
                }
              })
            ]
          },
          proxy: true
        },
        _vm.id && _vm.currentApp
          ? {
              key: "sidebar",
              fn: function() {
                return [
                  _c("app-details", {
                    attrs: { category: _vm.category, app: _vm.currentApp }
                  })
                ]
              },
              proxy: true
            }
          : null
      ],
      null,
      true
    )
  })
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/vue-loader/lib??vue-loader-options!./appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("ea375ae0", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("732d0d00", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./settings/src/components/appDetails.vue":
/*!************************************************!*\
  !*** ./settings/src/components/appDetails.vue ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appDetails.vue?vue&type=template&id=02f2d131&scoped=true& */ "./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true&");
/* harmony import */ var _appDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appDetails.vue?vue&type=script&lang=js& */ "./settings/src/components/appDetails.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& */ "./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _appDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "02f2d131",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/appDetails.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/appDetails.vue?vue&type=script&lang=js&":
/*!*************************************************************************!*\
  !*** ./settings/src/components/appDetails.vue?vue&type=script&lang=js& ***!
  \*************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./appDetails.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&":
/*!*********************************************************************************************************!*\
  !*** ./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& ***!
  \*********************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-style-loader!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/vue-loader/lib??vue-loader-options!./appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=style&index=0&id=02f2d131&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_style_index_0_id_02f2d131_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true&":
/*!*******************************************************************************************!*\
  !*** ./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true& ***!
  \*******************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib??vue-loader-options!./appDetails.vue?vue&type=template&id=02f2d131&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appDetails.vue?vue&type=template&id=02f2d131&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appDetails_vue_vue_type_template_id_02f2d131_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/components/appList.vue":
/*!*********************************************!*\
  !*** ./settings/src/components/appList.vue ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appList.vue?vue&type=template&id=11a8c382& */ "./settings/src/components/appList.vue?vue&type=template&id=11a8c382&");
/* harmony import */ var _appList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appList.vue?vue&type=script&lang=js& */ "./settings/src/components/appList.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _appList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__["render"],
  _appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/appList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/appList.vue?vue&type=script&lang=js&":
/*!**********************************************************************!*\
  !*** ./settings/src/components/appList.vue?vue&type=script&lang=js& ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./appList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/appList.vue?vue&type=template&id=11a8c382&":
/*!****************************************************************************!*\
  !*** ./settings/src/components/appList.vue?vue&type=template&id=11a8c382& ***!
  \****************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib??vue-loader-options!./appList.vue?vue&type=template&id=11a8c382& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList.vue?vue&type=template&id=11a8c382&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appList_vue_vue_type_template_id_11a8c382___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/components/appList/appItem.vue":
/*!*****************************************************!*\
  !*** ./settings/src/components/appList/appItem.vue ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appItem.vue?vue&type=template&id=09ee4a84&scoped=true& */ "./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true&");
/* harmony import */ var _appItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appItem.vue?vue&type=script&lang=js& */ "./settings/src/components/appList/appItem.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& */ "./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _appItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "09ee4a84",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/appList/appItem.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/appList/appItem.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./settings/src/components/appList/appItem.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./appItem.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&":
/*!**************************************************************************************************************!*\
  !*** ./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& ***!
  \**************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=style&index=0&id=09ee4a84&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_style_index_0_id_09ee4a84_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true& ***!
  \************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./appItem.vue?vue&type=template&id=09ee4a84&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appItem.vue?vue&type=template&id=09ee4a84&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appItem_vue_vue_type_template_id_09ee4a84_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/components/appList/appScore.vue":
/*!******************************************************!*\
  !*** ./settings/src/components/appList/appScore.vue ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appScore.vue?vue&type=template&id=350044f1& */ "./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1&");
/* harmony import */ var _appScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appScore.vue?vue&type=script&lang=js& */ "./settings/src/components/appList/appScore.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _appScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__["render"],
  _appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/appList/appScore.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/appList/appScore.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./settings/src/components/appList/appScore.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./appScore.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appScore.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1&":
/*!*************************************************************************************!*\
  !*** ./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1& ***!
  \*************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./appScore.vue?vue&type=template&id=350044f1& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appList/appScore.vue?vue&type=template&id=350044f1&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_appScore_vue_vue_type_template_id_350044f1___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/components/appManagement.vue":
/*!***************************************************!*\
  !*** ./settings/src/components/appManagement.vue ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _appManagement_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./appManagement.vue?vue&type=script&lang=js& */ "./settings/src/components/appManagement.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns




/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _appManagement_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/appManagement.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/appManagement.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./settings/src/components/appManagement.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appManagement_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./appManagement.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/appManagement.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_appManagement_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/prefixMixin.vue":
/*!*************************************************!*\
  !*** ./settings/src/components/prefixMixin.vue ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _prefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./prefixMixin.vue?vue&type=script&lang=js& */ "./settings/src/components/prefixMixin.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns




/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _prefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/prefixMixin.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/prefixMixin.vue?vue&type=script&lang=js&":
/*!**************************************************************************!*\
  !*** ./settings/src/components/prefixMixin.vue?vue&type=script&lang=js& ***!
  \**************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_prefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./prefixMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/prefixMixin.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_prefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/svgFilterMixin.vue":
/*!****************************************************!*\
  !*** ./settings/src/components/svgFilterMixin.vue ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _svgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./svgFilterMixin.vue?vue&type=script&lang=js& */ "./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns




/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _svgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/svgFilterMixin.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js&":
/*!*****************************************************************************!*\
  !*** ./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_svgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./svgFilterMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/svgFilterMixin.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_svgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/views/Apps.vue":
/*!*************************************!*\
  !*** ./settings/src/views/Apps.vue ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Apps.vue?vue&type=template&id=25c6e9ec& */ "./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec&");
/* harmony import */ var _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Apps.vue?vue&type=script&lang=js& */ "./settings/src/views/Apps.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__["render"],
  _Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/views/Apps.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!**************************************************************!*\
  !*** ./settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./Apps.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Apps.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec&":
/*!********************************************************************!*\
  !*** ./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec& ***!
  \********************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib??vue-loader-options!./Apps.vue?vue&type=template&id=25c6e9ec& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Apps.vue?vue&type=template&id=25c6e9ec&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_25c6e9ec___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ })

}]);
//# sourceMappingURL=vue-1.js.map