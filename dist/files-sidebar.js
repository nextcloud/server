/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/models/Tab.js":
/*!**************************************!*\
  !*** ./apps/files/src/models/Tab.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Tab; }
/* harmony export */ });
/* harmony import */ var _skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @skjnldsv/sanitize-svg */ "./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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

var Tab = /*#__PURE__*/function () {
  /**
   * Create a new tab instance
   *
   * @param {object} options destructuring object
   * @param {string} options.id the unique id of this tab
   * @param {string} options.name the translated tab name
   * @param {?string} options.icon the icon css class
   * @param {?string} options.iconSvg the icon in svg format
   * @param {Function} options.mount function to mount the tab
   * @param {Function} options.update function to update the tab
   * @param {Function} options.destroy function to destroy the tab
   * @param {Function} [options.enabled] define conditions whether this tab is active. Must returns a boolean
   * @param {Function} [options.scrollBottomReached] executed when the tab is scrolled to the bottom
   */
  function Tab() {
    var _this = this;
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      id = _ref.id,
      name = _ref.name,
      icon = _ref.icon,
      iconSvg = _ref.iconSvg,
      mount = _ref.mount,
      update = _ref.update,
      destroy = _ref.destroy,
      enabled = _ref.enabled,
      scrollBottomReached = _ref.scrollBottomReached;
    _classCallCheck(this, Tab);
    _defineProperty(this, "_id", void 0);
    _defineProperty(this, "_name", void 0);
    _defineProperty(this, "_icon", void 0);
    _defineProperty(this, "_iconSvgSanitized", void 0);
    _defineProperty(this, "_mount", void 0);
    _defineProperty(this, "_update", void 0);
    _defineProperty(this, "_destroy", void 0);
    _defineProperty(this, "_enabled", void 0);
    _defineProperty(this, "_scrollBottomReached", void 0);
    if (enabled === undefined) {
      enabled = function enabled() {
        return true;
      };
    }
    if (scrollBottomReached === undefined) {
      scrollBottomReached = function scrollBottomReached() {};
    }

    // Sanity checks
    if (typeof id !== 'string' || id.trim() === '') {
      throw new Error('The id argument is not a valid string');
    }
    if (typeof name !== 'string' || name.trim() === '') {
      throw new Error('The name argument is not a valid string');
    }
    if ((typeof icon !== 'string' || icon.trim() === '') && typeof iconSvg !== 'string') {
      throw new Error('Missing valid string for icon or iconSvg argument');
    }
    if (typeof mount !== 'function') {
      throw new Error('The mount argument should be a function');
    }
    if (typeof update !== 'function') {
      throw new Error('The update argument should be a function');
    }
    if (typeof destroy !== 'function') {
      throw new Error('The destroy argument should be a function');
    }
    if (typeof enabled !== 'function') {
      throw new Error('The enabled argument should be a function');
    }
    if (typeof scrollBottomReached !== 'function') {
      throw new Error('The scrollBottomReached argument should be a function');
    }
    this._id = id;
    this._name = name;
    this._icon = icon;
    this._mount = mount;
    this._update = update;
    this._destroy = destroy;
    this._enabled = enabled;
    this._scrollBottomReached = scrollBottomReached;
    if (typeof iconSvg === 'string') {
      (0,_skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__.sanitizeSVG)(iconSvg).then(function (sanitizedSvg) {
        _this._iconSvgSanitized = sanitizedSvg;
      });
    }
  }
  _createClass(Tab, [{
    key: "id",
    get: function get() {
      return this._id;
    }
  }, {
    key: "name",
    get: function get() {
      return this._name;
    }
  }, {
    key: "icon",
    get: function get() {
      return this._icon;
    }
  }, {
    key: "iconSvg",
    get: function get() {
      return this._iconSvgSanitized;
    }
  }, {
    key: "mount",
    get: function get() {
      return this._mount;
    }
  }, {
    key: "update",
    get: function get() {
      return this._update;
    }
  }, {
    key: "destroy",
    get: function get() {
      return this._destroy;
    }
  }, {
    key: "enabled",
    get: function get() {
      return this._enabled;
    }
  }, {
    key: "scrollBottomReached",
    get: function get() {
      return this._scrollBottomReached;
    }
  }]);
  return Tab;
}();


/***/ }),

/***/ "./apps/files/src/services/FileInfo.js":
/*!*********************************************!*\
  !*** ./apps/files/src/services/FileInfo.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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



/**
 * @param {any} url -
 */
/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(_x) {
  return _ref.apply(this, arguments);
}
function _ref() {
  _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(url) {
    var response, file, fileInfo;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"])({
              method: 'PROPFIND',
              url: url,
              data: "<?xml version=\"1.0\"?>\n\t\t\t<d:propfind  xmlns:d=\"DAV:\"\n\t\t\t\txmlns:oc=\"http://owncloud.org/ns\"\n\t\t\t\txmlns:nc=\"http://nextcloud.org/ns\"\n\t\t\t\txmlns:ocs=\"http://open-collaboration-services.org/ns\">\n\t\t\t<d:prop>\n\t\t\t\t<d:getlastmodified />\n\t\t\t\t<d:getetag />\n\t\t\t\t<d:getcontenttype />\n\t\t\t\t<d:resourcetype />\n\t\t\t\t<oc:fileid />\n\t\t\t\t<oc:permissions />\n\t\t\t\t<oc:size />\n\t\t\t\t<d:getcontentlength />\n\t\t\t\t<nc:has-preview />\n\t\t\t\t<nc:mount-type />\n\t\t\t\t<nc:is-encrypted />\n\t\t\t\t<ocs:share-permissions />\n\t\t\t\t<nc:share-attributes />\n\t\t\t\t<oc:tags />\n\t\t\t\t<oc:favorite />\n\t\t\t\t<oc:comments-unread />\n\t\t\t\t<oc:owner-id />\n\t\t\t\t<oc:owner-display-name />\n\t\t\t\t<oc:share-types />\n\t\t\t</d:prop>\n\t\t\t</d:propfind>"
            });
          case 2:
            response = _context.sent;
            // TODO: create new parser or use cdav-lib when available
            file = OCA.Files.App.fileList.filesClient._client.parseMultiStatus(response.data); // TODO: create new parser or use cdav-lib when available
            fileInfo = OCA.Files.App.fileList.filesClient._parseFileInfo(file[0]); // TODO remove when no more legacy backbone is used
            fileInfo.get = function (key) {
              return fileInfo[key];
            };
            fileInfo.isDirectory = function () {
              return fileInfo.mimetype === 'httpd/unix-directory';
            };
            return _context.abrupt("return", fileInfo);
          case 8:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _ref.apply(this, arguments);
}

/***/ }),

/***/ "./apps/files/src/services/Sidebar.js":
/*!********************************************!*\
  !*** ./apps/files/src/services/Sidebar.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Sidebar; }
/* harmony export */ });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
var Sidebar = /*#__PURE__*/function () {
  function Sidebar() {
    _classCallCheck(this, Sidebar);
    _defineProperty(this, "_state", void 0);
    // init empty state
    this._state = {};

    // init default values
    this._state.tabs = [];
    this._state.views = [];
    this._state.file = '';
    this._state.activeTab = '';
    console.debug('OCA.Files.Sidebar initialized');
  }

  /**
   * Get the sidebar state
   *
   * @readonly
   * @memberof Sidebar
   * @return {object} the data state
   */
  _createClass(Sidebar, [{
    key: "state",
    get: function get() {
      return this._state;
    }

    /**
     * Register a new tab view
     *
     * @memberof Sidebar
     * @param {object} tab a new unregistered tab
     * @return {boolean}
     */
  }, {
    key: "registerTab",
    value: function registerTab(tab) {
      var hasDuplicate = this._state.tabs.findIndex(function (check) {
        return check.id === tab.id;
      }) > -1;
      if (!hasDuplicate) {
        this._state.tabs.push(tab);
        return true;
      }
      console.error("An tab with the same id ".concat(tab.id, " already exists"), tab);
      return false;
    }
  }, {
    key: "registerSecondaryView",
    value: function registerSecondaryView(view) {
      var hasDuplicate = this._state.views.findIndex(function (check) {
        return check.id === view.id;
      }) > -1;
      if (!hasDuplicate) {
        this._state.views.push(view);
        return true;
      }
      console.error('A similar view already exists', view);
      return false;
    }

    /**
     * Return current opened file
     *
     * @memberof Sidebar
     * @return {string} the current opened file
     */
  }, {
    key: "file",
    get: function get() {
      return this._state.file;
    }

    /**
     * Set the current visible sidebar tab
     *
     * @memberof Sidebar
     * @param {string} id the tab unique id
     */
  }, {
    key: "setActiveTab",
    value: function setActiveTab(id) {
      this._state.activeTab = id;
    }
  }]);
  return Sidebar;
}();


/***/ }),

/***/ "./apps/files/src/sidebar.js":
/*!***********************************!*\
  !*** ./apps/files/src/sidebar.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Sidebar.vue */ "./apps/files/src/views/Sidebar.vue");
/* harmony import */ var _services_Sidebar__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/Sidebar */ "./apps/files/src/services/Sidebar.js");
/* harmony import */ var _models_Tab__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./models/Tab */ "./apps/files/src/models/Tab.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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






vue__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate;

// Init Sidebar Service
if (!window.OCA.Files) {
  window.OCA.Files = {};
}
Object.assign(window.OCA.Files, {
  Sidebar: new _services_Sidebar__WEBPACK_IMPORTED_MODULE_2__["default"]()
});
Object.assign(window.OCA.Files.Sidebar, {
  Tab: _models_Tab__WEBPACK_IMPORTED_MODULE_3__["default"]
});
console.debug('OCA.Files.Sidebar initialized');
window.addEventListener('DOMContentLoaded', function () {
  var contentElement = document.querySelector('body > .content') || document.querySelector('body > #content');

  // Make sure we have a proper layout
  if (contentElement) {
    // Make sure we have a mountpoint
    if (!document.getElementById('app-sidebar')) {
      var sidebarElement = document.createElement('div');
      sidebarElement.id = 'app-sidebar';
      contentElement.appendChild(sidebarElement);
    }
  }

  // Init vue app
  var View = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
  var AppSidebar = new View({
    name: 'SidebarRoot'
  });
  AppSidebar.$mount('#app-sidebar');
  window.OCA.Files.Sidebar.open = AppSidebar.open;
  window.OCA.Files.Sidebar.close = AppSidebar.close;
  window.OCA.Files.Sidebar.setFullScreenMode = AppSidebar.setFullScreenMode;
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LegacyView',
  props: {
    component: {
      type: Object,
      required: true
    },
    fileInfo: {
      type: Object,
      default: function _default() {},
      required: true
    }
  },
  watch: {
    fileInfo: function fileInfo(_fileInfo) {
      // update the backbone model FileInfo
      this.setFileInfo(_fileInfo);
    }
  },
  mounted: function mounted() {
    // append the backbone element and set the FileInfo
    this.component.$el.replaceAll(this.$el);
    this.setFileInfo(this.fileInfo);
  },
  methods: {
    setFileInfo: function setFileInfo(fileInfo) {
      this.component.setFileInfo(new OCA.Files.FileInfoModel(fileInfo));
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebarTab__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_1__);
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SidebarTab',
  components: {
    NcAppSidebarTab: (_nextcloud_vue_dist_Components_NcAppSidebarTab__WEBPACK_IMPORTED_MODULE_0___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_1___default())
  },
  props: {
    fileInfo: {
      type: Object,
      default: function _default() {},
      required: true
    },
    id: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: false
    },
    /**
     * Lifecycle methods.
     * They are prefixed with `on` to avoid conflict with Vue
     * methods like this.destroy
     */
    onMount: {
      type: Function,
      required: true
    },
    onUpdate: {
      type: Function,
      required: true
    },
    onDestroy: {
      type: Function,
      required: true
    },
    onScrollBottomReached: {
      type: Function,
      default: function _default() {}
    }
  },
  data: function data() {
    return {
      loading: true
    };
  },
  computed: {
    // TODO: implement a better way to force pass a prop from Sidebar
    activeTab: function activeTab() {
      return this.$parent.activeTab;
    }
  },
  watch: {
    fileInfo: function fileInfo(newFile, oldFile) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                if (!(newFile.id !== oldFile.id)) {
                  _context.next = 5;
                  break;
                }
                _this.loading = true;
                _context.next = 4;
                return _this.onUpdate(_this.fileInfo);
              case 4:
                _this.loading = false;
              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    }
  },
  mounted: function mounted() {
    var _this2 = this;
    return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
      return regeneratorRuntime.wrap(function _callee2$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              _this2.loading = true;
              // Mount the tab:  mounting point,   fileInfo,      vue context
              _context2.next = 3;
              return _this2.onMount(_this2.$refs.mount, _this2.fileInfo, _this2.$refs.tab);
            case 3:
              _this2.loading = false;
            case 4:
            case "end":
              return _context2.stop();
          }
        }
      }, _callee2);
    }))();
  },
  beforeDestroy: function beforeDestroy() {
    var _this3 = this;
    return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
      return regeneratorRuntime.wrap(function _callee3$(_context3) {
        while (1) {
          switch (_context3.prev = _context3.next) {
            case 0:
              _context3.next = 2;
              return _this3.onDestroy();
            case 2:
            case "end":
              return _context3.stop();
          }
        }
      }, _callee3);
    }))();
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_moment__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebar__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _services_FileInfo__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../services/FileInfo */ "./apps/files/src/services/FileInfo.js");
/* harmony import */ var _components_SidebarTab__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../components/SidebarTab */ "./apps/files/src/components/SidebarTab.vue");
/* harmony import */ var _components_LegacyView__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/LegacyView */ "./apps/files/src/components/LegacyView.vue");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }












/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Sidebar',
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_7___default()),
    NcAppSidebar: (_nextcloud_vue_dist_Components_NcAppSidebar__WEBPACK_IMPORTED_MODULE_6___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_8___default()),
    LegacyView: _components_LegacyView__WEBPACK_IMPORTED_MODULE_11__["default"],
    SidebarTab: _components_SidebarTab__WEBPACK_IMPORTED_MODULE_10__["default"]
  },
  data: function data() {
    return {
      // reactive state
      Sidebar: OCA.Files.Sidebar.state,
      error: null,
      loading: true,
      fileInfo: null,
      starLoading: false,
      isFullScreen: false,
      hasLowHeight: false
    };
  },
  computed: {
    /**
     * Current filename
     * This is bound to the Sidebar service and
     * is used to load a new file
     *
     * @return {string}
     */
    file: function file() {
      return this.Sidebar.file;
    },
    /**
     * List of all the registered tabs
     *
     * @return {Array}
     */
    tabs: function tabs() {
      return this.Sidebar.tabs;
    },
    /**
     * List of all the registered views
     *
     * @return {Array}
     */
    views: function views() {
      return this.Sidebar.views;
    },
    /**
     * Current user dav root path
     *
     * @return {string}
     */
    davPath: function davPath() {
      var user = OC.getCurrentUser().uid;
      return OC.linkToRemote("dav/files/".concat(user).concat((0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__.encodePath)(this.file)));
    },
    /**
     * Current active tab handler
     *
     * @param {string} id the tab id to set as active
     * @return {string} the current active tab
     */
    activeTab: function activeTab() {
      return this.Sidebar.activeTab;
    },
    /**
     * Sidebar subtitle
     *
     * @return {string}
     */
    subtitle: function subtitle() {
      return "".concat(this.size, ", ").concat(this.time);
    },
    /**
     * File last modified formatted string
     *
     * @return {string}
     */
    time: function time() {
      return OC.Util.relativeModifiedDate(this.fileInfo.mtime);
    },
    /**
     * File last modified full string
     *
     * @return {string}
     */
    fullTime: function fullTime() {
      return _nextcloud_moment__WEBPACK_IMPORTED_MODULE_4___default()(this.fileInfo.mtime).format('LLL');
    },
    /**
     * File size formatted string
     *
     * @return {string}
     */
    size: function size() {
      return OC.Util.humanFileSize(this.fileInfo.size);
    },
    /**
     * File background/figure to illustrate the sidebar header
     *
     * @return {string}
     */
    background: function background() {
      return this.getPreviewIfAny(this.fileInfo);
    },
    /**
     * App sidebar v-binding object
     *
     * @return {object}
     */
    appSidebar: function appSidebar() {
      if (this.fileInfo) {
        return {
          'data-mimetype': this.fileInfo.mimetype,
          'star-loading': this.starLoading,
          active: this.activeTab,
          background: this.background,
          class: {
            'app-sidebar--has-preview': this.fileInfo.hasPreview && !this.isFullScreen,
            'app-sidebar--full': this.isFullScreen
          },
          compact: this.hasLowHeight || !this.fileInfo.hasPreview || this.isFullScreen,
          loading: this.loading,
          starred: this.fileInfo.isFavourited,
          subtitle: this.subtitle,
          subtitleTooltip: this.fullTime,
          title: this.fileInfo.name,
          titleTooltip: this.fileInfo.name
        };
      } else if (this.error) {
        return {
          key: 'error',
          // force key to re-render
          subtitle: '',
          title: ''
        };
      }
      // no fileInfo yet, showing empty data
      return {
        loading: this.loading,
        subtitle: '',
        title: ''
      };
    },
    /**
     * Default action object for the current file
     *
     * @return {object}
     */
    defaultAction: function defaultAction() {
      return this.fileInfo && OCA.Files && OCA.Files.App && OCA.Files.App.fileList && OCA.Files.App.fileList.fileActions && OCA.Files.App.fileList.fileActions.getDefaultFileAction && OCA.Files.App.fileList.fileActions.getDefaultFileAction(this.fileInfo.mimetype, this.fileInfo.type, OC.PERMISSION_READ);
    },
    /**
     * Dynamic header click listener to ensure
     * nothing is listening for a click if there
     * is no default action
     *
     * @return {string|null}
     */
    defaultActionListener: function defaultActionListener() {
      return this.defaultAction ? 'figure-click' : null;
    },
    isSystemTagsEnabled: function isSystemTagsEnabled() {
      return OCA && 'SystemTags' in OCA;
    }
  },
  created: function created() {
    window.addEventListener('resize', this.handleWindowResize);
    this.handleWindowResize();
  },
  beforeDestroy: function beforeDestroy() {
    window.removeEventListener('resize', this.handleWindowResize);
  },
  methods: {
    /**
     * Can this tab be displayed ?
     *
     * @param {object} tab a registered tab
     * @return {boolean}
     */
    canDisplay: function canDisplay(tab) {
      return tab.enabled(this.fileInfo);
    },
    resetData: function resetData() {
      var _this = this;
      this.error = null;
      this.fileInfo = null;
      this.$nextTick(function () {
        if (_this.$refs.tabs) {
          _this.$refs.tabs.updateTabs();
        }
      });
    },
    getPreviewIfAny: function getPreviewIfAny(fileInfo) {
      if (fileInfo.hasPreview && !this.isFullScreen) {
        return OC.generateUrl("/core/preview?fileId=".concat(fileInfo.id, "&x=").concat(screen.width, "&y=").concat(screen.height, "&a=true"));
      }
      return this.getIconUrl(fileInfo);
    },
    /**
     * Copied from https://github.com/nextcloud/server/blob/16e0887ec63591113ee3f476e0c5129e20180cde/apps/files/js/filelist.js#L1377
     * TODO: We also need this as a standalone library
     *
     * @param {object} fileInfo the fileinfo
     * @return {string} Url to the icon for mimeType
     */
    getIconUrl: function getIconUrl(fileInfo) {
      var mimeType = fileInfo.mimetype || 'application/octet-stream';
      if (mimeType === 'httpd/unix-directory') {
        // use default folder icon
        if (fileInfo.mountType === 'shared' || fileInfo.mountType === 'shared-root') {
          return OC.MimeType.getIconUrl('dir-shared');
        } else if (fileInfo.mountType === 'external-root') {
          return OC.MimeType.getIconUrl('dir-external');
        } else if (fileInfo.mountType !== undefined && fileInfo.mountType !== '') {
          return OC.MimeType.getIconUrl('dir-' + fileInfo.mountType);
        } else if (fileInfo.shareTypes && (fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_5__.Type.SHARE_TYPE_LINK) > -1 || fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_5__.Type.SHARE_TYPE_EMAIL) > -1)) {
          return OC.MimeType.getIconUrl('dir-public');
        } else if (fileInfo.shareTypes && fileInfo.shareTypes.length > 0) {
          return OC.MimeType.getIconUrl('dir-shared');
        }
        return OC.MimeType.getIconUrl('dir');
      }
      return OC.MimeType.getIconUrl(mimeType);
    },
    /**
     * Set current active tab
     *
     * @param {string} id tab unique id
     */
    setActiveTab: function setActiveTab(id) {
      OCA.Files.Sidebar.setActiveTab(id);
    },
    /**
     * Toggle favourite state
     * TODO: better implementation
     *
     * @param {boolean} state favourited or not
     */
    toggleStarred: function toggleStarred(state) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.prev = 0;
                _this2.starLoading = true;
                _context.next = 4;
                return (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"])({
                  method: 'PROPPATCH',
                  url: _this2.davPath,
                  data: "<?xml version=\"1.0\"?>\n\t\t\t\t\t\t<d:propertyupdate xmlns:d=\"DAV:\" xmlns:oc=\"http://owncloud.org/ns\">\n\t\t\t\t\t\t".concat(state ? '<d:set>' : '<d:remove>', "\n\t\t\t\t\t\t\t<d:prop>\n\t\t\t\t\t\t\t\t<oc:favorite>1</oc:favorite>\n\t\t\t\t\t\t\t</d:prop>\n\t\t\t\t\t\t").concat(state ? '</d:set>' : '</d:remove>', "\n\t\t\t\t\t\t</d:propertyupdate>")
                });
              case 4:
                // TODO: Obliterate as soon as possible and use events with new files app
                // Terrible fallback for legacy files: toggle filelist as well
                if (OCA.Files && OCA.Files.App && OCA.Files.App.fileList && OCA.Files.App.fileList.fileActions) {
                  OCA.Files.App.fileList.fileActions.triggerAction('Favorite', OCA.Files.App.fileList.getModelForFile(_this2.fileInfo.name), OCA.Files.App.fileList);
                }
                _context.next = 11;
                break;
              case 7:
                _context.prev = 7;
                _context.t0 = _context["catch"](0);
                OC.Notification.showTemporary(t('files', 'Unable to change the favourite state of the file'));
                console.error('Unable to change favourite state', _context.t0);
              case 11:
                _this2.starLoading = false;
              case 12:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[0, 7]]);
      }))();
    },
    onDefaultAction: function onDefaultAction() {
      if (this.defaultAction) {
        // generate fake context
        this.defaultAction.action(this.fileInfo.name, {
          fileInfo: this.fileInfo,
          dir: this.fileInfo.dir,
          fileList: OCA.Files.App.fileList,
          $file: jquery__WEBPACK_IMPORTED_MODULE_1___default()('body')
        });
      }
    },
    /**
     * Toggle the tags selector
     */
    toggleTags: function toggleTags() {
      if (OCA.SystemTags && OCA.SystemTags.View) {
        OCA.SystemTags.View.toggle();
      }
    },
    /**
     * Open the sidebar for the given file
     *
     * @param {string} path the file path to load
     * @return {Promise}
     * @throws {Error} loading failure
     */
    open: function open(path) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                // update current opened file
                _this3.Sidebar.file = path;
                if (!(path && path.trim() !== '')) {
                  _context2.next = 21;
                  break;
                }
                // reset data, keep old fileInfo to not reload all tabs and just hide them
                _this3.error = null;
                _this3.loading = true;
                _context2.prev = 4;
                _context2.next = 7;
                return (0,_services_FileInfo__WEBPACK_IMPORTED_MODULE_9__["default"])(_this3.davPath);
              case 7:
                _this3.fileInfo = _context2.sent;
                // adding this as fallback because other apps expect it
                _this3.fileInfo.dir = _this3.file.split('/').slice(0, -1).join('/');

                // DEPRECATED legacy views
                // TODO: remove
                _this3.views.forEach(function (view) {
                  view.setFileInfo(_this3.fileInfo);
                });
                _this3.$nextTick(function () {
                  if (_this3.$refs.tabs) {
                    _this3.$refs.tabs.updateTabs();
                  }
                });
                _context2.next = 18;
                break;
              case 13:
                _context2.prev = 13;
                _context2.t0 = _context2["catch"](4);
                _this3.error = t('files', 'Error while loading the file data');
                console.error('Error while loading the file data', _context2.t0);
                throw new Error(_context2.t0);
              case 18:
                _context2.prev = 18;
                _this3.loading = false;
                return _context2.finish(18);
              case 21:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[4, 13, 18, 21]]);
      }))();
    },
    /**
     * Close the sidebar
     */
    close: function close() {
      this.Sidebar.file = '';
      this.resetData();
    },
    /**
     * Allow to set the Sidebar as fullscreen from OCA.Files.Sidebar
     *
     * @param {boolean} isFullScreen - Whether or not to render the Sidebar in fullscreen.
     */
    setFullScreenMode: function setFullScreenMode(isFullScreen) {
      this.isFullScreen = isFullScreen;
      if (isFullScreen) {
        var _document$querySelect, _document$querySelect2;
        ((_document$querySelect = document.querySelector('#content')) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.classList.add('with-sidebar--full')) || ((_document$querySelect2 = document.querySelector('#content-vue')) === null || _document$querySelect2 === void 0 ? void 0 : _document$querySelect2.classList.add('with-sidebar--full'));
      } else {
        var _document$querySelect3, _document$querySelect4;
        ((_document$querySelect3 = document.querySelector('#content')) === null || _document$querySelect3 === void 0 ? void 0 : _document$querySelect3.classList.remove('with-sidebar--full')) || ((_document$querySelect4 = document.querySelector('#content-vue')) === null || _document$querySelect4 === void 0 ? void 0 : _document$querySelect4.classList.remove('with-sidebar--full'));
      }
    },
    /**
     * Emit SideBar events.
     */
    handleOpening: function handleOpening() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:opening');
    },
    handleOpened: function handleOpened() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:opened');
    },
    handleClosing: function handleClosing() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:closing');
    },
    handleClosed: function handleClosed() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:closed');
    },
    handleWindowResize: function handleWindowResize() {
      this.hasLowHeight = document.documentElement.clientHeight < 1024;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5& ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div");
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080& ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppSidebarTab", {
    ref: "tab",
    attrs: {
      id: _vm.id,
      name: _vm.name,
      icon: _vm.icon
    },
    on: {
      bottomReached: _vm.onScrollBottomReached
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_vm._t("icon")];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm.loading ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-loading"
    }
  }) : _vm._e(), _vm._v(" "), _c("div", {
    ref: "mount"
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _vm.file ? _c("NcAppSidebar", _vm._b({
    ref: "sidebar",
    attrs: {
      "force-menu": true,
      tabindex: "0"
    },
    on: _vm._d({
      close: _vm.close,
      "update:active": _vm.setActiveTab,
      "update:starred": _vm.toggleStarred,
      opening: _vm.handleOpening,
      opened: _vm.handleOpened,
      closing: _vm.handleClosing,
      closed: _vm.handleClosed
    }, [_vm.defaultActionListener, function ($event) {
      $event.stopPropagation();
      $event.preventDefault();
      return _vm.onDefaultAction.apply(null, arguments);
    }]),
    scopedSlots: _vm._u([_vm.fileInfo ? {
      key: "description",
      fn: function fn() {
        return _vm._l(_vm.views, function (view) {
          return _c("LegacyView", {
            key: view.cid,
            attrs: {
              component: view,
              "file-info": _vm.fileInfo
            }
          });
        });
      },
      proxy: true
    } : null, _vm.fileInfo ? {
      key: "secondary-actions",
      fn: function fn() {
        return [_vm.isSystemTagsEnabled ? _c("NcActionButton", {
          attrs: {
            "close-after-click": true,
            icon: "icon-tag"
          },
          on: {
            click: _vm.toggleTags
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files", "Tags")) + "\n\t\t")]) : _vm._e()];
      },
      proxy: true
    } : null], null, true)
  }, "NcAppSidebar", _vm.appSidebar, false), [_vm._v(" "), _vm._v(" "), _vm.error ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.error) + "\n\t")]) : _vm.fileInfo ? _vm._l(_vm.tabs, function (tab) {
    return [tab.enabled(_vm.fileInfo) ? _c("SidebarTab", {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: !_vm.loading,
        expression: "!loading"
      }],
      key: tab.id,
      attrs: {
        id: tab.id,
        name: tab.name,
        icon: tab.icon,
        "on-mount": tab.mount,
        "on-update": tab.update,
        "on-destroy": tab.destroy,
        "on-scroll-bottom-reached": tab.scrollBottomReached,
        "file-info": _vm.fileInfo
      },
      scopedSlots: _vm._u([tab.iconSvg !== undefined ? {
        key: "icon",
        fn: function fn() {
          return [_c("span", {
            staticClass: "svg-icon",
            domProps: {
              innerHTML: _vm._s(tab.iconSvg)
            }
          })];
        },
        proxy: true
      } : null], null, true)
    }) : _vm._e()];
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".app-sidebar--has-preview[data-v-7c6102ee] .app-sidebar-header__figure {\n  background-size: cover;\n}\n.app-sidebar--has-preview[data-v-7c6102ee][data-mimetype=\"text/plain\"] .app-sidebar-header__figure, .app-sidebar--has-preview[data-v-7c6102ee][data-mimetype=\"text/markdown\"] .app-sidebar-header__figure {\n  background-size: contain;\n}\n.app-sidebar--full[data-v-7c6102ee] {\n  position: fixed !important;\n  z-index: 2025 !important;\n  top: 0 !important;\n  height: 100% !important;\n}\n.app-sidebar .svg-icon[data-v-7c6102ee] svg {\n  width: 20px;\n  height: 20px;\n  fill: currentColor;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$":
/*!***************************************************!*\
  !*** ./node_modules/moment/locale/ sync ^\.\/.*$ ***!
  \***************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./af": "./node_modules/moment/locale/af.js",
	"./af.js": "./node_modules/moment/locale/af.js",
	"./ar": "./node_modules/moment/locale/ar.js",
	"./ar-dz": "./node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "./node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "./node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "./node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "./node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "./node_modules/moment/locale/ar-ma.js",
	"./ar-sa": "./node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "./node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "./node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "./node_modules/moment/locale/ar-tn.js",
	"./ar.js": "./node_modules/moment/locale/ar.js",
	"./az": "./node_modules/moment/locale/az.js",
	"./az.js": "./node_modules/moment/locale/az.js",
	"./be": "./node_modules/moment/locale/be.js",
	"./be.js": "./node_modules/moment/locale/be.js",
	"./bg": "./node_modules/moment/locale/bg.js",
	"./bg.js": "./node_modules/moment/locale/bg.js",
	"./bm": "./node_modules/moment/locale/bm.js",
	"./bm.js": "./node_modules/moment/locale/bm.js",
	"./bn": "./node_modules/moment/locale/bn.js",
	"./bn-bd": "./node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "./node_modules/moment/locale/bn-bd.js",
	"./bn.js": "./node_modules/moment/locale/bn.js",
	"./bo": "./node_modules/moment/locale/bo.js",
	"./bo.js": "./node_modules/moment/locale/bo.js",
	"./br": "./node_modules/moment/locale/br.js",
	"./br.js": "./node_modules/moment/locale/br.js",
	"./bs": "./node_modules/moment/locale/bs.js",
	"./bs.js": "./node_modules/moment/locale/bs.js",
	"./ca": "./node_modules/moment/locale/ca.js",
	"./ca.js": "./node_modules/moment/locale/ca.js",
	"./cs": "./node_modules/moment/locale/cs.js",
	"./cs.js": "./node_modules/moment/locale/cs.js",
	"./cv": "./node_modules/moment/locale/cv.js",
	"./cv.js": "./node_modules/moment/locale/cv.js",
	"./cy": "./node_modules/moment/locale/cy.js",
	"./cy.js": "./node_modules/moment/locale/cy.js",
	"./da": "./node_modules/moment/locale/da.js",
	"./da.js": "./node_modules/moment/locale/da.js",
	"./de": "./node_modules/moment/locale/de.js",
	"./de-at": "./node_modules/moment/locale/de-at.js",
	"./de-at.js": "./node_modules/moment/locale/de-at.js",
	"./de-ch": "./node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "./node_modules/moment/locale/de-ch.js",
	"./de.js": "./node_modules/moment/locale/de.js",
	"./dv": "./node_modules/moment/locale/dv.js",
	"./dv.js": "./node_modules/moment/locale/dv.js",
	"./el": "./node_modules/moment/locale/el.js",
	"./el.js": "./node_modules/moment/locale/el.js",
	"./en-au": "./node_modules/moment/locale/en-au.js",
	"./en-au.js": "./node_modules/moment/locale/en-au.js",
	"./en-ca": "./node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "./node_modules/moment/locale/en-ca.js",
	"./en-gb": "./node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "./node_modules/moment/locale/en-gb.js",
	"./en-ie": "./node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "./node_modules/moment/locale/en-ie.js",
	"./en-il": "./node_modules/moment/locale/en-il.js",
	"./en-il.js": "./node_modules/moment/locale/en-il.js",
	"./en-in": "./node_modules/moment/locale/en-in.js",
	"./en-in.js": "./node_modules/moment/locale/en-in.js",
	"./en-nz": "./node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "./node_modules/moment/locale/en-nz.js",
	"./en-sg": "./node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "./node_modules/moment/locale/en-sg.js",
	"./eo": "./node_modules/moment/locale/eo.js",
	"./eo.js": "./node_modules/moment/locale/eo.js",
	"./es": "./node_modules/moment/locale/es.js",
	"./es-do": "./node_modules/moment/locale/es-do.js",
	"./es-do.js": "./node_modules/moment/locale/es-do.js",
	"./es-mx": "./node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "./node_modules/moment/locale/es-mx.js",
	"./es-us": "./node_modules/moment/locale/es-us.js",
	"./es-us.js": "./node_modules/moment/locale/es-us.js",
	"./es.js": "./node_modules/moment/locale/es.js",
	"./et": "./node_modules/moment/locale/et.js",
	"./et.js": "./node_modules/moment/locale/et.js",
	"./eu": "./node_modules/moment/locale/eu.js",
	"./eu.js": "./node_modules/moment/locale/eu.js",
	"./fa": "./node_modules/moment/locale/fa.js",
	"./fa.js": "./node_modules/moment/locale/fa.js",
	"./fi": "./node_modules/moment/locale/fi.js",
	"./fi.js": "./node_modules/moment/locale/fi.js",
	"./fil": "./node_modules/moment/locale/fil.js",
	"./fil.js": "./node_modules/moment/locale/fil.js",
	"./fo": "./node_modules/moment/locale/fo.js",
	"./fo.js": "./node_modules/moment/locale/fo.js",
	"./fr": "./node_modules/moment/locale/fr.js",
	"./fr-ca": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "./node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "./node_modules/moment/locale/fr-ch.js",
	"./fr.js": "./node_modules/moment/locale/fr.js",
	"./fy": "./node_modules/moment/locale/fy.js",
	"./fy.js": "./node_modules/moment/locale/fy.js",
	"./ga": "./node_modules/moment/locale/ga.js",
	"./ga.js": "./node_modules/moment/locale/ga.js",
	"./gd": "./node_modules/moment/locale/gd.js",
	"./gd.js": "./node_modules/moment/locale/gd.js",
	"./gl": "./node_modules/moment/locale/gl.js",
	"./gl.js": "./node_modules/moment/locale/gl.js",
	"./gom-deva": "./node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "./node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "./node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "./node_modules/moment/locale/gom-latn.js",
	"./gu": "./node_modules/moment/locale/gu.js",
	"./gu.js": "./node_modules/moment/locale/gu.js",
	"./he": "./node_modules/moment/locale/he.js",
	"./he.js": "./node_modules/moment/locale/he.js",
	"./hi": "./node_modules/moment/locale/hi.js",
	"./hi.js": "./node_modules/moment/locale/hi.js",
	"./hr": "./node_modules/moment/locale/hr.js",
	"./hr.js": "./node_modules/moment/locale/hr.js",
	"./hu": "./node_modules/moment/locale/hu.js",
	"./hu.js": "./node_modules/moment/locale/hu.js",
	"./hy-am": "./node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "./node_modules/moment/locale/hy-am.js",
	"./id": "./node_modules/moment/locale/id.js",
	"./id.js": "./node_modules/moment/locale/id.js",
	"./is": "./node_modules/moment/locale/is.js",
	"./is.js": "./node_modules/moment/locale/is.js",
	"./it": "./node_modules/moment/locale/it.js",
	"./it-ch": "./node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "./node_modules/moment/locale/it-ch.js",
	"./it.js": "./node_modules/moment/locale/it.js",
	"./ja": "./node_modules/moment/locale/ja.js",
	"./ja.js": "./node_modules/moment/locale/ja.js",
	"./jv": "./node_modules/moment/locale/jv.js",
	"./jv.js": "./node_modules/moment/locale/jv.js",
	"./ka": "./node_modules/moment/locale/ka.js",
	"./ka.js": "./node_modules/moment/locale/ka.js",
	"./kk": "./node_modules/moment/locale/kk.js",
	"./kk.js": "./node_modules/moment/locale/kk.js",
	"./km": "./node_modules/moment/locale/km.js",
	"./km.js": "./node_modules/moment/locale/km.js",
	"./kn": "./node_modules/moment/locale/kn.js",
	"./kn.js": "./node_modules/moment/locale/kn.js",
	"./ko": "./node_modules/moment/locale/ko.js",
	"./ko.js": "./node_modules/moment/locale/ko.js",
	"./ku": "./node_modules/moment/locale/ku.js",
	"./ku.js": "./node_modules/moment/locale/ku.js",
	"./ky": "./node_modules/moment/locale/ky.js",
	"./ky.js": "./node_modules/moment/locale/ky.js",
	"./lb": "./node_modules/moment/locale/lb.js",
	"./lb.js": "./node_modules/moment/locale/lb.js",
	"./lo": "./node_modules/moment/locale/lo.js",
	"./lo.js": "./node_modules/moment/locale/lo.js",
	"./lt": "./node_modules/moment/locale/lt.js",
	"./lt.js": "./node_modules/moment/locale/lt.js",
	"./lv": "./node_modules/moment/locale/lv.js",
	"./lv.js": "./node_modules/moment/locale/lv.js",
	"./me": "./node_modules/moment/locale/me.js",
	"./me.js": "./node_modules/moment/locale/me.js",
	"./mi": "./node_modules/moment/locale/mi.js",
	"./mi.js": "./node_modules/moment/locale/mi.js",
	"./mk": "./node_modules/moment/locale/mk.js",
	"./mk.js": "./node_modules/moment/locale/mk.js",
	"./ml": "./node_modules/moment/locale/ml.js",
	"./ml.js": "./node_modules/moment/locale/ml.js",
	"./mn": "./node_modules/moment/locale/mn.js",
	"./mn.js": "./node_modules/moment/locale/mn.js",
	"./mr": "./node_modules/moment/locale/mr.js",
	"./mr.js": "./node_modules/moment/locale/mr.js",
	"./ms": "./node_modules/moment/locale/ms.js",
	"./ms-my": "./node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "./node_modules/moment/locale/ms-my.js",
	"./ms.js": "./node_modules/moment/locale/ms.js",
	"./mt": "./node_modules/moment/locale/mt.js",
	"./mt.js": "./node_modules/moment/locale/mt.js",
	"./my": "./node_modules/moment/locale/my.js",
	"./my.js": "./node_modules/moment/locale/my.js",
	"./nb": "./node_modules/moment/locale/nb.js",
	"./nb.js": "./node_modules/moment/locale/nb.js",
	"./ne": "./node_modules/moment/locale/ne.js",
	"./ne.js": "./node_modules/moment/locale/ne.js",
	"./nl": "./node_modules/moment/locale/nl.js",
	"./nl-be": "./node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "./node_modules/moment/locale/nl-be.js",
	"./nl.js": "./node_modules/moment/locale/nl.js",
	"./nn": "./node_modules/moment/locale/nn.js",
	"./nn.js": "./node_modules/moment/locale/nn.js",
	"./oc-lnc": "./node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "./node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "./node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "./node_modules/moment/locale/pa-in.js",
	"./pl": "./node_modules/moment/locale/pl.js",
	"./pl.js": "./node_modules/moment/locale/pl.js",
	"./pt": "./node_modules/moment/locale/pt.js",
	"./pt-br": "./node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "./node_modules/moment/locale/pt-br.js",
	"./pt.js": "./node_modules/moment/locale/pt.js",
	"./ro": "./node_modules/moment/locale/ro.js",
	"./ro.js": "./node_modules/moment/locale/ro.js",
	"./ru": "./node_modules/moment/locale/ru.js",
	"./ru.js": "./node_modules/moment/locale/ru.js",
	"./sd": "./node_modules/moment/locale/sd.js",
	"./sd.js": "./node_modules/moment/locale/sd.js",
	"./se": "./node_modules/moment/locale/se.js",
	"./se.js": "./node_modules/moment/locale/se.js",
	"./si": "./node_modules/moment/locale/si.js",
	"./si.js": "./node_modules/moment/locale/si.js",
	"./sk": "./node_modules/moment/locale/sk.js",
	"./sk.js": "./node_modules/moment/locale/sk.js",
	"./sl": "./node_modules/moment/locale/sl.js",
	"./sl.js": "./node_modules/moment/locale/sl.js",
	"./sq": "./node_modules/moment/locale/sq.js",
	"./sq.js": "./node_modules/moment/locale/sq.js",
	"./sr": "./node_modules/moment/locale/sr.js",
	"./sr-cyrl": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "./node_modules/moment/locale/sr.js",
	"./ss": "./node_modules/moment/locale/ss.js",
	"./ss.js": "./node_modules/moment/locale/ss.js",
	"./sv": "./node_modules/moment/locale/sv.js",
	"./sv.js": "./node_modules/moment/locale/sv.js",
	"./sw": "./node_modules/moment/locale/sw.js",
	"./sw.js": "./node_modules/moment/locale/sw.js",
	"./ta": "./node_modules/moment/locale/ta.js",
	"./ta.js": "./node_modules/moment/locale/ta.js",
	"./te": "./node_modules/moment/locale/te.js",
	"./te.js": "./node_modules/moment/locale/te.js",
	"./tet": "./node_modules/moment/locale/tet.js",
	"./tet.js": "./node_modules/moment/locale/tet.js",
	"./tg": "./node_modules/moment/locale/tg.js",
	"./tg.js": "./node_modules/moment/locale/tg.js",
	"./th": "./node_modules/moment/locale/th.js",
	"./th.js": "./node_modules/moment/locale/th.js",
	"./tk": "./node_modules/moment/locale/tk.js",
	"./tk.js": "./node_modules/moment/locale/tk.js",
	"./tl-ph": "./node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "./node_modules/moment/locale/tl-ph.js",
	"./tlh": "./node_modules/moment/locale/tlh.js",
	"./tlh.js": "./node_modules/moment/locale/tlh.js",
	"./tr": "./node_modules/moment/locale/tr.js",
	"./tr.js": "./node_modules/moment/locale/tr.js",
	"./tzl": "./node_modules/moment/locale/tzl.js",
	"./tzl.js": "./node_modules/moment/locale/tzl.js",
	"./tzm": "./node_modules/moment/locale/tzm.js",
	"./tzm-latn": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "./node_modules/moment/locale/tzm.js",
	"./ug-cn": "./node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "./node_modules/moment/locale/ug-cn.js",
	"./uk": "./node_modules/moment/locale/uk.js",
	"./uk.js": "./node_modules/moment/locale/uk.js",
	"./ur": "./node_modules/moment/locale/ur.js",
	"./ur.js": "./node_modules/moment/locale/ur.js",
	"./uz": "./node_modules/moment/locale/uz.js",
	"./uz-latn": "./node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "./node_modules/moment/locale/uz-latn.js",
	"./uz.js": "./node_modules/moment/locale/uz.js",
	"./vi": "./node_modules/moment/locale/vi.js",
	"./vi.js": "./node_modules/moment/locale/vi.js",
	"./x-pseudo": "./node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "./node_modules/moment/locale/x-pseudo.js",
	"./yo": "./node_modules/moment/locale/yo.js",
	"./yo.js": "./node_modules/moment/locale/yo.js",
	"./zh-cn": "./node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "./node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "./node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "./node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "./node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "./node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "./node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "./node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=template&id=6ac0bcb5& */ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&");
/* harmony import */ var _LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=script&lang=js& */ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.render,
  _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/LegacyView.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=template&id=9d2bd080& */ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&");
/* harmony import */ var _SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=script&lang=js& */ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/SidebarTab.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue":
/*!******************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& */ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&");
/* harmony import */ var _Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=script&lang=js& */ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&");
/* harmony import */ var _Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7c6102ee",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/views/Sidebar.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&":
/*!*********************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=template&id=6ac0bcb5& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&");


/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&":
/*!*********************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=template&id=9d2bd080& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");


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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/files/src/sidebar.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-sidebar.js.map?v=775fed9ec6aa573b65cf