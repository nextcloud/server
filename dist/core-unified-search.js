/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./core/src/services/UnifiedSearchService.js":
/*!***************************************************!*\
  !*** ./core/src/services/UnifiedSearchService.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "defaultLimit": function() { return /* binding */ defaultLimit; },
/* harmony export */   "enableLiveSearch": function() { return /* binding */ enableLiveSearch; },
/* harmony export */   "getTypes": function() { return /* binding */ getTypes; },
/* harmony export */   "minSearchLength": function() { return /* binding */ minSearchLength; },
/* harmony export */   "regexFilterIn": function() { return /* binding */ regexFilterIn; },
/* harmony export */   "regexFilterNot": function() { return /* binding */ regexFilterNot; },
/* harmony export */   "search": function() { return /* binding */ search; }
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2020, John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
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




var defaultLimit = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'limit-default');
var minSearchLength = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'min-search-length', 1);
var enableLiveSearch = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'live-search', true);
var regexFilterIn = /(^|\s)in:([a-z_-]+)/ig;
var regexFilterNot = /(^|\s)-in:([a-z_-]+)/ig;

/**
 * Create a cancel token
 *
 * @return {import('axios').CancelTokenSource}
 */
var createCancelToken = function createCancelToken() {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].CancelToken.source();
};

/**
 * Get the list of available search providers
 *
 * @return {Promise<Array>}
 */
function getTypes() {
  return _getTypes.apply(this, arguments);
}

/**
 * Get the list of available search providers
 *
 * @param {object} options destructuring object
 * @param {string} options.type the type to search
 * @param {string} options.query the search
 * @param {number|string|undefined} options.cursor the offset for paginated searches
 * @return {object} {request: Promise, cancel: Promise}
 */
function _getTypes() {
  _getTypes = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
    var _yield$axios$get, data;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            _context2.prev = 0;
            _context2.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('search/providers'), {
              params: {
                // Sending which location we're currently at
                from: window.location.pathname.replace('/index.php', '') + window.location.search
              }
            });
          case 3:
            _yield$axios$get = _context2.sent;
            data = _yield$axios$get.data;
            if (!('ocs' in data && 'data' in data.ocs && Array.isArray(data.ocs.data) && data.ocs.data.length > 0)) {
              _context2.next = 7;
              break;
            }
            return _context2.abrupt("return", data.ocs.data);
          case 7:
            _context2.next = 12;
            break;
          case 9:
            _context2.prev = 9;
            _context2.t0 = _context2["catch"](0);
            console.error(_context2.t0);
          case 12:
            return _context2.abrupt("return", []);
          case 13:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2, null, [[0, 9]]);
  }));
  return _getTypes.apply(this, arguments);
}
function search(_ref) {
  var type = _ref.type,
    query = _ref.query,
    cursor = _ref.cursor;
  /**
   * Generate an axios cancel token
   */
  var cancelToken = createCancelToken();
  var request = /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              return _context.abrupt("return", _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('search/providers/{type}/search', {
                type: type
              }), {
                cancelToken: cancelToken.token,
                params: {
                  term: query,
                  cursor: cursor,
                  // Sending which location we're currently at
                  from: window.location.pathname.replace('/index.php', '') + window.location.search
                }
              }));
            case 1:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));
    return function request() {
      return _ref2.apply(this, arguments);
    };
  }();
  return {
    request: request,
    cancel: cancelToken.cancel
  };
}

/***/ }),

/***/ "./core/src/unified-search.js":
/*!************************************!*\
  !*** ./core/src/unified-search.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_UnifiedSearch_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./views/UnifiedSearch.vue */ "./core/src/views/UnifiedSearch.vue");
/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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







// eslint-disable-next-line camelcase
__webpack_require__.nc = btoa((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getRequestToken)());
var logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('unified-search').detectUser().build();
vue__WEBPACK_IMPORTED_MODULE_4__["default"].mixin({
  data: function data() {
    return {
      logger: logger
    };
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
    n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translatePlural
  }
});
/* harmony default export */ __webpack_exports__["default"] = (new vue__WEBPACK_IMPORTED_MODULE_4__["default"]({
  el: '#unified-search',
  // eslint-disable-next-line vue/match-component-file-name
  name: 'UnifiedSearchRoot',
  render: function render(h) {
    return h(_views_UnifiedSearch_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var v_click_outside__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! v-click-outside */ "./node_modules/v-click-outside/dist/v-click-outside.umd.js");
/* harmony import */ var v_click_outside__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(v_click_outside__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Mixins_excludeClickOutsideClasses__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Mixins/excludeClickOutsideClasses */ "./node_modules/@nextcloud/vue/dist/Mixins/excludeClickOutsideClasses.js");
/* harmony import */ var _nextcloud_vue_dist_Mixins_excludeClickOutsideClasses__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Mixins_excludeClickOutsideClasses__WEBPACK_IMPORTED_MODULE_1__);


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'HeaderMenu',
  directives: {
    ClickOutside: v_click_outside__WEBPACK_IMPORTED_MODULE_0__.directive
  },
  mixins: [(_nextcloud_vue_dist_Mixins_excludeClickOutsideClasses__WEBPACK_IMPORTED_MODULE_1___default())],
  props: {
    id: {
      type: String,
      required: true
    },
    ariaLabel: {
      type: String,
      default: ''
    },
    open: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      opened: this.open,
      clickOutsideConfig: {
        handler: this.closeMenu,
        middleware: this.clickOutsideMiddleware
      },
      shortcutsDisabled: OCP.Accessibility.disableKeyboardShortcuts()
    };
  },
  watch: {
    open: function open(newVal) {
      var _this = this;
      this.opened = newVal;
      this.$nextTick(function () {
        if (_this.opened) {
          _this.openMenu();
        } else {
          _this.closeMenu();
        }
      });
    }
  },
  mounted: function mounted() {
    document.addEventListener('keydown', this.onKeyDown);
  },
  beforeDestroy: function beforeDestroy() {
    document.removeEventListener('keydown', this.onKeyDown);
  },
  methods: {
    /**
     * Toggle the current menu open state
     */
    toggleMenu: function toggleMenu() {
      // Toggling current state
      if (!this.opened) {
        this.openMenu();
      } else {
        this.closeMenu();
      }
    },
    /**
     * Close the current menu
     */
    closeMenu: function closeMenu() {
      if (!this.opened) {
        return;
      }
      this.opened = false;
      this.$emit('close');
      this.$emit('update:open', false);
    },
    /**
     * Open the current menu
     */
    openMenu: function openMenu() {
      if (this.opened) {
        return;
      }
      this.opened = true;
      this.$emit('open');
      this.$emit('update:open', true);
    },
    onKeyDown: function onKeyDown(event) {
      if (this.shortcutsDisabled) {
        return;
      }

      // If opened and escape pressed, close
      if (event.key === 'Escape' && this.opened) {
        event.preventDefault();

        /** user cancelled the menu by pressing escape */
        this.$emit('cancel');

        /** we do NOT fire a close event to differentiate cancel and close */
        this.opened = false;
        this.$emit('update:open', false);
      }
    },
    handleFocusOut: function handleFocusOut(event) {
      if (!event.currentTarget.contains(event.relatedTarget)) {
        this.closeMenu();
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcHighlight */ "./node_modules/@nextcloud/vue/dist/Components/NcHighlight.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SearchResult',
  components: {
    NcHighlight: (_nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    thumbnailUrl: {
      type: String,
      default: null
    },
    title: {
      type: String,
      required: true
    },
    subline: {
      type: String,
      default: null
    },
    resourceUrl: {
      type: String,
      default: null
    },
    icon: {
      type: String,
      default: ''
    },
    rounded: {
      type: Boolean,
      default: false
    },
    query: {
      type: String,
      default: ''
    },
    /**
     * Only used for the first result as a visual feedback
     * so we can keep the search input focused but pressing
     * enter still opens the first result
     */
    focused: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      hasValidThumbnail: this.thumbnailUrl && this.thumbnailUrl.trim() !== '',
      loaded: false
    };
  },
  computed: {
    isIconUrl: function isIconUrl() {
      // If we're facing an absolute url
      if (this.icon.startsWith('/')) {
        return true;
      }

      // Otherwise, let's check if this is a valid url
      try {
        // eslint-disable-next-line no-new
        new URL(this.icon);
      } catch (_unused) {
        return false;
      }
      return true;
    }
  },
  watch: {
    // Make sure to reset state on change even when vue recycle the component
    thumbnailUrl: function thumbnailUrl() {
      this.hasValidThumbnail = this.thumbnailUrl && this.thumbnailUrl.trim() !== '';
      this.loaded = false;
    }
  },
  methods: {
    reEmitEvent: function reEmitEvent(e) {
      this.$emit(e.type, e);
    },
    /**
     * If the image fails to load, fallback to iconClass
     */
    onError: function onError() {
      this.hasValidThumbnail = false;
    },
    onLoad: function onLoad() {
      this.loaded = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SearchResultPlaceholders',
  data: function data() {
    return {
      light: null,
      dark: null
    };
  },
  mounted: function mounted() {
    var styles = getComputedStyle(document.documentElement);
    this.dark = styles.getPropertyValue('--color-placeholder-dark');
    this.light = styles.getPropertyValue('--color-placeholder-light');
  },
  methods: {
    randWidth: function randWidth() {
      return Math.floor(Math.random() * 20) + 30;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/UnifiedSearchService */ "./core/src/services/UnifiedSearchService.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcHighlight */ "./node_modules/@nextcloud/vue/dist/Components/NcHighlight.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var vue_material_design_icons_Magnify__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/Magnify */ "./node_modules/vue-material-design-icons/Magnify.vue");
/* harmony import */ var _components_HeaderMenu__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../components/HeaderMenu */ "./core/src/components/HeaderMenu.vue");
/* harmony import */ var _components_UnifiedSearch_SearchResult__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../components/UnifiedSearch/SearchResult */ "./core/src/components/UnifiedSearch/SearchResult.vue");
/* harmony import */ var _components_UnifiedSearch_SearchResultPlaceholders__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/UnifiedSearch/SearchResultPlaceholders */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue");
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }












var REQUEST_FAILED = 0;
var REQUEST_OK = 1;
var REQUEST_CANCELED = 2;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UnifiedSearch',
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_3___default()),
    NcActions: (_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_4___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_6___default()),
    HeaderMenu: _components_HeaderMenu__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcHighlight: (_nextcloud_vue_dist_Components_NcHighlight__WEBPACK_IMPORTED_MODULE_7___default()),
    Magnify: vue_material_design_icons_Magnify__WEBPACK_IMPORTED_MODULE_8__["default"],
    SearchResult: _components_UnifiedSearch_SearchResult__WEBPACK_IMPORTED_MODULE_10__["default"],
    SearchResultPlaceholders: _components_UnifiedSearch_SearchResultPlaceholders__WEBPACK_IMPORTED_MODULE_11__["default"]
  },
  data: function data() {
    return {
      types: [],
      // Cursors per types
      cursors: {},
      // Various search limits per types
      limits: {},
      // Loading types
      loading: {},
      // Reached search types
      reached: {},
      // Pending cancellable requests
      requests: [],
      // List of all results
      results: {},
      query: '',
      focused: null,
      triggered: false,
      defaultLimit: _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.defaultLimit,
      minSearchLength: _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.minSearchLength,
      enableLiveSearch: _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.enableLiveSearch,
      open: false
    };
  },
  computed: {
    typesIDs: function typesIDs() {
      return this.types.map(function (type) {
        return type.id;
      });
    },
    typesNames: function typesNames() {
      return this.types.map(function (type) {
        return type.name;
      });
    },
    typesMap: function typesMap() {
      return this.types.reduce(function (prev, curr) {
        prev[curr.id] = curr.name;
        return prev;
      }, {});
    },
    ariaLabel: function ariaLabel() {
      return t('core', 'Search');
    },
    /**
     * Is there any result to display
     *
     * @return {boolean}
     */
    hasResults: function hasResults() {
      return Object.keys(this.results).length !== 0;
    },
    /**
     * Return ordered results
     *
     * @return {Array}
     */
    orderedResults: function orderedResults() {
      var _this = this;
      return this.typesIDs.filter(function (type) {
        return type in _this.results;
      }).map(function (type) {
        return {
          type: type,
          list: _this.results[type]
        };
      });
    },
    /**
     * Available filters
     * We only show filters that are available on the results
     *
     * @return {string[]}
     */
    availableFilters: function availableFilters() {
      return Object.keys(this.results);
    },
    /**
     * Applied filters
     *
     * @return {string[]}
     */
    usedFiltersIn: function usedFiltersIn() {
      var match;
      var filters = [];
      while ((match = _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.regexFilterIn.exec(this.query)) !== null) {
        filters.push(match[2]);
      }
      return filters;
    },
    /**
     * Applied anti filters
     *
     * @return {string[]}
     */
    usedFiltersNot: function usedFiltersNot() {
      var match;
      var filters = [];
      while ((match = _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.regexFilterNot.exec(this.query)) !== null) {
        filters.push(match[2]);
      }
      return filters;
    },
    /**
     * Is the current search too short
     *
     * @return {boolean}
     */
    isShortQuery: function isShortQuery() {
      return this.query && this.query.trim().length < _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.minSearchLength;
    },
    /**
     * Is the current search valid
     *
     * @return {boolean}
     */
    isValidQuery: function isValidQuery() {
      return this.query && this.query.trim() !== '' && !this.isShortQuery;
    },
    /**
     * Have we reached the end of all types searches
     *
     * @return {boolean}
     */
    isDoneSearching: function isDoneSearching() {
      return Object.values(this.reached).every(function (state) {
        return state === false;
      });
    },
    /**
     * Is there any search in progress
     *
     * @return {boolean}
     */
    isLoading: function isLoading() {
      return Object.values(this.loading).some(function (state) {
        return state === true;
      });
    }
  },
  created: function created() {
    var _this2 = this;
    return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:navigation:changed', _this2.resetForm);
              _context.next = 3;
              return (0,_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.getTypes)();
            case 3:
              _this2.types = _context.sent;
              _this2.logger.debug('Unified Search initialized with the following providers', _this2.types);
            case 5:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }))();
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('files:navigation:changed', this.resetForm);
  },
  mounted: function mounted() {
    var _this3 = this;
    if (OCP.Accessibility.disableKeyboardShortcuts()) {
      return;
    }
    document.addEventListener('keydown', function (event) {
      // if not already opened, allows us to trigger default browser on second keydown
      if (event.ctrlKey && event.key === 'f' && !_this3.open) {
        event.preventDefault();
        _this3.open = true;
        _this3.focusInput();
      }

      // https://www.w3.org/WAI/GL/wiki/Using_ARIA_menus
      if (_this3.open) {
        // If arrow down, focus next result
        if (event.key === 'ArrowDown') {
          _this3.focusNext(event);
        }

        // If arrow up, focus prev result
        if (event.key === 'ArrowUp') {
          _this3.focusPrev(event);
        }
      }
    });
  },
  methods: {
    onOpen: function onOpen() {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this4.focusInput();
                // Update types list in the background
                _context2.next = 3;
                return (0,_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.getTypes)();
              case 3:
                _this4.types = _context2.sent;
              case 4:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2);
      }))();
    },
    onClose: function onClose() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('nextcloud:unified-search.close');
    },
    resetForm: function resetForm() {
      this.$el.querySelector('form[role="search"]').reset();
    },
    /**
     * Reset the search state
     */
    onReset: function onReset() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('nextcloud:unified-search.reset');
      this.logger.debug('Search reset');
      this.query = '';
      this.resetState();
      this.focusInput();
    },
    resetState: function resetState() {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _this5.cursors = {};
                _this5.limits = {};
                _this5.reached = {};
                _this5.results = {};
                _this5.focused = null;
                _this5.triggered = false;
                _context3.next = 8;
                return _this5.cancelPendingRequests();
              case 8:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3);
      }))();
    },
    /**
     * Cancel any ongoing searches
     */
    cancelPendingRequests: function cancelPendingRequests() {
      var _this6 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
        var requests;
        return regeneratorRuntime.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                // Cloning so we can keep processing other requests
                requests = _this6.requests.slice(0);
                _this6.requests = [];

                // Cancel all pending requests
                _context4.next = 4;
                return Promise.all(requests.map(function (cancel) {
                  return cancel();
                }));
              case 4:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4);
      }))();
    },
    /**
     * Focus the search input on next tick
     */
    focusInput: function focusInput() {
      var _this7 = this;
      this.$nextTick(function () {
        _this7.$refs.input.focus();
        _this7.$refs.input.select();
      });
    },
    /**
     * If we have results already, open first one
     * If not, trigger the search again
     */
    onInputEnter: function onInputEnter() {
      if (this.hasResults) {
        var results = this.getResultsList();
        results[0].click();
        return;
      }
      this.onInput();
    },
    /**
     * Start searching on input
     */
    onInput: function onInput() {
      var _this8 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6() {
        var _iterator, _step, type, types, query;
        return regeneratorRuntime.wrap(function _callee6$(_context6) {
          while (1) {
            switch (_context6.prev = _context6.next) {
              case 0:
                // emit the search query
                (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('nextcloud:unified-search.search', {
                  query: _this8.query
                });

                // Do not search if not long enough
                if (!(_this8.query.trim() === '' || _this8.isShortQuery)) {
                  _context6.next = 5;
                  break;
                }
                _iterator = _createForOfIteratorHelper(_this8.typesIDs);
                try {
                  for (_iterator.s(); !(_step = _iterator.n()).done;) {
                    type = _step.value;
                    _this8.$delete(_this8.results, type);
                  }
                } catch (err) {
                  _iterator.e(err);
                } finally {
                  _iterator.f();
                }
                return _context6.abrupt("return");
              case 5:
                types = _this8.typesIDs;
                query = _this8.query; // Filter out types
                if (_this8.usedFiltersNot.length > 0) {
                  types = _this8.typesIDs.filter(function (type) {
                    return _this8.usedFiltersNot.indexOf(type) === -1;
                  });
                }

                // Only use those filters if any and check if they are valid
                if (_this8.usedFiltersIn.length > 0) {
                  types = _this8.typesIDs.filter(function (type) {
                    return _this8.usedFiltersIn.indexOf(type) > -1;
                  });
                }

                // Remove any filters from the query
                query = query.replace(_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.regexFilterIn, '').replace(_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.regexFilterNot, '');

                // Reset search if the query changed
                _context6.next = 12;
                return _this8.resetState();
              case 12:
                _this8.triggered = true;
                if (types.length) {
                  _context6.next = 16;
                  break;
                }
                // no results since no types were selected
                _this8.logger.error('No types to search in');
                return _context6.abrupt("return");
              case 16:
                _this8.$set(_this8.loading, 'all', true);
                _this8.logger.debug("Searching ".concat(query, " in"), types);
                Promise.all(types.map( /*#__PURE__*/function () {
                  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5(type) {
                    var _search, request, cancel, _yield$request, data;
                    return regeneratorRuntime.wrap(function _callee5$(_context5) {
                      while (1) {
                        switch (_context5.prev = _context5.next) {
                          case 0:
                            _context5.prev = 0;
                            // Init cancellable request
                            _search = (0,_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.search)({
                              type: type,
                              query: query
                            }), request = _search.request, cancel = _search.cancel;
                            _this8.requests.push(cancel);

                            // Fetch results
                            _context5.next = 5;
                            return request();
                          case 5:
                            _yield$request = _context5.sent;
                            data = _yield$request.data;
                            // Process results
                            if (data.ocs.data.entries.length > 0) {
                              _this8.$set(_this8.results, type, data.ocs.data.entries);
                            } else {
                              _this8.$delete(_this8.results, type);
                            }

                            // Save cursor if any
                            if (data.ocs.data.cursor) {
                              _this8.$set(_this8.cursors, type, data.ocs.data.cursor);
                            } else if (!data.ocs.data.isPaginated) {
                              // If no cursor and no pagination, we save the default amount
                              // provided by server's initial state `defaultLimit`
                              _this8.$set(_this8.limits, type, _this8.defaultLimit);
                            }

                            // Check if we reached end of pagination
                            if (data.ocs.data.entries.length < _this8.defaultLimit) {
                              _this8.$set(_this8.reached, type, true);
                            }

                            // If none already focused, focus the first rendered result
                            if (_this8.focused === null) {
                              _this8.focused = 0;
                            }
                            return _context5.abrupt("return", REQUEST_OK);
                          case 14:
                            _context5.prev = 14;
                            _context5.t0 = _context5["catch"](0);
                            _this8.$delete(_this8.results, type);

                            // If this is not a cancelled throw
                            if (!(_context5.t0.response && _context5.t0.response.status)) {
                              _context5.next = 21;
                              break;
                            }
                            _this8.logger.error("Error searching for ".concat(_this8.typesMap[type]), _context5.t0);
                            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(_this8.t('core', 'An error occurred while searching for {type}', {
                              type: _this8.typesMap[type]
                            }));
                            return _context5.abrupt("return", REQUEST_FAILED);
                          case 21:
                            return _context5.abrupt("return", REQUEST_CANCELED);
                          case 22:
                          case "end":
                            return _context5.stop();
                        }
                      }
                    }, _callee5, null, [[0, 14]]);
                  }));
                  return function (_x) {
                    return _ref.apply(this, arguments);
                  };
                }())).then(function (results) {
                  // Do not declare loading finished if the request have been cancelled
                  // This means another search was triggered and we're therefore still loading
                  if (results.some(function (result) {
                    return result === REQUEST_CANCELED;
                  })) {
                    return;
                  }
                  // We finished all searches
                  _this8.loading = {};
                });
              case 19:
              case "end":
                return _context6.stop();
            }
          }
        }, _callee6);
      }))();
    },
    onInputDebounced: _services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.enableLiveSearch ? debounce__WEBPACK_IMPORTED_MODULE_5___default()(function (e) {
      this.onInput(e);
    }, 500) : function () {
      this.triggered = false;
    },
    /**
     * Load more results for the provided type
     *
     * @param {string} type type
     */
    loadMore: function loadMore(type) {
      var _this9 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee7() {
        var _search2, request, cancel, _yield$request2, data, _this9$results$type;
        return regeneratorRuntime.wrap(function _callee7$(_context7) {
          while (1) {
            switch (_context7.prev = _context7.next) {
              case 0:
                if (!_this9.loading[type]) {
                  _context7.next = 2;
                  break;
                }
                return _context7.abrupt("return");
              case 2:
                if (!_this9.cursors[type]) {
                  _context7.next = 14;
                  break;
                }
                // Init cancellable request
                _search2 = (0,_services_UnifiedSearchService__WEBPACK_IMPORTED_MODULE_1__.search)({
                  type: type,
                  query: _this9.query,
                  cursor: _this9.cursors[type]
                }), request = _search2.request, cancel = _search2.cancel;
                _this9.requests.push(cancel);

                // Fetch results
                _context7.next = 7;
                return request();
              case 7:
                _yield$request2 = _context7.sent;
                data = _yield$request2.data;
                // Save cursor if any
                if (data.ocs.data.cursor) {
                  _this9.$set(_this9.cursors, type, data.ocs.data.cursor);
                }

                // Process results
                if (data.ocs.data.entries.length > 0) {
                  (_this9$results$type = _this9.results[type]).push.apply(_this9$results$type, _toConsumableArray(data.ocs.data.entries));
                }

                // Check if we reached end of pagination
                if (data.ocs.data.entries.length < _this9.defaultLimit) {
                  _this9.$set(_this9.reached, type, true);
                }
                _context7.next = 15;
                break;
              case 14:
                // If no cursor, we might have all the results already,
                // let's fake pagination and show the next xxx entries
                if (_this9.limits[type] && _this9.limits[type] >= 0) {
                  _this9.limits[type] += _this9.defaultLimit;

                  // Check if we reached end of pagination
                  if (_this9.limits[type] >= _this9.results[type].length) {
                    _this9.$set(_this9.reached, type, true);
                  }
                }
              case 15:
                // Focus result after render
                if (_this9.focused !== null) {
                  _this9.$nextTick(function () {
                    _this9.focusIndex(_this9.focused);
                  });
                }
              case 16:
              case "end":
                return _context7.stop();
            }
          }
        }, _callee7);
      }))();
    },
    /**
     * Return a subset of the array if the search provider
     * doesn't supports pagination
     *
     * @param {Array} list the results
     * @param {string} type the type
     * @return {Array}
     */
    limitIfAny: function limitIfAny(list, type) {
      if (type in this.limits) {
        return list.slice(0, this.limits[type]);
      }
      return list;
    },
    getResultsList: function getResultsList() {
      return this.$el.querySelectorAll('.unified-search__results .unified-search__result');
    },
    /**
     * Focus the first result if any
     *
     * @param {Event} event the keydown event
     */
    focusFirst: function focusFirst(event) {
      var results = this.getResultsList();
      if (results && results.length > 0) {
        if (event) {
          event.preventDefault();
        }
        this.focused = 0;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the next result if any
     *
     * @param {Event} event the keydown event
     */
    focusNext: function focusNext(event) {
      if (this.focused === null) {
        this.focusFirst(event);
        return;
      }
      var results = this.getResultsList();
      // If we're not focusing the last, focus the next one
      if (results && results.length > 0 && this.focused + 1 < results.length) {
        event.preventDefault();
        this.focused++;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the previous result if any
     *
     * @param {Event} event the keydown event
     */
    focusPrev: function focusPrev(event) {
      if (this.focused === null) {
        this.focusFirst(event);
        return;
      }
      var results = this.getResultsList();
      // If we're not focusing the first, focus the previous one
      if (results && results.length > 0 && this.focused > 0) {
        event.preventDefault();
        this.focused--;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the specified result index if it exists
     *
     * @param {number} index the result index
     */
    focusIndex: function focusIndex(index) {
      var results = this.getResultsList();
      if (results && results[index]) {
        results[index].focus();
      }
    },
    /**
     * Set the current focused element based on the target
     *
     * @param {Event} event the focus event
     */
    setFocusedIndex: function setFocusedIndex(event) {
      var entry = event.target;
      var results = this.getResultsList();
      var index = _toConsumableArray(results).findIndex(function (search) {
        return search === entry;
      });
      if (index > -1) {
        // let's not use focusIndex as the entry is already focused
        this.focused = index;
      }
    },
    onClickFilter: function onClickFilter(filter) {
      this.query = "".concat(this.query, " ").concat(filter).replace(/ {2}/g, ' ').trim();
      this.onInput();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    directives: [{
      name: "click-outside",
      rawName: "v-click-outside",
      value: _vm.clickOutsideConfig,
      expression: "clickOutsideConfig"
    }],
    staticClass: "header-menu",
    class: {
      "header-menu--opened": _vm.opened
    },
    attrs: {
      id: _vm.id
    }
  }, [_c("a", {
    staticClass: "header-menu__trigger",
    attrs: {
      href: "#",
      "aria-label": _vm.ariaLabel,
      "aria-controls": "header-menu-".concat(_vm.id),
      "aria-expanded": _vm.opened.toString()
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        return _vm.toggleMenu.apply(null, arguments);
      }
    }
  }, [_vm._t("trigger")], 2), _vm._v(" "), _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.opened,
      expression: "opened"
    }],
    staticClass: "header-menu__carret"
  }), _vm._v(" "), _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.opened,
      expression: "opened"
    }],
    staticClass: "header-menu__wrapper",
    attrs: {
      id: "header-menu-".concat(_vm.id),
      role: "menu"
    },
    on: {
      focusout: _vm.handleFocusOut
    }
  }, [_c("div", {
    staticClass: "header-menu__content"
  }, [_vm._t("default")], 2)])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("a", {
    staticClass: "unified-search__result",
    class: {
      "unified-search__result--focused": _vm.focused
    },
    attrs: {
      href: _vm.resourceUrl || "#"
    },
    on: {
      click: _vm.reEmitEvent,
      focus: _vm.reEmitEvent
    }
  }, [_c("div", {
    staticClass: "unified-search__result-icon",
    class: _defineProperty({
      "unified-search__result-icon--rounded": _vm.rounded,
      "unified-search__result-icon--no-preview": !_vm.hasValidThumbnail && !_vm.loaded,
      "unified-search__result-icon--with-thumbnail": _vm.hasValidThumbnail && _vm.loaded
    }, _vm.icon, !_vm.loaded && !_vm.isIconUrl),
    style: {
      backgroundImage: _vm.isIconUrl ? "url(".concat(_vm.icon, ")") : ""
    }
  }, [_vm.hasValidThumbnail ? _c("img", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.loaded,
      expression: "loaded"
    }],
    attrs: {
      src: _vm.thumbnailUrl,
      alt: ""
    },
    on: {
      error: _vm.onError,
      load: _vm.onLoad
    }
  }) : _vm._e()]), _vm._v(" "), _c("span", {
    staticClass: "unified-search__result-content"
  }, [_c("span", {
    staticClass: "unified-search__result-line-one",
    attrs: {
      title: _vm.title
    }
  }, [_c("NcHighlight", {
    attrs: {
      text: _vm.title,
      search: _vm.query
    }
  })], 1), _vm._v(" "), _vm.subline ? _c("span", {
    staticClass: "unified-search__result-line-two",
    attrs: {
      title: _vm.subline
    }
  }, [_vm._v(_vm._s(_vm.subline))]) : _vm._e()])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", [_c("svg", {
    staticClass: "unified-search__result-placeholder-gradient"
  }, [_c("defs", [_c("linearGradient", {
    attrs: {
      id: "unified-search__result-placeholder-gradient"
    }
  }, [_c("stop", {
    attrs: {
      offset: "0%",
      "stop-color": _vm.light
    }
  }, [_c("animate", {
    attrs: {
      attributeName: "stop-color",
      values: "".concat(_vm.light, "; ").concat(_vm.light, "; ").concat(_vm.dark, "; ").concat(_vm.dark, "; ").concat(_vm.light),
      dur: "2s",
      repeatCount: "indefinite"
    }
  })]), _vm._v(" "), _c("stop", {
    attrs: {
      offset: "100%",
      "stop-color": _vm.dark
    }
  }, [_c("animate", {
    attrs: {
      attributeName: "stop-color",
      values: "".concat(_vm.dark, "; ").concat(_vm.light, "; ").concat(_vm.light, "; ").concat(_vm.dark, "; ").concat(_vm.dark),
      dur: "2s",
      repeatCount: "indefinite"
    }
  })])], 1)], 1)]), _vm._v(" "), _vm._l([1, 2, 3], function (placeholder) {
    return _c("li", {
      key: placeholder
    }, [_c("svg", {
      staticClass: "unified-search__result-placeholder",
      attrs: {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "url(#unified-search__result-placeholder-gradient)"
      }
    }, [_c("rect", {
      staticClass: "unified-search__result-placeholder-icon"
    }), _vm._v(" "), _c("rect", {
      staticClass: "unified-search__result-placeholder-line-one"
    }), _vm._v(" "), _c("rect", {
      staticClass: "unified-search__result-placeholder-line-two",
      style: {
        width: "calc(".concat(_vm.randWidth(), "%)")
      }
    })])]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("HeaderMenu", {
    staticClass: "unified-search",
    attrs: {
      id: "unified-search",
      "exclude-click-outside-classes": "popover",
      open: _vm.open,
      "aria-label": _vm.ariaLabel
    },
    on: {
      "update:open": function updateOpen($event) {
        _vm.open = $event;
      },
      open: _vm.onOpen,
      close: _vm.onClose
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function fn() {
        return [_c("Magnify", {
          staticClass: "unified-search__trigger",
          attrs: {
            size: 22 /* fit better next to other 20px icons */,
            "fill-color": "var(--color-primary-text)"
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("div", {
    staticClass: "unified-search__input-wrapper"
  }, [_c("label", {
    attrs: {
      for: "unified-search__input"
    }
  }, [_vm._v(_vm._s(_vm.ariaLabel))]), _vm._v(" "), _c("div", {
    staticClass: "unified-search__input-row"
  }, [_c("form", {
    staticClass: "unified-search__form",
    class: {
      "icon-loading-small": _vm.isLoading
    },
    attrs: {
      role: "search"
    },
    on: {
      submit: function submit($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onInputEnter.apply(null, arguments);
      },
      reset: function reset($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onReset.apply(null, arguments);
      }
    }
  }, [_c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.query,
      expression: "query"
    }],
    ref: "input",
    staticClass: "unified-search__form-input",
    class: {
      "unified-search__form-input--with-reset": !!_vm.query
    },
    attrs: {
      id: "unified-search__input",
      type: "search",
      placeholder: _vm.t("core", "Search {types} …", {
        types: _vm.typesNames.join(", ")
      }),
      "aria-describedby": "unified-search-desc"
    },
    domProps: {
      value: _vm.query
    },
    on: {
      input: [function ($event) {
        if ($event.target.composing) return;
        _vm.query = $event.target.value;
      }, _vm.onInputDebounced],
      keypress: function keypress($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onInputEnter.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _c("p", {
    staticClass: "hidden-visually",
    attrs: {
      id: "unified-search-desc"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Search starts once you start typing and results may be reached with the arrow keys")) + "\n\t\t\t\t")]), _vm._v(" "), !!_vm.query && !_vm.isLoading ? _c("input", {
    staticClass: "unified-search__form-reset icon-close",
    attrs: {
      type: "reset",
      "aria-label": _vm.t("core", "Reset search"),
      value: ""
    }
  }) : _vm._e(), _vm._v(" "), !!_vm.query && !_vm.isLoading && !_vm.enableLiveSearch ? _c("input", {
    staticClass: "unified-search__form-submit icon-confirm",
    attrs: {
      type: "submit",
      "aria-label": _vm.t("core", "Start search"),
      value: ""
    }
  }) : _vm._e()]), _vm._v(" "), _vm.availableFilters.length > 1 ? _c("NcActions", {
    staticClass: "unified-search__filters",
    attrs: {
      placement: "bottom",
      container: ".unified-search__input-wrapper"
    }
  }, _vm._l(_vm.availableFilters, function (type) {
    return _c("NcActionButton", {
      key: type,
      attrs: {
        icon: "icon-filter",
        title: _vm.t("core", "Search for {name} only", {
          name: _vm.typesMap[type]
        })
      },
      on: {
        click: function click($event) {
          $event.stopPropagation();
          return _vm.onClickFilter("in:".concat(type));
        }
      }
    }, [_vm._v("\n\t\t\t\t\t" + _vm._s("in:".concat(type)) + "\n\t\t\t\t")]);
  }), 1) : _vm._e()], 1)]), _vm._v(" "), !_vm.hasResults ? [_vm.isLoading ? _c("SearchResultPlaceholders") : _vm.isValidQuery ? _c("NcEmptyContent", {
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Magnify")];
      },
      proxy: true
    }], null, false, 931131664)
  }, [_vm.triggered ? _c("NcHighlight", {
    attrs: {
      text: _vm.t("core", "No results for {query}", {
        query: _vm.query
      }),
      search: _vm.query
    }
  }) : _c("div", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "Press enter to start searching")) + "\n\t\t\t")])], 1) : !_vm.isLoading || _vm.isShortQuery ? _c("NcEmptyContent", {
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Magnify")];
      },
      proxy: true
    }, _vm.isShortQuery ? {
      key: "desc",
      fn: function fn() {
        return [_vm._v("\n\t\t\t\t" + _vm._s(_vm.n("core", "Please enter {minSearchLength} character or more to search", "Please enter {minSearchLength} characters  or more to search", _vm.minSearchLength, {
          minSearchLength: _vm.minSearchLength
        })) + "\n\t\t\t")];
      },
      proxy: true
    } : null], null, true)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Start typing to search")) + "\n\t\t\t")]) : _vm._e()] : _vm._l(_vm.orderedResults, function (_ref, typesIndex) {
    var list = _ref.list,
      type = _ref.type;
    return _c("ul", {
      key: type,
      staticClass: "unified-search__results",
      class: "unified-search__results-".concat(type),
      attrs: {
        "aria-label": _vm.typesMap[type]
      }
    }, [_c("h2", {
      staticClass: "unified-search__results-header"
    }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.typesMap[type]) + "\n\t\t\t")]), _vm._v(" "), _vm._l(_vm.limitIfAny(list, type), function (result, index) {
      return _c("li", {
        key: result.resourceUrl
      }, [_c("SearchResult", _vm._b({
        attrs: {
          query: _vm.query,
          focused: _vm.focused === 0 && typesIndex === 0 && index === 0
        },
        on: {
          focus: _vm.setFocusedIndex
        }
      }, "SearchResult", result, false))], 1);
    }), _vm._v(" "), _c("li", [!_vm.reached[type] ? _c("SearchResult", {
      staticClass: "unified-search__result-more",
      attrs: {
        title: _vm.loading[type] ? _vm.t("core", "Loading more results …") : _vm.t("core", "Load more results"),
        "icon-class": _vm.loading[type] ? "icon-loading-small" : ""
      },
      on: {
        click: function click($event) {
          $event.stopPropagation();
          return _vm.loadMore(type);
        },
        focus: _vm.setFocusedIndex
      }
    }) : _vm._e()], 1)], 2);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".header-menu__trigger[data-v-261cf1f8] {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  width: 50px;\n  height: 44px;\n  margin: 2px 0;\n  padding: 0;\n  cursor: pointer;\n  opacity: 0.85;\n}\n.header-menu--opened .header-menu__trigger[data-v-261cf1f8], .header-menu__trigger[data-v-261cf1f8]:hover, .header-menu__trigger[data-v-261cf1f8]:focus, .header-menu__trigger[data-v-261cf1f8]:active {\n  opacity: 1;\n}\n.header-menu__trigger[data-v-261cf1f8]:focus-visible {\n  outline: none;\n}\n.header-menu__wrapper[data-v-261cf1f8] {\n  position: fixed;\n  z-index: 2000;\n  top: 50px;\n  right: 0;\n  box-sizing: border-box;\n  margin: 0 8px;\n  border-radius: 0 0 var(--border-radius) var(--border-radius);\n  background-color: var(--color-main-background);\n  filter: drop-shadow(0 1px 5px var(--color-box-shadow));\n  padding: 8px;\n  border-radius: var(--border-radius-large);\n}\n.header-menu__carret[data-v-261cf1f8] {\n  position: absolute;\n  z-index: 2001;\n  left: calc(50% - 10px);\n  bottom: 0;\n  width: 0;\n  height: 0;\n  content: \" \";\n  pointer-events: none;\n  border: 10px solid transparent;\n  border-bottom-color: var(--color-main-background);\n}\n.header-menu__content[data-v-261cf1f8] {\n  overflow: auto;\n  width: 350px;\n  max-width: calc(100vw - 16px);\n  min-height: 66px;\n  max-height: calc(100vh - 100px);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__result[data-v-69f8eb86] {\n  display: flex;\n  align-items: center;\n  height: 44px;\n  padding: 10px;\n  border-bottom: 1px solid var(--color-border);\n  border-radius: var(--border-radius-large) !important;\n}\n.unified-search__result[data-v-69f8eb86]:last-child {\n  border-bottom: none;\n}\n.unified-search__result--focused[data-v-69f8eb86], .unified-search__result[data-v-69f8eb86]:active, .unified-search__result[data-v-69f8eb86]:hover, .unified-search__result[data-v-69f8eb86]:focus {\n  background-color: var(--color-background-hover);\n}\n.unified-search__result *[data-v-69f8eb86] {\n  cursor: pointer;\n}\n.unified-search__result-icon[data-v-69f8eb86] {\n  overflow: hidden;\n  width: 44px;\n  height: 44px;\n  border-radius: var(--border-radius);\n  background-repeat: no-repeat;\n  background-position: center center;\n  background-size: 32px;\n}\n.unified-search__result-icon--rounded[data-v-69f8eb86] {\n  border-radius: 22px;\n}\n.unified-search__result-icon--no-preview[data-v-69f8eb86] {\n  background-size: 32px;\n}\n.unified-search__result-icon--with-thumbnail[data-v-69f8eb86] {\n  background-size: cover;\n}\n.unified-search__result-icon--with-thumbnail[data-v-69f8eb86]:not(.unified-search__result-icon--rounded) {\n  max-width: 42px;\n  max-height: 42px;\n  border: 1px solid var(--color-border);\n}\n.unified-search__result-icon img[data-v-69f8eb86] {\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n  object-position: center;\n}\n.unified-search__result-icon[data-v-69f8eb86], .unified-search__result-actions[data-v-69f8eb86] {\n  flex: 0 0 44px;\n}\n.unified-search__result-content[data-v-69f8eb86] {\n  display: flex;\n  align-items: center;\n  flex: 1 1 100%;\n  flex-wrap: wrap;\n  min-width: 0;\n  padding-left: 10px;\n}\n.unified-search__result-line-one[data-v-69f8eb86], .unified-search__result-line-two[data-v-69f8eb86] {\n  overflow: hidden;\n  flex: 1 1 100%;\n  margin: 1px 0;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n  color: inherit;\n  font-size: inherit;\n}\n.unified-search__result-line-two[data-v-69f8eb86] {\n  opacity: 0.7;\n  font-size: var(--default-font-size);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__result-placeholder-gradient[data-v-ff2497f4] {\n  position: fixed;\n  height: 0;\n  width: 0;\n  z-index: -1;\n}\n.unified-search__result-placeholder[data-v-ff2497f4] {\n  width: calc(100% - 2 * 10px);\n  height: 44px;\n  margin: 10px;\n}\n.unified-search__result-placeholder-icon[data-v-ff2497f4] {\n  width: 44px;\n  height: 44px;\n  rx: var(--border-radius);\n  ry: var(--border-radius);\n}\n.unified-search__result-placeholder-line-one[data-v-ff2497f4], .unified-search__result-placeholder-line-two[data-v-ff2497f4] {\n  width: calc(100% - 54px);\n  height: 1em;\n  x: 54px;\n}\n.unified-search__result-placeholder-line-one[data-v-ff2497f4] {\n  y: 5px;\n}\n.unified-search__result-placeholder-line-two[data-v-ff2497f4] {\n  y: 25px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__trigger[data-v-d79c2f68] {\n  filter: var(--background-image-invert-if-bright);\n}\n.unified-search__input-wrapper[data-v-d79c2f68] {\n  position: sticky;\n  z-index: 2;\n  top: 0;\n  display: inline-flex;\n  flex-direction: column;\n  align-items: center;\n  width: 100%;\n  background-color: var(--color-main-background);\n}\n.unified-search__input-wrapper label[for=unified-search__input][data-v-d79c2f68] {\n  align-self: flex-start;\n  font-weight: bold;\n  font-size: 19px;\n  margin-left: 13px;\n}\n.unified-search__form-input[data-v-d79c2f68] {\n  margin: 0 !important;\n}\n.unified-search__input-row[data-v-d79c2f68] {\n  display: flex;\n  width: 100%;\n  align-items: center;\n}\n.unified-search__filters[data-v-d79c2f68] {\n  margin: 10px 0 10px 5px;\n}\n.unified-search__filters ul[data-v-d79c2f68] {\n  display: inline-flex;\n  justify-content: space-between;\n}\n.unified-search__form[data-v-d79c2f68] {\n  position: relative;\n  width: 100%;\n  margin: 10px 0;\n}\n.unified-search__form[data-v-d79c2f68]::after {\n  right: 6px;\n  left: auto;\n}\n.unified-search__form-input[data-v-d79c2f68], .unified-search__form-reset[data-v-d79c2f68] {\n  margin: 3px;\n}\n.unified-search__form-input[data-v-d79c2f68] {\n  width: 100%;\n  height: 34px;\n  padding: 6px;\n}\n.unified-search__form-input[data-v-d79c2f68], .unified-search__form-input[placeholder][data-v-d79c2f68], .unified-search__form-input[data-v-d79c2f68]::placeholder {\n  overflow: hidden;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n}\n.unified-search__form-input[data-v-d79c2f68]::-webkit-search-decoration, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-cancel-button, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-results-button, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-results-decoration {\n  -webkit-appearance: none;\n}\n.icon-loading-small .unified-search__form-input[data-v-d79c2f68], .unified-search__form-input--with-reset[data-v-d79c2f68] {\n  padding-right: 34px;\n}\n.unified-search__form-reset[data-v-d79c2f68], .unified-search__form-submit[data-v-d79c2f68] {\n  position: absolute;\n  top: 0;\n  right: 4px;\n  width: 28px;\n  height: 28px;\n  min-height: 30px;\n  padding: 0;\n  opacity: 0.5;\n  border: none;\n  background-color: transparent;\n  margin-right: 0;\n}\n.unified-search__form-reset[data-v-d79c2f68]:hover, .unified-search__form-reset[data-v-d79c2f68]:focus, .unified-search__form-reset[data-v-d79c2f68]:active, .unified-search__form-submit[data-v-d79c2f68]:hover, .unified-search__form-submit[data-v-d79c2f68]:focus, .unified-search__form-submit[data-v-d79c2f68]:active {\n  opacity: 1;\n}\n.unified-search__form-submit[data-v-d79c2f68] {\n  right: 28px;\n}\n.unified-search__results[data-v-d79c2f68] {\n  display: flex;\n  flex-direction: column;\n  gap: 4px;\n}\n.unified-search__results-header[data-v-d79c2f68] {\n  display: block;\n  margin: 10px;\n  margin-bottom: 6px;\n  margin-left: 13px;\n  color: var(--color-primary-element);\n  font-size: 19px;\n  font-weight: bold;\n}\n.unified-search .unified-search__result-more[data-v-d79c2f68] {\n  color: var(--color-text-maxcontrast);\n}\n.unified-search .empty-content[data-v-d79c2f68] {\n  margin: 10vh 0;\n}\n.unified-search .empty-content[data-v-d79c2f68] .empty-content__title {\n  font-weight: normal;\n  font-size: var(--default-font-size);\n  padding: 0 15px;\n  text-align: center;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./core/src/components/HeaderMenu.vue":
/*!********************************************!*\
  !*** ./core/src/components/HeaderMenu.vue ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true& */ "./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true&");
/* harmony import */ var _HeaderMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./HeaderMenu.vue?vue&type=script&lang=js& */ "./core/src/components/HeaderMenu.vue?vue&type=script&lang=js&");
/* harmony import */ var _HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& */ "./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _HeaderMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "261cf1f8",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/HeaderMenu.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue":
/*!************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&");
/* harmony import */ var _SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=script&lang=js& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&");
/* harmony import */ var _SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "69f8eb86",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/UnifiedSearch/SearchResult.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue":
/*!************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&");
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=script&lang=js& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&");
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "ff2497f4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/UnifiedSearch/SearchResultPlaceholders.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue":
/*!******************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& */ "./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&");
/* harmony import */ var _UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=script&lang=js& */ "./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&");
/* harmony import */ var _UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "d79c2f68",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/views/UnifiedSearch.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/HeaderMenu.vue?vue&type=script&lang=js&":
/*!*********************************************************************!*\
  !*** ./core/src/components/HeaderMenu.vue?vue&type=script&lang=js& ***!
  \*********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderMenu.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true&":
/*!***************************************************************************************!*\
  !*** ./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true& ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_template_id_261cf1f8_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=template&id=261cf1f8&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&":
/*!*******************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& ***!
  \*******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&");


/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&");


/***/ }),

/***/ "./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderMenu_vue_vue_type_style_index_0_id_261cf1f8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/HeaderMenu.vue?vue&type=style&index=0&id=261cf1f8&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!**********************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");


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
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
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
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"core-unified-search": 0
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
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./core/src/unified-search.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-unified-search.js.map?v=08316232662cf2b0033e