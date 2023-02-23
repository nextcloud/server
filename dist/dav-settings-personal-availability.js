/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/dav/src/dav/client.js":
/*!************************************!*\
  !*** ./apps/dav/src/dav/client.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getClient": function() { return /* binding */ getClient; }
/* harmony export */ });
/* harmony import */ var webdav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav */ "./node_modules/webdav/dist/node/index.js");
/* harmony import */ var webdav__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(webdav__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var lodash_fp_memoize__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash/fp/memoize */ "./node_modules/lodash/fp/memoize.js");
/* harmony import */ var lodash_fp_memoize__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash_fp_memoize__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */






var getClient = lodash_fp_memoize__WEBPACK_IMPORTED_MODULE_2___default()(function (service) {
  // Add this so the server knows it is an request from the browser
  _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].defaults.headers["X-Requested-With"] = 'XMLHttpRequest';

  // force our axios
  var patcher = webdav__WEBPACK_IMPORTED_MODULE_0__.getPatcher();
  patcher.patch('request', _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"]);
  return webdav__WEBPACK_IMPORTED_MODULE_0__.createClient((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateRemoteUrl)("dav/".concat(service, "/").concat((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__.getCurrentUser)().uid)));
});

/***/ }),

/***/ "./apps/dav/src/service/CalendarService.js":
/*!*************************************************!*\
  !*** ./apps/dav/src/service/CalendarService.js ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "findScheduleInboxAvailability": function() { return /* binding */ findScheduleInboxAvailability; },
/* harmony export */   "getEmptySlots": function() { return /* binding */ getEmptySlots; },
/* harmony export */   "saveScheduleInboxAvailability": function() { return /* binding */ saveScheduleInboxAvailability; }
/* harmony export */ });
/* harmony import */ var _dav_client__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dav/client */ "./apps/dav/src/dav/client.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./logger */ "./apps/dav/src/service/logger.js");
/* harmony import */ var webdav_dist_node_tools_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! webdav/dist/node/tools/dav */ "./node_modules/webdav/dist/node/tools/dav.js");
/* harmony import */ var webdav_dist_node_tools_dav__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(webdav_dist_node_tools_dav__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_calendar_availability_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/calendar-availability-vue */ "./node_modules/@nextcloud/calendar-availability-vue/lib/index.esm.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */





/**
 *
 */
function getEmptySlots() {
  return {
    MO: [],
    TU: [],
    WE: [],
    TH: [],
    FR: [],
    SA: [],
    SU: []
  };
}

/**
 *
 */
function findScheduleInboxAvailability() {
  return _findScheduleInboxAvailability.apply(this, arguments);
}

/**
 * @param {any} slots -
 * @param {any} timezoneId -
 */
function _findScheduleInboxAvailability() {
  _findScheduleInboxAvailability = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
    var _xml$multistatus, _xml$multistatus$resp, _xml$multistatus$resp2;
    var client, response, xml, availability;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            client = (0,_dav_client__WEBPACK_IMPORTED_MODULE_0__.getClient)('calendars');
            _context.next = 3;
            return client.customRequest('inbox', {
              method: 'PROPFIND',
              data: "<?xml version=\"1.0\"?>\n\t\t\t<x0:propfind xmlns:x0=\"DAV:\">\n\t\t\t  <x0:prop>\n\t\t\t\t<x1:calendar-availability xmlns:x1=\"urn:ietf:params:xml:ns:caldav\"/>\n\t\t\t  </x0:prop>\n\t\t\t</x0:propfind>"
            });
          case 3:
            response = _context.sent;
            _context.next = 6;
            return (0,webdav_dist_node_tools_dav__WEBPACK_IMPORTED_MODULE_2__.parseXML)(response.data);
          case 6:
            xml = _context.sent;
            if (xml) {
              _context.next = 9;
              break;
            }
            return _context.abrupt("return", undefined);
          case 9:
            availability = xml === null || xml === void 0 ? void 0 : (_xml$multistatus = xml.multistatus) === null || _xml$multistatus === void 0 ? void 0 : (_xml$multistatus$resp = _xml$multistatus.response[0]) === null || _xml$multistatus$resp === void 0 ? void 0 : (_xml$multistatus$resp2 = _xml$multistatus$resp.propstat) === null || _xml$multistatus$resp2 === void 0 ? void 0 : _xml$multistatus$resp2.prop['calendar-availability'];
            if (availability) {
              _context.next = 12;
              break;
            }
            return _context.abrupt("return", undefined);
          case 12:
            return _context.abrupt("return", (0,_nextcloud_calendar_availability_vue__WEBPACK_IMPORTED_MODULE_3__.vavailabilityToSlots)(availability));
          case 13:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _findScheduleInboxAvailability.apply(this, arguments);
}
function saveScheduleInboxAvailability(_x, _x2) {
  return _saveScheduleInboxAvailability.apply(this, arguments);
}
function _saveScheduleInboxAvailability() {
  _saveScheduleInboxAvailability = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(slots, timezoneId) {
    var all, vavailability, client;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            all = _toConsumableArray(Object.keys(slots).flatMap(function (dayId) {
              return slots[dayId].map(function (slot) {
                return _objectSpread(_objectSpread({}, slot), {}, {
                  day: dayId
                });
              });
            }));
            vavailability = (0,_nextcloud_calendar_availability_vue__WEBPACK_IMPORTED_MODULE_3__.slotsToVavailability)(all, timezoneId);
            _logger__WEBPACK_IMPORTED_MODULE_1__["default"].debug('New availability ical created', {
              vavailability: vavailability
            });
            client = (0,_dav_client__WEBPACK_IMPORTED_MODULE_0__.getClient)('calendars');
            _context2.next = 6;
            return client.customRequest('inbox', {
              method: 'PROPPATCH',
              data: "<?xml version=\"1.0\"?>\n\t\t\t<x0:propertyupdate xmlns:x0=\"DAV:\">\n\t\t\t  <x0:set>\n\t\t\t\t<x0:prop>\n\t\t\t\t  <x1:calendar-availability xmlns:x1=\"urn:ietf:params:xml:ns:caldav\">".concat(vavailability, "</x1:calendar-availability>\n\t\t\t\t</x0:prop>\n\t\t\t  </x0:set>\n\t\t\t</x0:propertyupdate>")
            });
          case 6:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return _saveScheduleInboxAvailability.apply(this, arguments);
}

/***/ }),

/***/ "./apps/dav/src/service/PreferenceService.js":
/*!***************************************************!*\
  !*** ./apps/dav/src/service/PreferenceService.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "disableUserStatusAutomation": function() { return /* binding */ disableUserStatusAutomation; },
/* harmony export */   "enableUserStatusAutomation": function() { return /* binding */ enableUserStatusAutomation; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */




/**
 * Enable user status automation based on availability
 */
function enableUserStatusAutomation() {
  return _enableUserStatusAutomation.apply(this, arguments);
}

/**
 * Disable user status automation based on availability
 */
function _enableUserStatusAutomation() {
  _enableUserStatusAutomation = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
              appId: 'dav',
              configKey: 'user_status_automation'
            }), {
              configValue: 'yes'
            });
          case 2:
            return _context.abrupt("return", _context.sent);
          case 3:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _enableUserStatusAutomation.apply(this, arguments);
}
function disableUserStatusAutomation() {
  return _disableUserStatusAutomation.apply(this, arguments);
}
function _disableUserStatusAutomation() {
  _disableUserStatusAutomation = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            _context2.next = 2;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"]["delete"]((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
              appId: 'dav',
              configKey: 'user_status_automation'
            }));
          case 2:
            return _context2.abrupt("return", _context2.sent);
          case 3:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return _disableUserStatusAutomation.apply(this, arguments);
}

/***/ }),

/***/ "./apps/dav/src/service/logger.js":
/*!****************************************!*\
  !*** ./apps/dav/src/service/logger.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('dav').detectUser().build();
/* harmony default export */ __webpack_exports__["default"] = (logger);

/***/ }),

/***/ "./apps/dav/src/settings-personal-availability.js":
/*!********************************************************!*\
  !*** ./apps/dav/src/settings-personal-availability.js ***!
  \********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_Availability__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Availability */ "./apps/dav/src/views/Availability.vue");



vue__WEBPACK_IMPORTED_MODULE_2__["default"].prototype.$t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate;
var View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_views_Availability__WEBPACK_IMPORTED_MODULE_1__["default"]);
new View({}).$mount('#settings-personal-availability');

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_calendar_availability_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/calendar-availability-vue */ "./node_modules/@nextcloud/calendar-availability-vue/lib/index.esm.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _service_CalendarService__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../service/CalendarService */ "./apps/dav/src/service/CalendarService.js");
/* harmony import */ var _service_PreferenceService__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../service/PreferenceService */ "./apps/dav/src/service/PreferenceService.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSettingsSection */ "./node_modules/@nextcloud/vue/dist/Components/NcSettingsSection.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcTimezonePicker__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTimezonePicker */ "./node_modules/@nextcloud/vue/dist/Components/NcTimezonePicker.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTimezonePicker__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcTimezonePicker__WEBPACK_IMPORTED_MODULE_8__);
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }









/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Availability',
  components: {
    NcButton: (_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_5___default()),
    NcCheckboxRadioSwitch: (_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_6___default()),
    CalendarAvailability: _nextcloud_calendar_availability_vue__WEBPACK_IMPORTED_MODULE_0__.CalendarAvailability,
    NcSettingsSection: (_nextcloud_vue_dist_Components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_7___default()),
    NcTimezonePicker: (_nextcloud_vue_dist_Components_NcTimezonePicker__WEBPACK_IMPORTED_MODULE_8___default())
  },
  data: function data() {
    var _Intl$DateTimeFormat$, _Intl$DateTimeFormat, _Intl$DateTimeFormat$2;
    // Try to determine the current timezone, and fall back to UTC otherwise
    var defaultTimezoneId = (_Intl$DateTimeFormat$ = (_Intl$DateTimeFormat = new Intl.DateTimeFormat()) === null || _Intl$DateTimeFormat === void 0 ? void 0 : (_Intl$DateTimeFormat$2 = _Intl$DateTimeFormat.resolvedOptions()) === null || _Intl$DateTimeFormat$2 === void 0 ? void 0 : _Intl$DateTimeFormat$2.timeZone) !== null && _Intl$DateTimeFormat$ !== void 0 ? _Intl$DateTimeFormat$ : 'UTC';
    return {
      loading: true,
      saving: false,
      timezone: defaultTimezoneId,
      slots: (0,_service_CalendarService__WEBPACK_IMPORTED_MODULE_3__.getEmptySlots)(),
      automated: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('dav', 'user_status_automation') === 'yes'
    };
  },
  mounted: function mounted() {
    var _this = this;
    return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      var slotData, slots, timezoneId;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.prev = 0;
              _context.next = 3;
              return (0,_service_CalendarService__WEBPACK_IMPORTED_MODULE_3__.findScheduleInboxAvailability)();
            case 3:
              slotData = _context.sent;
              if (!slotData) {
                console.info('no availability is set');
                _this.slots = (0,_service_CalendarService__WEBPACK_IMPORTED_MODULE_3__.getEmptySlots)();
              } else {
                slots = slotData.slots, timezoneId = slotData.timezoneId;
                _this.slots = slots;
                if (timezoneId) {
                  _this.timezone = timezoneId;
                }
                console.info('availability loaded', _this.slots, _this.timezoneId);
              }
              _context.next = 11;
              break;
            case 7:
              _context.prev = 7;
              _context.t0 = _context["catch"](0);
              console.error('could not load existing availability', _context.t0);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('dav', 'Failed to load availability'));
            case 11:
              _context.prev = 11;
              _this.loading = false;
              return _context.finish(11);
            case 14:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[0, 7, 11, 14]]);
    }))();
  },
  methods: {
    save: function save() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _this2.saving = true;
                _context2.next = 4;
                return (0,_service_CalendarService__WEBPACK_IMPORTED_MODULE_3__.saveScheduleInboxAvailability)(_this2.slots, _this2.timezone);
              case 4:
                if (!_this2.automated) {
                  _context2.next = 9;
                  break;
                }
                _context2.next = 7;
                return (0,_service_PreferenceService__WEBPACK_IMPORTED_MODULE_4__.enableUserStatusAutomation)();
              case 7:
                _context2.next = 11;
                break;
              case 9:
                _context2.next = 11;
                return (0,_service_PreferenceService__WEBPACK_IMPORTED_MODULE_4__.disableUserStatusAutomation)();
              case 11:
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('dav', 'Saved availability'));
                _context2.next = 18;
                break;
              case 14:
                _context2.prev = 14;
                _context2.t0 = _context2["catch"](0);
                console.error('could not save availability', _context2.t0);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('dav', 'Failed to save availability'));
              case 18:
                _context2.prev = 18;
                _this2.saving = false;
                return _context2.finish(18);
              case 21:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 14, 18, 21]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcSettingsSection", {
    attrs: {
      title: _vm.$t("dav", "Availability"),
      description: _vm.$t("dav", "If you configure your working hours, other users will see when you are out of office when they book a meeting.")
    }
  }, [_c("div", {
    staticClass: "time-zone"
  }, [_c("strong", [_vm._v("\n\t\t\t" + _vm._s(_vm.$t("dav", "Time zone:")) + "\n\t\t")]), _vm._v(" "), _c("span", {
    staticClass: "time-zone-text"
  }, [_c("NcTimezonePicker", {
    model: {
      value: _vm.timezone,
      callback: function callback($$v) {
        _vm.timezone = $$v;
      },
      expression: "timezone"
    }
  })], 1)]), _vm._v(" "), _c("CalendarAvailability", {
    attrs: {
      slots: _vm.slots,
      loading: _vm.loading,
      "l10n-to": _vm.$t("dav", "to"),
      "l10n-delete-slot": _vm.$t("dav", "Delete slot"),
      "l10n-empty-day": _vm.$t("dav", "No working hours set"),
      "l10n-add-slot": _vm.$t("dav", "Add slot"),
      "l10n-monday": _vm.$t("dav", "Monday"),
      "l10n-tuesday": _vm.$t("dav", "Tuesday"),
      "l10n-wednesday": _vm.$t("dav", "Wednesday"),
      "l10n-thursday": _vm.$t("dav", "Thursday"),
      "l10n-friday": _vm.$t("dav", "Friday"),
      "l10n-saturday": _vm.$t("dav", "Saturday"),
      "l10n-sunday": _vm.$t("dav", "Sunday")
    },
    on: {
      "update:slots": function updateSlots($event) {
        _vm.slots = $event;
      }
    }
  }), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.automated
    },
    on: {
      "update:checked": function updateChecked($event) {
        _vm.automated = $event;
      }
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.$t("dav", 'Automatically set user status to "Do not disturb" outside of availability to mute all notifications.')) + "\n\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      disabled: _vm.loading || _vm.saving,
      type: "primary"
    },
    on: {
      click: _vm.save
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.$t("dav", "Save")) + "\n\t")])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".availability-day[data-v-aad5ff48] {\n  padding: 0 10px 0 10px;\n  position: absolute;\n}\n.availability-slots[data-v-aad5ff48] {\n  display: flex;\n  white-space: nowrap;\n}\n.availability-slot[data-v-aad5ff48] {\n  display: flex;\n  flex-direction: row;\n  align-items: center;\n}\n.availability-slot-group[data-v-aad5ff48] {\n  display: flex;\n  flex-direction: column;\n}\n[data-v-aad5ff48] .mx-input-wrapper {\n  width: 85px;\n}\n[data-v-aad5ff48] .mx-datepicker {\n  width: 97px;\n}\n[data-v-aad5ff48] .multiselect {\n  border: 1px solid var(--color-border-dark);\n  width: 120px;\n}\n.time-zone[data-v-aad5ff48] {\n  padding: 32px 12px 12px 0;\n}\n.grid-table[data-v-aad5ff48] {\n  display: grid;\n  margin-bottom: 32px;\n  grid-column-gap: 24px;\n  grid-row-gap: 6px;\n  grid-template-columns: min-content min-content min-content;\n}\n.button[data-v-aad5ff48] {\n  align-self: flex-end;\n}\n.label-weekday[data-v-aad5ff48] {\n  position: relative;\n  display: inline-flex;\n  padding-top: 4px;\n}\n.delete-slot[data-v-aad5ff48] {\n  background-color: transparent;\n  border: none;\n  padding-bottom: 12px;\n  opacity: 0.5;\n}\n.delete-slot[data-v-aad5ff48]:hover {\n  opacity: 1;\n}\n.add-another[data-v-aad5ff48] {\n  background-color: transparent;\n  border: none;\n  opacity: 0.5;\n  display: inline-flex;\n  padding: 0;\n  margin: 0;\n  margin-bottom: 3px;\n}\n.add-another[data-v-aad5ff48]:hover {\n  opacity: 1;\n}\n.to-text[data-v-aad5ff48] {\n  padding-right: 12px;\n}\n.time-zone-text[data-v-aad5ff48] {\n  padding-left: 22px;\n}\n.empty-content[data-v-aad5ff48] {\n  color: var(--color-text-lighter);\n  margin-top: 4px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/dav/src/views/Availability.vue":
/*!*********************************************!*\
  !*** ./apps/dav/src/views/Availability.vue ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Availability.vue?vue&type=template&id=aad5ff48&scoped=true& */ "./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true&");
/* harmony import */ var _Availability_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Availability.vue?vue&type=script&lang=js& */ "./apps/dav/src/views/Availability.vue?vue&type=script&lang=js&");
/* harmony import */ var _Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& */ "./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Availability_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "aad5ff48",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/dav/src/views/Availability.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/dav/src/views/Availability.vue?vue&type=script&lang=js&":
/*!**********************************************************************!*\
  !*** ./apps/dav/src/views/Availability.vue?vue&type=script&lang=js& ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Availability.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true&":
/*!****************************************************************************************!*\
  !*** ./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true& ***!
  \****************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_template_id_aad5ff48_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Availability.vue?vue&type=template&id=aad5ff48&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=template&id=aad5ff48&scoped=true&");


/***/ }),

/***/ "./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Availability_vue_vue_type_style_index_0_id_aad5ff48_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/dav/src/views/Availability.vue?vue&type=style&index=0&id=aad5ff48&lang=scss&scoped=true&");


/***/ }),

/***/ "?3e83":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (function() {

/* (ignored) */

/***/ }),

/***/ "?19e6":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (function() {

/* (ignored) */

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
/******/ 			"dav-settings-personal-availability": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/dav/src/settings-personal-availability.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=dav-settings-personal-availability.js.map?v=e2d337bd5087db58a04e