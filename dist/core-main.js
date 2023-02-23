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

/***/ "./core/src/OCP/accessibility.js":
/*!***************************************!*\
  !*** ./core/src/OCP/accessibility.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * @return {boolean} Whether the user opted-out of shortcuts so that they should not be registered
   */
  disableKeyboardShortcuts: function disableKeyboardShortcuts() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('theming', 'shortcutsDisabled', false);
  }
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

/***/ "./core/src/OCP/collaboration.js":
/*!***************************************!*\
  !*** ./core/src/OCP/collaboration.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "l10nProjects": function() { return /* binding */ l10nProjects; }
/* harmony export */ });
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
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



/**
 * @typedef TypeDefinition
 * @function {Function} action This action is executed to let the user select a resource
 * @param {string} icon Contains the icon css class for the type
 * @function Object() { [native code] }
 */

/**
 * @type {TypeDefinition[]}
 */
var types = {};

/**
 * Those translations will be used by the vue component but they should be shipped with the server
 * FIXME: Those translations should be added to the library
 *
 * @return {Array}
 */
var l10nProjects = function l10nProjects() {
  return [t('core', 'Add to a project'), t('core', 'Show details'), t('core', 'Hide details'), t('core', 'Rename project'), t('core', 'Failed to rename the project'), t('core', 'Failed to create a project'), t('core', 'Failed to add the item to the project'), t('core', 'Connect items to a project to make them easier to find'), t('core', 'Type to search for existing projects')];
};
/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   *
   * @param {string} type type
   * @param {TypeDefinition} typeDefinition typeDefinition
   */
  registerType: function registerType(type, typeDefinition) {
    types[type] = typeDefinition;
  },
  trigger: function trigger(type) {
    return types[type].action();
  },
  getTypes: function getTypes() {
    return Object.keys(types);
  },
  getIcon: function getIcon(type) {
    return types[type].typeIconClass || '';
  },
  getLabel: function getLabel(type) {
    return escape_html__WEBPACK_IMPORTED_MODULE_0___default()(types[type].typeString || type);
  },
  getLink: function getLink(type, id) {
    /* TODO: Allow action to be executed instead of href as well */
    return typeof types[type] !== 'undefined' ? types[type].link(id) : '';
  }
});

/***/ }),

/***/ "./core/src/OCP/comments.js":
/*!**********************************!*\
  !*** ./core/src/OCP/comments.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "formatLinksPlain": function() { return /* binding */ formatLinksPlain; },
/* harmony export */   "formatLinksRich": function() { return /* binding */ formatLinksRich; },
/* harmony export */   "plainToRich": function() { return /* binding */ plainToRich; },
/* harmony export */   "richToPlain": function() { return /* binding */ richToPlain; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
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



/*
 * Detects links:
 * Either the http(s) protocol is given or two strings, basically limited to ascii with the last
 * word being at least one digit long,
 * followed by at least another character
 *
 * The downside: anything not ascii is excluded. Not sure how common it is in areas using different
 * alphabets… the upside: fake domains with similar looking characters won't be formatted as links
 *
 * This is a copy of the backend regex in IURLGenerator, make sure to adjust both when changing
 */
var urlRegex = /(\s|^)(https?:\/\/)((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/ig;

/**
 * @param {any} content -
 */
function plainToRich(content) {
  return this.formatLinksRich(content);
}

/**
 * @param {any} content -
 */
function richToPlain(content) {
  return this.formatLinksPlain(content);
}

/**
 * @param {any} content -
 */
function formatLinksRich(content) {
  return content.replace(urlRegex, function (_, leadingSpace, protocol, url, trailingSpace) {
    var linkText = url;
    if (!protocol) {
      protocol = 'https://';
    } else if (protocol === 'http://') {
      linkText = protocol + url;
    }
    return leadingSpace + '<a class="external" target="_blank" rel="noopener noreferrer" href="' + protocol + url + '">' + linkText + '</a>' + trailingSpace;
  });
}

/**
 * @param {any} content -
 */
function formatLinksPlain(content) {
  var $content = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<div></div>').html(content);
  $content.find('a').each(function () {
    var $this = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    $this.html($this.attr('href'));
  });
  return $content.html();
}

/***/ }),

/***/ "./core/src/OCP/index.js":
/*!*******************************!*\
  !*** ./core/src/OCP/index.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _appconfig__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./appconfig */ "./core/src/OCP/appconfig.js");
/* harmony import */ var _comments__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./comments */ "./core/src/OCP/comments.js");
/* harmony import */ var _whatsnew__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./whatsnew */ "./core/src/OCP/whatsnew.js");
/* harmony import */ var _accessibility__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./accessibility */ "./core/src/OCP/accessibility.js");
/* harmony import */ var _collaboration__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./collaboration */ "./core/src/OCP/collaboration.js");
/* harmony import */ var _loader__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./loader */ "./core/src/OCP/loader.js");
/* harmony import */ var _toast__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./toast */ "./core/src/OCP/toast.js");
/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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










/** @namespace OCP */
/* harmony default export */ __webpack_exports__["default"] = ({
  Accessibility: _accessibility__WEBPACK_IMPORTED_MODULE_4__["default"],
  AppConfig: _appconfig__WEBPACK_IMPORTED_MODULE_1__,
  Collaboration: _collaboration__WEBPACK_IMPORTED_MODULE_5__["default"],
  Comments: _comments__WEBPACK_IMPORTED_MODULE_2__,
  InitialState: {
    /**
     * @deprecated 18.0.0 add https://www.npmjs.com/package/@nextcloud/initial-state to your app
     */
    loadState: _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState
  },
  Loader: _loader__WEBPACK_IMPORTED_MODULE_6__["default"],
  /**
   * @deprecated 19.0.0 use the `@nextcloud/dialogs` package instead
   */
  Toast: _toast__WEBPACK_IMPORTED_MODULE_7__["default"],
  WhatsNew: _whatsnew__WEBPACK_IMPORTED_MODULE_3__
});

/***/ }),

/***/ "./core/src/OCP/loader.js":
/*!********************************!*\
  !*** ./core/src/OCP/loader.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
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

var loadedScripts = {};
var loadedStylesheets = {};
/**
 * @namespace OCP
 * @class Loader
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * Load a script asynchronously
   *
   * @param {string} app the app name
   * @param {string} file the script file name
   * @return {Promise}
   */
  loadScript: function loadScript(app, file) {
    var key = app + file;
    if (Object.prototype.hasOwnProperty.call(loadedScripts, key)) {
      return Promise.resolve();
    }
    loadedScripts[key] = true;
    return new Promise(function (resolve, reject) {
      var scriptPath = OC.filePath(app, 'js', file);
      var script = document.createElement('script');
      script.src = scriptPath;
      script.setAttribute('nonce', btoa(OC.requestToken));
      script.onload = function () {
        return resolve();
      };
      script.onerror = function () {
        return reject(new Error("Failed to load script from ".concat(scriptPath)));
      };
      document.head.appendChild(script);
    });
  },
  /**
   * Load a stylesheet file asynchronously
   *
   * @param {string} app the app name
   * @param {string} file the script file name
   * @return {Promise}
   */
  loadStylesheet: function loadStylesheet(app, file) {
    var key = app + file;
    if (Object.prototype.hasOwnProperty.call(loadedStylesheets, key)) {
      return Promise.resolve();
    }
    loadedStylesheets[key] = true;
    return new Promise(function (resolve, reject) {
      var stylePath = OC.filePath(app, 'css', file);
      var link = document.createElement('link');
      link.href = stylePath;
      link.type = 'text/css';
      link.rel = 'stylesheet';
      link.onload = function () {
        return resolve();
      };
      link.onerror = function () {
        return reject(new Error("Failed to load stylesheet from ".concat(stylePath)));
      };
      document.head.appendChild(link);
    });
  }
});

/***/ }),

/***/ "./core/src/OCP/toast.js":
/*!*******************************!*\
  !*** ./core/src/OCP/toast.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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


/* harmony default export */ __webpack_exports__["default"] = ({
  /**
   * @deprecated 19.0.0 use `showSuccess` from the `@nextcloud/dialogs` package instead
   *
   * @param {string} text the toast text
   * @param {object} options options
   * @return {Toast}
   */
  success: function success(text, options) {
    return (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(text, options);
  },
  /**
   * @deprecated 19.0.0 use `showWarning` from the `@nextcloud/dialogs` package instead
   *
   * @param {string} text the toast text
   * @param {object} options options
   * @return {Toast}
   */
  warning: function warning(text, options) {
    return (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showWarning)(text, options);
  },
  /**
   * @deprecated 19.0.0 use `showError` from the `@nextcloud/dialogs` package instead
   *
   * @param {string} text the toast text
   * @param {object} options options
   * @return {Toast}
   */
  error: function error(text, options) {
    return (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(text, options);
  },
  /**
   * @deprecated 19.0.0 use `showInfo` from the `@nextcloud/dialogs` package instead
   *
   * @param {string} text the toast text
   * @param {object} options options
   * @return {Toast}
   */
  info: function info(text, options) {
    return (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showInfo)(text, options);
  },
  /**
   * @deprecated 19.0.0 use `showMessage` from the `@nextcloud/dialogs` package instead
   *
   * @param {string} text the toast text
   * @param {object} options options
   * @return {Toast}
   */
  message: function message(text, options) {
    return (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showMessage)(text, options);
  }
});

/***/ }),

/***/ "./core/src/OCP/whatsnew.js":
/*!**********************************!*\
  !*** ./core/src/OCP/whatsnew.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "dismiss": function() { return /* binding */ dismiss; },
/* harmony export */   "query": function() { return /* binding */ query; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * @param {any} options -
 */
function query(options) {
  options = options || {};
  var dismissOptions = options.dismiss || {};
  jquery__WEBPACK_IMPORTED_MODULE_1___default().ajax({
    type: 'GET',
    url: options.url || (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('core/whatsnew?format=json'),
    success: options.success || function (data, statusText, xhr) {
      onQuerySuccess(data, statusText, xhr, dismissOptions);
    },
    error: options.error || onQueryError
  });
}

/**
 * @param {any} version -
 * @param {any} options -
 */
function dismiss(version, options) {
  options = options || {};
  jquery__WEBPACK_IMPORTED_MODULE_1___default().ajax({
    type: 'POST',
    url: options.url || (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('core/whatsnew'),
    data: {
      version: encodeURIComponent(version)
    },
    success: options.success || onDismissSuccess,
    error: options.error || onDismissError
  });
  // remove element immediately
  jquery__WEBPACK_IMPORTED_MODULE_1___default()('.whatsNewPopover').remove();
}

/**
 * @param {any} data -
 * @param {any} statusText -
 * @param {any} xhr -
 * @param {any} dismissOptions -
 */
function onQuerySuccess(data, statusText, xhr, dismissOptions) {
  console.debug('querying Whats New data was successful: ' + statusText);
  console.debug(data);
  if (xhr.status !== 200) {
    return;
  }
  var item, menuItem, text, icon;
  var div = document.createElement('div');
  div.classList.add('popovermenu', 'open', 'whatsNewPopover', 'menu-left');
  var list = document.createElement('ul');

  // header
  item = document.createElement('li');
  menuItem = document.createElement('span');
  menuItem.className = 'menuitem';
  text = document.createElement('span');
  text.innerText = t('core', 'New in') + ' ' + data.ocs.data.product;
  text.className = 'caption';
  menuItem.appendChild(text);
  icon = document.createElement('span');
  icon.className = 'icon-close';
  icon.onclick = function () {
    dismiss(data.ocs.data.version, dismissOptions);
  };
  menuItem.appendChild(icon);
  item.appendChild(menuItem);
  list.appendChild(item);

  // Highlights
  for (var i in data.ocs.data.whatsNew.regular) {
    var whatsNewTextItem = data.ocs.data.whatsNew.regular[i];
    item = document.createElement('li');
    menuItem = document.createElement('span');
    menuItem.className = 'menuitem';
    icon = document.createElement('span');
    icon.className = 'icon-checkmark';
    menuItem.appendChild(icon);
    text = document.createElement('p');
    text.innerHTML = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].escape(whatsNewTextItem);
    menuItem.appendChild(text);
    item.appendChild(menuItem);
    list.appendChild(item);
  }

  // Changelog URL
  if (!underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isUndefined(data.ocs.data.changelogURL)) {
    item = document.createElement('li');
    menuItem = document.createElement('a');
    menuItem.href = data.ocs.data.changelogURL;
    menuItem.rel = 'noreferrer noopener';
    menuItem.target = '_blank';
    icon = document.createElement('span');
    icon.className = 'icon-link';
    menuItem.appendChild(icon);
    text = document.createElement('span');
    text.innerText = t('core', 'View changelog');
    menuItem.appendChild(text);
    item.appendChild(menuItem);
    list.appendChild(item);
  }
  div.appendChild(list);
  document.body.appendChild(div);
}

/**
 * @param {any} x -
 * @param {any} t -
 * @param {any} e -
 */
function onQueryError(x, t, e) {
  console.debug('querying Whats New Data resulted in an error: ' + t + e);
  console.debug(x);
}

/**
 * @param {any} data -
 */
function onDismissSuccess(data) {
  // noop
}

/**
 * @param {any} data -
 */
function onDismissError(data) {
  console.debug('dismissing Whats New data resulted in an error: ' + data);
}

/***/ }),

/***/ "./core/src/Polyfill/index.js":
/*!************************************!*\
  !*** ./core/src/Polyfill/index.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var focus_visible__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! focus-visible */ "./node_modules/focus-visible/dist/focus-visible.js");
/* harmony import */ var focus_visible__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(focus_visible__WEBPACK_IMPORTED_MODULE_0__);
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



/***/ }),

/***/ "./core/src/Polyfill/tooltip.js":
/*!**************************************!*\
  !*** ./core/src/Polyfill/tooltip.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright 2019 Julius Härtl <jus@bitgrid.net>
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


(jquery__WEBPACK_IMPORTED_MODULE_0___default().prototype.tooltip) = function (tooltip) {
  return function (config) {
    try {
      return tooltip.call(this, config);
    } catch (ex) {
      if (ex instanceof TypeError && config === 'destroy') {
        if (window.TESTING === undefined) {
          console.error('Deprecated call $.tooltip(\'destroy\') has been deprecated and should be removed');
        }
        return tooltip.call(this, 'dispose');
      }
      if (ex instanceof TypeError && config === 'fixTitle') {
        if (window.TESTING === undefined) {
          console.error('Deprecated call $.tooltip(\'fixTitle\') has been deprecated and should be removed');
        }
        return tooltip.call(this, '_fixTitle');
      }
    }
  };
}((jquery__WEBPACK_IMPORTED_MODULE_0___default().prototype.tooltip));

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

/***/ "./core/src/components/ContactsMenu.js":
/*!*********************************************!*\
  !*** ./core/src/components/ContactsMenu.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "setUp": function() { return /* binding */ setUp; }
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_ContactsMenu_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../views/ContactsMenu.vue */ "./core/src/views/ContactsMenu.vue");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christopher Ng <chrng8@gmail.com>
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
 * @todo move to contacts menu code https://github.com/orgs/nextcloud/projects/31#card-21213129
 */
var setUp = function setUp() {
  var mountPoint = document.getElementById('contactsmenu');
  if (mountPoint) {
    // eslint-disable-next-line no-new
    new vue__WEBPACK_IMPORTED_MODULE_1__["default"]({
      el: mountPoint,
      render: function render(h) {
        return h(_views_ContactsMenu_vue__WEBPACK_IMPORTED_MODULE_0__["default"]);
      }
    });
  }
};

/***/ }),

/***/ "./core/src/components/MainMenu.js":
/*!*****************************************!*\
  !*** ./core/src/components/MainMenu.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "setUp": function() { return /* binding */ setUp; }
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _AppMenu_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppMenu.vue */ "./core/src/components/AppMenu.vue");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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




var setUp = function setUp() {
  vue__WEBPACK_IMPORTED_MODULE_2__["default"].mixin({
    methods: {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
      n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translatePlural
    }
  });
  var container = document.getElementById('header-left__appmenu');
  if (!container) {
    // no container, possibly we're on a public page
    return;
  }
  var AppMenuApp = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_AppMenu_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
  var appMenu = new AppMenuApp({}).$mount(container);
  Object.assign(OC, {
    setNavigationCounter: function setNavigationCounter(id, counter) {
      appMenu.setNavigationCounter(id, counter);
    }
  });
};

/***/ }),

/***/ "./core/src/components/UserMenu.js":
/*!*****************************************!*\
  !*** ./core/src/components/UserMenu.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "setUp": function() { return /* binding */ setUp; }
/* harmony export */ });
/* harmony import */ var _OC__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../OC */ "./core/src/OC/index.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
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



var setUp = function setUp() {
  var $menu = jquery__WEBPACK_IMPORTED_MODULE_1___default()('#header #settings');
  // Using page terminoogy as below
  var $excludedPageClasses = ['user-status-menu-item__header'];

  // show loading feedback
  $menu.delegate('a', 'click', function (event) {
    var $page = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.target);
    if (!$page.is('a')) {
      $page = $page.closest('a');
    }
    if (event.which === 1 && !event.ctrlKey && !event.metaKey) {
      if (!$excludedPageClasses.includes($page.attr('class'))) {
        $page.find('img').remove();
        $page.find('div').remove(); // prevent odd double-clicks
        $page.prepend(jquery__WEBPACK_IMPORTED_MODULE_1___default()('<div></div>').addClass('icon-loading-small'));
      }
    } else {
      // Close navigation when opening menu entry in
      // a new tab
      _OC__WEBPACK_IMPORTED_MODULE_0__["default"].hideMenus(function () {
        return false;
      });
    }
  });
  $menu.delegate('a', 'mouseup', function (event) {
    if (event.which === 2) {
      // Close navigation when opening app in
      // a new tab via middle click
      _OC__WEBPACK_IMPORTED_MODULE_0__["default"].hideMenus(function () {
        return false;
      });
    }
  });
};

/***/ }),

/***/ "./core/src/globals.js":
/*!*****************************!*\
  !*** ./core/src/globals.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _init__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./init */ "./core/src/init.js");
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var jquery_migrate_dist_jquery_migrate_min__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! jquery-migrate/dist/jquery-migrate.min */ "./node_modules/jquery-migrate/dist/jquery-migrate.min.js");
/* harmony import */ var jquery_migrate_dist_jquery_migrate_min__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(jquery_migrate_dist_jquery_migrate_min__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var jquery_ui_dist_jquery_ui__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! jquery-ui-dist/jquery-ui */ "./node_modules/jquery-ui-dist/jquery-ui.js");
/* harmony import */ var jquery_ui_dist_jquery_ui__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(jquery_ui_dist_jquery_ui__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var jquery_ui_dist_jquery_ui_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! jquery-ui-dist/jquery-ui.css */ "./node_modules/jquery-ui-dist/jquery-ui.css");
/* harmony import */ var jquery_ui_dist_jquery_ui_theme_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! jquery-ui-dist/jquery-ui.theme.css */ "./node_modules/jquery-ui-dist/jquery-ui.theme.css");
/* harmony import */ var autosize__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! autosize */ "./node_modules/autosize/dist/autosize.esm.js");
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! backbone */ "./node_modules/backbone/backbone.js");
/* harmony import */ var backbone__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(backbone__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! bootstrap/js/dist/tooltip */ "./node_modules/bootstrap/js/dist/tooltip.js");
/* harmony import */ var bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _Polyfill_tooltip__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Polyfill/tooltip */ "./core/src/Polyfill/tooltip.js");
/* harmony import */ var clipboard__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! clipboard */ "./node_modules/clipboard/dist/clipboard.js");
/* harmony import */ var clipboard__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(clipboard__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var davclient_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! davclient.js */ "./node_modules/davclient.js/lib/client.js");
/* harmony import */ var davclient_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(davclient_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var handlebars__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! handlebars */ "./node_modules/handlebars/dist/cjs/handlebars.runtime.js");
/* harmony import */ var handlebars__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(handlebars__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var blueimp_md5__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! blueimp-md5 */ "./node_modules/blueimp-md5/js/md5.js");
/* harmony import */ var blueimp_md5__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(blueimp_md5__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! moment */ "./node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var select2__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! select2 */ "./node_modules/select2/select2.js");
/* harmony import */ var select2__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(select2__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var select2_select2_css__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! select2/select2.css */ "./node_modules/select2/select2.css");
/* harmony import */ var snap_js_dist_snap__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! snap.js/dist/snap */ "./node_modules/snap.js/dist/snap.js");
/* harmony import */ var snap_js_dist_snap__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(snap_js_dist_snap__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var strengthify__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! strengthify */ "./node_modules/strengthify/jquery.strengthify.js");
/* harmony import */ var strengthify__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(strengthify__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var strengthify_strengthify_css__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! strengthify/strengthify.css */ "./node_modules/strengthify/strengthify.css");
/* harmony import */ var _OC_index__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./OC/index */ "./core/src/OC/index.js");
/* harmony import */ var _OCP_index__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./OCP/index */ "./core/src/OCP/index.js");
/* harmony import */ var _OCA_index__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./OCA/index */ "./core/src/OCA/index.js");
/* harmony import */ var _OC_requesttoken__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./OC/requesttoken */ "./core/src/OC/requesttoken.js");
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

/* eslint-disable @nextcloud/no-deprecations */




// TODO: switch to `jquery-ui` package and import widgets and effects individually
//       `jquery-ui-dist` is used as a workaround for the issue of missing effects



// END TODO


















var warnIfNotTesting = function warnIfNotTesting() {
  if (window.TESTING === undefined) {
    console.warn.apply(console, arguments);
  }
};

/**
 * Mark a function as deprecated and automatically
 * warn if used!
 *
 * @param {Function} func the library to deprecate
 * @param {string} funcName the name of the library
 * @param {number} version the version this gets removed
 * @return {Function}
 */
var deprecate = function deprecate(func, funcName, version) {
  var oldFunc = func;
  var newFunc = function newFunc() {
    warnIfNotTesting("The ".concat(funcName, " library is deprecated! It will be removed in nextcloud ").concat(version, "."));
    return oldFunc.apply(this, arguments);
  };
  Object.assign(newFunc, oldFunc);
  return newFunc;
};
var setDeprecatedProp = function setDeprecatedProp(global, cb, msg) {
  (Array.isArray(global) ? global : [global]).forEach(function (global) {
    if (window[global] !== undefined) {
      delete window[global];
    }
    Object.defineProperty(window, global, {
      get: function get() {
        if (msg) {
          warnIfNotTesting("".concat(global, " is deprecated: ").concat(msg));
        } else {
          warnIfNotTesting("".concat(global, " is deprecated"));
        }
        return cb();
      }
    });
  });
};
window._ = underscore__WEBPACK_IMPORTED_MODULE_1__["default"];
setDeprecatedProp(['$', 'jQuery'], function () {
  return (jquery__WEBPACK_IMPORTED_MODULE_2___default());
}, 'The global jQuery is deprecated. It will be removed in a later versions without another warning. Please ship your own.');
setDeprecatedProp('autosize', function () {
  return autosize__WEBPACK_IMPORTED_MODULE_7__["default"];
}, 'please ship your own, this will be removed in Nextcloud 20');
setDeprecatedProp('Backbone', function () {
  return (backbone__WEBPACK_IMPORTED_MODULE_8___default());
}, 'please ship your own, this will be removed in Nextcloud 20');
setDeprecatedProp(['Clipboard', 'ClipboardJS'], function () {
  return (clipboard__WEBPACK_IMPORTED_MODULE_11___default());
}, 'please ship your own, this will be removed in Nextcloud 20');
window.dav = davclient_js__WEBPACK_IMPORTED_MODULE_12__.dav;
setDeprecatedProp('Handlebars', function () {
  return (handlebars__WEBPACK_IMPORTED_MODULE_13___default());
}, 'please ship your own, this will be removed in Nextcloud 20');
setDeprecatedProp('md5', function () {
  return (blueimp_md5__WEBPACK_IMPORTED_MODULE_14___default());
}, 'please ship your own, this will be removed in Nextcloud 20');
setDeprecatedProp('moment', function () {
  return (moment__WEBPACK_IMPORTED_MODULE_15___default());
}, 'please ship your own, this will be removed in Nextcloud 20');
window.OC = _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"];
setDeprecatedProp('initCore', function () {
  return _init__WEBPACK_IMPORTED_MODULE_0__.initCore;
}, 'this is an internal function');
setDeprecatedProp('oc_appswebroots', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].appswebroots;
}, 'use OC.appswebroots instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_config', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].config;
}, 'use OC.config instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_current_user', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].getCurrentUser().uid;
}, 'use OC.getCurrentUser().uid instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_debug', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].debug;
}, 'use OC.debug instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_defaults', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].theme;
}, 'use OC.theme instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_isadmin', _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].isUserAdmin, 'use OC.isUserAdmin() instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_requesttoken', function () {
  return (0,_OC_requesttoken__WEBPACK_IMPORTED_MODULE_24__.getToken)();
}, 'use OC.requestToken instead, this will be removed in Nextcloud 20');
setDeprecatedProp('oc_webroot', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].webroot;
}, 'use OC.getRootPath() instead, this will be removed in Nextcloud 20');
setDeprecatedProp('OCDialogs', function () {
  return _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].dialogs;
}, 'use OC.dialogs instead, this will be removed in Nextcloud 20');
window.OCP = _OCP_index__WEBPACK_IMPORTED_MODULE_22__["default"];
window.OCA = _OCA_index__WEBPACK_IMPORTED_MODULE_23__["default"];
(jquery__WEBPACK_IMPORTED_MODULE_2___default().fn.select2) = deprecate((jquery__WEBPACK_IMPORTED_MODULE_2___default().fn.select2), 'select2', 19);

/**
 * translate a string
 *
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text the string to translate
 * @param [vars] map of placeholder key to value
 * @param {number} [count] number to replace %n with
 * @return {string}
 */
window.t = underscore__WEBPACK_IMPORTED_MODULE_1__["default"].bind(_OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].L10N.translate, _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].L10N);

/**
 * translate a string
 *
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text_singular the string to translate for exactly one object
 * @param {string} text_plural the string to translate for n objects
 * @param {number} count number to determine whether to use singular or plural
 * @param [vars] map of placeholder key to value
 * @return {string} Translated string
 */
window.n = underscore__WEBPACK_IMPORTED_MODULE_1__["default"].bind(_OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].L10N.translatePlural, _OC_index__WEBPACK_IMPORTED_MODULE_21__["default"].L10N);

/***/ }),

/***/ "./core/src/init.js":
/*!**************************!*\
  !*** ./core/src/init.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "initCore": function() { return /* binding */ initCore; }
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! moment */ "./node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _session_heartbeat_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./session-heartbeat.js */ "./core/src/session-heartbeat.js");
/* harmony import */ var _OC_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./OC/index.js */ "./core/src/OC/index.js");
/* harmony import */ var _components_ContactsMenu_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/ContactsMenu.js */ "./core/src/components/ContactsMenu.js");
/* harmony import */ var _components_MainMenu_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/MainMenu.js */ "./core/src/components/MainMenu.js");
/* harmony import */ var _components_UserMenu_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/UserMenu.js */ "./core/src/components/UserMenu.js");
/* harmony import */ var _OC_password_confirmation_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./OC/password-confirmation.js */ "./core/src/OC/password-confirmation.js");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author nacho <nacho@ownyourbits.com>
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

/* globals Snap */










// keep in sync with core/css/variables.scss
var breakpointMobileWidth = 1024;
var initLiveTimestamps = function initLiveTimestamps() {
  // Update live timestamps every 30 seconds
  setInterval(function () {
    jquery__WEBPACK_IMPORTED_MODULE_1___default()('.live-relative-timestamp').each(function () {
      var timestamp = parseInt(jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).attr('data-timestamp'), 10);
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).text(moment__WEBPACK_IMPORTED_MODULE_2___default()(timestamp).fromNow());
    });
  }, 30 * 1000);
};

/**
 * Moment doesn't have aliases for every locale and doesn't parse some locale IDs correctly so we need to alias them
 */
var localeAliases = {
  zh: 'zh-cn',
  zh_Hans: 'zh-cn',
  zh_Hans_CN: 'zh-cn',
  zh_Hans_HK: 'zh-cn',
  zh_Hans_MO: 'zh-cn',
  zh_Hans_SG: 'zh-cn',
  zh_Hant: 'zh-hk',
  zh_Hant_HK: 'zh-hk',
  zh_Hant_MO: 'zh-mo',
  zh_Hant_TW: 'zh-tw'
};
var locale = _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].getLocale();
if (Object.prototype.hasOwnProperty.call(localeAliases, locale)) {
  locale = localeAliases[locale];
}

/**
 * Set users locale to moment.js as soon as possible
 */
moment__WEBPACK_IMPORTED_MODULE_2___default().locale(locale);

/**
 * Initializes core
 */
var initCore = function initCore() {
  jquery__WEBPACK_IMPORTED_MODULE_1___default()(window).on('unload.main', function () {
    _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"]._unloadCalled = true;
  });
  jquery__WEBPACK_IMPORTED_MODULE_1___default()(window).on('beforeunload.main', function () {
    // super-trick thanks to http://stackoverflow.com/a/4651049
    // in case another handler displays a confirmation dialog (ex: navigating away
    // during an upload), there are two possible outcomes: user clicked "ok" or
    // "cancel"

    // first timeout handler is called after unload dialog is closed
    setTimeout(function () {
      _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"]._userIsNavigatingAway = true;

      // second timeout event is only called if user cancelled (Chrome),
      // but in other browsers it might still be triggered, so need to
      // set a higher delay...
      setTimeout(function () {
        if (!_OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"]._unloadCalled) {
          _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"]._userIsNavigatingAway = false;
        }
      }, 10000);
    }, 1);
  });
  jquery__WEBPACK_IMPORTED_MODULE_1___default()(document).on('ajaxError.main', function (event, request, settings) {
    if (settings && settings.allowAuthErrors) {
      return;
    }
    _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"]._processAjaxError(request);
  });
  (0,_session_heartbeat_js__WEBPACK_IMPORTED_MODULE_3__.initSessionHeartBeat)();
  _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].registerMenu(jquery__WEBPACK_IMPORTED_MODULE_1___default()('#expand'), jquery__WEBPACK_IMPORTED_MODULE_1___default()('#expanddiv'), false, true);

  // toggle for menus
  jquery__WEBPACK_IMPORTED_MODULE_1___default()(document).on('mouseup.closemenus', function (event) {
    var $el = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.target);
    if ($el.closest('.menu').length || $el.closest('.menutoggle').length) {
      // don't close when clicking on the menu directly or a menu toggle
      return false;
    }
    _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].hideMenus();
  });
  (0,_components_MainMenu_js__WEBPACK_IMPORTED_MODULE_6__.setUp)();
  (0,_components_UserMenu_js__WEBPACK_IMPORTED_MODULE_7__.setUp)();
  (0,_components_ContactsMenu_js__WEBPACK_IMPORTED_MODULE_5__.setUp)();

  // just add snapper for logged in users
  // and if the app doesn't handle the nav slider itself
  if (jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-navigation').length && !jquery__WEBPACK_IMPORTED_MODULE_1___default()('html').hasClass('lte9') && !jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-content').hasClass('no-snapper')) {
    // App sidebar on mobile
    var snapper = new Snap({
      element: document.getElementById('app-content'),
      disable: 'right',
      maxPosition: 300,
      // $navigation-width
      minDragDistance: 100
    });
    jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-content').prepend('<div id="app-navigation-toggle" class="icon-menu" style="display:none" tabindex="0"></div>');

    // keep track whether snapper is currently animating, and
    // prevent to call open or close while that is the case
    // to avoid duplicating events (snap.js doesn't check this)
    var animating = false;
    snapper.on('animating', function () {
      // we need this because the trigger button
      // is also implicitly wired to close by snapper
      animating = true;
    });
    snapper.on('animated', function () {
      animating = false;
    });
    snapper.on('start', function () {
      // we need this because dragging triggers that
      animating = true;
    });
    snapper.on('end', function () {
      // we need this because dragging stop triggers that
      animating = false;
    });

    // These are necessary because calling open or close
    // on snapper during an animation makes it trigger an
    // unfinishable animation, which itself will continue
    // triggering animating events and cause high CPU load,
    //
    // Ref https://github.com/jakiestfu/Snap.js/issues/216
    var oldSnapperOpen = snapper.open;
    var oldSnapperClose = snapper.close;
    var _snapperOpen = function _snapperOpen() {
      if (animating || snapper.state().state !== 'closed') {
        return;
      }
      oldSnapperOpen('left');
    };
    var _snapperClose = function _snapperClose() {
      if (animating || snapper.state().state === 'closed') {
        return;
      }
      oldSnapperClose();
    };

    // Needs to be deferred to properly catch in-between
    // events that snap.js is triggering after dragging.
    //
    // Skipped when running unit tests as we are not testing
    // the snap.js workarounds...
    if (!window.TESTING) {
      snapper.open = function () {
        underscore__WEBPACK_IMPORTED_MODULE_0__["default"].defer(_snapperOpen);
      };
      snapper.close = function () {
        underscore__WEBPACK_IMPORTED_MODULE_0__["default"].defer(_snapperClose);
      };
    }
    jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-navigation-toggle').click(function (e) {
      // close is implicit in the button by snap.js
      if (snapper.state().state !== 'left') {
        snapper.open();
      }
    });
    jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-navigation-toggle').keypress(function (e) {
      if (snapper.state().state === 'left') {
        snapper.close();
      } else {
        snapper.open();
      }
    });

    // close sidebar when switching navigation entry
    var $appNavigation = jquery__WEBPACK_IMPORTED_MODULE_1___default()('#app-navigation');
    $appNavigation.delegate('a, :button', 'click', function (event) {
      var $target = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.target);
      // don't hide navigation when changing settings or adding things
      if ($target.is('.app-navigation-noclose') || $target.closest('.app-navigation-noclose').length) {
        return;
      }
      if ($target.is('.app-navigation-entry-utils-menu-button') || $target.closest('.app-navigation-entry-utils-menu-button').length) {
        return;
      }
      if ($target.is('.add-new') || $target.closest('.add-new').length) {
        return;
      }
      if ($target.is('#app-settings') || $target.closest('#app-settings').length) {
        return;
      }
      snapper.close();
    });
    var navigationBarSlideGestureEnabled = false;
    var navigationBarSlideGestureAllowed = true;
    var navigationBarSlideGestureEnablePending = false;
    _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].allowNavigationBarSlideGesture = function () {
      navigationBarSlideGestureAllowed = true;
      if (navigationBarSlideGestureEnablePending) {
        snapper.enable();
        navigationBarSlideGestureEnabled = true;
        navigationBarSlideGestureEnablePending = false;
      }
    };
    _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].disallowNavigationBarSlideGesture = function () {
      navigationBarSlideGestureAllowed = false;
      if (navigationBarSlideGestureEnabled) {
        var endCurrentDrag = true;
        snapper.disable(endCurrentDrag);
        navigationBarSlideGestureEnabled = false;
        navigationBarSlideGestureEnablePending = true;
      }
    };
    var toggleSnapperOnSize = function toggleSnapperOnSize() {
      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(window).width() > breakpointMobileWidth) {
        snapper.close();
        snapper.disable();
        navigationBarSlideGestureEnabled = false;
        navigationBarSlideGestureEnablePending = false;
      } else if (navigationBarSlideGestureAllowed) {
        snapper.enable();
        navigationBarSlideGestureEnabled = true;
        navigationBarSlideGestureEnablePending = false;
      } else {
        navigationBarSlideGestureEnablePending = true;
      }
    };
    jquery__WEBPACK_IMPORTED_MODULE_1___default()(window).resize(underscore__WEBPACK_IMPORTED_MODULE_0__["default"].debounce(toggleSnapperOnSize, 250));

    // initial call
    toggleSnapperOnSize();
  }
  initLiveTimestamps();
  _OC_password_confirmation_js__WEBPACK_IMPORTED_MODULE_8__["default"].init();
};

/***/ }),

/***/ "./core/src/jquery/avatar.js":
/*!***********************************!*\
  !*** ./core/src/jquery/avatar.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OC__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OC */ "./core/src/OC/index.js");
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * This plugin inserts the right avatar for the user, depending on, whether a
 * custom avatar is uploaded - which it uses then - or not, and display a
 * placeholder with the first letter of the users name instead.
 * For this it queries the core_avatar_get route, thus this plugin is fit very
 * tightly for owncloud, and it may not work anywhere else.
 *
 * You may use this on any <div></div>
 * Here I'm using <div class="avatardiv"></div> as an example.
 *
 * There are 5 ways to call this:
 *
 * 1. $('.avatardiv').avatar('jdoe', 128);
 * This will make the div to jdoe's fitting avatar, with a size of 128px.
 *
 * 2. $('.avatardiv').avatar('jdoe');
 * This will make the div to jdoe's fitting avatar. If the div already has a
 * height, it will be used for the avatars size. Otherwise this plugin will
 * search for 'size' DOM data, to use for avatar size. If neither are available
 * it will default to 64px.
 *
 * 3. $('.avatardiv').avatar();
 * This will search the DOM for 'user' data, to use as the username. If there
 * is no username available it will default to a placeholder with the value of
 * "?". The size will be determined the same way, as the second example.
 *
 * 4. $('.avatardiv').avatar('jdoe', 128, true);
 * This will behave like the first example, except it will also append random
 * hashes to the custom avatar images, to force image reloading in IE8.
 *
 * 5. $('.avatardiv').avatar('jdoe', 128, undefined, true);
 * This will behave like the first example, but it will hide the avatardiv, if
 * it will display the default placeholder. undefined is the ie8fix from
 * example 4 and can be either true, or false/undefined, to be ignored.
 *
 * 6. $('.avatardiv').avatar('jdoe', 128, undefined, true, callback);
 * This will behave like the above example, but it will call the function
 * defined in callback after the avatar is placed into the DOM.
 *
 */

(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.avatar) = function (user, size, ie8fix, hidedefault, callback, displayname) {
  var setAvatarForUnknownUser = function setAvatarForUnknownUser(target) {
    target.imageplaceholder('?');
    target.css('background-color', '#b9b9b9');
  };
  if (typeof user !== 'undefined') {
    user = String(user);
  }
  if (typeof displayname !== 'undefined') {
    displayname = String(displayname);
  }
  if (typeof size === 'undefined') {
    if (this.height() > 0) {
      size = this.height();
    } else if (this.data('size') > 0) {
      size = this.data('size');
    } else {
      size = 64;
    }
  }
  this.height(size);
  this.width(size);
  if (typeof user === 'undefined') {
    if (typeof this.data('user') !== 'undefined') {
      user = this.data('user');
    } else {
      setAvatarForUnknownUser(this);
      return;
    }
  }

  // sanitize
  user = String(user).replace(/\//g, '');
  var $div = this;
  var url;

  // If this is our own avatar we have to use the version attribute
  if (user === _OC__WEBPACK_IMPORTED_MODULE_1__["default"].getCurrentUser().uid) {
    url = _OC__WEBPACK_IMPORTED_MODULE_1__["default"].generateUrl('/avatar/{user}/{size}?v={version}', {
      user: user,
      size: Math.ceil(size * window.devicePixelRatio),
      version: oc_userconfig.avatar.version
    });
  } else {
    url = _OC__WEBPACK_IMPORTED_MODULE_1__["default"].generateUrl('/avatar/{user}/{size}', {
      user: user,
      size: Math.ceil(size * window.devicePixelRatio)
    });
  }
  var img = new Image();

  // If the new image loads successfully set it.
  img.onload = function () {
    $div.clearimageplaceholder();
    $div.append(img);
    if (typeof callback === 'function') {
      callback();
    }
  };
  // Fallback when avatar loading fails:
  // Use old placeholder when a displayname attribute is defined,
  // otherwise show the unknown user placeholder.
  img.onerror = function () {
    $div.clearimageplaceholder();
    if (typeof displayname !== 'undefined') {
      $div.imageplaceholder(user, displayname);
    } else {
      setAvatarForUnknownUser($div);
    }
    if (typeof callback === 'function') {
      callback();
    }
  };
  if (size < 32) {
    $div.addClass('icon-loading-small');
  } else {
    $div.addClass('icon-loading');
  }
  img.width = size;
  img.height = size;
  img.src = url;
  img.alt = '';
};

/***/ }),

/***/ "./core/src/jquery/contactsmenu.js":
/*!*****************************************!*\
  !*** ./core/src/jquery/contactsmenu.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OC__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OC */ "./core/src/OC/index.js");
/* harmony import */ var _Util_a11y_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../Util/a11y.js */ "./core/src/Util/a11y.js");
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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




var LIST = '' + '<div class="menu popovermenu menu-left hidden contactsmenu-popover">' + '    <ul>' + '        <li>' + '            <a>' + '                <span class="icon-loading-small"></span>' + '            </a>' + '        </li>' + '    </ul>' + '</div>';
var entryTemplate = __webpack_require__(/*! ./contactsmenu/jquery_entry.handlebars */ "./core/src/jquery/contactsmenu/jquery_entry.handlebars");
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.contactsMenu) = function (shareWith, shareType, appendTo) {
  // 0 - user, 4 - email, 6 - remote
  var allowedTypes = [0, 4, 6];
  if (allowedTypes.indexOf(shareType) === -1) {
    return;
  }
  var $div = this;
  appendTo.append(LIST);
  var $list = appendTo.find('div.contactsmenu-popover');
  $div.on('click keydown', function (event) {
    if (!(0,_Util_a11y_js__WEBPACK_IMPORTED_MODULE_2__.isA11yActivation)(event)) {
      return;
    }
    if (!$list.hasClass('hidden')) {
      $list.addClass('hidden');
      $list.hide();
      return;
    }
    $list.removeClass('hidden');
    $list.show();
    if ($list.hasClass('loaded')) {
      return;
    }
    $list.addClass('loaded');
    jquery__WEBPACK_IMPORTED_MODULE_0___default().ajax(_OC__WEBPACK_IMPORTED_MODULE_1__["default"].generateUrl('/contactsmenu/findOne'), {
      method: 'POST',
      data: {
        shareType: shareType,
        shareWith: shareWith
      }
    }).then(function (data) {
      $list.find('ul').find('li').addClass('hidden');
      var actions;
      if (!data.topAction) {
        actions = [{
          hyperlink: '#',
          title: t('core', 'No action available')
        }];
      } else {
        actions = [data.topAction].concat(data.actions);
      }
      actions.forEach(function (action) {
        $list.find('ul').append(entryTemplate(action));
      });
      $div.trigger('load');
    }, function (jqXHR) {
      $list.find('ul').find('li').addClass('hidden');
      var title;
      if (jqXHR.status === 404) {
        title = t('core', 'No action available');
      } else {
        title = t('core', 'Error fetching contact actions');
      }
      $list.find('ul').append(entryTemplate({
        hyperlink: '#',
        title: title
      }));
      $div.trigger('loaderror', jqXHR);
    });
  });
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).click(function (event) {
    var clickedList = $list.has(event.target).length > 0;
    var clickedTarget = $div.has(event.target).length > 0;
    $div.each(function () {
      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).is(event.target)) {
        clickedTarget = true;
      }
    });
    if (clickedList || clickedTarget) {
      return;
    }
    $list.addClass('hidden');
    $list.hide();
  });
};

/***/ }),

/***/ "./core/src/jquery/exists.js":
/*!***********************************!*\
  !*** ./core/src/jquery/exists.js ***!
  \***********************************/
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
 * check if an element exists.
 * allows you to write if ($('#myid').exists()) to increase readability
 *
 * @see {@link http://stackoverflow.com/questions/31044/is-there-an-exists-function-for-jquery}
 * @return {boolean}
 */
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.exists) = function () {
  return this.length > 0;
};

/***/ }),

/***/ "./core/src/jquery/filterattr.js":
/*!***************************************!*\
  !*** ./core/src/jquery/filterattr.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * Filter jQuery selector by attribute value
 *
 * @param {string} attrName attribute name
 * @param {string} attrValue attribute value
 * @return {void}
 */
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.filterAttr) = function (attrName, attrValue) {
  return this.filter(function () {
    return jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).attr(attrName) === attrValue;
  });
};

/***/ }),

/***/ "./core/src/jquery/index.js":
/*!**********************************!*\
  !*** ./core/src/jquery/index.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _avatar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./avatar */ "./core/src/jquery/avatar.js");
/* harmony import */ var _contactsmenu__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./contactsmenu */ "./core/src/jquery/contactsmenu.js");
/* harmony import */ var _exists__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./exists */ "./core/src/jquery/exists.js");
/* harmony import */ var _filterattr__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./filterattr */ "./core/src/jquery/filterattr.js");
/* harmony import */ var _ocdialog__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ocdialog */ "./core/src/jquery/ocdialog.js");
/* harmony import */ var _octemplate__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./octemplate */ "./core/src/jquery/octemplate.js");
/* harmony import */ var _placeholder__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./placeholder */ "./core/src/jquery/placeholder.js");
/* harmony import */ var _requesttoken__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./requesttoken */ "./core/src/jquery/requesttoken.js");
/* harmony import */ var _selectrange__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./selectrange */ "./core/src/jquery/selectrange.js");
/* harmony import */ var _showpassword__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./showpassword */ "./core/src/jquery/showpassword.js");
/* harmony import */ var _ui_fixes__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./ui-fixes */ "./core/src/jquery/ui-fixes.js");
/* harmony import */ var _css_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./css/jquery-ui-fixes.scss */ "./core/src/jquery/css/jquery-ui-fixes.scss");
/* harmony import */ var _css_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./css/jquery.ocdialog.scss */ "./core/src/jquery/css/jquery.ocdialog.scss");
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
 * Disable automatic evaluation of responses for $.ajax() functions (and its
 * higher-level alternatives like $.get() and $.post()).
 *
 * If a response to a $.ajax() request returns a content type of "application/javascript"
 * JQuery would previously execute the response body. This is a pretty unexpected
 * behaviour and can result in a bypass of our Content-Security-Policy as well as
 * multiple unexpected XSS vectors.
 */
jquery__WEBPACK_IMPORTED_MODULE_0___default().ajaxSetup({
  contents: {
    script: false
  }
});

/**
 * Disable execution of eval in jQuery. We do require an allowed eval CSP
 * configuration at the moment for handlebars et al. But for jQuery there is
 * not much of a reason to execute JavaScript directly via eval.
 *
 * This thus mitigates some unexpected XSS vectors.
 */
(jquery__WEBPACK_IMPORTED_MODULE_0___default().globalEval) = function () {};

/***/ }),

/***/ "./core/src/jquery/ocdialog.js":
/*!*************************************!*\
  !*** ./core/src/jquery/ocdialog.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Util_a11y__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Util/a11y */ "./core/src/Util/a11y.js");
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Gary Kim <gary@garykim.dev>
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



jquery__WEBPACK_IMPORTED_MODULE_0___default().widget('oc.ocdialog', {
  options: {
    width: 'auto',
    height: 'auto',
    closeButton: true,
    closeOnEscape: true,
    closeCallback: null,
    modal: false
  },
  _create: function _create() {
    var self = this;
    this.originalCss = {
      display: this.element[0].style.display,
      width: this.element[0].style.width,
      height: this.element[0].style.height
    };
    this.originalTitle = this.element.attr('title');
    this.options.title = this.options.title || this.originalTitle;
    this.$dialog = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<div class="oc-dialog"></div>').attr({
      // Setting tabIndex makes the div focusable
      tabIndex: -1,
      role: 'dialog'
    }).insertBefore(this.element);
    this.$dialog.append(this.element.detach());
    this.element.removeAttr('title').addClass('oc-dialog-content').appendTo(this.$dialog);

    // Activate the primary button on enter if there is a single input
    if (self.element.find('input').length === 1) {
      var $input = self.element.find('input');
      $input.on('keydown', function (event) {
        if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_1__.isA11yActivation)(event)) {
          if (self.$buttonrow) {
            var $button = self.$buttonrow.find('button.primary');
            if ($button && !$button.prop('disabled')) {
              $button.click();
            }
          }
        }
      });
    }
    this.$dialog.css({
      display: 'inline-block',
      position: 'fixed'
    });
    this.enterCallback = null;
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on('keydown keyup', function (event) {
      if (event.target !== self.$dialog.get(0) && self.$dialog.find(jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target)).length === 0) {
        return;
      }
      // Escape
      if (event.keyCode === 27 && event.type === 'keydown' && self.options.closeOnEscape) {
        event.stopImmediatePropagation();
        self.close();
        return false;
      }
      // Enter
      if (event.keyCode === 13) {
        event.stopImmediatePropagation();
        if (self.enterCallback !== null) {
          self.enterCallback();
          event.preventDefault();
          return false;
        }
        if (event.type === 'keyup') {
          event.preventDefault();
          return false;
        }
        return false;
      }
    });
    this._setOptions(this.options);
    this._createOverlay();
  },
  _init: function _init() {
    this.$dialog.focus();
    this._trigger('open');
  },
  _setOption: function _setOption(key, value) {
    var self = this;
    switch (key) {
      case 'title':
        if (this.$title) {
          this.$title.text(value);
        } else {
          var $title = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<h2 class="oc-dialog-title">' + value + '</h2>');
          this.$title = $title.prependTo(this.$dialog);
        }
        this._setSizes();
        break;
      case 'buttons':
        if (this.$buttonrow) {
          this.$buttonrow.empty();
        } else {
          var $buttonrow = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<div class="oc-dialog-buttonrow"></div>');
          this.$buttonrow = $buttonrow.appendTo(this.$dialog);
        }
        if (value.length === 1) {
          this.$buttonrow.addClass('onebutton');
        } else if (value.length === 2) {
          this.$buttonrow.addClass('twobuttons');
        } else if (value.length === 3) {
          this.$buttonrow.addClass('threebuttons');
        }
        jquery__WEBPACK_IMPORTED_MODULE_0___default().each(value, function (idx, val) {
          var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<button>').text(val.text);
          if (val.classes) {
            $button.addClass(val.classes);
          }
          if (val.defaultButton) {
            $button.addClass('primary');
            self.$defaultButton = $button;
          }
          self.$buttonrow.append($button);
          $button.on('click keydown', function (event) {
            if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_1__.isA11yActivation)(event)) {
              val.click.apply(self.element[0], arguments);
            }
          });
        });
        this.$buttonrow.find('button').on('focus', function (event) {
          self.$buttonrow.find('button').removeClass('primary');
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).addClass('primary');
        });
        this._setSizes();
        break;
      case 'style':
        if (value.buttons !== undefined) {
          this.$buttonrow.addClass(value.buttons);
        }
        break;
      case 'closeButton':
        if (value) {
          var $closeButton = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<a class="oc-dialog-close" tabindex="0"></a>');
          this.$dialog.prepend($closeButton);
          $closeButton.on('click keydown', function (event) {
            if ((0,_Util_a11y__WEBPACK_IMPORTED_MODULE_1__.isA11yActivation)(event)) {
              self.options.closeCallback && self.options.closeCallback();
              self.close();
            }
          });
        } else {
          this.$dialog.find('.oc-dialog-close').remove();
        }
        break;
      case 'width':
        this.$dialog.css('width', value);
        break;
      case 'height':
        this.$dialog.css('height', value);
        break;
      case 'close':
        this.closeCB = value;
        break;
    }
    // this._super(key, value);
    jquery__WEBPACK_IMPORTED_MODULE_0___default().Widget.prototype._setOption.apply(this, arguments);
  },
  _setOptions: function _setOptions(options) {
    // this._super(options);
    jquery__WEBPACK_IMPORTED_MODULE_0___default().Widget.prototype._setOptions.apply(this, arguments);
  },
  _setSizes: function _setSizes() {
    var lessHeight = 0;
    if (this.$title) {
      lessHeight += this.$title.outerHeight(true);
    }
    if (this.$buttonrow) {
      lessHeight += this.$buttonrow.outerHeight(true);
    }
    this.element.css({
      height: 'calc(100% - ' + lessHeight + 'px)'
    });
  },
  _createOverlay: function _createOverlay() {
    if (!this.options.modal) {
      return;
    }
    var self = this;
    var contentDiv = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#content');
    if (contentDiv.length === 0) {
      // nextcloud-vue compatibility
      contentDiv = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.content');
    }
    this.overlay = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<div>').addClass('oc-dialog-dim').appendTo(contentDiv);
    this.overlay.on('click keydown keyup', function (event) {
      if (event.target !== self.$dialog.get(0) && self.$dialog.find(jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target)).length === 0) {
        event.preventDefault();
        event.stopPropagation();
      }
    });
  },
  _destroyOverlay: function _destroyOverlay() {
    if (!this.options.modal) {
      return;
    }
    if (this.overlay) {
      this.overlay.off('click keydown keyup');
      this.overlay.remove();
      this.overlay = null;
    }
  },
  widget: function widget() {
    return this.$dialog;
  },
  setEnterCallback: function setEnterCallback(callback) {
    this.enterCallback = callback;
  },
  unsetEnterCallback: function unsetEnterCallback() {
    this.enterCallback = null;
  },
  close: function close() {
    this._destroyOverlay();
    var self = this;
    // Ugly hack to catch remaining keyup events.
    setTimeout(function () {
      self._trigger('close', self);
    }, 200);
    self.$dialog.remove();
    this.destroy();
  },
  destroy: function destroy() {
    if (this.$title) {
      this.$title.remove();
    }
    if (this.$buttonrow) {
      this.$buttonrow.remove();
    }
    if (this.originalTitle) {
      this.element.attr('title', this.originalTitle);
    }
    this.element.removeClass('oc-dialog-content').css(this.originalCss).detach().insertBefore(this.$dialog);
    this.$dialog.remove();
  }
});

/***/ }),

/***/ "./core/src/jquery/octemplate.js":
/*!***************************************!*\
  !*** ./core/src/jquery/octemplate.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_1__);
/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * jQuery plugin for micro templates
 *
 * Strings are automatically escaped, but that can be disabled by setting
 * escapeFunction to null.
 *
 * Usage examples:
 *
 *    var htmlStr = '<p>Bake, uncovered, until the {greasystuff} is melted and the {pasta} is heated through, about {min} minutes.</p>'
 *    $(htmlStr).octemplate({greasystuff: 'cheese', pasta: 'macaroni', min: 10});
 *
 *    var htmlStr = '<p>Welcome back {user}</p>';
 *    $(htmlStr).octemplate({user: 'John Q. Public'}, {escapeFunction: null});
 *
 * Be aware that the target string must be wrapped in an HTML element for the
 * plugin to work. The following won't work:
 *
 *      var textStr = 'Welcome back {user}';
 *      $(textStr).octemplate({user: 'John Q. Public'});
 *
 * For anything larger than one-liners, you can use a simple $.get() ajax
 * request to get the template, or you can embed them it the page using the
 * text/template type:
 *
 * <script id="contactListItemTemplate" type="text/template">
 *    <tr class="contact" data-id="{id}">
 *        <td class="name">
 *            <input type="checkbox" name="id" value="{id}" /><span class="nametext">{name}</span>
 *        </td>
 *        <td class="email">
 *            <a href="mailto:{email}">{email}</a>
 *        </td>
 *        <td class="phone">{phone}</td>
 *    </tr>
 * </script>
 *
 * var $tmpl = $('#contactListItemTemplate');
 * var contacts = // fetched in some ajax call
 *
 * $.each(contacts, function(idx, contact) {
 *         $contactList.append(
 *             $tmpl.octemplate({
 *                 id: contact.getId(),
 *                 name: contact.getDisplayName(),
 *                 email: contact.getPreferredEmail(),
 *                 phone: contact.getPreferredPhone(),
 *             });
 *         );
 * });
 */
/**
 * Object Template
 * Inspired by micro templating done by e.g. underscore.js
 */
var Template = {
  init: function init(vars, options, elem) {
    // Mix in the passed in options with the default options
    this.vars = vars;
    this.options = jquery__WEBPACK_IMPORTED_MODULE_0___default().extend({}, this.options, options);
    this.elem = elem;
    var self = this;
    if (typeof this.options.escapeFunction === 'function') {
      var keys = Object.keys(this.vars);
      for (var key = 0; key < keys.length; key++) {
        if (typeof this.vars[keys[key]] === 'string') {
          this.vars[keys[key]] = self.options.escapeFunction(this.vars[keys[key]]);
        }
      }
    }
    var _html = this._build(this.vars);
    return jquery__WEBPACK_IMPORTED_MODULE_0___default()(_html);
  },
  // From stackoverflow.com/questions/1408289/best-way-to-do-variable-interpolation-in-javascript
  _build: function _build(o) {
    var data = this.elem.attr('type') === 'text/template' ? this.elem.html() : this.elem.get(0).outerHTML;
    try {
      return data.replace(/{([^{}]*)}/g, function (a, b) {
        var r = o[b];
        return typeof r === 'string' || typeof r === 'number' ? r : a;
      });
    } catch (e) {
      console.error(e, 'data:', data);
    }
  },
  options: {
    escapeFunction: (escape_html__WEBPACK_IMPORTED_MODULE_1___default())
  }
};
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.octemplate) = function (vars, options) {
  vars = vars || {};
  if (this.length) {
    var _template = Object.create(Template);
    return _template.init(vars, options, this);
  }
};

/***/ }),

/***/ "./core/src/jquery/placeholder.js":
/*!****************************************!*\
  !*** ./core/src/jquery/placeholder.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var blueimp_md5__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! blueimp-md5 */ "./node_modules/blueimp-md5/js/md5.js");
/* harmony import */ var blueimp_md5__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(blueimp_md5__WEBPACK_IMPORTED_MODULE_1__);
/**
 * @copyright 2016-2018 John Molakvoæ <skjnldsv@protonmail.com>
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Sergey Shliakhov <husband.sergey@gmail.com>
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



/*
 * Adds a background color to the element called on and adds the first character
 * of the passed in string. This string is also the seed for the generation of
 * the background color.
 *
 * You have following HTML:
 *
 * <div id="albumart"></div>
 *
 * And call this from Javascript:
 *
 * $('#albumart').imageplaceholder('The Album Title');
 *
 * Which will result in:
 *
 * <div id="albumart" style="background-color: rgb(121, 90, 171); ... ">T</div>
 *
 * You may also call it like this, to have a different background, than the seed:
 *
 * $('#albumart').imageplaceholder('The Album Title', 'Album Title');
 *
 * Resulting in:
 *
 * <div id="albumart" style="background-color: rgb(121, 90, 171); ... ">A</div>
 *
 */

/*
* Alternatively, you can use the prototype function to convert your string to rgb colors:
*
* "a6741a86aded5611a8e46ce16f2ad646".toRgb()
*
* Will return the rgb parameters within the following object:
*
* Color {r: 208, g: 158, b: 109}
*
*/

var toRgb = function toRgb(s) {
  // Normalize hash
  var hash = s.toLowerCase();

  // Already a md5 hash?
  if (hash.match(/^([0-9a-f]{4}-?){8}$/) === null) {
    hash = blueimp_md5__WEBPACK_IMPORTED_MODULE_1___default()(hash);
  }
  hash = hash.replace(/[^0-9a-f]/g, '');
  function Color(r, g, b) {
    this.r = r;
    this.g = g;
    this.b = b;
  }
  function stepCalc(steps, ends) {
    var step = new Array(3);
    step[0] = (ends[1].r - ends[0].r) / steps;
    step[1] = (ends[1].g - ends[0].g) / steps;
    step[2] = (ends[1].b - ends[0].b) / steps;
    return step;
  }
  function mixPalette(steps, color1, color2) {
    var palette = [];
    palette.push(color1);
    var step = stepCalc(steps, [color1, color2]);
    for (var i = 1; i < steps; i++) {
      var r = parseInt(color1.r + step[0] * i);
      var g = parseInt(color1.g + step[1] * i);
      var b = parseInt(color1.b + step[2] * i);
      palette.push(new Color(r, g, b));
    }
    return palette;
  }
  var red = new Color(182, 70, 157);
  var yellow = new Color(221, 203, 85);
  var blue = new Color(0, 130, 201); // Nextcloud blue
  // Number of steps to go from a color to another
  // 3 colors * 6 will result in 18 generated colors
  var steps = 6;
  var palette1 = mixPalette(steps, red, yellow);
  var palette2 = mixPalette(steps, yellow, blue);
  var palette3 = mixPalette(steps, blue, red);
  var finalPalette = palette1.concat(palette2).concat(palette3);

  // Convert a string to an integer evenly
  function hashToInt(hash, maximum) {
    var finalInt = 0;
    var result = [];

    // Splitting evenly the string
    for (var i = 0; i < hash.length; i++) {
      // chars in md5 goes up to f, hex:16
      result.push(parseInt(hash.charAt(i), 16) % 16);
    }
    // Adds up all results
    for (var j in result) {
      finalInt += result[j];
    }
    // chars in md5 goes up to f, hex:16
    // make sure we're always using int in our operation
    return parseInt(parseInt(finalInt) % maximum);
  }
  return finalPalette[hashToInt(hash, steps * 3)];
};
String.prototype.toRgb = function () {
  console.warn('String.prototype.toRgb is deprecated! It will be removed in Nextcloud 22.');
  return toRgb(this);
};
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.imageplaceholder) = function (seed, text, size) {
  text = text || seed;

  // Compute the hash
  var rgb = toRgb(seed);
  this.css('background-color', 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')');

  // Placeholders are square
  var height = this.height() || size || 32;
  this.height(height);
  this.width(height);

  // CSS rules
  this.css('color', '#fff');
  this.css('font-weight', 'normal');
  this.css('text-align', 'center');

  // calculate the height
  this.css('line-height', height + 'px');
  this.css('font-size', height * 0.55 + 'px');
  if (seed !== null && seed.length) {
    var placeholderText = text.replace(/\s+/g, ' ').trim().split(' ', 2).map(function (word) {
      return word[0].toUpperCase();
    }).join('');
    this.html(placeholderText);
  }
};
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.clearimageplaceholder) = function () {
  this.css('background-color', '');
  this.css('color', '');
  this.css('font-weight', '');
  this.css('text-align', '');
  this.css('line-height', '');
  this.css('font-size', '');
  this.html('');
  this.removeClass('icon-loading');
  this.removeClass('icon-loading-small');
};

/***/ }),

/***/ "./core/src/jquery/requesttoken.js":
/*!*****************************************!*\
  !*** ./core/src/jquery/requesttoken.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OC_requesttoken__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OC/requesttoken */ "./core/src/OC/requesttoken.js");
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



jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on('ajaxSend', function (elm, xhr, settings) {
  if (settings.crossDomain === false) {
    xhr.setRequestHeader('requesttoken', (0,_OC_requesttoken__WEBPACK_IMPORTED_MODULE_1__.getToken)());
    xhr.setRequestHeader('OCS-APIREQUEST', 'true');
  }
});

/***/ }),

/***/ "./core/src/jquery/selectrange.js":
/*!****************************************!*\
  !*** ./core/src/jquery/selectrange.js ***!
  \****************************************/
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
 * select a range in an input field
 *
 * @see {@link http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area}
 * @param {number} start start selection from
 * @param {number} end number of char from start
 * @return {void}
 */
(jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.selectRange) = function (start, end) {
  return this.each(function () {
    if (this.setSelectionRange) {
      this.focus();
      this.setSelectionRange(start, end);
    } else if (this.createTextRange) {
      var range = this.createTextRange();
      range.collapse(true);
      range.moveEnd('character', end);
      range.moveStart('character', start);
      range.select();
    }
  });
};

/***/ }),

/***/ "./core/src/jquery/showpassword.js":
/*!*****************************************!*\
  !*** ./core/src/jquery/showpassword.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * @name Show Password
 * @description
 * @version 1.3.0
 * @requires Jquery 1.5
 *
 * @author Jan Jarfalk <jan.jarfalk@unwrongest.com>
 * author-website http://www.unwrongest.com
 *
 * special-thanks Michel Gratton
 *
 * @license MIT
 */
jquery__WEBPACK_IMPORTED_MODULE_0___default().fn.extend({
  showPassword: function showPassword(c) {
    // Setup callback object
    var callback = {
      fn: null,
      args: {}
    };
    callback.fn = c;

    // Clones passwords and turn the clones into text inputs
    var cloneElement = function cloneElement(element) {
      var $element = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element);
      var $clone = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<input />');

      // Name added for JQuery Validation compatibility
      // Element name is required to avoid script warning.
      $clone.attr({
        type: 'text',
        class: $element.attr('class'),
        style: $element.attr('style'),
        size: $element.attr('size'),
        name: $element.attr('name') + '-clone',
        tabindex: $element.attr('tabindex'),
        autocomplete: 'off'
      });
      if ($element.attr('placeholder') !== undefined) {
        $clone.attr('placeholder', $element.attr('placeholder'));
      }
      return $clone;
    };

    // Transfers values between two elements
    var update = function update(a, b) {
      b.val(a.val());
    };

    // Shows a or b depending on checkbox
    var setState = function setState(checkbox, a, b) {
      if (checkbox.is(':checked')) {
        update(a, b);
        b.show();
        a.hide();
      } else {
        update(b, a);
        b.hide();
        a.show();
      }
    };
    return this.each(function () {
      var $input = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
      var $checkbox = jquery__WEBPACK_IMPORTED_MODULE_0___default()($input.data('typetoggle'));

      // Create clone
      var $clone = cloneElement($input);
      $clone.insertAfter($input);

      // Set callback arguments
      if (callback.fn) {
        callback.args.input = $input;
        callback.args.checkbox = $checkbox;
        callback.args.clone = $clone;
      }
      $checkbox.bind('click', function () {
        setState($checkbox, $input, $clone);
      });
      $input.bind('keyup', function () {
        update($input, $clone);
      });
      $clone.bind('keyup', function () {
        update($clone, $input);

        // Added for JQuery Validation compatibility
        // This will trigger validation if it's ON for keyup event
        $input.trigger('keyup');
      });

      // Added for JQuery Validation compatibility
      // This will trigger validation if it's ON for blur event
      $clone.bind('blur', function () {
        $input.trigger('focusout');
      });
      setState($checkbox, $input, $clone);

      // set type of password field clone (type=text) to password right on submit
      // to prevent browser save the value of this field
      $clone.closest('form').submit(function (e) {
        // .prop has to be used, because .attr throws
        // an error while changing a type of an input
        // element
        $clone.prop('type', 'password');
      });
      if (callback.fn) {
        callback.fn(callback.args);
      }
    });
  }
});

/***/ }),

/***/ "./core/src/jquery/ui-fixes.js":
/*!*************************************!*\
  !*** ./core/src/jquery/ui-fixes.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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



// Set autocomplete width the same as the related input
// See http://stackoverflow.com/a/11845718
(jquery__WEBPACK_IMPORTED_MODULE_0___default().ui.autocomplete.prototype._resizeMenu) = function () {
  var ul = this.menu.element;
  ul.outerWidth(this.element.outerWidth());
};

/***/ }),

/***/ "./core/src/main.js":
/*!**************************!*\
  !*** ./core/src/main.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_stable__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/stable */ "./node_modules/core-js/stable/index.js");
/* harmony import */ var core_js_stable__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_stable__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var regenerator_runtime_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! regenerator-runtime/runtime */ "./node_modules/regenerator-runtime/runtime.js");
/* harmony import */ var regenerator_runtime_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(regenerator_runtime_runtime__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Polyfill_index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Polyfill/index.js */ "./core/src/Polyfill/index.js");
/* harmony import */ var _OC_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./OC/index.js */ "./core/src/OC/index.js");
/* harmony import */ var _globals_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./globals.js */ "./core/src/globals.js");
/* harmony import */ var _jquery_index_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./jquery/index.js */ "./core/src/jquery/index.js");
/* harmony import */ var _init_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./init.js */ "./core/src/init.js");
/* harmony import */ var _OC_apps_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./OC/apps.js */ "./core/src/OC/apps.js");
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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






// If you remove the line below, tests won't pass
// eslint-disable-next-line no-unused-vars





window.addEventListener('DOMContentLoaded', function () {
  (0,_init_js__WEBPACK_IMPORTED_MODULE_7__.initCore)();
  (0,_OC_apps_js__WEBPACK_IMPORTED_MODULE_8__.registerAppsSlideToggle)();

  // fallback to hashchange when no history support
  if (window.history.pushState) {
    window.onpopstate = _.bind(_OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].Util.History._onPopState, _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].Util.History);
  } else {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on('hashchange', _.bind(_OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].Util.History._onPopState, _OC_index_js__WEBPACK_IMPORTED_MODULE_4__["default"].Util.History));
  }
});

/***/ }),

/***/ "./core/src/session-heartbeat.js":
/*!***************************************!*\
  !*** ./core/src/session-heartbeat.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "initSessionHeartBeat": function() { return /* binding */ initSessionHeartBeat; }
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _OC__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./OC */ "./core/src/OC/index.js");
/* harmony import */ var _OC_requesttoken__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./OC/requesttoken */ "./core/src/OC/requesttoken.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
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








var config = null;
/**
 * The legacy jsunit tests overwrite OC.config before calling initCore
 * therefore we need to wait with assigning the config fallback until initCore calls initSessionHeartBeat
 */
var loadConfig = function loadConfig() {
  try {
    config = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('core', 'config');
  } catch (e) {
    // This fallback is just for our legacy jsunit tests since we have no way to mock loadState calls
    config = _OC__WEBPACK_IMPORTED_MODULE_5__["default"].config;
  }
};

/**
 * session heartbeat (defaults to enabled)
 *
 * @return {boolean}
 */
var keepSessionAlive = function keepSessionAlive() {
  return config.session_keepalive === undefined || !!config.session_keepalive;
};

/**
 * get interval in seconds
 *
 * @return {number}
 */
var getInterval = function getInterval() {
  var interval = NaN;
  if (config.session_lifetime) {
    interval = Math.floor(config.session_lifetime / 2);
  }

  // minimum one minute, max 24 hours, default 15 minutes
  return Math.min(24 * 3600, Math.max(60, isNaN(interval) ? 900 : interval));
};
var getToken = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
    var url, resp;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/csrftoken'); // Not using Axios here as Axios is not stubbable with the sinon fake server
            // see https://stackoverflow.com/questions/41516044/sinon-mocha-test-with-async-ajax-calls-didnt-return-promises
            // see js/tests/specs/coreSpec.js for the tests
            _context.next = 3;
            return jquery__WEBPACK_IMPORTED_MODULE_0___default().get(url);
          case 3:
            resp = _context.sent;
            return _context.abrupt("return", resp.token);
          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function getToken() {
    return _ref.apply(this, arguments);
  };
}();
var poll = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
    var token;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            _context2.prev = 0;
            _context2.next = 3;
            return getToken();
          case 3:
            token = _context2.sent;
            (0,_OC_requesttoken__WEBPACK_IMPORTED_MODULE_6__.setToken)(token);
            _context2.next = 10;
            break;
          case 7:
            _context2.prev = 7;
            _context2.t0 = _context2["catch"](0);
            console.error('session heartbeat failed', _context2.t0);
          case 10:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2, null, [[0, 7]]);
  }));
  return function poll() {
    return _ref2.apply(this, arguments);
  };
}();
var startPolling = function startPolling() {
  var interval = setInterval(poll, getInterval() * 1000);
  console.info('session heartbeat polling started');
  return interval;
};
var registerAutoLogout = function registerAutoLogout() {
  if (!config.auto_logout || !(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__.getCurrentUser)()) {
    return;
  }
  var lastActive = Date.now();
  window.addEventListener('mousemove', function (e) {
    lastActive = Date.now();
    localStorage.setItem('lastActive', lastActive);
  });
  window.addEventListener('touchstart', function (e) {
    lastActive = Date.now();
    localStorage.setItem('lastActive', lastActive);
  });
  window.addEventListener('storage', function (e) {
    if (e.key !== 'lastActive') {
      return;
    }
    lastActive = e.newValue;
  });
  setInterval(function () {
    var timeout = Date.now() - config.session_lifetime * 1000;
    if (lastActive < timeout) {
      console.info('Inactivity timout reached, logging out');
      var logoutUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/logout') + '?requesttoken=' + encodeURIComponent((0,_OC_requesttoken__WEBPACK_IMPORTED_MODULE_6__.getToken)());
      window.location = logoutUrl;
    }
  }, 1000);
};

/**
 * Calls the server periodically to ensure that session and CSRF
 * token doesn't expire
 */
var initSessionHeartBeat = function initSessionHeartBeat() {
  loadConfig();
  registerAutoLogout();
  if (!keepSessionAlive()) {
    console.info('session heartbeat disabled');
    return;
  }
  var interval = startPolling();
  window.addEventListener('online', /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
    return regeneratorRuntime.wrap(function _callee3$(_context3) {
      while (1) {
        switch (_context3.prev = _context3.next) {
          case 0:
            console.info('browser is online again, resuming heartbeat');
            interval = startPolling();
            _context3.prev = 2;
            _context3.next = 5;
            return poll();
          case 5:
            console.info('session token successfully updated after resuming network');

            // Let apps know we're online and requests will have the new token
            (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('networkOnline', {
              success: true
            });
            _context3.next = 13;
            break;
          case 9:
            _context3.prev = 9;
            _context3.t0 = _context3["catch"](2);
            console.error('could not update session token after resuming network', _context3.t0);

            // Let apps know we're online but requests might have an outdated token
            (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('networkOnline', {
              success: false
            });
          case 13:
          case "end":
            return _context3.stop();
        }
      }
    }, _callee3, null, [[2, 9]]);
  })));
  window.addEventListener('offline', function () {
    console.info('browser is offline, stopping heartbeat');

    // Let apps know we're offline
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('networkOffline', {});
    clearInterval(interval);
    console.info('session heartbeat polling stopped');
  });
};

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_3__);




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AppMenu',
  components: {
    NcActions: (_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_2___default()),
    NcActionLink: (_nextcloud_vue_dist_Components_NcActionLink__WEBPACK_IMPORTED_MODULE_3___default())
  },
  data: function data() {
    return {
      apps: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'apps', {}),
      appLimit: 0,
      observer: null
    };
  },
  computed: {
    appList: function appList() {
      return Object.values(this.apps);
    },
    mainAppList: function mainAppList() {
      return this.appList.slice(0, this.appLimit);
    },
    popoverAppList: function popoverAppList() {
      return this.appList.slice(this.appLimit);
    },
    appLabel: function appLabel() {
      return function (app) {
        return app.name + (app.active ? ' (' + t('core', 'Currently open') + ')' : '') + (app.unread > 0 ? ' (' + n('core', '{count} notification', '{count} notifications', app.unread, {
          count: app.unread
        }) + ')' : '');
      };
    }
  },
  mounted: function mounted() {
    this.observer = new ResizeObserver(this.resize);
    this.observer.observe(this.$el);
    this.resize();
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('nextcloud:app-menu.refresh', this.setApps);
  },
  beforeDestroy: function beforeDestroy() {
    this.observer.disconnect();
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('nextcloud:app-menu.refresh', this.setApps);
  },
  methods: {
    setNavigationCounter: function setNavigationCounter(id, counter) {
      this.$set(this.apps[id], 'unread', counter);
    },
    setApps: function setApps(_ref) {
      var apps = _ref.apps;
      this.apps = apps;
    },
    resize: function resize() {
      var availableWidth = this.$el.offsetWidth;
      var appCount = Math.floor(availableWidth / 50) - 1;
      var popoverAppCount = this.appList.length - appCount;
      if (popoverAppCount === 1) {
        appCount--;
      }
      if (appCount < 1) {
        appCount = 0;
      }
      this.appLimit = appCount;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcHeaderMenu.js */ "./node_modules/@nextcloud/vue/dist/Components/NcHeaderMenu.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue_material_design_icons_Contacts_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/Contacts.vue */ "./node_modules/vue-material-design-icons/Contacts.vue");
/* harmony import */ var _OC_index_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../OC/index.js */ "./core/src/OC/index.js");



/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ContactsMenu',
  components: {
    Contacts: vue_material_design_icons_Contacts_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcHeaderMenu: (_nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_0___default())
  },
  data: function data() {
    return {
      contactsMenu: null
    };
  },
  mounted: function mounted() {
    // eslint-disable-next-line no-new
    this.contactsMenu = new _OC_index_js__WEBPACK_IMPORTED_MODULE_2__["default"].ContactsMenu({
      el: '#contactsmenu-menu'
    });
  },
  methods: {
    handleOpen: function handleOpen() {
      var _this$contactsMenu;
      (_this$contactsMenu = this.contactsMenu) === null || _this$contactsMenu === void 0 ? void 0 : _this$contactsMenu.loadContacts();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("nav", {
    staticClass: "app-menu"
  }, [_c("ul", {
    staticClass: "app-menu-main"
  }, _vm._l(_vm.mainAppList, function (app) {
    return _c("li", {
      key: app.id,
      staticClass: "app-menu-entry",
      class: {
        "app-menu-entry__active": app.active
      },
      attrs: {
        "data-app-id": app.id
      }
    }, [_c("a", {
      class: {
        "has-unread": app.unread > 0
      },
      attrs: {
        href: app.href,
        "aria-label": _vm.appLabel(app),
        title: app.name,
        "aria-current": app.active ? "page" : false,
        target: app.target ? "_blank" : undefined,
        rel: app.target ? "noopener noreferrer" : undefined
      }
    }, [_c("img", {
      attrs: {
        src: app.icon,
        alt: ""
      }
    }), _vm._v(" "), _c("div", {
      staticClass: "app-menu-entry--label"
    }, [_vm._v("\n\t\t\t\t\t" + _vm._s(app.name) + "\n\t\t\t\t\t"), app.unread > 0 ? _c("span", {
      staticClass: "hidden-visually unread-counter"
    }, [_vm._v(_vm._s(app.unread))]) : _vm._e()])])]);
  }), 0), _vm._v(" "), _c("NcActions", {
    staticClass: "app-menu-more",
    attrs: {
      "aria-label": _vm.t("core", "More apps")
    }
  }, _vm._l(_vm.popoverAppList, function (app) {
    return _c("NcActionLink", {
      key: app.id,
      staticClass: "app-menu-popover-entry",
      attrs: {
        "aria-label": _vm.appLabel(app),
        "aria-current": app.active ? "page" : false,
        href: app.href
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function fn() {
          return [_c("div", {
            staticClass: "app-icon",
            class: {
              "has-unread": app.unread > 0
            }
          }, [_c("img", {
            attrs: {
              src: app.icon,
              alt: ""
            }
          })])];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t\t" + _vm._s(app.name) + "\n\t\t\t"), app.unread > 0 ? _c("span", {
      staticClass: "hidden-visually unread-counter"
    }, [_vm._v(_vm._s(app.unread))]) : _vm._e()]);
  }), 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcHeaderMenu", {
    attrs: {
      id: "contactsmenu",
      "aria-label": _vm.t("core", "Search contacts")
    },
    on: {
      open: _vm.handleOpen
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function fn() {
        return [_c("Contacts", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("div", {
    attrs: {
      id: "contactsmenu-menu"
    }
  })]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery-ui-fixes.scss":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery-ui-fixes.scss ***!
  \*******************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/getUrl.js */ "./node_modules/css-loader/dist/runtime/getUrl.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__);
// Imports



var ___CSS_LOADER_URL_IMPORT_0___ = new URL(/* asset import */ __webpack_require__(/*! images/ui-icons_1d2d44_256x240.png */ "./core/src/jquery/css/images/ui-icons_1d2d44_256x240.png"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_1___ = new URL(/* asset import */ __webpack_require__(/*! images/ui-icons_ffffff_256x240.png */ "./core/src/jquery/css/images/ui-icons_ffffff_256x240.png"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_2___ = new URL(/* asset import */ __webpack_require__(/*! images/ui-icons_ffd27a_256x240.png */ "./core/src/jquery/css/images/ui-icons_ffd27a_256x240.png"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_3___ = new URL(/* asset import */ __webpack_require__(/*! images/ui-bg_diagonals-thick_20_666666_40x40.png */ "./core/src/jquery/css/images/ui-bg_diagonals-thick_20_666666_40x40.png"), __webpack_require__.b);
var ___CSS_LOADER_URL_IMPORT_4___ = new URL(/* asset import */ __webpack_require__(/*! images/ui-bg_flat_10_000000_40x100.png */ "./core/src/jquery/css/images/ui-bg_flat_10_000000_40x100.png"), __webpack_require__.b);
var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
var ___CSS_LOADER_URL_REPLACEMENT_0___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_0___);
var ___CSS_LOADER_URL_REPLACEMENT_1___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_1___);
var ___CSS_LOADER_URL_REPLACEMENT_2___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_2___);
var ___CSS_LOADER_URL_REPLACEMENT_3___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_3___);
var ___CSS_LOADER_URL_REPLACEMENT_4___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_4___);
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/* Component containers\n----------------------------------*/\n.ui-widget-content {\n  border: 1px solid var(--color-border);\n  background: var(--color-main-background) none;\n  color: var(--color-main-text);\n}\n\n.ui-widget-content a {\n  color: var(--color-main-text);\n}\n\n.ui-widget-header {\n  border: none;\n  color: var(--color-main-text);\n  background-image: none;\n}\n\n.ui-widget-header a {\n  color: var(--color-main-text);\n}\n\n/* Interaction states\n----------------------------------*/\n.ui-state-default,\n.ui-widget-content .ui-state-default,\n.ui-widget-header .ui-state-default {\n  border: 1px solid var(--color-border);\n  background: var(--color-main-background) none;\n  font-weight: bold;\n  color: #555;\n}\n\n.ui-state-default a,\n.ui-state-default a:link,\n.ui-state-default a:visited {\n  color: #555;\n}\n\n.ui-state-hover,\n.ui-widget-content .ui-state-hover,\n.ui-widget-header .ui-state-hover,\n.ui-state-focus,\n.ui-widget-content .ui-state-focus,\n.ui-widget-header .ui-state-focus {\n  border: 1px solid #ddd;\n  background: var(--color-main-background) none;\n  font-weight: bold;\n  color: var(--color-main-text);\n}\n\n.ui-state-hover a,\n.ui-state-hover a:hover,\n.ui-state-hover a:link,\n.ui-state-hover a:visited {\n  color: var(--color-main-text);\n}\n\n.ui-state-active,\n.ui-widget-content .ui-state-active,\n.ui-widget-header .ui-state-active {\n  border: 1px solid var(--color-primary);\n  background: var(--color-main-background) none;\n  font-weight: bold;\n  color: var(--color-main-text);\n}\n\n.ui-state-active a,\n.ui-state-active a:link,\n.ui-state-active a:visited {\n  color: var(--color-main-text);\n}\n\n/* Interaction Cues\n----------------------------------*/\n.ui-state-highlight,\n.ui-widget-content .ui-state-highlight,\n.ui-widget-header .ui-state-highlight {\n  border: 1px solid var(--color-main-background);\n  background: var(--color-main-background) none;\n  color: var(--color-text-light);\n  font-weight: 600;\n}\n\n.ui-state-highlight a,\n.ui-widget-content .ui-state-highlight a,\n.ui-widget-header .ui-state-highlight a {\n  color: var(--color-text-lighter);\n}\n\n.ui-state-error,\n.ui-widget-content .ui-state-error,\n.ui-widget-header .ui-state-error {\n  border: var(--color-error);\n  background: var(--color-error) none;\n  color: #ffffff;\n}\n\n.ui-state-error a,\n.ui-widget-content .ui-state-error a,\n.ui-widget-header .ui-state-error a {\n  color: #ffffff;\n}\n\n.ui-state-error-text,\n.ui-widget-content .ui-state-error-text,\n.ui-widget-header .ui-state-error-text {\n  color: #ffffff;\n}\n\n/* Icons\n----------------------------------*/\n.ui-state-default .ui-icon {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_0___ + ");\n}\n\n.ui-state-hover .ui-icon,\n.ui-state-focus .ui-icon {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_0___ + ");\n}\n\n.ui-state-active .ui-icon {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_0___ + ");\n}\n\n.ui-state-highlight .ui-icon {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_1___ + ");\n}\n\n.ui-state-error .ui-icon,\n.ui-state-error-text .ui-icon {\n  background-image: url(" + ___CSS_LOADER_URL_REPLACEMENT_2___ + ");\n}\n\n.ui-icon.ui-icon-none {\n  display: none;\n}\n\n/* Misc visuals\n----------------------------------*/\n/* Overlays */\n.ui-widget-overlay {\n  background: #666666 url(" + ___CSS_LOADER_URL_REPLACEMENT_3___ + ") 50% 50% repeat;\n  opacity: 0.5;\n}\n\n.ui-widget-shadow {\n  margin: -5px 0 0 -5px;\n  padding: 5px;\n  background: #000000 url(" + ___CSS_LOADER_URL_REPLACEMENT_4___ + ") 50% 50% repeat-x;\n  opacity: 0.2;\n  border-radius: 5px;\n}\n\n/* Tabs customizations */\n.ui-tabs {\n  border: none;\n}\n.ui-tabs .ui-tabs-nav.ui-corner-all {\n  border-bottom-left-radius: 0;\n  border-bottom-right-radius: 0;\n}\n.ui-tabs .ui-tabs-nav {\n  background: none;\n  margin-bottom: 15px;\n}\n.ui-tabs .ui-tabs-nav .ui-state-default {\n  border: none;\n  border-bottom: 1px solid transparent;\n  font-weight: normal;\n  margin: 0 !important;\n  padding: 0 !important;\n}\n.ui-tabs .ui-tabs-nav .ui-state-hover,\n.ui-tabs .ui-tabs-nav .ui-state-active {\n  border: none;\n  border-bottom: 1px solid var(--color-main-text);\n  color: var(--color-main-text);\n}\n.ui-tabs .ui-tabs-nav .ui-state-hover a, .ui-tabs .ui-tabs-nav .ui-state-hover a:link, .ui-tabs .ui-tabs-nav .ui-state-hover a:hover, .ui-tabs .ui-tabs-nav .ui-state-hover a:visited,\n.ui-tabs .ui-tabs-nav .ui-state-active a,\n.ui-tabs .ui-tabs-nav .ui-state-active a:link,\n.ui-tabs .ui-tabs-nav .ui-state-active a:hover,\n.ui-tabs .ui-tabs-nav .ui-state-active a:visited {\n  color: var(--color-main-text);\n}\n.ui-tabs .ui-tabs-nav .ui-state-active {\n  font-weight: bold;\n}\n\n/* Select menus */\n.ui-autocomplete.ui-menu {\n  padding: 0;\n  /* scrolling starts from three items,\n   * so hide overflow and scrollbars for a clean layout */\n}\n.ui-autocomplete.ui-menu.item-count-1, .ui-autocomplete.ui-menu.item-count-2 {\n  overflow-y: hidden;\n}\n.ui-autocomplete.ui-menu .ui-menu-item a {\n  color: var(--color-text-lighter);\n  display: block;\n  padding: 4px 4px 4px 14px;\n}\n.ui-autocomplete.ui-menu .ui-menu-item a.ui-state-focus, .ui-autocomplete.ui-menu .ui-menu-item a.ui-state-active {\n  box-shadow: inset 4px 0 var(--color-primary-element);\n  color: var(--color-main-text);\n}\n.ui-autocomplete.ui-widget-content {\n  background: var(--color-main-background);\n  border-top: none;\n}\n.ui-autocomplete.ui-corner-all {\n  border-radius: 0;\n  border-bottom-left-radius: var(--border-radius);\n  border-bottom-right-radius: var(--border-radius);\n}\n.ui-autocomplete .ui-state-hover, .ui-autocomplete .ui-widget-content .ui-state-hover,\n.ui-autocomplete .ui-widget-header .ui-state-hover,\n.ui-autocomplete .ui-state-focus,\n.ui-autocomplete .ui-widget-content .ui-state-focus,\n.ui-autocomplete .ui-widget-header .ui-state-focus {\n  border: 1px solid transparent;\n  background: inherit;\n  color: var(--color-primary-element);\n}\n.ui-autocomplete .ui-menu-item a {\n  border-radius: 0 !important;\n}\n\n.ui-button.primary {\n  background-color: var(--color-primary);\n  color: var(--color-primary-text);\n  border: 1px solid var(--color-primary-text);\n}\n\n.ui-button:hover {\n  font-weight: bold !important;\n}\n\n/* DRAGGABLE */\n.ui-draggable-handle,\n.ui-selectable {\n  touch-action: pan-y;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery.ocdialog.scss":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery.ocdialog.scss ***!
  \*******************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".oc-dialog {\n  background: var(--color-main-background);\n  color: var(--color-text-light);\n  border-radius: var(--border-radius-large);\n  box-shadow: 0 0 30px var(--color-box-shadow);\n  padding: 24px;\n  z-index: 10000;\n  font-size: 100%;\n  box-sizing: border-box;\n  min-width: 200px;\n  top: 50%;\n  left: 50%;\n  transform: translate(-50%, -50%);\n  max-height: calc(100% - 20px);\n  max-width: calc(100% - 20px);\n  overflow: auto;\n}\n\n.oc-dialog-title {\n  background: var(--color-main-background);\n}\n\n.oc-dialog-buttonrow {\n  position: relative;\n  display: flex;\n  background: transparent;\n  right: 0;\n  bottom: 0;\n  padding: 0;\n  padding-top: 10px;\n  box-sizing: border-box;\n  width: 100%;\n  background-image: linear-gradient(rgba(255, 255, 255, 0), var(--color-main-background));\n}\n.oc-dialog-buttonrow.twobuttons {\n  justify-content: space-between;\n}\n.oc-dialog-buttonrow.onebutton, .oc-dialog-buttonrow.twobuttons.aside {\n  justify-content: flex-end;\n}\n.oc-dialog-buttonrow button {\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  height: 44px;\n  min-width: 44px;\n}\n\n.oc-dialog-close {\n  position: absolute;\n  top: 0;\n  right: 0;\n  padding: 25px;\n  background: var(--icon-close-dark) no-repeat center;\n  opacity: 0.5;\n  border-radius: var(--border-radius-pill);\n}\n.oc-dialog-close:hover, .oc-dialog-close:focus, .oc-dialog-close:active {\n  opacity: 1;\n}\n\n.oc-dialog-dim {\n  background-color: #000;\n  opacity: 0.2;\n  z-index: 9999;\n  position: fixed;\n  top: 0;\n  left: 0;\n  width: 100%;\n  height: 100%;\n}\n\nbody.theme--dark .oc-dialog-dim {\n  opacity: 0.8;\n}\n\n.oc-dialog-content {\n  width: 100%;\n  max-width: 550px;\n}\n\n.oc-dialog.password-confirmation .oc-dialog-content {\n  width: auto;\n}\n.oc-dialog.password-confirmation .oc-dialog-content input[type=password] {\n  width: 100%;\n}\n.oc-dialog.password-confirmation .oc-dialog-content label {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".app-menu[data-v-5dc6d664] {\n  width: 100%;\n  display: flex;\n  flex-shrink: 1;\n  flex-wrap: wrap;\n}\n.app-menu-main[data-v-5dc6d664] {\n  display: flex;\n  flex-wrap: nowrap;\n}\n.app-menu-main .app-menu-entry[data-v-5dc6d664] {\n  width: 50px;\n  height: 50px;\n  position: relative;\n  display: flex;\n  opacity: 0.7;\n  filter: var(--background-image-invert-if-bright);\n}\n.app-menu-main .app-menu-entry.app-menu-entry__active[data-v-5dc6d664] {\n  opacity: 1;\n}\n.app-menu-main .app-menu-entry.app-menu-entry__active[data-v-5dc6d664]::before {\n  content: \" \";\n  position: absolute;\n  pointer-events: none;\n  border-bottom-color: var(--color-main-background);\n  transform: translateX(-50%);\n  width: 12px;\n  height: 5px;\n  border-radius: 3px;\n  background-color: var(--color-primary-text);\n  left: 50%;\n  bottom: 6px;\n  display: block;\n  transition: all 0.1s ease-in-out;\n  opacity: 1;\n}\n.app-menu-main .app-menu-entry.app-menu-entry__active .app-menu-entry--label[data-v-5dc6d664] {\n  font-weight: bold;\n}\n.app-menu-main .app-menu-entry a[data-v-5dc6d664] {\n  width: calc(100% - 4px);\n  height: calc(100% - 4px);\n  margin: 2px;\n  color: var(--color-primary-text);\n  position: relative;\n}\n.app-menu-main .app-menu-entry img[data-v-5dc6d664] {\n  transition: margin 0.1s ease-in-out;\n  width: 20px;\n  height: 20px;\n  padding: calc((100% - 20px) / 2);\n  box-sizing: content-box;\n}\n.app-menu-main .app-menu-entry .app-menu-entry--label[data-v-5dc6d664] {\n  opacity: 0;\n  position: absolute;\n  font-size: 12px;\n  color: var(--color-primary-text);\n  text-align: center;\n  bottom: -5px;\n  left: 50%;\n  top: 45%;\n  display: block;\n  min-width: 100%;\n  transform: translateX(-50%);\n  transition: all 0.1s ease-in-out;\n  width: 100%;\n  text-overflow: ellipsis;\n  overflow: hidden;\n  letter-spacing: -0.5px;\n}\n.app-menu-main .app-menu-entry[data-v-5dc6d664]:hover, .app-menu-main .app-menu-entry[data-v-5dc6d664]:focus-within {\n  opacity: 1;\n}\n.app-menu-main .app-menu-entry:hover .app-menu-entry--label[data-v-5dc6d664], .app-menu-main .app-menu-entry:focus-within .app-menu-entry--label[data-v-5dc6d664] {\n  opacity: 1;\n  font-weight: bolder;\n  bottom: 0;\n  width: 100%;\n  text-overflow: ellipsis;\n  overflow: hidden;\n}\n.app-menu-main[data-v-5dc6d664]:hover, .app-menu-main[data-v-5dc6d664]:focus-within,\n.app-menu-main .app-menu-entry[data-v-5dc6d664]:hover,\n.app-menu-main .app-menu-entry[data-v-5dc6d664]:focus {\n  opacity: 1;\n}\n.app-menu-main:hover img[data-v-5dc6d664], .app-menu-main:focus-within img[data-v-5dc6d664],\n.app-menu-main .app-menu-entry:hover img[data-v-5dc6d664],\n.app-menu-main .app-menu-entry:focus img[data-v-5dc6d664] {\n  margin-top: -8px;\n}\n.app-menu-main:hover .app-menu-entry--label[data-v-5dc6d664], .app-menu-main:focus-within .app-menu-entry--label[data-v-5dc6d664],\n.app-menu-main .app-menu-entry:hover .app-menu-entry--label[data-v-5dc6d664],\n.app-menu-main .app-menu-entry:focus .app-menu-entry--label[data-v-5dc6d664] {\n  opacity: 1;\n  bottom: 0;\n}\n.app-menu-main[data-v-5dc6d664]:hover::before, .app-menu-main:hover .app-menu-entry[data-v-5dc6d664]::before, .app-menu-main[data-v-5dc6d664]:focus-within::before, .app-menu-main:focus-within .app-menu-entry[data-v-5dc6d664]::before,\n.app-menu-main .app-menu-entry[data-v-5dc6d664]:hover::before,\n.app-menu-main .app-menu-entry:hover .app-menu-entry[data-v-5dc6d664]::before,\n.app-menu-main .app-menu-entry[data-v-5dc6d664]:focus::before,\n.app-menu-main .app-menu-entry:focus .app-menu-entry[data-v-5dc6d664]::before {\n  opacity: 0;\n}\n[data-v-5dc6d664] .app-menu-more .button-vue--vue-tertiary {\n  color: var(--color-primary-text);\n  opacity: 0.7;\n  margin: 3px;\n  filter: var(--background-image-invert-if-bright);\n}\n[data-v-5dc6d664] .app-menu-more .button-vue--vue-tertiary:hover {\n  opacity: 1;\n  background-color: transparent !important;\n}\n[data-v-5dc6d664] .app-menu-more .button-vue--vue-tertiary:focus-visible {\n  opacity: 1;\n  outline: none !important;\n}\n.app-menu-popover-entry .app-icon[data-v-5dc6d664] {\n  position: relative;\n  height: 44px;\n}\n.app-menu-popover-entry .app-icon.has-unread[data-v-5dc6d664]::after {\n  background-color: var(--color-main-text);\n}\n.app-menu-popover-entry .app-icon img[data-v-5dc6d664] {\n  width: 20px;\n  height: 20px;\n  padding: 15px;\n}\n.has-unread[data-v-5dc6d664]::after {\n  content: \"\";\n  width: 8px;\n  height: 8px;\n  background-color: var(--color-primary-text);\n  border-radius: 50%;\n  position: absolute;\n  display: block;\n  top: 10px;\n  right: 10px;\n}\n.unread-counter[data-v-5dc6d664] {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, "#contactsmenu-menu[data-v-f3f9ac60] {\n  /* show 2.5 to 4.5 entries depending on the screen height */\n  height: calc(100vh - 150px);\n  max-height: 302px;\n  min-height: 175px;\n  width: 350px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .emptycontent {\n  margin-top: 5vh !important;\n  margin-bottom: 1.5vh;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .emptycontent .icon-loading,\n#contactsmenu-menu[data-v-f3f9ac60] .emptycontent .icon-search {\n  display: inline-block;\n}\n#contactsmenu-menu[data-v-f3f9ac60] #contactsmenu-search {\n  width: calc(100% - 16px);\n  margin: 8px;\n  height: 34px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .content {\n  /* fixed max height of the parent container without the search input */\n  height: calc(100vh - 150px - 50px);\n  max-height: 250px;\n  min-height: 125px;\n  overflow-y: auto;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .content .footer {\n  text-align: center;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .content .footer a {\n  display: block;\n  width: 100%;\n  padding: 12px 0;\n  opacity: 0.5;\n}\n#contactsmenu-menu[data-v-f3f9ac60] a {\n  padding: 2px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] a:focus-visible {\n  box-shadow: inset 0 0 0 2px var(--color-main-text) !important;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact {\n  display: flex;\n  position: relative;\n  align-items: center;\n  padding: 3px 3px 3px 10px;\n  /* actions menu */\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .avatar {\n  height: 32px;\n  width: 32px;\n  display: inline-block;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .body {\n  flex-grow: 1;\n  padding-left: 8px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .body div {\n  position: relative;\n  width: 100%;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .body .full-name, #contactsmenu-menu[data-v-f3f9ac60] .contact .body .last-message {\n  /* TODO: don't use fixed width */\n  max-width: 204px;\n  overflow: hidden;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .body .last-message, #contactsmenu-menu[data-v-f3f9ac60] .contact .body .email-address {\n  color: var(--color-text-maxcontrast);\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .top-action, #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action, #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions {\n  width: 16px;\n  height: 16px;\n  opacity: 0.5;\n  cursor: pointer;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .top-action:not(button), #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action:not(button), #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions:not(button) {\n  padding: 14px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .top-action img, #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action img, #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions img {\n  filter: var(--background-invert-if-dark);\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .top-action:hover, #contactsmenu-menu[data-v-f3f9ac60] .contact .top-action:active, #contactsmenu-menu[data-v-f3f9ac60] .contact .top-action:focus, #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action:hover, #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action:active, #contactsmenu-menu[data-v-f3f9ac60] .contact .second-action:focus, #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions:hover, #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions:active, #contactsmenu-menu[data-v-f3f9ac60] .contact .other-actions:focus {\n  opacity: 1;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact button.other-actions {\n  width: 44px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact button.other-actions:focus {\n  border-color: transparent;\n  box-shadow: 0 0 0 2px var(--color-main-text);\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact button.other-actions:focus-visible {\n  border-radius: var(--border-radius-pill);\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .menu {\n  top: 47px;\n  margin-right: 13px;\n}\n#contactsmenu-menu[data-v-f3f9ac60] .contact .popovermenu::after {\n  right: 2px;\n}", ""]);
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

/***/ "./core/src/jquery/contactsmenu/jquery_entry.handlebars":
/*!**************************************************************!*\
  !*** ./core/src/jquery/contactsmenu/jquery_entry.handlebars ***!
  \**************************************************************/
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

  return "<img src=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"icon") || (depth0 != null ? lookupProperty(depth0,"icon") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"icon","hash":{},"data":data,"loc":{"start":{"line":3,"column":24},"end":{"line":3,"column":32}}}) : helper)))
    + "\">";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<li>\n	<a href=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"hyperlink") || (depth0 != null ? lookupProperty(depth0,"hyperlink") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"hyperlink","hash":{},"data":data,"loc":{"start":{"line":2,"column":10},"end":{"line":2,"column":23}}}) : helper)))
    + "\">\n		"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"icon") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":2},"end":{"line":3,"column":41}}})) != null ? stack1 : "")
    + "\n		<span>"
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":17}}}) : helper)))
    + "</span>\n	</a>\n</li>\n";
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

/***/ "./core/src/jquery/css/jquery-ui-fixes.scss":
/*!**************************************************!*\
  !*** ./core/src/jquery/css/jquery-ui-fixes.scss ***!
  \**************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./jquery-ui-fixes.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery-ui-fixes.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ui_fixes_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./core/src/jquery/css/jquery.ocdialog.scss":
/*!**************************************************!*\
  !*** ./core/src/jquery/css/jquery.ocdialog.scss ***!
  \**************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./jquery.ocdialog.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/src/jquery/css/jquery.ocdialog.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_jquery_ocdialog_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./core/src/components/AppMenu.vue":
/*!*****************************************!*\
  !*** ./core/src/components/AppMenu.vue ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true& */ "./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true&");
/* harmony import */ var _AppMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppMenu.vue?vue&type=script&lang=js& */ "./core/src/components/AppMenu.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& */ "./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5dc6d664",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/AppMenu.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/views/ContactsMenu.vue":
/*!*****************************************!*\
  !*** ./core/src/views/ContactsMenu.vue ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true& */ "./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true&");
/* harmony import */ var _ContactsMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ContactsMenu.vue?vue&type=script&lang=js& */ "./core/src/views/ContactsMenu.vue?vue&type=script&lang=js&");
/* harmony import */ var _ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& */ "./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ContactsMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f3f9ac60",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/views/ContactsMenu.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/AppMenu.vue?vue&type=script&lang=js&":
/*!******************************************************************!*\
  !*** ./core/src/components/AppMenu.vue?vue&type=script&lang=js& ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppMenu.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/views/ContactsMenu.vue?vue&type=script&lang=js&":
/*!******************************************************************!*\
  !*** ./core/src/views/ContactsMenu.vue?vue&type=script&lang=js& ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ContactsMenu.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true&":
/*!************************************************************************************!*\
  !*** ./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_template_id_5dc6d664_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=template&id=5dc6d664&scoped=true&");


/***/ }),

/***/ "./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true&":
/*!************************************************************************************!*\
  !*** ./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_template_id_f3f9ac60_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=template&id=f3f9ac60&scoped=true&");


/***/ }),

/***/ "./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&":
/*!***************************************************************************************************!*\
  !*** ./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppMenu_vue_vue_type_style_index_0_id_5dc6d664_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/AppMenu.vue?vue&type=style&index=0&id=5dc6d664&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&":
/*!***************************************************************************************************!*\
  !*** ./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ContactsMenu_vue_vue_type_style_index_0_id_f3f9ac60_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/ContactsMenu.vue?vue&type=style&index=0&id=f3f9ac60&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/jquery/css/images/ui-bg_diagonals-thick_20_666666_40x40.png":
/*!******************************************************************************!*\
  !*** ./core/src/jquery/css/images/ui-bg_diagonals-thick_20_666666_40x40.png ***!
  \******************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoAQMAAAC2MCouAAAABlBMVEVmZmZ1dXVT6N0BAAAAUklEQVQIW8XNsQ3AIAwF0bMoKBmBURgNj8YojEBJEcXwu2yQ+p507BTeWDnozPISjPpY4O0W6CqEisUtiG/EF+IT8YG4fznihnhCPCNeEK/89D1Gd22TNOyXVAAAAABJRU5ErkJggg==";

/***/ }),

/***/ "./core/src/jquery/css/images/ui-bg_flat_10_000000_40x100.png":
/*!********************************************************************!*\
  !*** ./core/src/jquery/css/images/ui-bg_flat_10_000000_40x100.png ***!
  \********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAABkAQMAAADOquA5AAAAA1BMVEUAAACnej3aAAAADklEQVQYGWMYBaOABgAAAlgAARbiVEcAAAAASUVORK5CYII=";

/***/ }),

/***/ "./core/src/jquery/css/images/ui-icons_1d2d44_256x240.png":
/*!****************************************************************!*\
  !*** ./core/src/jquery/css/images/ui-icons_1d2d44_256x240.png ***!
  \****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAADwCAMAAADYSUr5AAAAaVBMVEUAAAAcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkQcLkSVcboQAAAAInRSTlMAGBAyCD9gIS5RZkqgwEQnj81slZ0MMK4WLB2ZcIGF737fFn1o5AAADQJJREFUeNrsml2OwjAMBuOrfPc/5IrsAwqjHVSVdiPhETy0tuOfuGlTGE3T7EClxjdTyeYVSJ1O0fN/fBblGwvCDsyDRQETlLxIK1mkSBEOYL8o39gS7MA8wByxAJxBSmlOB1SGySUwfk0BcqvgWIiPTmV6PI97ZIKokXcIZ1g7QAJAB9yGh4j8ABRkDbAWnMqb3RYuvAvwEprKe+X/B/0g1DRN0zTNF/CBJ8Gtn4Mq5c/ySUlC+QX18vcB8kKoMm4tCQNAAaiwHi0KqFeFBSjdPLLkn4bxe8TIGBWUemk9SZL5vQV28KQs4qI6Ey4p2JTu0wGyal30PmCOttEa0HeBpmmapmma/yPnH+ZPjZ+7E2AGfsKF78kx/2FAOKBcLXT8jFBlNQ9l5gABiFT8ywjwCDmklgHd5UUYCLWDYBAK3b9ul8MCiDgTz8DMNQAmmMmqkBf1CfwfKJG3MOcDx7R3cwZw0IOnx9FcIcEJlw8Q2ntDi8P3awCle90FLrbPg9E0TdM0TUPO/y01OR2A7hddlonH5+5zLABxAC3NwANYf1ZKLSInZRvozCGlgPRC/yyAJrCgM8gaVTLPFGTyb/7SAhTcvW8zrUCi+aMAPEPzrPV52mR4B2WC/TG3w/TvAUCKARAh7CGHPcXBAEMSRAFQoPcFQADQp4KLJ7p/HjTnJSAuhl0C9TTWS0B6nP5lEQsTAJwyiLAI2hzZIjjhImj2A6R8jlw8SPQaHoZ3AMn27wN+2DnX5bZBIIwuoBvquB13xp3ef5z3f8hGKO4KqNZx67bqlKMozrLCsJ8Qguji/voNMY1Go9FoHBjkd+KwT8zUOQB5IMA9CgCPjZ86BZwZf6Yad+8yrOvV1AFD5X8cJFyVksVS+G8FC1gbUAW8SQBDEN38wQIYz3cnV+aHG0Nt0lIFYLYPirxU2X+XAA7qoMj8icprXr42/WqoTeHF3hjhwZ1gKUClwP4exxKgzkFaqvyGALUfkMfi2Mx869kZuKqLtO9AKMC+neCWIIb/QWA/0YIzZ6933gSE5awVOvhs/vDjnEaj0Wg0fi/+Hz+RkRlQz+dqE34l/mO9KqmMTj80RFMAFrxkYJoHe1kWucHzb5XHozsZ8vmdX9wbG24+csChrlax/li363u8UE51UDspQJ6dvcvRjmMJwBVLIJ/ZtQD1hLUyNH4OdgjcbgH19olMoN0WQEK9JA72gLzdB+zuXrXxgq/6APUf9vg3zwJWly+KZ8EQNfe5gwVvjQNeDl5ejDugAL8KXhqNRqPR+CEBIMiL6RLyh4jAKYrBV+yRG5/ACjGU7mDr0ckEk6gCofz6ERilsjNDic9kGTQkPvd9RBMiQKyGujO7g9khkBiyeCHUtn4hZW201t1E1zF1xuXzlbxChaHAXJeosxP6vvcrhSCnTICNAnQLaAvIBABxTwg824FEYEcAuhWuAtB5H9gKcD6f7ScwBDLDFGDMBMQ/QeIqiPMrmwrmgl8W9loAEf14gmsfgFYwr/GFhYsK4MexzwR4//69ULfA2q4TagFG4PVWACATwHkKiRJaAO8XdluAiyzxO/0/QIAgKoAnrfp1K+gh8OrV9hA4y9InnrX8kJa7BdD446vX+wK4IkFwCS2AcRz3+wCcixDdVgCRrQABCJqfjwAfP14T/NoJ+uqYNwRIa52gAgyiJvMQgX5PgLJAxoQWwJs3b6DbbQHBxeiCCrDa+wK8WWE13cQ4Te+YXCZAEM0QlyUToCsF6AoByFrAvMZvC6DlfUgUTa7r9lpAcInAjk0EItkxOU0wrubEM1PVAjIB7joEICsvxV8JEPLyinEAX41xwD2nQZhJqygExqrF89JOb9Di64RaABk1/ocQwpAI8tPA+NgXJ9mM9NJoNBqN/4avX22/B2+4Ia02gbAzf4/Ado49szIX07Pxtq0RFfXpezG4wEVyhmHYxh+CKnDqgC9TRAc6M8yfMO/aDMD2T1QBmBfAmM9P03TbLvbJ8D16PHh63Z2zzNt9eoJTET8wjBo/qAK4on6UtvD2afmMKEEiGjAI7AaMnNOi+ZkEmTJbcvvSXSay+g9DXUE1Z7VnqhYnkcHr0JEAENgVwCfUlvCNvbNRTBOGovA1/CM4WTdcra7bef+HHAblJrklzOmoP/mw1WMieE8vScBgt6vtclsY8aOgiP7WgLpfzAAB5I5+NXVMsVGeQsMZrFEfb+8nIMbyNXYpUtWLtwia6G3MgD7jDI0dfuEnzPgR0V8bQJtuqfiU0pchA1iTrTkDOP502AMAvZXk4+2toVlzk5I5xw5AxEenPgM4A9KsW2T8GsA9HldQSrHe9AvPmBj2cdYRay439t+ObMQABTsj6KNjJ08rj7gwj5ekARGOiPit7TkGGHq7+VH/2AzH/ziSTWqOn0yUE7ASsq5ZH3Iftc8AcgCRUvy8gBt826DINIBI7hKDfCVmWpMTvzyAV2b8tEJJVGI1GLBLoTyvF4GWohGFVY1DFeMAcdpbaDFXaFKnHL/oBtkBZRQX1FEkZGaQh5zuEP9ASI6BAoFAIPCZFEBidGMdX8gDQP+THB35Bdf3+1GoiKgyu+Y9wA6sUBRZxg7kwI4M2iWiCMt2ZL5FgSMFa/kES/m5Qo66KN4tB4BLDEiRU47UeHFFlTsazwaN2Pm4vSqQU+oe3HC581Gt8wBKw3VAiDoHh4roC3J+YU1U4R1XMwBAyq/QsesfOwHYADeQgpCkQEpjBlhDTeiTUQAbQDv0mcdD9bIEDAO2iw5zg1Xn+ogBk/PpIcpz2PtUBVjxK0AakIGMw9ea45cZYr8eMaCrcAYABWVsAGkDDIfzts3znHXRxU8F6x6h4egxA+Rwu3Lij2C2ARtkHVgb41rr9fg+ZgBLBahB7wEUyIYnxNHrdrvYttjTEbyjIqovN8CfAbUdPweYV5ps0E7CQKluQoplgLXrZB3b7gbbn2q0DWjbbgewGsH3oqiR/+82oOYzcIkig9Y+54tqh73hAIjIbPYi2Aa8vh5vToKMtgFF1LYtWohu8P/1AjXVAAaZkE1VlmtWSLqbYgdg3PHDjPBxN4jsxEgbgOIAG8BcxQBJf/6lhuLTBw7osFqMd0XK2MfSaEGwDDDiozhC1N1imhoH3O41K+rlRRGT7g5K0eBYjzzjEggEAtehKIhZVuiolvQ8bIDNIL7iyFd6FpboWJqCaHhK06Ahg988mGESuhYNDjQ0GxsoNaTANzbg2/R3XzEJEnEsZD3h0WiiQ9xi/TOx7ANe9goGrgGMAtz4gWRi4ibrVbwaNG/zswzYAEoBG2Pj7nsoUbrx1xw7xz82dTdVKcB6RUQrq0LziQYkOJIIA2R+8ztWRhnHP2KAslJGTzSPwdUdAyI0TTPfSJcDlgYIOCTTP47/ogyYvRHkBFBqSIEXNuDFzAD/Crj84jaA5RzIRm/FcjXaCJqS8//iXoABzUaDgWZ4d5pU9HHCAFn6CF8wmKzRsT4rqIcyIBAIBAKBeUkg5IygTrxXSFyftzc3fgg5IwBbIA3QZcqskNTq8Au2f+Wgy77S+OFtAiRkawiJhOYCYAscA9geIBneng7PrmAZYJdLA2wJjZSguUBPKQ1ge/T9URLVAJwKlgG1jElG7JfwG3DXGQDNbWXAXG0Ac1NtwMy9ADQ31AvcAAls+XQGBAKBQOATwVNfR6W+En5tlTVQ2T/R9+Qq1J0BCTjkPFkDOTlAfP/BufpGqbDuDCBUliu1cADufXSevtWJjQoN0a+EGk4BoMqo7rQBOJD4e9zdhunb+H6az84ato4PS3yjw9voOG9+z3+hPAUyhd2IAYsjOGkIDaGxuNWvFNcZ0NFA2e1CBTt8uN9+F52nb3UXoFr3gSlq82i4QFbYBjxuI5gDzb4Bcvt0QJLACv+BP7DNNwA2d3nVfCAQCAQuhK8PmNZyEtX5mtc3j/Yjrw/wazmN7nzN65tDT7PEwHJKi4mUZ2qxvhm0H3l9gNYa1ikBlHaap9LiwMug4Wr6sJzX72yPXA1veUNEVrmtNaT1JHJyNE6wJkpT/WCyPpf7NYjGylmylvcgMnVZlqw1RC3wtwZYD6TWe2/qvGGCpz6JgER9j6HT74cA+HSr45/PAHnvC8ivpw2azoCW+vgx2y7g1wzrKQMBTGSIR6OlFlpPIq8PkI0aN4Ivo40UXE0j5SONJLkannKtfBpoWXuZuxsT65tBTzH/QIbXN4/2M/9Qltd3bX1L1zsEAoFAIHA3oOdSfdP/XNsL4gOY0I9tAPwG6IU1QH4DCHRfBgAcoNDSIOhfHg0KGXBnBjx5G/DsvUAgEAgEAoFrc6tzYyXTsyARITo//gXdCwtaXGzAAvcb/0UZwPHeb/x2BmBxWkYMwAk7XpCtH7cNiE5w+eAX67vKgUszoK9/v/H/awY8TPyX9gIy/sduA6b7/7vLAc6AK4BF/3NH8f/ZKBi5AADUzjm/v2XQ+gAAAABJRU5ErkJggg==";

/***/ }),

/***/ "./core/src/jquery/css/images/ui-icons_ffd27a_256x240.png":
/*!****************************************************************!*\
  !*** ./core/src/jquery/css/images/ui-icons_ffd27a_256x240.png ***!
  \****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,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";

/***/ }),

/***/ "./core/src/jquery/css/images/ui-icons_ffffff_256x240.png":
/*!****************************************************************!*\
  !*** ./core/src/jquery/css/images/ui-icons_ffffff_256x240.png ***!
  \****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,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";

/***/ }),

/***/ "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7":
/*!**************************************************************************************!*\
  !*** data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7 ***!
  \**************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

/***/ }),

/***/ "data:image/gif;base64,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":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/gif;base64,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 ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/gif;base64,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";

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
/******/ 			"core-main": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./core/src/main.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-main.js.map?v=309d4d5d00eebaefecb8