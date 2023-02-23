/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/weather_status/src/services/weatherStatusService.js":
/*!******************************************************************!*\
  !*** ./apps/weather_status/src/services/weatherStatusService.js ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "fetchForecast": function() { return /* binding */ fetchForecast; },
/* harmony export */   "getFavorites": function() { return /* binding */ getFavorites; },
/* harmony export */   "getLocation": function() { return /* binding */ getLocation; },
/* harmony export */   "saveFavorites": function() { return /* binding */ saveFavorites; },
/* harmony export */   "setAddress": function() { return /* binding */ setAddress; },
/* harmony export */   "setLocation": function() { return /* binding */ setLocation; },
/* harmony export */   "setMode": function() { return /* binding */ setMode; },
/* harmony export */   "usePersonalAddress": function() { return /* binding */ usePersonalAddress; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
 *
 *
 * @param {string} lat the latitude
 * @param {string} lon the longitude
 * @return {Promise<object>}
 */
var setLocation = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(lat, lon) {
    var url, response;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/location');
            _context.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              address: '',
              lat: lat,
              lon: lon
            });
          case 3:
            response = _context.sent;
            return _context.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function setLocation(_x, _x2) {
    return _ref.apply(this, arguments);
  };
}();

/**
 *
 * @param {string} address The location
 * @return {Promise<object>}
 */
var setAddress = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(address) {
    var url, response;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/location');
            _context2.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              address: address,
              lat: null,
              lon: null
            });
          case 3:
            response = _context2.sent;
            return _context2.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return function setAddress(_x3) {
    return _ref2.apply(this, arguments);
  };
}();

/**
 *
 * @param {string} mode can be 1 browser or 2 custom
 * @return {Promise<object>}
 */
var setMode = /*#__PURE__*/function () {
  var _ref3 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(mode) {
    var url, response;
    return regeneratorRuntime.wrap(function _callee3$(_context3) {
      while (1) {
        switch (_context3.prev = _context3.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/mode');
            _context3.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              mode: mode
            });
          case 3:
            response = _context3.sent;
            return _context3.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context3.stop();
        }
      }
    }, _callee3);
  }));
  return function setMode(_x4) {
    return _ref3.apply(this, arguments);
  };
}();

/**
 *
 * @return {Promise<object>}
 */
var usePersonalAddress = /*#__PURE__*/function () {
  var _ref4 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
    var url, response;
    return regeneratorRuntime.wrap(function _callee4$(_context4) {
      while (1) {
        switch (_context4.prev = _context4.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/use-personal');
            _context4.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url);
          case 3:
            response = _context4.sent;
            return _context4.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context4.stop();
        }
      }
    }, _callee4);
  }));
  return function usePersonalAddress() {
    return _ref4.apply(this, arguments);
  };
}();

/**
 * Fetches the location information for current user
 *
 * @return {Promise<object>}
 */
var getLocation = /*#__PURE__*/function () {
  var _ref5 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5() {
    var url, response;
    return regeneratorRuntime.wrap(function _callee5$(_context5) {
      while (1) {
        switch (_context5.prev = _context5.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/location');
            _context5.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
          case 3:
            response = _context5.sent;
            return _context5.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context5.stop();
        }
      }
    }, _callee5);
  }));
  return function getLocation() {
    return _ref5.apply(this, arguments);
  };
}();

/**
 * Fetches the weather forecast
 *
 * @return {Promise<object>}
 */
var fetchForecast = /*#__PURE__*/function () {
  var _ref6 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6() {
    var url, response;
    return regeneratorRuntime.wrap(function _callee6$(_context6) {
      while (1) {
        switch (_context6.prev = _context6.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/forecast');
            _context6.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
          case 3:
            response = _context6.sent;
            return _context6.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context6.stop();
        }
      }
    }, _callee6);
  }));
  return function fetchForecast() {
    return _ref6.apply(this, arguments);
  };
}();

/**
 * Fetches the location favorites
 *
 * @return {Promise<object>}
 */
var getFavorites = /*#__PURE__*/function () {
  var _ref7 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee7() {
    var url, response;
    return regeneratorRuntime.wrap(function _callee7$(_context7) {
      while (1) {
        switch (_context7.prev = _context7.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/favorites');
            _context7.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
          case 3:
            response = _context7.sent;
            return _context7.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context7.stop();
        }
      }
    }, _callee7);
  }));
  return function getFavorites() {
    return _ref7.apply(this, arguments);
  };
}();

/**
 *
 * @param {Array} favorites List of favorite addresses
 * @return {Promise<object>}
 */
var saveFavorites = /*#__PURE__*/function () {
  var _ref8 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee8(favorites) {
    var url, response;
    return regeneratorRuntime.wrap(function _callee8$(_context8) {
      while (1) {
        switch (_context8.prev = _context8.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/weather_status/api/v1/favorites');
            _context8.next = 3;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              favorites: favorites
            });
          case 3:
            response = _context8.sent;
            return _context8.abrupt("return", response.data.ocs.data);
          case 5:
          case "end":
            return _context8.stop();
        }
      }
    }, _callee8);
  }));
  return function saveFavorites(_x5) {
    return _ref8.apply(this, arguments);
  };
}();


/***/ }),

/***/ "./apps/weather_status/src/weather-status.js":
/*!***************************************************!*\
  !*** ./apps/weather_status/src/weather-status.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _App__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App */ "./apps/weather_status/src/App.vue");
/**
 * @copyright Copyright (c) 2016 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
__webpack_require__.nc = btoa((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getRequestToken)());
vue__WEBPACK_IMPORTED_MODULE_2__["default"].prototype.t = t;
document.addEventListener('DOMContentLoaded', function () {
  if (!OCA.Dashboard) {
    return;
  }
  OCA.Dashboard.registerStatus('weather', function (el) {
    var Dashboard = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_App__WEBPACK_IMPORTED_MODULE_1__["default"]);
    return new Dashboard({
      propsData: {
        inline: true
      }
    }).$mount(el);
  });
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionInput__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionSeparator */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionText__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./services/weatherStatusService */ "./apps/weather_status/src/services/weatherStatusService.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }










var MODE_BROWSER_LOCATION = 1;
var MODE_MANUAL_LOCATION = 2;
var weatherOptions = {
  clearsky_day: {
    icon: 'icon-clearsky-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} clear sky later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} clear sky', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  clearsky_night: {
    icon: 'icon-clearsky-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} clear sky later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} clear sky', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  cloudy: {
    icon: 'icon-cloudy',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} cloudy later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} cloudy', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  fair_day: {
    icon: 'icon-fair-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} fair weather later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} fair weather', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  fair_night: {
    icon: 'icon-fair-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} fair weather later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} fair weather', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  partlycloudy_day: {
    icon: 'icon-partlycloudy-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} partly cloudy later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} partly cloudy', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  partlycloudy_night: {
    icon: 'icon-partlycloudy-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} partly cloudy later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} partly cloudy', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  fog: {
    icon: 'icon-fog',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} foggy later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} foggy', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  lightrain: {
    icon: 'icon-lightrain',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} light rainfall later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} light rainfall', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  rain: {
    icon: 'icon-rain',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} rainfall later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} rainfall', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  heavyrain: {
    icon: 'icon-heavyrain',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} heavy rainfall later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} heavy rainfall', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  rainshowers_day: {
    icon: 'icon-rainshowers-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  rainshowers_night: {
    icon: 'icon-rainshowers-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  lightrainshowers_day: {
    icon: 'icon-light-rainshowers-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} light rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} light rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  lightrainshowers_night: {
    icon: 'icon-light-rainshowers-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} light rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} light rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  heavyrainshowers_day: {
    icon: 'icon-heavy-rainshowers-day',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} heavy rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} heavy rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  },
  heavyrainshowers_night: {
    icon: 'icon-heavy-rainshowers-night',
    text: function text(temperature, unit) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return later ? t('weather_status', '{temperature} {unit} heavy rainfall showers later today', {
        temperature: temperature,
        unit: unit
      }) : t('weather_status', '{temperature} {unit} heavy rainfall showers', {
        temperature: temperature,
        unit: unit
      });
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'App',
  components: {
    NcActions: (_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_3___default()),
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_4___default()),
    NcActionInput: (_nextcloud_vue_dist_Components_NcActionInput__WEBPACK_IMPORTED_MODULE_5___default()),
    NcActionLink: (_nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_6___default()),
    NcActionSeparator: (_nextcloud_vue_dist_Components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_7___default()),
    NcActionText: (_nextcloud_vue_dist_Components_NcActionText__WEBPACK_IMPORTED_MODULE_8___default())
  },
  props: {
    inline: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      locale: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getLocale)(),
      loading: true,
      errorMessage: '',
      mode: MODE_BROWSER_LOCATION,
      address: null,
      lat: null,
      lon: null,
      // how many hours ahead do we want to see the forecast?
      offset: 5,
      forecasts: [],
      loop: null,
      favorites: [],
      showFavorites: false
    };
  },
  computed: {
    useFahrenheitLocale: function useFahrenheitLocale() {
      return ['en_US', 'en_MH', 'en_FM', 'en_PW', 'en_KY', 'en_LR'].includes(this.locale);
    },
    temperatureUnit: function temperatureUnit() {
      return this.useFahrenheitLocale ? '°F' : '°C';
    },
    locationText: function locationText() {
      return t('weather_status', 'More weather for {adr}', {
        adr: this.address
      });
    },
    temperature: function temperature() {
      return this.getTemperature(this.forecasts, 0);
    },
    futureTemperature: function futureTemperature() {
      return this.getTemperature(this.forecasts, this.offset);
    },
    weatherCode: function weatherCode() {
      return this.getWeatherCode(this.forecasts, 0);
    },
    futureWeatherCode: function futureWeatherCode() {
      return this.getWeatherCode(this.forecasts, this.offset);
    },
    weatherIcon: function weatherIcon() {
      return this.getWeatherIcon(this.weatherCode, this.loading);
    },
    futureWeatherIcon: function futureWeatherIcon() {
      return this.getWeatherIcon(this.futureWeatherCode, this.loading);
    },
    /**
     * The message displayed in the top right corner
     *
     * @return {string}
     */
    currentWeatherMessage: function currentWeatherMessage() {
      if (this.loading) {
        return t('weather_status', 'Loading weather');
      } else if (this.errorMessage) {
        return this.errorMessage;
      } else {
        return this.getWeatherMessage(this.weatherCode, this.temperature);
      }
    },
    forecastMessage: function forecastMessage() {
      if (this.loading) {
        return t('weather_status', 'Loading weather');
      } else {
        return this.getWeatherMessage(this.futureWeatherCode, this.futureTemperature, true);
      }
    },
    weatherLinkTarget: function weatherLinkTarget() {
      return 'https://www.windy.com/-Rain-thunder-rain?rain,' + this.lat + ',' + this.lon + ',11';
    },
    gotWeather: function gotWeather() {
      return this.address && !this.errorMessage;
    },
    addRemoveFavoriteIcon: function addRemoveFavoriteIcon() {
      return this.currentAddressIsFavorite ? 'icon-starred' : 'icon-star';
    },
    addRemoveFavoriteText: function addRemoveFavoriteText() {
      return this.currentAddressIsFavorite ? t('weather_status', 'Remove from favorites') : t('weather_status', 'Add as favorite');
    },
    currentAddressIsFavorite: function currentAddressIsFavorite() {
      var _this = this;
      return this.favorites.find(function (f) {
        return f === _this.address;
      });
    },
    toggleFavoritesIcon: function toggleFavoritesIcon() {
      return this.showFavorites ? 'icon-triangle-s' : 'icon-triangle-e';
    },
    displayedFavorites: function displayedFavorites() {
      return this.showFavorites ? this.favorites : [];
    }
  },
  mounted: function mounted() {
    this.initWeatherStatus();
  },
  methods: {
    initWeatherStatus: function initWeatherStatus() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var loc, favs;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.prev = 0;
                _context.next = 3;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.getLocation();
              case 3:
                loc = _context.sent;
                _this2.lat = loc.lat;
                _this2.lon = loc.lon;
                _this2.address = loc.address;
                _this2.mode = loc.mode;
                if (_this2.mode === MODE_BROWSER_LOCATION) {
                  _this2.askBrowserLocation();
                } else if (_this2.mode === MODE_MANUAL_LOCATION) {
                  _this2.startLoop();
                }
                _context.next = 11;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.getFavorites();
              case 11:
                favs = _context.sent;
                _this2.favorites = favs;
                _context.next = 22;
                break;
              case 15:
                _context.prev = 15;
                _context.t0 = _context["catch"](0);
                if (!((_context.t0 === null || _context.t0 === void 0 ? void 0 : _context.t0.code) === 'ECONNABORTED')) {
                  _context.next = 20;
                  break;
                }
                console.info('The weather status request was cancelled because the user navigates.');
                return _context.abrupt("return");
              case 20:
                if (_context.t0.response && _context.t0.response.status === 401) {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'You are not logged in.'));
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'There was an error getting the weather status information.'));
                }
                console.error(_context.t0);
              case 22:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[0, 15]]);
      }))();
    },
    startLoop: function startLoop() {
      var _this3 = this;
      clearInterval(this.loop);
      if (this.lat && this.lon) {
        this.loop = setInterval(function () {
          return _this3.getForecast();
        }, 60 * 1000 * 60);
        this.getForecast();
      } else {
        this.loading = false;
      }
    },
    askBrowserLocation: function askBrowserLocation() {
      var _this4 = this;
      this.loading = true;
      this.errorMessage = '';
      if (navigator.geolocation && window.isSecureContext) {
        navigator.geolocation.getCurrentPosition(function (position) {
          console.debug('browser location success');
          _this4.lat = position.coords.latitude;
          _this4.lon = position.coords.longitude;
          _this4.saveMode(MODE_BROWSER_LOCATION);
          _this4.mode = MODE_BROWSER_LOCATION;
          _this4.saveLocation(_this4.lat, _this4.lon);
        }, function (error) {
          console.debug('location permission refused');
          console.debug(error);
          _this4.saveMode(MODE_MANUAL_LOCATION);
          _this4.mode = MODE_MANUAL_LOCATION;
          // fallback on what we have if possible
          if (_this4.lat && _this4.lon) {
            _this4.startLoop();
          } else {
            _this4.usePersonalAddress();
          }
        });
      } else {
        console.debug('no secure context!');
        this.saveMode(MODE_MANUAL_LOCATION);
        this.mode = MODE_MANUAL_LOCATION;
        this.startLoop();
      }
    },
    getForecast: function getForecast() {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.fetchForecast();
              case 3:
                _this5.forecasts = _context2.sent;
                _context2.next = 10;
                break;
              case 6:
                _context2.prev = 6;
                _context2.t0 = _context2["catch"](0);
                _this5.errorMessage = t('weather_status', 'No weather information found');
                console.debug(_context2.t0);
              case 10:
                _this5.loading = false;
              case 11:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 6]]);
      }))();
    },
    setAddress: function setAddress(address) {
      var _this6 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var loc;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _this6.loading = true;
                _this6.errorMessage = '';
                _context3.prev = 2;
                _context3.next = 5;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.setAddress(address);
              case 5:
                loc = _context3.sent;
                if (loc.success) {
                  _this6.lat = loc.lat;
                  _this6.lon = loc.lon;
                  _this6.address = loc.address;
                  _this6.mode = MODE_MANUAL_LOCATION;
                  _this6.startLoop();
                } else {
                  _this6.errorMessage = t('weather_status', 'Location not found');
                  _this6.loading = false;
                }
                _context3.next = 13;
                break;
              case 9:
                _context3.prev = 9;
                _context3.t0 = _context3["catch"](2);
                if (_context3.t0.response && _context3.t0.response.status === 401) {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'You are not logged in.'));
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'There was an error setting the location address.'));
                }
                _this6.loading = false;
              case 13:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[2, 9]]);
      }))();
    },
    saveLocation: function saveLocation(lat, lon) {
      var _this7 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
        var loc;
        return regeneratorRuntime.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                _context4.prev = 0;
                _context4.next = 3;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.setLocation(lat, lon);
              case 3:
                loc = _context4.sent;
                _this7.address = loc.address;
                _this7.startLoop();
                _context4.next = 12;
                break;
              case 8:
                _context4.prev = 8;
                _context4.t0 = _context4["catch"](0);
                if (_context4.t0.response && _context4.t0.response.status === 401) {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'You are not logged in.'));
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'There was an error setting the location.'));
                }
                console.debug(_context4.t0);
              case 12:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4, null, [[0, 8]]);
      }))();
    },
    saveMode: function saveMode(mode) {
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5() {
        return regeneratorRuntime.wrap(function _callee5$(_context5) {
          while (1) {
            switch (_context5.prev = _context5.next) {
              case 0:
                _context5.prev = 0;
                _context5.next = 3;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.setMode(mode);
              case 3:
                _context5.next = 9;
                break;
              case 5:
                _context5.prev = 5;
                _context5.t0 = _context5["catch"](0);
                if (_context5.t0.response && _context5.t0.response.status === 401) {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'You are not logged in.'));
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'There was an error saving the mode.'));
                }
                console.debug(_context5.t0);
              case 9:
              case "end":
                return _context5.stop();
            }
          }
        }, _callee5, null, [[0, 5]]);
      }))();
    },
    onBrowserLocationClick: function onBrowserLocationClick() {
      this.askBrowserLocation();
    },
    usePersonalAddress: function usePersonalAddress() {
      var _this8 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6() {
        var loc;
        return regeneratorRuntime.wrap(function _callee6$(_context6) {
          while (1) {
            switch (_context6.prev = _context6.next) {
              case 0:
                _this8.loading = true;
                _context6.prev = 1;
                _context6.next = 4;
                return _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.usePersonalAddress();
              case 4:
                loc = _context6.sent;
                _this8.lat = loc.lat;
                _this8.lon = loc.lon;
                _this8.address = loc.address;
                _this8.mode = MODE_MANUAL_LOCATION;
                _this8.startLoop();
                _context6.next = 17;
                break;
              case 12:
                _context6.prev = 12;
                _context6.t0 = _context6["catch"](1);
                if (_context6.t0.response && _context6.t0.response.status === 401) {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'You are not logged in.'));
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('weather_status', 'There was an error using personal address.'));
                }
                console.debug(_context6.t0);
                _this8.loading = false;
              case 17:
              case "end":
                return _context6.stop();
            }
          }
        }, _callee6, null, [[1, 12]]);
      }))();
    },
    onAddressSubmit: function onAddressSubmit() {
      var newAddress = this.$refs.addressInput.$el.querySelector('input[type="text"]').value;
      this.setAddress(newAddress);
    },
    getLocalizedTemperature: function getLocalizedTemperature(celcius) {
      return this.useFahrenheitLocale ? celcius * (9 / 5) + 32 : celcius;
    },
    onAddRemoveFavoriteClick: function onAddRemoveFavoriteClick() {
      var currentIsFavorite = this.currentAddressIsFavorite;
      if (currentIsFavorite) {
        var i = this.favorites.indexOf(currentIsFavorite);
        if (i !== -1) {
          this.favorites.splice(i, 1);
        }
      } else {
        this.favorites.push(this.address);
      }
      _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.saveFavorites(this.favorites);
    },
    onFavoriteClick: function onFavoriteClick(e, favAddress) {
      // clicked on the icon
      if (e.target.classList.contains('action-button__icon')) {
        var i = this.favorites.indexOf(favAddress);
        if (i !== -1) {
          this.favorites.splice(i, 1);
        }
        _services_weatherStatusService__WEBPACK_IMPORTED_MODULE_9__.saveFavorites(this.favorites);
      } else if (favAddress !== this.address) {
        // clicked on the text
        this.setAddress(favAddress);
      }
    },
    formatTime: function formatTime(time) {
      return _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()(time).format('LT');
    },
    getTemperature: function getTemperature(forecasts) {
      var offset = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      return forecasts.length > offset ? forecasts[offset].data.instant.details.air_temperature : '';
    },
    getWeatherCode: function getWeatherCode(forecasts) {
      var offset = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      return forecasts.length > offset ? forecasts[offset].data.next_1_hours.summary.symbol_code : '';
    },
    getWeatherIcon: function getWeatherIcon(weatherCode, loading) {
      if (loading) {
        return 'icon-loading-small';
      } else {
        return 'icon-weather ' + (weatherCode && weatherCode in weatherOptions ? weatherOptions[weatherCode].icon : 'icon-fair-day');
      }
    },
    getWeatherMessage: function getWeatherMessage(weatherCode, temperature) {
      var later = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      return weatherCode && weatherCode in weatherOptions ? weatherOptions[weatherCode].text(Math.round(this.getLocalizedTemperature(temperature)), this.temperatureUnit, later) : t('weather_status', 'Set location for weather');
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa&":
/*!*********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa& ***!
  \*********************************************************************************************************************************************************************************************************************************************************/
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
  return _c("li", {
    class: {
      inline: _vm.inline
    }
  }, [_c("div", {
    attrs: {
      id: "weather-status-menu-item"
    }
  }, [_c("NcActions", {
    staticClass: "weather-status-menu-item__subheader",
    attrs: {
      "default-icon": _vm.weatherIcon,
      "menu-title": _vm.currentWeatherMessage
    }
  }, [_vm.gotWeather ? _c("NcActionText", {
    attrs: {
      icon: _vm.futureWeatherIcon
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.forecastMessage) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.gotWeather ? _c("NcActionLink", {
    attrs: {
      icon: "icon-address",
      target: "_blank",
      href: _vm.weatherLinkTarget,
      "close-after-click": true
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.locationText) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.gotWeather ? _c("NcActionButton", {
    attrs: {
      icon: _vm.addRemoveFavoriteIcon
    },
    on: {
      click: _vm.onAddRemoveFavoriteClick
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.addRemoveFavoriteText) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.address && !_vm.errorMessage ? _c("NcActionSeparator") : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: "icon-crosshair",
      "close-after-click": true
    },
    on: {
      click: _vm.onBrowserLocationClick
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("weather_status", "Detect location")) + "\n\t\t\t")]), _vm._v(" "), _c("NcActionInput", {
    ref: "addressInput",
    attrs: {
      disabled: false,
      icon: "icon-rename",
      type: "text",
      value: ""
    },
    on: {
      submit: _vm.onAddressSubmit
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("weather_status", "Set custom address")) + "\n\t\t\t")]), _vm._v(" "), _c("NcActionButton", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.favorites.length > 0,
      expression: "favorites.length > 0"
    }],
    attrs: {
      icon: _vm.toggleFavoritesIcon
    },
    on: {
      click: function click($event) {
        _vm.showFavorites = !_vm.showFavorites;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("weather_status", "Favorites")) + "\n\t\t\t")]), _vm._v(" "), _vm._l(_vm.displayedFavorites, function (f) {
    return _c("NcActionButton", {
      key: f,
      attrs: {
        icon: "icon-starred"
      },
      on: {
        click: function click($event) {
          return _vm.onFavoriteClick($event, f);
        }
      }
    }, [_vm._v("\n\t\t\t\t" + _vm._s(f) + "\n\t\t\t")]);
  })], 2)], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/getUrl.js */ "./node_modules/css-loader/dist/runtime/getUrl.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__);
// Imports



var ___CSS_LOADER_URL_IMPORT_0___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/app-dark.svg */ "./apps/weather_status/img/app-dark.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_1___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun.svg */ "./apps/weather_status/img/sun.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_2___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon.svg */ "./apps/weather_status/img/moon.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_3___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/cloud-cloud.svg */ "./apps/weather_status/img/cloud-cloud.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_4___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun-small-cloud.svg */ "./apps/weather_status/img/sun-small-cloud.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_5___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon-small-cloud.svg */ "./apps/weather_status/img/moon-small-cloud.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_6___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun-cloud.svg */ "./apps/weather_status/img/sun-cloud.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_7___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon-cloud.svg */ "./apps/weather_status/img/moon-cloud.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_8___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/fog.svg */ "./apps/weather_status/img/fog.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_9___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/light-rain.svg */ "./apps/weather_status/img/light-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_10___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/rain.svg */ "./apps/weather_status/img/rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_11___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/heavy-rain.svg */ "./apps/weather_status/img/heavy-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_12___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun-cloud-light-rain.svg */ "./apps/weather_status/img/sun-cloud-light-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_13___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon-cloud-light-rain.svg */ "./apps/weather_status/img/moon-cloud-light-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_14___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun-cloud-rain.svg */ "./apps/weather_status/img/sun-cloud-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_15___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon-cloud-rain.svg */ "./apps/weather_status/img/moon-cloud-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_16___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/sun-cloud-heavy-rain.svg */ "./apps/weather_status/img/sun-cloud-heavy-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_17___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/moon-cloud-heavy-rain.svg */ "./apps/weather_status/img/moon-cloud-heavy-rain.svg"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_18___ = new URL(/* asset import */ __webpack_require__(/*! ./../img/cross.svg */ "./apps/weather_status/img/cross.svg"), __webpack_require__.b);
var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
var ___CSS_LOADER_URL_REPLACEMENT_0___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_0___);
var ___CSS_LOADER_URL_REPLACEMENT_1___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_1___);
var ___CSS_LOADER_URL_REPLACEMENT_2___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_2___);
var ___CSS_LOADER_URL_REPLACEMENT_3___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_3___);
var ___CSS_LOADER_URL_REPLACEMENT_4___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_4___);
var ___CSS_LOADER_URL_REPLACEMENT_5___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_5___);
var ___CSS_LOADER_URL_REPLACEMENT_6___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_6___);
var ___CSS_LOADER_URL_REPLACEMENT_7___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_7___);
var ___CSS_LOADER_URL_REPLACEMENT_8___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_8___);
var ___CSS_LOADER_URL_REPLACEMENT_9___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_9___);
var ___CSS_LOADER_URL_REPLACEMENT_10___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_10___);
var ___CSS_LOADER_URL_REPLACEMENT_11___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_11___);
var ___CSS_LOADER_URL_REPLACEMENT_12___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_12___);
var ___CSS_LOADER_URL_REPLACEMENT_13___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_13___);
var ___CSS_LOADER_URL_REPLACEMENT_14___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_14___);
var ___CSS_LOADER_URL_REPLACEMENT_15___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_15___);
var ___CSS_LOADER_URL_REPLACEMENT_16___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_16___);
var ___CSS_LOADER_URL_REPLACEMENT_17___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_17___);
var ___CSS_LOADER_URL_REPLACEMENT_18___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_18___);
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".icon-weather {\n  background-size: 16px;\n}\n.icon-weather-status {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_0___ + ");\n}\n.icon-clearsky-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_1___ + ");\n}\n.icon-clearsky-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_2___ + ");\n}\n.icon-cloudy {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_3___ + ");\n}\n.icon-fair-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_4___ + ");\n}\n.icon-fair-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_5___ + ");\n}\n.icon-partlycloudy-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_6___ + ");\n}\n.icon-partlycloudy-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_7___ + ");\n}\n.icon-fog {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_8___ + ");\n}\n.icon-lightrain {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_9___ + ");\n}\n.icon-rain {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_10___ + ");\n}\n.icon-heavyrain {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_11___ + ");\n}\n.icon-light-rainshowers-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_12___ + ");\n}\n.icon-light-rainshowers-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_13___ + ");\n}\n.icon-rainshowers-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_14___ + ");\n}\n.icon-rainshowers-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_15___ + ");\n}\n.icon-heavy-rainshowers-day {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_16___ + ");\n}\n.icon-heavy-rainshowers-night {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_17___ + ");\n}\n.icon-crosshair {\n  background-color: var(--color-main-text);\n  padding: 0 !important;\n  mask: url(" + ___CSS_LOADER_URL_REPLACEMENT_18___ + ") no-repeat;\n  mask-size: 18px 18px;\n  mask-position: center;\n  -webkit-mask: url(" + ___CSS_LOADER_URL_REPLACEMENT_18___ + ") no-repeat;\n  -webkit-mask-size: 18px 18px;\n  -webkit-mask-position: center;\n  min-width: 44px !important;\n  min-height: 44px !important;\n}\nli:not(.inline) .weather-status-menu-item__header {\n  display: block;\n  align-items: center;\n  color: var(--color-main-text);\n  padding: 10px 12px 5px 12px;\n  box-sizing: border-box;\n  opacity: 1;\n  white-space: nowrap;\n  width: 100%;\n  text-align: center;\n  max-width: 250px;\n  text-overflow: ellipsis;\n  min-width: 175px;\n}\nli:not(.inline) .weather-status-menu-item__subheader {\n  width: 100%;\n}\nli:not(.inline) .weather-status-menu-item__subheader .trigger > .icon {\n  background-color: var(--color-main-background);\n  background-size: 16px;\n  border: 0;\n  border-radius: 0;\n  font-weight: normal;\n  padding-left: 40px;\n}\nli:not(.inline) .weather-status-menu-item__subheader .trigger > .icon:hover, li:not(.inline) .weather-status-menu-item__subheader .trigger > .icon:focus {\n  box-shadow: inset 4px 0 var(--color-primary-element);\n}\n.inline .weather-status-menu-item__subheader {\n  width: 100%;\n}\n.inline .weather-status-menu-item__subheader .trigger > .icon {\n  background-size: 16px;\n  border: 0;\n  border-radius: var(--border-radius-pill);\n  font-weight: normal;\n  padding-left: 40px;\n}\n.inline .weather-status-menu-item__subheader .trigger > .icon.icon-loading-small::after {\n  left: 21px;\n}\nli {\n  list-style-type: none;\n}", ""]);
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

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/weather_status/src/App.vue":
/*!*****************************************!*\
  !*** ./apps/weather_status/src/App.vue ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./App.vue?vue&type=template&id=a14b84fa& */ "./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa&");
/* harmony import */ var _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App.vue?vue&type=script&lang=js& */ "./apps/weather_status/src/App.vue?vue&type=script&lang=js&");
/* harmony import */ var _App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& */ "./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__.render,
  _App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/weather_status/src/App.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/weather_status/src/App.vue?vue&type=script&lang=js&":
/*!******************************************************************!*\
  !*** ./apps/weather_status/src/App.vue?vue&type=script&lang=js& ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./App.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa&":
/*!************************************************************************!*\
  !*** ./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa& ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_a14b84fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./App.vue?vue&type=template&id=a14b84fa& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=template&id=a14b84fa&");


/***/ }),

/***/ "./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&":
/*!***************************************************************************************!*\
  !*** ./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_id_a14b84fa_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/weather_status/src/App.vue?vue&type=style&index=0&id=a14b84fa&lang=scss&");


/***/ }),

/***/ "./apps/weather_status/img/app-dark.svg":
/*!**********************************************!*\
  !*** ./apps/weather_status/img/app-dark.svg ***!
  \**********************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwLjUiIGhlaWdodD0iMjgwLjUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDI4MCAyODAiPjxwYXRoIGQ9Ik0xNDAuMjIgMjEwLjA0YzM4LjQ4IDAgNjkuNzgtMzEuMyA2OS43OC02OS43OHMtMzEuMy02OS43OC02OS43OC02OS43OGMtMzguNDcgMC02OS43OCAzMS4zLTY5Ljc4IDY5Ljc4czMxLjMgNjkuNzggNjkuNzggNjkuNzhNMTMyLjggMzguOWE3LjQzIDcuNDMgMCAwMDE0Ljg1IDBWNy40NGE3LjQzIDcuNDMgMCAwMC0xNC44NSAwVjM4LjlNMTMyLjggMjQxLjYzdjMxLjQ2YTcuNDMgNy40MyAwIDAwMTQuODUgMHYtMzEuNDZhNy40MyA3LjQzIDAgMDAtMTQuODUgME04OS41NCA1OS45MWE3LjQzIDcuNDMgMCAwMDYuNDMtMTEuMTRMODAuMjQgMjEuNTNhNy40MyA3LjQzIDAgMDAtMTIuODYgNy40M0w4My4xIDU2LjJhNy40MiA3LjQyIDAgMDA2LjQzIDMuNzFNMTg3LjIgMjIxLjYyYTcuNDMgNy40MyAwIDAwLTIuNzIgMTAuMTRMMjAwLjIgMjU5YTcuNDIgNy40MiAwIDEwMTIuODYtNy40MmwtMTUuNzMtMjcuMjVhNy40MyA3LjQzIDAgMDAtMTAuMTUtMi43MU0xOC43NiA3MC4xNGE3LjQzIDcuNDMgMCAwMDIuNzIgMTAuMTVMNDguNzIgOTZhNy40MiA3LjQyIDAgMTA3LjQzLTEyLjg2TDI4LjkgNjcuNDJhNy40MyA3LjQzIDAgMDAtMTAuMTQgMi43Mk0yNTguOTcgMjAwLjI0bC0yNy4yNS0xNS43M2E3LjQzIDcuNDMgMCAwMC03LjQyIDEyLjg3bDI3LjI0IDE1LjczYTcuNCA3LjQgMCAwMDEwLjE0LTIuNzIgNy40MyA3LjQzIDAgMDAtMi43MS0xMC4xNU00Ni4yOCAxNDAuMjdjMC00LjEtMy4zMy03LjQyLTcuNDMtNy40Mkg3LjRhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDMxLjQ2YzQuMSAwIDcuNDMtMy4zMyA3LjQzLTcuNDNNMjczLjA1IDEzMi44NWgtMzEuNDZhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDMxLjQ2YTcuNDMgNy40MyAwIDAwMC0xNC44NU00OC43MyAxODQuNTFMMjEuNSAyMDAuMjRhNy40MyA3LjQzIDAgMTA3LjQyIDEyLjg2bDI3LjI1LTE1LjczYTcuNDMgNy40MyAwIDAwLTcuNDMtMTIuODZNMjUxLjU0IDY3LjQyTDIyNC4zIDgzLjE1QTcuNDMgNy40MyAwIDAwMjMxLjcyIDk2bDI3LjI0LTE1LjczYTcuNDMgNy40MyAwIDAwLTcuNDItMTIuODZNODMuMSAyMjQuMzRsLTE1LjczIDI3LjI0YTcuNDMgNy40MyAwIDAwMTIuODcgNy40M2wxNS43My0yNy4yNWE3LjQzIDcuNDMgMCAwMC0xMi44Ny03LjQyTTE4Ny4yIDU4LjkxYTcuNCA3LjQgMCAwMDEwLjE0LTIuNzFsMTUuNzMtMjcuMjVhNy40MyA3LjQzIDAgMTAtMTIuODYtNy40MmwtMTUuNzMgMjcuMjRhNy40MyA3LjQzIDAgMDAyLjcxIDEwLjE0Ii8+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/cloud-cloud.svg":
/*!*************************************************!*\
  !*** ./apps/weather_status/img/cloud-cloud.svg ***!
  \*************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjk0LjcxIiBoZWlnaHQ9IjE4OS4xNiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjk1IDE5MCI+PHBhdGggZD0iTTEwOS4wMyAxMTkuMmMwLTI5LjE5IDI2LjQtNTIuOTQgNTguODYtNTIuOTQgNy4wMiAwIDEzLjgxIDEuMDggMjAuMjkgMy4yMmE1NC4zNiA1NC4zNiAwIDAxMjUuMDQtMTQuMzZBNTEuOTUgNTEuOTUgMCAwMDE2Ni45IDI2LjNjLTQuMjEgMC04LjQxLjUyLTEyLjQ4IDEuNTItMyAuNzQtNi4xNS0uNDQtNy45LTIuOTlhNTcuMTMgNTcuMTMgMCAwMC04NS41OC05Ljg0IDU2Ljk1IDU2Ljk1IDAgMDAtMTguMzMgMzYuNjUgNy40MiA3LjQyIDAgMDEtNC4yIDZjLS42OS4zNC0xLjM4LjY4LTIuMDUgMS4wNi0uNjIuMzQtMS4yOS42LTEuOTguNzZBNDQuMyA0NC4zIDAgMDA5LjggNzQuNjZhNDMuMiA0My4yIDAgMDAtOS44IDI3LjU4YzAgMjQuMTkgMTkuNzggNDMuODYgNDQuMSA0My44Nmg1Mi45NmE0OC4wNCA0OC4wNCAwIDAxMTIuMDMtMjQuNjNjLS4wMy0uNzYtLjA1LTEuNTEtLjA1LTIuMjciIGZpbGw9IiM2MWM5ZTciLz48cGF0aCBkPSJNMjY5LjI1IDEyMC40YTcuNDMgNy40MyAwIDAxLTQuNDYtOS41IDI4Ljg1IDI4Ljg1IDAgMDAxLjcyLTkuODJjMC0xNC4xLTEwLjI3LTI2LjE2LTI0LjctMzAuOWE0My42MyA0My42MyAwIDAwLTIxLjA4LTEuNTVjLTkuOSAxLjY4LTE4Ljc0IDYuNy0yNC41IDE0LjEyYTcuNDMgNy40MyAwIDAxLTguOCAyLjI2IDQ5LjEyIDQ5LjEyIDAgMDAtMTkuNTMtMy45NmMtMjQuMjcgMC00NC4wMiAxNy4xLTQ0LjAyIDM4LjEgMCAxLjM0LjA5IDIuNzMuMjYgNC4xYTcuMzkgNy4zOSAwIDAxLTIuMjMgNi4yNiAzNC4zIDM0LjMgMCAwMC05LjcxIDE2LjUzIDMxLjAzIDMxLjAzIDAgMDAuMDIgMTQuODVjNCAxNi4xMyAyMC42MiAyOC4yNyA0MC40NiAyOC4yN2gxMDAuNjNjMjIuODIgMCA0MS4zOC0xNi4wNCA0MS4zOC0zNS43NiAwLTE0LjQ1LTkuOTktMjcuNDEtMjUuNDQtMzMiIGZpbGw9IiM0NDkyYTgiLz48L3N2Zz4=";

/***/ }),

/***/ "./apps/weather_status/img/cross.svg":
/*!*******************************************!*\
  !*** ./apps/weather_status/img/cross.svg ***!
  \*******************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIHN0cm9rZS13aWR0aD0iMiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIvPjxwYXRoIGQ9Ik0yMiAxMmgtNE02IDEySDJNMTIgNlYyTTEyIDIydi00Ii8+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/fog.svg":
/*!*****************************************!*\
  !*** ./apps/weather_status/img/fog.svg ***!
  \*****************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU0LjQ0IiBoZWlnaHQ9IjI1Ni4zOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjU1IDI1NyI+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTIzMy4zIDcxLjU4YTcuNDIgNy40MiAwIDAxLTIuNjMtMy44NCA1NS41IDU1LjUgMCAwMC01My4wOC0zOS44N2MtNC41IDAtOSAuNTUtMTMuMzUgMS42My0zIC43NC02LjE1LS40NS03LjktM0E2MS4xIDYxLjEgMCAwMDEwNiAuMDEgNjAuODkgNjAuODkgMCAwMDY0LjgzIDE2YTYwLjg3IDYwLjg3IDAgMDAtMTkuNiAzOS4yIDcuNDIgNy40MiAwIDAxLTQuMiA2Yy0uNzMuMzQtMS40Ny43Mi0yLjIgMS4xMi0uNjEuMzUtMS4yOC42LTEuOTcuNzVBNDcuNDggNDcuNDggMCAwMDEwLjUgNzkuMzcgNDYuMyA0Ni4zIDAgMDAwIDEwOC45MmMwIDI1LjkxIDIxLjIgNDcgNDcuMjYgNDdIMjA0LjZjMjYuMDYgMCA0Ny4yNy0yMS4wOSA0Ny4yNy00N2E0Ni42IDQ2LjYgMCAwMC0xOC41Ni0zNy4zNE0yMzEuMzIgMTg3LjkxYzAtNC4xLTMuMzMtNy40My03LjQzLTcuNDNIMjguODdhNy40MiA3LjQyIDAgMTAwIDE0Ljg2aDE5NS4wMmM0LjEgMCA3LjQzLTMuMzQgNy40My03LjQzTTIxMy4xNCAyNDEuNTRIMTguMTJhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDE5NS4wMmE3LjQyIDcuNDIgMCAxMDAtMTQuODVNNjIuMjggMjExLjQzYTcuNDIgNy40MiAwIDEwMCAxNC44NWg3MS40N2E3LjQzIDcuNDMgMCAwMDAtMTQuODVINjIuMjhNMjQ3IDIxMS40M2gtNzEuNDhhNy40MiA3LjQyIDAgMTAwIDE0Ljg1SDI0N2E3LjQzIDcuNDMgMCAwMDAtMTQuODUiLz48L2c+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/heavy-rain.svg":
/*!************************************************!*\
  !*** ./apps/weather_status/img/heavy-rain.svg ***!
  \************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUxLjg4IiBoZWlnaHQ9IjI1OC42NCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjUxIDI1OSI+PHBhdGggZD0iTTI1MS44NSAxMDguOWE0Ni42IDQ2LjYgMCAwMC0xOC41Ni0zNy4zNCA3LjQzIDcuNDMgMCAwMS0yLjYzLTMuODUgNTUuNSA1NS41IDAgMDAtNTMuMDgtMzkuODZjLTQuNSAwLTkgLjU0LTEzLjM1IDEuNjItMyAuNzQtNi4xNS0uNDUtNy45LTNBNjEuMSA2MS4xIDAgMDAxMDYgMGE2MC44OSA2MC44OSAwIDAwLTQxLjE4IDE1Ljk3IDYwLjg3IDYwLjg3IDAgMDAtMTkuNiAzOS4yIDcuNDMgNy40MyAwIDAxLTQuMiA2Yy0uNzMuMzUtMS40Ny43My0yLjIgMS4xMy0uNjEuMzQtMS4yOC42LTEuOTcuNzVhNDcuNDggNDcuNDggMCAwMC0yNi4zNSAxNi4zQTQ2LjMgNDYuMyAwIDAwLS4wMSAxMDguOWMwIDI1LjkyIDIxLjIgNDcgNDcuMjYgNDdIMjA0LjZjMjYuMDcgMCA0Ny4yNy0yMS4wOCA0Ny4yNy00NyIgZmlsbD0iIzQ0OTJhOCIvPjxnIGZpbGw9IiM2MWM5ZTciPjxwYXRoIGQ9Ik02Ni42NiAyMjMuNDRhNy40MiA3LjQyIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDIgNy40MiAwIDEwLTE0LjIgNC4zNWw4Ljk0IDI5LjE3YTcuNDMgNy40MyAwIDAwNy4xIDUuMjZNMTA3LjQgMjU4LjYyYTcuNDMgNy40MyAwIDAwNy4xLTkuNmwtOC45My0yOS4xOGE3LjQyIDcuNDIgMCAxMC0xNC4yIDQuMzVsOC45NCAyOS4xOGE3LjQzIDcuNDMgMCAwMDcuMSA1LjI1TTE1OS4yMiAyMTMuMDZhNy40MyA3LjQzIDAgMDAtNC45MyA5LjI4bDguOTQgMjkuMTdhNy40MyA3LjQzIDAgMTAxNC4yLTQuMzVMMTY4LjUgMjE4YTcuNDMgNy40MyAwIDAwLTkuMjctNC45M00xMjguODQgMjIzLjQ0YTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45My0yOS4xOGE3LjQzIDcuNDMgMCAxMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2TTE5MS4wMyAyMjMuNDRhNy40MSA3LjQxIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4QTcuNDIgNy40MiAwIDEwMTc1IDE4OWw4Ljk0IDI5LjE3YTcuNDMgNy40MyAwIDAwNy4xIDUuMjYiLz48L2c+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/light-rain.svg":
/*!************************************************!*\
  !*** ./apps/weather_status/img/light-rain.svg ***!
  \************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjk0LjcxIiBoZWlnaHQ9IjI1OC41NyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjk1IDI1OSI+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTEwOS4wNCAxMTkuMThjMC0yOS4yIDI2LjQtNTIuOTUgNTguODctNTIuOTUgNyAwIDEzLjggMS4wOCAyMC4yOCAzLjIyYTU0LjMyIDU0LjMyIDAgMDEyNS4wNC0xNC4zNSA1MS45MyA1MS45MyAwIDAwLTQ2LjMyLTI4LjgzYy00LjIxIDAtOC40MS41MS0xMi40NyAxLjUyLTMgLjc1LTYuMTYtLjQ1LTcuOTEtM0E1Ny4xNSA1Ny4xNSAwIDAwOTkuNDYuMDJjLTE0LjMgMC0yNy45NyA1LjMxLTM4LjUxIDE0Ljk1YTU2LjkzIDU2LjkzIDAgMDAtMTguMzMgMzYuNjUgNy40MyA3LjQzIDAgMDEtNC4yIDZjLS42OC4zMi0xLjM3LjY4LTIuMDUgMS4wNS0uNjIuMzUtMS4yOS42LTEuOTguNzVBNDQuMyA0NC4zIDAgMDA5LjggNzQuNjQgNDMuMiA0My4yIDAgMDAwIDEwMi4yYzAgMjQuMTggMTkuNzggNDMuODYgNDQuMSA0My44Nmg1Mi45NmE0OC4wNCA0OC4wNCAwIDAxMTIuMDMtMjQuNjNjLS4wMy0uNzYtLjA1LTEuNTEtLjA1LTIuMjZNNjQuOTkgMjU4LjU5YTcuNDQgNy40NCAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2TTEyNy4xNyAyNTguNTlhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE3YTcuNDMgNy40MyAwIDAwNy4xIDUuMjZNMTg5LjM2IDI1OC41OWE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMTdhNy40MyA3LjQzIDAgMDA3LjEgNS4yNiIvPjwvZz48cGF0aCBkPSJNMjY5LjI2IDEyMC40MmE3LjQzIDcuNDMgMCAwMS00LjQ2LTkuNSAyOC44NiAyOC44NiAwIDAwMS43My05LjgyYzAtMTQuMS0xMC4yOC0yNi4xNS0yNC43LTMwLjkxYTQzLjU3IDQzLjU3IDAgMDAtMjEuMDktMS41NWMtOS45IDEuNjktMTguNzQgNi43LTI0LjQ5IDE0LjEyYTcuNDMgNy40MyAwIDAxLTguOCAyLjI4IDQ5LjEyIDQ5LjEyIDAgMDAtMTkuNTQtMy45NmMtMjQuMjcgMC00NC4wMiAxNy4wOS00NC4wMiAzOC4wOSAwIDEuMzUuMDkgMi43My4yNiA0LjFhNy40MyA3LjQzIDAgMDEtMi4yMyA2LjI2IDM0LjIxIDM0LjIxIDAgMDAtOS43MSAxNi41MyAzMS4wMyAzMS4wMyAwIDAwLjAzIDE0Ljg1YzQgMTYuMTMgMjAuNjEgMjguMjcgNDAuNDUgMjguMjdoMTAwLjYzYzIyLjgyIDAgNDEuMzgtMTYuMDUgNDEuMzgtMzUuNzYgMC0xNC40Ni05Ljk4LTI3LjQxLTI1LjQ0LTMzIiBmaWxsPSIjNDQ5MmE4Ii8+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/moon-cloud-heavy-rain.svg":
/*!***********************************************************!*\
  !*** ./apps/weather_status/img/moon-cloud-heavy-rain.svg ***!
  \***********************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzE4Ljk0IiBoZWlnaHQ9IjI4OS42MiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzE5IDI5MCI+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTI4NS4wNCAxMjcuNmE3LjQyIDcuNDIgMCAwMS0yLjY0LTMuODUgNTUuNSA1NS41IDAgMDAtNTMuMDgtMzkuODZjLTQuNSAwLTkgLjU0LTEzLjM0IDEuNjItMyAuNzUtNi4xNS0uNDUtNy45LTNhNjEuMSA2MS4xIDAgMDAtNTAuMzMtMjYuNDggNjAuODkgNjAuODkgMCAwMC00MS4xOCAxNS45OCA2MC44OCA2MC44OCAwIDAwLTE5LjYgMzkuMTkgNy40MiA3LjQyIDAgMDEtNC4yIDZjLS43My4zNS0xLjQ3LjczLTIuMiAxLjEzLS42Mi4zNC0xLjI5LjYtMS45OC43NWE0Ny40OCA0Ny40OCAwIDAwLTI2LjM1IDE2LjMgNDYuMjkgNDYuMjkgMCAwMC0xMC41MSAyOS41NWMwIDI1LjkyIDIxLjIgNDcgNDcuMjcgNDdoMTU3LjM0YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzMiIHBhaW50LW9yZGVyPSJzdHJva2UgZmlsbCBtYXJrZXJzIi8+PHBhdGggZD0iTTkxLjk4IDI4Ny4xYTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xOGE3LjQzIDcuNDMgMCAwMDcuMSA1LjI1TTE1NC4xNiAyODcuMWE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0yMTYuMzUgMjg3LjFhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDEwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMTE2LjU4IDI1OS45N2E3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMTdhNy40MyA3LjQzIDAgMDA3LjEgNS4yNk0xNzguNzYgMjU5Ljk3YTcuNDMgNy40MyAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAxMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2TTI3OC4zMyAyODcuMDlhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDEwLTE0LjIgNC4zNWw4Ljk0IDI5LjE3YTcuNDMgNy40MyAwIDAwNy4xIDUuMjZNMjQwLjczIDI1OS45NWE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNSIvPjwvZz48cGF0aCBkPSJNNzkuODItLjA1Yy0zLjM3IDEuMDItNi42OSAyLjItOS45NiAzLjU0LTU3LjU1IDIzLjU3LTg1LjIgODkuNTYtNjEuNjMgMTQ3LjEyIDguMTkgMjAgMjEuNjEgMzYuODQgMzguOCA0OS4wN2E2MS42IDYxLjYgMCAwMS0xMC44LTM0LjgzYzAtMTQuMyA0LjkzLTI4LjEzIDEzLjk2LTM5LjIyYTYyLjY2IDYyLjY2IDAgMDE1Ljk5LTYuMzYgMTI1LjgyIDEyNS44MiAwIDAxLTEuNjktNzAuMDlBMTI4LjQzIDEyOC40MyAwIDAxNzkuODItLjA1eiIgZmlsbD0iI2UxYzAxNCIvPjwvc3ZnPg==";

/***/ }),

/***/ "./apps/weather_status/img/moon-cloud-light-rain.svg":
/*!***********************************************************!*\
  !*** ./apps/weather_status/img/moon-cloud-light-rain.svg ***!
  \***********************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzE4Ljk0IiBoZWlnaHQ9IjI4OS42MiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzE5IDI5MCI+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTI4NS4wNCAxMjcuNmE3LjQyIDcuNDIgMCAwMS0yLjY0LTMuODUgNTUuNSA1NS41IDAgMDAtNTMuMDgtMzkuODZjLTQuNSAwLTkgLjU0LTEzLjM0IDEuNjItMyAuNzUtNi4xNS0uNDUtNy45LTNhNjEuMSA2MS4xIDAgMDAtNTAuMzMtMjYuNDggNjAuODkgNjAuODkgMCAwMC00MS4xOCAxNS45OCA2MC44OCA2MC44OCAwIDAwLTE5LjYgMzkuMTkgNy40MiA3LjQyIDAgMDEtNC4yIDZjLS43My4zNS0xLjQ3LjczLTIuMiAxLjEzLS42Mi4zNC0xLjI5LjYtMS45OC43NWE0Ny40OCA0Ny40OCAwIDAwLTI2LjM1IDE2LjMgNDYuMjkgNDYuMjkgMCAwMC0xMC41MSAyOS41NWMwIDI1LjkyIDIxLjIgNDcgNDcuMjcgNDdoMTU3LjM0YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzMiIHBhaW50LW9yZGVyPSJzdHJva2UgZmlsbCBtYXJrZXJzIi8+PHBhdGggZD0iTTExOS45NSAyODkuNjNhNy40MSA3LjQxIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMTgyLjEzIDI4OS42M2E3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTMtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0yNDQuMzIgMjg5LjYzYTcuNDMgNy40MyAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAxMC0xNC4yIDQuMzVsOC45NCAyOS4xOGE3LjQzIDcuNDMgMCAwMDcuMSA1LjI1Ii8+PC9nPjxwYXRoIGQ9Ik04MC42Ni0uOUM3Ny4yOS4xNCA3My45NyAxLjMyIDcwLjcgMi42NiAxMy4xNSAyNi4yMi0xNC41IDkyLjIxIDkuMDcgMTQ5Ljc3YzguMTkgMjAgMjEuNjEgMzYuODQgMzguOCA0OS4wN0E2MS42IDYxLjYgMCAwMTM3LjA3IDE2NGMwLTE0LjMgNC45My0yOC4xMyAxMy45Ni0zOS4yMmE2Mi42NiA2Mi42NiAwIDAxNS45OS02LjM2IDEyNS44MiAxMjUuODIgMCAwMS0xLjY5LTcwLjA5QTEyOC40MyAxMjguNDMgMCAwMTgwLjY2LS44OXoiIGZpbGw9IiNlMWMwMTQiLz48L3N2Zz4=";

/***/ }),

/***/ "./apps/weather_status/img/moon-cloud-rain.svg":
/*!*****************************************************!*\
  !*** ./apps/weather_status/img/moon-cloud-rain.svg ***!
  \*****************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzE4Ljk0IiBoZWlnaHQ9IjI4OS42MiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzE5IDI5MCI+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTI4NS4wNCAxMjcuNmE3LjQyIDcuNDIgMCAwMS0yLjY0LTMuODUgNTUuNSA1NS41IDAgMDAtNTMuMDgtMzkuODZjLTQuNSAwLTkgLjU0LTEzLjM0IDEuNjItMyAuNzUtNi4xNS0uNDUtNy45LTNhNjEuMSA2MS4xIDAgMDAtNTAuMzMtMjYuNDggNjAuODkgNjAuODkgMCAwMC00MS4xOCAxNS45OCA2MC44OCA2MC44OCAwIDAwLTE5LjYgMzkuMTkgNy40MiA3LjQyIDAgMDEtNC4yIDZjLS43My4zNS0xLjQ3LjczLTIuMiAxLjEzLS42Mi4zNC0xLjI5LjYtMS45OC43NWE0Ny40OCA0Ny40OCAwIDAwLTI2LjM1IDE2LjMgNDYuMjkgNDYuMjkgMCAwMC0xMC41MSAyOS41NWMwIDI1LjkyIDIxLjIgNDcgNDcuMjcgNDdoMTU3LjM0YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzMiIHBhaW50LW9yZGVyPSJzdHJva2UgZmlsbCBtYXJrZXJzIi8+PHBhdGggZD0iTTEyNS4wMyAyODcuOTRhNy40MSA3LjQxIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMTg3LjIxIDI4Ny45NGE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTMtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0yNDkuNCAyODcuOTRhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDEwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMTQ5LjYzIDI2MC44YTcuNDMgNy40MyAwIDAwNy4xLTkuNmwtOC45NC0yOS4xN2E3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2TTIxMS44MSAyNjAuOGE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMTdhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTUgMjkuMTdhNy40MyA3LjQzIDAgMDA3LjEgNS4yNiIvPjwvZz48cGF0aCBkPSJNNzkuODItLjA1Yy0zLjM3IDEuMDItNi42OSAyLjItOS45NiAzLjU0LTU3LjU1IDIzLjU3LTg1LjIgODkuNTYtNjEuNjMgMTQ3LjEyIDguMTkgMjAgMjEuNjEgMzYuODQgMzguOCA0OS4wN2E2MS42IDYxLjYgMCAwMS0xMC44LTM0LjgzYzAtMTQuMyA0LjkzLTI4LjEzIDEzLjk2LTM5LjIyYTYyLjY2IDYyLjY2IDAgMDE1Ljk5LTYuMzYgMTI1LjgyIDEyNS44MiAwIDAxLTEuNjktNzAuMDlBMTI4LjQzIDEyOC40MyAwIDAxNzkuODItLjA1eiIgZmlsbD0iI2UxYzAxNCIvPjwvc3ZnPg==";

/***/ }),

/***/ "./apps/weather_status/img/moon-cloud.svg":
/*!************************************************!*\
  !*** ./apps/weather_status/img/moon-cloud.svg ***!
  \************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjM4LjMiIGhlaWdodD0iMjI4LjU3IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMzkgMjI5Ij48ZyBmaWxsPSIjNjFjOWU3Ij48cGF0aCBkPSJNOTEuNjQgNjYuNTRMODYuNDYgODUuOWwxOC41Mi03LjY2IDE2LjggMTAuOTEtMS41NS0xOS45NyAxNS41Ny0xMi42Mi0xOS40OC00LjY5LTcuMTktMTguNy0xMC40OCAxNy4wNy0yMC4wMSAxLjA0IDEzIDE1LjI2TTE3Ni43OSA0NS40NmwtNy42Ny0xMC4zLTMuNTEgMTIuMzUtMTIuMTcgNC4xIDEwLjY2IDcuMTYuMTQgMTIuODQgMTAuMS03LjkyIDEyLjI3IDMuODMtNC40Mi0xMi4wNiA3LjQzLTEwLjQ4LTEyLjgzLjQ4Ii8+PHBhdGggZD0iTTIxMi4xNyAxNTUuNjRhNS4yNSA1LjI1IDAgMDEtMS44Ny0yLjczIDM5LjUyIDM5LjUyIDAgMDAtMzcuNzktMjguMzhjLTMuMiAwLTYuNC4zOS05LjUgMS4xNmE1LjI5IDUuMjkgMCAwMS01LjYzLTIuMTQgNDMuNSA0My41IDAgMDAtNjUuMTQtNy40OCA0My4zNSA0My4zNSAwIDAwLTEzLjk1IDI3LjkgNS4zIDUuMyAwIDAxLTMgNC4yOGMtLjUyLjI0LTEuMDQuNTEtMS41Ni44LS40NC4yNC0uOTEuNDItMS40LjUzYTMzLjg0IDMzLjg0IDAgMDAtMTguNzcgMTEuNiAzMi45OSAzMi45OSAwIDAwLTcuNDggMjEuMDVjMCAxOC40NCAxNS4xIDMzLjQ1IDMzLjY1IDMzLjQ1aDExMmMxOC41NiAwIDMzLjY2LTE1IDMzLjY2LTMzLjQ1YTMzLjIgMzMuMiAwIDAwLTEzLjIyLTI2LjU5IiBwYWludC1vcmRlcj0ic3Ryb2tlIGZpbGwgbWFya2VycyIvPjwvZz48cGF0aCBkPSJNODIuOS0uMzNhMTE3LjYyIDExNy42MiAwIDAwLTEwLjMgMy42N0MxMy4wNiAyNy43My0xNS41NSA5NiA4Ljg0IDE1NS41NGExMTYuNDMgMTE2LjQzIDAgMDAyNi4zNiAzOS4yNCA0NS42MiA0NS42MiAwIDAxLTEuODctMTIuOUE0Ni4zIDQ2LjMgMCAwMTQzLjcgMTUyLjdsLjAxLS4wMWE0Ni41OSA0Ni41OSAwIDAxMjAuNTItMTQuNDdBMTMwLjQgMTMwLjQgMCAwMTU2LjcgNTAuNiAxMzIuODggMTMyLjg4IDAgMDE4Mi45LS4zMnoiIGZpbGw9IiNlMWMwMTQiLz48L3N2Zz4=";

/***/ }),

/***/ "./apps/weather_status/img/moon-small-cloud.svg":
/*!******************************************************!*\
  !*** ./apps/weather_status/img/moon-small-cloud.svg ***!
  \******************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjM4LjMiIGhlaWdodD0iMjI4LjU3IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMzkgMjI5Ij48ZyBmaWxsPSIjNjFjOWU3Ij48cGF0aCBkPSJNOTguNDMgMTAyLjEybC01LjE5IDE5LjM2IDE4LjUzLTcuNjUgMTYuOCAxMC45LTEuNTYtMTkuOTcgMTUuNTgtMTIuNjEtMTkuNDktNC43LTcuMTgtMTguNy0xMC40OCAxNy4wOC0yMC4wMiAxLjA0IDEzLjAxIDE1LjI1TTE3Ni43OSA0NS40NmwtNy42Ny0xMC4zLTMuNTEgMTIuMzUtMTIuMTcgNC4xIDEwLjY2IDcuMTYuMTQgMTIuODQgMTAuMS03LjkyIDEyLjI3IDMuODMtNC40Mi0xMi4wNiA3LjQzLTEwLjQ4LTEyLjgzLjQ4Ii8+PHBhdGggZD0iTTIyMC43NiAxNzkuNTlhMy41MyAzLjUzIDAgMDEtMS4yNi0xLjg0IDI2LjU0IDI2LjU0IDAgMDAtMzEuNzYtMTguMjggMy41NSAzLjU1IDAgMDEtMy43OC0xLjQ0IDI5LjIxIDI5LjIxIDAgMDAtNTMuMTIgMTMuNzIgMy41NiAzLjU2IDAgMDEtMiAyLjg3Yy0uMzYuMTctLjcxLjM1LTEuMDYuNTQtLjMuMTYtLjYxLjI4LS45NS4zNWEyMi43MyAyMi43MyAwIDAwLTEyLjYgNy44IDIyLjE1IDIyLjE1IDAgMDAtNS4wMiAxNC4xMyAyMi41NiAyMi41NiAwIDAwMjIuNiAyMi40N2g3NS4yM2EyMi41NiAyMi41NiAwIDAwMjIuNi0yMi40NyAyMi4zIDIyLjMgMCAwMC04Ljg4LTE3Ljg1IiBwYWludC1vcmRlcj0ic3Ryb2tlIGZpbGwgbWFya2VycyIvPjwvZz48cGF0aCBkPSJNODIuNDctLjAyYTExNy42MiAxMTcuNjIgMCAwMC0xMC4zIDMuNjdDMTIuNjMgMjguMDMtMTUuOTcgOTYuMyA4LjQxIDE1NS44NWExMTUuOTEgMTE1LjkxIDAgMDA2Mi45IDYzLjQgMTE2LjE4IDExNi4xOCAwIDAwNTQuMzkgOC43M2MtMTQuNC0yLjc0LTI1LjQ1LTE1LjQ0LTI1LjQ1LTMwLjU3IDAtMy40Ni41OS02Ljg2IDEuNy0xMC4wOWExMzAuNDIgMTMwLjQyIDAgMDEtNDAuMTItNTMuMzUgMTMwLjQgMTMwLjQgMCAwMS01LjU2LTgzLjA2QTEzMi44OCAxMzIuODggMCAwMTgyLjQ3LS4wMnoiIGZpbGw9IiNlMWMwMTQiLz48L3N2Zz4=";

/***/ }),

/***/ "./apps/weather_status/img/moon.svg":
/*!******************************************!*\
  !*** ./apps/weather_status/img/moon.svg ***!
  \******************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTg5LjYzIiBoZWlnaHQ9IjIyOC40IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODkgMjI4Ij48cGF0aCBkPSJNNjIuMTEgMTM0YTEzMC40IDEzMC40IDAgMDEtNS41NS04My4wNUExMzIuODkgMTMyLjg5IDAgMDE4Mi43NiAwYTExNy42NCAxMTcuNjQgMCAwMC0xMC4zIDMuNjdDMTIuOTIgMjguMDctMTUuNyA5Ni4zNCA4LjcgMTU1Ljg4YTExNS45MSAxMTUuOTEgMCAwMDYyLjg5IDYzLjQgMTE1LjkzIDExNS45MyAwIDAwODkuMy4zNmMzLjM5LTEuMzkgNi43LTIuOTIgOS45Mi00LjYyYTEzMi42NiAxMzIuNjYgMCAwMS01NC40LTE3LjkyIDEzMC40NSAxMzAuNDUgMCAwMS01NC4zLTYzLjEiIGZpbGw9IiNlMWMwMTQiLz48cGF0aCBkPSJNMTIyLjE1IDEyMy4zbC01LjE5IDE5LjM3IDE4LjUyLTcuNjUgMTYuODEgMTAuOS0xLjU2LTE5Ljk3IDE1LjU4LTEyLjYxLTE5LjQ5LTQuNy03LjE4LTE4LjctMTAuNDggMTcuMDgtMjAuMDIgMS4wNCAxMy4wMSAxNS4yNU0xNzYuNzkgNDUuNDZsLTcuNjctMTAuMy0zLjUxIDEyLjM1LTEyLjE3IDQuMSAxMC42NiA3LjE2LjE0IDEyLjg0IDEwLjEtNy45MiAxMi4yNyAzLjgzLTQuNDItMTIuMDYgNy40My0xMC40OC0xMi44My40OCIgZmlsbD0iIzYxYzllNyIvPjwvc3ZnPg==";

/***/ }),

/***/ "./apps/weather_status/img/rain.svg":
/*!******************************************!*\
  !*** ./apps/weather_status/img/rain.svg ***!
  \******************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUxLjg3IiBoZWlnaHQ9IjIyMy40NSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjUyIDIyNCI+PHBhdGggZD0iTTQ3LjI2IDE1NS45SDIwNC42YzI2LjA2IDAgNDcuMjctMjEuMSA0Ny4yNy00N2E0Ni42IDQ2LjYgMCAwMC0xOC41Ni0zNy4zNCA3LjQzIDcuNDMgMCAwMS0yLjY0LTMuODUgNTUuNSA1NS41IDAgMDAtNTMuMDgtMzkuODZjLTQuNSAwLTkgLjU0LTEzLjM0IDEuNjItMyAuNzQtNi4xNS0uNDUtNy45LTNBNjEuMSA2MS4xIDAgMDAxMDYuMDEgMGE2MC44OSA2MC44OSAwIDAwLTQxLjE4IDE1Ljk4IDYwLjg4IDYwLjg4IDAgMDAtMTkuNiAzOS4xOSA3LjQyIDcuNDIgMCAwMS00LjIgNmMtLjc0LjM1LTEuNDguNzMtMi4yIDEuMTMtLjYyLjM0LTEuMjkuNi0xLjk4Ljc1YTQ3LjQ3IDQ3LjQ3IDAgMDAtMjYuMzUgMTYuM0E0Ni4zIDQ2LjMgMCAwMDAgMTA4LjljMCAyNS45MiAyMS4yIDQ3IDQ3LjI2IDQ3IiBmaWxsPSIjNDQ5MmE4Ii8+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTU1LjU1IDE3OS43NGE3LjQyIDcuNDIgMCAwMC00LjkyIDkuMjhsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAxMDE0LjItNC4zNWwtOC45NC0yOS4xN2E3LjQyIDcuNDIgMCAwMC05LjI4LTQuOTNNMTE3Ljc0IDE3OS43NGE3LjQzIDcuNDMgMCAwMC00LjkzIDkuMjhsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDE0LjItNC4zNWwtOC45NC0yOS4xN2E3LjQzIDcuNDMgMCAwMC05LjI3LTQuOTNNMTc5LjkyIDE3OS43NGE3LjQzIDcuNDMgMCAwMC00LjkyIDkuMjhsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDE0LjItNC4zNWwtOC45NC0yOS4xN2E3LjQzIDcuNDMgMCAwMC05LjI4LTQuOTMiLz48L2c+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/sun-cloud-heavy-rain.svg":
/*!**********************************************************!*\
  !*** ./apps/weather_status/img/sun-cloud-heavy-rain.svg ***!
  \**********************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzA3LjE5IiBoZWlnaHQ9IjI5MS4zMyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzA3IDI5MSI+PHBhdGggZD0iTTU1LjU3IDkyLjU0YzAgOS4yNyAzLjQzIDE4LjAyIDkuNTQgMjQuNzZhNjIuMzYgNjIuMzYgMCAwMTIxLjIyLTEwLjM4IDc1LjcyIDc1LjcyIDAgMDEyNS42MS00NS44IDM2Ljk3IDM2Ljk3IDAgMDAtNTYuMzcgMzEuNCIgZmlsbD0iI2RlYzYwZiIvPjxnIGZpbGw9IiM2MWM5ZTciPjxwYXRoIGQ9Ik0yODguNiAxMjkuM2E3LjQyIDcuNDIgMCAwMS0yLjYzLTMuODVBNTUuNSA1NS41IDAgMDAyMzIuOSA4NS42Yy00LjUgMC05IC41NC0xMy4zNCAxLjYyLTMgLjc1LTYuMTUtLjQ1LTcuOS0zYTYxLjEgNjEuMSAwIDAwLTUwLjMzLTI2LjQ4IDYwLjg5IDYwLjg5IDAgMDAtNDEuMTggMTUuOTggNjAuODggNjAuODggMCAwMC0xOS42IDM5LjE5IDcuNDIgNy40MiAwIDAxLTQuMiA2Yy0uNzMuMzUtMS40Ny43My0yLjIgMS4xMy0uNjIuMzQtMS4yOS42LTEuOTguNzVhNDcuNDggNDcuNDggMCAwMC0yNi4zNSAxNi4zIDQ2LjI5IDQ2LjI5IDAgMDAtMTAuNTEgMjkuNTVjMCAyNS45MiAyMS4yIDQ3IDQ3LjI3IDQ3SDI1OS45YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzNNOTUuMzUgMjYzLjc3YTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xOGE3LjQzIDcuNDMgMCAwMDcuMSA1LjI1TTE1Ny41MyAyNjMuNzdhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMjE5LjcyIDI2My43N2E3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNSIvPjwvZz48ZyBmaWxsPSIjZGVjNjBmIj48cGF0aCBkPSJNODUuMDggNy40NXYyMC44OWE3LjQzIDcuNDMgMCAwMDE0Ljg1IDBWNy40NGE3LjQzIDcuNDMgMCAxMC0xNC44NSAwTTYwLjQxIDQ0LjM2YTcuNDMgNy40MyAwIDAwNi40Mi0xMS4xNEw1Ni40IDE1LjEyYTcuNDMgNy40MyAwIDAwLTEyLjg2IDcuNDRsMTAuNDUgMTguMDlhNy40MiA3LjQyIDAgMDA2LjQzIDMuNzFNNDAuNjEgNTRMMjIuNTIgNDMuNTZhNy40MyA3LjQzIDAgMTAtNy40MyAxMi44NmwxOC4xIDEwLjQ1QTcuNDMgNy40MyAwIDAwNDAuNjIgNTRNMzUuNzMgOTIuNTRjMC00LjEtMy4zMi03LjQzLTcuNDItNy40M0g3LjRhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDIwLjljNC4xIDAgNy40Mi0zLjMyIDcuNDItNy40Mk00MC42MSAxMzEuMDdhNy40MyA3LjQzIDAgMTAtNy40Mi0xMi44NmwtMTguMSAxMC40NGE3LjQzIDcuNDMgMCAxMDcuNDMgMTIuODdsMTguMS0xMC40NU0xMjAuOSA0My4zNmE3LjQyIDcuNDIgMCAwMDEwLjE1LTIuNzJsMTAuNDMtMTguMDlhNy40MyA3LjQzIDAgMDAtMTIuODYtNy40MmwtMTAuNDUgMTguMWE3LjQzIDcuNDMgMCAwMDIuNzIgMTAuMTMiLz48L2c+PGcgZmlsbD0iIzYxYzllNyI+PHBhdGggZD0iTTE5NC40MSAyOTAuNTlhNy40MSA3LjQxIDAgMDA3LjEtOS42bC04Ljk0LTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE3YTcuNDMgNy40MyAwIDAwNy4xIDUuMjZNMTMyLjcgMjg5LjM5YTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45NS0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2TTI4MC4xNyAyNjEuNmE3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0yNTQuODYgMjg4LjQxYTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xOGE3LjQzIDcuNDMgMCAwMDcuMSA1LjI1Ii8+PC9nPjwvc3ZnPg==";

/***/ }),

/***/ "./apps/weather_status/img/sun-cloud-light-rain.svg":
/*!**********************************************************!*\
  !*** ./apps/weather_status/img/sun-cloud-light-rain.svg ***!
  \**********************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzA3LjE5IiBoZWlnaHQ9IjI5MS4zMyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzA3IDI5MSI+PHBhdGggZD0iTTU1LjU3IDkyLjU0YzAgOS4yNyAzLjQzIDE4LjAyIDkuNTQgMjQuNzZhNjIuMzYgNjIuMzYgMCAwMTIxLjIyLTEwLjM4IDc1LjcyIDc1LjcyIDAgMDEyNS42MS00NS44IDM2Ljk3IDM2Ljk3IDAgMDAtNTYuMzcgMzEuNCIgZmlsbD0iI2RlYzYwZiIvPjxnIGZpbGw9IiM2MWM5ZTciPjxwYXRoIGQ9Ik0yODguNiAxMjkuM2E3LjQyIDcuNDIgMCAwMS0yLjYzLTMuODVBNTUuNSA1NS41IDAgMDAyMzIuOSA4NS42Yy00LjUgMC05IC41NC0xMy4zNCAxLjYyLTMgLjc1LTYuMTUtLjQ1LTcuOS0zYTYxLjEgNjEuMSAwIDAwLTUwLjMzLTI2LjQ4IDYwLjg5IDYwLjg5IDAgMDAtNDEuMTggMTUuOTggNjAuODggNjAuODggMCAwMC0xOS42IDM5LjE5IDcuNDIgNy40MiAwIDAxLTQuMiA2Yy0uNzMuMzUtMS40Ny43My0yLjIgMS4xMy0uNjIuMzQtMS4yOS42LTEuOTguNzVhNDcuNDggNDcuNDggMCAwMC0yNi4zNSAxNi4zIDQ2LjI5IDQ2LjI5IDAgMDAtMTAuNTEgMjkuNTVjMCAyNS45MiAyMS4yIDQ3IDQ3LjI3IDQ3SDI1OS45YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzNNMTIzLjUyIDI5MS4zM2E3LjQxIDcuNDEgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0xODUuNyAyOTEuMzNhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04LjkzLTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMjQ3Ljg5IDI5MS4zM2E3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNSIvPjwvZz48ZyBmaWxsPSIjZGVjNjBmIj48cGF0aCBkPSJNODUuMDggNy40NXYyMC44OWE3LjQzIDcuNDMgMCAwMDE0Ljg1IDBWNy40NGE3LjQzIDcuNDMgMCAxMC0xNC44NSAwTTYwLjQxIDQ0LjM2YTcuNDMgNy40MyAwIDAwNi40Mi0xMS4xNEw1Ni40IDE1LjEyYTcuNDMgNy40MyAwIDAwLTEyLjg2IDcuNDRsMTAuNDUgMTguMDlhNy40MiA3LjQyIDAgMDA2LjQzIDMuNzFNNDAuNjEgNTRMMjIuNTIgNDMuNTZhNy40MyA3LjQzIDAgMTAtNy40MyAxMi44NmwxOC4xIDEwLjQ1QTcuNDMgNy40MyAwIDAwNDAuNjIgNTRNMzUuNzMgOTIuNTRjMC00LjEtMy4zMi03LjQzLTcuNDItNy40M0g3LjRhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDIwLjljNC4xIDAgNy40Mi0zLjMyIDcuNDItNy40Mk00MC42MSAxMzEuMDdhNy40MyA3LjQzIDAgMTAtNy40Mi0xMi44NmwtMTguMSAxMC40NGE3LjQzIDcuNDMgMCAxMDcuNDMgMTIuODdsMTguMS0xMC40NU0xMjAuOSA0My4zNmE3LjQyIDcuNDIgMCAwMDEwLjE1LTIuNzJsMTAuNDMtMTguMDlhNy40MyA3LjQzIDAgMDAtMTIuODYtNy40MmwtMTAuNDUgMTguMWE3LjQzIDcuNDMgMCAwMDIuNzIgMTAuMTMiLz48L2c+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/sun-cloud-rain.svg":
/*!****************************************************!*\
  !*** ./apps/weather_status/img/sun-cloud-rain.svg ***!
  \****************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzA3LjE5IiBoZWlnaHQ9IjI5MS4zMyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzA3IDI5MSI+PHBhdGggZD0iTTU1LjU3IDkyLjU0YzAgOS4yNyAzLjQzIDE4LjAyIDkuNTQgMjQuNzZhNjIuMzYgNjIuMzYgMCAwMTIxLjIyLTEwLjM4IDc1LjcyIDc1LjcyIDAgMDEyNS42MS00NS44IDM2Ljk3IDM2Ljk3IDAgMDAtNTYuMzcgMzEuNCIgZmlsbD0iI2RlYzYwZiIvPjxnIGZpbGw9IiM2MWM5ZTciPjxwYXRoIGQ9Ik0yODguNiAxMjkuM2E3LjQyIDcuNDIgMCAwMS0yLjYzLTMuODVBNTUuNSA1NS41IDAgMDAyMzIuOSA4NS42Yy00LjUgMC05IC41NC0xMy4zNCAxLjYyLTMgLjc1LTYuMTUtLjQ1LTcuOS0zYTYxLjEgNjEuMSAwIDAwLTUwLjMzLTI2LjQ4IDYwLjg5IDYwLjg5IDAgMDAtNDEuMTggMTUuOTggNjAuODggNjAuODggMCAwMC0xOS42IDM5LjE5IDcuNDIgNy40MiAwIDAxLTQuMiA2Yy0uNzMuMzUtMS40Ny43My0yLjIgMS4xMy0uNjIuMzQtMS4yOS42LTEuOTguNzVhNDcuNDggNDcuNDggMCAwMC0yNi4zNSAxNi4zIDQ2LjI5IDQ2LjI5IDAgMDAtMTAuNTEgMjkuNTVjMCAyNS45MiAyMS4yIDQ3IDQ3LjI3IDQ3SDI1OS45YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNiA0Ni42IDAgMDAtMTguNTYtMzcuMzNNMTE2LjkyIDI2NC45N2E3LjQxIDcuNDEgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNU0xNzkuMSAyNjQuOTdhNy40MyA3LjQzIDAgMDA3LjEtOS42bC04LjkzLTI5LjE4YTcuNDMgNy40MyAwIDAwLTE0LjIgNC4zNWw4Ljk0IDI5LjE4YTcuNDMgNy40MyAwIDAwNy4xIDUuMjVNMjQxLjI5IDI2NC45N2E3LjQzIDcuNDMgMCAwMDcuMS05LjZsLTguOTQtMjkuMThhNy40MyA3LjQzIDAgMTAtMTQuMiA0LjM1bDguOTQgMjkuMThhNy40MyA3LjQzIDAgMDA3LjEgNS4yNSIvPjwvZz48ZyBmaWxsPSIjZGVjNjBmIj48cGF0aCBkPSJNODUuMDggNy40NXYyMC44OWE3LjQzIDcuNDMgMCAwMDE0Ljg1IDBWNy40NGE3LjQzIDcuNDMgMCAxMC0xNC44NSAwTTYwLjQxIDQ0LjM2YTcuNDMgNy40MyAwIDAwNi40Mi0xMS4xNEw1Ni40IDE1LjEyYTcuNDMgNy40MyAwIDAwLTEyLjg2IDcuNDRsMTAuNDUgMTguMDlhNy40MiA3LjQyIDAgMDA2LjQzIDMuNzFNNDAuNjEgNTRMMjIuNTIgNDMuNTZhNy40MyA3LjQzIDAgMTAtNy40MyAxMi44NmwxOC4xIDEwLjQ1QTcuNDMgNy40MyAwIDAwNDAuNjIgNTRNMzUuNzMgOTIuNTRjMC00LjEtMy4zMi03LjQzLTcuNDItNy40M0g3LjRhNy40MyA3LjQzIDAgMDAwIDE0Ljg1aDIwLjljNC4xIDAgNy40Mi0zLjMyIDcuNDItNy40Mk00MC42MSAxMzEuMDdhNy40MyA3LjQzIDAgMTAtNy40Mi0xMi44NmwtMTguMSAxMC40NGE3LjQzIDcuNDMgMCAxMDcuNDMgMTIuODdsMTguMS0xMC40NU0xMjAuOSA0My4zNmE3LjQyIDcuNDIgMCAwMDEwLjE1LTIuNzJsMTAuNDMtMTguMDlhNy40MyA3LjQzIDAgMDAtMTIuODYtNy40MmwtMTAuNDUgMTguMWE3LjQzIDcuNDMgMCAwMDIuNzIgMTAuMTMiLz48L2c+PHBhdGggZD0iTTIxNS45OCAyOTEuNzlhNy40MSA3LjQxIDAgMDA3LjEtOS42TDIxNC4xNSAyNTNhNy40MyA3LjQzIDAgMDAtMTQuMiA0LjM1bDguOTUgMjkuMTdhNy40MyA3LjQzIDAgMDA3LjEgNS4yNk0xNTQuMjcgMjkwLjU5YTcuNDEgNy40MSAwIDAwNy4xLTkuNmwtOC45NC0yOS4xOGE3LjQzIDcuNDMgMCAwMC0xNC4yIDQuMzVsOC45NCAyOS4xN2E3LjQzIDcuNDMgMCAwMDcuMSA1LjI2IiBmaWxsPSIjNjFjOWU3Ii8+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/sun-cloud.svg":
/*!***********************************************!*\
  !*** ./apps/weather_status/img/sun-cloud.svg ***!
  \***********************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzA3LjE5IiBoZWlnaHQ9IjIxMy42MSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMzA3IDIxNCI+PHBhdGggZD0iTTI4OC42IDEyOS4zN2E3LjM4IDcuMzggMCAwMS0yLjYzLTMuODQgNTUuNTEgNTUuNTEgMCAwMC01My4wOC0zOS44N2MtNC41IDAtOSAuNTUtMTMuMzQgMS42My0zIC43NS02LjE1LS40NS03LjktM2E2MS4xIDYxLjEgMCAwMC05MS41MS0xMC41IDYwLjg5IDYwLjg5IDAgMDAtMTkuNiAzOS4yIDcuNDQgNy40NCAwIDAxLTQuMiA2Yy0uNzMuMzQtMS40Ny43MS0yLjIgMS4xMS0uNjIuMzUtMS4yOS42LTEuOTguNzVhNDcuNTQgNDcuNTQgMCAwMC0yNi4zNSAxNi4zIDQ2LjMzIDQ2LjMzIDAgMDAtMTAuNTEgMjkuNTZjMCAyNS45MiAyMS4yIDQ3IDQ3LjI3IDQ3SDI1OS45YzI2LjA2IDAgNDcuMjYtMjEuMDggNDcuMjYtNDdhNDYuNjMgNDYuNjMgMCAwMC0xOC41Ni0zNy4zNCIgZmlsbD0iIzYxYzllNyIvPjxnIGZpbGw9IiNkZWM2MGYiPjxwYXRoIGQ9Ik01NS41NyA5Mi41N2EzNi43IDM2LjcgMCAwMDkuNTQgMjQuNzYgNjIuMzYgNjIuMzYgMCAwMTIxLjIyLTEwLjM5IDc1LjY4IDc1LjY4IDAgMDEyNS42MS00NS43OCAzNi45NyAzNi45NyAwIDAwLTU2LjM3IDMxLjQxTTkyLjUgMzUuNzdjNC4xIDAgNy40My0zLjMzIDcuNDMtNy40M1Y3LjQ1YTcuNDIgNy40MiAwIDEwLTE0Ljg1IDB2MjAuOWMwIDQuMDkgMy4zMyA3LjQyIDcuNDMgNy40Mk01My45NyA0MC43YTcuNDIgNy40MiAwIDEwMTIuODctNy40MmwtMTAuNDUtMTguMWE3LjQzIDcuNDMgMCAwMC0xMi44NiA3LjQzbDEwLjQ0IDE4LjFNMTUuMSA1Ni40NGwxOC4wOSAxMC40NWE3LjQ3IDcuNDcgMCAwMDEwLjE0LTIuNzIgNy40MyA3LjQzIDAgMDAtMi43MS0xMC4xNWwtMTguMS0xMC40NWE3LjQzIDcuNDMgMCAwMC03LjQzIDEyLjg3TTcuNDIgMTAwLjA0SDI4LjNhNy40MyA3LjQzIDAgMDAwLTE0Ljg2SDcuNDFhNy40MiA3LjQyIDAgMTAwIDE0Ljg2TTQzLjMzIDEyMC45N2E3LjQyIDcuNDIgMCAwMC0xMC4xNC0yLjcybC0xOC4xIDEwLjQ0YTcuNDMgNy40MyAwIDAwNy40MyAxMi44NmwxOC4xLTEwLjQ0YTcuNDMgNy40MyAwIDAwMi43MS0xMC4xNE0xMjAuOSA0My4zN2E3LjQyIDcuNDIgMCAwMDEwLjE1LTIuNzJsMTAuNDMtMTguMWE3LjQzIDcuNDMgMCAwMC0xMi44Ni03LjQybC0xMC40NSAxOC4xYTcuNDMgNy40MyAwIDAwMi43MiAxMC4xNCIvPjwvZz48L3N2Zz4=";

/***/ }),

/***/ "./apps/weather_status/img/sun-small-cloud.svg":
/*!*****************************************************!*\
  !*** ./apps/weather_status/img/sun-small-cloud.svg ***!
  \*****************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwLjUiIGhlaWdodD0iMjgwLjUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDI4MCAyODAiPjxwYXRoIGQ9Ik0yNTcgMjE5Ljc0YTQuMyA0LjMgMCAwMS0xLjUzLTIuMjQgMzIuMzggMzIuMzggMCAwMC0zMC45Ni0yMy4yNmMtMi42MyAwLTUuMjUuMzItNy43OC45NWE0LjMzIDQuMzMgMCAwMS00LjYxLTEuNzUgMzUuNjMgMzUuNjMgMCAwMC01My4zOC02LjEzIDM1LjUyIDM1LjUyIDAgMDAtMTEuNDMgMjIuODcgNC4zNCA0LjM0IDAgMDEtMi40NSAzLjVjLS40Mi4yLS44Ni40Mi0xLjI4LjY1LS4zNi4yLS43NS4zNS0xLjE1LjQ0YTI3LjczIDI3LjczIDAgMDAtMTUuMzcgOS41IDI3LjAzIDI3LjAzIDAgMDAtNi4xMyAxNy4yNSAyNy41MiAyNy41MiAwIDAwMjcuNTYgMjcuNGg5MS43N2MxNS4yIDAgMjcuNTctMTIuMjkgMjcuNTctMjcuNGEyNy4yIDI3LjIgMCAwMC0xMC44Mi0yMS43OCIgZmlsbD0iIzYxYzllNyIgcGFpbnQtb3JkZXI9InN0cm9rZSBmaWxsIG1hcmtlcnMiLz48ZyBmaWxsPSIjZGVjNjBmIj48cGF0aCBkPSJNMTQwLjIgNzAuNzNBNjkuODYgNjkuODYgMCAwMDcwLjQgMTQwLjVjMCAzMy4zMyAyMy41IDYxLjI3IDU0LjggNjguMTRhNDAuNiA0MC42IDAgMDExMC4wMy01LjEgNDguNjEgNDguNjEgMCAwMTE0LjctMjUuNThoLjAxYTQ4LjU5IDQ4LjU5IDAgMDE1My41LTguMDUgNjkuMzUgNjkuMzUgMCAwMDYuNTItMjkuNDEgNjkuODYgNjkuODYgMCAwMC02OS43OC02OS43OHptLS45IDEzMS40NGwtLjEuMDQtLjA0LjAzLjEzLS4wN3pNMTMyLjggMzguOWE3LjQzIDcuNDMgMCAwMDE0Ljg1IDBWNy40NGE3LjQzIDcuNDMgMCAwMC0xNC44NSAwVjM4LjlNODkuNTQgNTkuOTFhNy40MyA3LjQzIDAgMDA2LjQzLTExLjE0TDgwLjI0IDIxLjUzYTcuNDMgNy40MyAwIDAwLTEyLjg2IDcuNDNMODMuMSA1Ni4yYTcuNDIgNy40MiAwIDAwNi40MyAzLjcxTTE4Ljc2IDcwLjE0YTcuNDMgNy40MyAwIDAwMi43MiAxMC4xNUw0OC43MiA5NmE3LjQyIDcuNDIgMCAxMDcuNDMtMTIuODZMMjguOSA2Ny40MmE3LjQzIDcuNDMgMCAwMC0xMC4xNCAyLjcyTTQ2LjI4IDE0MC4yN2MwLTQuMS0zLjMzLTcuNDItNy40My03LjQySDcuNGE3LjQzIDcuNDMgMCAwMDAgMTQuODVoMzEuNDZjNC4xIDAgNy40My0zLjMzIDcuNDMtNy40M00yNzMuMDUgMTMyLjg1aC0zMS40NmE3LjQzIDcuNDMgMCAwMDAgMTQuODVoMzEuNDZhNy40MyA3LjQzIDAgMDAwLTE0Ljg1TTQ4LjczIDE4NC41MUwyMS41IDIwMC4yNGE3LjQzIDcuNDMgMCAxMDcuNDIgMTIuODZsMjcuMjUtMTUuNzNhNy40MyA3LjQzIDAgMDAtNy40My0xMi44Nk0yNTEuNTQgNjcuNDJMMjI0LjMgODMuMTVBNy40MyA3LjQzIDAgMDAyMzEuNzIgOTZsMjcuMjQtMTUuNzNhNy40MyA3LjQzIDAgMDAtNy40Mi0xMi44Nk04My4xIDIyNC4zNGwtMTUuNzMgMjcuMjRhNy40MyA3LjQzIDAgMDAxMi44NyA3LjQzbDE1LjczLTI3LjI1YTcuNDMgNy40MyAwIDAwLTEyLjg3LTcuNDJNMTg3LjIgNTguOTFhNy40IDcuNCAwIDAwMTAuMTQtMi43MWwxNS43My0yNy4yNWE3LjQzIDcuNDMgMCAxMC0xMi44Ni03LjQybC0xNS43MyAyNy4yNGE3LjQzIDcuNDMgMCAwMDIuNzEgMTAuMTQiLz48L2c+PC9zdmc+";

/***/ }),

/***/ "./apps/weather_status/img/sun.svg":
/*!*****************************************!*\
  !*** ./apps/weather_status/img/sun.svg ***!
  \*****************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwLjUiIGhlaWdodD0iMjgwLjUiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMjgwIDI4MCI+PGcgZmlsbD0iI2RlYzYwZiI+PHBhdGggZD0iTTE0MC4yMiAyMTAuMDRjMzguNDggMCA2OS43OC0zMS4zIDY5Ljc4LTY5Ljc4cy0zMS4zLTY5Ljc4LTY5Ljc4LTY5Ljc4Yy0zOC40NyAwLTY5Ljc4IDMxLjMtNjkuNzggNjkuNzhzMzEuMyA2OS43OCA2OS43OCA2OS43OE0xMzIuOCAzOC45YTcuNDMgNy40MyAwIDAwMTQuODUgMFY3LjQ0YTcuNDMgNy40MyAwIDAwLTE0Ljg1IDBWMzguOU0xMzIuOCAyNDEuNjN2MzEuNDZhNy40MyA3LjQzIDAgMDAxNC44NSAwdi0zMS40NmE3LjQzIDcuNDMgMCAwMC0xNC44NSAwTTg5LjU0IDU5LjkxYTcuNDMgNy40MyAwIDAwNi40My0xMS4xNEw4MC4yNCAyMS41M2E3LjQzIDcuNDMgMCAwMC0xMi44NiA3LjQzTDgzLjEgNTYuMmE3LjQyIDcuNDIgMCAwMDYuNDMgMy43MU0xODcuMiAyMjEuNjJhNy40MyA3LjQzIDAgMDAtMi43MiAxMC4xNEwyMDAuMiAyNTlhNy40MiA3LjQyIDAgMTAxMi44Ni03LjQybC0xNS43My0yNy4yNWE3LjQzIDcuNDMgMCAwMC0xMC4xNS0yLjcxTTE4Ljc2IDcwLjE0YTcuNDMgNy40MyAwIDAwMi43MiAxMC4xNUw0OC43MiA5NmE3LjQyIDcuNDIgMCAxMDcuNDMtMTIuODZMMjguOSA2Ny40MmE3LjQzIDcuNDMgMCAwMC0xMC4xNCAyLjcyTTI1OC45NyAyMDAuMjRsLTI3LjI1LTE1LjczYTcuNDMgNy40MyAwIDAwLTcuNDIgMTIuODdsMjcuMjQgMTUuNzNhNy40IDcuNCAwIDAwMTAuMTQtMi43MiA3LjQzIDcuNDMgMCAwMC0yLjcxLTEwLjE1TTQ2LjI4IDE0MC4yN2MwLTQuMS0zLjMzLTcuNDItNy40My03LjQySDcuNGE3LjQzIDcuNDMgMCAwMDAgMTQuODVoMzEuNDZjNC4xIDAgNy40My0zLjMzIDcuNDMtNy40M00yNzMuMDUgMTMyLjg1aC0zMS40NmE3LjQzIDcuNDMgMCAwMDAgMTQuODVoMzEuNDZhNy40MyA3LjQzIDAgMDAwLTE0Ljg1TTQ4LjczIDE4NC41MUwyMS41IDIwMC4yNGE3LjQzIDcuNDMgMCAxMDcuNDIgMTIuODZsMjcuMjUtMTUuNzNhNy40MyA3LjQzIDAgMDAtNy40My0xMi44Nk0yNTEuNTQgNjcuNDJMMjI0LjMgODMuMTVBNy40MyA3LjQzIDAgMDAyMzEuNzIgOTZsMjcuMjQtMTUuNzNhNy40MyA3LjQzIDAgMDAtNy40Mi0xMi44Nk04My4xIDIyNC4zNGwtMTUuNzMgMjcuMjRhNy40MyA3LjQzIDAgMDAxMi44NyA3LjQzbDE1LjczLTI3LjI1YTcuNDMgNy40MyAwIDAwLTEyLjg3LTcuNDJNMTg3LjIgNTguOTFhNy40IDcuNCAwIDAwMTAuMTQtMi43MWwxNS43My0yNy4yNWE3LjQzIDcuNDMgMCAxMC0xMi44Ni03LjQybC0xNS43MyAyNy4yNGE3LjQzIDcuNDMgMCAwMDIuNzEgMTAuMTQiIGZpbGw9IiNkZWM2MGYiLz48L2c+PC9zdmc+Cg==";

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
/******/ 			"weather_status-weather-status": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/weather_status/src/weather-status.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=weather_status-weather-status.js.map?v=f50d986d2eba83649409