/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./core/src/OC/admin.js":
/*!******************************!*\
  !*** ./core/src/OC/admin.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "isUserAdmin": function() { return /* binding */ isUserAdmin; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var isAdmin = !!window._oc_isadmin;

/**
 * Returns whether the current user is an administrator
 *
 * @return {boolean} true if the user is an admin, false otherwise
 * @since 9.0.0
 */
var isUserAdmin = function isUserAdmin() {
  return isAdmin;
};

/***/ }),

/***/ "./core/src/OC/appconfig.js":
/*!**********************************!*\
  !*** ./core/src/OC/appconfig.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "AppConfig": function() { return /* binding */ AppConfig; },
/* harmony export */   "appConfig": function() { return /* binding */ appConfig; }
/* harmony export */ });
/* harmony import */ var _OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../OCP/appconfig */ "./core/src/OCP/appconfig.js");
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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

/* eslint-disable */

var appConfig = window.oc_appconfig || {};

/**
 * @namespace
 * @deprecated 16.0.0 Use OCP.AppConfig instead
 */
var AppConfig = {
  /**
   * @deprecated Use OCP.AppConfig.getValue() instead
   */
  getValue: function getValue(app, key, defaultValue, callback) {
    (0,_OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__.getValue)(app, key, defaultValue, {
      success: callback
    });
  },
  /**
   * @deprecated Use OCP.AppConfig.setValue() instead
   */
  setValue: function setValue(app, key, value) {
    (0,_OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__.setValue)(app, key, value);
  },
  /**
   * @deprecated Use OCP.AppConfig.getApps() instead
   */
  getApps: function getApps(callback) {
    (0,_OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__.getApps)({
      success: callback
    });
  },
  /**
   * @deprecated Use OCP.AppConfig.getKeys() instead
   */
  getKeys: function getKeys(app, callback) {
    (0,_OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__.getKeys)(app, {
      success: callback
    });
  },
  /**
   * @deprecated Use OCP.AppConfig.deleteKey() instead
   */
  deleteKey: function deleteKey(app, key) {
    (0,_OCP_appconfig__WEBPACK_IMPORTED_MODULE_0__.deleteKey)(app, key);
  }
};

/***/ }),

/***/ "./core/src/OC/apps.js":
/*!*****************************!*\
  !*** ./core/src/OC/apps.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "registerAppsSlideToggle": function() { return /* binding */ registerAppsSlideToggle; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright Bernhard Posselt 2014
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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


var dynamicSlideToggleEnabled = false;
var Apps = {
  enableDynamicSlideToggle: function enableDynamicSlideToggle() {
    dynamicSlideToggleEnabled = true;
  }
};

/**
 * Shows the #app-sidebar and add .with-app-sidebar to subsequent siblings
 *
 * @param {object} [$el] sidebar element to show, defaults to $('#app-sidebar')
 */
Apps.showAppSidebar = function ($el) {
  var $appSidebar = $el || jquery__WEBPACK_IMPORTED_MODULE_0___default()('#app-sidebar');
  $appSidebar.removeClass('disappear').show();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#app-content').trigger(new (jquery__WEBPACK_IMPORTED_MODULE_0___default().Event)('appresized'));
};

/**
 * Shows the #app-sidebar and removes .with-app-sidebar from subsequent
 * siblings
 *
 * @param {object} [$el] sidebar element to hide, defaults to $('#app-sidebar')
 */
Apps.hideAppSidebar = function ($el) {
  var $appSidebar = $el || jquery__WEBPACK_IMPORTED_MODULE_0___default()('#app-sidebar');
  $appSidebar.hide().addClass('disappear');
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#app-content').trigger(new (jquery__WEBPACK_IMPORTED_MODULE_0___default().Event)('appresized'));
};

/**
 * Provides a way to slide down a target area through a button and slide it
 * up if the user clicks somewhere else. Used for the news app settings and
 * add new field.
 *
 * Usage:
 * <button data-apps-slide-toggle=".slide-area">slide</button>
 * <div class=".slide-area" class="hidden">I'm sliding up</div>
 */
var registerAppsSlideToggle = function registerAppsSlideToggle() {
  var buttons = jquery__WEBPACK_IMPORTED_MODULE_0___default()('[data-apps-slide-toggle]');
  if (buttons.length === 0) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()('#app-navigation').addClass('without-app-settings');
  }
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).click(function (event) {
    if (dynamicSlideToggleEnabled) {
      buttons = jquery__WEBPACK_IMPORTED_MODULE_0___default()('[data-apps-slide-toggle]');
    }
    buttons.each(function (index, button) {
      var areaSelector = jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).data('apps-slide-toggle');
      var area = jquery__WEBPACK_IMPORTED_MODULE_0___default()(areaSelector);

      /**
       *
       */
      function hideArea() {
        area.slideUp(OC.menuSpeed * 4, function () {
          area.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_0___default().Event)('hide'));
        });
        area.removeClass('opened');
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).removeClass('opened');
      }

      /**
       *
       */
      function showArea() {
        area.slideDown(OC.menuSpeed * 4, function () {
          area.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_0___default().Event)('show'));
        });
        area.addClass('opened');
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).addClass('opened');
        var input = jquery__WEBPACK_IMPORTED_MODULE_0___default()(areaSelector + ' [autofocus]');
        if (input.length === 1) {
          input.focus();
        }
      }

      // do nothing if the area is animated
      if (!area.is(':animated')) {
        // button toggles the area
        if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).is(jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).closest('[data-apps-slide-toggle]'))) {
          if (area.is(':visible')) {
            hideArea();
          } else {
            showArea();
          }

          // all other areas that have not been clicked but are open
          // should be slid up
        } else {
          var closest = jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).closest(areaSelector);
          if (area.is(':visible') && closest[0] !== area[0]) {
            hideArea();
          }
        }
      }
    });
  });
};
/* harmony default export */ __webpack_exports__["default"] = (Apps);

/***/ }),

/***/ "./core/src/OC/appsettings.js":
/*!************************************!*\
  !*** ./core/src/OC/appsettings.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "appSettings": function() { return /* binding */ appSettings; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _routing__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./routing */ "./core/src/OC/routing.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

/* eslint-disable */




/**
 * Opens a popup with the setting for an app.
 * @param {string} appid The ID of the app e.g. 'calendar', 'contacts' or 'files'.
 * @param {boolean|string} loadJS If true 'js/settings.js' is loaded. If it's a string
 * it will attempt to load a script by that name in the 'js' directory.
 * @param {boolean} [cache] If true the javascript file won't be forced refreshed. Defaults to true.
 * @param {string} [scriptName] The name of the PHP file to load. Defaults to 'settings.php' in
 * the root of the app directory hierarchy.
 *
 * @deprecated 17.0.0 this method is unused and will be removed with Nextcloud 18
 */
var appSettings = function appSettings(args) {
  console.warn('OC.appSettings is deprecated and will be removed with Nextcloud 18');
  if (typeof args === 'undefined' || typeof args.appid === 'undefined') {
    throw {
      name: 'MissingParameter',
      message: 'The parameter appid is missing'
    };
  }
  var props = {
    scriptName: 'settings.php',
    cache: true
  };
  jquery__WEBPACK_IMPORTED_MODULE_0___default().extend(props, args);
  var settings = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#appsettings');
  if (settings.length === 0) {
    throw {
      name: 'MissingDOMElement',
      message: 'There has be be an element with id "appsettings" for the popup to show.'
    };
  }
  var popup = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#appsettings_popup');
  if (popup.length === 0) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()('body').prepend('<div class="popup hidden" id="appsettings_popup"></div>');
    popup = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#appsettings_popup');
    popup.addClass(settings.hasClass('topright') ? 'topright' : 'bottomleft');
  }
  if (popup.is(':visible')) {
    popup.hide().remove();
  } else {
    var arrowclass = settings.hasClass('topright') ? 'up' : 'left';
    jquery__WEBPACK_IMPORTED_MODULE_0___default().get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateFilePath)(props.appid, '', props.scriptName), function (data) {
      popup.html(data).ready(function () {
        popup.prepend('<span class="arrow ' + arrowclass + '"></span><h2>' + t('core', 'Settings') + '</h2><a class="close"></a>').show();
        popup.find('.close').bind('click', function () {
          popup.remove();
        });
        if (typeof props.loadJS !== 'undefined') {
          var scriptname;
          if (props.loadJS === true) {
            scriptname = 'settings.js';
          } else if (typeof props.loadJS === 'string') {
            scriptname = props.loadJS;
          } else {
            throw {
              name: 'InvalidParameter',
              message: 'The "loadJS" parameter must be either boolean or a string.'
            };
          }
          if (props.cache) {
            jquery__WEBPACK_IMPORTED_MODULE_0___default().ajaxSetup({
              cache: true
            });
          }
          jquery__WEBPACK_IMPORTED_MODULE_0___default().getScript((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateFilePath)(props.appid, 'js', scriptname)).fail(function (jqxhr, settings, e) {
            throw e;
          });
        }
      }).show();
    }, 'html');
  }
};

/***/ }),

/***/ "./core/src/OC/appswebroots.js":
/*!*************************************!*\
  !*** ./core/src/OC/appswebroots.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var appswebroots = window._oc_appswebroots !== undefined ? window._oc_appswebroots : false;
/* harmony default export */ __webpack_exports__["default"] = (appswebroots);

/***/ }),

/***/ "./core/src/OC/backbone-webdav.js":
/*!****************************************!*\
  !*** ./core/src/OC/backbone-webdav.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "davCall": function() { return /* binding */ davCall; },
/* harmony export */   "davSync": function() { return /* binding */ davSync; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var davclient_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! davclient.js */ "./node_modules/davclient.js/lib/client.js");
/* harmony import */ var davclient_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(davclient_js__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Copyright (c) 2015
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

/* eslint-disable */


var methodMap = {
  create: 'POST',
  update: 'PROPPATCH',
  patch: 'PROPPATCH',
  delete: 'DELETE',
  read: 'PROPFIND'
};

// Throw an error when a URL is needed, and none is supplied.
function urlError() {
  throw new Error('A "url" property or function must be specified');
}

/**
 * Convert a single propfind result to JSON
 *
 * @param {Object} result
 * @param {Object} davProperties properties mapping
 */
function parsePropFindResult(result, davProperties) {
  if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isArray(result)) {
    return underscore__WEBPACK_IMPORTED_MODULE_0__["default"].map(result, function (subResult) {
      return parsePropFindResult(subResult, davProperties);
    });
  }
  var props = {
    href: result.href
  };
  underscore__WEBPACK_IMPORTED_MODULE_0__["default"].each(result.propStat, function (propStat) {
    if (propStat.status !== 'HTTP/1.1 200 OK') {
      return;
    }
    for (var key in propStat.properties) {
      var propKey = key;
      if (key in davProperties) {
        propKey = davProperties[key];
      }
      props[propKey] = propStat.properties[key];
    }
  });
  if (!props.id) {
    // parse id from href
    props.id = parseIdFromLocation(props.href);
  }
  return props;
}

/**
 * Parse ID from location
 *
 * @param {string} url url
 * @returns {string} id
 */
function parseIdFromLocation(url) {
  var queryPos = url.indexOf('?');
  if (queryPos > 0) {
    url = url.substr(0, queryPos);
  }
  var parts = url.split('/');
  var result;
  do {
    result = parts[parts.length - 1];
    parts.pop();
    // note: first result can be empty when there is a trailing slash,
    // so we take the part before that
  } while (!result && parts.length > 0);
  return result;
}
function isSuccessStatus(status) {
  return status >= 200 && status <= 299;
}
function convertModelAttributesToDavProperties(attrs, davProperties) {
  var props = {};
  var key;
  for (key in attrs) {
    var changedProp = davProperties[key];
    var value = attrs[key];
    if (!changedProp) {
      console.warn('No matching DAV property for property "' + key);
      changedProp = key;
    }
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isBoolean(value) || underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isNumber(value)) {
      // convert to string
      value = '' + value;
    }
    props[changedProp] = value;
  }
  return props;
}
function callPropFind(client, options, model, headers) {
  return client.propFind(options.url, underscore__WEBPACK_IMPORTED_MODULE_0__["default"].values(options.davProperties) || [], options.depth, headers).then(function (response) {
    if (isSuccessStatus(response.status)) {
      if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.success)) {
        var propsMapping = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].invert(options.davProperties);
        var results = parsePropFindResult(response.body, propsMapping);
        if (options.depth > 0) {
          // discard root entry
          results.shift();
        }
        options.success(results);
      }
    } else if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.error)) {
      options.error(response);
    }
  });
}
function callPropPatch(client, options, model, headers) {
  return client.propPatch(options.url, convertModelAttributesToDavProperties(model.changed, options.davProperties), headers).then(function (result) {
    if (isSuccessStatus(result.status)) {
      if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.success)) {
        // pass the object's own values because the server
        // does not return the updated model
        options.success(model.toJSON());
      }
    } else if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.error)) {
      options.error(result);
    }
  });
}
function callMkCol(client, options, model, headers) {
  // call MKCOL without data, followed by PROPPATCH
  return client.request(options.type, options.url, headers, null).then(function (result) {
    if (!isSuccessStatus(result.status)) {
      if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.error)) {
        options.error(result);
      }
      return;
    }
    callPropPatch(client, options, model, headers);
  });
}
function callMethod(client, options, model, headers) {
  headers['Content-Type'] = 'application/json';
  return client.request(options.type, options.url, headers, options.data).then(function (result) {
    if (!isSuccessStatus(result.status)) {
      if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.error)) {
        options.error(result);
      }
      return;
    }
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(options.success)) {
      if (options.type === 'PUT' || options.type === 'POST' || options.type === 'MKCOL') {
        // pass the object's own values because the server
        // does not return anything
        var responseJson = result.body || model.toJSON();
        var locationHeader = result.xhr.getResponseHeader('Content-Location');
        if (options.type === 'POST' && locationHeader) {
          responseJson.id = parseIdFromLocation(locationHeader);
        }
        options.success(responseJson);
        return;
      }
      // if multi-status, parse
      if (result.status === 207) {
        var propsMapping = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].invert(options.davProperties);
        options.success(parsePropFindResult(result.body, propsMapping));
      } else {
        options.success(result.body);
      }
    }
  });
}
var davCall = function davCall(options, model) {
  var client = new davclient_js__WEBPACK_IMPORTED_MODULE_1__.dav.Client({
    baseUrl: options.url,
    xmlNamespaces: underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
      'DAV:': 'd',
      'http://owncloud.org/ns': 'oc'
    }, options.xmlNamespaces || {})
  });
  client.resolveUrl = function () {
    return options.url;
  };
  var headers = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
    'X-Requested-With': 'XMLHttpRequest',
    'requesttoken': OC.requestToken
  }, options.headers);
  if (options.type === 'PROPFIND') {
    return callPropFind(client, options, model, headers);
  } else if (options.type === 'PROPPATCH') {
    return callPropPatch(client, options, model, headers);
  } else if (options.type === 'MKCOL') {
    return callMkCol(client, options, model, headers);
  } else {
    return callMethod(client, options, model, headers);
  }
};

/**
 * DAV transport
 */
var davSync = function davSync(Backbone) {
  return function (method, model, options) {
    var params = {
      type: methodMap[method] || method
    };
    var isCollection = model instanceof Backbone.Collection;
    if (method === 'update') {
      // if a model has an inner collection, it must define an
      // attribute "hasInnerCollection" that evaluates to true
      if (model.hasInnerCollection) {
        // if the model itself is a Webdav collection, use MKCOL
        params.type = 'MKCOL';
      } else if (model.usePUT || model.collection && model.collection.usePUT) {
        // use PUT instead of PROPPATCH
        params.type = 'PUT';
      }
    }

    // Ensure that we have a URL.
    if (!options.url) {
      params.url = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].result(model, 'url') || urlError();
    }

    // Ensure that we have the appropriate request data.
    if (options.data == null && model && (method === 'create' || method === 'update' || method === 'patch')) {
      params.data = JSON.stringify(options.attrs || model.toJSON(options));
    }

    // Don't process data on a non-GET request.
    if (params.type !== 'PROPFIND') {
      params.processData = false;
    }
    if (params.type === 'PROPFIND' || params.type === 'PROPPATCH') {
      var davProperties = model.davProperties;
      if (!davProperties && model.model) {
        // use dav properties from model in case of collection
        davProperties = model.model.prototype.davProperties;
      }
      if (davProperties) {
        if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(davProperties)) {
          params.davProperties = davProperties.call(model);
        } else {
          params.davProperties = davProperties;
        }
      }
      params.davProperties = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend(params.davProperties || {}, options.davProperties);
      if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isUndefined(options.depth)) {
        if (isCollection) {
          options.depth = 1;
        } else {
          options.depth = 0;
        }
      }
    }

    // Pass along `textStatus` and `errorThrown` from jQuery.
    var error = options.error;
    options.error = function (xhr, textStatus, errorThrown) {
      options.textStatus = textStatus;
      options.errorThrown = errorThrown;
      if (error) {
        error.call(options.context, xhr, textStatus, errorThrown);
      }
    };

    // Make the request, allowing the user to override any Ajax options.
    var xhr = options.xhr = Backbone.davCall(underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend(params, options), model);
    model.trigger('request', model, xhr, options);
    return xhr;
  };
};

/***/ }),

/***/ "./core/src/OC/backbone.js":
/*!*********************************!*\
  !*** ./core/src/OC/backbone.js ***!
  \*********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! backbone */ "./node_modules/backbone/backbone.js");
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(backbone__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _backbone_webdav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./backbone-webdav */ "./core/src/OC/backbone-webdav.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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



var Backbone = backbone__WEBPACK_IMPORTED_MODULE_0___default().noConflict();

// Patch Backbone for DAV
Object.assign(Backbone, {
  davCall: _backbone_webdav__WEBPACK_IMPORTED_MODULE_1__.davCall,
  davSync: (0,_backbone_webdav__WEBPACK_IMPORTED_MODULE_1__.davSync)(Backbone)
});
/* harmony default export */ __webpack_exports__["default"] = (Backbone);

/***/ }),

/***/ "./core/src/OC/capabilities.js":
/*!*************************************!*\
  !*** ./core/src/OC/capabilities.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getCapabilities": function() { return /* binding */ getCapabilities; }
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * Returns the capabilities
 *
 * @return {Array} capabilities
 *
 * @since 14.0.0
 */
var getCapabilities = function getCapabilities() {
  console.warn('OC.getCapabilities is deprecated and will be removed in Nextcloud 21. See @nextcloud/capabilities');
  return (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)();
};

/***/ }),

/***/ "./core/src/OC/config.js":
/*!*******************************!*\
  !*** ./core/src/OC/config.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var config = window._oc_config || {};
/* harmony default export */ __webpack_exports__["default"] = (config);

/***/ }),

/***/ "./core/src/OC/constants.js":
/*!**********************************!*\
  !*** ./core/src/OC/constants.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "PERMISSION_ALL": function() { return /* binding */ PERMISSION_ALL; },
/* harmony export */   "PERMISSION_CREATE": function() { return /* binding */ PERMISSION_CREATE; },
/* harmony export */   "PERMISSION_DELETE": function() { return /* binding */ PERMISSION_DELETE; },
/* harmony export */   "PERMISSION_NONE": function() { return /* binding */ PERMISSION_NONE; },
/* harmony export */   "PERMISSION_READ": function() { return /* binding */ PERMISSION_READ; },
/* harmony export */   "PERMISSION_SHARE": function() { return /* binding */ PERMISSION_SHARE; },
/* harmony export */   "PERMISSION_UPDATE": function() { return /* binding */ PERMISSION_UPDATE; },
/* harmony export */   "TAG_FAVORITE": function() { return /* binding */ TAG_FAVORITE; },
/* harmony export */   "coreApps": function() { return /* binding */ coreApps; },
/* harmony export */   "menuSpeed": function() { return /* binding */ menuSpeed; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var coreApps = ['', 'admin', 'log', 'core/search', 'core', '3rdparty'];
var menuSpeed = 50;
var PERMISSION_NONE = 0;
var PERMISSION_CREATE = 4;
var PERMISSION_READ = 1;
var PERMISSION_UPDATE = 2;
var PERMISSION_DELETE = 8;
var PERMISSION_SHARE = 16;
var PERMISSION_ALL = 31;
var TAG_FAVORITE = '_$!<Favorite>!$_';

/***/ }),

/***/ "./core/src/OC/contactsmenu.js":
/*!*************************************!*\
  !*** ./core/src/OC/contactsmenu.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! backbone */ "./node_modules/backbone/backbone.js");
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(backbone__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

/* eslint-disable */





/**
 * @class Contact
 */
var Contact = backbone__WEBPACK_IMPORTED_MODULE_2__.Model.extend({
  defaults: {
    fullName: '',
    lastMessage: '',
    actions: [],
    hasOneAction: false,
    hasTwoActions: false,
    hasManyActions: false
  },
  /**
   * @returns {undefined}
   */
  initialize: function initialize() {
    // Add needed property for easier template rendering
    if (this.get('actions').length === 0) {
      this.set('hasOneAction', true);
    } else if (this.get('actions').length === 1) {
      this.set('hasTwoActions', true);
      this.set('secondAction', this.get('actions')[0]);
    } else {
      this.set('hasManyActions', true);
    }
  }
});

/**
 * @class ContactCollection
 * @private
 */
var ContactCollection = backbone__WEBPACK_IMPORTED_MODULE_2__.Collection.extend({
  model: Contact
});

/**
 * @class ContactsListView
 * @private
 */
var ContactsListView = backbone__WEBPACK_IMPORTED_MODULE_2__.View.extend({
  /** @type {ContactCollection} */
  _collection: undefined,
  /** @type {array} */
  _subViews: [],
  /**
   * @param {object} options
   * @returns {undefined}
   */
  initialize: function initialize(options) {
    this._collection = options.collection;
  },
  /**
   * @returns {self}
   */
  render: function render() {
    var self = this;
    self.$el.html('');
    self._subViews = [];
    self._collection.forEach(function (contact) {
      var item = new ContactsListItemView({
        model: contact
      });
      item.render();
      self.$el.append(item.$el);
      item.on('toggle:actionmenu', self._onChildActionMenuToggle, self);
      self._subViews.push(item);
    });
    return self;
  },
  /**
   * Event callback to propagate opening (another) entry's action menu
   *
   * @param {type} $src
   * @returns {undefined}
   */
  _onChildActionMenuToggle: function _onChildActionMenuToggle($src) {
    this._subViews.forEach(function (view) {
      view.trigger('parent:toggle:actionmenu', $src);
    });
  }
});

/**
 * @class ContactsListItemView
 * @private
 */
var ContactsListItemView = backbone__WEBPACK_IMPORTED_MODULE_2__.View.extend({
  /** @type {string} */
  className: 'contact',
  /** @type {undefined|function} */
  _template: undefined,
  /** @type {Contact} */
  _model: undefined,
  /** @type {boolean} */
  _actionMenuShown: false,
  events: {
    'click .icon-more': '_onToggleActionsMenu'
  },
  contactTemplate: __webpack_require__(/*! ./contactsmenu/contact.handlebars */ "./core/src/OC/contactsmenu/contact.handlebars"),
  /**
   * @param {object} data
   * @returns {undefined}
   */
  template: function template(data) {
    return this.contactTemplate(data);
  },
  /**
   * @param {object} options
   * @returns {undefined}
   */
  initialize: function initialize(options) {
    this._model = options.model;
    this.on('parent:toggle:actionmenu', this._onOtherActionMenuOpened, this);
  },
  /**
   * @returns {self}
   */
  render: function render() {
    this.$el.html(this.template({
      contact: this._model.toJSON()
    }));
    this.delegateEvents();

    // Show placeholder if no avatar is available (avatar is rendered as img, not div)
    this.$('div.avatar').imageplaceholder(this._model.get('fullName'));

    // Show tooltip for top action
    this.$('.top-action').tooltip({
      placement: 'left'
    });
    // Show tooltip for second action
    this.$('.second-action').tooltip({
      placement: 'left'
    });
    return this;
  },
  /**
   * Toggle the visibility of the action popover menu
   *
   * @private
   * @returns {undefined}
   */
  _onToggleActionsMenu: function _onToggleActionsMenu() {
    this._actionMenuShown = !this._actionMenuShown;
    if (this._actionMenuShown) {
      this.$('.menu').show();
    } else {
      this.$('.menu').hide();
    }
    this.trigger('toggle:actionmenu', this.$el);
  },
  /**
   * @private
   * @argument {jQuery} $src
   * @returns {undefined}
   */
  _onOtherActionMenuOpened: function _onOtherActionMenuOpened($src) {
    if (this.$el.is($src)) {
      // Ignore
      return;
    }
    this._actionMenuShown = false;
    this.$('.menu').hide();
  }
});

/**
 * @class ContactsMenuView
 * @private
 */
var ContactsMenuView = backbone__WEBPACK_IMPORTED_MODULE_2__.View.extend({
  /** @type {undefined|function} */
  _loadingTemplate: undefined,
  /** @type {undefined|function} */
  _errorTemplate: undefined,
  /** @type {undefined|function} */
  _contentTemplate: undefined,
  /** @type {undefined|function} */
  _contactsTemplate: undefined,
  /** @type {undefined|ContactCollection} */
  _contacts: undefined,
  /** @type {string} */
  _searchTerm: '',
  events: {
    'input #contactsmenu-search': '_onSearch'
  },
  templates: {
    loading: __webpack_require__(/*! ./contactsmenu/loading.handlebars */ "./core/src/OC/contactsmenu/loading.handlebars"),
    error: __webpack_require__(/*! ./contactsmenu/error.handlebars */ "./core/src/OC/contactsmenu/error.handlebars"),
    menu: __webpack_require__(/*! ./contactsmenu/menu.handlebars */ "./core/src/OC/contactsmenu/menu.handlebars"),
    list: __webpack_require__(/*! ./contactsmenu/list.handlebars */ "./core/src/OC/contactsmenu/list.handlebars")
  },
  /**
   * @returns {undefined}
   */
  _onSearch: underscore__WEBPACK_IMPORTED_MODULE_0__["default"].debounce(function (e) {
    var searchTerm = this.$('#contactsmenu-search').val();
    // IE11 triggers an 'input' event after the view has been rendered
    // resulting in an endless loading loop. To prevent this, we remember
    // the last search term to savely ignore some events
    // See https://github.com/nextcloud/server/issues/5281
    if (searchTerm !== this._searchTerm) {
      this.trigger('search', this.$('#contactsmenu-search').val());
      this._searchTerm = searchTerm;
    }
  }, 700),
  /**
   * @param {object} data
   * @returns {string}
   */
  loadingTemplate: function loadingTemplate(data) {
    return this.templates.loading(data);
  },
  /**
   * @param {object} data
   * @returns {string}
   */
  errorTemplate: function errorTemplate(data) {
    return this.templates.error(underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
      couldNotLoadText: t('core', 'Could not load your contacts')
    }, data));
  },
  /**
   * @param {object} data
   * @returns {string}
   */
  contentTemplate: function contentTemplate(data) {
    return this.templates.menu(underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
      searchContactsText: t('core', 'Search contacts …')
    }, data));
  },
  /**
   * @param {object} data
   * @returns {string}
   */
  contactsTemplate: function contactsTemplate(data) {
    return this.templates.list(underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
      noContactsFoundText: t('core', 'No contacts found'),
      showAllContactsText: t('core', 'Show all contacts …'),
      contactsAppMgmtText: t('core', 'Install the Contacts app')
    }, data));
  },
  /**
   * @param {object} options
   * @returns {undefined}
   */
  initialize: function initialize(options) {
    this.options = options;
  },
  /**
   * @param {string} text
   * @returns {undefined}
   */
  showLoading: function showLoading(text) {
    this.render();
    this._contacts = undefined;
    this.$('.content').html(this.loadingTemplate({
      loadingText: text
    }));
  },
  /**
   * @returns {undefined}
   */
  showError: function showError() {
    this.render();
    this._contacts = undefined;
    this.$('.content').html(this.errorTemplate());
  },
  /**
   * @param {object} viewData
   * @param {string} searchTerm
   * @returns {undefined}
   */
  showContacts: function showContacts(viewData, searchTerm) {
    this._contacts = viewData.contacts;
    this.render({
      contacts: viewData.contacts
    });
    var list = new ContactsListView({
      collection: viewData.contacts
    });
    list.render();
    this.$('.content').html(this.contactsTemplate({
      contacts: viewData.contacts,
      searchTerm: searchTerm,
      contactsAppEnabled: viewData.contactsAppEnabled,
      contactsAppURL: _index__WEBPACK_IMPORTED_MODULE_3__["default"].generateUrl('/apps/contacts'),
      canInstallApp: _index__WEBPACK_IMPORTED_MODULE_3__["default"].isUserAdmin(),
      contactsAppMgmtURL: _index__WEBPACK_IMPORTED_MODULE_3__["default"].generateUrl('/settings/apps/social/contacts')
    }));
    this.$('#contactsmenu-contacts').html(list.$el);
  },
  /**
   * @param {object} data
   * @returns {self}
   */
  render: function render(data) {
    var searchVal = this.$('#contactsmenu-search').val();
    this.$el.html(this.contentTemplate(data));

    // Focus search
    this.$('#contactsmenu-search').val(searchVal);
    this.$('#contactsmenu-search').focus();
    return this;
  }
});

/**
 * @param {Object} options
 * @param {string} options.el
 * @class ContactsMenu
 * @memberOf OC
 */
var ContactsMenu = function ContactsMenu(options) {
  this.initialize(options);
};
ContactsMenu.prototype = {
  /** @type {string} */
  $el: undefined,
  /** @type {ContactsMenuView} */
  _view: undefined,
  /** @type {Promise} */
  _contactsPromise: undefined,
  /**
   * @param {Object} options
   * @param {string} options.el - the selector of the element to render the menu in
   * @returns {undefined}
   */
  initialize: function initialize(options) {
    this.$el = jquery__WEBPACK_IMPORTED_MODULE_1___default()(options.el);
    this._view = new ContactsMenuView({
      el: this.$el
    });
    this._view.on('search', function (searchTerm) {
      this.loadContacts(searchTerm);
    }, this);
  },
  /**
   * @private
   * @param {string|undefined} searchTerm
   * @returns {Promise}
   */
  _getContacts: function _getContacts(searchTerm) {
    var url = _index__WEBPACK_IMPORTED_MODULE_3__["default"].generateUrl('/contactsmenu/contacts');
    return Promise.resolve(jquery__WEBPACK_IMPORTED_MODULE_1___default().ajax(url, {
      method: 'POST',
      data: {
        filter: searchTerm
      }
    }));
  },
  /**
   * @param {string|undefined} searchTerm
   * @returns {undefined}
   */
  loadContacts: function loadContacts(searchTerm) {
    var self = this;
    if (!self._contactsPromise) {
      self._contactsPromise = self._getContacts(searchTerm);
    }
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isUndefined(searchTerm) || searchTerm === '') {
      self._view.showLoading(t('core', 'Loading your contacts …'));
    } else {
      self._view.showLoading(t('core', 'Looking for {term} …', {
        term: searchTerm
      }));
    }
    return self._contactsPromise.then(function (data) {
      // Convert contact entries to Backbone collection
      data.contacts = new ContactCollection(data.contacts);
      self._view.showContacts(data, searchTerm);
    }, function (e) {
      self._view.showError();
      console.error('There was an error loading your contacts', e);
    }).then(function () {
      // Delete promise, so that contacts are fetched again when the
      // menu is opened the next time.
      delete self._contactsPromise;
    }).catch(console.error.bind(this));
  }
};
/* harmony default export */ __webpack_exports__["default"] = (ContactsMenu);

/***/ }),

/***/ "./core/src/OC/currentuser.js":
/*!************************************!*\
  !*** ./core/src/OC/currentuser.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "currentUser": function() { return /* binding */ currentUser; },
/* harmony export */   "getCurrentUser": function() { return /* binding */ getCurrentUser; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

var rawUid = document.getElementsByTagName('head')[0].getAttribute('data-user');
var displayName = document.getElementsByTagName('head')[0].getAttribute('data-user-displayname');
var currentUser = rawUid !== undefined ? rawUid : false;
var getCurrentUser = function getCurrentUser() {
  return {
    uid: currentUser,
    displayName: displayName
  };
};

/***/ }),

/***/ "./core/src/OC/debug.js":
/*!******************************!*\
  !*** ./core/src/OC/debug.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "debug": function() { return /* binding */ debug; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var base = window._oc_debug;
var debug = base;

/***/ }),

/***/ "./core/src/OC/dialogs.js":
/*!********************************!*\
  !*** ./core/src/OC/dialogs.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/* harmony import */ var _OCA_index__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../OCA/index */ "./core/src/OCA/index.js");
/* harmony import */ var _Util_a11y__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Util/a11y */ "./core/src/Util/a11y.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
 *
 * @author Bartek Przybylski <bart.p.pl@gmail.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Florian Schunk <florian.schunk@rwth-aachen.de>
 * @author Gary Kim <gary@garykim.dev>
 * @author Hendrik Leppelsack <hendrik@leppelsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Loïc Hermann <loic.hermann@sciam.fr>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith Haridasan <Sujith_Haridasan@mentor.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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

/* eslint-disable */






/**
 * this class to ease the usage of jquery dialogs
 */
var Dialogs = {
  // dialog button types
  YES_NO_BUTTONS: 70,
  OK_BUTTONS: 71,
  FILEPICKER_TYPE_CHOOSE: 1,
  FILEPICKER_TYPE_MOVE: 2,
  FILEPICKER_TYPE_COPY: 3,
  FILEPICKER_TYPE_COPY_MOVE: 4,
  FILEPICKER_TYPE_CUSTOM: 5,
  // used to name each dialog
  dialogsCounter: 0,
  /**
   * displays alert dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses OK
   * @param {boolean} [modal] make the dialog modal
   */
  alert: function alert(text, title, callback, modal) {
    this.message(text, title, 'alert', Dialogs.OK_BUTTON, callback, modal);
  },
  /**
   * displays info dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses OK
   * @param {boolean} [modal] make the dialog modal
   */
  info: function info(text, title, callback, modal) {
    this.message(text, title, 'info', Dialogs.OK_BUTTON, callback, modal);
  },
  /**
   * displays confirmation dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @returns {Promise}
   */
  confirm: function confirm(text, title, callback, modal) {
    return this.message(text, title, 'notice', Dialogs.YES_NO_BUTTONS, callback, modal);
  },
  /**
   * displays confirmation dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {(number|{type: number, confirm: string, cancel: string, confirmClasses: string})} buttons text content of buttons
   * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @returns {Promise}
   */
  confirmDestructive: function confirmDestructive(text, title, buttons, callback, modal) {
    return this.message(text, title, 'none', buttons, callback, modal === undefined ? true : modal);
  },
  /**
   * displays confirmation dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @returns {Promise}
   */
  confirmHtml: function confirmHtml(text, title, callback, modal) {
    return this.message(text, title, 'notice', Dialogs.YES_NO_BUTTONS, callback, modal, true);
  },
  /**
   * displays prompt dialog
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @param {string} name name of the input field
   * @param {boolean} password whether the input should be a password input
   * @returns {Promise}
   */
  prompt: function prompt(text, title, callback, modal, name, password) {
    return jquery__WEBPACK_IMPORTED_MODULE_1___default().when(this._getMessageTemplate()).then(function ($tmpl) {
      var dialogName = 'oc-dialog-' + Dialogs.dialogsCounter + '-content';
      var dialogId = '#' + dialogName;
      var $dlg = $tmpl.octemplate({
        dialog_name: dialogName,
        title: title,
        message: text,
        type: 'notice'
      });
      var input = jquery__WEBPACK_IMPORTED_MODULE_1___default()('<input/>');
      input.attr('type', password ? 'password' : 'text').attr('id', dialogName + '-input').attr('placeholder', name);
      var label = jquery__WEBPACK_IMPORTED_MODULE_1___default()('<label/>').attr('for', dialogName + '-input').text(name + ': ');
      $dlg.append(label);
      $dlg.append(input);
      if (modal === undefined) {
        modal = false;
      }
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('body').append($dlg);

      // wrap callback in _.once():
      // only call callback once and not twice (button handler and close
      // event) but call it for the close event, if ESC or the x is hit
      if (callback !== undefined) {
        callback = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].once(callback);
      }
      var buttonlist = [{
        text: t('core', 'No'),
        click: function click() {
          if (callback !== undefined) {
            // eslint-disable-next-line standard/no-callback-literal
            callback(false, input.val());
          }
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
        }
      }, {
        text: t('core', 'Yes'),
        click: function click() {
          if (callback !== undefined) {
            // eslint-disable-next-line standard/no-callback-literal
            callback(true, input.val());
          }
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
        },
        defaultButton: true
      }];
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog({
        closeOnEscape: true,
        modal: modal,
        buttons: buttonlist,
        close: function close() {
          // callback is already fired if Yes/No is clicked directly
          if (callback !== undefined) {
            // eslint-disable-next-line standard/no-callback-literal
            callback(false, input.val());
          }
        }
      });
      input.focus();
      Dialogs.dialogsCounter++;
    });
  },
  /**
   * show a file picker to pick a file from
   *
   * In order to pick several types of mime types they need to be passed as an
   * array of strings.
   *
   * When no mime type filter is given only files can be selected. In order to
   * be able to select both files and folders "['*', 'httpd/unix-directory']"
   * should be used instead.
   *
   * @param {string} title dialog title
   * @param {function} callback which will be triggered when user presses Choose
   * @param {boolean} [multiselect] whether it should be possible to select multiple files
   * @param {string[]} [mimetypeFilter] mimetype to filter by - directories will always be included
   * @param {boolean} [modal] make the dialog modal
   * @param {string} [type] Type of file picker : Choose, copy, move, copy and move
   * @param {string} [path] path to the folder that the the file can be picket from
   * @param {Object} [options] additonal options that need to be set
   * @param {Function} [options.filter] filter function for advanced filtering
   */
  filepicker: function filepicker(title, callback, multiselect, mimetypeFilter, modal, type, path, options) {
    var self = this;
    this.filepicker.sortField = 'name';
    this.filepicker.sortOrder = 'asc';
    // avoid opening the picker twice
    if (this.filepicker.loading) {
      return;
    }
    if (type === undefined) {
      type = this.FILEPICKER_TYPE_CHOOSE;
    }
    var emptyText = t('core', 'No files in here');
    var newText = t('files', 'New folder');
    if (type === this.FILEPICKER_TYPE_COPY || type === this.FILEPICKER_TYPE_MOVE || type === this.FILEPICKER_TYPE_COPY_MOVE) {
      emptyText = t('core', 'No more subfolders in here');
    }
    this.filepicker.loading = true;
    this.filepicker.filesClient = _OCA_index__WEBPACK_IMPORTED_MODULE_3__["default"].Sharing && _OCA_index__WEBPACK_IMPORTED_MODULE_3__["default"].Sharing.PublicApp && _OCA_index__WEBPACK_IMPORTED_MODULE_3__["default"].Sharing.PublicApp.fileList ? _OCA_index__WEBPACK_IMPORTED_MODULE_3__["default"].Sharing.PublicApp.fileList.filesClient : _index__WEBPACK_IMPORTED_MODULE_2__["default"].Files.getClient();
    this.filelist = null;
    path = path || '';
    options = Object.assign({
      allowDirectoryChooser: false
    }, options);
    jquery__WEBPACK_IMPORTED_MODULE_1___default().when(this._getFilePickerTemplate()).then(function ($tmpl) {
      self.filepicker.loading = false;
      var dialogName = 'oc-dialog-filepicker-content';
      if (self.$filePicker) {
        self.$filePicker.ocdialog('close');
      }
      if (mimetypeFilter === undefined || mimetypeFilter === null) {
        mimetypeFilter = [];
      }
      if (typeof mimetypeFilter === 'string') {
        mimetypeFilter = [mimetypeFilter];
      }
      self.$filePicker = $tmpl.octemplate({
        dialog_name: dialogName,
        title: title,
        emptytext: emptyText,
        newtext: newText,
        nameCol: t('core', 'Name'),
        sizeCol: t('core', 'Size'),
        modifiedCol: t('core', 'Modified')
      }).data('path', path).data('multiselect', multiselect).data('mimetype', mimetypeFilter).data('allowDirectoryChooser', options.allowDirectoryChooser);
      if (typeof options.filter === 'function') {
        self.$filePicker.data('filter', options.filter);
      }
      if (modal === undefined) {
        modal = false;
      }
      if (multiselect === undefined) {
        multiselect = false;
      }
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('body').prepend(self.$filePicker);
      self.$showGridView = jquery__WEBPACK_IMPORTED_MODULE_1___default()('button#picker-showgridview');
      self.$showGridView.on('click keydown', function (event) {
        if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_4__.isA11yActivation)(event)) {
          self._onGridviewChange();
        }
      });
      self._getGridSettings();
      var newButton = self.$filePicker.find('.actions.creatable .button-add');
      if (type === self.FILEPICKER_TYPE_CHOOSE && !options.allowDirectoryChooser) {
        self.$filePicker.find('.actions.creatable').hide();
      }
      newButton.on('focus', function () {
        self.$filePicker.ocdialog('setEnterCallback', function (event) {
          event.stopImmediatePropagation();
          event.preventDefault();
          newButton.click();
        });
      });
      newButton.on('blur', function () {
        self.$filePicker.ocdialog('unsetEnterCallback');
      });
      _index__WEBPACK_IMPORTED_MODULE_2__["default"].registerMenu(newButton, self.$filePicker.find('.menu'), function () {
        $input.tooltip('hide');
        $input.focus();
        self.$filePicker.ocdialog('setEnterCallback', function (event) {
          event.stopImmediatePropagation();
          event.preventDefault();
          self.$filePicker.submit();
        });
        var newName = $input.val();
        var lastPos = newName.lastIndexOf('.');
        if (lastPos === -1) {
          lastPos = newName.length;
        }
        $input.selectRange(0, lastPos);
      });
      var $form = self.$filePicker.find('.filenameform');
      var $input = $form.find('input[type=\'text\']');
      var $submit = $form.find('input[type=\'submit\']');
      $input.on('keydown', function (event) {
        if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_4__.isA11yActivation)(event)) {
          event.stopImmediatePropagation();
          event.preventDefault();
          $form.submit();
        }
      });
      $submit.on('click', function (event) {
        event.stopImmediatePropagation();
        event.preventDefault();
        $form.submit();
      });

      /**
       * Checks whether the given file name is valid.
       *
       * @param name file name to check
       * @return true if the file name is valid.
       * @throws a string exception with an error message if
       * the file name is not valid
       *
       * NOTE: This function is duplicated in the files app:
       * https://github.com/nextcloud/server/blob/b9bc2417e7a8dc81feb0abe20359bedaf864f790/apps/files/js/files.js#L127-L148
       */
      var isFileNameValid = function isFileNameValid(name) {
        var trimmedName = name.trim();
        if (trimmedName === '.' || trimmedName === '..') {
          throw t('files', '"{name}" is an invalid file name.', {
            name: name
          });
        } else if (trimmedName.length === 0) {
          throw t('files', 'File name cannot be empty.');
        } else if (trimmedName.indexOf('/') !== -1) {
          throw t('files', '"/" is not allowed inside a file name.');
        } else if (!!trimmedName.match(_index__WEBPACK_IMPORTED_MODULE_2__["default"].config.blacklist_files_regex)) {
          throw t('files', '"{name}" is not an allowed filetype', {
            name: name
          });
        }
        return true;
      };
      var checkInput = function checkInput() {
        var filename = $input.val();
        try {
          if (!isFileNameValid(filename)) {
            // isFileNameValid(filename) throws an exception itself
          } else if (self.filelist.find(function (file) {
            return file.name === this;
          }, filename)) {
            throw t('files', '{newName} already exists', {
              newName: filename
            }, undefined, {
              escape: false
            });
          } else {
            return true;
          }
        } catch (error) {
          $input.attr('title', error);
          $input.tooltip({
            placement: 'right',
            trigger: 'manual',
            'container': '.newFolderMenu'
          });
          $input.tooltip('_fixTitle');
          $input.tooltip('show');
          $input.addClass('error');
        }
        return false;
      };
      $form.on('submit', function (event) {
        event.stopPropagation();
        event.preventDefault();
        if (checkInput()) {
          var newname = $input.val();
          self.filepicker.filesClient.createDirectory(self.$filePicker.data('path') + "/" + newname).always(function (status) {
            self._fillFilePicker(self.$filePicker.data('path') + "/" + newname);
          });
          _index__WEBPACK_IMPORTED_MODULE_2__["default"].hideMenus();
          self.$filePicker.ocdialog('unsetEnterCallback');
          self.$filePicker.click();
          $input.val(newText);
        }
      });
      $input.on('input', function (event) {
        $input.tooltip('hide');
      });
      self.$filePicker.ready(function () {
        self.$fileListHeader = self.$filePicker.find('.filelist thead tr');
        self.$filelist = self.$filePicker.find('.filelist tbody');
        self.$filelistContainer = self.$filePicker.find('.filelist-container');
        self.$dirTree = self.$filePicker.find('.dirtree');
        self.$dirTree.on('click keydown', 'div:not(:last-child)', self, function (event) {
          if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_4__.isA11yActivation)(event)) {
            self._handleTreeListSelect(event, type);
          }
        });
        self.$filelist.on('click keydown', 'tr', function (event) {
          if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_4__.isA11yActivation)(event)) {
            self._handlePickerClick(event, jquery__WEBPACK_IMPORTED_MODULE_1___default()(this), type);
          }
        });
        self.$fileListHeader.on('click keydown', 'a', function (event) {
          if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_4__.isA11yActivation)(event)) {
            var dir = self.$filePicker.data('path');
            self.filepicker.sortField = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data('sort');
            self.filepicker.sortOrder = self.filepicker.sortOrder === 'asc' ? 'desc' : 'asc';
            self._fillFilePicker(dir);
          }
        });
        self._fillFilePicker(path);
      });

      // build buttons
      var functionToCall = function functionToCall(returnType) {
        if (callback !== undefined) {
          var datapath;
          if (multiselect === true) {
            datapath = [];
            self.$filelist.find('tr.filepicker_element_selected').each(function (index, element) {
              datapath.push(self.$filePicker.data('path') + '/' + jquery__WEBPACK_IMPORTED_MODULE_1___default()(element).data('entryname'));
            });
          } else {
            datapath = self.$filePicker.data('path');
            var selectedName = self.$filelist.find('tr.filepicker_element_selected').data('entryname');
            if (selectedName) {
              datapath += '/' + selectedName;
            }
          }
          callback(datapath, returnType);
          self.$filePicker.ocdialog('close');
        }
      };
      var chooseCallback = function chooseCallback() {
        functionToCall(Dialogs.FILEPICKER_TYPE_CHOOSE);
      };
      var copyCallback = function copyCallback() {
        functionToCall(Dialogs.FILEPICKER_TYPE_COPY);
      };
      var moveCallback = function moveCallback() {
        functionToCall(Dialogs.FILEPICKER_TYPE_MOVE);
      };
      var buttonlist = [];
      if (type === Dialogs.FILEPICKER_TYPE_CHOOSE) {
        buttonlist.push({
          text: t('core', 'Choose'),
          click: chooseCallback,
          defaultButton: true
        });
      } else if (type === Dialogs.FILEPICKER_TYPE_CUSTOM) {
        options.buttons.forEach(function (button) {
          buttonlist.push({
            text: button.text,
            click: function click() {
              functionToCall(button.type);
            },
            defaultButton: button.defaultButton
          });
        });
      } else {
        if (type === Dialogs.FILEPICKER_TYPE_COPY || type === Dialogs.FILEPICKER_TYPE_COPY_MOVE) {
          buttonlist.push({
            text: t('core', 'Copy'),
            click: copyCallback,
            defaultButton: false
          });
        }
        if (type === Dialogs.FILEPICKER_TYPE_MOVE || type === Dialogs.FILEPICKER_TYPE_COPY_MOVE) {
          buttonlist.push({
            text: t('core', 'Move'),
            click: moveCallback,
            defaultButton: true
          });
        }
      }
      self.$filePicker.ocdialog({
        closeOnEscape: true,
        // max-width of 600
        width: 600,
        height: 500,
        modal: modal,
        buttons: buttonlist,
        style: {
          buttons: 'aside'
        },
        close: function close() {
          try {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).ocdialog('destroy').remove();
          } catch (e) {}
          self.$filePicker = null;
        }
      });

      // We can access primary class only from oc-dialog.
      // Hence this is one of the approach to get the choose button.
      var getOcDialog = self.$filePicker.closest('.oc-dialog');
      var buttonEnableDisable = getOcDialog.find('.primary');
      if (self.$filePicker.data('mimetype').indexOf('httpd/unix-directory') !== -1 || self.$filePicker.data('allowDirectoryChooser')) {
        buttonEnableDisable.prop('disabled', false);
      } else {
        buttonEnableDisable.prop('disabled', true);
      }
    }).fail(function (status, error) {
      // If the method is called while navigating away
      // from the page, it is probably not needed ;)
      self.filepicker.loading = false;
      if (status !== 0) {
        alert(t('core', 'Error loading file picker template: {error}', {
          error: error
        }));
      }
    });
  },
  /**
   * Displays raw dialog
   * You better use a wrapper instead ...
   */
  message: function message(content, title, dialogType, buttons, callback, modal, allowHtml) {
    return jquery__WEBPACK_IMPORTED_MODULE_1___default().when(this._getMessageTemplate()).then(function ($tmpl) {
      var dialogName = 'oc-dialog-' + Dialogs.dialogsCounter + '-content';
      var dialogId = '#' + dialogName;
      var $dlg = $tmpl.octemplate({
        dialog_name: dialogName,
        title: title,
        message: content,
        type: dialogType
      }, allowHtml ? {
        escapeFunction: ''
      } : {});
      if (modal === undefined) {
        modal = false;
      }
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('body').append($dlg);
      var buttonlist = [];
      switch (buttons) {
        case Dialogs.YES_NO_BUTTONS:
          buttonlist = [{
            text: t('core', 'No'),
            click: function click() {
              if (callback !== undefined) {
                callback(false);
              }
              jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
            }
          }, {
            text: t('core', 'Yes'),
            click: function click() {
              if (callback !== undefined) {
                callback(true);
              }
              jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
            },
            defaultButton: true
          }];
          break;
        case Dialogs.OK_BUTTON:
          var functionToCall = function functionToCall() {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
            if (callback !== undefined) {
              callback();
            }
          };
          buttonlist[0] = {
            text: t('core', 'OK'),
            click: functionToCall,
            defaultButton: true
          };
          break;
        default:
          if (_typeof(buttons) === 'object') {
            switch (buttons.type) {
              case Dialogs.YES_NO_BUTTONS:
                buttonlist = [{
                  text: buttons.cancel || t('core', 'No'),
                  click: function click() {
                    if (callback !== undefined) {
                      callback(false);
                    }
                    jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
                  }
                }, {
                  text: buttons.confirm || t('core', 'Yes'),
                  click: function click() {
                    if (callback !== undefined) {
                      callback(true);
                    }
                    jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
                  },
                  defaultButton: true,
                  classes: buttons.confirmClasses
                }];
                break;
            }
          }
          break;
      }
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog({
        closeOnEscape: true,
        closeCallback: function closeCallback() {
          callback && callback(false);
        },
        modal: modal,
        buttons: buttonlist
      });
      Dialogs.dialogsCounter++;
    }).fail(function (status, error) {
      // If the method is called while navigating away from
      // the page, we still want to deliver the message.
      if (status === 0) {
        alert(title + ': ' + content);
      } else {
        alert(t('core', 'Error loading message template: {error}', {
          error: error
        }));
      }
    });
  },
  _fileexistsshown: false,
  /**
   * Displays file exists dialog
   * @param {object} data upload object
   * @param {object} original file with name, size and mtime
   * @param {object} replacement file with name, size and mtime
   * @param {object} controller with onCancel, onSkip, onReplace and onRename methods
   * @returns {Promise} jquery promise that resolves after the dialog template was loaded
   */
  fileexists: function fileexists(data, original, replacement, controller) {
    var self = this;
    var dialogDeferred = new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred)();
    var getCroppedPreview = function getCroppedPreview(file) {
      var deferred = new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred)();
      // Only process image files.
      var type = file.type && file.type.split('/').shift();
      if (window.FileReader && type === 'image') {
        var reader = new FileReader();
        reader.onload = function (e) {
          var blob = new Blob([e.target.result]);
          window.URL = window.URL || window.webkitURL;
          var originalUrl = window.URL.createObjectURL(blob);
          var image = new Image();
          image.src = originalUrl;
          image.onload = function () {
            var url = crop(image);
            deferred.resolve(url);
          };
        };
        reader.readAsArrayBuffer(file);
      } else {
        deferred.reject();
      }
      return deferred;
    };
    var crop = function crop(img) {
      var canvas = document.createElement('canvas');
      var targetSize = 96;
      var width = img.width;
      var height = img.height;
      var x;
      var y;
      var size;

      // Calculate the width and height, constraining the proportions
      if (width > height) {
        y = 0;
        x = (width - height) / 2;
      } else {
        y = (height - width) / 2;
        x = 0;
      }
      size = Math.min(width, height);

      // Set canvas size to the cropped area
      canvas.width = size;
      canvas.height = size;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, x, y, size, size, 0, 0, size, size);

      // Resize the canvas to match the destination (right size uses 96px)
      resampleHermite(canvas, size, size, targetSize, targetSize);
      return canvas.toDataURL('image/png', 0.7);
    };

    /**
     * Fast image resize/resample using Hermite filter with JavaScript.
     *
     * @author: ViliusL
     *
     * @param {*} canvas
     * @param {number} W
     * @param {number} H
     * @param {number} W2
     * @param {number} H2
     */
    var resampleHermite = function resampleHermite(canvas, W, H, W2, H2) {
      W2 = Math.round(W2);
      H2 = Math.round(H2);
      var img = canvas.getContext('2d').getImageData(0, 0, W, H);
      var img2 = canvas.getContext('2d').getImageData(0, 0, W2, H2);
      var data = img.data;
      var data2 = img2.data;
      var ratio_w = W / W2;
      var ratio_h = H / H2;
      var ratio_w_half = Math.ceil(ratio_w / 2);
      var ratio_h_half = Math.ceil(ratio_h / 2);
      for (var j = 0; j < H2; j++) {
        for (var i = 0; i < W2; i++) {
          var x2 = (i + j * W2) * 4;
          var weight = 0;
          var weights = 0;
          var weights_alpha = 0;
          var gx_r = 0;
          var gx_g = 0;
          var gx_b = 0;
          var gx_a = 0;
          var center_y = (j + 0.5) * ratio_h;
          for (var yy = Math.floor(j * ratio_h); yy < (j + 1) * ratio_h; yy++) {
            var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half;
            var center_x = (i + 0.5) * ratio_w;
            var w0 = dy * dy; // pre-calc part of w
            for (var xx = Math.floor(i * ratio_w); xx < (i + 1) * ratio_w; xx++) {
              var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half;
              var w = Math.sqrt(w0 + dx * dx);
              if (w >= -1 && w <= 1) {
                // hermite filter
                weight = 2 * w * w * w - 3 * w * w + 1;
                if (weight > 0) {
                  dx = 4 * (xx + yy * W);
                  // alpha
                  gx_a += weight * data[dx + 3];
                  weights_alpha += weight;
                  // colors
                  if (data[dx + 3] < 255) {
                    weight = weight * data[dx + 3] / 250;
                  }
                  gx_r += weight * data[dx];
                  gx_g += weight * data[dx + 1];
                  gx_b += weight * data[dx + 2];
                  weights += weight;
                }
              }
            }
          }
          data2[x2] = gx_r / weights;
          data2[x2 + 1] = gx_g / weights;
          data2[x2 + 2] = gx_b / weights;
          data2[x2 + 3] = gx_a / weights_alpha;
        }
      }
      canvas.getContext('2d').clearRect(0, 0, Math.max(W, W2), Math.max(H, H2));
      canvas.width = W2;
      canvas.height = H2;
      canvas.getContext('2d').putImageData(img2, 0, 0);
    };
    var addConflict = function addConflict($conflicts, original, replacement) {
      var $conflict = $conflicts.find('.template').clone().removeClass('template').addClass('conflict');
      var $originalDiv = $conflict.find('.original');
      var $replacementDiv = $conflict.find('.replacement');
      $conflict.data('data', data);
      $conflict.find('.filename').text(original.name);
      $originalDiv.find('.size').text(_index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.humanFileSize(original.size));
      $originalDiv.find('.mtime').text(_index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.formatDate(original.mtime));
      // ie sucks
      if (replacement.size && replacement.lastModified) {
        $replacementDiv.find('.size').text(_index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.humanFileSize(replacement.size));
        $replacementDiv.find('.mtime').text(_index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.formatDate(replacement.lastModified));
      }
      var path = original.directory + '/' + original.name;
      var urlSpec = {
        file: path,
        x: 96,
        y: 96,
        c: original.etag,
        forceIcon: 0
      };
      var previewpath = Files.generatePreviewUrl(urlSpec);
      // Escaping single quotes
      previewpath = previewpath.replace(/'/g, '%27');
      $originalDiv.find('.icon').css({
        'background-image': "url('" + previewpath + "')"
      });
      getCroppedPreview(replacement).then(function (path) {
        $replacementDiv.find('.icon').css('background-image', 'url(' + path + ')');
      }, function () {
        path = _index__WEBPACK_IMPORTED_MODULE_2__["default"].MimeType.getIconUrl(replacement.type);
        $replacementDiv.find('.icon').css('background-image', 'url(' + path + ')');
      });
      // connect checkboxes with labels
      var checkboxId = $conflicts.find('.conflict').length;
      $originalDiv.find('input:checkbox').attr('id', 'checkbox_original_' + checkboxId);
      $replacementDiv.find('input:checkbox').attr('id', 'checkbox_replacement_' + checkboxId);
      $conflicts.append($conflict);

      // set more recent mtime bold
      // ie sucks
      if (replacement.lastModified > original.mtime) {
        $replacementDiv.find('.mtime').css('font-weight', 'bold');
      } else if (replacement.lastModified < original.mtime) {
        $originalDiv.find('.mtime').css('font-weight', 'bold');
      } else {
        // TODO add to same mtime collection?
      }

      // set bigger size bold
      if (replacement.size && replacement.size > original.size) {
        $replacementDiv.find('.size').css('font-weight', 'bold');
      } else if (replacement.size && replacement.size < original.size) {
        $originalDiv.find('.size').css('font-weight', 'bold');
      } else {
        // TODO add to same size collection?
      }

      // TODO show skip action for files with same size and mtime in bottom row

      // always keep readonly files

      if (original.status === 'readonly') {
        $originalDiv.addClass('readonly').find('input[type="checkbox"]').prop('checked', true).prop('disabled', true);
        $originalDiv.find('.message').text(t('core', 'read-only'));
      }
    };
    // var selection = controller.getSelection(data.originalFiles);
    // if (selection.defaultAction) {
    //	controller[selection.defaultAction](data);
    // } else {
    var dialogName = 'oc-dialog-fileexists-content';
    var dialogId = '#' + dialogName;
    if (this._fileexistsshown) {
      // add conflict

      var $conflicts = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId + ' .conflicts');
      addConflict($conflicts, original, replacement);
      var count = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId + ' .conflict').length;
      var title = n('core', '{count} file conflict', '{count} file conflicts', count, {
        count: count
      });
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).parent().children('.oc-dialog-title').text(title);

      // recalculate dimensions
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(window).trigger('resize');
      dialogDeferred.resolve();
    } else {
      // create dialog
      this._fileexistsshown = true;
      jquery__WEBPACK_IMPORTED_MODULE_1___default().when(this._getFileExistsTemplate()).then(function ($tmpl) {
        var title = t('core', 'One file conflict');
        var $dlg = $tmpl.octemplate({
          dialog_name: dialogName,
          title: title,
          type: 'fileexists',
          allnewfiles: t('core', 'New Files'),
          allexistingfiles: t('core', 'Already existing files'),
          why: t('core', 'Which files do you want to keep?'),
          what: t('core', 'If you select both versions, the copied file will have a number added to its name.')
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()('body').append($dlg);
        if (original && replacement) {
          var $conflicts = $dlg.find('.conflicts');
          addConflict($conflicts, original, replacement);
        }
        var buttonlist = [{
          text: t('core', 'Cancel'),
          classes: 'cancel',
          click: function click() {
            if (typeof controller.onCancel !== 'undefined') {
              controller.onCancel(data);
            }
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
          }
        }, {
          text: t('core', 'Continue'),
          classes: 'continue',
          click: function click() {
            if (typeof controller.onContinue !== 'undefined') {
              controller.onContinue(jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId + ' .conflict'));
            }
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog('close');
          }
        }];
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).ocdialog({
          width: 500,
          closeOnEscape: true,
          modal: true,
          buttons: buttonlist,
          closeButton: null,
          close: function close() {
            self._fileexistsshown = false;
            try {
              jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).ocdialog('destroy').remove();
            } catch (e) {
              // ignore
            }
          }
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).css('height', 'auto');
        var $primaryButton = $dlg.closest('.oc-dialog').find('button.continue');
        $primaryButton.prop('disabled', true);
        function updatePrimaryButton() {
          var checkedCount = $dlg.find('.conflicts .checkbox:checked').length;
          $primaryButton.prop('disabled', checkedCount === 0);
        }

        // add checkbox toggling actions
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles').on('click', function () {
          var $checkboxes = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflict .replacement input[type="checkbox"]');
          $checkboxes.prop('checked', jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).prop('checked'));
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles').on('click', function () {
          var $checkboxes = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflict .original:not(.readonly) input[type="checkbox"]');
          $checkboxes.prop('checked', jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).prop('checked'));
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflicts').on('click', '.replacement,.original:not(.readonly)', function () {
          var $checkbox = jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).find('input[type="checkbox"]');
          $checkbox.prop('checked', !$checkbox.prop('checked'));
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflicts').on('click', '.replacement input[type="checkbox"],.original:not(.readonly) input[type="checkbox"]', function () {
          var $checkbox = jquery__WEBPACK_IMPORTED_MODULE_1___default()(this);
          $checkbox.prop('checked', !$checkbox.prop('checked'));
        });

        // update counters
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).on('click', '.replacement,.allnewfiles', function () {
          var count = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflict .replacement input[type="checkbox"]:checked').length;
          if (count === jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId + ' .conflict').length) {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles').prop('checked', true);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles + .count').text(t('core', '(all selected)'));
          } else if (count > 0) {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles').prop('checked', false);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles + .count').text(t('core', '({count} selected)', {
              count: count
            }));
          } else {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles').prop('checked', false);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allnewfiles + .count').text('');
          }
          updatePrimaryButton();
        });
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).on('click', '.original,.allexistingfiles', function () {
          var count = jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.conflict .original input[type="checkbox"]:checked').length;
          if (count === jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId + ' .conflict').length) {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles').prop('checked', true);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles + .count').text(t('core', '(all selected)'));
          } else if (count > 0) {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles').prop('checked', false);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles + .count').text(t('core', '({count} selected)', {
              count: count
            }));
          } else {
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles').prop('checked', false);
            jquery__WEBPACK_IMPORTED_MODULE_1___default()(dialogId).find('.allexistingfiles + .count').text('');
          }
          updatePrimaryButton();
        });
        dialogDeferred.resolve();
      }).fail(function () {
        dialogDeferred.reject();
        alert(t('core', 'Error loading file exists template'));
      });
    }
    // }
    return dialogDeferred.promise();
  },
  // get the gridview setting and set the input accordingly
  _getGridSettings: function _getGridSettings() {
    var self = this;
    jquery__WEBPACK_IMPORTED_MODULE_1___default().get(_index__WEBPACK_IMPORTED_MODULE_2__["default"].generateUrl('/apps/files/api/v1/showgridview'), function (response) {
      self.$showGridView.removeClass('icon-toggle-filelist icon-toggle-pictures').addClass(response.gridview ? 'icon-toggle-filelist' : 'icon-toggle-pictures');
      self.$showGridView.attr('aria-label', response.gridview ? t('files', 'Show list view') : t('files', 'Show grid view'));
      jquery__WEBPACK_IMPORTED_MODULE_1___default()('.list-container').toggleClass('view-grid', response.gridview);
    });
  },
  _onGridviewChange: function _onGridviewChange() {
    var isGridView = this.$showGridView.hasClass('icon-toggle-filelist');
    // only save state if user is logged in
    if (_index__WEBPACK_IMPORTED_MODULE_2__["default"].currentUser) {
      jquery__WEBPACK_IMPORTED_MODULE_1___default().post(_index__WEBPACK_IMPORTED_MODULE_2__["default"].generateUrl('/apps/files/api/v1/showgridview'), {
        show: !isGridView
      });
    }
    this.$showGridView.removeClass('icon-toggle-filelist icon-toggle-pictures').addClass(isGridView ? 'icon-toggle-pictures' : 'icon-toggle-filelist');
    this.$showGridView.attr('aria-label', isGridView ? t('files', 'Show grid view') : t('files', 'Show list view'));
    this.$filePicker.find('.list-container').toggleClass('view-grid', !isGridView);
  },
  _getFilePickerTemplate: function _getFilePickerTemplate() {
    var defer = jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred();
    if (!this.$filePickerTemplate) {
      var self = this;
      jquery__WEBPACK_IMPORTED_MODULE_1___default().get(_index__WEBPACK_IMPORTED_MODULE_2__["default"].filePath('core', 'templates', 'filepicker.html'), function (tmpl) {
        self.$filePickerTemplate = jquery__WEBPACK_IMPORTED_MODULE_1___default()(tmpl);
        self.$listTmpl = self.$filePickerTemplate.find('.filelist tbody tr:first-child').detach();
        defer.resolve(self.$filePickerTemplate);
      }).fail(function (jqXHR, textStatus, errorThrown) {
        defer.reject(jqXHR.status, errorThrown);
      });
    } else {
      defer.resolve(this.$filePickerTemplate);
    }
    return defer.promise();
  },
  _getMessageTemplate: function _getMessageTemplate() {
    var defer = jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred();
    if (!this.$messageTemplate) {
      var self = this;
      jquery__WEBPACK_IMPORTED_MODULE_1___default().get(_index__WEBPACK_IMPORTED_MODULE_2__["default"].filePath('core', 'templates', 'message.html'), function (tmpl) {
        self.$messageTemplate = jquery__WEBPACK_IMPORTED_MODULE_1___default()(tmpl);
        defer.resolve(self.$messageTemplate);
      }).fail(function (jqXHR, textStatus, errorThrown) {
        defer.reject(jqXHR.status, errorThrown);
      });
    } else {
      defer.resolve(this.$messageTemplate);
    }
    return defer.promise();
  },
  _getFileExistsTemplate: function _getFileExistsTemplate() {
    var defer = jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred();
    if (!this.$fileexistsTemplate) {
      var self = this;
      jquery__WEBPACK_IMPORTED_MODULE_1___default().get(_index__WEBPACK_IMPORTED_MODULE_2__["default"].filePath('files', 'templates', 'fileexists.html'), function (tmpl) {
        self.$fileexistsTemplate = jquery__WEBPACK_IMPORTED_MODULE_1___default()(tmpl);
        defer.resolve(self.$fileexistsTemplate);
      }).fail(function () {
        defer.reject();
      });
    } else {
      defer.resolve(this.$fileexistsTemplate);
    }
    return defer.promise();
  },
  /**
   * fills the filepicker with files
   */
  _fillFilePicker: function () {
    var _fillFilePicker2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(dir) {
      var self, filter, advancedFilter, getFolderContents, files, showHiddenInput, showHidden, Comparators, comparator;
      return regeneratorRuntime.wrap(function _callee2$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              self = this;
              this.$filelist.empty();
              this.$filePicker.find('.emptycontent').hide();
              this.$filelistContainer.addClass('icon-loading');
              this.$filePicker.data('path', dir);
              filter = this.$filePicker.data('mimetype');
              advancedFilter = this.$filePicker.data('filter');
              if (typeof filter === 'string') {
                filter = [filter];
              }
              self.$fileListHeader.find('.sort-indicator').addClass('hidden').removeClass('icon-triangle-n').removeClass('icon-triangle-s');
              self.$fileListHeader.find('[data-sort=' + self.filepicker.sortField + '] .sort-indicator').removeClass('hidden');
              if (self.filepicker.sortOrder === 'asc') {
                self.$fileListHeader.find('[data-sort=' + self.filepicker.sortField + '] .sort-indicator').addClass('icon-triangle-n');
              } else {
                self.$fileListHeader.find('[data-sort=' + self.filepicker.sortField + '] .sort-indicator').addClass('icon-triangle-s');
              }

              // Wrap within a method because a promise cannot return multiple values
              // But the client impleemntation still does it...
              getFolderContents = /*#__PURE__*/function () {
                var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(dir) {
                  return regeneratorRuntime.wrap(function _callee$(_context) {
                    while (1) {
                      switch (_context.prev = _context.next) {
                        case 0:
                          return _context.abrupt("return", self.filepicker.filesClient.getFolderContents(dir).then(function (status, files) {
                            return files;
                          }));
                        case 1:
                        case "end":
                          return _context.stop();
                      }
                    }
                  }, _callee);
                }));
                return function getFolderContents(_x2) {
                  return _ref.apply(this, arguments);
                };
              }();
              _context2.prev = 12;
              _context2.next = 15;
              return getFolderContents(dir);
            case 15:
              files = _context2.sent;
              _context2.next = 25;
              break;
            case 18:
              _context2.prev = 18;
              _context2.t0 = _context2["catch"](12);
              // fallback to root if requested dir is non-existent
              console.error('Requested path does not exists, falling back to root');
              _context2.next = 23;
              return getFolderContents('/');
            case 23:
              files = _context2.sent;
              this.$filePicker.data('path', '/');
            case 25:
              self.filelist = files;
              if (filter && filter.length > 0 && filter.indexOf('*') === -1) {
                files = files.filter(function (file) {
                  return file.type === 'dir' || filter.indexOf(file.mimetype) !== -1;
                });
              }
              if (advancedFilter) {
                files = files.filter(advancedFilter);
              }

              // Check if the showHidden input field exist and if it exist follow it
              // Otherwise just show the hidden files
              showHiddenInput = document.getElementById('showHiddenFiles');
              showHidden = showHiddenInput === null || showHiddenInput.value === "1";
              if (!showHidden) {
                files = files.filter(function (file) {
                  return !file.name.startsWith('.');
                });
              }
              Comparators = {
                name: function name(fileInfo1, fileInfo2) {
                  if (fileInfo1.type === 'dir' && fileInfo2.type !== 'dir') {
                    return -1;
                  }
                  if (fileInfo1.type !== 'dir' && fileInfo2.type === 'dir') {
                    return 1;
                  }
                  return _index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.naturalSortCompare(fileInfo1.name, fileInfo2.name);
                },
                size: function size(fileInfo1, fileInfo2) {
                  return fileInfo1.size - fileInfo2.size;
                },
                mtime: function mtime(fileInfo1, fileInfo2) {
                  return fileInfo1.mtime - fileInfo2.mtime;
                }
              };
              comparator = Comparators[self.filepicker.sortField] || Comparators.name;
              files = files.sort(function (file1, file2) {
                var isFavorite = function isFavorite(fileInfo) {
                  return fileInfo.tags && fileInfo.tags.indexOf(_index__WEBPACK_IMPORTED_MODULE_2__["default"].TAG_FAVORITE) >= 0;
                };
                if (isFavorite(file1) && !isFavorite(file2)) {
                  return -1;
                } else if (!isFavorite(file1) && isFavorite(file2)) {
                  return 1;
                }
                return self.filepicker.sortOrder === 'asc' ? comparator(file1, file2) : -comparator(file1, file2);
              });
              self._fillSlug();
              if (files.length === 0) {
                self.$filePicker.find('.emptycontent').show();
                self.$fileListHeader.hide();
              } else {
                self.$filePicker.find('.emptycontent').hide();
                self.$fileListHeader.show();
              }
              self.$filelist.empty();
              jquery__WEBPACK_IMPORTED_MODULE_1___default().each(files, function (idx, entry) {
                if (entry.isEncrypted && entry.mimetype === 'httpd/unix-directory') {
                  entry.icon = _index__WEBPACK_IMPORTED_MODULE_2__["default"].MimeType.getIconUrl('dir-encrypted');
                } else {
                  entry.icon = _index__WEBPACK_IMPORTED_MODULE_2__["default"].MimeType.getIconUrl(entry.mimetype);
                }
                var simpleSize, sizeColor;
                if (typeof entry.size !== 'undefined' && entry.size >= 0) {
                  simpleSize = _index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.humanFileSize(parseInt(entry.size, 10), true);
                  sizeColor = Math.round(160 - Math.pow(entry.size / (1024 * 1024), 2));
                } else {
                  simpleSize = t('files', 'Pending');
                  sizeColor = 80;
                }

                // split the filename in half if the size is bigger than 20 char
                // for ellipsis
                if (entry.name.length >= 10) {
                  // leave maximum 10 letters
                  var split = Math.min(Math.floor(entry.name.length / 2), 10);
                  var filename1 = entry.name.substr(0, entry.name.length - split);
                  var filename2 = entry.name.substr(entry.name.length - split);
                } else {
                  var filename1 = entry.name;
                  var filename2 = '';
                }
                var $row = self.$listTmpl.octemplate({
                  type: entry.type,
                  dir: dir,
                  filename: entry.name,
                  filename1: filename1,
                  filename2: filename2,
                  date: _index__WEBPACK_IMPORTED_MODULE_2__["default"].Util.relativeModifiedDate(entry.mtime),
                  size: simpleSize,
                  sizeColor: sizeColor,
                  icon: entry.icon
                });
                if (entry.type === 'file') {
                  var urlSpec = {
                    file: dir + '/' + entry.name,
                    x: 100,
                    y: 100
                  };
                  var img = new Image();
                  var previewUrl = _index__WEBPACK_IMPORTED_MODULE_2__["default"].generateUrl('/core/preview.png?') + jquery__WEBPACK_IMPORTED_MODULE_1___default().param(urlSpec);
                  img.onload = function () {
                    if (img.width > 5) {
                      $row.find('td.filename').attr('style', 'background-image:url(' + previewUrl + ')');
                    }
                  };
                  img.src = previewUrl;
                }
                self.$filelist.append($row);
              });
              self.$filelistContainer.removeClass('icon-loading');
            case 39:
            case "end":
              return _context2.stop();
          }
        }
      }, _callee2, this, [[12, 18]]);
    }));
    function _fillFilePicker(_x) {
      return _fillFilePicker2.apply(this, arguments);
    }
    return _fillFilePicker;
  }(),
  /**
   * fills the tree list with directories
   */
  _fillSlug: function _fillSlug() {
    var addButton = this.$dirTree.find('.actions.creatable').detach();
    this.$dirTree.empty();
    var self = this;
    self.$dirTree.append(addButton);
    var dir;
    var path = this.$filePicker.data('path');
    var $template = jquery__WEBPACK_IMPORTED_MODULE_1___default()('<div data-dir="{dir}" tabindex="0"><a>{name}</a></div>').addClass('crumb');
    if (path) {
      var paths = path.split('/');
      jquery__WEBPACK_IMPORTED_MODULE_1___default().each(paths, function (index, dir) {
        dir = paths.pop();
        if (dir === '') {
          return false;
        }
        self.$dirTree.prepend($template.octemplate({
          dir: paths.join('/') + '/' + dir,
          name: dir
        }));
      });
    }
    $template.octemplate({
      dir: '',
      name: '' // Ugly but works ;)
    }, {
      escapeFunction: null
    }).prependTo(this.$dirTree);
  },
  /**
   * handle selection made in the tree list
   */
  _handleTreeListSelect: function _handleTreeListSelect(event, type) {
    var self = event.data;
    var dir = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.target).closest('.crumb').data('dir');
    self._fillFilePicker(dir);
    var getOcDialog = event.target.closest('.oc-dialog');
    var buttonEnableDisable = jquery__WEBPACK_IMPORTED_MODULE_1___default()('.primary', getOcDialog);
    this._changeButtonsText(type, dir.split(/[/]+/).pop());
    if (this.$filePicker.data('mimetype').indexOf('httpd/unix-directory') !== -1 || this.$filePicker.data('allowDirectoryChooser')) {
      buttonEnableDisable.prop('disabled', false);
    } else {
      buttonEnableDisable.prop('disabled', true);
    }
  },
  /**
   * handle clicks made in the filepicker
   */
  _handlePickerClick: function _handlePickerClick(event, $element, type) {
    var getOcDialog = this.$filePicker.closest('.oc-dialog');
    var buttonEnableDisable = getOcDialog.find('.primary');
    if ($element.data('type') === 'file') {
      if (this.$filePicker.data('multiselect') !== true || !event.ctrlKey) {
        this.$filelist.find('.filepicker_element_selected').removeClass('filepicker_element_selected');
      }
      $element.toggleClass('filepicker_element_selected');
      buttonEnableDisable.prop('disabled', false);
    } else if ($element.data('type') === 'dir') {
      this._fillFilePicker(this.$filePicker.data('path') + '/' + $element.data('entryname'));
      this._changeButtonsText(type, $element.data('entryname'));
      if (this.$filePicker.data('mimetype').indexOf('httpd/unix-directory') !== -1 || this.$filePicker.data('allowDirectoryChooser')) {
        buttonEnableDisable.prop('disabled', false);
      } else {
        buttonEnableDisable.prop('disabled', true);
      }
    }
  },
  /**
   * Handle
   * @param type of action
   * @param dir on which to change buttons text
   * @private
   */
  _changeButtonsText: function _changeButtonsText(type, dir) {
    var copyText = dir === '' ? t('core', 'Copy') : t('core', 'Copy to {folder}', {
      folder: dir
    });
    var moveText = dir === '' ? t('core', 'Move') : t('core', 'Move to {folder}', {
      folder: dir
    });
    var buttons = jquery__WEBPACK_IMPORTED_MODULE_1___default()('.oc-dialog-buttonrow button');
    switch (type) {
      case this.FILEPICKER_TYPE_CHOOSE:
        break;
      case this.FILEPICKER_TYPE_CUSTOM:
        break;
      case this.FILEPICKER_TYPE_COPY:
        buttons.text(copyText);
        break;
      case this.FILEPICKER_TYPE_MOVE:
        buttons.text(moveText);
        break;
      case this.FILEPICKER_TYPE_COPY_MOVE:
        buttons.eq(0).text(copyText);
        buttons.eq(1).text(moveText);
        break;
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Dialogs);

/***/ }),

/***/ "./core/src/OC/eventsource.js":
/*!************************************!*\
  !*** ./core/src/OC/eventsource.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _requesttoken__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./requesttoken */ "./core/src/OC/requesttoken.js");
/**
 * @copyright 2012 Robin Appelman icewind1991@gmail.com
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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

/* eslint-disable */



/**
 * Create a new event source
 * @param {string} src
 * @param {object} [data] to be send as GET
 *
 * @constructs OCEventSource
 */
var OCEventSource = function OCEventSource(src, data) {
  var dataStr = '';
  var name;
  var joinChar;
  this.typelessListeners = [];
  this.closed = false;
  this.listeners = {};
  if (data) {
    for (name in data) {
      dataStr += name + '=' + encodeURIComponent(data[name]) + '&';
    }
  }
  dataStr += 'requesttoken=' + encodeURIComponent((0,_requesttoken__WEBPACK_IMPORTED_MODULE_1__.getToken)());
  if (!this.useFallBack && typeof EventSource !== 'undefined') {
    joinChar = '&';
    if (src.indexOf('?') === -1) {
      joinChar = '?';
    }
    this.source = new EventSource(src + joinChar + dataStr);
    this.source.onmessage = function (e) {
      for (var i = 0; i < this.typelessListeners.length; i++) {
        this.typelessListeners[i](JSON.parse(e.data));
      }
    }.bind(this);
  } else {
    var iframeId = 'oc_eventsource_iframe_' + OCEventSource.iframeCount;
    OCEventSource.fallBackSources[OCEventSource.iframeCount] = this;
    this.iframe = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<iframe></iframe>');
    this.iframe.attr('id', iframeId);
    this.iframe.hide();
    joinChar = '&';
    if (src.indexOf('?') === -1) {
      joinChar = '?';
    }
    this.iframe.attr('src', src + joinChar + 'fallback=true&fallback_id=' + OCEventSource.iframeCount + '&' + dataStr);
    jquery__WEBPACK_IMPORTED_MODULE_0___default()('body').append(this.iframe);
    this.useFallBack = true;
    OCEventSource.iframeCount++;
  }
  // add close listener
  this.listen('__internal__', function (data) {
    if (data === 'close') {
      this.close();
    }
  }.bind(this));
};
OCEventSource.fallBackSources = [];
OCEventSource.iframeCount = 0; // number of fallback iframes
OCEventSource.fallBackCallBack = function (id, type, data) {
  OCEventSource.fallBackSources[id].fallBackCallBack(type, data);
};
OCEventSource.prototype = {
  typelessListeners: [],
  iframe: null,
  listeners: {},
  // only for fallback
  useFallBack: false,
  /**
   * Fallback callback for browsers that don't have the
   * native EventSource object.
   *
   * Calls the registered listeners.
   *
   * @private
   * @param {String} type event type
   * @param {Object} data received data
   */
  fallBackCallBack: function fallBackCallBack(type, data) {
    var i;
    // ignore messages that might appear after closing
    if (this.closed) {
      return;
    }
    if (type) {
      if (typeof this.listeners.done !== 'undefined') {
        for (i = 0; i < this.listeners[type].length; i++) {
          this.listeners[type][i](data);
        }
      }
    } else {
      for (i = 0; i < this.typelessListeners.length; i++) {
        this.typelessListeners[i](data);
      }
    }
  },
  lastLength: 0,
  // for fallback
  /**
   * Listen to a given type of events.
   *
   * @param {String} type event type
   * @param {Function} callback event callback
   */
  listen: function listen(type, callback) {
    if (callback && callback.call) {
      if (type) {
        if (this.useFallBack) {
          if (!this.listeners[type]) {
            this.listeners[type] = [];
          }
          this.listeners[type].push(callback);
        } else {
          this.source.addEventListener(type, function (e) {
            if (typeof e.data !== 'undefined') {
              callback(JSON.parse(e.data));
            } else {
              callback('');
            }
          }, false);
        }
      } else {
        this.typelessListeners.push(callback);
      }
    }
  },
  /**
   * Closes this event source.
   */
  close: function close() {
    this.closed = true;
    if (typeof this.source !== 'undefined') {
      this.source.close();
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (OCEventSource);

/***/ }),

/***/ "./core/src/OC/get_set.js":
/*!********************************!*\
  !*** ./core/src/OC/get_set.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "get": function() { return /* binding */ get; },
/* harmony export */   "set": function() { return /* binding */ set; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var get = function get(context) {
  return function (name) {
    var namespaces = name.split('.');
    var tail = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
      context = context[namespaces[i]];
      if (!context) {
        return false;
      }
    }
    return context[tail];
  };
};

/**
 * Set a variable by name
 *
 * @param {string} context context
 * @return {Function} setter
 * @deprecated 19.0.0 use https://lodash.com/docs#set
 */
var set = function set(context) {
  return function (name, value) {
    var namespaces = name.split('.');
    var tail = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
      if (!context[namespaces[i]]) {
        context[namespaces[i]] = {};
      }
      context = context[namespaces[i]];
    }
    context[tail] = value;
    return value;
  };
};

/***/ }),

/***/ "./core/src/OC/host.js":
/*!*****************************!*\
  !*** ./core/src/OC/host.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getHost": function() { return /* binding */ getHost; },
/* harmony export */   "getHostName": function() { return /* binding */ getHostName; },
/* harmony export */   "getPort": function() { return /* binding */ getPort; },
/* harmony export */   "getProtocol": function() { return /* binding */ getProtocol; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var getProtocol = function getProtocol() {
  return window.location.protocol.split(':')[0];
};

/**
 * Returns the host used to access this Nextcloud instance
 * Host is sometimes the same as the hostname but now always.
 *
 * Examples:
 * http://example.com => example.com
 * https://example.com => example.com
 * http://example.com:8080 => example.com:8080
 *
 * @return {string} host
 *
 * @since 8.2.0
 * @deprecated 17.0.0 use window.location.host directly
 */
var getHost = function getHost() {
  return window.location.host;
};

/**
 * Returns the hostname used to access this Nextcloud instance
 * The hostname is always stripped of the port
 *
 * @return {string} hostname
 * @since 9.0.0
 * @deprecated 17.0.0 use window.location.hostname directly
 */
var getHostName = function getHostName() {
  return window.location.hostname;
};

/**
 * Returns the port number used to access this Nextcloud instance
 *
 * @return {number} port number
 *
 * @since 8.2.0
 * @deprecated 17.0.0 use window.location.port directly
 */
var getPort = function getPort() {
  return window.location.port;
};

/***/ }),

/***/ "./core/src/OC/index.js":
/*!******************************!*\
  !*** ./core/src/OC/index.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _legacy_loader__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./legacy-loader */ "./core/src/OC/legacy-loader.js");
/* harmony import */ var _xhr_error__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./xhr-error */ "./core/src/OC/xhr-error.js");
/* harmony import */ var _apps__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./apps */ "./core/src/OC/apps.js");
/* harmony import */ var _appconfig__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./appconfig */ "./core/src/OC/appconfig.js");
/* harmony import */ var _appsettings__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./appsettings */ "./core/src/OC/appsettings.js");
/* harmony import */ var _appswebroots__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./appswebroots */ "./core/src/OC/appswebroots.js");
/* harmony import */ var _backbone__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./backbone */ "./core/src/OC/backbone.js");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.js");
/* harmony import */ var _query_string__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./query-string */ "./core/src/OC/query-string.js");
/* harmony import */ var _config__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./config */ "./core/src/OC/config.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./constants */ "./core/src/OC/constants.js");
/* harmony import */ var _contactsmenu__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./contactsmenu */ "./core/src/OC/contactsmenu.js");
/* harmony import */ var _currentuser__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./currentuser */ "./core/src/OC/currentuser.js");
/* harmony import */ var _dialogs__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./dialogs */ "./core/src/OC/dialogs.js");
/* harmony import */ var _eventsource__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./eventsource */ "./core/src/OC/eventsource.js");
/* harmony import */ var _get_set__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./get_set */ "./core/src/OC/get_set.js");
/* harmony import */ var _capabilities__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./capabilities */ "./core/src/OC/capabilities.js");
/* harmony import */ var _host__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./host */ "./core/src/OC/host.js");
/* harmony import */ var _requesttoken__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./requesttoken */ "./core/src/OC/requesttoken.js");
/* harmony import */ var _menu__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./menu */ "./core/src/OC/menu.js");
/* harmony import */ var _admin__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./admin */ "./core/src/OC/admin.js");
/* harmony import */ var _l10n__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./l10n */ "./core/src/OC/l10n.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _routing__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./routing */ "./core/src/OC/routing.js");
/* harmony import */ var _msg__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./msg */ "./core/src/OC/msg.js");
/* harmony import */ var _notification__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./notification */ "./core/src/OC/notification.js");
/* harmony import */ var _password_confirmation__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./password-confirmation */ "./core/src/OC/password-confirmation.js");
/* harmony import */ var _plugins__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./plugins */ "./core/src/OC/plugins.js");
/* harmony import */ var _theme__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./theme */ "./core/src/OC/theme.js");
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./util */ "./core/src/OC/util.js");
/* harmony import */ var _debug__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./debug */ "./core/src/OC/debug.js");
/* harmony import */ var _navigation__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./navigation */ "./core/src/OC/navigation.js");
/* harmony import */ var _webroot__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./webroot */ "./core/src/OC/webroot.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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





































/** @namespace OC */
/* harmony default export */ __webpack_exports__["default"] = ({
  /*
   * Constants
   */
  coreApps: _constants__WEBPACK_IMPORTED_MODULE_11__.coreApps,
  menuSpeed: _constants__WEBPACK_IMPORTED_MODULE_11__.menuSpeed,
  PERMISSION_ALL: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_ALL,
  PERMISSION_CREATE: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_CREATE,
  PERMISSION_DELETE: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_DELETE,
  PERMISSION_NONE: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_NONE,
  PERMISSION_READ: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_READ,
  PERMISSION_SHARE: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_SHARE,
  PERMISSION_UPDATE: _constants__WEBPACK_IMPORTED_MODULE_11__.PERMISSION_UPDATE,
  TAG_FAVORITE: _constants__WEBPACK_IMPORTED_MODULE_11__.TAG_FAVORITE,
  /*
   * Deprecated helpers to be removed
   */
  /**
   * Check if a user file is allowed to be handled.
   *
   * @param {string} file to check
   * @return {boolean}
   * @deprecated 17.0.0
   */
  fileIsBlacklisted: function fileIsBlacklisted(file) {
    return !!file.match(_config__WEBPACK_IMPORTED_MODULE_10__["default"].blacklist_files_regex);
  },
  addScript: _legacy_loader__WEBPACK_IMPORTED_MODULE_1__.addScript,
  addStyle: _legacy_loader__WEBPACK_IMPORTED_MODULE_1__.addStyle,
  Apps: _apps__WEBPACK_IMPORTED_MODULE_3__["default"],
  AppConfig: _appconfig__WEBPACK_IMPORTED_MODULE_4__.AppConfig,
  appConfig: _appconfig__WEBPACK_IMPORTED_MODULE_4__.appConfig,
  appSettings: _appsettings__WEBPACK_IMPORTED_MODULE_5__.appSettings,
  appswebroots: _appswebroots__WEBPACK_IMPORTED_MODULE_6__["default"],
  Backbone: _backbone__WEBPACK_IMPORTED_MODULE_7__["default"],
  ContactsMenu: _contactsmenu__WEBPACK_IMPORTED_MODULE_12__["default"],
  config: _config__WEBPACK_IMPORTED_MODULE_10__["default"],
  /**
   * Currently logged in user or null if none
   *
   * @type {string}
   * @deprecated use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
   */
  currentUser: _currentuser__WEBPACK_IMPORTED_MODULE_13__.currentUser,
  dialogs: _dialogs__WEBPACK_IMPORTED_MODULE_14__["default"],
  EventSource: _eventsource__WEBPACK_IMPORTED_MODULE_15__["default"],
  /**
   * Returns the currently logged in user or null if there is no logged in
   * user (public page mode)
   *
   * @since 9.0.0
   * @deprecated 19.0.0 use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
   */
  getCurrentUser: _currentuser__WEBPACK_IMPORTED_MODULE_13__.getCurrentUser,
  isUserAdmin: _admin__WEBPACK_IMPORTED_MODULE_21__.isUserAdmin,
  L10N: _l10n__WEBPACK_IMPORTED_MODULE_22__["default"],
  /**
   * Ajax error handlers
   *
   * @todo remove from here and keep internally -> requires new tests
   */
  _ajaxConnectionLostHandler: _xhr_error__WEBPACK_IMPORTED_MODULE_2__.ajaxConnectionLostHandler,
  _processAjaxError: _xhr_error__WEBPACK_IMPORTED_MODULE_2__.processAjaxError,
  registerXHRForErrorProcessing: _xhr_error__WEBPACK_IMPORTED_MODULE_2__.registerXHRForErrorProcessing,
  /**
   * Capabilities
   *
   * @type {Array}
   * @deprecated 20.0.0 use @nextcloud/capabilities instead
   */
  getCapabilities: _capabilities__WEBPACK_IMPORTED_MODULE_17__.getCapabilities,
  /*
   * Legacy menu helpers
   */
  hideMenus: _menu__WEBPACK_IMPORTED_MODULE_20__.hideMenus,
  registerMenu: _menu__WEBPACK_IMPORTED_MODULE_20__.registerMenu,
  showMenu: _menu__WEBPACK_IMPORTED_MODULE_20__.showMenu,
  unregisterMenu: _menu__WEBPACK_IMPORTED_MODULE_20__.unregisterMenu,
  /*
   * Path helpers
   */
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  basename: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__.basename,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  encodePath: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__.encodePath,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  dirname: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__.dirname,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  isSamePath: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__.isSamePath,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  joinPaths: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_8__.joinPaths,
  /**
   * Host (url) helpers
   */
  getHost: _host__WEBPACK_IMPORTED_MODULE_18__.getHost,
  getHostName: _host__WEBPACK_IMPORTED_MODULE_18__.getHostName,
  getPort: _host__WEBPACK_IMPORTED_MODULE_18__.getPort,
  getProtocol: _host__WEBPACK_IMPORTED_MODULE_18__.getProtocol,
  /**
   * @deprecated 20.0.0 use `getCanonicalLocale` from https://www.npmjs.com/package/@nextcloud/l10n
   */
  getCanonicalLocale: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_23__.getCanonicalLocale,
  getLocale: _l10n__WEBPACK_IMPORTED_MODULE_22__.getLocale,
  getLanguage: _l10n__WEBPACK_IMPORTED_MODULE_22__.getLanguage,
  /**
   * Loads translations for the given app asynchronously.
   *
   * @param {string} app app name
   * @param {Function} callback callback to call after loading
   * @return {Promise}
   * @deprecated 17.0.0 use OC.L10N.load instead
   */
  addTranslations: _l10n__WEBPACK_IMPORTED_MODULE_22__["default"].load,
  /**
   * Query string helpers
   */
  buildQueryString: _query_string__WEBPACK_IMPORTED_MODULE_9__.build,
  parseQueryString: _query_string__WEBPACK_IMPORTED_MODULE_9__.parse,
  msg: _msg__WEBPACK_IMPORTED_MODULE_26__["default"],
  Notification: _notification__WEBPACK_IMPORTED_MODULE_27__["default"],
  PasswordConfirmation: _password_confirmation__WEBPACK_IMPORTED_MODULE_28__["default"],
  Plugins: _plugins__WEBPACK_IMPORTED_MODULE_29__["default"],
  theme: _theme__WEBPACK_IMPORTED_MODULE_30__.theme,
  Util: _util__WEBPACK_IMPORTED_MODULE_31__["default"],
  debug: _debug__WEBPACK_IMPORTED_MODULE_32__.debug,
  /**
   * @deprecated 19.0.0 use `generateFilePath` from https://www.npmjs.com/package/@nextcloud/router
   */
  filePath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.generateFilePath,
  /**
   * @deprecated 19.0.0 use `generateUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  generateUrl: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.generateUrl,
  /**
   * @deprecated 19.0.0 use https://lodash.com/docs#get
   */
  get: (0,_get_set__WEBPACK_IMPORTED_MODULE_16__.get)(window),
  /**
   * @deprecated 19.0.0 use https://lodash.com/docs#set
   */
  set: (0,_get_set__WEBPACK_IMPORTED_MODULE_16__.set)(window),
  /**
   * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  getRootPath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.getRootUrl,
  /**
   * @deprecated 19.0.0 use `imagePath` from https://www.npmjs.com/package/@nextcloud/router
   */
  imagePath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.imagePath,
  redirect: _navigation__WEBPACK_IMPORTED_MODULE_33__.redirect,
  reload: _navigation__WEBPACK_IMPORTED_MODULE_33__.reload,
  requestToken: (0,_requesttoken__WEBPACK_IMPORTED_MODULE_19__.getToken)(),
  /**
   * @deprecated 19.0.0 use `linkTo` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkTo: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.linkTo,
  /**
   * @param {string} service service name
   * @param {number} version OCS API version
   * @return {string} OCS API base path
   * @deprecated 19.0.0 use `generateOcsUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkToOCS: function linkToOCS(service, version) {
    return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.generateOcsUrl)(service, {}, {
      ocsVersion: version || 1
    }) + '/';
  },
  /**
   * @deprecated 19.0.0 use `generateRemoteUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkToRemote: _nextcloud_router__WEBPACK_IMPORTED_MODULE_24__.generateRemoteUrl,
  linkToRemoteBase: _routing__WEBPACK_IMPORTED_MODULE_25__.linkToRemoteBase,
  /**
   * Relative path to Nextcloud root.
   * For example: "/nextcloud"
   *
   * @type {string}
   *
   * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
   * @see OC#getRootPath
   */
  webroot: _webroot__WEBPACK_IMPORTED_MODULE_34__["default"]
});

// Keep the request token prop in sync
(0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('csrf-token-update', function (e) {
  OC.requestToken = e.token;

  // Logging might help debug (Sentry) issues
  console.info('OC.requestToken changed', e.token);
});

/***/ }),

/***/ "./core/src/OC/l10n-registry.js":
/*!**************************************!*\
  !*** ./core/src/OC/l10n-registry.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getAppTranslations": function() { return /* binding */ getAppTranslations; },
/* harmony export */   "hasAppTranslations": function() { return /* binding */ hasAppTranslations; },
/* harmony export */   "registerAppTranslations": function() { return /* binding */ registerAppTranslations; },
/* harmony export */   "unregisterAppTranslations": function() { return /* binding */ unregisterAppTranslations; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

// This var is global because it's shared across webpack bundles
window._oc_l10n_registry_translations = window._oc_l10n_registry_translations || {};
window._oc_l10n_registry_plural_functions = window._oc_l10n_registry_plural_functions || {};

/**
 * @param {string} appId the app id
 * @param {object} translations the translations list
 * @param {Function} pluralFunction the translations list
 */
var register = function register(appId, translations, pluralFunction) {
  window._oc_l10n_registry_translations[appId] = translations;
  window._oc_l10n_registry_plural_functions[appId] = pluralFunction;
};

/**
 * @param {string} appId the app id
 * @param {object} translations the translations list
 * @param {Function} pluralFunction the translations list
 */
var extend = function extend(appId, translations, pluralFunction) {
  window._oc_l10n_registry_translations[appId] = Object.assign(window._oc_l10n_registry_translations[appId], translations);
  window._oc_l10n_registry_plural_functions[appId] = pluralFunction;
};

/**
 * @param {string} appId the app id
 * @param {object} translations the translations list
 * @param {Function} pluralFunction the translations list
 */
var registerAppTranslations = function registerAppTranslations(appId, translations, pluralFunction) {
  if (!hasAppTranslations(appId)) {
    register(appId, translations, pluralFunction);
  } else {
    extend(appId, translations, pluralFunction);
  }
};

/**
 * @param {string} appId the app id
 */
var unregisterAppTranslations = function unregisterAppTranslations(appId) {
  delete window._oc_l10n_registry_translations[appId];
  delete window._oc_l10n_registry_plural_functions[appId];
};

/**
 * @param {string} appId the app id
 * @return {boolean}
 */
var hasAppTranslations = function hasAppTranslations(appId) {
  return window._oc_l10n_registry_translations[appId] !== undefined && window._oc_l10n_registry_plural_functions[appId] !== undefined;
};

/**
 * @param {string} appId the app id
 * @return {object}
 */
var getAppTranslations = function getAppTranslations(appId) {
  return {
    translations: window._oc_l10n_registry_translations[appId] || {},
    pluralFunction: window._oc_l10n_registry_plural_functions[appId]
  };
};

/***/ }),

/***/ "./core/src/OC/l10n.js":
/*!*****************************!*\
  !*** ./core/src/OC/l10n.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getLanguage": function() { return /* binding */ getLanguage; },
/* harmony export */   "getLocale": function() { return /* binding */ getLocale; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var handlebars__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! handlebars */ "./node_modules/handlebars/dist/cjs/handlebars.runtime.js");
/* harmony import */ var handlebars__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(handlebars__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash_fp_identity__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! lodash/fp/identity */ "./node_modules/lodash/fp/identity.js");
/* harmony import */ var lodash_fp_identity__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash_fp_identity__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/* harmony import */ var _l10n_registry__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./l10n-registry */ "./core/src/OC/l10n-registry.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * L10N namespace with localization functions.
 *
 * @namespace OC.L10n
 */
var L10n = {
  /**
   * Load an app's translation bundle if not loaded already.
   *
   * @param {string} appName name of the app
   * @param {Function} callback callback to be called when
   * the translations are loaded
   * @return {Promise} promise
   */
  load: function load(appName, callback) {
    // already available ?
    if ((0,_l10n_registry__WEBPACK_IMPORTED_MODULE_8__.hasAppTranslations)(appName) || _index__WEBPACK_IMPORTED_MODULE_7__["default"].getLocale() === 'en') {
      var deferred = jquery__WEBPACK_IMPORTED_MODULE_1___default().Deferred();
      var promise = deferred.promise();
      promise.then(callback);
      deferred.resolve();
      return promise;
    }
    var self = this;
    var url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateFilePath)(appName, 'l10n', _index__WEBPACK_IMPORTED_MODULE_7__["default"].getLocale() + '.json');

    // load JSON translation bundle per AJAX
    return jquery__WEBPACK_IMPORTED_MODULE_1___default().get(url).then(function (result) {
      if (result.translations) {
        self.register(appName, result.translations, result.pluralForm);
      }
    }).then(callback);
  },
  /**
   * Register an app's translation bundle.
   *
   * @param {string} appName name of the app
   * @param {Object<string, string>} bundle bundle
   */
  register: function register(appName, bundle) {
    (0,_l10n_registry__WEBPACK_IMPORTED_MODULE_8__.registerAppTranslations)(appName, bundle, this._getPlural);
  },
  /**
   * @private
   */
  _unregister: _l10n_registry__WEBPACK_IMPORTED_MODULE_8__.unregisterAppTranslations,
  /**
   * Translate a string
   *
   * @param {string} app the id of the app for which to translate the string
   * @param {string} text the string to translate
   * @param {object} [vars] map of placeholder key to value
   * @param {number} [count] number to replace %n with
   * @param {Array} [options] options array
   * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
   * @param {boolean} [options.sanitize=true] enable/disable sanitization (by default enabled)
   * @return {string}
   */
  translate: function translate(app, text, vars, count, options) {
    var defaultOptions = {
      escape: true,
      sanitize: true
    };
    var allOptions = options || {};
    underscore__WEBPACK_IMPORTED_MODULE_0__["default"].defaults(allOptions, defaultOptions);
    var optSanitize = allOptions.sanitize ? (dompurify__WEBPACK_IMPORTED_MODULE_2___default().sanitize) : (lodash_fp_identity__WEBPACK_IMPORTED_MODULE_4___default());
    var optEscape = allOptions.escape ? (escape_html__WEBPACK_IMPORTED_MODULE_5___default()) : (lodash_fp_identity__WEBPACK_IMPORTED_MODULE_4___default());

    // TODO: cache this function to avoid inline recreation
    // of the same function over and over again in case
    // translate() is used in a loop
    var _build = function _build(text, vars, count) {
      return text.replace(/%n/g, count).replace(/{([^{}]*)}/g, function (a, b) {
        var r = vars[b];
        if (typeof r === 'string' || typeof r === 'number') {
          return optSanitize(optEscape(r));
        } else {
          return optSanitize(a);
        }
      });
    };
    var translation = text;
    var bundle = (0,_l10n_registry__WEBPACK_IMPORTED_MODULE_8__.getAppTranslations)(app);
    var value = bundle.translations[text];
    if (typeof value !== 'undefined') {
      translation = value;
    }
    if (_typeof(vars) === 'object' || count !== undefined) {
      return optSanitize(_build(translation, vars, count));
    } else {
      return optSanitize(translation);
    }
  },
  /**
   * Translate a plural string
   *
   * @param {string} app the id of the app for which to translate the string
   * @param {string} textSingular the string to translate for exactly one object
   * @param {string} textPlural the string to translate for n objects
   * @param {number} count number to determine whether to use singular or plural
   * @param {object} [vars] map of placeholder key to value
   * @param {Array} [options] options array
   * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
   * @return {string} Translated string
   */
  translatePlural: function translatePlural(app, textSingular, textPlural, count, vars, options) {
    var identifier = '_' + textSingular + '_::_' + textPlural + '_';
    var bundle = (0,_l10n_registry__WEBPACK_IMPORTED_MODULE_8__.getAppTranslations)(app);
    var value = bundle.translations[identifier];
    if (typeof value !== 'undefined') {
      var translation = value;
      if (jquery__WEBPACK_IMPORTED_MODULE_1___default().isArray(translation)) {
        var plural = bundle.pluralFunction(count);
        return this.translate(app, translation[plural], vars, count, options);
      }
    }
    if (count === 1) {
      return this.translate(app, textSingular, vars, count, options);
    } else {
      return this.translate(app, textPlural, vars, count, options);
    }
  },
  /**
   * The plural function taken from symfony
   *
   * @param {number} number the number of elements
   * @return {number}
   * @private
   */
  _getPlural: function _getPlural(number) {
    var language = _index__WEBPACK_IMPORTED_MODULE_7__["default"].getLanguage();
    if (language === 'pt-BR') {
      // temporary set a locale for brazilian
      language = 'xbr';
    }
    if (typeof language === 'undefined' || language === '') {
      return number === 1 ? 0 : 1;
    }
    if (language.length > 3) {
      language = language.substring(0, language.lastIndexOf('-'));
    }

    /*
     * The plural rules are derived from code of the Zend Framework (2010-09-25),
     * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */
    switch (language) {
      case 'az':
      case 'bo':
      case 'dz':
      case 'id':
      case 'ja':
      case 'jv':
      case 'ka':
      case 'km':
      case 'kn':
      case 'ko':
      case 'ms':
      case 'th':
      case 'tr':
      case 'vi':
      case 'zh':
        return 0;
      case 'af':
      case 'bn':
      case 'bg':
      case 'ca':
      case 'da':
      case 'de':
      case 'el':
      case 'en':
      case 'eo':
      case 'es':
      case 'et':
      case 'eu':
      case 'fa':
      case 'fi':
      case 'fo':
      case 'fur':
      case 'fy':
      case 'gl':
      case 'gu':
      case 'ha':
      case 'he':
      case 'hu':
      case 'is':
      case 'it':
      case 'ku':
      case 'lb':
      case 'ml':
      case 'mn':
      case 'mr':
      case 'nah':
      case 'nb':
      case 'ne':
      case 'nl':
      case 'nn':
      case 'no':
      case 'oc':
      case 'om':
      case 'or':
      case 'pa':
      case 'pap':
      case 'ps':
      case 'pt':
      case 'so':
      case 'sq':
      case 'sv':
      case 'sw':
      case 'ta':
      case 'te':
      case 'tk':
      case 'ur':
      case 'zu':
        return number === 1 ? 0 : 1;
      case 'am':
      case 'bh':
      case 'fil':
      case 'fr':
      case 'gun':
      case 'hi':
      case 'hy':
      case 'ln':
      case 'mg':
      case 'nso':
      case 'xbr':
      case 'ti':
      case 'wa':
        return number === 0 || number === 1 ? 0 : 1;
      case 'be':
      case 'bs':
      case 'hr':
      case 'ru':
      case 'sh':
      case 'sr':
      case 'uk':
        return number % 10 === 1 && number % 100 !== 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
      case 'cs':
      case 'sk':
        return number === 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;
      case 'ga':
        return number === 1 ? 0 : number === 2 ? 1 : 2;
      case 'lt':
        return number % 10 === 1 && number % 100 !== 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
      case 'sl':
        return number % 100 === 1 ? 0 : number % 100 === 2 ? 1 : number % 100 === 3 || number % 100 === 4 ? 2 : 3;
      case 'mk':
        return number % 10 === 1 ? 0 : 1;
      case 'mt':
        return number === 1 ? 0 : number === 0 || number % 100 > 1 && number % 100 < 11 ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;
      case 'lv':
        return number === 0 ? 0 : number % 10 === 1 && number % 100 !== 11 ? 1 : 2;
      case 'pl':
        return number === 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;
      case 'cy':
        return number === 1 ? 0 : number === 2 ? 1 : number === 8 || number === 11 ? 2 : 3;
      case 'ro':
        return number === 1 ? 0 : number === 0 || number % 100 > 0 && number % 100 < 20 ? 1 : 2;
      case 'ar':
        return number === 0 ? 0 : number === 1 ? 1 : number === 2 ? 2 : number % 100 >= 3 && number % 100 <= 10 ? 3 : number % 100 >= 11 && number % 100 <= 99 ? 4 : 5;
      default:
        return 0;
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (L10n);

/**
 * Returns the user's locale
 *
 * @return {string} locale string
 */
var getLocale = function getLocale() {
  var _$$data;
  return (_$$data = jquery__WEBPACK_IMPORTED_MODULE_1___default()('html').data('locale')) !== null && _$$data !== void 0 ? _$$data : 'en';
};

/**
 * Returns the user's language
 *
 * @return {string} language string
 */
var getLanguage = function getLanguage() {
  return jquery__WEBPACK_IMPORTED_MODULE_1___default()('html').prop('lang');
};
handlebars__WEBPACK_IMPORTED_MODULE_3___default().registerHelper('t', function (app, text) {
  return L10n.translate(app, text);
});

/***/ }),

/***/ "./core/src/OC/legacy-loader.js":
/*!**************************************!*\
  !*** ./core/src/OC/legacy-loader.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "addScript": function() { return /* binding */ addScript; },
/* harmony export */   "addStyle": function() { return /* binding */ addStyle; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

/** @typedef {import('jquery')} jQuery */


var loadedScripts = {};
var loadedStyles = [];

/**
 * Load a script for the server and load it. If the script is already loaded,
 * the event handler will be called directly
 *
 * @param {string} app the app id to which the script belongs
 * @param {string} script the filename of the script
 * @param {Function} ready event handler to be called when the script is loaded
 * @return {jQuery.Deferred}
 * @deprecated 16.0.0 Use OCP.Loader.loadScript
 */
var addScript = function addScript(app, script, ready) {
  console.warn('OC.addScript is deprecated, use OCP.Loader.loadScript instead');
  var deferred;
  var path = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateFilePath)(app, 'js', script + '.js');
  if (!loadedScripts[path]) {
    deferred = jquery__WEBPACK_IMPORTED_MODULE_0___default().Deferred();
    jquery__WEBPACK_IMPORTED_MODULE_0___default().getScript(path, function () {
      return deferred.resolve();
    });
    loadedScripts[path] = deferred;
  } else {
    if (ready) {
      ready();
    }
  }
  return loadedScripts[path];
};

/**
 * Loads a CSS file
 *
 * @param {string} app the app id to which the css style belongs
 * @param {string} style the filename of the css file
 * @deprecated 16.0.0 Use OCP.Loader.loadStylesheet
 */
var addStyle = function addStyle(app, style) {
  console.warn('OC.addStyle is deprecated, use OCP.Loader.loadStylesheet instead');
  var path = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateFilePath)(app, 'css', style + '.css');
  if (loadedStyles.indexOf(path) === -1) {
    loadedStyles.push(path);
    if (document.createStyleSheet) {
      document.createStyleSheet(path);
    } else {
      style = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<link rel="stylesheet" type="text/css" href="' + path + '"/>');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()('head').append(style);
    }
  }
};

/***/ }),

/***/ "./core/src/OC/menu.js":
/*!*****************************!*\
  !*** ./core/src/OC/menu.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "currentMenu": function() { return /* binding */ currentMenu; },
/* harmony export */   "currentMenuToggle": function() { return /* binding */ currentMenuToggle; },
/* harmony export */   "hideMenus": function() { return /* binding */ hideMenus; },
/* harmony export */   "registerMenu": function() { return /* binding */ registerMenu; },
/* harmony export */   "showMenu": function() { return /* binding */ showMenu; },
/* harmony export */   "unregisterMenu": function() { return /* binding */ unregisterMenu; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./core/src/OC/constants.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
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


/** @typedef {import('jquery')} jQuery */


var currentMenu = null;
var currentMenuToggle = null;

/**
 * For menu toggling
 *
 * @param {jQuery} $toggle the toggle element
 * @param {jQuery} $menuEl the menu container element
 * @param {Function | undefined} toggle callback invoked everytime the menu is opened
 * @param {boolean} headerMenu is this a top right header menu?
 * @return {void}
 */
var registerMenu = function registerMenu($toggle, $menuEl, toggle, headerMenu) {
  $menuEl.addClass('menu');
  var isClickableElement = $toggle.prop('tagName') === 'A' || $toggle.prop('tagName') === 'BUTTON';

  // On link and button, the enter key trigger a click event
  // Only use the click to avoid two fired events
  $toggle.on(isClickableElement ? 'click.menu' : 'click.menu keyup.menu', function (event) {
    // prevent the link event (append anchor to URL)
    event.preventDefault();

    // allow enter key as a trigger
    if (event.key && event.key !== 'Enter') {
      return;
    }
    if ($menuEl.is(currentMenu)) {
      hideMenus();
      return;
    } else if (currentMenu) {
      // another menu was open?
      // close it
      hideMenus();
    }
    if (headerMenu === true) {
      $menuEl.parent().addClass('openedMenu');
    }

    // Set menu to expanded
    $toggle.attr('aria-expanded', true);
    $menuEl.slideToggle(_constants__WEBPACK_IMPORTED_MODULE_2__.menuSpeed, toggle);
    currentMenu = $menuEl;
    currentMenuToggle = $toggle;
  });
};

/**
 * Unregister a previously registered menu
 *
 * @param {jQuery} $toggle the toggle element
 * @param {jQuery} $menuEl the menu container element
 */
var unregisterMenu = function unregisterMenu($toggle, $menuEl) {
  // close menu if opened
  if ($menuEl.is(currentMenu)) {
    hideMenus();
  }
  $toggle.off('click.menu').removeClass('menutoggle');
  $menuEl.removeClass('menu');
};

/**
 * Hides any open menus
 *
 * @param {Function} complete callback when the hiding animation is done
 */
var hideMenus = function hideMenus(complete) {
  if (currentMenu) {
    var lastMenu = currentMenu;
    currentMenu.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('beforeHide'));
    currentMenu.slideUp(_constants__WEBPACK_IMPORTED_MODULE_2__.menuSpeed, function () {
      lastMenu.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('afterHide'));
      if (complete) {
        complete.apply(this, arguments);
      }
    });
  }

  // Set menu to closed
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('.menutoggle').attr('aria-expanded', false);
  if (currentMenuToggle) {
    currentMenuToggle.attr('aria-expanded', false);
  }
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('.openedMenu').removeClass('openedMenu');
  currentMenu = null;
  currentMenuToggle = null;
};

/**
 * Shows a given element as menu
 *
 * @param {object} [$toggle=null] menu toggle
 * @param {object} $menuEl menu element
 * @param {Function} complete callback when the showing animation is done
 */
var showMenu = function showMenu($toggle, $menuEl, complete) {
  if ($menuEl.is(currentMenu)) {
    return;
  }
  hideMenus();
  currentMenu = $menuEl;
  currentMenuToggle = $toggle;
  $menuEl.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('beforeShow'));
  $menuEl.show();
  $menuEl.trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('afterShow'));
  // no animation
  if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(complete)) {
    complete();
  }
};

/***/ }),

/***/ "./core/src/OC/msg.js":
/*!****************************!*\
  !*** ./core/src/OC/msg.js ***!
  \****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author rakekniven <mark.ziegler@rakekniven.de>
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
 * A little class to manage a status field for a "saving" process.
 * It can be used to display a starting message (e.g. "Saving...") and then
 * replace it with a green success message or a red error message.
 *
 * @namespace OC.msg
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * Displayes a "Saving..." message in the given message placeholder
   *
   * @param {object} selector    Placeholder to display the message in
   */
  startSaving: function startSaving(selector) {
    this.startAction(selector, t('core', 'Saving …'));
  },
  /**
   * Displayes a custom message in the given message placeholder
   *
   * @param {object} selector    Placeholder to display the message in
   * @param {string} message    Plain text message to display (no HTML allowed)
   */
  startAction: function startAction(selector, message) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(selector).text(message).removeClass('success').removeClass('error').stop(true, true).show();
  },
  /**
   * Displayes an success/error message in the given selector
   *
   * @param {object} selector    Placeholder to display the message in
   * @param {object} response    Response of the server
   * @param {object} response.data    Data of the servers response
   * @param {string} response.data.message    Plain text message to display (no HTML allowed)
   * @param {string} response.status    is being used to decide whether the message
   * is displayed as an error/success
   */
  finishedSaving: function finishedSaving(selector, response) {
    this.finishedAction(selector, response);
  },
  /**
   * Displayes an success/error message in the given selector
   *
   * @param {object} selector    Placeholder to display the message in
   * @param {object} response    Response of the server
   * @param {object} response.data Data of the servers response
   * @param {string} response.data.message Plain text message to display (no HTML allowed)
   * @param {string} response.status is being used to decide whether the message
   * is displayed as an error/success
   */
  finishedAction: function finishedAction(selector, response) {
    if (response.status === 'success') {
      this.finishedSuccess(selector, response.data.message);
    } else {
      this.finishedError(selector, response.data.message);
    }
  },
  /**
   * Displayes an success message in the given selector
   *
   * @param {object} selector Placeholder to display the message in
   * @param {string} message Plain text success message to display (no HTML allowed)
   */
  finishedSuccess: function finishedSuccess(selector, message) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(selector).text(message).addClass('success').removeClass('error').stop(true, true).delay(3000).fadeOut(900).show();
  },
  /**
   * Displayes an error message in the given selector
   *
   * @param {object} selector Placeholder to display the message in
   * @param {string} message Plain text error message to display (no HTML allowed)
   */
  finishedError: function finishedError(selector, message) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(selector).text(message).addClass('error').removeClass('success').show();
  }
});

/***/ }),

/***/ "./core/src/OC/navigation.js":
/*!***********************************!*\
  !*** ./core/src/OC/navigation.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "redirect": function() { return /* binding */ redirect; },
/* harmony export */   "reload": function() { return /* binding */ reload; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var redirect = function redirect(targetURL) {
  window.location = targetURL;
};

/**
 * Reloads the current page
 *
 * @deprecated 17.0.0 use window.location.reload directly
 */
var reload = function reload() {
  window.location.reload();
};

/***/ }),

/***/ "./core/src/OC/notification.js":
/*!*************************************!*\
  !*** ./core/src/OC/notification.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author npmbuildbot[bot] "npmbuildbot[bot]@users.noreply.github.com"
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


/** @typedef {import('jquery')} jQuery */



/**
 * @todo Write documentation
 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package instead
 * @namespace OC.Notification
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  updatableNotification: null,
  getDefaultNotificationFunction: null,
  /**
   * @param {Function} callback callback function
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  setDefault: function setDefault(callback) {
    this.getDefaultNotificationFunction = callback;
  },
  /**
   * Hides a notification.
   *
   * If a row is given, only hide that one.
   * If no row is given, hide all notifications.
   *
   * @param {jQuery} [$row] notification row
   * @param {Function} [callback] callback
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  hide: function hide($row, callback) {
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction($row)) {
      // first arg is the callback
      callback = $row;
      $row = undefined;
    }
    if (!$row) {
      console.error('Missing argument $row in OC.Notification.hide() call, caller needs to be adjusted to only dismiss its own notification');
      return;
    }

    // remove the row directly
    $row.each(function () {
      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this)[0].toastify) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(this)[0].toastify.hideToast();
      } else {
        console.error('cannot hide toast because object is not set');
      }
      if (this === this.updatableNotification) {
        this.updatableNotification = null;
      }
    });
    if (callback) {
      callback.call();
    }
    if (this.getDefaultNotificationFunction) {
      this.getDefaultNotificationFunction();
    }
  },
  /**
   * Shows a notification as HTML without being sanitized before.
   * If you pass unsanitized user input this may lead to a XSS vulnerability.
   * Consider using show() instead of showHTML()
   *
   * @param {string} html Message to display
   * @param {object} [options] options
   * @param {string} [options.type] notification type
   * @param {number} [options.timeout=0] timeout value, defaults to 0 (permanent)
   * @return {jQuery} jQuery element for notification row
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  showHtml: function showHtml(html, options) {
    options = options || {};
    options.isHTML = true;
    options.timeout = !options.timeout ? _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.TOAST_PERMANENT_TIMEOUT : options.timeout;
    var toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showMessage)(html, options);
    toast.toastElement.toastify = toast;
    return jquery__WEBPACK_IMPORTED_MODULE_1___default()(toast.toastElement);
  },
  /**
   * Shows a sanitized notification
   *
   * @param {string} text Message to display
   * @param {object} [options] options
   * @param {string} [options.type] notification type
   * @param {number} [options.timeout=0] timeout value, defaults to 0 (permanent)
   * @return {jQuery} jQuery element for notification row
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  show: function show(text, options) {
    var escapeHTML = function escapeHTML(text) {
      return text.toString().split('&').join('&amp;').split('<').join('&lt;').split('>').join('&gt;').split('"').join('&quot;').split('\'').join('&#039;');
    };
    options = options || {};
    options.timeout = !options.timeout ? _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.TOAST_PERMANENT_TIMEOUT : options.timeout;
    var toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showMessage)(escapeHTML(text), options);
    toast.toastElement.toastify = toast;
    return jquery__WEBPACK_IMPORTED_MODULE_1___default()(toast.toastElement);
  },
  /**
   * Updates (replaces) a sanitized notification.
   *
   * @param {string} text Message to display
   * @return {jQuery} JQuery element for notificaiton row
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  showUpdate: function showUpdate(text) {
    if (this.updatableNotification) {
      this.updatableNotification.hideToast();
    }
    this.updatableNotification = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showMessage)(text, {
      timeout: _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.TOAST_PERMANENT_TIMEOUT
    });
    this.updatableNotification.toastElement.toastify = this.updatableNotification;
    return jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.updatableNotification.toastElement);
  },
  /**
   * Shows a notification that disappears after x seconds, default is
   * 7 seconds
   *
   * @param {string} text Message to show
   * @param {Array} [options] options array
   * @param {number} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
   * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
   * @param {string} [options.type] notification type
   * @return {JQuery} the toast element
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  showTemporary: function showTemporary(text, options) {
    options = options || {};
    options.timeout = options.timeout || _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.TOAST_DEFAULT_TIMEOUT;
    var toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showMessage)(text, options);
    toast.toastElement.toastify = toast;
    return jquery__WEBPACK_IMPORTED_MODULE_1___default()(toast.toastElement);
  },
  /**
   * Returns whether a notification is hidden.
   *
   * @return {boolean}
   * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
   */
  isHidden: function isHidden() {
    return !jquery__WEBPACK_IMPORTED_MODULE_1___default()('#content').find('.toastify').length;
  }
});

/***/ }),

/***/ "./core/src/OC/password-confirmation.js":
/*!**********************************************!*\
  !*** ./core/src/OC/password-confirmation.js ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! moment */ "./node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * @namespace OC.PasswordConfirmation
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  callback: null,
  pageLoadTime: null,
  init: function init() {
    jquery__WEBPACK_IMPORTED_MODULE_1___default()('.password-confirm-required').on('click', underscore__WEBPACK_IMPORTED_MODULE_0__["default"].bind(this.requirePasswordConfirmation, this));
    this.pageLoadTime = moment__WEBPACK_IMPORTED_MODULE_2___default().now();
  },
  requiresPasswordConfirmation: function requiresPasswordConfirmation() {
    var serverTimeDiff = this.pageLoadTime - window.nc_pageLoad * 1000;
    var timeSinceLogin = moment__WEBPACK_IMPORTED_MODULE_2___default().now() - (serverTimeDiff + window.nc_lastLogin * 1000);

    // if timeSinceLogin > 30 minutes and user backend allows password confirmation
    return window.backendAllowsPasswordConfirmation && timeSinceLogin > 30 * 60 * 1000;
  },
  /**
   * @param {Function} callback success callback function
   * @param {object} options options
   * @param {Function} rejectCallback error callback function
   */
  requirePasswordConfirmation: function requirePasswordConfirmation(callback, options, rejectCallback) {
    options = typeof options !== 'undefined' ? options : {};
    var defaults = {
      title: t('core', 'Authentication required'),
      text: t('core', 'This action requires you to confirm your password'),
      confirm: t('core', 'Confirm'),
      label: t('core', 'Password'),
      error: ''
    };
    var config = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend(defaults, options);
    var self = this;
    if (this.requiresPasswordConfirmation()) {
      _index__WEBPACK_IMPORTED_MODULE_4__["default"].dialogs.prompt(config.text, config.title, function (result, password) {
        if (result && password !== '') {
          self._confirmPassword(password, config);
        } else if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(rejectCallback)) {
          rejectCallback();
        }
      }, true, config.label, true).then(function () {
        var $dialog = jquery__WEBPACK_IMPORTED_MODULE_1___default()('.oc-dialog:visible');
        $dialog.find('.ui-icon').remove();
        $dialog.addClass('password-confirmation');
        if (config.error !== '') {
          var $error = jquery__WEBPACK_IMPORTED_MODULE_1___default()('<p></p>').addClass('msg warning').text(config.error);
          $dialog.find('.oc-dialog-content').append($error);
        }
        $dialog.find('.oc-dialog-buttonrow').addClass('aside');
        var $buttons = $dialog.find('button');
        $buttons.eq(0).hide();
        $buttons.eq(1).text(config.confirm);
      });
    }
    this.callback = callback;
  },
  _confirmPassword: function _confirmPassword(password, config) {
    var self = this;
    jquery__WEBPACK_IMPORTED_MODULE_1___default().ajax({
      url: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('/login/confirm'),
      data: {
        password: password
      },
      type: 'POST',
      success: function success(response) {
        window.nc_lastLogin = response.lastLogin;
        if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isFunction(self.callback)) {
          self.callback();
        }
      },
      error: function error() {
        config.error = t('core', 'Failed to authenticate, try again');
        _index__WEBPACK_IMPORTED_MODULE_4__["default"].PasswordConfirmation.requirePasswordConfirmation(self.callback, config);
      }
    });
  }
});

/***/ }),

/***/ "./core/src/OC/plugins.js":
/*!********************************!*\
  !*** ./core/src/OC/plugins.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * @type {Array.<OC.Plugin>}
   */
  _plugins: {},
  /**
   * Register plugin
   *
   * @param {string} targetName app name / class name to hook into
   * @param {OC.Plugin} plugin plugin
   */
  register: function register(targetName, plugin) {
    var plugins = this._plugins[targetName];
    if (!plugins) {
      plugins = this._plugins[targetName] = [];
    }
    plugins.push(plugin);
  },
  /**
   * Returns all plugin registered to the given target
   * name / app name / class name.
   *
   * @param {string} targetName app name / class name to hook into
   * @return {Array.<OC.Plugin>} array of plugins
   */
  getPlugins: function getPlugins(targetName) {
    return this._plugins[targetName] || [];
  },
  /**
   * Call attach() on all plugins registered to the given target name.
   *
   * @param {string} targetName app name / class name
   * @param {object} targetObject to be extended
   * @param {object} [options] options
   */
  attach: function attach(targetName, targetObject, options) {
    var plugins = this.getPlugins(targetName);
    for (var i = 0; i < plugins.length; i++) {
      if (plugins[i].attach) {
        plugins[i].attach(targetObject, options);
      }
    }
  },
  /**
   * Call detach() on all plugins registered to the given target name.
   *
   * @param {string} targetName app name / class name
   * @param {object} targetObject to be extended
   * @param {object} [options] options
   */
  detach: function detach(targetName, targetObject, options) {
    var plugins = this.getPlugins(targetName);
    for (var i = 0; i < plugins.length; i++) {
      if (plugins[i].detach) {
        plugins[i].detach(targetObject, options);
      }
    }
  }
});

/***/ }),

/***/ "./core/src/OC/query-string.js":
/*!*************************************!*\
  !*** ./core/src/OC/query-string.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "build": function() { return /* binding */ build; },
/* harmony export */   "parse": function() { return /* binding */ parse; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * Parses a URL query string into a JS map
 *
 * @param {string} queryString query string in the format param1=1234&param2=abcde&param3=xyz
 * @return {Object<string, string>} map containing key/values matching the URL parameters
 */
var parse = function parse(queryString) {
  var pos;
  var components;
  var result = {};
  var key;
  if (!queryString) {
    return null;
  }
  pos = queryString.indexOf('?');
  if (pos >= 0) {
    queryString = queryString.substr(pos + 1);
  }
  var parts = queryString.replace(/\+/g, '%20').split('&');
  for (var i = 0; i < parts.length; i++) {
    // split on first equal sign
    var part = parts[i];
    pos = part.indexOf('=');
    if (pos >= 0) {
      components = [part.substr(0, pos), part.substr(pos + 1)];
    } else {
      // key only
      components = [part];
    }
    if (!components.length) {
      continue;
    }
    key = decodeURIComponent(components[0]);
    if (!key) {
      continue;
    }
    // if equal sign was there, return string
    if (components.length > 1) {
      result[key] = decodeURIComponent(components[1]);
    } else {
      // no equal sign => null value
      result[key] = null;
    }
  }
  return result;
};

/**
 * Builds a URL query from a JS map.
 *
 * @param {Object<string, string>} params map containing key/values matching the URL parameters
 * @return {string} String containing a URL query (without question) mark
 */
var build = function build(params) {
  if (!params) {
    return '';
  }
  return jquery__WEBPACK_IMPORTED_MODULE_0___default().map(params, function (value, key) {
    var s = encodeURIComponent(key);
    if (value !== null && typeof value !== 'undefined') {
      s += '=' + encodeURIComponent(value);
    }
    return s;
  }).join('&');
};

/***/ }),

/***/ "./core/src/OC/requesttoken.js":
/*!*************************************!*\
  !*** ./core/src/OC/requesttoken.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getToken": function() { return /* binding */ getToken; },
/* harmony export */   "manageToken": function() { return /* binding */ manageToken; },
/* harmony export */   "setToken": function() { return /* binding */ setToken; }
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * @private
 * @param {Document} global the document to read the initial value from
 * @param {Function} emit the function to invoke for every new token
 * @return {object}
 */
var manageToken = function manageToken(global, emit) {
  var token = global.getElementsByTagName('head')[0].getAttribute('data-requesttoken');
  return {
    getToken: function getToken() {
      return token;
    },
    setToken: function setToken(newToken) {
      token = newToken;
      emit('csrf-token-update', {
        token: token
      });
    }
  };
};
var manageFromDocument = manageToken(document, _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit);

/**
 * @return {string}
 */
var getToken = manageFromDocument.getToken;

/**
 * @param {string} newToken new token
 */
var setToken = manageFromDocument.setToken;

/***/ }),

/***/ "./core/src/OC/routing.js":
/*!********************************!*\
  !*** ./core/src/OC/routing.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "linkToRemoteBase": function() { return /* binding */ linkToRemoteBase; }
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * Creates a relative url for remote use
 *
 * @param {string} service id
 * @return {string} the url
 */
var linkToRemoteBase = function linkToRemoteBase(service) {
  return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.getRootUrl)() + '/remote.php/' + service;
};

/***/ }),

/***/ "./core/src/OC/theme.js":
/*!******************************!*\
  !*** ./core/src/OC/theme.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "theme": function() { return /* binding */ theme; }
/* harmony export */ });
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var theme = window._theme || {};

/***/ }),

/***/ "./core/src/OC/util-history.js":
/*!*************************************!*\
  !*** ./core/src/OC/util-history.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * Utility class for the history API,
 * includes fallback to using the URL hash when
 * the browser doesn't support the history API.
 *
 * @namespace OC.Util.History
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  _handlers: [],
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string
   * or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used,
   * using the params as query string
   * @param {boolean} [replace=false] whether to replace instead of pushing
   */
  _pushState: function _pushState(params, url, replace) {
    var strParams;
    if (typeof params === 'string') {
      strParams = params;
    } else {
      strParams = _index__WEBPACK_IMPORTED_MODULE_1__["default"].buildQueryString(params);
    }
    if (window.history.pushState) {
      url = url || location.pathname + '?' + strParams;
      // Workaround for bug with SVG and window.history.pushState on Firefox < 51
      // https://bugzilla.mozilla.org/show_bug.cgi?id=652991
      var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
      if (isFirefox && parseInt(navigator.userAgent.split('/').pop()) < 51) {
        var patterns = document.querySelectorAll('[fill^="url(#"], [stroke^="url(#"], [filter^="url(#invert"]');
        for (var i = 0, ii = patterns.length, pattern; i < ii; i++) {
          pattern = patterns[i];
          // eslint-disable-next-line no-self-assign
          pattern.style.fill = pattern.style.fill;
          // eslint-disable-next-line no-self-assign
          pattern.style.stroke = pattern.style.stroke;
          pattern.removeAttribute('filter');
          pattern.setAttribute('filter', 'url(#invert)');
        }
      }
      if (replace) {
        window.history.replaceState(params, '', url);
      } else {
        window.history.pushState(params, '', url);
      }
    } else {
      // use URL hash for IE8
      window.location.hash = '?' + strParams;
      // inhibit next onhashchange that just added itself
      // to the event queue
      this._cancelPop = true;
    }
  },
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used, using the params as query string
   */
  pushState: function pushState(params, url) {
    this._pushState(params, url, false);
  },
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string
   * or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used,
   * using the params as query string
   */
  replaceState: function replaceState(params, url) {
    this._pushState(params, url, true);
  },
  /**
   * Add a popstate handler
   *
   * @param {Function} handler handler
   */
  addOnPopStateHandler: function addOnPopStateHandler(handler) {
    this._handlers.push(handler);
  },
  /**
   * Parse a query string from the hash part of the URL.
   * (workaround for IE8 / IE9)
   *
   * @return {string}
   */
  _parseHashQuery: function _parseHashQuery() {
    var hash = window.location.hash;
    var pos = hash.indexOf('?');
    if (pos >= 0) {
      return hash.substr(pos + 1);
    }
    if (hash.length) {
      // remove hash sign
      return hash.substr(1);
    }
    return '';
  },
  _decodeQuery: function _decodeQuery(query) {
    return query.replace(/\+/g, ' ');
  },
  /**
   * Parse the query/search part of the URL.
   * Also try and parse it from the URL hash (for IE8)
   *
   * @return {object} map of parameters
   */
  parseUrlQuery: function parseUrlQuery() {
    var query = this._parseHashQuery();
    var params;
    // try and parse from URL hash first
    if (query) {
      params = _index__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(this._decodeQuery(query));
    }
    // else read from query attributes
    params = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend(params || {}, _index__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(this._decodeQuery(location.search)));
    return params || {};
  },
  _onPopState: function _onPopState(e) {
    if (this._cancelPop) {
      this._cancelPop = false;
      return;
    }
    var params;
    if (!this._handlers.length) {
      return;
    }
    params = e && e.state;
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isString(params)) {
      params = _index__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(params);
    } else if (!params) {
      params = this.parseUrlQuery() || {};
    }
    for (var i = 0; i < this._handlers.length; i++) {
      this._handlers[i](params);
    }
  }
});

/***/ }),

/***/ "./core/src/OC/util.js":
/*!*****************************!*\
  !*** ./core/src/OC/util.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! moment */ "./node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util_history__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util-history */ "./core/src/OC/util-history.js");
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.esm.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * @param {any} t -
 */
function chunkify(t) {
  // Adapted from http://my.opera.com/GreyWyvern/blog/show.dml/1671288
  var tz = [];
  var x = 0;
  var y = -1;
  var n = 0;
  var c;
  while (x < t.length) {
    c = t.charAt(x);
    // only include the dot in strings
    var m = !n && c === '.' || c >= '0' && c <= '9';
    if (m !== n) {
      // next chunk
      y++;
      tz[y] = '';
      n = m;
    }
    tz[y] += c;
    x++;
  }
  return tz;
}

/**
 * Utility functions
 *
 * @namespace OC.Util
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  History: _util_history__WEBPACK_IMPORTED_MODULE_1__["default"],
  /**
   * @deprecated use https://nextcloud.github.io/nextcloud-files/modules/_humanfilesize_.html#formatfilesize
   */
  humanFileSize: _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.formatFileSize,
  /**
   * Returns a file size in bytes from a humanly readable string
   * Makes 2kB to 2048.
   * Inspired by computerFileSize in helper.php
   *
   * @param  {string} string file size in human readable format
   * @return {number} or null if string could not be parsed
   *
   *
   */
  computerFileSize: function computerFileSize(string) {
    if (typeof string !== 'string') {
      return null;
    }
    var s = string.toLowerCase().trim();
    var bytes = null;
    var bytesArray = {
      b: 1,
      k: 1024,
      kb: 1024,
      mb: 1024 * 1024,
      m: 1024 * 1024,
      gb: 1024 * 1024 * 1024,
      g: 1024 * 1024 * 1024,
      tb: 1024 * 1024 * 1024 * 1024,
      t: 1024 * 1024 * 1024 * 1024,
      pb: 1024 * 1024 * 1024 * 1024 * 1024,
      p: 1024 * 1024 * 1024 * 1024 * 1024
    };
    var matches = s.match(/^[\s+]?([0-9]*)(\.([0-9]+))?( +)?([kmgtp]?b?)$/i);
    if (matches !== null) {
      bytes = parseFloat(s);
      if (!isFinite(bytes)) {
        return null;
      }
    } else {
      return null;
    }
    if (matches[5]) {
      bytes = bytes * bytesArray[matches[5]];
    }
    bytes = Math.round(bytes);
    return bytes;
  },
  /**
   * @param {string|number} timestamp timestamp
   * @param {string} format date format, see momentjs docs
   * @return {string} timestamp formatted as requested
   */
  formatDate: function formatDate(timestamp, format) {
    if (window.TESTING === undefined) {
      console.warn('OC.Util.formatDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment');
    }
    format = format || 'LLL';
    return moment__WEBPACK_IMPORTED_MODULE_0___default()(timestamp).format(format);
  },
  /**
   * @param {string|number} timestamp timestamp
   * @return {string} human readable difference from now
   */
  relativeModifiedDate: function relativeModifiedDate(timestamp) {
    if (window.TESTING === undefined) {
      console.warn('OC.Util.relativeModifiedDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment');
    }
    var diff = moment__WEBPACK_IMPORTED_MODULE_0___default()().diff(moment__WEBPACK_IMPORTED_MODULE_0___default()(timestamp));
    if (diff >= 0 && diff < 45000) {
      return t('core', 'seconds ago');
    }
    return moment__WEBPACK_IMPORTED_MODULE_0___default()(timestamp).fromNow();
  },
  /**
   * Returns the width of a generic browser scrollbar
   *
   * @return {number} width of scrollbar
   */
  getScrollBarWidth: function getScrollBarWidth() {
    if (this._scrollBarWidth) {
      return this._scrollBarWidth;
    }
    var inner = document.createElement('p');
    inner.style.width = '100%';
    inner.style.height = '200px';
    var outer = document.createElement('div');
    outer.style.position = 'absolute';
    outer.style.top = '0px';
    outer.style.left = '0px';
    outer.style.visibility = 'hidden';
    outer.style.width = '200px';
    outer.style.height = '150px';
    outer.style.overflow = 'hidden';
    outer.appendChild(inner);
    document.body.appendChild(outer);
    var w1 = inner.offsetWidth;
    outer.style.overflow = 'scroll';
    var w2 = inner.offsetWidth;
    if (w1 === w2) {
      w2 = outer.clientWidth;
    }
    document.body.removeChild(outer);
    this._scrollBarWidth = w1 - w2;
    return this._scrollBarWidth;
  },
  /**
   * Remove the time component from a given date
   *
   * @param {Date} date date
   * @return {Date} date with stripped time
   */
  stripTime: function stripTime(date) {
    // FIXME: likely to break when crossing DST
    // would be better to use a library like momentJS
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  },
  /**
   * Compare two strings to provide a natural sort
   *
   * @param {string} a first string to compare
   * @param {string} b second string to compare
   * @return {number} -1 if b comes before a, 1 if a comes before b
   * or 0 if the strings are identical
   */
  naturalSortCompare: function naturalSortCompare(a, b) {
    var x;
    var aa = chunkify(a);
    var bb = chunkify(b);
    for (x = 0; aa[x] && bb[x]; x++) {
      if (aa[x] !== bb[x]) {
        var aNum = Number(aa[x]);
        var bNum = Number(bb[x]);
        // note: == is correct here
        /* eslint-disable-next-line */
        if (aNum == aa[x] && bNum == bb[x]) {
          return aNum - bNum;
        } else {
          // Note: This locale setting isn't supported by all browsers but for the ones
          // that do there will be more consistency between client-server sorting
          return aa[x].localeCompare(bb[x], _index__WEBPACK_IMPORTED_MODULE_2__["default"].getLanguage());
        }
      }
    }
    return aa.length - bb.length;
  },
  /**
   * Calls the callback in a given interval until it returns true
   *
   * @param {Function} callback function to call on success
   * @param {number} interval in milliseconds
   */
  waitFor: function waitFor(callback, interval) {
    var internalCallback = function internalCallback() {
      if (callback() !== true) {
        setTimeout(internalCallback, interval);
      }
    };
    internalCallback();
  },
  /**
   * Checks if a cookie with the given name is present and is set to the provided value.
   *
   * @param {string} name name of the cookie
   * @param {string} value value of the cookie
   * @return {boolean} true if the cookie with the given name has the given value
   */
  isCookieSetToValue: function isCookieSetToValue(name, value) {
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].split('=');
      if (cookie[0].trim() === name && cookie[1].trim() === value) {
        return true;
      }
    }
    return false;
  }
});

/***/ }),

/***/ "./core/src/OC/webroot.js":
/*!********************************!*\
  !*** ./core/src/OC/webroot.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

var webroot = window._oc_webroot;
if (typeof webroot === 'undefined') {
  webroot = location.pathname;
  var pos = webroot.indexOf('/index.php/');
  if (pos !== -1) {
    webroot = webroot.substr(0, pos);
  } else {
    webroot = webroot.substr(0, webroot.lastIndexOf('/'));
  }
}
/* harmony default export */ __webpack_exports__["default"] = (webroot);

/***/ }),

/***/ "./core/src/OC/xhr-error.js":
/*!**********************************!*\
  !*** ./core/src/OC/xhr-error.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "ajaxConnectionLostHandler": function() { return /* binding */ ajaxConnectionLostHandler; },
/* harmony export */   "processAjaxError": function() { return /* binding */ processAjaxError; },
/* harmony export */   "registerXHRForErrorProcessing": function() { return /* binding */ registerXHRForErrorProcessing; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./index */ "./core/src/OC/index.js");
/* harmony import */ var _notification__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./notification */ "./core/src/OC/notification.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * Warn users that the connection to the server was lost temporarily
 *
 * This function is throttled to prevent stacked notfications.
 * After 7sec the first notification is gone, then we can show another one
 * if necessary.
 */
var ajaxConnectionLostHandler = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].throttle(function () {
  _notification__WEBPACK_IMPORTED_MODULE_3__["default"].showTemporary(t('core', 'Connection to server lost'));
}, 7 * 1000, {
  trailing: false
});

/**
 * Process ajax error, redirects to main page
 * if an error/auth error status was returned.
 *
 * @param {XMLHttpRequest} xhr xhr request
 */
var processAjaxError = function processAjaxError(xhr) {
  // purposefully aborted request ?
  // OC._userIsNavigatingAway needed to distinguish ajax calls cancelled by navigating away
  // from calls cancelled by failed cross-domain ajax due to SSO redirect
  if (xhr.status === 0 && (xhr.statusText === 'abort' || xhr.statusText === 'timeout' || _index__WEBPACK_IMPORTED_MODULE_2__["default"]._reloadCalled)) {
    return;
  }
  if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].contains([302, 303, 307, 401], xhr.status) && _index__WEBPACK_IMPORTED_MODULE_2__["default"].currentUser) {
    // sometimes "beforeunload" happens later, so need to defer the reload a bit
    setTimeout(function () {
      if (!_index__WEBPACK_IMPORTED_MODULE_2__["default"]._userIsNavigatingAway && !_index__WEBPACK_IMPORTED_MODULE_2__["default"]._reloadCalled) {
        var timer = 0;
        var seconds = 5;
        var interval = setInterval(function () {
          _notification__WEBPACK_IMPORTED_MODULE_3__["default"].showUpdate(n('core', 'Problem loading page, reloading in %n second', 'Problem loading page, reloading in %n seconds', seconds - timer));
          if (timer >= seconds) {
            clearInterval(interval);
            _index__WEBPACK_IMPORTED_MODULE_2__["default"].reload();
          }
          timer++;
        }, 1000 // 1 second interval
        );

        // only call reload once
        _index__WEBPACK_IMPORTED_MODULE_2__["default"]._reloadCalled = true;
      }
    }, 100);
  } else if (xhr.status === 0) {
    // Connection lost (e.g. WiFi disconnected or server is down)
    setTimeout(function () {
      if (!_index__WEBPACK_IMPORTED_MODULE_2__["default"]._userIsNavigatingAway && !_index__WEBPACK_IMPORTED_MODULE_2__["default"]._reloadCalled) {
        // TODO: call method above directly
        _index__WEBPACK_IMPORTED_MODULE_2__["default"]._ajaxConnectionLostHandler();
      }
    }, 100);
  }
};

/**
 * Registers XmlHttpRequest object for global error processing.
 *
 * This means that if this XHR object returns 401 or session timeout errors,
 * the current page will automatically be reloaded.
 *
 * @param {XMLHttpRequest} xhr xhr request
 */
var registerXHRForErrorProcessing = function registerXHRForErrorProcessing(xhr) {
  var loadCallback = function loadCallback() {
    if (xhr.readyState !== 4) {
      return;
    }
    if (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304) {
      return;
    }

    // fire jquery global ajax error handler
    jquery__WEBPACK_IMPORTED_MODULE_1___default()(document).trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('ajaxError'), xhr);
  };
  var errorCallback = function errorCallback() {
    // fire jquery global ajax error handler
    jquery__WEBPACK_IMPORTED_MODULE_1___default()(document).trigger(new (jquery__WEBPACK_IMPORTED_MODULE_1___default().Event)('ajaxError'), xhr);
  };
  if (xhr.addEventListener) {
    xhr.addEventListener('load', loadCallback);
    xhr.addEventListener('error', errorCallback);
  }
};

/***/ }),

/***/ "./core/src/OCA/index.js":
/*!*******************************!*\
  !*** ./core/src/OCA/index.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./search */ "./core/src/OCA/search.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * Namespace for apps
 *
 * @namespace OCA
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * @deprecated 20.0.0, will be removed in Nextcloud 22
   */
  Search: _search__WEBPACK_IMPORTED_MODULE_0__["default"]
});

/***/ }),

/***/ "./core/src/OCA/search.js":
/*!********************************!*\
  !*** ./core/src/OCA/search.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Search; }
/* harmony export */ });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
var Search = /*#__PURE__*/_createClass(
/**
 * @deprecated 20.0.0, will be removed in Nextcloud 22
 */
function Search() {
  _classCallCheck(this, Search);
  console.warn('OCA.Search is deprecated. Please use the unified search API instead');
});


/***/ }),

/***/ "./core/src/OCP/appconfig.js":
/*!***********************************!*\
  !*** ./core/src/OCP/appconfig.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "deleteKey": function() { return /* binding */ deleteKey; },
/* harmony export */   "getApps": function() { return /* binding */ getApps; },
/* harmony export */   "getKeys": function() { return /* binding */ getKeys; },
/* harmony export */   "getValue": function() { return /* binding */ getValue; },
/* harmony export */   "setValue": function() { return /* binding */ setValue; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _OC_index__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../OC/index */ "./core/src/OC/index.js");
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * @param {string} method 'post' or 'delete'
 * @param {string} endpoint endpoint
 * @param {object} [options] destructuring object
 * @param {object} [options.data] option data
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 */
function call(method, endpoint, options) {
  if ((method === 'post' || method === 'delete') && _OC_index__WEBPACK_IMPORTED_MODULE_2__["default"].PasswordConfirmation.requiresPasswordConfirmation()) {
    _OC_index__WEBPACK_IMPORTED_MODULE_2__["default"].PasswordConfirmation.requirePasswordConfirmation(_.bind(call, this, method, endpoint, options));
    return;
  }
  options = options || {};
  jquery__WEBPACK_IMPORTED_MODULE_0___default().ajax({
    type: method.toUpperCase(),
    url: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/provisioning_api/api/v1/config/apps') + endpoint,
    data: options.data || {},
    success: options.success,
    error: options.error
  });
}

/**
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @since 11.0.0
 */
function getApps(options) {
  call('get', '', options);
}

/**
 * @param {string} app app id
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
function getKeys(app, options) {
  call('get', '/' + app, options);
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {string | Function} defaultValue default value
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
function getValue(app, key, defaultValue, options) {
  options = options || {};
  options.data = {
    defaultValue: defaultValue
  };
  call('get', '/' + app + '/' + key, options);
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {string} value value
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
function setValue(app, key, value, options) {
  options = options || {};
  options.data = {
    value: value
  };
  call('post', '/' + app + '/' + key, options);
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
function deleteKey(app, key, options) {
  call('delete', '/' + app + '/' + key, options);
}

/***/ }),

/***/ "./core/src/Util/a11y.js":
/*!*******************************!*\
  !*** ./core/src/Util/a11y.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "isA11yActivation": function() { return /* binding */ isA11yActivation; }
/* harmony export */ });
/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Return whether the DOM event is an accessible mouse or keyboard element activation
 *
 * @param {Event} event DOM event
 *
 * @return {boolean}
 */
var isA11yActivation = function isA11yActivation(event) {
  if (event.type === 'click') {
    return true;
  }
  if (event.type === 'keydown' && event.key === 'Enter') {
    return true;
  }
  return false;
};

/***/ }),

/***/ "./core/src/login.js":
/*!***************************!*\
  !*** ./core/src/login.js ***!
  \***************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _OC_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./OC/index */ "./core/src/OC/index.js");
/* harmony import */ var _views_Login_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Login.vue */ "./core/src/views/Login.vue");
/* harmony import */ var _mixins_Nextcloud__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./mixins/Nextcloud */ "./core/src/mixins/Nextcloud.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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



// eslint-disable-next-line no-unused-vars
 // TODO: Not needed but L10n breaks if removed


vue__WEBPACK_IMPORTED_MODULE_3__["default"].mixin(_mixins_Nextcloud__WEBPACK_IMPORTED_MODULE_2__["default"]);
var View = vue__WEBPACK_IMPORTED_MODULE_3__["default"].extend(_views_Login_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
new View().$mount('#login');

/***/ }),

/***/ "./core/src/mixins/Nextcloud.js":
/*!**************************************!*\
  !*** ./core/src/mixins/Nextcloud.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _OC_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../OC/l10n */ "./core/src/OC/l10n.js");
/* harmony import */ var _OC_index__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OC/index */ "./core/src/OC/index.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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



/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      OC: _OC_index__WEBPACK_IMPORTED_MODULE_1__["default"]
    };
  },
  methods: {
    t: _OC_l10n__WEBPACK_IMPORTED_MODULE_0__["default"].translate.bind(_OC_l10n__WEBPACK_IMPORTED_MODULE_0__["default"]),
    n: _OC_l10n__WEBPACK_IMPORTED_MODULE_0__["default"].translatePlural.bind(_OC_l10n__WEBPACK_IMPORTED_MODULE_0__["default"])
  }
});

/***/ }),

/***/ "./core/src/services/WebAuthnAuthenticationService.js":
/*!************************************************************!*\
  !*** ./core/src/services/WebAuthnAuthenticationService.js ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "finishAuthentication": function() { return /* binding */ finishAuthentication; },
/* harmony export */   "startAuthentication": function() { return /* binding */ startAuthentication; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * @copyright 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * @param {any} loginName -
 */
function startAuthentication(loginName) {
  var url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/login/webauthn/start');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
    loginName: loginName
  }).then(function (resp) {
    return resp.data;
  });
}

/**
 * @param {any} data -
 */
function finishAuthentication(data) {
  var url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/login/webauthn/finish');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
    data: data
  }).then(function (resp) {
    return resp.data;
  });
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue");


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LoginButton',
  components: {
    ArrowRight: vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    value: {
      type: String,
      default: t('core', 'Log in')
    },
    valueLoading: {
      type: String,
      default: t('core', 'Logging in …')
    },
    loading: {
      type: Boolean,
      required: true
    },
    invertedColors: {
      type: Boolean,
      default: false
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");





/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LoginForm',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcPasswordField: (_nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcTextField: (_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcNoteCard: (_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  props: {
    username: {
      type: String,
      default: ''
    },
    redirectUrl: {
      type: [String, Boolean],
      default: false
    },
    errors: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    messages: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    throttleDelay: {
      type: Number,
      default: 0
    },
    autoCompleteAllowed: {
      type: Boolean,
      default: true
    },
    directLogin: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    var _Intl$DateTimeFormat, _Intl$DateTimeFormat$;
    return {
      loading: false,
      timezone: (_Intl$DateTimeFormat = new Intl.DateTimeFormat()) === null || _Intl$DateTimeFormat === void 0 ? void 0 : (_Intl$DateTimeFormat$ = _Intl$DateTimeFormat.resolvedOptions()) === null || _Intl$DateTimeFormat$ === void 0 ? void 0 : _Intl$DateTimeFormat$.timeZone,
      timezoneOffset: -new Date().getTimezoneOffset() / 60,
      headline: t('core', 'Log in to {productName}', {
        productName: OC.theme.name
      }),
      user: '',
      password: ''
    };
  },
  computed: {
    isError: function isError() {
      return this.invalidPassword || this.userDisabled || this.throttleDelay > 5000;
    },
    errorLabel: function errorLabel() {
      if (this.invalidPassword) {
        return t('core', 'Wrong username or password.');
      }
      if (this.userDisabled) {
        return t('core', 'User disabled');
      }
      if (this.throttleDelay > 5000) {
        return t('core', 'We have detected multiple invalid login attempts from your IP. Therefore your next login is throttled up to 30 seconds.');
      }
      return undefined;
    },
    apacheAuthFailed: function apacheAuthFailed() {
      return this.errors.indexOf('apacheAuthFailed') !== -1;
    },
    internalException: function internalException() {
      return this.errors.indexOf('internalexception') !== -1;
    },
    invalidPassword: function invalidPassword() {
      return this.errors.indexOf('invalidpassword') !== -1;
    },
    userDisabled: function userDisabled() {
      return this.errors.indexOf('userdisabled') !== -1;
    },
    loadingIcon: function loadingIcon() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.imagePath)('core', 'loading-dark.gif');
    },
    loginActionUrl: function loginActionUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('login');
    }
  },
  mounted: function mounted() {
    if (this.username === '') {
      this.$refs.user.$refs.inputField.$refs.input.focus();
    } else {
      this.user = this.username;
      this.$refs.password.$refs.inputField.$refs.input.focus();
    }
  },
  methods: {
    updateUsername: function updateUsername() {
      this.$emit('update:username', this.user);
    },
    submit: function submit() {
      this.loading = true;
      this.$emit('submit');
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _services_WebAuthnAuthenticationService__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../services/WebAuthnAuthenticationService */ "./core/src/services/WebAuthnAuthenticationService.js");
/* harmony import */ var _LoginButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginButton */ "./core/src/components/login/LoginButton.vue");
/* harmony import */ var vue_material_design_icons_Information__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Information */ "./node_modules/vue-material-design-icons/Information.vue");
/* harmony import */ var vue_material_design_icons_LockOpen__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/LockOpen */ "./node_modules/vue-material-design-icons/LockOpen.vue");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _wrapNativeSuper(Class) { var _cache = typeof Map === "function" ? new Map() : undefined; _wrapNativeSuper = function _wrapNativeSuper(Class) { if (Class === null || !_isNativeFunction(Class)) return Class; if (typeof Class !== "function") { throw new TypeError("Super expression must either be null or a function"); } if (typeof _cache !== "undefined") { if (_cache.has(Class)) return _cache.get(Class); _cache.set(Class, Wrapper); } function Wrapper() { return _construct(Class, arguments, _getPrototypeOf(this).constructor); } Wrapper.prototype = Object.create(Class.prototype, { constructor: { value: Wrapper, enumerable: false, writable: true, configurable: true } }); return _setPrototypeOf(Wrapper, Class); }; return _wrapNativeSuper(Class); }
function _construct(Parent, args, Class) { if (_isNativeReflectConstruct()) { _construct = Reflect.construct.bind(); } else { _construct = function _construct(Parent, args, Class) { var a = [null]; a.push.apply(a, args); var Constructor = Function.bind.apply(Parent, a); var instance = new Constructor(); if (Class) _setPrototypeOf(instance, Class.prototype); return instance; }; } return _construct.apply(null, arguments); }
function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }
function _isNativeFunction(fn) { return Function.toString.call(fn).indexOf("[native code]") !== -1; }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }




var NoValidCredentials = /*#__PURE__*/function (_Error) {
  _inherits(NoValidCredentials, _Error);
  var _super = _createSuper(NoValidCredentials);
  function NoValidCredentials() {
    _classCallCheck(this, NoValidCredentials);
    return _super.apply(this, arguments);
  }
  return _createClass(NoValidCredentials);
}( /*#__PURE__*/_wrapNativeSuper(Error));
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'PasswordLessLoginForm',
  components: {
    LoginButton: _LoginButton__WEBPACK_IMPORTED_MODULE_1__["default"],
    InformationIcon: vue_material_design_icons_Information__WEBPACK_IMPORTED_MODULE_2__["default"],
    LockOpenIcon: vue_material_design_icons_LockOpen__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    username: {
      type: String,
      default: ''
    },
    redirectUrl: {
      type: [String, Boolean],
      default: false
    },
    autoCompleteAllowed: {
      type: Boolean,
      default: true
    },
    isHttps: {
      type: Boolean,
      default: false
    },
    isLocalhost: {
      type: Boolean,
      default: false
    },
    hasPublicKeyCredential: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      user: this.username,
      loading: false,
      validCredentials: true
    };
  },
  methods: {
    authenticate: function authenticate() {
      var _this = this;
      console.debug('passwordless login initiated');
      this.getAuthenticationData(this.user).then(function (publicKey) {
        console.debug(publicKey);
        return publicKey;
      }).then(this.sign).then(this.completeAuthentication).catch(function (error) {
        if (error instanceof NoValidCredentials) {
          _this.validCredentials = false;
          return;
        }
        console.debug(error);
      });
    },
    getAuthenticationData: function getAuthenticationData(uid) {
      var base64urlDecode = function base64urlDecode(input) {
        // Replace non-url compatible chars with base64 standard chars
        input = input.replace(/-/g, '+').replace(/_/g, '/');

        // Pad out with standard base64 required padding characters
        var pad = input.length % 4;
        if (pad) {
          if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
          }
          input += new Array(5 - pad).join('=');
        }
        return window.atob(input);
      };
      return (0,_services_WebAuthnAuthenticationService__WEBPACK_IMPORTED_MODULE_0__.startAuthentication)(uid).then(function (publicKey) {
        console.debug('Obtained PublicKeyCredentialRequestOptions');
        console.debug(publicKey);
        if (!Object.prototype.hasOwnProperty.call(publicKey, 'allowCredentials')) {
          console.debug('No credentials found.');
          throw new NoValidCredentials();
        }
        publicKey.challenge = Uint8Array.from(base64urlDecode(publicKey.challenge), function (c) {
          return c.charCodeAt(0);
        });
        publicKey.allowCredentials = publicKey.allowCredentials.map(function (data) {
          return _objectSpread(_objectSpread({}, data), {}, {
            id: Uint8Array.from(base64urlDecode(data.id), function (c) {
              return c.charCodeAt(0);
            })
          });
        });
        console.debug('Converted PublicKeyCredentialRequestOptions');
        console.debug(publicKey);
        return publicKey;
      }).catch(function (error) {
        console.debug('Error while obtaining data');
        throw error;
      });
    },
    sign: function sign(publicKey) {
      var arrayToBase64String = function arrayToBase64String(a) {
        return window.btoa(String.fromCharCode.apply(String, _toConsumableArray(a)));
      };
      var arrayToString = function arrayToString(a) {
        return String.fromCharCode.apply(String, _toConsumableArray(a));
      };
      return navigator.credentials.get({
        publicKey: publicKey
      }).then(function (data) {
        console.debug(data);
        console.debug(new Uint8Array(data.rawId));
        console.debug(arrayToBase64String(new Uint8Array(data.rawId)));
        return {
          id: data.id,
          type: data.type,
          rawId: arrayToBase64String(new Uint8Array(data.rawId)),
          response: {
            authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
            clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
            signature: arrayToBase64String(new Uint8Array(data.response.signature)),
            userHandle: data.response.userHandle ? arrayToString(new Uint8Array(data.response.userHandle)) : null
          }
        };
      }).then(function (challenge) {
        console.debug(challenge);
        return challenge;
      }).catch(function (error) {
        console.debug('GOT AN ERROR!');
        console.debug(error); // Example: timeout, interaction refused...
      });
    },
    completeAuthentication: function completeAuthentication(challenge) {
      console.debug('TIME TO COMPLETE');
      var redirectUrl = this.redirectUrl;
      return (0,_services_WebAuthnAuthenticationService__WEBPACK_IMPORTED_MODULE_0__.finishAuthentication)(JSON.stringify(challenge)).then(function (_ref) {
        var defaultRedirectUrl = _ref.defaultRedirectUrl;
        console.debug('Logged in redirecting');
        // Redirect url might be false so || should be used instead of ??.
        window.location.href = redirectUrl || defaultRedirectUrl;
      }).catch(function (error) {
        console.debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!');
        console.debug(error); // Example: timeout, interaction refused...
      });
    },
    submit: function submit() {
      // noop
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__);





/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ResetPassword',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcNoteCard: (_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcTextField: (_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  props: {
    username: {
      type: String,
      required: true
    },
    resetPasswordLink: {
      type: String,
      required: true
    }
  },
  data: function data() {
    return {
      error: false,
      loading: false,
      message: undefined,
      user: this.username
    };
  },
  watch: {
    username: function username(value) {
      this.user = value;
    }
  },
  methods: {
    updateUsername: function updateUsername() {
      this.$emit('update:username', this.user);
    },
    submit: function submit() {
      var _this = this;
      this.loading = true;
      this.error = false;
      this.message = '';
      var url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/lostpassword/email');
      var data = {
        user: this.user
      };
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, data).then(function (resp) {
        return resp.data;
      }).then(function (data) {
        if (data.status !== 'success') {
          throw new Error("got status ".concat(data.status));
        }
        _this.message = 'send-success';
      }).catch(function (e) {
        console.error('could not send reset email request', e);
        _this.error = true;
        _this.message = 'send-error';
      }).then(function () {
        _this.loading = false;
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UpdatePassword',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    username: {
      type: String,
      required: true
    },
    resetPasswordTarget: {
      type: String,
      required: true
    }
  },
  data: function data() {
    return {
      error: false,
      loading: false,
      message: undefined,
      user: this.username,
      password: '',
      encrypted: false,
      proceed: false
    };
  },
  watch: {
    username: function username(value) {
      this.user = value;
    }
  },
  methods: {
    submit: function submit() {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _yield$Axios$post, data;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this.loading = true;
                _this.error = false;
                _this.message = '';
                _context.prev = 3;
                _context.next = 6;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(_this.resetPasswordTarget, {
                  password: _this.password,
                  proceed: _this.proceed
                });
              case 6:
                _yield$Axios$post = _context.sent;
                data = _yield$Axios$post.data;
                if (!(data && data.status === 'success')) {
                  _context.next = 14;
                  break;
                }
                _this.message = 'send-success';
                _this.$emit('update:username', _this.user);
                _this.$emit('done');
                _context.next = 23;
                break;
              case 14:
                if (!(data && data.encryption)) {
                  _context.next = 18;
                  break;
                }
                _this.encrypted = true;
                _context.next = 23;
                break;
              case 18:
                if (!(data && data.msg)) {
                  _context.next = 22;
                  break;
                }
                throw new Error(data.msg);
              case 22:
                throw new Error();
              case 23:
                _context.next = 29;
                break;
              case 25:
                _context.prev = 25;
                _context.t0 = _context["catch"](3);
                _this.error = true;
                _this.message = _context.t0.message ? _context.t0.message : t('core', 'Password cannot be changed. Please contact your administrator.');
              case 29:
                _context.prev = 29;
                _this.loading = false;
                return _context.finish(29);
              case 32:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[3, 25, 29, 32]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var query_string__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! query-string */ "./node_modules/query-string/index.js");
/* harmony import */ var _components_login_LoginForm_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/login/LoginForm.vue */ "./core/src/components/login/LoginForm.vue");
/* harmony import */ var _components_login_PasswordLessLoginForm_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/login/PasswordLessLoginForm.vue */ "./core/src/components/login/PasswordLessLoginForm.vue");
/* harmony import */ var _components_login_ResetPassword_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/login/ResetPassword.vue */ "./core/src/components/login/ResetPassword.vue");
/* harmony import */ var _components_login_UpdatePassword_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/login/UpdatePassword.vue */ "./core/src/components/login/UpdatePassword.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7__);








var query = query_string__WEBPACK_IMPORTED_MODULE_1__.parse(location.search);
if (query.clear === '1') {
  try {
    window.localStorage.clear();
    window.sessionStorage.clear();
    console.debug('Browser storage cleared');
  } catch (e) {
    console.error('Could not clear browser storage', e);
  }
}
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Login',
  components: {
    LoginForm: _components_login_LoginForm_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    PasswordLessLoginForm: _components_login_PasswordLessLoginForm_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    ResetPassword: _components_login_ResetPassword_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    UpdatePassword: _components_login_UpdatePassword_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcNoteCard: (_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7___default())
  },
  data: function data() {
    return {
      loading: false,
      user: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginUsername', ''),
      passwordlessLogin: false,
      resetPassword: false,
      // Initial data
      errors: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginErrors', []),
      messages: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginMessages', []),
      redirectUrl: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginRedirectUrl', false),
      throttleDelay: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginThrottleDelay', 0),
      canResetPassword: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginCanResetPassword', false),
      resetPasswordLink: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginResetPasswordLink', ''),
      autoCompleteAllowed: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginAutocomplete', true),
      resetPasswordTarget: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'resetPasswordTarget', ''),
      resetPasswordUser: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'resetPasswordUser', ''),
      directLogin: query.direct === '1',
      hasPasswordless: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'webauthn-available', false),
      countAlternativeLogins: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'countAlternativeLogins', false),
      alternativeLogins: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'alternativeLogins', []),
      isHttps: window.location.protocol === 'https:',
      isLocalhost: window.location.hostname === 'localhost',
      hasPublicKeyCredential: typeof window.PublicKeyCredential !== 'undefined',
      hideLoginForm: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'hideLoginForm', false)
    };
  },
  methods: {
    passwordResetFinished: function passwordResetFinished() {
      this.resetPasswordTarget = '';
      this.directLogin = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcButton", {
    attrs: {
      type: "primary",
      "native-type": "submit",
      wide: true
    },
    on: {
      click: function click($event) {
        return _vm.$emit("click");
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_vm.loading ? _c("div", {
          staticClass: "submit-wrapper__icon icon-loading-small-dark"
        }) : _c("ArrowRight", {
          staticClass: "submit-wrapper__icon"
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t" + _vm._s(!_vm.loading ? _vm.value : _vm.valueLoading) + "\n\t")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("form", {
    ref: "loginForm",
    staticClass: "login-form",
    attrs: {
      method: "post",
      name: "login",
      action: _vm.loginActionUrl
    },
    on: {
      submit: _vm.submit
    }
  }, [_c("fieldset", {
    staticClass: "login-form__fieldset",
    attrs: {
      "data-login-form": ""
    }
  }, [_vm.apacheAuthFailed ? _c("NcNoteCard", {
    attrs: {
      title: _vm.t("core", "Server side authentication failed!"),
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Please contact your administrator.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.messages.length > 0 ? _c("NcNoteCard", _vm._l(_vm.messages, function (message, index) {
    return _c("div", {
      key: index
    }, [_vm._v("\n\t\t\t\t" + _vm._s(message)), _c("br")]);
  }), 0) : _vm._e(), _vm._v(" "), _vm.internalException ? _c("NcNoteCard", {
    class: _vm.t("core", "An internal error occurred."),
    attrs: {
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Please try again or contact your administrator.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "hidden",
    attrs: {
      id: "message"
    }
  }, [_c("img", {
    staticClass: "float-spinner",
    attrs: {
      alt: "",
      src: _vm.loadingIcon
    }
  }), _vm._v(" "), _c("span", {
    attrs: {
      id: "messageText"
    }
  }), _vm._v(" "), _c("div", {
    staticStyle: {
      clear: "both"
    }
  })]), _vm._v(" "), _c("h2", {
    staticClass: "login-form__headline",
    attrs: {
      "data-login-form-headline": ""
    },
    domProps: {
      innerHTML: _vm._s(_vm.headline)
    }
  }), _vm._v(" "), _c("NcTextField", {
    ref: "user",
    class: {
      shake: _vm.invalidPassword
    },
    attrs: {
      id: "user",
      label: _vm.t("core", "Account name or email"),
      "label-visible": true,
      name: "user",
      value: _vm.user,
      autocapitalize: "none",
      spellchecking: false,
      autocomplete: _vm.autoCompleteAllowed ? "username" : "off",
      required: "",
      "data-login-form-input-user": ""
    },
    on: {
      "update:value": function updateValue($event) {
        _vm.user = $event;
      },
      change: _vm.updateUsername
    }
  }), _vm._v(" "), _c("NcPasswordField", {
    ref: "password",
    class: {
      shake: _vm.invalidPassword
    },
    attrs: {
      id: "password",
      name: "password",
      "label-visible": true,
      value: _vm.password,
      spellchecking: false,
      autocapitalize: "none",
      autocomplete: _vm.autoCompleteAllowed ? "current-password" : "off",
      label: _vm.t("core", "Password"),
      "helper-text": _vm.errorLabel,
      error: _vm.isError,
      "data-login-form-input-password": "",
      required: ""
    },
    on: {
      "update:value": function updateValue($event) {
        _vm.password = $event;
      }
    }
  }), _vm._v(" "), _c("LoginButton", {
    attrs: {
      "data-login-form-submit": "",
      loading: _vm.loading
    }
  }), _vm._v(" "), _vm.redirectUrl ? _c("input", {
    attrs: {
      type: "hidden",
      name: "redirect_url"
    },
    domProps: {
      value: _vm.redirectUrl
    }
  }) : _vm._e(), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "timezone"
    },
    domProps: {
      value: _vm.timezone
    }
  }), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "timezone_offset"
    },
    domProps: {
      value: _vm.timezoneOffset
    }
  }), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "requesttoken"
    },
    domProps: {
      value: _vm.OC.requestToken
    }
  }), _vm._v(" "), _vm.directLogin ? _c("input", {
    attrs: {
      type: "hidden",
      name: "direct",
      value: "1"
    }
  }) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
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
  return (_vm.isHttps || _vm.isLocalhost) && _vm.hasPublicKeyCredential ? _c("form", {
    ref: "loginForm",
    attrs: {
      method: "post",
      name: "login"
    },
    on: {
      submit: function submit($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("fieldset", [_c("p", {
    staticClass: "grouptop groupbottom"
  }, [_c("label", {
    staticClass: "infield",
    attrs: {
      for: "user"
    }
  }, [_vm._v(_vm._s(_vm.t("core", "Username or	email")))]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.user,
      expression: "user"
    }],
    ref: "user",
    attrs: {
      id: "user",
      type: "text",
      name: "user",
      autocomplete: _vm.autoCompleteAllowed ? "on" : "off",
      placeholder: _vm.t("core", "Username or email"),
      "aria-label": _vm.t("core", "Username or email"),
      required: ""
    },
    domProps: {
      value: _vm.user
    },
    on: {
      change: function change($event) {
        return _vm.$emit("update:username", _vm.user);
      },
      input: function input($event) {
        if ($event.target.composing) return;
        _vm.user = $event.target.value;
      }
    }
  })]), _vm._v(" "), !_vm.validCredentials ? _c("div", {
    staticClass: "body-login-container update form__message-box"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Your account is not setup for passwordless login.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.validCredentials ? _c("LoginButton", {
    attrs: {
      loading: _vm.loading
    },
    on: {
      click: _vm.authenticate
    }
  }) : _vm._e()], 1)]) : !_vm.hasPublicKeyCredential ? _c("div", {
    staticClass: "body-login-container update"
  }, [_c("InformationIcon", {
    attrs: {
      size: "70"
    }
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("core", "Browser not supported")))]), _vm._v(" "), _c("p", {
    staticClass: "infogroup"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Passwordless authentication is not supported in your browser.")) + "\n\t")])], 1) : !_vm.isHttps && !_vm.isLocalhost ? _c("div", {
    staticClass: "body-login-container update"
  }, [_c("LockOpenIcon", {
    attrs: {
      size: "70"
    }
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("core", "Your connection is not secure")))]), _vm._v(" "), _c("p", {
    staticClass: "infogroup"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Passwordless authentication is only available over a secure connection.")) + "\n\t")])], 1) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("form", {
    staticClass: "login-form",
    on: {
      submit: function submit($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("fieldset", {
    staticClass: "login-form__fieldset"
  }, [_c("NcTextField", {
    attrs: {
      id: "user",
      value: _vm.user,
      name: "user",
      autocapitalize: "off",
      label: _vm.t("core", "Account name or email"),
      "label-visible": true,
      required: ""
    },
    on: {
      "update:value": function updateValue($event) {
        _vm.user = $event;
      },
      change: _vm.updateUsername
    }
  }), _vm._v(" "), _c("LoginButton", {
    attrs: {
      value: _vm.t("core", "Reset password")
    }
  }), _vm._v(" "), _vm.message === "send-success" ? _c("NcNoteCard", {
    attrs: {
      type: "success"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "A password reset message has been sent to the email address of this account. If you do not receive it, check your spam/junk folders or ask your local administrator for help.")) + "\n\t\t\t"), _c("br"), _vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "If it is not there ask your local administrator.")) + "\n\t\t")]) : _vm.message === "send-error" ? _c("NcNoteCard", {
    attrs: {
      type: "error"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Couldn't send reset email. Please contact your administrator.")) + "\n\t\t")]) : _vm.message === "reset-error" ? _c("NcNoteCard", {
    attrs: {
      type: "error"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Password cannot be changed. Please contact your administrator.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("a", {
    staticClass: "login-form__link",
    attrs: {
      href: "#"
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        return _vm.$emit("abort");
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Back to login")) + "\n\t\t")])], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("form", {
    on: {
      submit: function submit($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("fieldset", [_c("p", [_c("label", {
    staticClass: "infield",
    attrs: {
      for: "password"
    }
  }, [_vm._v(_vm._s(_vm.t("core", "New password")))]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.password,
      expression: "password"
    }],
    attrs: {
      id: "password",
      type: "password",
      name: "password",
      autocomplete: "new-password",
      autocapitalize: "none",
      autocorrect: "off",
      required: "",
      placeholder: _vm.t("core", "New password")
    },
    domProps: {
      value: _vm.password
    },
    on: {
      input: function input($event) {
        if ($event.target.composing) return;
        _vm.password = $event.target.value;
      }
    }
  })]), _vm._v(" "), _vm.encrypted ? _c("div", {
    staticClass: "update"
  }, [_c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "Your files are encrypted. There will be no way to get your data back after your password is reset. If you are not sure what to do, please contact your administrator before you continue. Do you really want to continue?")) + "\n\t\t\t")]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.proceed,
      expression: "proceed"
    }],
    staticClass: "checkbox",
    attrs: {
      id: "encrypted-continue",
      type: "checkbox"
    },
    domProps: {
      checked: Array.isArray(_vm.proceed) ? _vm._i(_vm.proceed, null) > -1 : _vm.proceed
    },
    on: {
      change: function change($event) {
        var $$a = _vm.proceed,
          $$el = $event.target,
          $$c = $$el.checked ? true : false;
        if (Array.isArray($$a)) {
          var $$v = null,
            $$i = _vm._i($$a, $$v);
          if ($$el.checked) {
            $$i < 0 && (_vm.proceed = $$a.concat([$$v]));
          } else {
            $$i > -1 && (_vm.proceed = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
          }
        } else {
          _vm.proceed = $$c;
        }
      }
    }
  }), _vm._v(" "), _c("label", {
    attrs: {
      for: "encrypted-continue"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "I know what I'm doing")) + "\n\t\t\t")])]) : _vm._e(), _vm._v(" "), _c("LoginButton", {
    attrs: {
      loading: _vm.loading,
      value: _vm.t("core", "Reset password"),
      "value-loading": _vm.t("core", "Resetting password")
    }
  }), _vm._v(" "), _vm.error && _vm.message ? _c("p", {
    class: {
      warning: _vm.error
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.message) + "\n\t\t")]) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b&":
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b& ***!
  \**************************************************************************************************************************************************************************************************************************************************/
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
  return _c("div", {
    staticClass: "guest-box login-box"
  }, [!_vm.hideLoginForm || _vm.directLogin ? _c("div", [_c("transition", {
    attrs: {
      name: "fade",
      mode: "out-in"
    }
  }, [!_vm.passwordlessLogin && !_vm.resetPassword && _vm.resetPasswordTarget === "" ? _c("div", [_c("LoginForm", {
    attrs: {
      username: _vm.user,
      "redirect-url": _vm.redirectUrl,
      "direct-login": _vm.directLogin,
      messages: _vm.messages,
      errors: _vm.errors,
      "throttle-delay": _vm.throttleDelay,
      "auto-complete-allowed": _vm.autoCompleteAllowed
    },
    on: {
      "update:username": function updateUsername($event) {
        _vm.user = $event;
      },
      submit: function submit($event) {
        _vm.loading = true;
      }
    }
  }), _vm._v(" "), _vm.canResetPassword && _vm.resetPasswordLink !== "" ? _c("a", {
    staticClass: "login-box__link",
    attrs: {
      id: "lost-password",
      href: _vm.resetPasswordLink
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Forgot password?")) + "\n\t\t\t\t")]) : _vm.canResetPassword && !_vm.resetPassword ? _c("a", {
    staticClass: "login-box__link",
    attrs: {
      id: "lost-password",
      href: _vm.resetPasswordLink
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        _vm.resetPassword = true;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Forgot password?")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.hasPasswordless ? [_vm.countAlternativeLogins ? _c("div", {
    staticClass: "alternative-logins"
  }, [_vm.hasPasswordless ? _c("a", {
    staticClass: "button",
    class: {
      "single-alt-login-option": _vm.countAlternativeLogins
    },
    attrs: {
      href: "#"
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        _vm.passwordlessLogin = true;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.t("core", "Log in with a device")) + "\n\t\t\t\t\t\t")]) : _vm._e()]) : _c("a", {
    attrs: {
      href: "#"
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        _vm.passwordlessLogin = true;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("core", "Log in with a device")) + "\n\t\t\t\t\t")])] : _vm._e()], 2) : !_vm.loading && _vm.passwordlessLogin ? _c("div", {
    key: "reset",
    staticClass: "login-additional"
  }, [_c("PasswordLessLoginForm", {
    attrs: {
      username: _vm.user,
      "redirect-url": _vm.redirectUrl,
      "auto-complete-allowed": _vm.autoCompleteAllowed,
      "is-https": _vm.isHttps,
      "is-localhost": _vm.isLocalhost,
      "has-public-key-credential": _vm.hasPublicKeyCredential
    },
    on: {
      "update:username": function updateUsername($event) {
        _vm.user = $event;
      },
      submit: function submit($event) {
        _vm.loading = true;
      }
    }
  }), _vm._v(" "), _c("a", {
    staticClass: "login-box__link",
    attrs: {
      href: "#"
    },
    on: {
      click: function click($event) {
        $event.preventDefault();
        _vm.passwordlessLogin = false;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Back")) + "\n\t\t\t\t")])], 1) : !_vm.loading && _vm.canResetPassword ? _c("div", {
    key: "reset",
    staticClass: "login-additional"
  }, [_c("div", {
    staticClass: "lost-password-container"
  }, [_vm.resetPassword ? _c("ResetPassword", {
    attrs: {
      username: _vm.user,
      "reset-password-link": _vm.resetPasswordLink
    },
    on: {
      "update:username": function updateUsername($event) {
        _vm.user = $event;
      },
      abort: function abort($event) {
        _vm.resetPassword = false;
      }
    }
  }) : _vm._e()], 1)]) : _vm.resetPasswordTarget !== "" ? _c("div", [_c("UpdatePassword", {
    attrs: {
      username: _vm.user,
      "reset-password-target": _vm.resetPasswordTarget
    },
    on: {
      "update:username": function updateUsername($event) {
        _vm.user = $event;
      },
      done: _vm.passwordResetFinished
    }
  })], 1) : _vm._e()])], 1) : _c("div", [_c("transition", {
    attrs: {
      name: "fade",
      mode: "out-in"
    }
  }, [_c("NcNoteCard", {
    attrs: {
      type: "warning",
      title: _vm.t("core", "Login form is disabled.")
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "Please contact your administrator.")) + "\n\t\t\t")])], 1)], 1), _vm._v(" "), _c("div", {
    staticClass: "alternative-logins",
    attrs: {
      id: "alternative-logins"
    }
  }, _vm._l(_vm.alternativeLogins, function (alternativeLogin, index) {
    return _c("NcButton", {
      key: index,
      class: [alternativeLogin.class],
      attrs: {
        type: "secondary",
        wide: true,
        role: "link",
        href: alternativeLogin.href
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(alternativeLogin.name) + "\n\t\t")]);
  }), 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".button-vue[data-v-2b0f9fce] {\n  margin-top: 0.5rem;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".login-form[data-v-722a846b] {\n  text-align: left;\n  font-size: 1rem;\n}\n.login-form__fieldset[data-v-722a846b] {\n  width: 100%;\n  display: flex;\n  flex-direction: column;\n  gap: 0.5rem;\n}\n.login-form__headline[data-v-722a846b] {\n  text-align: center;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".body-login-container.update[data-v-34bf48f7] {\n  margin: 15px 0;\n}\n.body-login-container.update.form__message-box[data-v-34bf48f7] {\n  width: 240px;\n  margin: 5px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".login-form[data-v-a10057b0] {\n  text-align: left;\n  font-size: 1rem;\n}\n.login-form__fieldset[data-v-a10057b0] {\n  width: 100%;\n  display: flex;\n  flex-direction: column;\n  gap: 0.5rem;\n}\n.login-form__link[data-v-a10057b0] {\n  display: block;\n  font-weight: normal !important;\n  padding-bottom: 1rem;\n  cursor: pointer;\n  font-size: var(--default-font-size);\n  text-align: center;\n  padding: 0.5rem 1rem 1rem 1rem;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "body {\n  font-size: var(--default-font-size);\n}\n.login-box {\n  width: 300px;\n}\n.login-box__link {\n  display: block;\n  padding: 1rem;\n  font-size: var(--default-font-size);\n  text-align: center;\n  font-weight: normal !important;\n}\n.fade-enter-active, .fade-leave-active {\n  transition: opacity 0.3s;\n}\n.fade-enter, .fade-leave-to {\n  opacity: 0;\n}\n.alternative-logins {\n  display: flex;\n  flex-direction: column;\n  gap: 0.75rem;\n}\n.alternative-logins .button-vue {\n  box-sizing: border-box;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&":
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, "\nfieldset[data-v-66634656] {\n\ttext-align: center;\n}\ninput[type=submit][data-v-66634656] {\n\tmargin-top: 20px;\n}\n", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./core/src/OC/contactsmenu/contact.handlebars":
/*!*****************************************************!*\
  !*** ./core/src/OC/contactsmenu/contact.handlebars ***!
  \*****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.program(5, data, 0),"data":data,"loc":{"start":{"line":2,"column":1},"end":{"line":10,"column":8}}})) != null ? stack1 : "");
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileTitle") : stack1),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":2},"end":{"line":7,"column":9}}})) != null ? stack1 : "");
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "			<a class=\"profile-link--avatar\" href=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1), depth0))
    + "\">\n				<img src=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=32\" class=\"avatar\" srcset=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=32 1x, "
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=64 2x, "
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=128 4x\" alt=\"\">\n			</a>\n";
},"5":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<img src=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=32\" class=\"avatar\" srcset=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=32 1x, "
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=64 2x, "
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1), depth0))
    + "&size=128 4x\" alt=\"\">\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.program(11, data, 0),"data":data,"loc":{"start":{"line":12,"column":1},"end":{"line":20,"column":8}}})) != null ? stack1 : "");
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileTitle") : stack1),{"name":"if","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":13,"column":2},"end":{"line":17,"column":9}}})) != null ? stack1 : "");
},"9":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "			<a class=\"profile-link--avatar\" href=\""
    + container.escapeExpression(container.lambda(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1), depth0))
    + "\">\n				<div class=\"avatar\"></div>\n			</a>\n";
},"11":function(container,depth0,helpers,partials,data) {
    return "		<div class=\"avatar\"></div>\n";
},"13":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileTitle") : stack1),{"name":"if","hash":{},"fn":container.program(14, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":23,"column":1},"end":{"line":29,"column":8}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1),{"name":"if","hash":{},"fn":container.program(16, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":30,"column":1},"end":{"line":34,"column":8}}})) != null ? stack1 : "");
},"14":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<a class=\"body profile-link--full-name\" href=\""
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1), depth0))
    + "\">\n			<div class=\"full-name\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"fullName") : stack1), depth0))
    + "</div>\n			<div class=\"last-message\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"lastMessage") : stack1), depth0))
    + "</div>\n			<div class=\"email-address\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"emailAddresses") : stack1), depth0))
    + "</div>\n		</a>\n";
},"16":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<a class=\"top-action\" href=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"hyperlink") : stack1), depth0))
    + "\" title=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n			<img src=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"icon") : stack1), depth0))
    + "\" alt=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n		</a>\n";
},"18":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1),{"name":"if","hash":{},"fn":container.program(19, data, 0),"inverse":container.program(21, data, 0),"data":data,"loc":{"start":{"line":35,"column":0},"end":{"line":50,"column":0}}})) != null ? stack1 : "");
},"19":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<a class=\"body\" href=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"hyperlink") : stack1), depth0))
    + "\">\n		<div class=\"full-name\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"fullName") : stack1), depth0))
    + "</div>\n		<div class=\"last-message\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"lastMessage") : stack1), depth0))
    + "</div>\n		<div class=\"email-address\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"emailAddresses") : stack1), depth0))
    + "</div>\n	</a>\n  	<a class=\"top-action\" href=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"hyperlink") : stack1), depth0))
    + "\" title=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n  		<img src=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"icon") : stack1), depth0))
    + "\" alt=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"topAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n	</a>\n";
},"21":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<div class=\"body\">\n		<div class=\"full-name\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"fullName") : stack1), depth0))
    + "</div>\n		<div class=\"last-message\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"lastMessage") : stack1), depth0))
    + "</div>\n		<div class=\"email-address\">"
    + alias2(alias1(((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"emailAddresses") : stack1), depth0))
    + "</div>\n	</div>\n";
},"23":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.lambda, alias2=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<a class=\"second-action\" href=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"secondAction") : stack1)) != null ? lookupProperty(stack1,"hyperlink") : stack1), depth0))
    + "\" title=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"secondAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n	<img src=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"secondAction") : stack1)) != null ? lookupProperty(stack1,"icon") : stack1), depth0))
    + "\" alt=\""
    + alias2(alias1(((stack1 = ((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"secondAction") : stack1)) != null ? lookupProperty(stack1,"title") : stack1), depth0))
    + "\">\n</a>\n";
},"25":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<button class=\"other-actions icon-more\"></button>\n	<div class=\"menu popovermenu\">\n		<ul>\n"
    + ((stack1 = lookupProperty(helpers,"each").call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"actions") : stack1),{"name":"each","hash":{},"fn":container.program(26, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":60,"column":3},"end":{"line":67,"column":12}}})) != null ? stack1 : "")
    + "		</ul>\n	</div>\n";
},"26":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "			<li>\n				<a href=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"hyperlink") || (depth0 != null ? lookupProperty(depth0,"hyperlink") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"hyperlink","hash":{},"data":data,"loc":{"start":{"line":62,"column":13},"end":{"line":62,"column":26}}}) : helper)))
    + "\">\n					<img src=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"icon") || (depth0 != null ? lookupProperty(depth0,"icon") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"icon","hash":{},"data":data,"loc":{"start":{"line":63,"column":15},"end":{"line":63,"column":23}}}) : helper)))
    + "\" alt=\"\">\n					<span>"
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":64,"column":11},"end":{"line":64,"column":20}}}) : helper)))
    + "</span>\n				</a>\n			</li>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"avatar") : stack1),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(7, data, 0),"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":21,"column":7}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"profileUrl") : stack1),{"name":"if","hash":{},"fn":container.program(13, data, 0),"inverse":container.program(18, data, 0),"data":data,"loc":{"start":{"line":22,"column":0},"end":{"line":50,"column":7}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"hasTwoActions") : stack1),{"name":"if","hash":{},"fn":container.program(23, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":51,"column":0},"end":{"line":55,"column":7}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contact") : depth0)) != null ? lookupProperty(stack1,"hasManyActions") : stack1),{"name":"if","hash":{},"fn":container.program(25, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":56,"column":0},"end":{"line":70,"column":7}}})) != null ? stack1 : "");
},"useData":true});

/***/ }),

/***/ "./core/src/OC/contactsmenu/error.handlebars":
/*!***************************************************!*\
  !*** ./core/src/OC/contactsmenu/error.handlebars ***!
  \***************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"emptycontent\">\n	<div class=\"icon-search\"></div>\n	<h2>"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"couldNotLoadText") || (depth0 != null ? lookupProperty(depth0,"couldNotLoadText") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"couldNotLoadText","hash":{},"data":data,"loc":{"start":{"line":3,"column":5},"end":{"line":3,"column":25}}}) : helper)))
    + "</h2>\n</div>\n";
},"useData":true});

/***/ }),

/***/ "./core/src/OC/contactsmenu/list.handlebars":
/*!**************************************************!*\
  !*** ./core/src/OC/contactsmenu/list.handlebars ***!
  \**************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"emptycontent\">\n	<div class=\"icon-search\"></div>\n	<h2>"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"noContactsFoundText") || (depth0 != null ? lookupProperty(depth0,"noContactsFoundText") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"noContactsFoundText","hash":{},"data":data,"loc":{"start":{"line":4,"column":5},"end":{"line":4,"column":28}}}) : helper)))
    + "</h2>\n</div>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"footer\"><a href=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"contactsAppURL") || (depth0 != null ? lookupProperty(depth0,"contactsAppURL") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"contactsAppURL","hash":{},"data":data,"loc":{"start":{"line":9,"column":29},"end":{"line":9,"column":47}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"showAllContactsText") || (depth0 != null ? lookupProperty(depth0,"showAllContactsText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"showAllContactsText","hash":{},"data":data,"loc":{"start":{"line":9,"column":49},"end":{"line":9,"column":72}}}) : helper)))
    + "</a></div>\n";
},"5":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"canInstallApp") : depth0),{"name":"if","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":10,"column":0},"end":{"line":12,"column":0}}})) != null ? stack1 : "");
},"6":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"footer\"><a href=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"contactsAppMgmtURL") || (depth0 != null ? lookupProperty(depth0,"contactsAppMgmtURL") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"contactsAppMgmtURL","hash":{},"data":data,"loc":{"start":{"line":11,"column":29},"end":{"line":11,"column":51}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"contactsAppMgmtText") || (depth0 != null ? lookupProperty(depth0,"contactsAppMgmtText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"contactsAppMgmtText","hash":{},"data":data,"loc":{"start":{"line":11,"column":53},"end":{"line":11,"column":76}}}) : helper)))
    + "</a></div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"unless").call(alias1,((stack1 = (depth0 != null ? lookupProperty(depth0,"contacts") : depth0)) != null ? lookupProperty(stack1,"length") : stack1),{"name":"unless","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":6,"column":11}}})) != null ? stack1 : "")
    + "<div id=\"contactsmenu-contacts\"></div>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"contactsAppEnabled") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.program(5, data, 0),"data":data,"loc":{"start":{"line":8,"column":0},"end":{"line":12,"column":7}}})) != null ? stack1 : "");
},"useData":true});

/***/ }),

/***/ "./core/src/OC/contactsmenu/loading.handlebars":
/*!*****************************************************!*\
  !*** ./core/src/OC/contactsmenu/loading.handlebars ***!
  \*****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"emptycontent\">\n	<div class=\"icon-loading\"></div>\n	<h2>"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"loadingText") || (depth0 != null ? lookupProperty(depth0,"loadingText") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"loadingText","hash":{},"data":data,"loc":{"start":{"line":3,"column":5},"end":{"line":3,"column":20}}}) : helper)))
    + "</h2>\n</div>\n";
},"useData":true});

/***/ }),

/***/ "./core/src/OC/contactsmenu/menu.handlebars":
/*!**************************************************!*\
  !*** ./core/src/OC/contactsmenu/menu.handlebars ***!
  \**************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<label class=\"hidden-visually\" for=\"contactsmenu-search\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"searchContactsText") || (depth0 != null ? lookupProperty(depth0,"searchContactsText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"searchContactsText","hash":{},"data":data,"loc":{"start":{"line":1,"column":57},"end":{"line":1,"column":79}}}) : helper)))
    + "</label>\n<input id=\"contactsmenu-search\" type=\"search\" placeholder=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"searchContactsText") || (depth0 != null ? lookupProperty(depth0,"searchContactsText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"searchContactsText","hash":{},"data":data,"loc":{"start":{"line":2,"column":59},"end":{"line":2,"column":81}}}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"searchTerm") || (depth0 != null ? lookupProperty(depth0,"searchTerm") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"searchTerm","hash":{},"data":data,"loc":{"start":{"line":2,"column":90},"end":{"line":2,"column":104}}}) : helper)))
    + "\">\n<div class=\"content\">\n</div>\n";
},"useData":true});

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

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./core/src/components/login/LoginButton.vue":
/*!***************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true& */ "./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true&");
/* harmony import */ var _LoginButton_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=script&lang=js& */ "./core/src/components/login/LoginButton.vue?vue&type=script&lang=js&");
/* harmony import */ var _LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& */ "./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LoginButton_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2b0f9fce",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/login/LoginButton.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/login/LoginForm.vue":
/*!*************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=template&id=722a846b&scoped=true& */ "./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true&");
/* harmony import */ var _LoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=script&lang=js& */ "./core/src/components/login/LoginForm.vue?vue&type=script&lang=js&");
/* harmony import */ var _LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& */ "./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "722a846b",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/login/LoginForm.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/login/PasswordLessLoginForm.vue":
/*!*************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue ***!
  \*************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true& */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true&");
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=script&lang=js& */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js&");
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PasswordLessLoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "34bf48f7",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/login/PasswordLessLoginForm.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/login/ResetPassword.vue":
/*!*****************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true& */ "./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true&");
/* harmony import */ var _ResetPassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=script&lang=js& */ "./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js&");
/* harmony import */ var _ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& */ "./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ResetPassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "a10057b0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/login/ResetPassword.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/login/UpdatePassword.vue":
/*!******************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue ***!
  \******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=template&id=66634656&scoped=true& */ "./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true&");
/* harmony import */ var _UpdatePassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=script&lang=js& */ "./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js&");
/* harmony import */ var _UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& */ "./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UpdatePassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "66634656",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/login/UpdatePassword.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/views/Login.vue":
/*!**********************************!*\
  !*** ./core/src/views/Login.vue ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Login.vue?vue&type=template&id=020fd45b& */ "./core/src/views/Login.vue?vue&type=template&id=020fd45b&");
/* harmony import */ var _Login_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Login.vue?vue&type=script&lang=js& */ "./core/src/views/Login.vue?vue&type=script&lang=js&");
/* harmony import */ var _Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& */ "./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Login_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__.render,
  _Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/views/Login.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/login/LoginButton.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/login/LoginForm.vue?vue&type=script&lang=js&":
/*!**************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=script&lang=js& ***!
  \**************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/views/Login.vue?vue&type=script&lang=js&":
/*!***********************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=script&lang=js& ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true&":
/*!**********************************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true& ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true&":
/*!********************************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true& ***!
  \********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=template&id=722a846b&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true&":
/*!********************************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true& ***!
  \********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true&":
/*!*************************************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=template&id=66634656&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true&");


/***/ }),

/***/ "./core/src/views/Login.vue?vue&type=template&id=020fd45b&":
/*!*****************************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=template&id=020fd45b& ***!
  \*****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=template&id=020fd45b& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b&");


/***/ }),

/***/ "./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&":
/*!*************************************************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& ***!
  \*************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&":
/*!***********************************************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& ***!
  \***********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&":
/*!***********************************************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&":
/*!***************************************************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&":
/*!********************************************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&lang=scss&");


/***/ }),

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&":
/*!***************************************************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css&");


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
/******/ 			"core-login": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./core/src/login.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-login.js.map?v=5f1b6fbad35f17c81c3b