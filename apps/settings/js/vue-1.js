(window["webpackJsonpSettings"] = window["webpackJsonpSettings"] || []).push([[1],{

/***/ "./node_modules/@nextcloud/auth/dist/index.js":
/*!****************************************************!*\
  !*** ./node_modules/@nextcloud/auth/dist/index.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "getRequestToken", {
  enumerable: true,
  get: function get() {
    return _requesttoken.getRequestToken;
  }
});
Object.defineProperty(exports, "onRequestTokenUpdate", {
  enumerable: true,
  get: function get() {
    return _requesttoken.onRequestTokenUpdate;
  }
});
Object.defineProperty(exports, "getCurrentUser", {
  enumerable: true,
  get: function get() {
    return _user.getCurrentUser;
  }
});

var _requesttoken = __webpack_require__(/*! ./requesttoken */ "./node_modules/@nextcloud/auth/dist/requesttoken.js");

var _user = __webpack_require__(/*! ./user */ "./node_modules/@nextcloud/auth/dist/user.js");
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/dist/requesttoken.js":
/*!***********************************************************!*\
  !*** ./node_modules/@nextcloud/auth/dist/requesttoken.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getRequestToken = getRequestToken;
exports.onRequestTokenUpdate = onRequestTokenUpdate;

var _eventBus = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/index.js");

var tokenElement = document.getElementsByTagName('head')[0];
var token = tokenElement ? tokenElement.getAttribute('data-requesttoken') : null;
var observers = [];

function getRequestToken() {
  return token;
}

function onRequestTokenUpdate(observer) {
  observers.push(observer);
} // Listen to server event and keep token in sync


(0, _eventBus.subscribe)('csrf-token-update', function (e) {
  token = e.token;
  observers.forEach(function (observer) {
    try {
      observer(e.token);
    } catch (e) {
      console.error('error updating CSRF token observer', e);
    }
  });
});
//# sourceMappingURL=requesttoken.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/dist/user.js":
/*!***************************************************!*\
  !*** ./node_modules/@nextcloud/auth/dist/user.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getCurrentUser = getCurrentUser;
var uidElement = document.getElementsByTagName('head')[0];
var uid = uidElement ? uidElement.getAttribute('data-user') : null;
var displayNameElement = document.getElementsByTagName('head')[0];
var displayName = displayNameElement ? displayNameElement.getAttribute('data-user-displayname') : null;

function getCurrentUser() {
  if (uid === null) {
    return null;
  }

  return {
    uid: uid,
    displayName: displayName
  };
}
//# sourceMappingURL=user.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/ProxyBus.js":
/*!*****************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/ProxyBus.js ***!
  \*****************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ProxyBus = void 0;

var _semver = _interopRequireDefault(__webpack_require__(/*! semver */ "./node_modules/@nextcloud/auth/node_modules/semver/semver.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var packageJson = {
  name: "@nextcloud/event-bus",
  version: "1.1.2",
  description: "",
  main: "dist/index.js",
  types: "dist/index.d.ts",
  scripts: {
    build: "babel ./lib --out-dir dist --extensions '.ts,.tsx' --source-maps && tsc --emitDeclarationOnly",
    "build:doc": "typedoc --excludeNotExported --mode file --out dist/doc lib/index.ts && touch dist/doc/.nojekyll",
    "check-types": "tsc",
    dev: "babel ./lib --out-dir dist --extensions '.ts,.tsx' --watch",
    test: "jest",
    "test:watch": "jest --watchAll"
  },
  keywords: ["nextcloud"],
  homepage: "https://github.com/nextcloud/nextcloud-event-bus#readme",
  author: "Christoph Wurst",
  license: "GPL-3.0-or-later",
  repository: {
    type: "git",
    url: "https://github.com/nextcloud/nextcloud-event-bus"
  },
  dependencies: {
    "@types/semver": "^6.2.0",
    "core-js": "^3.6.2",
    semver: "^6.3.0"
  },
  devDependencies: {
    "@babel/cli": "^7.6.0",
    "@babel/core": "^7.6.0",
    "@babel/plugin-proposal-class-properties": "^7.5.5",
    "@babel/preset-env": "^7.6.0",
    "@babel/preset-typescript": "^7.6.0",
    "@nextcloud/browserslist-config": "^1.0.0",
    "babel-jest": "^24.9.0",
    "babel-plugin-inline-json-import": "^0.3.2",
    jest: "^24.9.0",
    typedoc: "^0.15.7",
    typescript: "^3.6.3"
  },
  browserslist: ["extends @nextcloud/browserslist-config"]
};

var ProxyBus =
/*#__PURE__*/
function () {
  function ProxyBus(bus) {
    _classCallCheck(this, ProxyBus);

    _defineProperty(this, "bus", void 0);

    if (typeof bus.getVersion !== 'function' || !_semver.default.valid(bus.getVersion())) {
      console.warn('Proxying an event bus with an unknown or invalid version');
    } else if (_semver.default.major(bus.getVersion()) !== _semver.default.major(this.getVersion())) {
      console.warn('Proxying an event bus of version ' + bus.getVersion() + ' with ' + this.getVersion());
    }

    this.bus = bus;
  }

  _createClass(ProxyBus, [{
    key: "getVersion",
    value: function getVersion() {
      return packageJson.version;
    }
  }, {
    key: "subscribe",
    value: function subscribe(name, handler) {
      this.bus.subscribe(name, handler);
    }
  }, {
    key: "unsubscribe",
    value: function unsubscribe(name, handler) {
      this.bus.unsubscribe(name, handler);
    }
  }, {
    key: "emit",
    value: function emit(name, event) {
      this.bus.emit(name, event);
    }
  }]);

  return ProxyBus;
}();

exports.ProxyBus = ProxyBus;
//# sourceMappingURL=ProxyBus.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/SimpleBus.js":
/*!******************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/SimpleBus.js ***!
  \******************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(/*! core-js/modules/es.array.concat */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.concat.js");

__webpack_require__(/*! core-js/modules/es.array.filter */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.filter.js");

__webpack_require__(/*! core-js/modules/es.array.iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.iterator.js");

__webpack_require__(/*! core-js/modules/es.map */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.map.js");

__webpack_require__(/*! core-js/modules/es.object.to-string */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.object.to-string.js");

__webpack_require__(/*! core-js/modules/es.string.iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.string.iterator.js");

__webpack_require__(/*! core-js/modules/web.dom-collections.for-each */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.for-each.js");

__webpack_require__(/*! core-js/modules/web.dom-collections.iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.iterator.js");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.SimpleBus = void 0;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var packageJson = {
  name: "@nextcloud/event-bus",
  version: "1.1.2",
  description: "",
  main: "dist/index.js",
  types: "dist/index.d.ts",
  scripts: {
    build: "babel ./lib --out-dir dist --extensions '.ts,.tsx' --source-maps && tsc --emitDeclarationOnly",
    "build:doc": "typedoc --excludeNotExported --mode file --out dist/doc lib/index.ts && touch dist/doc/.nojekyll",
    "check-types": "tsc",
    dev: "babel ./lib --out-dir dist --extensions '.ts,.tsx' --watch",
    test: "jest",
    "test:watch": "jest --watchAll"
  },
  keywords: ["nextcloud"],
  homepage: "https://github.com/nextcloud/nextcloud-event-bus#readme",
  author: "Christoph Wurst",
  license: "GPL-3.0-or-later",
  repository: {
    type: "git",
    url: "https://github.com/nextcloud/nextcloud-event-bus"
  },
  dependencies: {
    "@types/semver": "^6.2.0",
    "core-js": "^3.6.2",
    semver: "^6.3.0"
  },
  devDependencies: {
    "@babel/cli": "^7.6.0",
    "@babel/core": "^7.6.0",
    "@babel/plugin-proposal-class-properties": "^7.5.5",
    "@babel/preset-env": "^7.6.0",
    "@babel/preset-typescript": "^7.6.0",
    "@nextcloud/browserslist-config": "^1.0.0",
    "babel-jest": "^24.9.0",
    "babel-plugin-inline-json-import": "^0.3.2",
    jest: "^24.9.0",
    typedoc: "^0.15.7",
    typescript: "^3.6.3"
  },
  browserslist: ["extends @nextcloud/browserslist-config"]
};

var SimpleBus =
/*#__PURE__*/
function () {
  function SimpleBus() {
    _classCallCheck(this, SimpleBus);

    _defineProperty(this, "handlers", new Map());
  }

  _createClass(SimpleBus, [{
    key: "getVersion",
    value: function getVersion() {
      return packageJson.version;
    }
  }, {
    key: "subscribe",
    value: function subscribe(name, handler) {
      this.handlers.set(name, (this.handlers.get(name) || []).concat(handler));
    }
  }, {
    key: "unsubscribe",
    value: function unsubscribe(name, handler) {
      this.handlers.set(name, (this.handlers.get(name) || []).filter(function (h) {
        return h != handler;
      }));
    }
  }, {
    key: "emit",
    value: function emit(name, event) {
      (this.handlers.get(name) || []).forEach(function (h) {
        try {
          h(event);
        } catch (e) {
          console.error('could not invoke event listener', e);
        }
      });
    }
  }]);

  return SimpleBus;
}();

exports.SimpleBus = SimpleBus;
//# sourceMappingURL=SimpleBus.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/index.js":
/*!**************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/index.js ***!
  \**************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.subscribe = subscribe;
exports.unsubscribe = unsubscribe;
exports.emit = emit;

var _ProxyBus = __webpack_require__(/*! ./ProxyBus */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/ProxyBus.js");

var _SimpleBus = __webpack_require__(/*! ./SimpleBus */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/dist/SimpleBus.js");

function getBus() {
  if (typeof window.OC !== 'undefined' && window.OC._eventBus && typeof window._nc_event_bus === 'undefined') {
    console.warn('found old event bus instance at OC._eventBus. Update your version!');
    window._nc_event_bus = window.OC._eventBus;
  } // Either use an existing event bus instance or create one


  if (typeof window._nc_event_bus !== 'undefined') {
    return new _ProxyBus.ProxyBus(window._nc_event_bus);
  } else {
    return window._nc_event_bus = new _SimpleBus.SimpleBus();
  }
}

var bus = getBus();
/**
 * Register an event listener
 *
 * @param name name of the event
 * @param handler callback invoked for every matching event emitted on the bus
 */

function subscribe(name, handler) {
  bus.subscribe(name, handler);
}
/**
 * Unregister a previously registered event listener
 *
 * Note: doesn't work with anonymous functions (closures). Use method of an object or store listener function in variable.
 *
 * @param name name of the event
 * @param handler callback passed to `subscribed`
 */


function unsubscribe(name, handler) {
  bus.unsubscribe(name, handler);
}
/**
 * Emit an event
 *
 * @param name name of the event
 * @param event event payload
 */


function emit(name, event) {
  bus.emit(name, event);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-function.js":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-function.js ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = function (it) {
  if (typeof it != 'function') {
    throw TypeError(String(it) + ' is not a function');
  } return it;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-possible-prototype.js":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-possible-prototype.js ***!
  \*******************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");

module.exports = function (it) {
  if (!isObject(it) && it !== null) {
    throw TypeError("Can't set " + String(it) + ' as a prototype');
  } return it;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/add-to-unscopables.js":
/*!*****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/add-to-unscopables.js ***!
  \*****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var create = __webpack_require__(/*! ../internals/object-create */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-create.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");

var UNSCOPABLES = wellKnownSymbol('unscopables');
var ArrayPrototype = Array.prototype;

// Array.prototype[@@unscopables]
// https://tc39.github.io/ecma262/#sec-array.prototype-@@unscopables
if (ArrayPrototype[UNSCOPABLES] == undefined) {
  definePropertyModule.f(ArrayPrototype, UNSCOPABLES, {
    configurable: true,
    value: create(null)
  });
}

// add a key to Array.prototype[@@unscopables]
module.exports = function (key) {
  ArrayPrototype[UNSCOPABLES][key] = true;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-instance.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-instance.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = function (it, Constructor, name) {
  if (!(it instanceof Constructor)) {
    throw TypeError('Incorrect ' + (name ? name + ' ' : '') + 'invocation');
  } return it;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");

module.exports = function (it) {
  if (!isObject(it)) {
    throw TypeError(String(it) + ' is not an object');
  } return it;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-for-each.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-for-each.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $forEach = __webpack_require__(/*! ../internals/array-iteration */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-iteration.js").forEach;
var arrayMethodIsStrict = __webpack_require__(/*! ../internals/array-method-is-strict */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-is-strict.js");
var arrayMethodUsesToLength = __webpack_require__(/*! ../internals/array-method-uses-to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-uses-to-length.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');
var USES_TO_LENGTH = arrayMethodUsesToLength('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.github.io/ecma262/#sec-array.prototype.foreach
module.exports = (!STRICT_METHOD || !USES_TO_LENGTH) ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
} : [].forEach;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-includes.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-includes.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js");
var toLength = __webpack_require__(/*! ../internals/to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js");
var toAbsoluteIndex = __webpack_require__(/*! ../internals/to-absolute-index */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-absolute-index.js");

// `Array.prototype.{ indexOf, includes }` methods implementation
var createMethod = function (IS_INCLUDES) {
  return function ($this, el, fromIndex) {
    var O = toIndexedObject($this);
    var length = toLength(O.length);
    var index = toAbsoluteIndex(fromIndex, length);
    var value;
    // Array#includes uses SameValueZero equality algorithm
    // eslint-disable-next-line no-self-compare
    if (IS_INCLUDES && el != el) while (length > index) {
      value = O[index++];
      // eslint-disable-next-line no-self-compare
      if (value != value) return true;
    // Array#indexOf ignores holes, Array#includes - not
    } else for (;length > index; index++) {
      if ((IS_INCLUDES || index in O) && O[index] === el) return IS_INCLUDES || index || 0;
    } return !IS_INCLUDES && -1;
  };
};

module.exports = {
  // `Array.prototype.includes` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.includes
  includes: createMethod(true),
  // `Array.prototype.indexOf` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.indexof
  indexOf: createMethod(false)
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-iteration.js":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-iteration.js ***!
  \**************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var bind = __webpack_require__(/*! ../internals/function-bind-context */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/function-bind-context.js");
var IndexedObject = __webpack_require__(/*! ../internals/indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/indexed-object.js");
var toObject = __webpack_require__(/*! ../internals/to-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-object.js");
var toLength = __webpack_require__(/*! ../internals/to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js");
var arraySpeciesCreate = __webpack_require__(/*! ../internals/array-species-create */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-species-create.js");

var push = [].push;

// `Array.prototype.{ forEach, map, filter, some, every, find, findIndex }` methods implementation
var createMethod = function (TYPE) {
  var IS_MAP = TYPE == 1;
  var IS_FILTER = TYPE == 2;
  var IS_SOME = TYPE == 3;
  var IS_EVERY = TYPE == 4;
  var IS_FIND_INDEX = TYPE == 6;
  var NO_HOLES = TYPE == 5 || IS_FIND_INDEX;
  return function ($this, callbackfn, that, specificCreate) {
    var O = toObject($this);
    var self = IndexedObject(O);
    var boundFunction = bind(callbackfn, that, 3);
    var length = toLength(self.length);
    var index = 0;
    var create = specificCreate || arraySpeciesCreate;
    var target = IS_MAP ? create($this, length) : IS_FILTER ? create($this, 0) : undefined;
    var value, result;
    for (;length > index; index++) if (NO_HOLES || index in self) {
      value = self[index];
      result = boundFunction(value, index, O);
      if (TYPE) {
        if (IS_MAP) target[index] = result; // map
        else if (result) switch (TYPE) {
          case 3: return true;              // some
          case 5: return value;             // find
          case 6: return index;             // findIndex
          case 2: push.call(target, value); // filter
        } else if (IS_EVERY) return false;  // every
      }
    }
    return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
  };
};

module.exports = {
  // `Array.prototype.forEach` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.foreach
  forEach: createMethod(0),
  // `Array.prototype.map` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.map
  map: createMethod(1),
  // `Array.prototype.filter` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.filter
  filter: createMethod(2),
  // `Array.prototype.some` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.some
  some: createMethod(3),
  // `Array.prototype.every` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.every
  every: createMethod(4),
  // `Array.prototype.find` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.find
  find: createMethod(5),
  // `Array.prototype.findIndex` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.findIndex
  findIndex: createMethod(6)
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-has-species-support.js":
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-has-species-support.js ***!
  \*******************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__(/*! ../internals/engine-v8-version */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-v8-version.js");

var SPECIES = wellKnownSymbol('species');

module.exports = function (METHOD_NAME) {
  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/677
  return V8_VERSION >= 51 || !fails(function () {
    var array = [];
    var constructor = array.constructor = {};
    constructor[SPECIES] = function () {
      return { foo: 1 };
    };
    return array[METHOD_NAME](Boolean).foo !== 1;
  });
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-is-strict.js":
/*!*********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-is-strict.js ***!
  \*********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

module.exports = function (METHOD_NAME, argument) {
  var method = [][METHOD_NAME];
  return !!method && fails(function () {
    // eslint-disable-next-line no-useless-call,no-throw-literal
    method.call(null, argument || function () { throw 1; }, 1);
  });
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-uses-to-length.js":
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-uses-to-length.js ***!
  \**************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");

var defineProperty = Object.defineProperty;
var cache = {};

var thrower = function (it) { throw it; };

module.exports = function (METHOD_NAME, options) {
  if (has(cache, METHOD_NAME)) return cache[METHOD_NAME];
  if (!options) options = {};
  var method = [][METHOD_NAME];
  var ACCESSORS = has(options, 'ACCESSORS') ? options.ACCESSORS : false;
  var argument0 = has(options, 0) ? options[0] : thrower;
  var argument1 = has(options, 1) ? options[1] : undefined;

  return cache[METHOD_NAME] = !!method && !fails(function () {
    if (ACCESSORS && !DESCRIPTORS) return true;
    var O = { length: -1 };

    if (ACCESSORS) defineProperty(O, 1, { enumerable: true, get: thrower });
    else O[1] = 1;

    method.call(O, argument0, argument1);
  });
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-species-create.js":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-species-create.js ***!
  \*******************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var isArray = __webpack_require__(/*! ../internals/is-array */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var SPECIES = wellKnownSymbol('species');

// `ArraySpeciesCreate` abstract operation
// https://tc39.github.io/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray, length) {
  var C;
  if (isArray(originalArray)) {
    C = originalArray.constructor;
    // cross-realm fallback
    if (typeof C == 'function' && (C === Array || isArray(C.prototype))) C = undefined;
    else if (isObject(C)) {
      C = C[SPECIES];
      if (C === null) C = undefined;
    }
  } return new (C === undefined ? Array : C)(length === 0 ? 0 : length);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/call-with-safe-iteration-closing.js":
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/call-with-safe-iteration-closing.js ***!
  \*******************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");

// call something on iterator step with safe closing on error
module.exports = function (iterator, fn, value, ENTRIES) {
  try {
    return ENTRIES ? fn(anObject(value)[0], value[1]) : fn(value);
  // 7.4.6 IteratorClose(iterator, completion)
  } catch (error) {
    var returnMethod = iterator['return'];
    if (returnMethod !== undefined) anObject(returnMethod.call(iterator));
    throw error;
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/check-correctness-of-iteration.js":
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/check-correctness-of-iteration.js ***!
  \*****************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var ITERATOR = wellKnownSymbol('iterator');
var SAFE_CLOSING = false;

try {
  var called = 0;
  var iteratorWithReturn = {
    next: function () {
      return { done: !!called++ };
    },
    'return': function () {
      SAFE_CLOSING = true;
    }
  };
  iteratorWithReturn[ITERATOR] = function () {
    return this;
  };
  // eslint-disable-next-line no-throw-literal
  Array.from(iteratorWithReturn, function () { throw 2; });
} catch (error) { /* empty */ }

module.exports = function (exec, SKIP_CLOSING) {
  if (!SKIP_CLOSING && !SAFE_CLOSING) return false;
  var ITERATION_SUPPORT = false;
  try {
    var object = {};
    object[ITERATOR] = function () {
      return {
        next: function () {
          return { done: ITERATION_SUPPORT = true };
        }
      };
    };
    exec(object);
  } catch (error) { /* empty */ }
  return ITERATION_SUPPORT;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof-raw.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof-raw.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var toString = {}.toString;

module.exports = function (it) {
  return toString.call(it).slice(8, -1);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof.js":
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof.js ***!
  \******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var TO_STRING_TAG_SUPPORT = __webpack_require__(/*! ../internals/to-string-tag-support */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-string-tag-support.js");
var classofRaw = __webpack_require__(/*! ../internals/classof-raw */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof-raw.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');
// ES3 wrong here
var CORRECT_ARGUMENTS = classofRaw(function () { return arguments; }()) == 'Arguments';

// fallback for IE11 Script Access Denied error
var tryGet = function (it, key) {
  try {
    return it[key];
  } catch (error) { /* empty */ }
};

// getting tag from ES6+ `Object.prototype.toString`
module.exports = TO_STRING_TAG_SUPPORT ? classofRaw : function (it) {
  var O, tag, result;
  return it === undefined ? 'Undefined' : it === null ? 'Null'
    // @@toStringTag case
    : typeof (tag = tryGet(O = Object(it), TO_STRING_TAG)) == 'string' ? tag
    // builtinTag case
    : CORRECT_ARGUMENTS ? classofRaw(O)
    // ES3 arguments fallback
    : (result = classofRaw(O)) == 'Object' && typeof O.callee == 'function' ? 'Arguments' : result;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection-strong.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection-strong.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var defineProperty = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js").f;
var create = __webpack_require__(/*! ../internals/object-create */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-create.js");
var redefineAll = __webpack_require__(/*! ../internals/redefine-all */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine-all.js");
var bind = __webpack_require__(/*! ../internals/function-bind-context */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/function-bind-context.js");
var anInstance = __webpack_require__(/*! ../internals/an-instance */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-instance.js");
var iterate = __webpack_require__(/*! ../internals/iterate */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterate.js");
var defineIterator = __webpack_require__(/*! ../internals/define-iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/define-iterator.js");
var setSpecies = __webpack_require__(/*! ../internals/set-species */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-species.js");
var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var fastKey = __webpack_require__(/*! ../internals/internal-metadata */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-metadata.js").fastKey;
var InternalStateModule = __webpack_require__(/*! ../internals/internal-state */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js");

var setInternalState = InternalStateModule.set;
var internalStateGetterFor = InternalStateModule.getterFor;

module.exports = {
  getConstructor: function (wrapper, CONSTRUCTOR_NAME, IS_MAP, ADDER) {
    var C = wrapper(function (that, iterable) {
      anInstance(that, C, CONSTRUCTOR_NAME);
      setInternalState(that, {
        type: CONSTRUCTOR_NAME,
        index: create(null),
        first: undefined,
        last: undefined,
        size: 0
      });
      if (!DESCRIPTORS) that.size = 0;
      if (iterable != undefined) iterate(iterable, that[ADDER], that, IS_MAP);
    });

    var getInternalState = internalStateGetterFor(CONSTRUCTOR_NAME);

    var define = function (that, key, value) {
      var state = getInternalState(that);
      var entry = getEntry(that, key);
      var previous, index;
      // change existing entry
      if (entry) {
        entry.value = value;
      // create new entry
      } else {
        state.last = entry = {
          index: index = fastKey(key, true),
          key: key,
          value: value,
          previous: previous = state.last,
          next: undefined,
          removed: false
        };
        if (!state.first) state.first = entry;
        if (previous) previous.next = entry;
        if (DESCRIPTORS) state.size++;
        else that.size++;
        // add to index
        if (index !== 'F') state.index[index] = entry;
      } return that;
    };

    var getEntry = function (that, key) {
      var state = getInternalState(that);
      // fast case
      var index = fastKey(key);
      var entry;
      if (index !== 'F') return state.index[index];
      // frozen object case
      for (entry = state.first; entry; entry = entry.next) {
        if (entry.key == key) return entry;
      }
    };

    redefineAll(C.prototype, {
      // 23.1.3.1 Map.prototype.clear()
      // 23.2.3.2 Set.prototype.clear()
      clear: function clear() {
        var that = this;
        var state = getInternalState(that);
        var data = state.index;
        var entry = state.first;
        while (entry) {
          entry.removed = true;
          if (entry.previous) entry.previous = entry.previous.next = undefined;
          delete data[entry.index];
          entry = entry.next;
        }
        state.first = state.last = undefined;
        if (DESCRIPTORS) state.size = 0;
        else that.size = 0;
      },
      // 23.1.3.3 Map.prototype.delete(key)
      // 23.2.3.4 Set.prototype.delete(value)
      'delete': function (key) {
        var that = this;
        var state = getInternalState(that);
        var entry = getEntry(that, key);
        if (entry) {
          var next = entry.next;
          var prev = entry.previous;
          delete state.index[entry.index];
          entry.removed = true;
          if (prev) prev.next = next;
          if (next) next.previous = prev;
          if (state.first == entry) state.first = next;
          if (state.last == entry) state.last = prev;
          if (DESCRIPTORS) state.size--;
          else that.size--;
        } return !!entry;
      },
      // 23.2.3.6 Set.prototype.forEach(callbackfn, thisArg = undefined)
      // 23.1.3.5 Map.prototype.forEach(callbackfn, thisArg = undefined)
      forEach: function forEach(callbackfn /* , that = undefined */) {
        var state = getInternalState(this);
        var boundFunction = bind(callbackfn, arguments.length > 1 ? arguments[1] : undefined, 3);
        var entry;
        while (entry = entry ? entry.next : state.first) {
          boundFunction(entry.value, entry.key, this);
          // revert to the last existing entry
          while (entry && entry.removed) entry = entry.previous;
        }
      },
      // 23.1.3.7 Map.prototype.has(key)
      // 23.2.3.7 Set.prototype.has(value)
      has: function has(key) {
        return !!getEntry(this, key);
      }
    });

    redefineAll(C.prototype, IS_MAP ? {
      // 23.1.3.6 Map.prototype.get(key)
      get: function get(key) {
        var entry = getEntry(this, key);
        return entry && entry.value;
      },
      // 23.1.3.9 Map.prototype.set(key, value)
      set: function set(key, value) {
        return define(this, key === 0 ? 0 : key, value);
      }
    } : {
      // 23.2.3.1 Set.prototype.add(value)
      add: function add(value) {
        return define(this, value = value === 0 ? 0 : value, value);
      }
    });
    if (DESCRIPTORS) defineProperty(C.prototype, 'size', {
      get: function () {
        return getInternalState(this).size;
      }
    });
    return C;
  },
  setStrong: function (C, CONSTRUCTOR_NAME, IS_MAP) {
    var ITERATOR_NAME = CONSTRUCTOR_NAME + ' Iterator';
    var getInternalCollectionState = internalStateGetterFor(CONSTRUCTOR_NAME);
    var getInternalIteratorState = internalStateGetterFor(ITERATOR_NAME);
    // add .keys, .values, .entries, [@@iterator]
    // 23.1.3.4, 23.1.3.8, 23.1.3.11, 23.1.3.12, 23.2.3.5, 23.2.3.8, 23.2.3.10, 23.2.3.11
    defineIterator(C, CONSTRUCTOR_NAME, function (iterated, kind) {
      setInternalState(this, {
        type: ITERATOR_NAME,
        target: iterated,
        state: getInternalCollectionState(iterated),
        kind: kind,
        last: undefined
      });
    }, function () {
      var state = getInternalIteratorState(this);
      var kind = state.kind;
      var entry = state.last;
      // revert to the last existing entry
      while (entry && entry.removed) entry = entry.previous;
      // get next entry
      if (!state.target || !(state.last = entry = entry ? entry.next : state.state.first)) {
        // or finish the iteration
        state.target = undefined;
        return { value: undefined, done: true };
      }
      // return step by kind
      if (kind == 'keys') return { value: entry.key, done: false };
      if (kind == 'values') return { value: entry.value, done: false };
      return { value: [entry.key, entry.value], done: false };
    }, IS_MAP ? 'entries' : 'values', !IS_MAP, true);

    // add [@@species], 23.1.2.2, 23.2.2.2
    setSpecies(CONSTRUCTOR_NAME);
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection.js":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection.js ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js");
var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var isForced = __webpack_require__(/*! ../internals/is-forced */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-forced.js");
var redefine = __webpack_require__(/*! ../internals/redefine */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js");
var InternalMetadataModule = __webpack_require__(/*! ../internals/internal-metadata */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-metadata.js");
var iterate = __webpack_require__(/*! ../internals/iterate */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterate.js");
var anInstance = __webpack_require__(/*! ../internals/an-instance */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-instance.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var checkCorrectnessOfIteration = __webpack_require__(/*! ../internals/check-correctness-of-iteration */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/check-correctness-of-iteration.js");
var setToStringTag = __webpack_require__(/*! ../internals/set-to-string-tag */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-to-string-tag.js");
var inheritIfRequired = __webpack_require__(/*! ../internals/inherit-if-required */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inherit-if-required.js");

module.exports = function (CONSTRUCTOR_NAME, wrapper, common) {
  var IS_MAP = CONSTRUCTOR_NAME.indexOf('Map') !== -1;
  var IS_WEAK = CONSTRUCTOR_NAME.indexOf('Weak') !== -1;
  var ADDER = IS_MAP ? 'set' : 'add';
  var NativeConstructor = global[CONSTRUCTOR_NAME];
  var NativePrototype = NativeConstructor && NativeConstructor.prototype;
  var Constructor = NativeConstructor;
  var exported = {};

  var fixMethod = function (KEY) {
    var nativeMethod = NativePrototype[KEY];
    redefine(NativePrototype, KEY,
      KEY == 'add' ? function add(value) {
        nativeMethod.call(this, value === 0 ? 0 : value);
        return this;
      } : KEY == 'delete' ? function (key) {
        return IS_WEAK && !isObject(key) ? false : nativeMethod.call(this, key === 0 ? 0 : key);
      } : KEY == 'get' ? function get(key) {
        return IS_WEAK && !isObject(key) ? undefined : nativeMethod.call(this, key === 0 ? 0 : key);
      } : KEY == 'has' ? function has(key) {
        return IS_WEAK && !isObject(key) ? false : nativeMethod.call(this, key === 0 ? 0 : key);
      } : function set(key, value) {
        nativeMethod.call(this, key === 0 ? 0 : key, value);
        return this;
      }
    );
  };

  // eslint-disable-next-line max-len
  if (isForced(CONSTRUCTOR_NAME, typeof NativeConstructor != 'function' || !(IS_WEAK || NativePrototype.forEach && !fails(function () {
    new NativeConstructor().entries().next();
  })))) {
    // create collection constructor
    Constructor = common.getConstructor(wrapper, CONSTRUCTOR_NAME, IS_MAP, ADDER);
    InternalMetadataModule.REQUIRED = true;
  } else if (isForced(CONSTRUCTOR_NAME, true)) {
    var instance = new Constructor();
    // early implementations not supports chaining
    var HASNT_CHAINING = instance[ADDER](IS_WEAK ? {} : -0, 1) != instance;
    // V8 ~ Chromium 40- weak-collections throws on primitives, but should return false
    var THROWS_ON_PRIMITIVES = fails(function () { instance.has(1); });
    // most early implementations doesn't supports iterables, most modern - not close it correctly
    // eslint-disable-next-line no-new
    var ACCEPT_ITERABLES = checkCorrectnessOfIteration(function (iterable) { new NativeConstructor(iterable); });
    // for early implementations -0 and +0 not the same
    var BUGGY_ZERO = !IS_WEAK && fails(function () {
      // V8 ~ Chromium 42- fails only with 5+ elements
      var $instance = new NativeConstructor();
      var index = 5;
      while (index--) $instance[ADDER](index, index);
      return !$instance.has(-0);
    });

    if (!ACCEPT_ITERABLES) {
      Constructor = wrapper(function (dummy, iterable) {
        anInstance(dummy, Constructor, CONSTRUCTOR_NAME);
        var that = inheritIfRequired(new NativeConstructor(), dummy, Constructor);
        if (iterable != undefined) iterate(iterable, that[ADDER], that, IS_MAP);
        return that;
      });
      Constructor.prototype = NativePrototype;
      NativePrototype.constructor = Constructor;
    }

    if (THROWS_ON_PRIMITIVES || BUGGY_ZERO) {
      fixMethod('delete');
      fixMethod('has');
      IS_MAP && fixMethod('get');
    }

    if (BUGGY_ZERO || HASNT_CHAINING) fixMethod(ADDER);

    // weak collections should not contains .clear method
    if (IS_WEAK && NativePrototype.clear) delete NativePrototype.clear;
  }

  exported[CONSTRUCTOR_NAME] = Constructor;
  $({ global: true, forced: Constructor != NativeConstructor }, exported);

  setToStringTag(Constructor, CONSTRUCTOR_NAME);

  if (!IS_WEAK) common.setStrong(Constructor, CONSTRUCTOR_NAME, IS_MAP);

  return Constructor;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/copy-constructor-properties.js":
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/copy-constructor-properties.js ***!
  \**************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var ownKeys = __webpack_require__(/*! ../internals/own-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/own-keys.js");
var getOwnPropertyDescriptorModule = __webpack_require__(/*! ../internals/object-get-own-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-descriptor.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");

module.exports = function (target, source) {
  var keys = ownKeys(source);
  var defineProperty = definePropertyModule.f;
  var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
  for (var i = 0; i < keys.length; i++) {
    var key = keys[i];
    if (!has(target, key)) defineProperty(target, key, getOwnPropertyDescriptor(source, key));
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/correct-prototype-getter.js":
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/correct-prototype-getter.js ***!
  \***********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

module.exports = !fails(function () {
  function F() { /* empty */ }
  F.prototype.constructor = null;
  return Object.getPrototypeOf(new F()) !== F.prototype;
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-iterator-constructor.js":
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-iterator-constructor.js ***!
  \**************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var IteratorPrototype = __webpack_require__(/*! ../internals/iterators-core */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators-core.js").IteratorPrototype;
var create = __webpack_require__(/*! ../internals/object-create */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-create.js");
var createPropertyDescriptor = __webpack_require__(/*! ../internals/create-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js");
var setToStringTag = __webpack_require__(/*! ../internals/set-to-string-tag */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-to-string-tag.js");
var Iterators = __webpack_require__(/*! ../internals/iterators */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js");

var returnThis = function () { return this; };

module.exports = function (IteratorConstructor, NAME, next) {
  var TO_STRING_TAG = NAME + ' Iterator';
  IteratorConstructor.prototype = create(IteratorPrototype, { next: createPropertyDescriptor(1, next) });
  setToStringTag(IteratorConstructor, TO_STRING_TAG, false, true);
  Iterators[TO_STRING_TAG] = returnThis;
  return IteratorConstructor;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js":
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js ***!
  \*****************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");
var createPropertyDescriptor = __webpack_require__(/*! ../internals/create-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js");

module.exports = DESCRIPTORS ? function (object, key, value) {
  return definePropertyModule.f(object, key, createPropertyDescriptor(1, value));
} : function (object, key, value) {
  object[key] = value;
  return object;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js":
/*!*************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js ***!
  \*************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = function (bitmap, value) {
  return {
    enumerable: !(bitmap & 1),
    configurable: !(bitmap & 2),
    writable: !(bitmap & 4),
    value: value
  };
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property.js":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property.js ***!
  \**************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var toPrimitive = __webpack_require__(/*! ../internals/to-primitive */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-primitive.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");
var createPropertyDescriptor = __webpack_require__(/*! ../internals/create-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js");

module.exports = function (object, key, value) {
  var propertyKey = toPrimitive(key);
  if (propertyKey in object) definePropertyModule.f(object, propertyKey, createPropertyDescriptor(0, value));
  else object[propertyKey] = value;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/define-iterator.js":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/define-iterator.js ***!
  \**************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js");
var createIteratorConstructor = __webpack_require__(/*! ../internals/create-iterator-constructor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-iterator-constructor.js");
var getPrototypeOf = __webpack_require__(/*! ../internals/object-get-prototype-of */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-prototype-of.js");
var setPrototypeOf = __webpack_require__(/*! ../internals/object-set-prototype-of */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-set-prototype-of.js");
var setToStringTag = __webpack_require__(/*! ../internals/set-to-string-tag */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-to-string-tag.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var redefine = __webpack_require__(/*! ../internals/redefine */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var IS_PURE = __webpack_require__(/*! ../internals/is-pure */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-pure.js");
var Iterators = __webpack_require__(/*! ../internals/iterators */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js");
var IteratorsCore = __webpack_require__(/*! ../internals/iterators-core */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators-core.js");

var IteratorPrototype = IteratorsCore.IteratorPrototype;
var BUGGY_SAFARI_ITERATORS = IteratorsCore.BUGGY_SAFARI_ITERATORS;
var ITERATOR = wellKnownSymbol('iterator');
var KEYS = 'keys';
var VALUES = 'values';
var ENTRIES = 'entries';

var returnThis = function () { return this; };

module.exports = function (Iterable, NAME, IteratorConstructor, next, DEFAULT, IS_SET, FORCED) {
  createIteratorConstructor(IteratorConstructor, NAME, next);

  var getIterationMethod = function (KIND) {
    if (KIND === DEFAULT && defaultIterator) return defaultIterator;
    if (!BUGGY_SAFARI_ITERATORS && KIND in IterablePrototype) return IterablePrototype[KIND];
    switch (KIND) {
      case KEYS: return function keys() { return new IteratorConstructor(this, KIND); };
      case VALUES: return function values() { return new IteratorConstructor(this, KIND); };
      case ENTRIES: return function entries() { return new IteratorConstructor(this, KIND); };
    } return function () { return new IteratorConstructor(this); };
  };

  var TO_STRING_TAG = NAME + ' Iterator';
  var INCORRECT_VALUES_NAME = false;
  var IterablePrototype = Iterable.prototype;
  var nativeIterator = IterablePrototype[ITERATOR]
    || IterablePrototype['@@iterator']
    || DEFAULT && IterablePrototype[DEFAULT];
  var defaultIterator = !BUGGY_SAFARI_ITERATORS && nativeIterator || getIterationMethod(DEFAULT);
  var anyNativeIterator = NAME == 'Array' ? IterablePrototype.entries || nativeIterator : nativeIterator;
  var CurrentIteratorPrototype, methods, KEY;

  // fix native
  if (anyNativeIterator) {
    CurrentIteratorPrototype = getPrototypeOf(anyNativeIterator.call(new Iterable()));
    if (IteratorPrototype !== Object.prototype && CurrentIteratorPrototype.next) {
      if (!IS_PURE && getPrototypeOf(CurrentIteratorPrototype) !== IteratorPrototype) {
        if (setPrototypeOf) {
          setPrototypeOf(CurrentIteratorPrototype, IteratorPrototype);
        } else if (typeof CurrentIteratorPrototype[ITERATOR] != 'function') {
          createNonEnumerableProperty(CurrentIteratorPrototype, ITERATOR, returnThis);
        }
      }
      // Set @@toStringTag to native iterators
      setToStringTag(CurrentIteratorPrototype, TO_STRING_TAG, true, true);
      if (IS_PURE) Iterators[TO_STRING_TAG] = returnThis;
    }
  }

  // fix Array#{values, @@iterator}.name in V8 / FF
  if (DEFAULT == VALUES && nativeIterator && nativeIterator.name !== VALUES) {
    INCORRECT_VALUES_NAME = true;
    defaultIterator = function values() { return nativeIterator.call(this); };
  }

  // define iterator
  if ((!IS_PURE || FORCED) && IterablePrototype[ITERATOR] !== defaultIterator) {
    createNonEnumerableProperty(IterablePrototype, ITERATOR, defaultIterator);
  }
  Iterators[NAME] = defaultIterator;

  // export additional methods
  if (DEFAULT) {
    methods = {
      values: getIterationMethod(VALUES),
      keys: IS_SET ? defaultIterator : getIterationMethod(KEYS),
      entries: getIterationMethod(ENTRIES)
    };
    if (FORCED) for (KEY in methods) {
      if (BUGGY_SAFARI_ITERATORS || INCORRECT_VALUES_NAME || !(KEY in IterablePrototype)) {
        redefine(IterablePrototype, KEY, methods[KEY]);
      }
    } else $({ target: NAME, proto: true, forced: BUGGY_SAFARI_ITERATORS || INCORRECT_VALUES_NAME }, methods);
  }

  return methods;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

// Thank's IE8 for his funny defineProperty
module.exports = !fails(function () {
  return Object.defineProperty({}, 1, { get: function () { return 7; } })[1] != 7;
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/document-create-element.js":
/*!**********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/document-create-element.js ***!
  \**********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");

var document = global.document;
// typeof document.createElement is 'object' in old IE
var EXISTS = isObject(document) && isObject(document.createElement);

module.exports = function (it) {
  return EXISTS ? document.createElement(it) : {};
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/dom-iterables.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/dom-iterables.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// iterable DOM collections
// flag - `iterable` interface - 'entries', 'keys', 'values', 'forEach' methods
module.exports = {
  CSSRuleList: 0,
  CSSStyleDeclaration: 0,
  CSSValueList: 0,
  ClientRectList: 0,
  DOMRectList: 0,
  DOMStringList: 0,
  DOMTokenList: 1,
  DataTransferItemList: 0,
  FileList: 0,
  HTMLAllCollection: 0,
  HTMLCollection: 0,
  HTMLFormElement: 0,
  HTMLSelectElement: 0,
  MediaList: 0,
  MimeTypeArray: 0,
  NamedNodeMap: 0,
  NodeList: 1,
  PaintRequestList: 0,
  Plugin: 0,
  PluginArray: 0,
  SVGLengthList: 0,
  SVGNumberList: 0,
  SVGPathSegList: 0,
  SVGPointList: 0,
  SVGStringList: 0,
  SVGTransformList: 0,
  SourceBufferList: 0,
  StyleSheetList: 0,
  TextTrackCueList: 0,
  TextTrackList: 0,
  TouchList: 0
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-user-agent.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-user-agent.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__(/*! ../internals/get-built-in */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js");

module.exports = getBuiltIn('navigator', 'userAgent') || '';


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-v8-version.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-v8-version.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var userAgent = __webpack_require__(/*! ../internals/engine-user-agent */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-user-agent.js");

var process = global.process;
var versions = process && process.versions;
var v8 = versions && versions.v8;
var match, version;

if (v8) {
  match = v8.split('.');
  version = match[0] + match[1];
} else if (userAgent) {
  match = userAgent.match(/Edge\/(\d+)/);
  if (!match || match[1] >= 74) {
    match = userAgent.match(/Chrome\/(\d+)/);
    if (match) version = match[1];
  }
}

module.exports = version && +version;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/enum-bug-keys.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/enum-bug-keys.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// IE8- don't enum bug keys
module.exports = [
  'constructor',
  'hasOwnProperty',
  'isPrototypeOf',
  'propertyIsEnumerable',
  'toLocaleString',
  'toString',
  'valueOf'
];


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js":
/*!*****************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js ***!
  \*****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var getOwnPropertyDescriptor = __webpack_require__(/*! ../internals/object-get-own-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-descriptor.js").f;
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var redefine = __webpack_require__(/*! ../internals/redefine */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js");
var setGlobal = __webpack_require__(/*! ../internals/set-global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-global.js");
var copyConstructorProperties = __webpack_require__(/*! ../internals/copy-constructor-properties */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/copy-constructor-properties.js");
var isForced = __webpack_require__(/*! ../internals/is-forced */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-forced.js");

/*
  options.target      - name of the target object
  options.global      - target is the global object
  options.stat        - export as static methods of target
  options.proto       - export as prototype methods of target
  options.real        - real prototype method for the `pure` version
  options.forced      - export even if the native feature is available
  options.bind        - bind methods to the target, required for the `pure` version
  options.wrap        - wrap constructors to preventing global pollution, required for the `pure` version
  options.unsafe      - use the simple assignment of property instead of delete + defineProperty
  options.sham        - add a flag to not completely full polyfills
  options.enumerable  - export as enumerable property
  options.noTargetGet - prevent calling a getter on target
*/
module.exports = function (options, source) {
  var TARGET = options.target;
  var GLOBAL = options.global;
  var STATIC = options.stat;
  var FORCED, target, key, targetProperty, sourceProperty, descriptor;
  if (GLOBAL) {
    target = global;
  } else if (STATIC) {
    target = global[TARGET] || setGlobal(TARGET, {});
  } else {
    target = (global[TARGET] || {}).prototype;
  }
  if (target) for (key in source) {
    sourceProperty = source[key];
    if (options.noTargetGet) {
      descriptor = getOwnPropertyDescriptor(target, key);
      targetProperty = descriptor && descriptor.value;
    } else targetProperty = target[key];
    FORCED = isForced(GLOBAL ? key : TARGET + (STATIC ? '.' : '#') + key, options.forced);
    // contained in target
    if (!FORCED && targetProperty !== undefined) {
      if (typeof sourceProperty === typeof targetProperty) continue;
      copyConstructorProperties(sourceProperty, targetProperty);
    }
    // add a flag to not completely full polyfills
    if (options.sham || (targetProperty && targetProperty.sham)) {
      createNonEnumerableProperty(sourceProperty, 'sham', true);
    }
    // extend global
    redefine(target, key, sourceProperty, options);
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js":
/*!****************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js ***!
  \****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = function (exec) {
  try {
    return !!exec();
  } catch (error) {
    return true;
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/freezing.js":
/*!*******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/freezing.js ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

module.exports = !fails(function () {
  return Object.isExtensible(Object.preventExtensions({}));
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/function-bind-context.js":
/*!********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/function-bind-context.js ***!
  \********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var aFunction = __webpack_require__(/*! ../internals/a-function */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-function.js");

// optional / simple context binding
module.exports = function (fn, that, length) {
  aFunction(fn);
  if (that === undefined) return fn;
  switch (length) {
    case 0: return function () {
      return fn.call(that);
    };
    case 1: return function (a) {
      return fn.call(that, a);
    };
    case 2: return function (a, b) {
      return fn.call(that, a, b);
    };
    case 3: return function (a, b, c) {
      return fn.call(that, a, b, c);
    };
  }
  return function (/* ...args */) {
    return fn.apply(that, arguments);
  };
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js":
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var path = __webpack_require__(/*! ../internals/path */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/path.js");
var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");

var aFunction = function (variable) {
  return typeof variable == 'function' ? variable : undefined;
};

module.exports = function (namespace, method) {
  return arguments.length < 2 ? aFunction(path[namespace]) || aFunction(global[namespace])
    : path[namespace] && path[namespace][method] || global[namespace] && global[namespace][method];
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-iterator-method.js":
/*!******************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-iterator-method.js ***!
  \******************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var classof = __webpack_require__(/*! ../internals/classof */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof.js");
var Iterators = __webpack_require__(/*! ../internals/iterators */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var ITERATOR = wellKnownSymbol('iterator');

module.exports = function (it) {
  if (it != undefined) return it[ITERATOR]
    || it['@@iterator']
    || Iterators[classof(it)];
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js":
/*!*****************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js ***!
  \*****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {var check = function (it) {
  return it && it.Math == Math && it;
};

// https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
module.exports =
  // eslint-disable-next-line no-undef
  check(typeof globalThis == 'object' && globalThis) ||
  check(typeof window == 'object' && window) ||
  check(typeof self == 'object' && self) ||
  check(typeof global == 'object' && global) ||
  // eslint-disable-next-line no-new-func
  Function('return this')();

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../../../../../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js":
/*!**************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js ***!
  \**************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var hasOwnProperty = {}.hasOwnProperty;

module.exports = function (it, key) {
  return hasOwnProperty.call(it, key);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = {};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/html.js":
/*!***************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/html.js ***!
  \***************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__(/*! ../internals/get-built-in */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js");

module.exports = getBuiltIn('document', 'documentElement');


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/ie8-dom-define.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/ie8-dom-define.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var createElement = __webpack_require__(/*! ../internals/document-create-element */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/document-create-element.js");

// Thank's IE8 for his funny defineProperty
module.exports = !DESCRIPTORS && !fails(function () {
  return Object.defineProperty(createElement('div'), 'a', {
    get: function () { return 7; }
  }).a != 7;
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/indexed-object.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/indexed-object.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var classof = __webpack_require__(/*! ../internals/classof-raw */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof-raw.js");

var split = ''.split;

// fallback for non-array-like ES3 and non-enumerable old V8 strings
module.exports = fails(function () {
  // throws an error in rhino, see https://github.com/mozilla/rhino/issues/346
  // eslint-disable-next-line no-prototype-builtins
  return !Object('z').propertyIsEnumerable(0);
}) ? function (it) {
  return classof(it) == 'String' ? split.call(it, '') : Object(it);
} : Object;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inherit-if-required.js":
/*!******************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inherit-if-required.js ***!
  \******************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var setPrototypeOf = __webpack_require__(/*! ../internals/object-set-prototype-of */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-set-prototype-of.js");

// makes subclassing work correct for wrapped built-ins
module.exports = function ($this, dummy, Wrapper) {
  var NewTarget, NewTargetPrototype;
  if (
    // it can work only with native `setPrototypeOf`
    setPrototypeOf &&
    // we haven't completely correct pre-ES6 way for getting `new.target`, so use this
    typeof (NewTarget = dummy.constructor) == 'function' &&
    NewTarget !== Wrapper &&
    isObject(NewTargetPrototype = NewTarget.prototype) &&
    NewTargetPrototype !== Wrapper.prototype
  ) setPrototypeOf($this, NewTargetPrototype);
  return $this;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inspect-source.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inspect-source.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var store = __webpack_require__(/*! ../internals/shared-store */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-store.js");

var functionToString = Function.toString;

// this helper broken in `3.4.1-3.4.4`, so we can't use `shared` helper
if (typeof store.inspectSource != 'function') {
  store.inspectSource = function (it) {
    return functionToString.call(it);
  };
}

module.exports = store.inspectSource;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-metadata.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-metadata.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var hiddenKeys = __webpack_require__(/*! ../internals/hidden-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var defineProperty = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js").f;
var uid = __webpack_require__(/*! ../internals/uid */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/uid.js");
var FREEZING = __webpack_require__(/*! ../internals/freezing */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/freezing.js");

var METADATA = uid('meta');
var id = 0;

var isExtensible = Object.isExtensible || function () {
  return true;
};

var setMetadata = function (it) {
  defineProperty(it, METADATA, { value: {
    objectID: 'O' + ++id, // object ID
    weakData: {}          // weak collections IDs
  } });
};

var fastKey = function (it, create) {
  // return a primitive with prefix
  if (!isObject(it)) return typeof it == 'symbol' ? it : (typeof it == 'string' ? 'S' : 'P') + it;
  if (!has(it, METADATA)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return 'F';
    // not necessary to add metadata
    if (!create) return 'E';
    // add missing metadata
    setMetadata(it);
  // return object ID
  } return it[METADATA].objectID;
};

var getWeakData = function (it, create) {
  if (!has(it, METADATA)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return true;
    // not necessary to add metadata
    if (!create) return false;
    // add missing metadata
    setMetadata(it);
  // return the store of weak collections IDs
  } return it[METADATA].weakData;
};

// add metadata on freeze-family methods calling
var onFreeze = function (it) {
  if (FREEZING && meta.REQUIRED && isExtensible(it) && !has(it, METADATA)) setMetadata(it);
  return it;
};

var meta = module.exports = {
  REQUIRED: false,
  fastKey: fastKey,
  getWeakData: getWeakData,
  onFreeze: onFreeze
};

hiddenKeys[METADATA] = true;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var NATIVE_WEAK_MAP = __webpack_require__(/*! ../internals/native-weak-map */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-weak-map.js");
var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var objectHas = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var sharedKey = __webpack_require__(/*! ../internals/shared-key */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-key.js");
var hiddenKeys = __webpack_require__(/*! ../internals/hidden-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js");

var WeakMap = global.WeakMap;
var set, get, has;

var enforce = function (it) {
  return has(it) ? get(it) : set(it, {});
};

var getterFor = function (TYPE) {
  return function (it) {
    var state;
    if (!isObject(it) || (state = get(it)).type !== TYPE) {
      throw TypeError('Incompatible receiver, ' + TYPE + ' required');
    } return state;
  };
};

if (NATIVE_WEAK_MAP) {
  var store = new WeakMap();
  var wmget = store.get;
  var wmhas = store.has;
  var wmset = store.set;
  set = function (it, metadata) {
    wmset.call(store, it, metadata);
    return metadata;
  };
  get = function (it) {
    return wmget.call(store, it) || {};
  };
  has = function (it) {
    return wmhas.call(store, it);
  };
} else {
  var STATE = sharedKey('state');
  hiddenKeys[STATE] = true;
  set = function (it, metadata) {
    createNonEnumerableProperty(it, STATE, metadata);
    return metadata;
  };
  get = function (it) {
    return objectHas(it, STATE) ? it[STATE] : {};
  };
  has = function (it) {
    return objectHas(it, STATE);
  };
}

module.exports = {
  set: set,
  get: get,
  has: has,
  enforce: enforce,
  getterFor: getterFor
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array-iterator-method.js":
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array-iterator-method.js ***!
  \***********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var Iterators = __webpack_require__(/*! ../internals/iterators */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js");

var ITERATOR = wellKnownSymbol('iterator');
var ArrayPrototype = Array.prototype;

// check on default Array iterator
module.exports = function (it) {
  return it !== undefined && (Iterators.Array === it || ArrayPrototype[ITERATOR] === it);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array.js":
/*!*******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array.js ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var classof = __webpack_require__(/*! ../internals/classof-raw */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof-raw.js");

// `IsArray` abstract operation
// https://tc39.github.io/ecma262/#sec-isarray
module.exports = Array.isArray || function isArray(arg) {
  return classof(arg) == 'Array';
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-forced.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-forced.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

var replacement = /#|\.prototype\./;

var isForced = function (feature, detection) {
  var value = data[normalize(feature)];
  return value == POLYFILL ? true
    : value == NATIVE ? false
    : typeof detection == 'function' ? fails(detection)
    : !!detection;
};

var normalize = isForced.normalize = function (string) {
  return String(string).replace(replacement, '.').toLowerCase();
};

var data = isForced.data = {};
var NATIVE = isForced.NATIVE = 'N';
var POLYFILL = isForced.POLYFILL = 'P';

module.exports = isForced;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = function (it) {
  return typeof it === 'object' ? it !== null : typeof it === 'function';
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-pure.js":
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-pure.js ***!
  \******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = false;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterate.js":
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterate.js ***!
  \******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");
var isArrayIteratorMethod = __webpack_require__(/*! ../internals/is-array-iterator-method */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array-iterator-method.js");
var toLength = __webpack_require__(/*! ../internals/to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js");
var bind = __webpack_require__(/*! ../internals/function-bind-context */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/function-bind-context.js");
var getIteratorMethod = __webpack_require__(/*! ../internals/get-iterator-method */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-iterator-method.js");
var callWithSafeIterationClosing = __webpack_require__(/*! ../internals/call-with-safe-iteration-closing */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/call-with-safe-iteration-closing.js");

var Result = function (stopped, result) {
  this.stopped = stopped;
  this.result = result;
};

var iterate = module.exports = function (iterable, fn, that, AS_ENTRIES, IS_ITERATOR) {
  var boundFunction = bind(fn, that, AS_ENTRIES ? 2 : 1);
  var iterator, iterFn, index, length, result, next, step;

  if (IS_ITERATOR) {
    iterator = iterable;
  } else {
    iterFn = getIteratorMethod(iterable);
    if (typeof iterFn != 'function') throw TypeError('Target is not iterable');
    // optimisation for array iterators
    if (isArrayIteratorMethod(iterFn)) {
      for (index = 0, length = toLength(iterable.length); length > index; index++) {
        result = AS_ENTRIES
          ? boundFunction(anObject(step = iterable[index])[0], step[1])
          : boundFunction(iterable[index]);
        if (result && result instanceof Result) return result;
      } return new Result(false);
    }
    iterator = iterFn.call(iterable);
  }

  next = iterator.next;
  while (!(step = next.call(iterator)).done) {
    result = callWithSafeIterationClosing(iterator, boundFunction, step.value, AS_ENTRIES);
    if (typeof result == 'object' && result && result instanceof Result) return result;
  } return new Result(false);
};

iterate.stop = function (result) {
  return new Result(true, result);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators-core.js":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators-core.js ***!
  \*************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var getPrototypeOf = __webpack_require__(/*! ../internals/object-get-prototype-of */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-prototype-of.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var IS_PURE = __webpack_require__(/*! ../internals/is-pure */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-pure.js");

var ITERATOR = wellKnownSymbol('iterator');
var BUGGY_SAFARI_ITERATORS = false;

var returnThis = function () { return this; };

// `%IteratorPrototype%` object
// https://tc39.github.io/ecma262/#sec-%iteratorprototype%-object
var IteratorPrototype, PrototypeOfArrayIteratorPrototype, arrayIterator;

if ([].keys) {
  arrayIterator = [].keys();
  // Safari 8 has buggy iterators w/o `next`
  if (!('next' in arrayIterator)) BUGGY_SAFARI_ITERATORS = true;
  else {
    PrototypeOfArrayIteratorPrototype = getPrototypeOf(getPrototypeOf(arrayIterator));
    if (PrototypeOfArrayIteratorPrototype !== Object.prototype) IteratorPrototype = PrototypeOfArrayIteratorPrototype;
  }
}

if (IteratorPrototype == undefined) IteratorPrototype = {};

// 25.1.2.1.1 %IteratorPrototype%[@@iterator]()
if (!IS_PURE && !has(IteratorPrototype, ITERATOR)) {
  createNonEnumerableProperty(IteratorPrototype, ITERATOR, returnThis);
}

module.exports = {
  IteratorPrototype: IteratorPrototype,
  BUGGY_SAFARI_ITERATORS: BUGGY_SAFARI_ITERATORS
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = {};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-symbol.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-symbol.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");

module.exports = !!Object.getOwnPropertySymbols && !fails(function () {
  // Chrome 38 Symbol has incorrect toString conversion
  // eslint-disable-next-line no-undef
  return !String(Symbol());
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-weak-map.js":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-weak-map.js ***!
  \**************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var inspectSource = __webpack_require__(/*! ../internals/inspect-source */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inspect-source.js");

var WeakMap = global.WeakMap;

module.exports = typeof WeakMap === 'function' && /native code/.test(inspectSource(WeakMap));


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-create.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-create.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");
var defineProperties = __webpack_require__(/*! ../internals/object-define-properties */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-properties.js");
var enumBugKeys = __webpack_require__(/*! ../internals/enum-bug-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/enum-bug-keys.js");
var hiddenKeys = __webpack_require__(/*! ../internals/hidden-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js");
var html = __webpack_require__(/*! ../internals/html */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/html.js");
var documentCreateElement = __webpack_require__(/*! ../internals/document-create-element */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/document-create-element.js");
var sharedKey = __webpack_require__(/*! ../internals/shared-key */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-key.js");

var GT = '>';
var LT = '<';
var PROTOTYPE = 'prototype';
var SCRIPT = 'script';
var IE_PROTO = sharedKey('IE_PROTO');

var EmptyConstructor = function () { /* empty */ };

var scriptTag = function (content) {
  return LT + SCRIPT + GT + content + LT + '/' + SCRIPT + GT;
};

// Create object with fake `null` prototype: use ActiveX Object with cleared prototype
var NullProtoObjectViaActiveX = function (activeXDocument) {
  activeXDocument.write(scriptTag(''));
  activeXDocument.close();
  var temp = activeXDocument.parentWindow.Object;
  activeXDocument = null; // avoid memory leak
  return temp;
};

// Create object with fake `null` prototype: use iframe Object with cleared prototype
var NullProtoObjectViaIFrame = function () {
  // Thrash, waste and sodomy: IE GC bug
  var iframe = documentCreateElement('iframe');
  var JS = 'java' + SCRIPT + ':';
  var iframeDocument;
  iframe.style.display = 'none';
  html.appendChild(iframe);
  // https://github.com/zloirock/core-js/issues/475
  iframe.src = String(JS);
  iframeDocument = iframe.contentWindow.document;
  iframeDocument.open();
  iframeDocument.write(scriptTag('document.F=Object'));
  iframeDocument.close();
  return iframeDocument.F;
};

// Check for document.domain and active x support
// No need to use active x approach when document.domain is not set
// see https://github.com/es-shims/es5-shim/issues/150
// variation of https://github.com/kitcambridge/es5-shim/commit/4f738ac066346
// avoid IE GC bug
var activeXDocument;
var NullProtoObject = function () {
  try {
    /* global ActiveXObject */
    activeXDocument = document.domain && new ActiveXObject('htmlfile');
  } catch (error) { /* ignore */ }
  NullProtoObject = activeXDocument ? NullProtoObjectViaActiveX(activeXDocument) : NullProtoObjectViaIFrame();
  var length = enumBugKeys.length;
  while (length--) delete NullProtoObject[PROTOTYPE][enumBugKeys[length]];
  return NullProtoObject();
};

hiddenKeys[IE_PROTO] = true;

// `Object.create` method
// https://tc39.github.io/ecma262/#sec-object.create
module.exports = Object.create || function create(O, Properties) {
  var result;
  if (O !== null) {
    EmptyConstructor[PROTOTYPE] = anObject(O);
    result = new EmptyConstructor();
    EmptyConstructor[PROTOTYPE] = null;
    // add "__proto__" for Object.getPrototypeOf polyfill
    result[IE_PROTO] = O;
  } else result = NullProtoObject();
  return Properties === undefined ? result : defineProperties(result, Properties);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-properties.js":
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-properties.js ***!
  \***********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");
var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");
var objectKeys = __webpack_require__(/*! ../internals/object-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys.js");

// `Object.defineProperties` method
// https://tc39.github.io/ecma262/#sec-object.defineproperties
module.exports = DESCRIPTORS ? Object.defineProperties : function defineProperties(O, Properties) {
  anObject(O);
  var keys = objectKeys(Properties);
  var length = keys.length;
  var index = 0;
  var key;
  while (length > index) definePropertyModule.f(O, key = keys[index++], Properties[key]);
  return O;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js":
/*!*********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js ***!
  \*********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var IE8_DOM_DEFINE = __webpack_require__(/*! ../internals/ie8-dom-define */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/ie8-dom-define.js");
var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");
var toPrimitive = __webpack_require__(/*! ../internals/to-primitive */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-primitive.js");

var nativeDefineProperty = Object.defineProperty;

// `Object.defineProperty` method
// https://tc39.github.io/ecma262/#sec-object.defineproperty
exports.f = DESCRIPTORS ? nativeDefineProperty : function defineProperty(O, P, Attributes) {
  anObject(O);
  P = toPrimitive(P, true);
  anObject(Attributes);
  if (IE8_DOM_DEFINE) try {
    return nativeDefineProperty(O, P, Attributes);
  } catch (error) { /* empty */ }
  if ('get' in Attributes || 'set' in Attributes) throw TypeError('Accessors not supported');
  if ('value' in Attributes) O[P] = Attributes.value;
  return O;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-descriptor.js":
/*!*********************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-descriptor.js ***!
  \*********************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");
var propertyIsEnumerableModule = __webpack_require__(/*! ../internals/object-property-is-enumerable */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-property-is-enumerable.js");
var createPropertyDescriptor = __webpack_require__(/*! ../internals/create-property-descriptor */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property-descriptor.js");
var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js");
var toPrimitive = __webpack_require__(/*! ../internals/to-primitive */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-primitive.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var IE8_DOM_DEFINE = __webpack_require__(/*! ../internals/ie8-dom-define */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/ie8-dom-define.js");

var nativeGetOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;

// `Object.getOwnPropertyDescriptor` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertydescriptor
exports.f = DESCRIPTORS ? nativeGetOwnPropertyDescriptor : function getOwnPropertyDescriptor(O, P) {
  O = toIndexedObject(O);
  P = toPrimitive(P, true);
  if (IE8_DOM_DEFINE) try {
    return nativeGetOwnPropertyDescriptor(O, P);
  } catch (error) { /* empty */ }
  if (has(O, P)) return createPropertyDescriptor(!propertyIsEnumerableModule.f.call(O, P), O[P]);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-names.js":
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-names.js ***!
  \****************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var internalObjectKeys = __webpack_require__(/*! ../internals/object-keys-internal */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys-internal.js");
var enumBugKeys = __webpack_require__(/*! ../internals/enum-bug-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/enum-bug-keys.js");

var hiddenKeys = enumBugKeys.concat('length', 'prototype');

// `Object.getOwnPropertyNames` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertynames
exports.f = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
  return internalObjectKeys(O, hiddenKeys);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-symbols.js":
/*!******************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-symbols.js ***!
  \******************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

exports.f = Object.getOwnPropertySymbols;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-prototype-of.js":
/*!**********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-prototype-of.js ***!
  \**********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var toObject = __webpack_require__(/*! ../internals/to-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-object.js");
var sharedKey = __webpack_require__(/*! ../internals/shared-key */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-key.js");
var CORRECT_PROTOTYPE_GETTER = __webpack_require__(/*! ../internals/correct-prototype-getter */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/correct-prototype-getter.js");

var IE_PROTO = sharedKey('IE_PROTO');
var ObjectPrototype = Object.prototype;

// `Object.getPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.getprototypeof
module.exports = CORRECT_PROTOTYPE_GETTER ? Object.getPrototypeOf : function (O) {
  O = toObject(O);
  if (has(O, IE_PROTO)) return O[IE_PROTO];
  if (typeof O.constructor == 'function' && O instanceof O.constructor) {
    return O.constructor.prototype;
  } return O instanceof Object ? ObjectPrototype : null;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys-internal.js":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys-internal.js ***!
  \*******************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js");
var indexOf = __webpack_require__(/*! ../internals/array-includes */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-includes.js").indexOf;
var hiddenKeys = __webpack_require__(/*! ../internals/hidden-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/hidden-keys.js");

module.exports = function (object, names) {
  var O = toIndexedObject(object);
  var i = 0;
  var result = [];
  var key;
  for (key in O) !has(hiddenKeys, key) && has(O, key) && result.push(key);
  // Don't enum bug & hidden keys
  while (names.length > i) if (has(O, key = names[i++])) {
    ~indexOf(result, key) || result.push(key);
  }
  return result;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var internalObjectKeys = __webpack_require__(/*! ../internals/object-keys-internal */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-keys-internal.js");
var enumBugKeys = __webpack_require__(/*! ../internals/enum-bug-keys */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/enum-bug-keys.js");

// `Object.keys` method
// https://tc39.github.io/ecma262/#sec-object.keys
module.exports = Object.keys || function keys(O) {
  return internalObjectKeys(O, enumBugKeys);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-property-is-enumerable.js":
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-property-is-enumerable.js ***!
  \****************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var nativePropertyIsEnumerable = {}.propertyIsEnumerable;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;

// Nashorn ~ JDK8 bug
var NASHORN_BUG = getOwnPropertyDescriptor && !nativePropertyIsEnumerable.call({ 1: 2 }, 1);

// `Object.prototype.propertyIsEnumerable` method implementation
// https://tc39.github.io/ecma262/#sec-object.prototype.propertyisenumerable
exports.f = NASHORN_BUG ? function propertyIsEnumerable(V) {
  var descriptor = getOwnPropertyDescriptor(this, V);
  return !!descriptor && descriptor.enumerable;
} : nativePropertyIsEnumerable;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-set-prototype-of.js":
/*!**********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-set-prototype-of.js ***!
  \**********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");
var aPossiblePrototype = __webpack_require__(/*! ../internals/a-possible-prototype */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/a-possible-prototype.js");

// `Object.setPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.setprototypeof
// Works with __proto__ only. Old v8 can't work with null proto objects.
/* eslint-disable no-proto */
module.exports = Object.setPrototypeOf || ('__proto__' in {} ? function () {
  var CORRECT_SETTER = false;
  var test = {};
  var setter;
  try {
    setter = Object.getOwnPropertyDescriptor(Object.prototype, '__proto__').set;
    setter.call(test, []);
    CORRECT_SETTER = test instanceof Array;
  } catch (error) { /* empty */ }
  return function setPrototypeOf(O, proto) {
    anObject(O);
    aPossiblePrototype(proto);
    if (CORRECT_SETTER) setter.call(O, proto);
    else O.__proto__ = proto;
    return O;
  };
}() : undefined);


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-to-string.js":
/*!***************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-to-string.js ***!
  \***************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var TO_STRING_TAG_SUPPORT = __webpack_require__(/*! ../internals/to-string-tag-support */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-string-tag-support.js");
var classof = __webpack_require__(/*! ../internals/classof */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/classof.js");

// `Object.prototype.toString` method implementation
// https://tc39.github.io/ecma262/#sec-object.prototype.tostring
module.exports = TO_STRING_TAG_SUPPORT ? {}.toString : function toString() {
  return '[object ' + classof(this) + ']';
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/own-keys.js":
/*!*******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/own-keys.js ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__(/*! ../internals/get-built-in */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js");
var getOwnPropertyNamesModule = __webpack_require__(/*! ../internals/object-get-own-property-names */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-names.js");
var getOwnPropertySymbolsModule = __webpack_require__(/*! ../internals/object-get-own-property-symbols */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-get-own-property-symbols.js");
var anObject = __webpack_require__(/*! ../internals/an-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/an-object.js");

// all object keys, includes non-enumerable and symbols
module.exports = getBuiltIn('Reflect', 'ownKeys') || function ownKeys(it) {
  var keys = getOwnPropertyNamesModule.f(anObject(it));
  var getOwnPropertySymbols = getOwnPropertySymbolsModule.f;
  return getOwnPropertySymbols ? keys.concat(getOwnPropertySymbols(it)) : keys;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/path.js":
/*!***************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/path.js ***!
  \***************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");

module.exports = global;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine-all.js":
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine-all.js ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var redefine = __webpack_require__(/*! ../internals/redefine */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js");

module.exports = function (target, src, options) {
  for (var key in src) redefine(target, key, src[key], options);
  return target;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js":
/*!*******************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var setGlobal = __webpack_require__(/*! ../internals/set-global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-global.js");
var inspectSource = __webpack_require__(/*! ../internals/inspect-source */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/inspect-source.js");
var InternalStateModule = __webpack_require__(/*! ../internals/internal-state */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js");

var getInternalState = InternalStateModule.get;
var enforceInternalState = InternalStateModule.enforce;
var TEMPLATE = String(String).split('String');

(module.exports = function (O, key, value, options) {
  var unsafe = options ? !!options.unsafe : false;
  var simple = options ? !!options.enumerable : false;
  var noTargetGet = options ? !!options.noTargetGet : false;
  if (typeof value == 'function') {
    if (typeof key == 'string' && !has(value, 'name')) createNonEnumerableProperty(value, 'name', key);
    enforceInternalState(value).source = TEMPLATE.join(typeof key == 'string' ? key : '');
  }
  if (O === global) {
    if (simple) O[key] = value;
    else setGlobal(key, value);
    return;
  } else if (!unsafe) {
    delete O[key];
  } else if (!noTargetGet && O[key]) {
    simple = true;
  }
  if (simple) O[key] = value;
  else createNonEnumerableProperty(O, key, value);
// add fake Function#toString for correct work wrapped methods / constructors with methods like LoDash isNative
})(Function.prototype, 'toString', function toString() {
  return typeof this == 'function' && getInternalState(this).source || inspectSource(this);
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/require-object-coercible.js":
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/require-object-coercible.js ***!
  \***********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// `RequireObjectCoercible` abstract operation
// https://tc39.github.io/ecma262/#sec-requireobjectcoercible
module.exports = function (it) {
  if (it == undefined) throw TypeError("Can't call method on " + it);
  return it;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-global.js":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-global.js ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");

module.exports = function (key, value) {
  try {
    createNonEnumerableProperty(global, key, value);
  } catch (error) {
    global[key] = value;
  } return value;
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-species.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-species.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var getBuiltIn = __webpack_require__(/*! ../internals/get-built-in */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/get-built-in.js");
var definePropertyModule = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/descriptors.js");

var SPECIES = wellKnownSymbol('species');

module.exports = function (CONSTRUCTOR_NAME) {
  var Constructor = getBuiltIn(CONSTRUCTOR_NAME);
  var defineProperty = definePropertyModule.f;

  if (DESCRIPTORS && Constructor && !Constructor[SPECIES]) {
    defineProperty(Constructor, SPECIES, {
      configurable: true,
      get: function () { return this; }
    });
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-to-string-tag.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-to-string-tag.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var defineProperty = __webpack_require__(/*! ../internals/object-define-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-define-property.js").f;
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');

module.exports = function (it, TAG, STATIC) {
  if (it && !has(it = STATIC ? it : it.prototype, TO_STRING_TAG)) {
    defineProperty(it, TO_STRING_TAG, { configurable: true, value: TAG });
  }
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-key.js":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-key.js ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var shared = __webpack_require__(/*! ../internals/shared */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared.js");
var uid = __webpack_require__(/*! ../internals/uid */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/uid.js");

var keys = shared('keys');

module.exports = function (key) {
  return keys[key] || (keys[key] = uid(key));
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-store.js":
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-store.js ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var setGlobal = __webpack_require__(/*! ../internals/set-global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/set-global.js");

var SHARED = '__core-js_shared__';
var store = global[SHARED] || setGlobal(SHARED, {});

module.exports = store;


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared.js":
/*!*****************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared.js ***!
  \*****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var IS_PURE = __webpack_require__(/*! ../internals/is-pure */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-pure.js");
var store = __webpack_require__(/*! ../internals/shared-store */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared-store.js");

(module.exports = function (key, value) {
  return store[key] || (store[key] = value !== undefined ? value : {});
})('versions', []).push({
  version: '3.6.4',
  mode: IS_PURE ? 'pure' : 'global',
  copyright: '© 2020 Denis Pushkarev (zloirock.ru)'
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/string-multibyte.js":
/*!***************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/string-multibyte.js ***!
  \***************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__(/*! ../internals/to-integer */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-integer.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/require-object-coercible.js");

// `String.prototype.{ codePointAt, at }` methods implementation
var createMethod = function (CONVERT_TO_STRING) {
  return function ($this, pos) {
    var S = String(requireObjectCoercible($this));
    var position = toInteger(pos);
    var size = S.length;
    var first, second;
    if (position < 0 || position >= size) return CONVERT_TO_STRING ? '' : undefined;
    first = S.charCodeAt(position);
    return first < 0xD800 || first > 0xDBFF || position + 1 === size
      || (second = S.charCodeAt(position + 1)) < 0xDC00 || second > 0xDFFF
        ? CONVERT_TO_STRING ? S.charAt(position) : first
        : CONVERT_TO_STRING ? S.slice(position, position + 2) : (first - 0xD800 << 10) + (second - 0xDC00) + 0x10000;
  };
};

module.exports = {
  // `String.prototype.codePointAt` method
  // https://tc39.github.io/ecma262/#sec-string.prototype.codepointat
  codeAt: createMethod(false),
  // `String.prototype.at` method
  // https://github.com/mathiasbynens/String.prototype.at
  charAt: createMethod(true)
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-absolute-index.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-absolute-index.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__(/*! ../internals/to-integer */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-integer.js");

var max = Math.max;
var min = Math.min;

// Helper for a popular repeating case of the spec:
// Let integer be ? ToInteger(index).
// If integer < 0, let result be max((length + integer), 0); else let result be min(integer, length).
module.exports = function (index, length) {
  var integer = toInteger(index);
  return integer < 0 ? max(integer + length, 0) : min(integer, length);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// toObject with fallback for non-array-like ES3 strings
var IndexedObject = __webpack_require__(/*! ../internals/indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/indexed-object.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/require-object-coercible.js");

module.exports = function (it) {
  return IndexedObject(requireObjectCoercible(it));
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-integer.js":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-integer.js ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var ceil = Math.ceil;
var floor = Math.floor;

// `ToInteger` abstract operation
// https://tc39.github.io/ecma262/#sec-tointeger
module.exports = function (argument) {
  return isNaN(argument = +argument) ? 0 : (argument > 0 ? floor : ceil)(argument);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__(/*! ../internals/to-integer */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-integer.js");

var min = Math.min;

// `ToLength` abstract operation
// https://tc39.github.io/ecma262/#sec-tolength
module.exports = function (argument) {
  return argument > 0 ? min(toInteger(argument), 0x1FFFFFFFFFFFFF) : 0; // 2 ** 53 - 1 == 9007199254740991
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-object.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-object.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/require-object-coercible.js");

// `ToObject` abstract operation
// https://tc39.github.io/ecma262/#sec-toobject
module.exports = function (argument) {
  return Object(requireObjectCoercible(argument));
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-primitive.js":
/*!***********************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-primitive.js ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");

// `ToPrimitive` abstract operation
// https://tc39.github.io/ecma262/#sec-toprimitive
// instead of the ES6 spec version, we didn't implement @@toPrimitive case
// and the second argument - flag - preferred type is a string
module.exports = function (input, PREFERRED_STRING) {
  if (!isObject(input)) return input;
  var fn, val;
  if (PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
  if (typeof (fn = input.valueOf) == 'function' && !isObject(val = fn.call(input))) return val;
  if (!PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
  throw TypeError("Can't convert object to primitive value");
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-string-tag-support.js":
/*!********************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-string-tag-support.js ***!
  \********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');
var test = {};

test[TO_STRING_TAG] = 'z';

module.exports = String(test) === '[object z]';


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/uid.js":
/*!**************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/uid.js ***!
  \**************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var id = 0;
var postfix = Math.random();

module.exports = function (key) {
  return 'Symbol(' + String(key === undefined ? '' : key) + ')_' + (++id + postfix).toString(36);
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/use-symbol-as-uid.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/use-symbol-as-uid.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var NATIVE_SYMBOL = __webpack_require__(/*! ../internals/native-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-symbol.js");

module.exports = NATIVE_SYMBOL
  // eslint-disable-next-line no-undef
  && !Symbol.sham
  // eslint-disable-next-line no-undef
  && typeof Symbol.iterator == 'symbol';


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var shared = __webpack_require__(/*! ../internals/shared */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/shared.js");
var has = __webpack_require__(/*! ../internals/has */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/has.js");
var uid = __webpack_require__(/*! ../internals/uid */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/uid.js");
var NATIVE_SYMBOL = __webpack_require__(/*! ../internals/native-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/native-symbol.js");
var USE_SYMBOL_AS_UID = __webpack_require__(/*! ../internals/use-symbol-as-uid */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/use-symbol-as-uid.js");

var WellKnownSymbolsStore = shared('wks');
var Symbol = global.Symbol;
var createWellKnownSymbol = USE_SYMBOL_AS_UID ? Symbol : Symbol && Symbol.withoutSetter || uid;

module.exports = function (name) {
  if (!has(WellKnownSymbolsStore, name)) {
    if (NATIVE_SYMBOL && has(Symbol, name)) WellKnownSymbolsStore[name] = Symbol[name];
    else WellKnownSymbolsStore[name] = createWellKnownSymbol('Symbol.' + name);
  } return WellKnownSymbolsStore[name];
};


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.concat.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.concat.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/fails.js");
var isArray = __webpack_require__(/*! ../internals/is-array */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-array.js");
var isObject = __webpack_require__(/*! ../internals/is-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/is-object.js");
var toObject = __webpack_require__(/*! ../internals/to-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-object.js");
var toLength = __webpack_require__(/*! ../internals/to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-length.js");
var createProperty = __webpack_require__(/*! ../internals/create-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-property.js");
var arraySpeciesCreate = __webpack_require__(/*! ../internals/array-species-create */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-species-create.js");
var arrayMethodHasSpeciesSupport = __webpack_require__(/*! ../internals/array-method-has-species-support */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-has-species-support.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__(/*! ../internals/engine-v8-version */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/engine-v8-version.js");

var IS_CONCAT_SPREADABLE = wellKnownSymbol('isConcatSpreadable');
var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF;
var MAXIMUM_ALLOWED_INDEX_EXCEEDED = 'Maximum allowed index exceeded';

// We can't use this feature detection in V8 since it causes
// deoptimization and serious performance degradation
// https://github.com/zloirock/core-js/issues/679
var IS_CONCAT_SPREADABLE_SUPPORT = V8_VERSION >= 51 || !fails(function () {
  var array = [];
  array[IS_CONCAT_SPREADABLE] = false;
  return array.concat()[0] !== array;
});

var SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('concat');

var isConcatSpreadable = function (O) {
  if (!isObject(O)) return false;
  var spreadable = O[IS_CONCAT_SPREADABLE];
  return spreadable !== undefined ? !!spreadable : isArray(O);
};

var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !SPECIES_SUPPORT;

// `Array.prototype.concat` method
// https://tc39.github.io/ecma262/#sec-array.prototype.concat
// with adding support of @@isConcatSpreadable and @@species
$({ target: 'Array', proto: true, forced: FORCED }, {
  concat: function concat(arg) { // eslint-disable-line no-unused-vars
    var O = toObject(this);
    var A = arraySpeciesCreate(O, 0);
    var n = 0;
    var i, k, length, len, E;
    for (i = -1, length = arguments.length; i < length; i++) {
      E = i === -1 ? O : arguments[i];
      if (isConcatSpreadable(E)) {
        len = toLength(E.length);
        if (n + len > MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
        for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
      } else {
        if (n >= MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
        createProperty(A, n++, E);
      }
    }
    A.length = n;
    return A;
  }
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.filter.js":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.filter.js ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/export.js");
var $filter = __webpack_require__(/*! ../internals/array-iteration */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-iteration.js").filter;
var arrayMethodHasSpeciesSupport = __webpack_require__(/*! ../internals/array-method-has-species-support */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-has-species-support.js");
var arrayMethodUsesToLength = __webpack_require__(/*! ../internals/array-method-uses-to-length */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-method-uses-to-length.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');
// Edge 14- issue
var USES_TO_LENGTH = arrayMethodUsesToLength('filter');

// `Array.prototype.filter` method
// https://tc39.github.io/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT || !USES_TO_LENGTH }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.iterator.js":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.iterator.js ***!
  \**************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-indexed-object.js");
var addToUnscopables = __webpack_require__(/*! ../internals/add-to-unscopables */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/add-to-unscopables.js");
var Iterators = __webpack_require__(/*! ../internals/iterators */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/iterators.js");
var InternalStateModule = __webpack_require__(/*! ../internals/internal-state */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js");
var defineIterator = __webpack_require__(/*! ../internals/define-iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/define-iterator.js");

var ARRAY_ITERATOR = 'Array Iterator';
var setInternalState = InternalStateModule.set;
var getInternalState = InternalStateModule.getterFor(ARRAY_ITERATOR);

// `Array.prototype.entries` method
// https://tc39.github.io/ecma262/#sec-array.prototype.entries
// `Array.prototype.keys` method
// https://tc39.github.io/ecma262/#sec-array.prototype.keys
// `Array.prototype.values` method
// https://tc39.github.io/ecma262/#sec-array.prototype.values
// `Array.prototype[@@iterator]` method
// https://tc39.github.io/ecma262/#sec-array.prototype-@@iterator
// `CreateArrayIterator` internal method
// https://tc39.github.io/ecma262/#sec-createarrayiterator
module.exports = defineIterator(Array, 'Array', function (iterated, kind) {
  setInternalState(this, {
    type: ARRAY_ITERATOR,
    target: toIndexedObject(iterated), // target
    index: 0,                          // next index
    kind: kind                         // kind
  });
// `%ArrayIteratorPrototype%.next` method
// https://tc39.github.io/ecma262/#sec-%arrayiteratorprototype%.next
}, function () {
  var state = getInternalState(this);
  var target = state.target;
  var kind = state.kind;
  var index = state.index++;
  if (!target || index >= target.length) {
    state.target = undefined;
    return { value: undefined, done: true };
  }
  if (kind == 'keys') return { value: index, done: false };
  if (kind == 'values') return { value: target[index], done: false };
  return { value: [index, target[index]], done: false };
}, 'values');

// argumentsList[@@iterator] is %ArrayProto_values%
// https://tc39.github.io/ecma262/#sec-createunmappedargumentsobject
// https://tc39.github.io/ecma262/#sec-createmappedargumentsobject
Iterators.Arguments = Iterators.Array;

// https://tc39.github.io/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables('keys');
addToUnscopables('values');
addToUnscopables('entries');


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.map.js":
/*!***************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.map.js ***!
  \***************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var collection = __webpack_require__(/*! ../internals/collection */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection.js");
var collectionStrong = __webpack_require__(/*! ../internals/collection-strong */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/collection-strong.js");

// `Map` constructor
// https://tc39.github.io/ecma262/#sec-map-objects
module.exports = collection('Map', function (init) {
  return function Map() { return init(this, arguments.length ? arguments[0] : undefined); };
}, collectionStrong);


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.object.to-string.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.object.to-string.js ***!
  \****************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var TO_STRING_TAG_SUPPORT = __webpack_require__(/*! ../internals/to-string-tag-support */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/to-string-tag-support.js");
var redefine = __webpack_require__(/*! ../internals/redefine */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/redefine.js");
var toString = __webpack_require__(/*! ../internals/object-to-string */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/object-to-string.js");

// `Object.prototype.toString` method
// https://tc39.github.io/ecma262/#sec-object.prototype.tostring
if (!TO_STRING_TAG_SUPPORT) {
  redefine(Object.prototype, 'toString', toString, { unsafe: true });
}


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.string.iterator.js":
/*!***************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.string.iterator.js ***!
  \***************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var charAt = __webpack_require__(/*! ../internals/string-multibyte */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/string-multibyte.js").charAt;
var InternalStateModule = __webpack_require__(/*! ../internals/internal-state */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/internal-state.js");
var defineIterator = __webpack_require__(/*! ../internals/define-iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/define-iterator.js");

var STRING_ITERATOR = 'String Iterator';
var setInternalState = InternalStateModule.set;
var getInternalState = InternalStateModule.getterFor(STRING_ITERATOR);

// `String.prototype[@@iterator]` method
// https://tc39.github.io/ecma262/#sec-string.prototype-@@iterator
defineIterator(String, 'String', function (iterated) {
  setInternalState(this, {
    type: STRING_ITERATOR,
    string: String(iterated),
    index: 0
  });
// `%StringIteratorPrototype%.next` method
// https://tc39.github.io/ecma262/#sec-%stringiteratorprototype%.next
}, function next() {
  var state = getInternalState(this);
  var string = state.string;
  var index = state.index;
  var point;
  if (index >= string.length) return { value: undefined, done: true };
  point = charAt(string, index);
  state.index += point.length;
  return { value: point, done: false };
});


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.for-each.js":
/*!*************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.for-each.js ***!
  \*************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__(/*! ../internals/dom-iterables */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/dom-iterables.js");
var forEach = __webpack_require__(/*! ../internals/array-for-each */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");

for (var COLLECTION_NAME in DOMIterables) {
  var Collection = global[COLLECTION_NAME];
  var CollectionPrototype = Collection && Collection.prototype;
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
}


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.iterator.js":
/*!*************************************************************************************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/web.dom-collections.iterator.js ***!
  \*************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__(/*! ../internals/global */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__(/*! ../internals/dom-iterables */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/dom-iterables.js");
var ArrayIteratorMethods = __webpack_require__(/*! ../modules/es.array.iterator */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/modules/es.array.iterator.js");
var createNonEnumerableProperty = __webpack_require__(/*! ../internals/create-non-enumerable-property */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/create-non-enumerable-property.js");
var wellKnownSymbol = __webpack_require__(/*! ../internals/well-known-symbol */ "./node_modules/@nextcloud/auth/node_modules/@nextcloud/event-bus/node_modules/core-js/internals/well-known-symbol.js");

var ITERATOR = wellKnownSymbol('iterator');
var TO_STRING_TAG = wellKnownSymbol('toStringTag');
var ArrayValues = ArrayIteratorMethods.values;

for (var COLLECTION_NAME in DOMIterables) {
  var Collection = global[COLLECTION_NAME];
  var CollectionPrototype = Collection && Collection.prototype;
  if (CollectionPrototype) {
    // some Chrome versions have non-configurable methods on DOMTokenList
    if (CollectionPrototype[ITERATOR] !== ArrayValues) try {
      createNonEnumerableProperty(CollectionPrototype, ITERATOR, ArrayValues);
    } catch (error) {
      CollectionPrototype[ITERATOR] = ArrayValues;
    }
    if (!CollectionPrototype[TO_STRING_TAG]) {
      createNonEnumerableProperty(CollectionPrototype, TO_STRING_TAG, COLLECTION_NAME);
    }
    if (DOMIterables[COLLECTION_NAME]) for (var METHOD_NAME in ArrayIteratorMethods) {
      // some Chrome versions have non-configurable methods on DOMTokenList
      if (CollectionPrototype[METHOD_NAME] !== ArrayIteratorMethods[METHOD_NAME]) try {
        createNonEnumerableProperty(CollectionPrototype, METHOD_NAME, ArrayIteratorMethods[METHOD_NAME]);
      } catch (error) {
        CollectionPrototype[METHOD_NAME] = ArrayIteratorMethods[METHOD_NAME];
      }
    }
  }
}


/***/ }),

/***/ "./node_modules/@nextcloud/auth/node_modules/semver/semver.js":
/*!********************************************************************!*\
  !*** ./node_modules/@nextcloud/auth/node_modules/semver/semver.js ***!
  \********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(process) {exports = module.exports = SemVer

var debug
/* istanbul ignore next */
if (typeof process === 'object' &&
    process.env &&
    process.env.NODE_DEBUG &&
    /\bsemver\b/i.test(process.env.NODE_DEBUG)) {
  debug = function () {
    var args = Array.prototype.slice.call(arguments, 0)
    args.unshift('SEMVER')
    console.log.apply(console, args)
  }
} else {
  debug = function () {}
}

// Note: this is the semver.org version of the spec that it implements
// Not necessarily the package version of this code.
exports.SEMVER_SPEC_VERSION = '2.0.0'

var MAX_LENGTH = 256
var MAX_SAFE_INTEGER = Number.MAX_SAFE_INTEGER ||
  /* istanbul ignore next */ 9007199254740991

// Max safe segment length for coercion.
var MAX_SAFE_COMPONENT_LENGTH = 16

// The actual regexps go on exports.re
var re = exports.re = []
var src = exports.src = []
var t = exports.tokens = {}
var R = 0

function tok (n) {
  t[n] = R++
}

// The following Regular Expressions can be used for tokenizing,
// validating, and parsing SemVer version strings.

// ## Numeric Identifier
// A single `0`, or a non-zero digit followed by zero or more digits.

tok('NUMERICIDENTIFIER')
src[t.NUMERICIDENTIFIER] = '0|[1-9]\\d*'
tok('NUMERICIDENTIFIERLOOSE')
src[t.NUMERICIDENTIFIERLOOSE] = '[0-9]+'

// ## Non-numeric Identifier
// Zero or more digits, followed by a letter or hyphen, and then zero or
// more letters, digits, or hyphens.

tok('NONNUMERICIDENTIFIER')
src[t.NONNUMERICIDENTIFIER] = '\\d*[a-zA-Z-][a-zA-Z0-9-]*'

// ## Main Version
// Three dot-separated numeric identifiers.

tok('MAINVERSION')
src[t.MAINVERSION] = '(' + src[t.NUMERICIDENTIFIER] + ')\\.' +
                   '(' + src[t.NUMERICIDENTIFIER] + ')\\.' +
                   '(' + src[t.NUMERICIDENTIFIER] + ')'

tok('MAINVERSIONLOOSE')
src[t.MAINVERSIONLOOSE] = '(' + src[t.NUMERICIDENTIFIERLOOSE] + ')\\.' +
                        '(' + src[t.NUMERICIDENTIFIERLOOSE] + ')\\.' +
                        '(' + src[t.NUMERICIDENTIFIERLOOSE] + ')'

// ## Pre-release Version Identifier
// A numeric identifier, or a non-numeric identifier.

tok('PRERELEASEIDENTIFIER')
src[t.PRERELEASEIDENTIFIER] = '(?:' + src[t.NUMERICIDENTIFIER] +
                            '|' + src[t.NONNUMERICIDENTIFIER] + ')'

tok('PRERELEASEIDENTIFIERLOOSE')
src[t.PRERELEASEIDENTIFIERLOOSE] = '(?:' + src[t.NUMERICIDENTIFIERLOOSE] +
                                 '|' + src[t.NONNUMERICIDENTIFIER] + ')'

// ## Pre-release Version
// Hyphen, followed by one or more dot-separated pre-release version
// identifiers.

tok('PRERELEASE')
src[t.PRERELEASE] = '(?:-(' + src[t.PRERELEASEIDENTIFIER] +
                  '(?:\\.' + src[t.PRERELEASEIDENTIFIER] + ')*))'

tok('PRERELEASELOOSE')
src[t.PRERELEASELOOSE] = '(?:-?(' + src[t.PRERELEASEIDENTIFIERLOOSE] +
                       '(?:\\.' + src[t.PRERELEASEIDENTIFIERLOOSE] + ')*))'

// ## Build Metadata Identifier
// Any combination of digits, letters, or hyphens.

tok('BUILDIDENTIFIER')
src[t.BUILDIDENTIFIER] = '[0-9A-Za-z-]+'

// ## Build Metadata
// Plus sign, followed by one or more period-separated build metadata
// identifiers.

tok('BUILD')
src[t.BUILD] = '(?:\\+(' + src[t.BUILDIDENTIFIER] +
             '(?:\\.' + src[t.BUILDIDENTIFIER] + ')*))'

// ## Full Version String
// A main version, followed optionally by a pre-release version and
// build metadata.

// Note that the only major, minor, patch, and pre-release sections of
// the version string are capturing groups.  The build metadata is not a
// capturing group, because it should not ever be used in version
// comparison.

tok('FULL')
tok('FULLPLAIN')
src[t.FULLPLAIN] = 'v?' + src[t.MAINVERSION] +
                  src[t.PRERELEASE] + '?' +
                  src[t.BUILD] + '?'

src[t.FULL] = '^' + src[t.FULLPLAIN] + '$'

// like full, but allows v1.2.3 and =1.2.3, which people do sometimes.
// also, 1.0.0alpha1 (prerelease without the hyphen) which is pretty
// common in the npm registry.
tok('LOOSEPLAIN')
src[t.LOOSEPLAIN] = '[v=\\s]*' + src[t.MAINVERSIONLOOSE] +
                  src[t.PRERELEASELOOSE] + '?' +
                  src[t.BUILD] + '?'

tok('LOOSE')
src[t.LOOSE] = '^' + src[t.LOOSEPLAIN] + '$'

tok('GTLT')
src[t.GTLT] = '((?:<|>)?=?)'

// Something like "2.*" or "1.2.x".
// Note that "x.x" is a valid xRange identifer, meaning "any version"
// Only the first item is strictly required.
tok('XRANGEIDENTIFIERLOOSE')
src[t.XRANGEIDENTIFIERLOOSE] = src[t.NUMERICIDENTIFIERLOOSE] + '|x|X|\\*'
tok('XRANGEIDENTIFIER')
src[t.XRANGEIDENTIFIER] = src[t.NUMERICIDENTIFIER] + '|x|X|\\*'

tok('XRANGEPLAIN')
src[t.XRANGEPLAIN] = '[v=\\s]*(' + src[t.XRANGEIDENTIFIER] + ')' +
                   '(?:\\.(' + src[t.XRANGEIDENTIFIER] + ')' +
                   '(?:\\.(' + src[t.XRANGEIDENTIFIER] + ')' +
                   '(?:' + src[t.PRERELEASE] + ')?' +
                   src[t.BUILD] + '?' +
                   ')?)?'

tok('XRANGEPLAINLOOSE')
src[t.XRANGEPLAINLOOSE] = '[v=\\s]*(' + src[t.XRANGEIDENTIFIERLOOSE] + ')' +
                        '(?:\\.(' + src[t.XRANGEIDENTIFIERLOOSE] + ')' +
                        '(?:\\.(' + src[t.XRANGEIDENTIFIERLOOSE] + ')' +
                        '(?:' + src[t.PRERELEASELOOSE] + ')?' +
                        src[t.BUILD] + '?' +
                        ')?)?'

tok('XRANGE')
src[t.XRANGE] = '^' + src[t.GTLT] + '\\s*' + src[t.XRANGEPLAIN] + '$'
tok('XRANGELOOSE')
src[t.XRANGELOOSE] = '^' + src[t.GTLT] + '\\s*' + src[t.XRANGEPLAINLOOSE] + '$'

// Coercion.
// Extract anything that could conceivably be a part of a valid semver
tok('COERCE')
src[t.COERCE] = '(^|[^\\d])' +
              '(\\d{1,' + MAX_SAFE_COMPONENT_LENGTH + '})' +
              '(?:\\.(\\d{1,' + MAX_SAFE_COMPONENT_LENGTH + '}))?' +
              '(?:\\.(\\d{1,' + MAX_SAFE_COMPONENT_LENGTH + '}))?' +
              '(?:$|[^\\d])'
tok('COERCERTL')
re[t.COERCERTL] = new RegExp(src[t.COERCE], 'g')

// Tilde ranges.
// Meaning is "reasonably at or greater than"
tok('LONETILDE')
src[t.LONETILDE] = '(?:~>?)'

tok('TILDETRIM')
src[t.TILDETRIM] = '(\\s*)' + src[t.LONETILDE] + '\\s+'
re[t.TILDETRIM] = new RegExp(src[t.TILDETRIM], 'g')
var tildeTrimReplace = '$1~'

tok('TILDE')
src[t.TILDE] = '^' + src[t.LONETILDE] + src[t.XRANGEPLAIN] + '$'
tok('TILDELOOSE')
src[t.TILDELOOSE] = '^' + src[t.LONETILDE] + src[t.XRANGEPLAINLOOSE] + '$'

// Caret ranges.
// Meaning is "at least and backwards compatible with"
tok('LONECARET')
src[t.LONECARET] = '(?:\\^)'

tok('CARETTRIM')
src[t.CARETTRIM] = '(\\s*)' + src[t.LONECARET] + '\\s+'
re[t.CARETTRIM] = new RegExp(src[t.CARETTRIM], 'g')
var caretTrimReplace = '$1^'

tok('CARET')
src[t.CARET] = '^' + src[t.LONECARET] + src[t.XRANGEPLAIN] + '$'
tok('CARETLOOSE')
src[t.CARETLOOSE] = '^' + src[t.LONECARET] + src[t.XRANGEPLAINLOOSE] + '$'

// A simple gt/lt/eq thing, or just "" to indicate "any version"
tok('COMPARATORLOOSE')
src[t.COMPARATORLOOSE] = '^' + src[t.GTLT] + '\\s*(' + src[t.LOOSEPLAIN] + ')$|^$'
tok('COMPARATOR')
src[t.COMPARATOR] = '^' + src[t.GTLT] + '\\s*(' + src[t.FULLPLAIN] + ')$|^$'

// An expression to strip any whitespace between the gtlt and the thing
// it modifies, so that `> 1.2.3` ==> `>1.2.3`
tok('COMPARATORTRIM')
src[t.COMPARATORTRIM] = '(\\s*)' + src[t.GTLT] +
                      '\\s*(' + src[t.LOOSEPLAIN] + '|' + src[t.XRANGEPLAIN] + ')'

// this one has to use the /g flag
re[t.COMPARATORTRIM] = new RegExp(src[t.COMPARATORTRIM], 'g')
var comparatorTrimReplace = '$1$2$3'

// Something like `1.2.3 - 1.2.4`
// Note that these all use the loose form, because they'll be
// checked against either the strict or loose comparator form
// later.
tok('HYPHENRANGE')
src[t.HYPHENRANGE] = '^\\s*(' + src[t.XRANGEPLAIN] + ')' +
                   '\\s+-\\s+' +
                   '(' + src[t.XRANGEPLAIN] + ')' +
                   '\\s*$'

tok('HYPHENRANGELOOSE')
src[t.HYPHENRANGELOOSE] = '^\\s*(' + src[t.XRANGEPLAINLOOSE] + ')' +
                        '\\s+-\\s+' +
                        '(' + src[t.XRANGEPLAINLOOSE] + ')' +
                        '\\s*$'

// Star ranges basically just allow anything at all.
tok('STAR')
src[t.STAR] = '(<|>)?=?\\s*\\*'

// Compile to actual regexp objects.
// All are flag-free, unless they were created above with a flag.
for (var i = 0; i < R; i++) {
  debug(i, src[i])
  if (!re[i]) {
    re[i] = new RegExp(src[i])
  }
}

exports.parse = parse
function parse (version, options) {
  if (!options || typeof options !== 'object') {
    options = {
      loose: !!options,
      includePrerelease: false
    }
  }

  if (version instanceof SemVer) {
    return version
  }

  if (typeof version !== 'string') {
    return null
  }

  if (version.length > MAX_LENGTH) {
    return null
  }

  var r = options.loose ? re[t.LOOSE] : re[t.FULL]
  if (!r.test(version)) {
    return null
  }

  try {
    return new SemVer(version, options)
  } catch (er) {
    return null
  }
}

exports.valid = valid
function valid (version, options) {
  var v = parse(version, options)
  return v ? v.version : null
}

exports.clean = clean
function clean (version, options) {
  var s = parse(version.trim().replace(/^[=v]+/, ''), options)
  return s ? s.version : null
}

exports.SemVer = SemVer

function SemVer (version, options) {
  if (!options || typeof options !== 'object') {
    options = {
      loose: !!options,
      includePrerelease: false
    }
  }
  if (version instanceof SemVer) {
    if (version.loose === options.loose) {
      return version
    } else {
      version = version.version
    }
  } else if (typeof version !== 'string') {
    throw new TypeError('Invalid Version: ' + version)
  }

  if (version.length > MAX_LENGTH) {
    throw new TypeError('version is longer than ' + MAX_LENGTH + ' characters')
  }

  if (!(this instanceof SemVer)) {
    return new SemVer(version, options)
  }

  debug('SemVer', version, options)
  this.options = options
  this.loose = !!options.loose

  var m = version.trim().match(options.loose ? re[t.LOOSE] : re[t.FULL])

  if (!m) {
    throw new TypeError('Invalid Version: ' + version)
  }

  this.raw = version

  // these are actually numbers
  this.major = +m[1]
  this.minor = +m[2]
  this.patch = +m[3]

  if (this.major > MAX_SAFE_INTEGER || this.major < 0) {
    throw new TypeError('Invalid major version')
  }

  if (this.minor > MAX_SAFE_INTEGER || this.minor < 0) {
    throw new TypeError('Invalid minor version')
  }

  if (this.patch > MAX_SAFE_INTEGER || this.patch < 0) {
    throw new TypeError('Invalid patch version')
  }

  // numberify any prerelease numeric ids
  if (!m[4]) {
    this.prerelease = []
  } else {
    this.prerelease = m[4].split('.').map(function (id) {
      if (/^[0-9]+$/.test(id)) {
        var num = +id
        if (num >= 0 && num < MAX_SAFE_INTEGER) {
          return num
        }
      }
      return id
    })
  }

  this.build = m[5] ? m[5].split('.') : []
  this.format()
}

SemVer.prototype.format = function () {
  this.version = this.major + '.' + this.minor + '.' + this.patch
  if (this.prerelease.length) {
    this.version += '-' + this.prerelease.join('.')
  }
  return this.version
}

SemVer.prototype.toString = function () {
  return this.version
}

SemVer.prototype.compare = function (other) {
  debug('SemVer.compare', this.version, this.options, other)
  if (!(other instanceof SemVer)) {
    other = new SemVer(other, this.options)
  }

  return this.compareMain(other) || this.comparePre(other)
}

SemVer.prototype.compareMain = function (other) {
  if (!(other instanceof SemVer)) {
    other = new SemVer(other, this.options)
  }

  return compareIdentifiers(this.major, other.major) ||
         compareIdentifiers(this.minor, other.minor) ||
         compareIdentifiers(this.patch, other.patch)
}

SemVer.prototype.comparePre = function (other) {
  if (!(other instanceof SemVer)) {
    other = new SemVer(other, this.options)
  }

  // NOT having a prerelease is > having one
  if (this.prerelease.length && !other.prerelease.length) {
    return -1
  } else if (!this.prerelease.length && other.prerelease.length) {
    return 1
  } else if (!this.prerelease.length && !other.prerelease.length) {
    return 0
  }

  var i = 0
  do {
    var a = this.prerelease[i]
    var b = other.prerelease[i]
    debug('prerelease compare', i, a, b)
    if (a === undefined && b === undefined) {
      return 0
    } else if (b === undefined) {
      return 1
    } else if (a === undefined) {
      return -1
    } else if (a === b) {
      continue
    } else {
      return compareIdentifiers(a, b)
    }
  } while (++i)
}

SemVer.prototype.compareBuild = function (other) {
  if (!(other instanceof SemVer)) {
    other = new SemVer(other, this.options)
  }

  var i = 0
  do {
    var a = this.build[i]
    var b = other.build[i]
    debug('prerelease compare', i, a, b)
    if (a === undefined && b === undefined) {
      return 0
    } else if (b === undefined) {
      return 1
    } else if (a === undefined) {
      return -1
    } else if (a === b) {
      continue
    } else {
      return compareIdentifiers(a, b)
    }
  } while (++i)
}

// preminor will bump the version up to the next minor release, and immediately
// down to pre-release. premajor and prepatch work the same way.
SemVer.prototype.inc = function (release, identifier) {
  switch (release) {
    case 'premajor':
      this.prerelease.length = 0
      this.patch = 0
      this.minor = 0
      this.major++
      this.inc('pre', identifier)
      break
    case 'preminor':
      this.prerelease.length = 0
      this.patch = 0
      this.minor++
      this.inc('pre', identifier)
      break
    case 'prepatch':
      // If this is already a prerelease, it will bump to the next version
      // drop any prereleases that might already exist, since they are not
      // relevant at this point.
      this.prerelease.length = 0
      this.inc('patch', identifier)
      this.inc('pre', identifier)
      break
    // If the input is a non-prerelease version, this acts the same as
    // prepatch.
    case 'prerelease':
      if (this.prerelease.length === 0) {
        this.inc('patch', identifier)
      }
      this.inc('pre', identifier)
      break

    case 'major':
      // If this is a pre-major version, bump up to the same major version.
      // Otherwise increment major.
      // 1.0.0-5 bumps to 1.0.0
      // 1.1.0 bumps to 2.0.0
      if (this.minor !== 0 ||
          this.patch !== 0 ||
          this.prerelease.length === 0) {
        this.major++
      }
      this.minor = 0
      this.patch = 0
      this.prerelease = []
      break
    case 'minor':
      // If this is a pre-minor version, bump up to the same minor version.
      // Otherwise increment minor.
      // 1.2.0-5 bumps to 1.2.0
      // 1.2.1 bumps to 1.3.0
      if (this.patch !== 0 || this.prerelease.length === 0) {
        this.minor++
      }
      this.patch = 0
      this.prerelease = []
      break
    case 'patch':
      // If this is not a pre-release version, it will increment the patch.
      // If it is a pre-release it will bump up to the same patch version.
      // 1.2.0-5 patches to 1.2.0
      // 1.2.0 patches to 1.2.1
      if (this.prerelease.length === 0) {
        this.patch++
      }
      this.prerelease = []
      break
    // This probably shouldn't be used publicly.
    // 1.0.0 "pre" would become 1.0.0-0 which is the wrong direction.
    case 'pre':
      if (this.prerelease.length === 0) {
        this.prerelease = [0]
      } else {
        var i = this.prerelease.length
        while (--i >= 0) {
          if (typeof this.prerelease[i] === 'number') {
            this.prerelease[i]++
            i = -2
          }
        }
        if (i === -1) {
          // didn't increment anything
          this.prerelease.push(0)
        }
      }
      if (identifier) {
        // 1.2.0-beta.1 bumps to 1.2.0-beta.2,
        // 1.2.0-beta.fooblz or 1.2.0-beta bumps to 1.2.0-beta.0
        if (this.prerelease[0] === identifier) {
          if (isNaN(this.prerelease[1])) {
            this.prerelease = [identifier, 0]
          }
        } else {
          this.prerelease = [identifier, 0]
        }
      }
      break

    default:
      throw new Error('invalid increment argument: ' + release)
  }
  this.format()
  this.raw = this.version
  return this
}

exports.inc = inc
function inc (version, release, loose, identifier) {
  if (typeof (loose) === 'string') {
    identifier = loose
    loose = undefined
  }

  try {
    return new SemVer(version, loose).inc(release, identifier).version
  } catch (er) {
    return null
  }
}

exports.diff = diff
function diff (version1, version2) {
  if (eq(version1, version2)) {
    return null
  } else {
    var v1 = parse(version1)
    var v2 = parse(version2)
    var prefix = ''
    if (v1.prerelease.length || v2.prerelease.length) {
      prefix = 'pre'
      var defaultResult = 'prerelease'
    }
    for (var key in v1) {
      if (key === 'major' || key === 'minor' || key === 'patch') {
        if (v1[key] !== v2[key]) {
          return prefix + key
        }
      }
    }
    return defaultResult // may be undefined
  }
}

exports.compareIdentifiers = compareIdentifiers

var numeric = /^[0-9]+$/
function compareIdentifiers (a, b) {
  var anum = numeric.test(a)
  var bnum = numeric.test(b)

  if (anum && bnum) {
    a = +a
    b = +b
  }

  return a === b ? 0
    : (anum && !bnum) ? -1
    : (bnum && !anum) ? 1
    : a < b ? -1
    : 1
}

exports.rcompareIdentifiers = rcompareIdentifiers
function rcompareIdentifiers (a, b) {
  return compareIdentifiers(b, a)
}

exports.major = major
function major (a, loose) {
  return new SemVer(a, loose).major
}

exports.minor = minor
function minor (a, loose) {
  return new SemVer(a, loose).minor
}

exports.patch = patch
function patch (a, loose) {
  return new SemVer(a, loose).patch
}

exports.compare = compare
function compare (a, b, loose) {
  return new SemVer(a, loose).compare(new SemVer(b, loose))
}

exports.compareLoose = compareLoose
function compareLoose (a, b) {
  return compare(a, b, true)
}

exports.compareBuild = compareBuild
function compareBuild (a, b, loose) {
  var versionA = new SemVer(a, loose)
  var versionB = new SemVer(b, loose)
  return versionA.compare(versionB) || versionA.compareBuild(versionB)
}

exports.rcompare = rcompare
function rcompare (a, b, loose) {
  return compare(b, a, loose)
}

exports.sort = sort
function sort (list, loose) {
  return list.sort(function (a, b) {
    return exports.compareBuild(a, b, loose)
  })
}

exports.rsort = rsort
function rsort (list, loose) {
  return list.sort(function (a, b) {
    return exports.compareBuild(b, a, loose)
  })
}

exports.gt = gt
function gt (a, b, loose) {
  return compare(a, b, loose) > 0
}

exports.lt = lt
function lt (a, b, loose) {
  return compare(a, b, loose) < 0
}

exports.eq = eq
function eq (a, b, loose) {
  return compare(a, b, loose) === 0
}

exports.neq = neq
function neq (a, b, loose) {
  return compare(a, b, loose) !== 0
}

exports.gte = gte
function gte (a, b, loose) {
  return compare(a, b, loose) >= 0
}

exports.lte = lte
function lte (a, b, loose) {
  return compare(a, b, loose) <= 0
}

exports.cmp = cmp
function cmp (a, op, b, loose) {
  switch (op) {
    case '===':
      if (typeof a === 'object')
        a = a.version
      if (typeof b === 'object')
        b = b.version
      return a === b

    case '!==':
      if (typeof a === 'object')
        a = a.version
      if (typeof b === 'object')
        b = b.version
      return a !== b

    case '':
    case '=':
    case '==':
      return eq(a, b, loose)

    case '!=':
      return neq(a, b, loose)

    case '>':
      return gt(a, b, loose)

    case '>=':
      return gte(a, b, loose)

    case '<':
      return lt(a, b, loose)

    case '<=':
      return lte(a, b, loose)

    default:
      throw new TypeError('Invalid operator: ' + op)
  }
}

exports.Comparator = Comparator
function Comparator (comp, options) {
  if (!options || typeof options !== 'object') {
    options = {
      loose: !!options,
      includePrerelease: false
    }
  }

  if (comp instanceof Comparator) {
    if (comp.loose === !!options.loose) {
      return comp
    } else {
      comp = comp.value
    }
  }

  if (!(this instanceof Comparator)) {
    return new Comparator(comp, options)
  }

  debug('comparator', comp, options)
  this.options = options
  this.loose = !!options.loose
  this.parse(comp)

  if (this.semver === ANY) {
    this.value = ''
  } else {
    this.value = this.operator + this.semver.version
  }

  debug('comp', this)
}

var ANY = {}
Comparator.prototype.parse = function (comp) {
  var r = this.options.loose ? re[t.COMPARATORLOOSE] : re[t.COMPARATOR]
  var m = comp.match(r)

  if (!m) {
    throw new TypeError('Invalid comparator: ' + comp)
  }

  this.operator = m[1] !== undefined ? m[1] : ''
  if (this.operator === '=') {
    this.operator = ''
  }

  // if it literally is just '>' or '' then allow anything.
  if (!m[2]) {
    this.semver = ANY
  } else {
    this.semver = new SemVer(m[2], this.options.loose)
  }
}

Comparator.prototype.toString = function () {
  return this.value
}

Comparator.prototype.test = function (version) {
  debug('Comparator.test', version, this.options.loose)

  if (this.semver === ANY || version === ANY) {
    return true
  }

  if (typeof version === 'string') {
    try {
      version = new SemVer(version, this.options)
    } catch (er) {
      return false
    }
  }

  return cmp(version, this.operator, this.semver, this.options)
}

Comparator.prototype.intersects = function (comp, options) {
  if (!(comp instanceof Comparator)) {
    throw new TypeError('a Comparator is required')
  }

  if (!options || typeof options !== 'object') {
    options = {
      loose: !!options,
      includePrerelease: false
    }
  }

  var rangeTmp

  if (this.operator === '') {
    if (this.value === '') {
      return true
    }
    rangeTmp = new Range(comp.value, options)
    return satisfies(this.value, rangeTmp, options)
  } else if (comp.operator === '') {
    if (comp.value === '') {
      return true
    }
    rangeTmp = new Range(this.value, options)
    return satisfies(comp.semver, rangeTmp, options)
  }

  var sameDirectionIncreasing =
    (this.operator === '>=' || this.operator === '>') &&
    (comp.operator === '>=' || comp.operator === '>')
  var sameDirectionDecreasing =
    (this.operator === '<=' || this.operator === '<') &&
    (comp.operator === '<=' || comp.operator === '<')
  var sameSemVer = this.semver.version === comp.semver.version
  var differentDirectionsInclusive =
    (this.operator === '>=' || this.operator === '<=') &&
    (comp.operator === '>=' || comp.operator === '<=')
  var oppositeDirectionsLessThan =
    cmp(this.semver, '<', comp.semver, options) &&
    ((this.operator === '>=' || this.operator === '>') &&
    (comp.operator === '<=' || comp.operator === '<'))
  var oppositeDirectionsGreaterThan =
    cmp(this.semver, '>', comp.semver, options) &&
    ((this.operator === '<=' || this.operator === '<') &&
    (comp.operator === '>=' || comp.operator === '>'))

  return sameDirectionIncreasing || sameDirectionDecreasing ||
    (sameSemVer && differentDirectionsInclusive) ||
    oppositeDirectionsLessThan || oppositeDirectionsGreaterThan
}

exports.Range = Range
function Range (range, options) {
  if (!options || typeof options !== 'object') {
    options = {
      loose: !!options,
      includePrerelease: false
    }
  }

  if (range instanceof Range) {
    if (range.loose === !!options.loose &&
        range.includePrerelease === !!options.includePrerelease) {
      return range
    } else {
      return new Range(range.raw, options)
    }
  }

  if (range instanceof Comparator) {
    return new Range(range.value, options)
  }

  if (!(this instanceof Range)) {
    return new Range(range, options)
  }

  this.options = options
  this.loose = !!options.loose
  this.includePrerelease = !!options.includePrerelease

  // First, split based on boolean or ||
  this.raw = range
  this.set = range.split(/\s*\|\|\s*/).map(function (range) {
    return this.parseRange(range.trim())
  }, this).filter(function (c) {
    // throw out any that are not relevant for whatever reason
    return c.length
  })

  if (!this.set.length) {
    throw new TypeError('Invalid SemVer Range: ' + range)
  }

  this.format()
}

Range.prototype.format = function () {
  this.range = this.set.map(function (comps) {
    return comps.join(' ').trim()
  }).join('||').trim()
  return this.range
}

Range.prototype.toString = function () {
  return this.range
}

Range.prototype.parseRange = function (range) {
  var loose = this.options.loose
  range = range.trim()
  // `1.2.3 - 1.2.4` => `>=1.2.3 <=1.2.4`
  var hr = loose ? re[t.HYPHENRANGELOOSE] : re[t.HYPHENRANGE]
  range = range.replace(hr, hyphenReplace)
  debug('hyphen replace', range)
  // `> 1.2.3 < 1.2.5` => `>1.2.3 <1.2.5`
  range = range.replace(re[t.COMPARATORTRIM], comparatorTrimReplace)
  debug('comparator trim', range, re[t.COMPARATORTRIM])

  // `~ 1.2.3` => `~1.2.3`
  range = range.replace(re[t.TILDETRIM], tildeTrimReplace)

  // `^ 1.2.3` => `^1.2.3`
  range = range.replace(re[t.CARETTRIM], caretTrimReplace)

  // normalize spaces
  range = range.split(/\s+/).join(' ')

  // At this point, the range is completely trimmed and
  // ready to be split into comparators.

  var compRe = loose ? re[t.COMPARATORLOOSE] : re[t.COMPARATOR]
  var set = range.split(' ').map(function (comp) {
    return parseComparator(comp, this.options)
  }, this).join(' ').split(/\s+/)
  if (this.options.loose) {
    // in loose mode, throw out any that are not valid comparators
    set = set.filter(function (comp) {
      return !!comp.match(compRe)
    })
  }
  set = set.map(function (comp) {
    return new Comparator(comp, this.options)
  }, this)

  return set
}

Range.prototype.intersects = function (range, options) {
  if (!(range instanceof Range)) {
    throw new TypeError('a Range is required')
  }

  return this.set.some(function (thisComparators) {
    return (
      isSatisfiable(thisComparators, options) &&
      range.set.some(function (rangeComparators) {
        return (
          isSatisfiable(rangeComparators, options) &&
          thisComparators.every(function (thisComparator) {
            return rangeComparators.every(function (rangeComparator) {
              return thisComparator.intersects(rangeComparator, options)
            })
          })
        )
      })
    )
  })
}

// take a set of comparators and determine whether there
// exists a version which can satisfy it
function isSatisfiable (comparators, options) {
  var result = true
  var remainingComparators = comparators.slice()
  var testComparator = remainingComparators.pop()

  while (result && remainingComparators.length) {
    result = remainingComparators.every(function (otherComparator) {
      return testComparator.intersects(otherComparator, options)
    })

    testComparator = remainingComparators.pop()
  }

  return result
}

// Mostly just for testing and legacy API reasons
exports.toComparators = toComparators
function toComparators (range, options) {
  return new Range(range, options).set.map(function (comp) {
    return comp.map(function (c) {
      return c.value
    }).join(' ').trim().split(' ')
  })
}

// comprised of xranges, tildes, stars, and gtlt's at this point.
// already replaced the hyphen ranges
// turn into a set of JUST comparators.
function parseComparator (comp, options) {
  debug('comp', comp, options)
  comp = replaceCarets(comp, options)
  debug('caret', comp)
  comp = replaceTildes(comp, options)
  debug('tildes', comp)
  comp = replaceXRanges(comp, options)
  debug('xrange', comp)
  comp = replaceStars(comp, options)
  debug('stars', comp)
  return comp
}

function isX (id) {
  return !id || id.toLowerCase() === 'x' || id === '*'
}

// ~, ~> --> * (any, kinda silly)
// ~2, ~2.x, ~2.x.x, ~>2, ~>2.x ~>2.x.x --> >=2.0.0 <3.0.0
// ~2.0, ~2.0.x, ~>2.0, ~>2.0.x --> >=2.0.0 <2.1.0
// ~1.2, ~1.2.x, ~>1.2, ~>1.2.x --> >=1.2.0 <1.3.0
// ~1.2.3, ~>1.2.3 --> >=1.2.3 <1.3.0
// ~1.2.0, ~>1.2.0 --> >=1.2.0 <1.3.0
function replaceTildes (comp, options) {
  return comp.trim().split(/\s+/).map(function (comp) {
    return replaceTilde(comp, options)
  }).join(' ')
}

function replaceTilde (comp, options) {
  var r = options.loose ? re[t.TILDELOOSE] : re[t.TILDE]
  return comp.replace(r, function (_, M, m, p, pr) {
    debug('tilde', comp, _, M, m, p, pr)
    var ret

    if (isX(M)) {
      ret = ''
    } else if (isX(m)) {
      ret = '>=' + M + '.0.0 <' + (+M + 1) + '.0.0'
    } else if (isX(p)) {
      // ~1.2 == >=1.2.0 <1.3.0
      ret = '>=' + M + '.' + m + '.0 <' + M + '.' + (+m + 1) + '.0'
    } else if (pr) {
      debug('replaceTilde pr', pr)
      ret = '>=' + M + '.' + m + '.' + p + '-' + pr +
            ' <' + M + '.' + (+m + 1) + '.0'
    } else {
      // ~1.2.3 == >=1.2.3 <1.3.0
      ret = '>=' + M + '.' + m + '.' + p +
            ' <' + M + '.' + (+m + 1) + '.0'
    }

    debug('tilde return', ret)
    return ret
  })
}

// ^ --> * (any, kinda silly)
// ^2, ^2.x, ^2.x.x --> >=2.0.0 <3.0.0
// ^2.0, ^2.0.x --> >=2.0.0 <3.0.0
// ^1.2, ^1.2.x --> >=1.2.0 <2.0.0
// ^1.2.3 --> >=1.2.3 <2.0.0
// ^1.2.0 --> >=1.2.0 <2.0.0
function replaceCarets (comp, options) {
  return comp.trim().split(/\s+/).map(function (comp) {
    return replaceCaret(comp, options)
  }).join(' ')
}

function replaceCaret (comp, options) {
  debug('caret', comp, options)
  var r = options.loose ? re[t.CARETLOOSE] : re[t.CARET]
  return comp.replace(r, function (_, M, m, p, pr) {
    debug('caret', comp, _, M, m, p, pr)
    var ret

    if (isX(M)) {
      ret = ''
    } else if (isX(m)) {
      ret = '>=' + M + '.0.0 <' + (+M + 1) + '.0.0'
    } else if (isX(p)) {
      if (M === '0') {
        ret = '>=' + M + '.' + m + '.0 <' + M + '.' + (+m + 1) + '.0'
      } else {
        ret = '>=' + M + '.' + m + '.0 <' + (+M + 1) + '.0.0'
      }
    } else if (pr) {
      debug('replaceCaret pr', pr)
      if (M === '0') {
        if (m === '0') {
          ret = '>=' + M + '.' + m + '.' + p + '-' + pr +
                ' <' + M + '.' + m + '.' + (+p + 1)
        } else {
          ret = '>=' + M + '.' + m + '.' + p + '-' + pr +
                ' <' + M + '.' + (+m + 1) + '.0'
        }
      } else {
        ret = '>=' + M + '.' + m + '.' + p + '-' + pr +
              ' <' + (+M + 1) + '.0.0'
      }
    } else {
      debug('no pr')
      if (M === '0') {
        if (m === '0') {
          ret = '>=' + M + '.' + m + '.' + p +
                ' <' + M + '.' + m + '.' + (+p + 1)
        } else {
          ret = '>=' + M + '.' + m + '.' + p +
                ' <' + M + '.' + (+m + 1) + '.0'
        }
      } else {
        ret = '>=' + M + '.' + m + '.' + p +
              ' <' + (+M + 1) + '.0.0'
      }
    }

    debug('caret return', ret)
    return ret
  })
}

function replaceXRanges (comp, options) {
  debug('replaceXRanges', comp, options)
  return comp.split(/\s+/).map(function (comp) {
    return replaceXRange(comp, options)
  }).join(' ')
}

function replaceXRange (comp, options) {
  comp = comp.trim()
  var r = options.loose ? re[t.XRANGELOOSE] : re[t.XRANGE]
  return comp.replace(r, function (ret, gtlt, M, m, p, pr) {
    debug('xRange', comp, ret, gtlt, M, m, p, pr)
    var xM = isX(M)
    var xm = xM || isX(m)
    var xp = xm || isX(p)
    var anyX = xp

    if (gtlt === '=' && anyX) {
      gtlt = ''
    }

    // if we're including prereleases in the match, then we need
    // to fix this to -0, the lowest possible prerelease value
    pr = options.includePrerelease ? '-0' : ''

    if (xM) {
      if (gtlt === '>' || gtlt === '<') {
        // nothing is allowed
        ret = '<0.0.0-0'
      } else {
        // nothing is forbidden
        ret = '*'
      }
    } else if (gtlt && anyX) {
      // we know patch is an x, because we have any x at all.
      // replace X with 0
      if (xm) {
        m = 0
      }
      p = 0

      if (gtlt === '>') {
        // >1 => >=2.0.0
        // >1.2 => >=1.3.0
        // >1.2.3 => >= 1.2.4
        gtlt = '>='
        if (xm) {
          M = +M + 1
          m = 0
          p = 0
        } else {
          m = +m + 1
          p = 0
        }
      } else if (gtlt === '<=') {
        // <=0.7.x is actually <0.8.0, since any 0.7.x should
        // pass.  Similarly, <=7.x is actually <8.0.0, etc.
        gtlt = '<'
        if (xm) {
          M = +M + 1
        } else {
          m = +m + 1
        }
      }

      ret = gtlt + M + '.' + m + '.' + p + pr
    } else if (xm) {
      ret = '>=' + M + '.0.0' + pr + ' <' + (+M + 1) + '.0.0' + pr
    } else if (xp) {
      ret = '>=' + M + '.' + m + '.0' + pr +
        ' <' + M + '.' + (+m + 1) + '.0' + pr
    }

    debug('xRange return', ret)

    return ret
  })
}

// Because * is AND-ed with everything else in the comparator,
// and '' means "any version", just remove the *s entirely.
function replaceStars (comp, options) {
  debug('replaceStars', comp, options)
  // Looseness is ignored here.  star is always as loose as it gets!
  return comp.trim().replace(re[t.STAR], '')
}

// This function is passed to string.replace(re[t.HYPHENRANGE])
// M, m, patch, prerelease, build
// 1.2 - 3.4.5 => >=1.2.0 <=3.4.5
// 1.2.3 - 3.4 => >=1.2.0 <3.5.0 Any 3.4.x will do
// 1.2 - 3.4 => >=1.2.0 <3.5.0
function hyphenReplace ($0,
  from, fM, fm, fp, fpr, fb,
  to, tM, tm, tp, tpr, tb) {
  if (isX(fM)) {
    from = ''
  } else if (isX(fm)) {
    from = '>=' + fM + '.0.0'
  } else if (isX(fp)) {
    from = '>=' + fM + '.' + fm + '.0'
  } else {
    from = '>=' + from
  }

  if (isX(tM)) {
    to = ''
  } else if (isX(tm)) {
    to = '<' + (+tM + 1) + '.0.0'
  } else if (isX(tp)) {
    to = '<' + tM + '.' + (+tm + 1) + '.0'
  } else if (tpr) {
    to = '<=' + tM + '.' + tm + '.' + tp + '-' + tpr
  } else {
    to = '<=' + to
  }

  return (from + ' ' + to).trim()
}

// if ANY of the sets match ALL of its comparators, then pass
Range.prototype.test = function (version) {
  if (!version) {
    return false
  }

  if (typeof version === 'string') {
    try {
      version = new SemVer(version, this.options)
    } catch (er) {
      return false
    }
  }

  for (var i = 0; i < this.set.length; i++) {
    if (testSet(this.set[i], version, this.options)) {
      return true
    }
  }
  return false
}

function testSet (set, version, options) {
  for (var i = 0; i < set.length; i++) {
    if (!set[i].test(version)) {
      return false
    }
  }

  if (version.prerelease.length && !options.includePrerelease) {
    // Find the set of versions that are allowed to have prereleases
    // For example, ^1.2.3-pr.1 desugars to >=1.2.3-pr.1 <2.0.0
    // That should allow `1.2.3-pr.2` to pass.
    // However, `1.2.4-alpha.notready` should NOT be allowed,
    // even though it's within the range set by the comparators.
    for (i = 0; i < set.length; i++) {
      debug(set[i].semver)
      if (set[i].semver === ANY) {
        continue
      }

      if (set[i].semver.prerelease.length > 0) {
        var allowed = set[i].semver
        if (allowed.major === version.major &&
            allowed.minor === version.minor &&
            allowed.patch === version.patch) {
          return true
        }
      }
    }

    // Version has a -pre, but it's not one of the ones we like.
    return false
  }

  return true
}

exports.satisfies = satisfies
function satisfies (version, range, options) {
  try {
    range = new Range(range, options)
  } catch (er) {
    return false
  }
  return range.test(version)
}

exports.maxSatisfying = maxSatisfying
function maxSatisfying (versions, range, options) {
  var max = null
  var maxSV = null
  try {
    var rangeObj = new Range(range, options)
  } catch (er) {
    return null
  }
  versions.forEach(function (v) {
    if (rangeObj.test(v)) {
      // satisfies(v, range, options)
      if (!max || maxSV.compare(v) === -1) {
        // compare(max, v, true)
        max = v
        maxSV = new SemVer(max, options)
      }
    }
  })
  return max
}

exports.minSatisfying = minSatisfying
function minSatisfying (versions, range, options) {
  var min = null
  var minSV = null
  try {
    var rangeObj = new Range(range, options)
  } catch (er) {
    return null
  }
  versions.forEach(function (v) {
    if (rangeObj.test(v)) {
      // satisfies(v, range, options)
      if (!min || minSV.compare(v) === 1) {
        // compare(min, v, true)
        min = v
        minSV = new SemVer(min, options)
      }
    }
  })
  return min
}

exports.minVersion = minVersion
function minVersion (range, loose) {
  range = new Range(range, loose)

  var minver = new SemVer('0.0.0')
  if (range.test(minver)) {
    return minver
  }

  minver = new SemVer('0.0.0-0')
  if (range.test(minver)) {
    return minver
  }

  minver = null
  for (var i = 0; i < range.set.length; ++i) {
    var comparators = range.set[i]

    comparators.forEach(function (comparator) {
      // Clone to avoid manipulating the comparator's semver object.
      var compver = new SemVer(comparator.semver.version)
      switch (comparator.operator) {
        case '>':
          if (compver.prerelease.length === 0) {
            compver.patch++
          } else {
            compver.prerelease.push(0)
          }
          compver.raw = compver.format()
          /* fallthrough */
        case '':
        case '>=':
          if (!minver || gt(minver, compver)) {
            minver = compver
          }
          break
        case '<':
        case '<=':
          /* Ignore maximum versions */
          break
        /* istanbul ignore next */
        default:
          throw new Error('Unexpected operation: ' + comparator.operator)
      }
    })
  }

  if (minver && range.test(minver)) {
    return minver
  }

  return null
}

exports.validRange = validRange
function validRange (range, options) {
  try {
    // Return '*' instead of '' so that truthiness works.
    // This will throw if it's invalid anyway
    return new Range(range, options).range || '*'
  } catch (er) {
    return null
  }
}

// Determine if version is less than all the versions possible in the range
exports.ltr = ltr
function ltr (version, range, options) {
  return outside(version, range, '<', options)
}

// Determine if version is greater than all the versions possible in the range.
exports.gtr = gtr
function gtr (version, range, options) {
  return outside(version, range, '>', options)
}

exports.outside = outside
function outside (version, range, hilo, options) {
  version = new SemVer(version, options)
  range = new Range(range, options)

  var gtfn, ltefn, ltfn, comp, ecomp
  switch (hilo) {
    case '>':
      gtfn = gt
      ltefn = lte
      ltfn = lt
      comp = '>'
      ecomp = '>='
      break
    case '<':
      gtfn = lt
      ltefn = gte
      ltfn = gt
      comp = '<'
      ecomp = '<='
      break
    default:
      throw new TypeError('Must provide a hilo val of "<" or ">"')
  }

  // If it satisifes the range it is not outside
  if (satisfies(version, range, options)) {
    return false
  }

  // From now on, variable terms are as if we're in "gtr" mode.
  // but note that everything is flipped for the "ltr" function.

  for (var i = 0; i < range.set.length; ++i) {
    var comparators = range.set[i]

    var high = null
    var low = null

    comparators.forEach(function (comparator) {
      if (comparator.semver === ANY) {
        comparator = new Comparator('>=0.0.0')
      }
      high = high || comparator
      low = low || comparator
      if (gtfn(comparator.semver, high.semver, options)) {
        high = comparator
      } else if (ltfn(comparator.semver, low.semver, options)) {
        low = comparator
      }
    })

    // If the edge version comparator has a operator then our version
    // isn't outside it
    if (high.operator === comp || high.operator === ecomp) {
      return false
    }

    // If the lowest version comparator has an operator and our version
    // is less than it then it isn't higher than the range
    if ((!low.operator || low.operator === comp) &&
        ltefn(version, low.semver)) {
      return false
    } else if (low.operator === ecomp && ltfn(version, low.semver)) {
      return false
    }
  }
  return true
}

exports.prerelease = prerelease
function prerelease (version, options) {
  var parsed = parse(version, options)
  return (parsed && parsed.prerelease.length) ? parsed.prerelease : null
}

exports.intersects = intersects
function intersects (r1, r2, options) {
  r1 = new Range(r1, options)
  r2 = new Range(r2, options)
  return r1.intersects(r2)
}

exports.coerce = coerce
function coerce (version, options) {
  if (version instanceof SemVer) {
    return version
  }

  if (typeof version === 'number') {
    version = String(version)
  }

  if (typeof version !== 'string') {
    return null
  }

  options = options || {}

  var match = null
  if (!options.rtl) {
    match = version.match(re[t.COERCE])
  } else {
    // Find the right-most coercible string that does not share
    // a terminus with a more left-ward coercible string.
    // Eg, '1.2.3.4' wants to coerce '2.3.4', not '3.4' or '4'
    //
    // Walk through the string checking with a /g regexp
    // Manually set the index so as to pick up overlapping matches.
    // Stop when we get a match that ends at the string end, since no
    // coercible string can be more right-ward without the same terminus.
    var next
    while ((next = re[t.COERCERTL].exec(version)) &&
      (!match || match.index + match[0].length !== version.length)
    ) {
      if (!match ||
          next.index + next[0].length !== match.index + match[0].length) {
        match = next
      }
      re[t.COERCERTL].lastIndex = next.index + next[1].length + next[2].length
    }
    // leave it in a clean state
    re[t.COERCERTL].lastIndex = -1
  }

  if (match === null) {
    return null
  }

  return parse(match[2] +
    '.' + (match[3] || '0') +
    '.' + (match[4] || '0'), options)
}

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../../process/browser.js */ "./node_modules/process/browser.js")))

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/ActionButton.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/ActionButton.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(t,e){ true?module.exports=e():undefined}(window,function(){return function(t){var e={};function n(o){if(e[o])return e[o].exports;var r=e[o]={i:o,l:!1,exports:{}};return t[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=t,n.c=e,n.d=function(t,e,o){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:o})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)n.d(o,r,function(e){return t[e]}.bind(null,r));return o},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="/dist/",n(n.s=81)}({0:function(t,e,n){"use strict";function o(t,e,n,o,r,i,a,s){var c,u="function"==typeof t?t.options:t;if(e&&(u.render=e,u.staticRenderFns=n,u._compiled=!0),o&&(u.functional=!0),i&&(u._scopeId="data-v-"+i),a?(c=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),r&&r.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(a)},u._ssrRegister=c):r&&(c=s?function(){r.call(this,this.$root.$options.shadowRoot)}:r),c)if(u.functional){u._injectStyles=c;var d=u.render;u.render=function(t,e){return c.call(e),d(t,e)}}else{var l=u.beforeCreate;u.beforeCreate=l?[].concat(l,c):[c]}return{exports:t,options:u}}n.d(e,"a",function(){return o})},1:function(t,e,n){"use strict";t.exports=function(t){var e=[];return e.toString=function(){return this.map(function(e){var n=function(t,e){var n=t[1]||"",o=t[3];if(!o)return n;if(e&&"function"==typeof btoa){var r=(a=o,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),c="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(c," */")),i=o.sources.map(function(t){return"/*# sourceURL=".concat(o.sourceRoot||"").concat(t," */")});return[n].concat(i).concat([r]).join("\n")}var a,s,c;return[n].join("\n")}(e,t);return e[2]?"@media ".concat(e[2]," {").concat(n,"}"):n}).join("")},e.i=function(t,n,o){"string"==typeof t&&(t=[[null,t,""]]);var r={};if(o)for(var i=0;i<this.length;i++){var a=this[i][0];null!=a&&(r[a]=!0)}for(var s=0;s<t.length;s++){var c=[].concat(t[s]);o&&r[c[0]]||(n&&(c[2]?c[2]="".concat(n," and ").concat(c[2]):c[2]=n),e.push(c))}},e}},112:function(t,e,n){"use strict";var o=n(39);n.n(o).a},113:function(t,e,n){(e=n(1)(!1)).push([t.i,"li.active[data-v-42604de1]{box-shadow:inset 4px 0 var(--color-primary)}.action--disabled[data-v-42604de1]{pointer-events:none;opacity:.5}.action--disabled[data-v-42604de1]:hover,.action--disabled[data-v-42604de1]:focus{cursor:default;opacity:.5}.action--disabled *[data-v-42604de1]{opacity:1 !important}.action-button[data-v-42604de1]{display:flex;align-items:flex-start;width:100%;height:auto;margin:0;padding:0;padding-right:14px;cursor:pointer;white-space:nowrap;opacity:.7;color:var(--color-main-text);border:0;border-radius:0;background-color:transparent;box-shadow:none;font-weight:normal;line-height:44px}.action-button[data-v-42604de1]:hover,.action-button[data-v-42604de1]:focus{opacity:1}.action-button>span[data-v-42604de1]{cursor:pointer;white-space:nowrap}.action-button__icon[data-v-42604de1]{width:44px;height:44px;opacity:1;background-position:14px center;background-size:16px}.action-button p[data-v-42604de1]{width:150px;padding:7px 0;margin:auto;cursor:pointer;text-align:left;line-height:1.6em}.action-button__longtext[data-v-42604de1]{cursor:pointer;white-space:pre-wrap}.action-button__title[data-v-42604de1]{font-weight:bold}\n",""]),t.exports=e},13:function(t,e,n){"use strict";n(6),n(23);var o=n(5),r=n.n(o);
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 */e.a={before:function(){this.$slots.default&&""!==this.text.trim()||(r.a.util.warn("".concat(this.$options.name," cannot be empty and requires a meaningful text content"),this),this.$destroy(),this.$el.remove())},beforeUpdate:function(){this.text=this.getText()},data:function(){return{text:this.getText()}},computed:{isLongText:function(){return this.text&&this.text.trim().length>20}},methods:{getText:function(){return this.$slots.default?this.$slots.default[0].text.trim():""}}}},16:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/es.array.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.iterator.js")},17:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/es.string.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.iterator.js")},18:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/web.dom-collections.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/web.dom-collections.iterator.js")},2:function(t,e,n){"use strict";function o(t,e){for(var n=[],o={},r=0;r<e.length;r++){var i=e[r],a=i[0],s={id:t+":"+r,css:i[1],media:i[2],sourceMap:i[3]};o[a]?o[a].parts.push(s):n.push(o[a]={id:a,parts:[s]})}return n}n.r(e),n.d(e,"default",function(){return h});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=r&&(document.head||document.getElementsByTagName("head")[0]),s=null,c=0,u=!1,d=function(){},l=null,f="data-vue-ssr-id",p="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function h(t,e,n,r){u=n,l=r||{};var a=o(t,e);return v(a),function(e){for(var n=[],r=0;r<a.length;r++){var s=a[r];(c=i[s.id]).refs--,n.push(c)}e?v(a=o(t,e)):a=[];for(r=0;r<n.length;r++){var c;if(0===(c=n[r]).refs){for(var u=0;u<c.parts.length;u++)c.parts[u]();delete i[c.id]}}}}function v(t){for(var e=0;e<t.length;e++){var n=t[e],o=i[n.id];if(o){o.refs++;for(var r=0;r<o.parts.length;r++)o.parts[r](n.parts[r]);for(;r<n.parts.length;r++)o.parts.push(b(n.parts[r]));o.parts.length>n.parts.length&&(o.parts.length=n.parts.length)}else{var a=[];for(r=0;r<n.parts.length;r++)a.push(b(n.parts[r]));i[n.id]={id:n.id,refs:1,parts:a}}}}function m(){var t=document.createElement("style");return t.type="text/css",a.appendChild(t),t}function b(t){var e,n,o=document.querySelector("style["+f+'~="'+t.id+'"]');if(o){if(u)return d;o.parentNode.removeChild(o)}if(p){var r=c++;o=s||(s=m()),e=y.bind(null,o,r,!1),n=y.bind(null,o,r,!0)}else o=m(),e=function(t,e){var n=e.css,o=e.media,r=e.sourceMap;o&&t.setAttribute("media",o);l.ssrId&&t.setAttribute(f,e.id);r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */");if(t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}.bind(null,o),n=function(){o.parentNode.removeChild(o)};return e(t),function(o){if(o){if(o.css===t.css&&o.media===t.media&&o.sourceMap===t.sourceMap)return;e(t=o)}else n()}}var g,x=(g=[],function(t,e){return g[t]=e,g.filter(Boolean).join("\n")});function y(t,e,n,o){var r=n?"":o.css;if(t.styleSheet)t.styleSheet.cssText=x(e,r);else{var i=document.createTextNode(r),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(i,a[e]):t.appendChild(i)}}},22:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/web.url */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/web.url.js")},23:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/es.string.trim */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.trim.js")},25:function(t,e,n){"use strict";n(16),n(3),n(17),n(18),n(22);var o=n(13),r=(n(6),function(t,e){for(var n=t.$parent;n;){if(n.$options.name===e)return n;n=n.$parent}});e.a={mixins:[o.a],props:{icon:{type:String,default:""},title:{type:String,default:""},closeAfterClick:{type:Boolean,default:!1}},computed:{isIconUrl:function(){try{return new URL(this.icon)}catch(t){return!1}}},methods:{onClick:function(t){if(this.$emit("click",t),this.closeAfterClick){var e=r(this,"Actions");e&&e.closeMenu&&e.closeMenu()}}}}},3:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/es.object.to-string */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.object.to-string.js")},39:function(t,e,n){var o=n(113);"string"==typeof o&&(o=[[t.i,o,""]]),o.locals&&(t.exports=o.locals);(0,n(2).default)("4a580912",o,!0,{})},48:function(t,e,n){"use strict";var o={name:"ActionButton",mixins:[n(25).a],props:{disabled:{type:Boolean,default:!1}},computed:{isFocusable:function(){return!this.disabled}}},r=(n(112),n(0)),i=n(50),a=n.n(i),s=Object(r.a)(o,function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("li",{class:{"action--disabled":t.disabled}},[n("button",{staticClass:"action-button",class:{focusable:t.isFocusable},on:{click:t.onClick}},[n("span",{staticClass:"action-button__icon",class:[t.isIconUrl?"action-button__icon--url":t.icon],style:{backgroundImage:t.isIconUrl?"url("+t.icon+")":null}}),t._v(" "),t.title?n("p",[n("strong",{staticClass:"action-button__title"},[t._v("\n\t\t\t\t"+t._s(t.title)+"\n\t\t\t")]),t._v(" "),n("br"),t._v(" "),n("span",{staticClass:"action-button__longtext",domProps:{textContent:t._s(t.text)}})]):t.isLongText?n("p",{staticClass:"action-button__longtext",domProps:{textContent:t._s(t.text)}}):n("span",{staticClass:"action-button__text"},[t._v(t._s(t.text))]),t._v(" "),t._e()],2)])},[],!1,null,"42604de1",null);"function"==typeof a.a&&a()(s);e.a=s.exports},5:function(t,e){t.exports=__webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js")},50:function(t,e){},6:function(t,e){t.exports=__webpack_require__(/*! core-js/modules/es.function.name */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.function.name.js")},81:function(t,e,n){"use strict";n.r(e);var o=n(48);n.d(e,"ActionButton",function(){return o.a}),
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 */e.default=o.a}})});
//# sourceMappingURL=ActionButton.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationCaption.js":
/*!*****************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/AppNavigationCaption.js ***!
  \*****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(window,function(){return function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/dist/",n(n.s=164)}({0:function(e,t,n){"use strict";function r(e,t,n,r,o,i,a,s){var u,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=n,c._compiled=!0),r&&(c.functional=!0),i&&(c._scopeId="data-v-"+i),a?(u=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},c._ssrRegister=u):o&&(u=s?function(){o.call(this,this.$root.$options.shadowRoot)}:o),u)if(c.functional){c._injectStyles=u;var p=c.render;c.render=function(e,t){return u.call(t),p(e,t)}}else{var f=c.beforeCreate;c.beforeCreate=f?[].concat(f,u):[u]}return{exports:e,options:c}}n.d(t,"a",function(){return r})},1:function(e,t,n){"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map(function(t){var n=function(e,t){var n=e[1]||"",r=e[3];if(!r)return n;if(t&&"function"==typeof btoa){var o=(a=r,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),u="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(u," */")),i=r.sources.map(function(e){return"/*# sourceURL=".concat(r.sourceRoot||"").concat(e," */")});return[n].concat(i).concat([o]).join("\n")}var a,s,u;return[n].join("\n")}(t,e);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n}).join("")},t.i=function(e,n,r){"string"==typeof e&&(e=[[null,e,""]]);var o={};if(r)for(var i=0;i<this.length;i++){var a=this[i][0];null!=a&&(o[a]=!0)}for(var s=0;s<e.length;s++){var u=[].concat(e[s]);r&&o[u[0]]||(n&&(u[2]?u[2]="".concat(n," and ").concat(u[2]):u[2]=n),t.push(u))}},t}},164:function(e,t,n){"use strict";n.r(t);var r={name:"AppNavigationCaption",props:{title:{type:String,required:!0}}},o=(n(195),n(0)),i=Object(o.a)(r,function(){var e=this.$createElement;return(this._self._c||e)("li",{staticClass:"app-navigation-caption"},[this._v("\n\t"+this._s(this.title)+"\n")])},[],!1,null,"779383f0",null).exports;n.d(t,"AppNavigationCaption",function(){return i});t.default=i},195:function(e,t,n){"use strict";var r=n(92);n.n(r).a},196:function(e,t,n){(t=n(1)(!1)).push([e.i,".app-navigation-caption[data-v-779383f0]{font-weight:bold;color:var(--color-text-maxcontrast);line-height:44px;padding-left:44px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;opacity:0.7;box-shadow:none !important;order:1;flex-shrink:0}.app-navigation-caption[data-v-779383f0]:not(:first-child){margin-top:22px}\n",""]),e.exports=t},2:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var i=t[o],a=i[0],s={id:e+":"+o,css:i[1],media:i[2],sourceMap:i[3]};r[a]?r[a].parts.push(s):n.push(r[a]={id:a,parts:[s]})}return n}n.r(t),n.d(t,"default",function(){return v});var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=o&&(document.head||document.getElementsByTagName("head")[0]),s=null,u=0,c=!1,p=function(){},f=null,d="data-vue-ssr-id",l="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function v(e,t,n,o){c=n,f=o||{};var a=r(e,t);return h(a),function(t){for(var n=[],o=0;o<a.length;o++){var s=a[o];(u=i[s.id]).refs--,n.push(u)}t?h(a=r(e,t)):a=[];for(o=0;o<n.length;o++){var u;if(0===(u=n[o]).refs){for(var c=0;c<u.parts.length;c++)u.parts[c]();delete i[u.id]}}}}function h(e){for(var t=0;t<e.length;t++){var n=e[t],r=i[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(m(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(o=0;o<n.parts.length;o++)a.push(m(n.parts[o]));i[n.id]={id:n.id,refs:1,parts:a}}}}function g(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function m(e){var t,n,r=document.querySelector("style["+d+'~="'+e.id+'"]');if(r){if(c)return p;r.parentNode.removeChild(r)}if(l){var o=u++;r=s||(s=g()),t=x.bind(null,r,o,!1),n=x.bind(null,r,o,!0)}else r=g(),t=function(e,t){var n=t.css,r=t.media,o=t.sourceMap;r&&e.setAttribute("media",r);f.ssrId&&e.setAttribute(d,t.id);o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */");if(e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var y,b=(y=[],function(e,t){return y[e]=t,y.filter(Boolean).join("\n")});function x(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=b(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}},92:function(e,t,n){var r=n(196);"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n(2).default)("2ebdb218",r,!0,{})}})});
//# sourceMappingURL=AppNavigationCaption.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationNew.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/AppNavigationNew.js ***!
  \*************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(window,function(){return function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/dist/",n(n.s=167)}({0:function(e,t,n){"use strict";function r(e,t,n,r,o,i,a,s){var u,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=n,c._compiled=!0),r&&(c.functional=!0),i&&(c._scopeId="data-v-"+i),a?(u=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},c._ssrRegister=u):o&&(u=s?function(){o.call(this,this.$root.$options.shadowRoot)}:o),u)if(c.functional){c._injectStyles=u;var d=c.render;c.render=function(e,t){return u.call(t),d(e,t)}}else{var f=c.beforeCreate;c.beforeCreate=f?[].concat(f,u):[u]}return{exports:e,options:c}}n.d(t,"a",function(){return r})},1:function(e,t,n){"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map(function(t){var n=function(e,t){var n=e[1]||"",r=e[3];if(!r)return n;if(t&&"function"==typeof btoa){var o=(a=r,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),u="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(u," */")),i=r.sources.map(function(e){return"/*# sourceURL=".concat(r.sourceRoot||"").concat(e," */")});return[n].concat(i).concat([o]).join("\n")}var a,s,u;return[n].join("\n")}(t,e);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n}).join("")},t.i=function(e,n,r){"string"==typeof e&&(e=[[null,e,""]]);var o={};if(r)for(var i=0;i<this.length;i++){var a=this[i][0];null!=a&&(o[a]=!0)}for(var s=0;s<e.length;s++){var u=[].concat(e[s]);r&&o[u[0]]||(n&&(u[2]?u[2]="".concat(n," and ").concat(u[2]):u[2]=n),t.push(u))}},t}},167:function(e,t,n){"use strict";n.r(t);var r={props:{buttonId:{type:String,required:!1,default:""},buttonClass:{type:[String,Array,Object],required:!1,default:""},disabled:{type:Boolean,required:!1,default:!1},text:{type:String,required:!0}}},o=(n(205),n(0)),i=Object(o.a)(r,function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{staticClass:"app-navigation-new"},[n("button",{class:e.buttonClass,attrs:{id:e.buttonId,type:"button",disabled:e.disabled},on:{click:function(t){return e.$emit("click")}}},[e._v("\n\t\t"+e._s(e.text)+"\n\t")])])},[],!1,null,"15885669",null).exports;n.d(t,"AppNavigationNew",function(){return i});
/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
 */t.default=i},2:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var i=t[o],a=i[0],s={id:e+":"+o,css:i[1],media:i[2],sourceMap:i[3]};r[a]?r[a].parts.push(s):n.push(r[a]={id:a,parts:[s]})}return n}n.r(t),n.d(t,"default",function(){return v});var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=o&&(document.head||document.getElementsByTagName("head")[0]),s=null,u=0,c=!1,d=function(){},f=null,l="data-vue-ssr-id",p="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function v(e,t,n,o){c=n,f=o||{};var a=r(e,t);return g(a),function(t){for(var n=[],o=0;o<a.length;o++){var s=a[o];(u=i[s.id]).refs--,n.push(u)}t?g(a=r(e,t)):a=[];for(o=0;o<n.length;o++){var u;if(0===(u=n[o]).refs){for(var c=0;c<u.parts.length;c++)u.parts[c]();delete i[u.id]}}}}function g(e){for(var t=0;t<e.length;t++){var n=e[t],r=i[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(b(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(o=0;o<n.parts.length;o++)a.push(b(n.parts[o]));i[n.id]={id:n.id,refs:1,parts:a}}}}function h(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function b(e){var t,n,r=document.querySelector("style["+l+'~="'+e.id+'"]');if(r){if(c)return d;r.parentNode.removeChild(r)}if(p){var o=u++;r=s||(s=h()),t=x.bind(null,r,o,!1),n=x.bind(null,r,o,!0)}else r=h(),t=function(e,t){var n=t.css,r=t.media,o=t.sourceMap;r&&e.setAttribute("media",r);f.ssrId&&e.setAttribute(l,t.id);o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */");if(e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var y,m=(y=[],function(e,t){return y[e]=t,y.filter(Boolean).join("\n")});function x(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=m(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}},205:function(e,t,n){"use strict";var r=n(97);n.n(r).a},206:function(e,t,n){(t=n(1)(!1)).push([e.i,".app-navigation-new[data-v-15885669]{display:block;padding:10px}.app-navigation-new button[data-v-15885669]{display:inline-block;width:100%;padding:10px;padding-left:34px;background-position:10px center;text-align:left;margin:0}\n",""]),e.exports=t},97:function(e,t,n){var r=n(206);"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n(2).default)("3890adfa",r,!0,{})}})});
//# sourceMappingURL=AppNavigationNew.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationSettings.js":
/*!******************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/AppNavigationSettings.js ***!
  \******************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(window,function(){return function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/dist/",n(n.s=168)}({0:function(e,t,n){"use strict";function r(e,t,n,r,o,i,a,s){var u,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=n,c._compiled=!0),r&&(c.functional=!0),i&&(c._scopeId="data-v-"+i),a?(u=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},c._ssrRegister=u):o&&(u=s?function(){o.call(this,this.$root.$options.shadowRoot)}:o),u)if(c.functional){c._injectStyles=u;var d=c.render;c.render=function(e,t){return u.call(t),d(e,t)}}else{var p=c.beforeCreate;c.beforeCreate=p?[].concat(p,u):[u]}return{exports:e,options:c}}n.d(t,"a",function(){return r})},1:function(e,t,n){"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map(function(t){var n=function(e,t){var n=e[1]||"",r=e[3];if(!r)return n;if(t&&"function"==typeof btoa){var o=(a=r,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),u="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(u," */")),i=r.sources.map(function(e){return"/*# sourceURL=".concat(r.sourceRoot||"").concat(e," */")});return[n].concat(i).concat([o]).join("\n")}var a,s,u;return[n].join("\n")}(t,e);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n}).join("")},t.i=function(e,n,r){"string"==typeof e&&(e=[[null,e,""]]);var o={};if(r)for(var i=0;i<this.length;i++){var a=this[i][0];null!=a&&(o[a]=!0)}for(var s=0;s<e.length;s++){var u=[].concat(e[s]);r&&o[u[0]]||(n&&(u[2]?u[2]="".concat(n," and ").concat(u[2]):u[2]=n),t.push(u))}},t}},15:function(e,t){e.exports=__webpack_require__(/*! v-click-outside */ "./node_modules/v-click-outside/dist/v-click-outside.umd.js")},168:function(e,n,r){"use strict";r.r(n);var o={directives:{ClickOutside:r(15).directive},props:{title:{type:String,required:!1,default:t("core","Settings")}},data:function(){return{open:!1}},methods:{toggleMenu:function(){this.open=!this.open},closeMenu:function(){this.open=!1}}},i=(r(207),r(0)),a=Object(i.a)(o,function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{directives:[{name:"click-outside",rawName:"v-click-outside",value:e.closeMenu,expression:"closeMenu"}],class:{open:e.open},attrs:{id:"app-settings"}},[n("div",{attrs:{id:"app-settings-header"}},[n("button",{staticClass:"settings-button",on:{click:e.toggleMenu}},[e._v("\n\t\t\t"+e._s(e.title)+"\n\t\t")])]),e._v(" "),n("transition",{attrs:{name:"slide-up"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:e.open,expression:"open"}],attrs:{id:"app-settings-content"}},[e._t("default")],2)])],1)},[],!1,null,"7dc165a0",null).exports;r.d(n,"AppNavigationSettings",function(){return a});
/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
 */n.default=a},2:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var i=t[o],a=i[0],s={id:e+":"+o,css:i[1],media:i[2],sourceMap:i[3]};r[a]?r[a].parts.push(s):n.push(r[a]={id:a,parts:[s]})}return n}n.r(t),n.d(t,"default",function(){return v});var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=o&&(document.head||document.getElementsByTagName("head")[0]),s=null,u=0,c=!1,d=function(){},p=null,l="data-vue-ssr-id",f="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function v(e,t,n,o){c=n,p=o||{};var a=r(e,t);return h(a),function(t){for(var n=[],o=0;o<a.length;o++){var s=a[o];(u=i[s.id]).refs--,n.push(u)}t?h(a=r(e,t)):a=[];for(o=0;o<n.length;o++){var u;if(0===(u=n[o]).refs){for(var c=0;c<u.parts.length;c++)u.parts[c]();delete i[u.id]}}}}function h(e){for(var t=0;t<e.length;t++){var n=e[t],r=i[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(m(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(o=0;o<n.parts.length;o++)a.push(m(n.parts[o]));i[n.id]={id:n.id,refs:1,parts:a}}}}function g(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function m(e){var t,n,r=document.querySelector("style["+l+'~="'+e.id+'"]');if(r){if(c)return d;r.parentNode.removeChild(r)}if(f){var o=u++;r=s||(s=g()),t=x.bind(null,r,o,!1),n=x.bind(null,r,o,!0)}else r=g(),t=function(e,t){var n=t.css,r=t.media,o=t.sourceMap;r&&e.setAttribute("media",r);p.ssrId&&e.setAttribute(l,t.id);o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */");if(e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var y,b=(y=[],function(e,t){return y[e]=t,y.filter(Boolean).join("\n")});function x(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=b(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}},207:function(e,t,n){"use strict";var r=n(98);n.n(r).a},208:function(e,t,n){(t=n(1)(!1)).push([e.i,"#app-settings-content[data-v-7dc165a0]{display:block;padding:10px;background-color:var(--color-main-background);max-height:300px;overflow-y:auto;box-sizing:border-box}.slide-up-leave-active[data-v-7dc165a0],.slide-up-enter-active[data-v-7dc165a0]{transition-duration:var(--animation-slow);transition-property:max-height, padding;overflow-y:hidden !important}.slide-up-enter[data-v-7dc165a0],.slide-up-leave-to[data-v-7dc165a0]{max-height:0 !important;padding:0 10px !important}\n",""]),e.exports=t},98:function(e,t,n){var r=n(208);"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n(2).default)("56fb5cfa",r,!0,{})}})});
//# sourceMappingURL=AppNavigationSettings.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/Multiselect.js":
/*!********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/Multiselect.js ***!
  \********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(A,t){ true?module.exports=t():undefined}(window,function(){return function(A){var t={};function e(i){if(t[i])return t[i].exports;var a=t[i]={i:i,l:!1,exports:{}};return A[i].call(a.exports,a,a.exports,e),a.l=!0,a.exports}return e.m=A,e.c=t,e.d=function(A,t,i){e.o(A,t)||Object.defineProperty(A,t,{enumerable:!0,get:i})},e.r=function(A){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(A,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(A,"__esModule",{value:!0})},e.t=function(A,t){if(1&t&&(A=e(A)),8&t)return A;if(4&t&&"object"==typeof A&&A&&A.__esModule)return A;var i=Object.create(null);if(e.r(i),Object.defineProperty(i,"default",{enumerable:!0,value:A}),2&t&&"string"!=typeof A)for(var a in A)e.d(i,a,function(t){return A[t]}.bind(null,a));return i},e.n=function(A){var t=A&&A.__esModule?function(){return A.default}:function(){return A};return e.d(t,"a",t),t},e.o=function(A,t){return Object.prototype.hasOwnProperty.call(A,t)},e.p="/dist/",e(e.s=107)}([function(A,t,e){"use strict";function i(A,t,e,i,a,n,o,r){var s,c="function"==typeof A?A.options:A;if(t&&(c.render=t,c.staticRenderFns=e,c._compiled=!0),i&&(c.functional=!0),n&&(c._scopeId="data-v-"+n),o?(s=function(A){(A=A||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(A=__VUE_SSR_CONTEXT__),a&&a.call(this,A),A&&A._registeredComponents&&A._registeredComponents.add(o)},c._ssrRegister=s):a&&(s=r?function(){a.call(this,this.$root.$options.shadowRoot)}:a),s)if(c.functional){c._injectStyles=s;var l=c.render;c.render=function(A,t){return s.call(t),l(A,t)}}else{var d=c.beforeCreate;c.beforeCreate=d?[].concat(d,s):[s]}return{exports:A,options:c}}e.d(t,"a",function(){return i})},function(A,t,e){"use strict";A.exports=function(A){var t=[];return t.toString=function(){return this.map(function(t){var e=function(A,t){var e=A[1]||"",i=A[3];if(!i)return e;if(t&&"function"==typeof btoa){var a=(o=i,r=btoa(unescape(encodeURIComponent(JSON.stringify(o)))),s="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(r),"/*# ".concat(s," */")),n=i.sources.map(function(A){return"/*# sourceURL=".concat(i.sourceRoot||"").concat(A," */")});return[e].concat(n).concat([a]).join("\n")}var o,r,s;return[e].join("\n")}(t,A);return t[2]?"@media ".concat(t[2]," {").concat(e,"}"):e}).join("")},t.i=function(A,e,i){"string"==typeof A&&(A=[[null,A,""]]);var a={};if(i)for(var n=0;n<this.length;n++){var o=this[n][0];null!=o&&(a[o]=!0)}for(var r=0;r<A.length;r++){var s=[].concat(A[r]);i&&a[s[0]]||(e&&(s[2]?s[2]="".concat(e," and ").concat(s[2]):s[2]=e),t.push(s))}},t}},function(A,t,e){"use strict";function i(A,t){for(var e=[],i={},a=0;a<t.length;a++){var n=t[a],o=n[0],r={id:A+":"+a,css:n[1],media:n[2],sourceMap:n[3]};i[o]?i[o].parts.push(r):e.push(i[o]={id:o,parts:[r]})}return e}e.r(t),e.d(t,"default",function(){return g});var a="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!a)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var n={},o=a&&(document.head||document.getElementsByTagName("head")[0]),r=null,s=0,c=!1,l=function(){},d=null,u="data-vue-ssr-id",p="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function g(A,t,e,a){c=e,d=a||{};var o=i(A,t);return f(o),function(t){for(var e=[],a=0;a<o.length;a++){var r=o[a];(s=n[r.id]).refs--,e.push(s)}t?f(o=i(A,t)):o=[];for(a=0;a<e.length;a++){var s;if(0===(s=e[a]).refs){for(var c=0;c<s.parts.length;c++)s.parts[c]();delete n[s.id]}}}}function f(A){for(var t=0;t<A.length;t++){var e=A[t],i=n[e.id];if(i){i.refs++;for(var a=0;a<i.parts.length;a++)i.parts[a](e.parts[a]);for(;a<e.parts.length;a++)i.parts.push(v(e.parts[a]));i.parts.length>e.parts.length&&(i.parts.length=e.parts.length)}else{var o=[];for(a=0;a<e.parts.length;a++)o.push(v(e.parts[a]));n[e.id]={id:e.id,refs:1,parts:o}}}}function m(){var A=document.createElement("style");return A.type="text/css",o.appendChild(A),A}function v(A){var t,e,i=document.querySelector("style["+u+'~="'+A.id+'"]');if(i){if(c)return l;i.parentNode.removeChild(i)}if(p){var a=s++;i=r||(r=m()),t=x.bind(null,i,a,!1),e=x.bind(null,i,a,!0)}else i=m(),t=function(A,t){var e=t.css,i=t.media,a=t.sourceMap;i&&A.setAttribute("media",i);d.ssrId&&A.setAttribute(u,t.id);a&&(e+="\n/*# sourceURL="+a.sources[0]+" */",e+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(a))))+" */");if(A.styleSheet)A.styleSheet.cssText=e;else{for(;A.firstChild;)A.removeChild(A.firstChild);A.appendChild(document.createTextNode(e))}}.bind(null,i),e=function(){i.parentNode.removeChild(i)};return t(A),function(i){if(i){if(i.css===A.css&&i.media===A.media&&i.sourceMap===A.sourceMap)return;t(A=i)}else e()}}var b,h=(b=[],function(A,t){return b[A]=t,b.filter(Boolean).join("\n")});function x(A,t,e,i){var a=e?"":i.css;if(A.styleSheet)A.styleSheet.cssText=h(t,a);else{var n=document.createTextNode(a),o=A.childNodes;o[t]&&A.removeChild(o[t]),o.length?A.insertBefore(n,o[t]):A.appendChild(n)}}},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.object.to-string */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.object.to-string.js")},function(A,t){A.exports=__webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js")},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.function.name */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.function.name.js")},function(A,t,e){"use strict";A.exports=function(A,t){return t||(t={}),"string"!=typeof(A=A&&A.__esModule?A.default:A)?A:(/^['"].*['"]$/.test(A)&&(A=A.slice(1,-1)),t.hash&&(A+=t.hash),/["'() \t\n]/.test(A)||t.needQuotes?'"'.concat(A.replace(/"/g,'\\"').replace(/\n/g,"\\n"),'"'):A)}},function(A,t,e){"use strict";e.r(t),t.default="data:application/vnd.ms-fontobject;base64,qgoAAOAJAAABAAIAAAAAAAIABQMAAAAAAAABQJABAAAAAExQAAAAABAAAAAAAAAAAAAAAAAAAAEAAAAAYwwKLwAAAAAAAAAAAAAAAAAAAAAAACgAAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQAAAAAAABYAAFYAZQByAHMAaQBvAG4AIAAxAC4AMAAAKAAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AAAAAAABAAAACgCAAAMAIE9TLzJ045CJAAAArAAAAGBjbWFwAA3rsgAAAQwAAAFCZ2x5ZrP154sAAAJQAAADgGhlYWQnIrhJAAAF0AAAADZoaGVhJxwTgAAABggAAAAkaG10eBOI//8AAAYsAAAAHmxvY2EFNAW+AAAGTAAAABxtYXhwARoAVwAABmgAAAAgbmFtZUwtfAwAAAaIAAACpnBvc3TeHIDjAAAJMAAAALAABBOIAZAABQAADGUNrAAAArwMZQ2sAAAJYAD1BQoAAAIABQMAAAAAAAAAAAAAEAAAAAAAAAAAAAAAUGZFZABA6gHqDBOIAAABwhOIAAAAAAABAAAAAAAAAAAAAAAgAAAAAAADAAAAAwAAABwAAQAAAAAAPAADAAEAAAAcAAQAIAAAAAQABAABAADqDP//AADqAf//FgAAAQAAAAAAAAEGAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAOpg9DAAUACwAACQIRCQQRCQEOpvqCBX77ugRG+oL6ggV++7oERg9C+oL6ggE4BEYERgE4+oL6ggE4BEYERgABAAAAAA1uElAABQAACQERCQERBhsHU/d0CIwJxPit/sgIiwiM/scAAgAAAAAP3w9DAAUACwAACQIRCQQRCQEE4gV++oIERvu6BX4Ff/qBBEb7ugRGBX4Ffv7I+7r7uv7IBX4Ffv7I+7r7ugABAAAAAA6mElAABQAACQERCQERDW74rQiL93UJxAdTATn3dPd1ATgAAQAAAAARhw+DAAUAAAkFD8338/v7/kYFvwnHD4P38wQF/kf6QQnGAAEAAAAAERcRFwALAAAJCxEX/e36wPrA/e0FQPrAAhMFQAVAAhP6wASE/e0FQPrAAhMFQAVAAhP6wAVA/e36wAAB//8AABOTEuwAMwAAASIHDgEXFhcBISYHBgcGBwYUFxYXFhcWNyEBBgcGFxYXHgEXFhcWNzY3ATY3NicmJwEuAQpgZU9KRhASSAXX8eBNPjopJxQUFBQnKTo+TQ4g+ik3GhgDAxsZVjU3Oz46PzUH7TsVFRQVPPgTLHQS7Dk0rFlgR/oqARsYLiw5OHg4OSwuGBsC+ik1Pzs+Ojc2VhkaAwMYGTgH7DxRUE9SPAfsLTIAAAADAAAAABEXERcAAwAHAAsAAAERIREBESERAREhEQJxDqbxWg6m8VoOphEX/Y8Ccfnm/Y8Ccfnl/Y8CcQADAAAAABJQDDUAGAAxAEoAAAEiBw4BBwYWFx4BFxYyNz4BNzY0Jy4BJyYhIgcOAQcGFBceARcWMjc+ATc2NCcuAScmISIHDgEHBhQXHgEXFjI3PgE3NjQnLgEnJgOqgHRwrS8yATEvrXB0/3RwrS8yMi+tcHQFm390cK0wMTEwrXB0/nRwrTAxMTCtcHQFnIB0cK0vMTEvrXB0/3RwrS8yMi+tcHQMNTEwrXB0/nRwrTAxMTCtcHT+dHCtMDExMK1wdP50cK0wMTEwrXB0/nRwrTAxMTCtcHT+dHCtMDExMK1wdP50cK0wMQAAAAIAAAAAD98P3wADAAcAAAERIREhESERA6oE4gJxBOIP3/PLDDXzyww1AAAAAQAAAAARFxEXAAIAAAkCAnEOpvFaERf4rfitAAEAAAAADqYMNQACAAAJAgTiBOIE4gw1+x4E4AABAAAAAQAALwoMY18PPPUACxOIAAAAANphmGwAAAAA2hC8bP//AAATkxLsAAAACAACAAAAAAAAAAEAABOIAAAAABOI////9ROTAAEAAAAAAAAAAAAAAAAAAAACAAAAABOIAAAAAAAAAAAAAAAA//8AAAAAAAAAAAAAAAAAAAAAACIANgBYAGwAgACgAPoBGAGOAaQBsgHAAAEAAAANAEsAAwAAAAAAAgAAAAoACgAAAP8AAAAAAAAAAAAQAMYAAQAAAAAAAQAUAAAAAQAAAAAAAgAHABQAAQAAAAAAAwAUABsAAQAAAAAABAAUAC8AAQAAAAAABQALAEMAAQAAAAAABgAUAE4AAQAAAAAACgArAGIAAQAAAAAACwATAI0AAwABBAkAAQAoAKAAAwABBAkAAgAOAMgAAwABBAkAAwAoANYAAwABBAkABAAoAP4AAwABBAkABQAWASYAAwABBAkABgAoATwAAwABBAkACgBWAWQAAwABBAkACwAmAbppY29uZm9udC12dWUtYzBmOTNiOVJlZ3VsYXJpY29uZm9udC12dWUtYzBmOTNiOWljb25mb250LXZ1ZS1jMGY5M2I5VmVyc2lvbiAxLjBpY29uZm9udC12dWUtYzBmOTNiOUdlbmVyYXRlZCBieSBzdmcydHRmIGZyb20gRm9udGVsbG8gcHJvamVjdC5odHRwOi8vZm9udGVsbG8uY29tAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQBSAGUAZwB1AGwAYQByAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQAtAGMAMABmADkAMwBiADkAVgBlAHIAcwBpAG8AbgAgADEALgAwAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQBHAGUAbgBlAHIAYQB0AGUAZAAgAGIAeQAgAHMAdgBnADIAdAB0AGYAIABmAHIAbwBtACAARgBvAG4AdABlAGwAbABvACAAcAByAG8AagBlAGMAdAAuAGgAdAB0AHAAOgAvAC8AZgBvAG4AdABlAGwAbABvAC4AYwBvAG0AAAACAAAAAAAAADIAAAAAAAAAAAAAAAAAAAAAAAAAAAANAA0AAAECAQMBBAEFAQYBBwEIAQkBCgELAQwBDRFhcnJvdy1sZWZ0LWRvdWJsZQphcnJvdy1sZWZ0EmFycm93LXJpZ2h0LWRvdWJsZQthcnJvdy1yaWdodAljaGVja21hcmsFY2xvc2UHY29uZmlybQRtZW51BG1vcmUFcGF1c2UEcGxheQp0cmlhbmdsZS1z"},function(A,t,e){"use strict";e.r(t),t.default="data:font/woff;base64,d09GRgABAAAAAAooAAoAAAAACeAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABPUy8yAAAA9AAAAGAAAABgdOOQiWNtYXAAAAFUAAABQgAAAUIADeuyZ2x5ZgAAApgAAAOAAAADgLP154toZWFkAAAGGAAAADYAAAA2JyK4SWhoZWEAAAZQAAAAJAAAACQnHBOAaG10eAAABnQAAAAeAAAAHhOI//9sb2NhAAAGlAAAABwAAAAcBTQFvm1heHAAAAawAAAAIAAAACABGgBXbmFtZQAABtAAAAKmAAACpkwtfAxwb3N0AAAJeAAAALAAAACw3hyA4wAEE4gBkAAFAAAMZQ2sAAACvAxlDawAAAlgAPUFCgAAAgAFAwAAAAAAAAAAAAAQAAAAAAAAAAAAAABQZkVkAEDqAeoME4gAAAHCE4gAAAAAAAEAAAAAAAAAAAAAACAAAAAAAAMAAAADAAAAHAABAAAAAAA8AAMAAQAAABwABAAgAAAABAAEAAEAAOoM//8AAOoB//8WAAABAAAAAAAAAQYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAA6mD0MABQALAAAJAhEJBBEJAQ6m+oIFfvu6BEb6gvqCBX77ugRGD0L6gvqCATgERgRGATj6gvqCATgERgRGAAEAAAAADW4SUAAFAAAJAREJAREGGwdT93QIjAnE+K3+yAiLCIz+xwACAAAAAA/fD0MABQALAAAJAhEJBBEJAQTiBX76ggRG+7oFfgV/+oEERvu6BEYFfgV+/sj7uvu6/sgFfgV+/sj7uvu6AAEAAAAADqYSUAAFAAAJAREJARENbvitCIv3dQnEB1MBOfd093UBOAABAAAAABGHD4MABQAACQUPzffz+/v+RgW/CccPg/fzBAX+R/pBCcYAAQAAAAARFxEXAAsAAAkLERf97frA+sD97QVA+sACEwVABUACE/rABIT97QVA+sACEwVABUACE/rABUD97frAAAH//wAAE5MS7AAzAAABIgcOARcWFwEhJgcGBwYHBhQXFhcWFxY3IQEGBwYXFhceARcWFxY3NjcBNjc2JyYnAS4BCmBlT0pGEBJIBdfx4E0+OiknFBQUFCcpOj5NDiD6KTcaGAMDGxlWNTc7Pjo/NQftOxUVFBU8+BMsdBLsOTSsWWBH+ioBGxguLDk4eDg5LC4YGwL6KTU/Oz46NzZWGRoDAxgZOAfsPFFQT1I8B+wtMgAAAAMAAAAAERcRFwADAAcACwAAAREhEQERIREBESERAnEOpvFaDqbxWg6mERf9jwJx+eb9jwJx+eX9jwJxAAMAAAAAElAMNQAYADEASgAAASIHDgEHBhYXHgEXFjI3PgE3NjQnLgEnJiEiBw4BBwYUFx4BFxYyNz4BNzY0Jy4BJyYhIgcOAQcGFBceARcWMjc+ATc2NCcuAScmA6qAdHCtLzIBMS+tcHT/dHCtLzIyL61wdAWbf3RwrTAxMTCtcHT+dHCtMDExMK1wdAWcgHRwrS8xMS+tcHT/dHCtLzIyL61wdAw1MTCtcHT+dHCtMDExMK1wdP50cK0wMTEwrXB0/nRwrTAxMTCtcHT+dHCtMDExMK1wdP50cK0wMTEwrXB0/nRwrTAxAAAAAgAAAAAP3w/fAAMABwAAAREhESERIREDqgTiAnEE4g/f88sMNfPLDDUAAAABAAAAABEXERcAAgAACQICcQ6m8VoRF/it+K0AAQAAAAAOpgw1AAIAAAkCBOIE4gTiDDX7HgTgAAEAAAABAAAvCgxjXw889QALE4gAAAAA2mGYbAAAAADaELxs//8AABOTEuwAAAAIAAIAAAAAAAAAAQAAE4gAAAAAE4j////1E5MAAQAAAAAAAAAAAAAAAAAAAAIAAAAAE4gAAAAAAAAAAAAAAAD//wAAAAAAAAAAAAAAAAAAAAAAIgA2AFgAbACAAKAA+gEYAY4BpAGyAcAAAQAAAA0ASwADAAAAAAACAAAACgAKAAAA/wAAAAAAAAAAABAAxgABAAAAAAABABQAAAABAAAAAAACAAcAFAABAAAAAAADABQAGwABAAAAAAAEABQALwABAAAAAAAFAAsAQwABAAAAAAAGABQATgABAAAAAAAKACsAYgABAAAAAAALABMAjQADAAEECQABACgAoAADAAEECQACAA4AyAADAAEECQADACgA1gADAAEECQAEACgA/gADAAEECQAFABYBJgADAAEECQAGACgBPAADAAEECQAKAFYBZAADAAEECQALACYBumljb25mb250LXZ1ZS1jMGY5M2I5UmVndWxhcmljb25mb250LXZ1ZS1jMGY5M2I5aWNvbmZvbnQtdnVlLWMwZjkzYjlWZXJzaW9uIDEuMGljb25mb250LXZ1ZS1jMGY5M2I5R2VuZXJhdGVkIGJ5IHN2ZzJ0dGYgZnJvbSBGb250ZWxsbyBwcm9qZWN0Lmh0dHA6Ly9mb250ZWxsby5jb20AaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AFIAZQBnAHUAbABhAHIAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQBWAGUAcgBzAGkAbwBuACAAMQAuADAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AEcAZQBuAGUAcgBhAHQAZQBkACAAYgB5ACAAcwB2AGcAMgB0AHQAZgAgAGYAcgBvAG0AIABGAG8AbgB0AGUAbABsAG8AIABwAHIAbwBqAGUAYwB0AC4AaAB0AHQAcAA6AC8ALwBmAG8AbgB0AGUAbABsAG8ALgBjAG8AbQAAAAIAAAAAAAAAMgAAAAAAAAAAAAAAAAAAAAAAAAAAAA0ADQAAAQIBAwEEAQUBBgEHAQgBCQEKAQsBDAENEWFycm93LWxlZnQtZG91YmxlCmFycm93LWxlZnQSYXJyb3ctcmlnaHQtZG91YmxlC2Fycm93LXJpZ2h0CWNoZWNrbWFyawVjbG9zZQdjb25maXJtBG1lbnUEbW9yZQVwYXVzZQRwbGF5CnRyaWFuZ2xlLXM="},function(A,t,e){"use strict";e.r(t),t.default="data:font/ttf;base64,AAEAAAAKAIAAAwAgT1MvMnTjkIkAAACsAAAAYGNtYXAADeuyAAABDAAAAUJnbHlms/XniwAAAlAAAAOAaGVhZCciuEkAAAXQAAAANmhoZWEnHBOAAAAGCAAAACRobXR4E4j//wAABiwAAAAebG9jYQU0Bb4AAAZMAAAAHG1heHABGgBXAAAGaAAAACBuYW1lTC18DAAABogAAAKmcG9zdN4cgOMAAAkwAAAAsAAEE4gBkAAFAAAMZQ2sAAACvAxlDawAAAlgAPUFCgAAAgAFAwAAAAAAAAAAAAAQAAAAAAAAAAAAAABQZkVkAEDqAeoME4gAAAHCE4gAAAAAAAEAAAAAAAAAAAAAACAAAAAAAAMAAAADAAAAHAABAAAAAAA8AAMAAQAAABwABAAgAAAABAAEAAEAAOoM//8AAOoB//8WAAABAAAAAAAAAQYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAA6mD0MABQALAAAJAhEJBBEJAQ6m+oIFfvu6BEb6gvqCBX77ugRGD0L6gvqCATgERgRGATj6gvqCATgERgRGAAEAAAAADW4SUAAFAAAJAREJAREGGwdT93QIjAnE+K3+yAiLCIz+xwACAAAAAA/fD0MABQALAAAJAhEJBBEJAQTiBX76ggRG+7oFfgV/+oEERvu6BEYFfgV+/sj7uvu6/sgFfgV+/sj7uvu6AAEAAAAADqYSUAAFAAAJAREJARENbvitCIv3dQnEB1MBOfd093UBOAABAAAAABGHD4MABQAACQUPzffz+/v+RgW/CccPg/fzBAX+R/pBCcYAAQAAAAARFxEXAAsAAAkLERf97frA+sD97QVA+sACEwVABUACE/rABIT97QVA+sACEwVABUACE/rABUD97frAAAH//wAAE5MS7AAzAAABIgcOARcWFwEhJgcGBwYHBhQXFhcWFxY3IQEGBwYXFhceARcWFxY3NjcBNjc2JyYnAS4BCmBlT0pGEBJIBdfx4E0+OiknFBQUFCcpOj5NDiD6KTcaGAMDGxlWNTc7Pjo/NQftOxUVFBU8+BMsdBLsOTSsWWBH+ioBGxguLDk4eDg5LC4YGwL6KTU/Oz46NzZWGRoDAxgZOAfsPFFQT1I8B+wtMgAAAAMAAAAAERcRFwADAAcACwAAAREhEQERIREBESERAnEOpvFaDqbxWg6mERf9jwJx+eb9jwJx+eX9jwJxAAMAAAAAElAMNQAYADEASgAAASIHDgEHBhYXHgEXFjI3PgE3NjQnLgEnJiEiBw4BBwYUFx4BFxYyNz4BNzY0Jy4BJyYhIgcOAQcGFBceARcWMjc+ATc2NCcuAScmA6qAdHCtLzIBMS+tcHT/dHCtLzIyL61wdAWbf3RwrTAxMTCtcHT+dHCtMDExMK1wdAWcgHRwrS8xMS+tcHT/dHCtLzIyL61wdAw1MTCtcHT+dHCtMDExMK1wdP50cK0wMTEwrXB0/nRwrTAxMTCtcHT+dHCtMDExMK1wdP50cK0wMTEwrXB0/nRwrTAxAAAAAgAAAAAP3w/fAAMABwAAAREhESERIREDqgTiAnEE4g/f88sMNfPLDDUAAAABAAAAABEXERcAAgAACQICcQ6m8VoRF/it+K0AAQAAAAAOpgw1AAIAAAkCBOIE4gTiDDX7HgTgAAEAAAABAAAvCgxjXw889QALE4gAAAAA2mGYbAAAAADaELxs//8AABOTEuwAAAAIAAIAAAAAAAAAAQAAE4gAAAAAE4j////1E5MAAQAAAAAAAAAAAAAAAAAAAAIAAAAAE4gAAAAAAAAAAAAAAAD//wAAAAAAAAAAAAAAAAAAAAAAIgA2AFgAbACAAKAA+gEYAY4BpAGyAcAAAQAAAA0ASwADAAAAAAACAAAACgAKAAAA/wAAAAAAAAAAABAAxgABAAAAAAABABQAAAABAAAAAAACAAcAFAABAAAAAAADABQAGwABAAAAAAAEABQALwABAAAAAAAFAAsAQwABAAAAAAAGABQATgABAAAAAAAKACsAYgABAAAAAAALABMAjQADAAEECQABACgAoAADAAEECQACAA4AyAADAAEECQADACgA1gADAAEECQAEACgA/gADAAEECQAFABYBJgADAAEECQAGACgBPAADAAEECQAKAFYBZAADAAEECQALACYBumljb25mb250LXZ1ZS1jMGY5M2I5UmVndWxhcmljb25mb250LXZ1ZS1jMGY5M2I5aWNvbmZvbnQtdnVlLWMwZjkzYjlWZXJzaW9uIDEuMGljb25mb250LXZ1ZS1jMGY5M2I5R2VuZXJhdGVkIGJ5IHN2ZzJ0dGYgZnJvbSBGb250ZWxsbyBwcm9qZWN0Lmh0dHA6Ly9mb250ZWxsby5jb20AaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AFIAZQBnAHUAbABhAHIAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AYwAwAGYAOQAzAGIAOQBWAGUAcgBzAGkAbwBuACAAMQAuADAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBjADAAZgA5ADMAYgA5AEcAZQBuAGUAcgBhAHQAZQBkACAAYgB5ACAAcwB2AGcAMgB0AHQAZgAgAGYAcgBvAG0AIABGAG8AbgB0AGUAbABsAG8AIABwAHIAbwBqAGUAYwB0AC4AaAB0AHQAcAA6AC8ALwBmAG8AbgB0AGUAbABsAG8ALgBjAG8AbQAAAAIAAAAAAAAAMgAAAAAAAAAAAAAAAAAAAAAAAAAAAA0ADQAAAQIBAwEEAQUBBgEHAQgBCQEKAQsBDAENEWFycm93LWxlZnQtZG91YmxlCmFycm93LWxlZnQSYXJyb3ctcmlnaHQtZG91YmxlC2Fycm93LXJpZ2h0CWNoZWNrbWFyawVjbG9zZQdjb25maXJtBG1lbnUEbW9yZQVwYXVzZQRwbGF5CnRyaWFuZ2xlLXM="},function(A,t,e){"use strict";e.r(t),t.default="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgPjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48bWV0YWRhdGE+PC9tZXRhZGF0YT48ZGVmcz48Zm9udCBpZD0iaWNvbmZvbnQtdnVlLWMwZjkzYjkiIGhvcml6LWFkdi14PSI1MDAwIj48Zm9udC1mYWNlIGZvbnQtZmFtaWx5PSJpY29uZm9udC12dWUtYzBmOTNiOSIgZm9udC13ZWlnaHQ9IjQwMCIgZm9udC1zdHJldGNoPSJub3JtYWwiIHVuaXRzLXBlci1lbT0iNTAwMCIgcGFub3NlLTE9IjIgMCA1IDMgMCAwIDAgMCAwIDAiIGFzY2VudD0iNTAwMCIgZGVzY2VudD0iMCIgeC1oZWlnaHQ9IjAiIGJib3g9Ii0xIDAgNTAxMSA0ODQ0IiB1bmRlcmxpbmUtdGhpY2tuZXNzPSIwIiB1bmRlcmxpbmUtcG9zaXRpb249IjUwIiB1bmljb2RlLXJhbmdlPSJVK2VhMDEtZWEwYyIgLz48bWlzc2luZy1nbHlwaCBob3Jpei1hZHYteD0iMCIgIC8+PGdseXBoIGdseXBoLW5hbWU9ImFycm93LWxlZnQtZG91YmxlIiB1bmljb2RlPSImI3hlYTAxOyIgZD0iTTM3NTAgMzkwNiBsLTE0MDYgLTE0MDYgbDE0MDYgLTE0MDYgbDAgMzEyIGwtMTA5NCAxMDk0IGwxMDk0IDEwOTQgbDAgMzEyIFpNMjM0NCAzOTA2IGwtMTQwNiAtMTQwNiBsMTQwNiAtMTQwNiBsMCAzMTIgbC0xMDk0IDEwOTQgbDEwOTQgMTA5NCBsMCAzMTIgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0iYXJyb3ctbGVmdCIgdW5pY29kZT0iJiN4ZWEwMjsiIGQ9Ik0xNTYzIDI1MDAgbDE4NzUgLTE4NzUgbDAgLTMxMiBsLTIxODggMjE4NyBsMjE4OCAyMTg4IGwwIC0zMTMgbC0xODc1IC0xODc1IFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImFycm93LXJpZ2h0LWRvdWJsZSIgdW5pY29kZT0iJiN4ZWEwMzsiIGQ9Ik0xMjUwIDEwOTQgbDE0MDYgMTQwNiBsLTE0MDYgMTQwNiBsMCAtMzEyIGwxMDk0IC0xMDk0IGwtMTA5NCAtMTA5NCBsMCAtMzEyIFpNMjY1NiAxMDk0IGwxNDA3IDE0MDYgbC0xNDA3IDE0MDYgbDAgLTMxMiBsMTA5NCAtMTA5NCBsLTEwOTQgLTEwOTQgbDAgLTMxMiBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJhcnJvdy1yaWdodCIgdW5pY29kZT0iJiN4ZWEwNDsiIGQ9Ik0zNDM4IDI1MDAgbC0xODc1IDE4NzUgbDAgMzEzIGwyMTg3IC0yMTg4IGwtMjE4NyAtMjE4NyBsMCAzMTIgbDE4NzUgMTg3NSBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJjaGVja21hcmsiIHVuaWNvZGU9IiYjeGVhMDU7IiBkPSJNNDA0NSAzOTcxIGwtMjA2MSAtMjA2MSBsLTEwMjkgMTAyOSBsLTQ0MiAtNDQxIGwxNDcxIC0xNDcxIGwyNTAzIDI1MDIgbC00NDIgNDQyIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImNsb3NlIiB1bmljb2RlPSImI3hlYTA2OyIgZD0iTTQzNzUgMTE1NiBsLTUzMSAtNTMxIGwtMTM0NCAxMzQ0IGwtMTM0NCAtMTM0NCBsLTUzMSA1MzEgbDEzNDQgMTM0NCBsLTEzNDQgMTM0NCBsNTMxIDUzMSBsMTM0NCAtMTM0NCBsMTM0NCAxMzQ0IGw1MzEgLTUzMSBsLTEzNDQgLTEzNDQgbDEzNDQgLTEzNDQgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0iY29uZmlybSIgdW5pY29kZT0iJiN4ZWEwNzsiIGQ9Ik0yNjU2IDQ4NDQgcS0xMDEgMCAtMTgwIC01NyBxLTc0IC01MiAtMTA5IC0xMzggcS0zNSAtODYgLTE5IC0xNzUgcTE4IC05NiA5MCAtMTY3IGwxNDk1IC0xNDk0IGwtMzYxNiAwIHEtNzcgMSAtMTM5IC0yNiBxLTU4IC0yNCAtOTkgLTcwIHEtMzkgLTQ0IC01OSAtMTAxIHEtMjAgLTU2IC0yMCAtMTE2IHEwIC02MCAyMCAtMTE2IHEyMCAtNTcgNTkgLTEwMSBxNDEgLTQ2IDk5IC03MCBxNjIgLTI3IDEzOSAtMjUgbDM2MTYgMCBsLTE0OTUgLTE0OTUgcS01NSAtNTMgLTgxIC0xMTYgcS0yNCAtNTkgLTIxIC0xMjEgcTMgLTU4IDMwIC0xMTMgcTI1IC01NCA2OCAtOTcgcTQzIC00MyA5NiAtNjggcTU1IC0yNiAxMTQgLTI5IHE2MiAtMyAxMjAgMjEgcTYzIDI1IDExNiA4MSBsMjAyOSAyMDI4IHE1OSA2MCA4MCAxNDEgcTIxIDgwIDEgMTU5IHEtMjEgODIgLTgxIDE0MiBsLTIwMjkgMjAyOCBxLTQ0IDQ1IC0xMDIgNzAgcS01OCAyNSAtMTIyIDI1IFoiIC8+PGdseXBoIGdseXBoLW5hbWU9Im1lbnUiIHVuaWNvZGU9IiYjeGVhMDg7IiBkPSJNNjI1IDQzNzUgbDAgLTYyNSBsMzc1MCAwIGwwIDYyNSBsLTM3NTAgMCBaTTYyNSAyODEzIGwwIC02MjUgbDM3NTAgMCBsMCA2MjUgbC0zNzUwIDAgWk02MjUgMTI1MCBsMCAtNjI1IGwzNzUwIDAgbDAgNjI1IGwtMzc1MCAwIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9Im1vcmUiIHVuaWNvZGU9IiYjeGVhMDk7IiBkPSJNOTM4IDMxMjUgcS0xMjggMCAtMjQ0IC00OSBxLTExMiAtNDggLTE5OC41IC0xMzQuNSBxLTg2LjUgLTg2LjUgLTEzMy41IC0xOTguNSBxLTUwIC0xMTYgLTQ5LjUgLTI0MyBxMC41IC0xMjcgNDkuNSAtMjQzIHE0NyAtMTEyIDEzMy41IC0xOTguNSBxODYuNSAtODYuNSAxOTguNSAtMTM0LjUgcTExNiAtNDkgMjQzLjUgLTQ5IHExMjcuNSAwIDI0My41IDQ5IHExMTIgNDggMTk4LjUgMTM0LjUgcTg2LjUgODYuNSAxMzMuNSAxOTguNSBxNTAgMTE2IDUwIDI0MyBxMCAxMjcgLTUwIDI0MyBxLTQ3IDExMiAtMTMzLjUgMTk4LjUgcS04Ni41IDg2LjUgLTE5OC41IDEzNC41IHEtMTE2IDQ5IC0yNDMgNDkgWk0yNTAwIDMxMjUgcS0xMjcgMCAtMjQzIC00OSBxLTExMiAtNDggLTE5OC41IC0xMzQuNSBxLTg2LjUgLTg2LjUgLTEzNC41IC0xOTguNSBxLTQ5IC0xMTYgLTQ5IC0yNDMgcTAgLTEyNyA0OSAtMjQzIHE0OCAtMTEyIDEzNC41IC0xOTguNSBxODYuNSAtODYuNSAxOTguNSAtMTM0LjUgcTExNiAtNDkgMjQzIC00OSBxMTI3IDAgMjQzIDQ5IHExMTIgNDggMTk4LjUgMTM0LjUgcTg2LjUgODYuNSAxMzQuNSAxOTguNSBxNDkgMTE2IDQ5IDI0MyBxMCAxMjcgLTQ5IDI0MyBxLTQ4IDExMiAtMTM0LjUgMTk4LjUgcS04Ni41IDg2LjUgLTE5OC41IDEzNC41IHEtMTE2IDQ5IC0yNDMgNDkgWk00MDYzIDMxMjUgcS0xMjggMCAtMjQ0IC00OSBxLTExMiAtNDggLTE5OC41IC0xMzQuNSBxLTg2LjUgLTg2LjUgLTEzMy41IC0xOTguNSBxLTQ5IC0xMTYgLTQ5IC0yNDMgcTAgLTEyNyA0OSAtMjQzIHE0NyAtMTEyIDEzMy41IC0xOTguNSBxODYuNSAtODYuNSAxOTguNSAtMTM0LjUgcTExNiAtNDkgMjQzLjUgLTQ5IHExMjcuNSAwIDI0My41IDQ5IHExMTIgNDggMTk4LjUgMTM0LjUgcTg2LjUgODYuNSAxMzMuNSAxOTguNSBxNTAgMTE2IDUwIDI0MyBxMCAxMjcgLTUwIDI0MyBxLTQ3IDExMiAtMTMzLjUgMTk4LjUgcS04Ni41IDg2LjUgLTE5OC41IDEzNC41IHEtMTE2IDQ5IC0yNDMgNDkgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0icGF1c2UiIHVuaWNvZGU9IiYjeGVhMGE7IiBkPSJNOTM4IDQwNjMgbDAgLTMxMjUgbDEyNTAgMCBsMCAzMTI1IGwtMTI1MCAwIFpNMjgxMyA0MDYzIGwwIC0zMTI1IGwxMjUwIDAgbDAgMzEyNSBsLTEyNTAgMCBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJwbGF5IiB1bmljb2RlPSImI3hlYTBiOyIgZD0iTTYyNSA0Mzc1IGwzNzUwIC0xODc1IGwtMzc1MCAtMTg3NSBsMCAzNzUwIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9InRyaWFuZ2xlLXMiIHVuaWNvZGU9IiYjeGVhMGM7IiBkPSJNMTI1MCAzMTI1IGwxMjUwIC0xMjUwIGwxMjUwIDEyNDggbC0yNTAwIDIgWiIgLz48L2ZvbnQ+PC9kZWZzPjwvc3ZnPg=="},function(A,t,e){"use strict";e.r(t);var i=e(4);e(37);
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
i.VTooltip.options.defaultTemplate='<div class="vue-tooltip" role="tooltip" data-v-'.concat("c0f93b9",'><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'),i.VTooltip.options.defaultHtml=!1,t.default=i.VTooltip},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.regexp.exec */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.regexp.exec.js")},function(A,t){A.exports=__webpack_require__(/*! v-click-outside */ "./node_modules/v-click-outside/dist/v-click-outside.umd.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.iterator.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.string.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.iterator.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/web.dom-collections.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/web.dom-collections.iterator.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.index-of */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.index-of.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.regexp.to-string */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.regexp.to-string.js")},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/web.url */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/web.url.js")},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.string.replace */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.replace.js")},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.concat */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.concat.js")},function(A,t,e){var i=e(63);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("6d914181",i,!0,{})},function(A,t,e){var i=e(65);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("2fc216d3",i,!0,{})},function(A,t,e){var i=e(67);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("b619cfa6",i,!0,{})},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.number.constructor */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.number.constructor.js")},,function(A,t,e){var i=e(80);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("9be9793c",i,!0,{})},function(A,t){},function(A,t,e){"use strict";
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 */t.a=function(A){A.mounted?Array.isArray(A.mounted)||(A.mounted=[A.mounted]):A.mounted=[],A.mounted.push(function(){this.$el.setAttribute("data-v-".concat("c0f93b9"),"")})}},,function(A,t){A.exports=__webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js")},function(A,t,e){var i=e(38);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("941c791e",i,!0,{})},function(A,t,e){(t=e(1)(!1)).push([A.i,".vue-tooltip[data-v-c0f93b9]{position:absolute;z-index:100000;right:auto;left:auto;display:block;margin:0;margin-top:-3px;padding:10px 0;text-align:left;text-align:start;white-space:normal;text-decoration:none;letter-spacing:normal;word-spacing:normal;text-transform:none;word-wrap:normal;word-break:normal;opacity:0;text-shadow:none;font-family:'Nunito', 'Open Sans', Frutiger, Calibri, 'Myriad Pro', Myriad, sans-serif;font-size:12px;font-weight:normal;font-style:normal;line-height:1.6;line-break:auto;filter:drop-shadow(0 1px 10px var(--color-box-shadow))}.vue-tooltip[data-v-c0f93b9][x-placement^='top'] .tooltip-arrow{bottom:0;margin-top:0;margin-bottom:0;border-width:10px 10px 0 10px;border-right-color:transparent;border-bottom-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-c0f93b9][x-placement^='bottom'] .tooltip-arrow{top:0;margin-top:0;margin-bottom:0;border-width:0 10px 10px 10px;border-top-color:transparent;border-right-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-c0f93b9][x-placement^='right'] .tooltip-arrow{right:100%;margin-right:0;margin-left:0;border-width:10px 10px 10px 0;border-top-color:transparent;border-bottom-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-c0f93b9][x-placement^='left'] .tooltip-arrow{left:100%;margin-right:0;margin-left:0;border-width:10px 0 10px 10px;border-top-color:transparent;border-right-color:transparent;border-bottom-color:transparent}.vue-tooltip[data-v-c0f93b9][aria-hidden='true']{visibility:hidden;transition:opacity .15s, visibility .15s;opacity:0}.vue-tooltip[data-v-c0f93b9][aria-hidden='false']{visibility:visible;transition:opacity .15s;opacity:1}.vue-tooltip[data-v-c0f93b9] .tooltip-inner{max-width:350px;padding:5px 8px;text-align:center;color:var(--color-main-text);border-radius:var(--border-radius);background-color:var(--color-main-background)}.vue-tooltip[data-v-c0f93b9] .tooltip-arrow{position:absolute;z-index:1;width:0;height:0;margin:0;border-style:solid;border-color:var(--color-main-background)}\n",""]),A.exports=t},,,function(A,t,e){"use strict";e(26);
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
function i(A,t,e){this.r=A,this.g=t,this.b=e}function a(A,t,e){var a=[];a.push(t);for(var n=function(A,t){var e=new Array(3);return e[0]=(t[1].r-t[0].r)/A,e[1]=(t[1].g-t[0].g)/A,e[2]=(t[1].b-t[0].b)/A,e}(A,[t,e]),o=1;o<A;o++){var r=parseInt(t.r+n[0]*o,10),s=parseInt(t.g+n[1]*o,10),c=parseInt(t.b+n[2]*o,10);a.push(new i(r,s,c))}return a}t.a=function(A){A||(A=6);var t=new i(182,70,157),e=new i(221,203,85),n=new i(0,130,201),o=a(A,t,e),r=a(A,e,n),s=a(A,n,t);return o.concat(r).concat(s)}},,function(A,t){},function(A,t,e){"use strict";e.r(t);e(19),e(16),e(3),e(20),e(17),e(18),e(22);var i={name:"PopoverMenuItem",props:{item:{type:Object,required:!0,default:function(){return{key:"nextcloud-link",href:"https://nextcloud.com",icon:"icon-links",text:"Nextcloud"}},validator:function(A){return!A.input||-1!==["text","checkbox"].indexOf(A.input)}}},computed:{key:function(){return this.item.key?this.item.key:Math.round(16*Math.random()*1e6).toString(16)},iconIsUrl:function(){try{return new URL(this.item.icon),!0}catch(A){return!1}}},methods:{action:function(A){this.item.action&&this.item.action(A)}}},a=(e(62),e(64),e(0)),n={name:"PopoverMenu",components:{PopoverMenuItem:Object(a.a)(i,function(){var A=this,t=A.$createElement,e=A._self._c||t;return e("li",[A.item.href?e("a",{staticClass:"focusable",attrs:{href:A.item.href?A.item.href:"#",target:A.item.target?A.item.target:"",download:A.item.download,rel:"noreferrer noopener"},on:{click:A.action}},[A.iconIsUrl?e("img",{attrs:{src:A.item.icon}}):e("span",{class:A.item.icon}),A._v(" "),A.item.text&&A.item.longtext?e("p",[e("strong",{staticClass:"menuitem-text"},[A._v("\n\t\t\t\t"+A._s(A.item.text)+"\n\t\t\t")]),e("br"),A._v(" "),e("span",{staticClass:"menuitem-text-detail"},[A._v("\n\t\t\t\t"+A._s(A.item.longtext)+"\n\t\t\t")])]):A.item.text?e("span",[A._v("\n\t\t\t"+A._s(A.item.text)+"\n\t\t")]):A.item.longtext?e("p",[A._v("\n\t\t\t"+A._s(A.item.longtext)+"\n\t\t")]):A._e()]):A.item.input?e("span",{staticClass:"menuitem",class:{active:A.item.active}},["checkbox"!==A.item.input?e("span",{class:A.item.icon}):A._e(),A._v(" "),"text"===A.item.input?e("form",{class:A.item.input,on:{submit:function(t){return t.preventDefault(),A.item.action(t)}}},[e("input",{attrs:{type:A.item.input,placeholder:A.item.text,required:""},domProps:{value:A.item.value}}),A._v(" "),e("input",{staticClass:"icon-confirm",attrs:{type:"submit",value:""}})]):["checkbox"===A.item.input?e("input",{directives:[{name:"model",rawName:"v-model",value:A.item.model,expression:"item.model"}],class:A.item.input,attrs:{id:A.key,type:"checkbox"},domProps:{checked:Array.isArray(A.item.model)?A._i(A.item.model,null)>-1:A.item.model},on:{change:[function(t){var e=A.item.model,i=t.target,a=!!i.checked;if(Array.isArray(e)){var n=A._i(e,null);i.checked?n<0&&A.$set(A.item,"model",e.concat([null])):n>-1&&A.$set(A.item,"model",e.slice(0,n).concat(e.slice(n+1)))}else A.$set(A.item,"model",a)},A.item.action]}}):"radio"===A.item.input?e("input",{directives:[{name:"model",rawName:"v-model",value:A.item.model,expression:"item.model"}],class:A.item.input,attrs:{id:A.key,type:"radio"},domProps:{checked:A._q(A.item.model,null)},on:{change:[function(t){return A.$set(A.item,"model",null)},A.item.action]}}):e("input",{directives:[{name:"model",rawName:"v-model",value:A.item.model,expression:"item.model"}],class:A.item.input,attrs:{id:A.key,type:A.item.input},domProps:{value:A.item.model},on:{change:A.item.action,input:function(t){t.target.composing||A.$set(A.item,"model",t.target.value)}}}),A._v(" "),e("label",{attrs:{for:A.key},on:{click:function(t){return t.stopPropagation(),t.preventDefault(),A.item.action(t)}}},[A._v("\n\t\t\t\t"+A._s(A.item.text)+"\n\t\t\t")])]],2):A.item.action?e("button",{staticClass:"menuitem focusable",class:{active:A.item.active},attrs:{disabled:A.item.disabled},on:{click:function(t){return t.stopPropagation(),t.preventDefault(),A.item.action(t)}}},[e("span",{class:A.item.icon}),A._v(" "),A.item.text&&A.item.longtext?e("p",[e("strong",{staticClass:"menuitem-text"},[A._v("\n\t\t\t\t"+A._s(A.item.text)+"\n\t\t\t")]),e("br"),A._v(" "),e("span",{staticClass:"menuitem-text-detail"},[A._v("\n\t\t\t\t"+A._s(A.item.longtext)+"\n\t\t\t")])]):A.item.text?e("span",[A._v("\n\t\t\t"+A._s(A.item.text)+"\n\t\t")]):A.item.longtext?e("p",[A._v("\n\t\t\t"+A._s(A.item.longtext)+"\n\t\t")]):A._e()]):e("span",{staticClass:"menuitem",class:{active:A.item.active}},[e("span",{class:A.item.icon}),A._v(" "),A.item.text&&A.item.longtext?e("p",[e("strong",{staticClass:"menuitem-text"},[A._v("\n\t\t\t\t"+A._s(A.item.text)+"\n\t\t\t")]),e("br"),A._v(" "),e("span",{staticClass:"menuitem-text-detail"},[A._v("\n\t\t\t\t"+A._s(A.item.longtext)+"\n\t\t\t")])]):A.item.text?e("span",[A._v("\n\t\t\t"+A._s(A.item.text)+"\n\t\t")]):A.item.longtext?e("p",[A._v("\n\t\t\t"+A._s(A.item.longtext)+"\n\t\t")]):A._e()])])},[],!1,null,"8dc4efb0",null).exports},props:{menu:{type:Array,default:function(){return[{href:"https://nextcloud.com",icon:"icon-links",text:"Nextcloud"}]},required:!0}}},o=(e(66),e(33)),r=e.n(o),s=Object(a.a)(n,function(){var A=this.$createElement,t=this._self._c||A;return t("ul",this._l(this.menu,function(A,e){return t("PopoverMenuItem",{key:e,attrs:{item:A}})}),1)},[],!1,null,"769d0d8a",null);"function"==typeof r.a&&r()(s);var c=s.exports;e.d(t,"PopoverMenu",function(){return c});
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 */t.default=c},function(A,t){A.exports=__webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.map */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.map.js")},function(A,t,e){"use strict";e.r(t);e(26),e(61),e(46),e(30),e(3),e(59),e(60);var i=e(15),a=e(44),n=e(45),o=e.n(n),r=e(12),s=(e(14),e(78),e(24),e(57)),c=e.n(s),l=e(41),d=function(A){var t=A.toLowerCase();null===t.match(/^([0-9a-f]{4}-?){8}$/)&&(t=c()(t)),t=t.replace(/[^0-9a-f]/g,"");return Object(l.a)(6)[function(A,t){for(var e=0,i=[],a=0;a<A.length;a++)i.push(parseInt(A.charAt(a),16)%16);for(var n in i)e+=i[n];return parseInt(parseInt(e,10)%t,10)}(t,18)]};function u(A,t,e,i,a,n,o){try{var r=A[n](o),s=r.value}catch(A){return void e(A)}r.done?t(s):Promise.resolve(s).then(i,a)}var p={name:"Avatar",directives:{tooltip:r.default,ClickOutside:i.directive},components:{PopoverMenu:a.PopoverMenu},props:{url:{type:String,default:void 0},iconClass:{type:String,default:void 0},user:{type:String,default:void 0},isGuest:{type:Boolean,default:!1},displayName:{type:String,default:void 0},size:{type:Number,default:32},allowPlaceholder:{type:Boolean,default:!0},disableTooltip:{type:Boolean,default:!1},disableMenu:{type:Boolean,default:!1},tooltipMessage:{type:String,default:null},isNoUser:{type:Boolean,default:!1},status:{type:String,default:null,validator:function(A){switch(A){case"positive":case"negative":case"neutral":return!0}return!1}},statusColor:{type:[Number,String],default:null,validator:function(A){return/^([a-f0-9]{3}){1,2}$/i.test(A)}},menuPosition:{type:String,default:"center"}},data:function(){return{avatarUrlLoaded:null,avatarSrcSetLoaded:null,userDoesNotExist:!1,isAvatarLoaded:!1,isMenuLoaded:!1,contactsMenuActions:[],contactsMenuOpenState:!1}},computed:{getUserIdentifier:function(){return this.isDisplayNameDefined?this.displayName:this.isUserDefined?this.user:""},isUserDefined:function(){return void 0!==this.user},isDisplayNameDefined:function(){return void 0!==this.displayName},isUrlDefined:function(){return void 0!==this.url},hasMenu:function(){return!this.disableMenu&&(this.isMenuLoaded?this.menu.length>0:!(this.user===OC.getCurrentUser().uid||this.userDoesNotExist||this.url))},shouldShowPlaceholder:function(){return this.allowPlaceholder&&this.userDoesNotExist},avatarStyle:function(){var A={width:this.size+"px",height:this.size+"px",lineHeight:this.size+"px",fontSize:Math.round(.55*this.size)+"px"};if(!this.iconClass&&!this.avatarSrcSetLoaded){var t=d(this.getUserIdentifier);A.backgroundColor="rgb("+t.r+", "+t.g+", "+t.b+")"}return A},tooltip:function(){return!this.disableTooltip&&(this.tooltipMessage?this.tooltipMessage:this.displayName)},initials:function(){return this.shouldShowPlaceholder?this.getUserIdentifier.charAt(0).toUpperCase():"?"},menu:function(){return this.contactsMenuActions.map(function(A){return{href:A.hyperlink,icon:A.icon,text:A.title}})}},watch:{url:function(){this.userDoesNotExist=!1,this.loadAvatarUrl()},user:function(){this.userDoesNotExist=!1,this.isMenuLoaded=!1,this.loadAvatarUrl()}},mounted:function(){this.loadAvatarUrl()},methods:{toggleMenu:function(){this.hasMenu&&(this.contactsMenuOpenState=!this.contactsMenuOpenState,this.contactsMenuOpenState&&this.fetchContactsMenu())},closeMenu:function(){this.contactsMenuOpenState=!1},fetchContactsMenu:function(){var A,t=this;return(A=regeneratorRuntime.mark(function A(){var e,i,a;return regeneratorRuntime.wrap(function(A){for(;;)switch(A.prev=A.next){case 0:return A.prev=0,e=encodeURIComponent(t.user),A.next=4,o.a.post(OC.generateUrl("contactsmenu/findOne"),"shareType=0&shareWith=".concat(e));case 4:i=A.sent,a=i.data,t.contactsMenuActions=[a.topAction].concat(a.actions),A.next=12;break;case 9:A.prev=9,A.t0=A.catch(0),t.contactsMenuOpenState=!1;case 12:t.isMenuLoaded=!0;case 13:case"end":return A.stop()}},A,null,[[0,9]])}),function(){var t=this,e=arguments;return new Promise(function(i,a){var n=A.apply(t,e);function o(A){u(n,i,a,o,r,"next",A)}function r(A){u(n,i,a,o,r,"throw",A)}o(void 0)})})()},loadAvatarUrl:function(){var A=this;if(this.isAvatarLoaded=!1,!this.isUrlDefined&&(!this.isUserDefined||this.isNoUser))return this.isAvatarLoaded=!0,void(this.userDoesNotExist=!0);var t=function(t,e){var i="/avatar/{user}/{size}";A.isGuest&&(i="/avatar/guest/{user}/{size}");var a=OC.generateUrl(i,{user:t,size:e});return t===OC.getCurrentUser().uid&&"undefined"!=typeof oc_userconfig&&(a+="?v="+oc_userconfig.avatar.version),a},e=t(this.user,this.size);this.isUrlDefined&&(e=this.url);var i=[e+" 1x",t(this.user,2*this.size)+" 2x",t(this.user,4*this.size)+" 4x"].join(", "),a=new Image;a.onload=function(){A.avatarUrlLoaded=e,A.isUrlDefined||(A.avatarSrcSetLoaded=i),A.isAvatarLoaded=!0},a.onerror=function(){A.userDoesNotExist=!0,A.isAvatarLoaded=!0},this.isUrlDefined||(a.srcset=i),a.src=e}}},g=(e(79),e(0)),f=e(43),m=e.n(f),v=Object(g.a)(p,function(){var A=this,t=A.$createElement,e=A._self._c||t;return e("div",{directives:[{name:"tooltip",rawName:"v-tooltip",value:A.tooltip,expression:"tooltip"},{name:"click-outside",rawName:"v-click-outside",value:A.closeMenu,expression:"closeMenu"}],staticClass:"avatardiv popovermenu-wrapper",class:{"icon-loading":!A.isAvatarLoaded&&A.size>16,"icon-loading-small":!A.isAvatarLoaded&&A.size<=16,"avatardiv--unknown":A.userDoesNotExist,"avatardiv--with-menu":A.hasMenu},style:A.avatarStyle,on:{click:A.toggleMenu}},[A.iconClass?e("div",{staticClass:"avatar-class-icon",class:A.iconClass}):A.isAvatarLoaded&&!A.userDoesNotExist?e("img",{attrs:{src:A.avatarUrlLoaded,srcset:A.avatarSrcSetLoaded}}):A._e(),A._v(" "),A.hasMenu?e("div",{staticClass:"icon-more"}):A._e(),A._v(" "),A.status?e("div",{staticClass:"avatardiv__status",class:"avatardiv__status--"+A.status,style:{backgroundColor:"#"+A.statusColor}},["neutral"===A.status?e("svg",{attrs:{xmlns:"http://www.w3.org/2000/svg",width:"12",height:"11",viewBox:"0 0 3.175 2.91"}},[e("path",{style:{fill:"#"+A.statusColor},attrs:{d:"M3.21 3.043H.494l.679-1.177.68-1.176.678 1.176z",stroke:"#fff","stroke-width":".265","stroke-linecap":"square"}})]):A._e()]):A._e(),A._v(" "),A.userDoesNotExist?e("div",{staticClass:"unknown"},[A._v("\n\t\t"+A._s(A.initials)+"\n\t")]):A._e(),A._v(" "),A.hasMenu?e("div",{directives:[{name:"show",rawName:"v-show",value:A.contactsMenuOpenState,expression:"contactsMenuOpenState"}],staticClass:"popovermenu",class:"menu-"+A.menuPosition},[e("PopoverMenu",{attrs:{"is-open":A.contactsMenuOpenState,menu:A.menu}})],1):A._e()])},[],!1,null,"27e1af54",null);"function"==typeof m.a&&m()(v);var b=v.exports;e.d(t,"Avatar",function(){return b});
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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
 */t.default=b},,,,,,function(A,t,e){var i=e(141);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("05387ef8",i,!0,{})},function(A,t,e){var i=e(143);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("a375d0ac",i,!0,{})},,,function(A,t){A.exports=__webpack_require__(/*! md5 */ "./node_modules/md5/md5.js")},,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.promise */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.promise.js")},function(A,t){A.exports=__webpack_require__(/*! regenerator-runtime/runtime */ "./node_modules/regenerator-runtime/runtime.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.join */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.join.js")},function(A,t,e){"use strict";var i=e(27);e.n(i).a},function(A,t,e){(t=e(1)(!1)).push([A.i,"\nbutton.menuitem[data-v-8dc4efb0] {\n\ttext-align: left;\n}\nbutton.menuitem *[data-v-8dc4efb0] {\n\tcursor: pointer;\n}\nbutton.menuitem[data-v-8dc4efb0]:disabled {\n\topacity: 0.5 !important;\n\tcursor: default;\n}\nbutton.menuitem:disabled *[data-v-8dc4efb0] {\n\tcursor: default;\n}\n.menuitem.active[data-v-8dc4efb0] {\n\tbox-shadow: inset 2px 0 var(--color-primary);\n\tborder-radius: 0;\n}\n",""]),A.exports=t},function(A,t,e){"use strict";var i=e(28);e.n(i).a},function(A,t,e){(t=e(1)(!1)).push([A.i,"li[data-v-8dc4efb0]{display:flex;flex:0 0 auto}li.hidden[data-v-8dc4efb0]{display:none}li>button[data-v-8dc4efb0],li>a[data-v-8dc4efb0],li>.menuitem[data-v-8dc4efb0]{cursor:pointer;line-height:44px;border:0;border-radius:0;background-color:transparent;display:flex;align-items:flex-start;height:auto;margin:0;padding:0;font-weight:normal;box-shadow:none;width:100%;color:var(--color-main-text);white-space:nowrap;opacity:.7}li>button span[class^='icon-'][data-v-8dc4efb0],li>button span[class*=' icon-'][data-v-8dc4efb0],li>button[class^='icon-'][data-v-8dc4efb0],li>button[class*=' icon-'][data-v-8dc4efb0],li>a span[class^='icon-'][data-v-8dc4efb0],li>a span[class*=' icon-'][data-v-8dc4efb0],li>a[class^='icon-'][data-v-8dc4efb0],li>a[class*=' icon-'][data-v-8dc4efb0],li>.menuitem span[class^='icon-'][data-v-8dc4efb0],li>.menuitem span[class*=' icon-'][data-v-8dc4efb0],li>.menuitem[class^='icon-'][data-v-8dc4efb0],li>.menuitem[class*=' icon-'][data-v-8dc4efb0]{min-width:0;min-height:0;background-position:14px center;background-size:16px}li>button span[class^='icon-'][data-v-8dc4efb0],li>button span[class*=' icon-'][data-v-8dc4efb0],li>a span[class^='icon-'][data-v-8dc4efb0],li>a span[class*=' icon-'][data-v-8dc4efb0],li>.menuitem span[class^='icon-'][data-v-8dc4efb0],li>.menuitem span[class*=' icon-'][data-v-8dc4efb0]{padding:22px 0 22px 44px}li>button:not([class^='icon-']):not([class*='icon-'])>span[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>button:not([class^='icon-']):not([class*='icon-'])>input[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>button:not([class^='icon-']):not([class*='icon-'])>form[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>a:not([class^='icon-']):not([class*='icon-'])>span[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>a:not([class^='icon-']):not([class*='icon-'])>input[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>a:not([class^='icon-']):not([class*='icon-'])>form[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>.menuitem:not([class^='icon-']):not([class*='icon-'])>span[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>.menuitem:not([class^='icon-']):not([class*='icon-'])>input[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child,li>.menuitem:not([class^='icon-']):not([class*='icon-'])>form[data-v-8dc4efb0]:not([class^='icon-']):not([class*='icon-']):first-child{margin-left:44px}li>button[class^='icon-'][data-v-8dc4efb0],li>button[class*=' icon-'][data-v-8dc4efb0],li>a[class^='icon-'][data-v-8dc4efb0],li>a[class*=' icon-'][data-v-8dc4efb0],li>.menuitem[class^='icon-'][data-v-8dc4efb0],li>.menuitem[class*=' icon-'][data-v-8dc4efb0]{padding:0 14px 0 44px}li>button[data-v-8dc4efb0]:not(:disabled):hover,li>button[data-v-8dc4efb0]:not(:disabled):focus,li>button:not(:disabled).active[data-v-8dc4efb0],li>a[data-v-8dc4efb0]:not(:disabled):hover,li>a[data-v-8dc4efb0]:not(:disabled):focus,li>a:not(:disabled).active[data-v-8dc4efb0],li>.menuitem[data-v-8dc4efb0]:not(:disabled):hover,li>.menuitem[data-v-8dc4efb0]:not(:disabled):focus,li>.menuitem:not(:disabled).active[data-v-8dc4efb0]{opacity:1 !important}li>button.action[data-v-8dc4efb0],li>a.action[data-v-8dc4efb0],li>.menuitem.action[data-v-8dc4efb0]{padding:inherit !important}li>button>span[data-v-8dc4efb0],li>a>span[data-v-8dc4efb0],li>.menuitem>span[data-v-8dc4efb0]{cursor:pointer;white-space:nowrap}li>button>p[data-v-8dc4efb0],li>a>p[data-v-8dc4efb0],li>.menuitem>p[data-v-8dc4efb0]{width:150px;line-height:1.6em;padding:8px 0;white-space:normal}li>button>select[data-v-8dc4efb0],li>a>select[data-v-8dc4efb0],li>.menuitem>select[data-v-8dc4efb0]{margin:0;margin-left:6px}li>button[data-v-8dc4efb0]:not(:empty),li>a[data-v-8dc4efb0]:not(:empty),li>.menuitem[data-v-8dc4efb0]:not(:empty){padding-right:14px !important}li>button>img[data-v-8dc4efb0],li>a>img[data-v-8dc4efb0],li>.menuitem>img[data-v-8dc4efb0]{width:16px;padding:14px}li>button>input.radio+label[data-v-8dc4efb0],li>button>input.checkbox+label[data-v-8dc4efb0],li>a>input.radio+label[data-v-8dc4efb0],li>a>input.checkbox+label[data-v-8dc4efb0],li>.menuitem>input.radio+label[data-v-8dc4efb0],li>.menuitem>input.checkbox+label[data-v-8dc4efb0]{padding:0 !important;width:100%}li>button>input.checkbox+label[data-v-8dc4efb0]::before,li>a>input.checkbox+label[data-v-8dc4efb0]::before,li>.menuitem>input.checkbox+label[data-v-8dc4efb0]::before{margin:-2px 13px 0}li>button>input.radio+label[data-v-8dc4efb0]::before,li>a>input.radio+label[data-v-8dc4efb0]::before,li>.menuitem>input.radio+label[data-v-8dc4efb0]::before{margin:-2px 12px 0}li>button>input[data-v-8dc4efb0]:not([type=radio]):not([type=checkbox]):not([type=image]),li>a>input[data-v-8dc4efb0]:not([type=radio]):not([type=checkbox]):not([type=image]),li>.menuitem>input[data-v-8dc4efb0]:not([type=radio]):not([type=checkbox]):not([type=image]){width:150px}li>button form[data-v-8dc4efb0],li>a form[data-v-8dc4efb0],li>.menuitem form[data-v-8dc4efb0]{display:flex;flex:1 1 auto}li>button form[data-v-8dc4efb0]:not(:first-child),li>a form[data-v-8dc4efb0]:not(:first-child),li>.menuitem form[data-v-8dc4efb0]:not(:first-child){margin-left:5px}li>button>span.hidden+form[data-v-8dc4efb0],li>button>span[style*='display:none']+form[data-v-8dc4efb0],li>a>span.hidden+form[data-v-8dc4efb0],li>a>span[style*='display:none']+form[data-v-8dc4efb0],li>.menuitem>span.hidden+form[data-v-8dc4efb0],li>.menuitem>span[style*='display:none']+form[data-v-8dc4efb0]{margin-left:0}li>button input[data-v-8dc4efb0],li>a input[data-v-8dc4efb0],li>.menuitem input[data-v-8dc4efb0]{min-width:44px;max-height:40px;margin:2px 0;flex:1 1 auto}li>button input[data-v-8dc4efb0]:not(:first-child),li>a input[data-v-8dc4efb0]:not(:first-child),li>.menuitem input[data-v-8dc4efb0]:not(:first-child){margin-left:5px}li:not(.hidden):not([style*='display:none']):first-of-type>button>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):first-of-type>button>input[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):first-of-type>a>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):first-of-type>a>input[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):first-of-type>.menuitem>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):first-of-type>.menuitem>input[data-v-8dc4efb0]{margin-top:12px}li:not(.hidden):not([style*='display:none']):last-of-type>button>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):last-of-type>button>input[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):last-of-type>a>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):last-of-type>a>input[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):last-of-type>.menuitem>form[data-v-8dc4efb0],li:not(.hidden):not([style*='display:none']):last-of-type>.menuitem>input[data-v-8dc4efb0]{margin-bottom:12px}li>button[data-v-8dc4efb0]{padding:0}li>button span[data-v-8dc4efb0]{opacity:1}\n",""]),A.exports=t},function(A,t,e){"use strict";var i=e(29);e.n(i).a},function(A,t,e){(t=e(1)(!1)).push([A.i,"ul[data-v-769d0d8a]{display:flex;flex-direction:column}\n",""]),A.exports=t},,function(A,t){},function(A,t){},,,,,,,,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.string.match */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.match.js")},function(A,t,e){"use strict";var i=e(32);e.n(i).a},function(A,t,e){var i=e(1),a=e(7),n=e(8),o=e(9),r=e(10),s=e(11);t=i(!1);var c=a(n),l=a(o),d=a(r),u=a(s);t.push([A.i,'@font-face{font-family:"iconfont-vue-c0f93b9";src:url('+c+");src:url("+c+') format("embedded-opentype"),url('+l+') format("woff"),url('+d+') format("truetype"),url('+u+') format("svg")}.icon[data-v-27e1af54]{font-style:normal;font-weight:400}.icon.arrow-left-double[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.arrow-left[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.arrow-right-double[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.arrow-right[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.checkmark[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.close[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.confirm[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.menu[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.more[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.pause[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.play[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.icon.triangle-s[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";content:""}.avatardiv[data-v-27e1af54]{position:relative;display:inline-block}.avatardiv--unknown[data-v-27e1af54]{position:relative;background-color:var(--color-text-maxcontrast)}.avatardiv--with-menu[data-v-27e1af54]{cursor:pointer}.avatardiv--with-menu .icon-more[data-v-27e1af54]{position:absolute;top:0;left:0;display:flex;align-items:center;justify-content:center;width:inherit;height:inherit;cursor:pointer;opacity:0;background:none;font-size:18px}.avatardiv--with-menu .icon-more[data-v-27e1af54]:before{font-family:"iconfont-vue-c0f93b9";font-style:normal;font-weight:400;content:""}.avatardiv--with-menu .icon-more[data-v-27e1af54]::before{display:block}.avatardiv--with-menu:focus .icon-more[data-v-27e1af54],.avatardiv--with-menu:hover .icon-more[data-v-27e1af54]{opacity:1}.avatardiv--with-menu:focus img[data-v-27e1af54],.avatardiv--with-menu:hover img[data-v-27e1af54]{opacity:0}.avatardiv--with-menu .icon-more[data-v-27e1af54],.avatardiv--with-menu img[data-v-27e1af54]{transition:opacity var(--animation-quick)}.avatardiv>.unknown[data-v-27e1af54]{position:absolute;top:0;left:0;display:block;width:100%;text-align:center;color:var(--color-main-background)}.avatardiv img[data-v-27e1af54]{width:100%;height:100%}.avatardiv .avatardiv__status[data-v-27e1af54]{position:absolute;top:22px;left:22px;width:10px;height:10px;border:1px solid rgba(255,255,255,0.5);background-clip:content-box}.avatardiv .avatardiv__status--positive[data-v-27e1af54]{border-radius:50%;background-color:var(--color-success)}.avatardiv .avatardiv__status--negative[data-v-27e1af54]{background-color:var(--color-error)}.avatardiv .avatardiv__status--neutral[data-v-27e1af54]{border:none;background-color:transparent !important}.avatardiv .avatardiv__status--neutral svg[data-v-27e1af54]{position:absolute;top:-3px;left:-2px}.avatardiv .avatardiv__status--neutral svg path[data-v-27e1af54]{fill:#aaa}.avatardiv .popovermenu-wrapper[data-v-27e1af54]{position:relative;display:inline-block}.avatardiv .popovermenu[data-v-27e1af54]{display:block;margin:0;font-size:initial}.avatar-class-icon[data-v-27e1af54]{border-radius:50%;background-color:var(--color-background-darker)}\n',""]),A.exports=t},,,,,,,,,,,,,,,,,,,,,,,,,,,function(A,t,e){"use strict";e.r(t);var i=e(34),a=(e(130),e(136),e(137),e(16),e(61),e(46),e(138),e(30),e(3),e(17),e(18),e(14),e(110),e(36)),n=e.n(a),o=e(47),r=(e(139),e(20),e(24),{methods:{highlightText:function(A,t){return t.length?A.replace(new RegExp(t,"gi"),"<strong>".concat(t,"</strong>")):A}}}),s={name:"AvatarSelectOption",components:{Avatar:o.default},mixins:[r],props:{desc:{type:String,default:""},displayName:{type:String,required:!0},icon:{type:String,default:""},user:{type:String,default:""},isNoUser:{type:Boolean,default:!1},search:{type:String,default:""}},computed:{highlightedDisplayName:function(){return this.highlightText(n()(this.displayName),this.search)},highlightedDesc:function(){return this.highlightText(n()(this.desc),this.search)}}},c=(e(140),e(0)),l=e(69),d=e.n(l),u=Object(c.a)(s,function(){var A=this,t=A.$createElement,e=A._self._c||t;return e("span",{staticClass:"option"},[e("Avatar",{staticClass:"option__avatar",attrs:{"display-name":A.displayName,user:A.user,"is-no-user":A.isNoUser,"disable-menu":!0,"disable-tooltip":!0}}),A._v(" "),e("div",{staticClass:"option__desc"},[e("span",{staticClass:"option__desc--lineone",domProps:{innerHTML:A._s(A.highlightedDisplayName)}}),A._v(" "),""!==A.desc?e("span",{staticClass:"option__desc--linetwo",domProps:{innerHTML:A._s(A.highlightedDesc)}}):A._e()]),A._v(" "),""!==A.icon?e("span",{staticClass:"icon option__icon",class:A.icon}):A._e()],1)},[],!1,null,"30d8da34",null);"function"==typeof d.a&&d()(u);var p=u.exports,g=(e(6),{name:"EllipsisedOption",mixins:[r],props:{option:{type:[String,Object],required:!0,default:""},label:{type:String,default:""},search:{type:String,default:""}},computed:{name:function(){return this.$parent.getOptionLabel(this.option)},needsTruncate:function(){return this.name&&this.name.length>=10},part1:function(){if(this.needsTruncate){var A=Math.min(Math.floor(this.name.length/2),10);return this.name.substr(0,this.name.length-A)}return this.name},part2:function(){if(this.needsTruncate){var A=Math.min(Math.floor(this.name.length/2),10);return this.name.substr(this.name.length-A)}return""},highlightedPart1:function(){return this.highlightText(n()(this.part1),this.search)},highlightedPart2:function(){return this.highlightText(n()(this.part2),this.search)}}}),f=(e(142),Object(c.a)(g,function(){var A=this,t=A.$createElement,e=A._self._c||t;return e("div",{staticClass:"name-parts",attrs:{title:A.name}},[e("span",{staticClass:"name-parts__first",domProps:{innerHTML:A._s(A.highlightedPart1)}}),A._v(" "),A.part2?e("span",{staticClass:"name-parts__last",domProps:{innerHTML:A._s(A.highlightedPart2)}}):A._e()])},[],!1,null,"c4325954",null).exports),m=e(12),v=e(111);function b(A){return(b="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(A){return typeof A}:function(A){return A&&"function"==typeof Symbol&&A.constructor===Symbol&&A!==Symbol.prototype?"symbol":typeof A})(A)}var h={name:"Multiselect",components:{AvatarSelectOption:p,EllipsisedOption:f,VueMultiselect:e.n(v).a},directives:{tooltip:m.default},inheritAttrs:!1,props:{value:{default:function(){return[]}},multiple:{type:Boolean,default:!1},limit:{type:Number,default:99999},label:{type:String,default:""},trackBy:{type:String,default:""},options:{type:Array,required:!0},userSelect:{type:Boolean,default:!1},loading:{type:Boolean,default:!1},autoLimit:{type:Boolean,default:!0},tagWidth:{type:Number,default:150,validator:function(A){return A>0}}},data:function(){return{elWidth:0}},computed:{maxOptions:function(){if(this.autoLimit&&this.elWidth>0&&0!==this.tagWidth){var A=Math.floor(this.elWidth/this.tagWidth);return A>0?A:1}return this.limit?this.limit:9999},limitString:function(){return"+".concat(this.value.length-this.maxOptions)},localValue:{get:function(){return this.trackBy&&this.options&&"object"!==b(this.value)&&this.options[this.value]?this.options[this.value]:this.value},set:function(A){this.$emit("update:value",A),this.$emit("change",A)}}},watch:{value:function(){this.updateWidth()}},mounted:function(){this.updateWidth(),window.addEventListener("resize",this.updateWidth)},beforeDestroy:function(){window.removeEventListener("resize",this.updateWidth)},methods:{formatLimitTitle:function(A){var t=this;if(Array.isArray(A)&&A.length>0){var e=A;return"object"===b(A[0])&&(e=A.map(function(A){return A[t.label]})),e.slice(this.maxOptions).join(", ")}return""},updateWidth:function(){this.$el&&this.$el.querySelector(".multiselect__tags-wrap")&&(this.elWidth=this.$el.querySelector(".multiselect__tags-wrap").offsetWidth-10)}}},x=e(70),B=e.n(x),M=Object(c.a)(h,function(){var A=this,t=A.$createElement,e=A._self._c||t;return e("VueMultiselect",A._g(A._b({class:[{"icon-loading-small":A.loading},A.multiple?"multiselect--multiple":"multiselect--single"],attrs:{options:A.options,limit:A.maxOptions,"close-on-select":!A.multiple,multiple:A.multiple,label:A.label,"track-by":A.trackBy,"tag-placeholder":"create"},scopedSlots:A._u([{key:"option",fn:function(t){return[A.userSelect&&!A.$scopedSlots.option?e("AvatarSelectOption",A._b({attrs:{search:t.search}},"AvatarSelectOption",t.option,!1)):A.$scopedSlots.option?A._t("option",null,null,t):e("EllipsisedOption",{attrs:{option:t.option,search:t.search,label:A.label}})]}},A.multiple?{key:"limit",fn:function(){return[e("span",{directives:[{name:"tooltip",rawName:"v-tooltip.auto",value:A.formatLimitTitle(A.value),expression:"formatLimitTitle(value)",modifiers:{auto:!0}}],staticClass:"multiselect__limit"},[A._v("\n\t\t\t"+A._s(A.limitString)+"\n\t\t")])]},proxy:!0}:null,A._l(A.$scopedSlots,function(t,e){return{key:e,fn:function(t){return[A._t(e,null,null,t)]}}})],null,!0),model:{value:A.localValue,callback:function(t){A.localValue=t},expression:"localValue"}},"VueMultiselect",A.$attrs,!1),A.$listeners))},[],!1,null,null,null);"function"==typeof B.a&&B()(M);var w=M.exports;e(144);e.d(t,"Multiselect",function(){return w}),
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
Object(i.a)(w);t.default=w},,,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.string.search */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.string.search.js")},function(A,t){A.exports=__webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js")},,,,,,,,,,,,,,,,,,,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.symbol */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.symbol.js")},,,,,,function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.symbol.description */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.symbol.description.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.symbol.iterator */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.symbol.iterator.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.array.slice */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.array.slice.js")},function(A,t){A.exports=__webpack_require__(/*! core-js/modules/es.regexp.constructor */ "./node_modules/@nextcloud/vue/node_modules/core-js/modules/es.regexp.constructor.js")},function(A,t,e){"use strict";var i=e(53);e.n(i).a},function(A,t,e){(t=e(1)(!1)).push([A.i,".option[data-v-30d8da34]{display:flex;align-items:center;width:100%;height:32px}.option__avatar[data-v-30d8da34]{flex:0 0 32px;width:32px;height:32px;margin-right:6px}.option__desc[data-v-30d8da34]{display:flex;flex:1 1;flex-direction:column;justify-content:center;min-width:0}.option__desc--lineone[data-v-30d8da34]{color:var(--color-text-light)}.option__desc--linetwo[data-v-30d8da34]{opacity:.7}.option__desc--lineone[data-v-30d8da34],.option__desc--linetwo[data-v-30d8da34]{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.option__desc--lineone strong[data-v-30d8da34],.option__desc--linetwo strong[data-v-30d8da34]{font-weight:bold}.option__icon[data-v-30d8da34]{flex:0 0 44px;width:44px;height:44px;margin:-6px;opacity:.5;background-position:center;background-size:16px}\n",""]),A.exports=t},function(A,t,e){"use strict";var i=e(54);e.n(i).a},function(A,t,e){(t=e(1)(!1)).push([A.i,".name-parts[data-v-c4325954]{display:flex;max-width:100%}.name-parts__first[data-v-c4325954]{overflow:hidden;text-overflow:ellipsis}.name-parts__first[data-v-c4325954],.name-parts__last[data-v-c4325954]{white-space:pre}.name-parts__first strong[data-v-c4325954],.name-parts__last strong[data-v-c4325954]{font-weight:bold}\n",""]),A.exports=t},function(A,t,e){var i=e(145);"string"==typeof i&&(i=[[A.i,i,""]]),i.locals&&(A.exports=i.locals);(0,e(2).default)("b5985a26",i,!0,{})},function(A,t,e){(t=e(1)(!1)).push([A.i,".multiselect[data-v-c0f93b9]{margin:0;padding:0 !important;display:inline-block;min-width:160px;position:relative;background-color:var(--color-main-background)}.multiselect[data-v-c0f93b9].multiselect--active input.multiselect__input{opacity:1 !important;cursor:text !important;border-radius:var(--border-radius) var(--border-radius) 0 0}.multiselect[data-v-c0f93b9].multiselect--active .multiselect__limit{display:none}.multiselect[data-v-c0f93b9].multiselect--active.multiselect--above input.multiselect__input{border-radius:0 0 var(--border-radius) var(--border-radius)}.multiselect[data-v-c0f93b9].multiselect--disabled,.multiselect[data-v-c0f93b9].multiselect--disabled .multiselect__single{background-color:var(--color-background-dark) !important}.multiselect[data-v-c0f93b9].icon-loading-small::after{left:100%;margin-left:-24px}.multiselect[data-v-c0f93b9] .multiselect__tags{display:flex;flex-wrap:nowrap;overflow:hidden;border:1px solid var(--color-border-dark);cursor:pointer;position:relative;border-radius:3px;height:34px}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap{align-items:center;display:inline-flex;overflow:hidden;max-width:100%;position:relative;padding:3px 5px;flex-grow:1}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap:empty ~ input.multiselect__input{opacity:1 !important}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap:empty ~ input.multiselect__input+span:not(.multiselect__single){display:none}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap .multiselect__tag{flex:1 0 0;line-height:20px;padding:1px 5px;background-image:none;color:var(--color-main-text);border:1px solid var(--color-border-dark);display:inline-flex;align-items:center;border-radius:3px;min-width:0;max-width:50%;max-width:fit-content;max-width:-moz-fit-content}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap .multiselect__tag:only-child{flex:0 1 auto}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap .multiselect__tag:not(:last-child){margin-right:5px}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__tags-wrap .multiselect__tag>span{white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__single,.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__placeholder{padding:7px 6px;flex:0 0 100%;z-index:1;background-color:var(--color-main-background);cursor:pointer;line-height:18px;color:var(--color-text-lighter)}.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__strong,.multiselect[data-v-c0f93b9] .multiselect__tags .multiselect__limit{flex:0 0 auto;line-height:20px;color:var(--color-text-lighter);display:inline-flex;align-items:center;opacity:.7;margin-right:5px;z-index:5}.multiselect[data-v-c0f93b9] .multiselect__tags input.multiselect__input{width:100% !important;position:absolute !important;top:0;left:0;margin:0;opacity:0;height:100%;border:none;display:block !important;cursor:pointer;padding:7px 6px !important}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper{position:absolute;width:100%;margin-top:-1px;border:1px solid var(--color-border-dark);background:var(--color-main-background);z-index:50;max-height:250px;overflow-y:auto;border-radius:0 0 var(--border-radius) var(--border-radius)}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper .multiselect__content{width:100%;padding:0}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li{position:relative;display:flex;align-items:center;background-color:transparent}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li,.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li span{cursor:pointer}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span{padding:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0;height:auto;min-height:1em;-webkit-touch-callout:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;display:inline-flex;align-items:center;background-color:transparent;color:var(--color-text-lighter);width:100%}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span::before{content:' ';background-repeat:no-repeat;background-position:center;min-width:16px;min-height:16px;display:block;opacity:.5;margin-right:5px;visibility:hidden}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span.multiselect__option--disabled{background-color:var(--color-background-dark);opacity:.5}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span.multiselect__option--highlight{color:var(--color-main-text);background-color:var(--color-background-dark)}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span:not(.multiselect__option--disabled):hover::before{opacity:.3}.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span.multiselect__option--selected::before,.multiselect[data-v-c0f93b9] .multiselect__content-wrapper li>span:not(.multiselect__option--disabled):hover::before{visibility:visible}.multiselect[data-v-c0f93b9].multiselect--above .multiselect__content-wrapper{bottom:100%;margin-bottom:-1px}.multiselect[data-v-c0f93b9].multiselect--multiple .multiselect__content-wrapper li>span::before{background-image:var(--icon-checkmark-000)}.multiselect[data-v-c0f93b9].multiselect--multiple .multiselect__content-wrapper li>span[data-select='create']::before{background-image:var(--icon-add-000);visibility:visible}.multiselect[data-v-c0f93b9].multiselect--single .multiselect__content-wrapper li>span::before{display:none}.multiselect[data-v-c0f93b9]:hover .multiselect__placeholder,.multiselect[data-v-c0f93b9] input.multiselect__input .multiselect__placeholder{color:var(--color-main-text)}\n",""]),A.exports=t}])});
//# sourceMappingURL=Multiselect.js.map

/***/ }),

/***/ "./node_modules/vue-click-outside/index.js":
/*!*************************************************!*\
  !*** ./node_modules/vue-click-outside/index.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function validate(binding) {
  if (typeof binding.value !== 'function') {
    console.warn('[Vue-click-outside:] provided expression', binding.expression, 'is not a function.')
    return false
  }

  return true
}

function isPopup(popupItem, elements) {
  if (!popupItem || !elements)
    return false

  for (var i = 0, len = elements.length; i < len; i++) {
    try {
      if (popupItem.contains(elements[i])) {
        return true
      }
      if (elements[i].contains(popupItem)) {
        return false
      }
    } catch(e) {
      return false
    }
  }

  return false
}

function isServer(vNode) {
  return typeof vNode.componentInstance !== 'undefined' && vNode.componentInstance.$isServer
}

exports = module.exports = {
  bind: function (el, binding, vNode) {
    if (!validate(binding)) return

    // Define Handler and cache it on the element
    function handler(e) {
      if (!vNode.context) return

      // some components may have related popup item, on which we shall prevent the click outside event handler.
      var elements = e.path || (e.composedPath && e.composedPath())
      elements && elements.length > 0 && elements.unshift(e.target)
      
      if (el.contains(e.target) || isPopup(vNode.context.popupItem, elements)) return

      el.__vueClickOutside__.callback(e)
    }

    // add Event Listeners
    el.__vueClickOutside__ = {
      handler: handler,
      callback: binding.value
    }
    !isServer(vNode) && document.addEventListener('click', handler)
  },

  update: function (el, binding) {
    if (validate(binding)) el.__vueClickOutside__.callback = binding.value
  },
  
  unbind: function (el, binding, vNode) {
    // Remove Event Listeners
    !isServer(vNode) && document.removeEventListener('click', el.__vueClickOutside__.handler)
    delete el.__vueClickOutside__
  }
}


/***/ }),

/***/ "./node_modules/vue-infinite-loading/dist/vue-infinite-loading.js":
/*!************************************************************************!*\
  !*** ./node_modules/vue-infinite-loading/dist/vue-infinite-loading.js ***!
  \************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
 * vue-infinite-loading v2.4.4
 * (c) 2016-2019 PeachScript
 * MIT License
 */
!function(t,e){ true?module.exports=e():undefined}(this,function(){return function(t){var e={};function n(i){if(e[i])return e[i].exports;var r=e[i]={i:i,l:!1,exports:{}};return t[i].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=t,n.c=e,n.d=function(t,e,i){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:i})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var i=Object.create(null);if(n.r(i),Object.defineProperty(i,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)n.d(i,r,function(e){return t[e]}.bind(null,r));return i},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=9)}([function(t,e,n){var i=n(6);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("09280948",i,!0,{})},function(t,e,n){var i=n(8);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("51e4c3f9",i,!0,{})},function(t,e){t.exports=function(t){var e=[];return e.toString=function(){return this.map(function(e){var n=function(t,e){var n=t[1]||"",i=t[3];if(!i)return n;if(e&&"function"==typeof btoa){var r=(o=i,"/*# sourceMappingURL=data:application/json;charset=utf-8;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),a=i.sources.map(function(t){return"/*# sourceURL="+i.sourceRoot+t+" */"});return[n].concat(a).concat([r]).join("\n")}var o;return[n].join("\n")}(e,t);return e[2]?"@media "+e[2]+"{"+n+"}":n}).join("")},e.i=function(t,n){"string"==typeof t&&(t=[[null,t,""]]);for(var i={},r=0;r<this.length;r++){var a=this[r][0];"number"==typeof a&&(i[a]=!0)}for(r=0;r<t.length;r++){var o=t[r];"number"==typeof o[0]&&i[o[0]]||(n&&!o[2]?o[2]=n:n&&(o[2]="("+o[2]+") and ("+n+")"),e.push(o))}},e}},function(t,e,n){"use strict";function i(t,e){for(var n=[],i={},r=0;r<e.length;r++){var a=e[r],o=a[0],s={id:t+":"+r,css:a[1],media:a[2],sourceMap:a[3]};i[o]?i[o].parts.push(s):n.push(i[o]={id:o,parts:[s]})}return n}n.r(e),n.d(e,"default",function(){return b});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var a={},o=r&&(document.head||document.getElementsByTagName("head")[0]),s=null,l=0,d=!1,c=function(){},u=null,p="data-vue-ssr-id",f="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function b(t,e,n,r){d=n,u=r||{};var o=i(t,e);return h(o),function(e){for(var n=[],r=0;r<o.length;r++){var s=o[r];(l=a[s.id]).refs--,n.push(l)}e?h(o=i(t,e)):o=[];for(r=0;r<n.length;r++){var l;if(0===(l=n[r]).refs){for(var d=0;d<l.parts.length;d++)l.parts[d]();delete a[l.id]}}}}function h(t){for(var e=0;e<t.length;e++){var n=t[e],i=a[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(g(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{var o=[];for(r=0;r<n.parts.length;r++)o.push(g(n.parts[r]));a[n.id]={id:n.id,refs:1,parts:o}}}}function m(){var t=document.createElement("style");return t.type="text/css",o.appendChild(t),t}function g(t){var e,n,i=document.querySelector("style["+p+'~="'+t.id+'"]');if(i){if(d)return c;i.parentNode.removeChild(i)}if(f){var r=l++;i=s||(s=m()),e=w.bind(null,i,r,!1),n=w.bind(null,i,r,!0)}else i=m(),e=function(t,e){var n=e.css,i=e.media,r=e.sourceMap;i&&t.setAttribute("media",i);u.ssrId&&t.setAttribute(p,e.id);r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */");if(t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}.bind(null,i),n=function(){i.parentNode.removeChild(i)};return e(t),function(i){if(i){if(i.css===t.css&&i.media===t.media&&i.sourceMap===t.sourceMap)return;e(t=i)}else n()}}var v,y=(v=[],function(t,e){return v[t]=e,v.filter(Boolean).join("\n")});function w(t,e,n,i){var r=n?"":i.css;if(t.styleSheet)t.styleSheet.cssText=y(e,r);else{var a=document.createTextNode(r),o=t.childNodes;o[e]&&t.removeChild(o[e]),o.length?t.insertBefore(a,o[e]):t.appendChild(a)}}},function(t,e){function n(t){return(n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function i(e){return"function"==typeof Symbol&&"symbol"===n(Symbol.iterator)?t.exports=i=function(t){return n(t)}:t.exports=i=function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":n(t)},i(e)}t.exports=i},function(t,e,n){"use strict";n.r(e);var i=n(0),r=n.n(i);for(var a in i)"default"!==a&&function(t){n.d(e,t,function(){return i[t]})}(a);e.default=r.a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,'.loading-wave-dots[data-v-46b20d22]{position:relative}.loading-wave-dots[data-v-46b20d22] .wave-item{position:absolute;top:50%;left:50%;display:inline-block;margin-top:-4px;width:8px;height:8px;border-radius:50%;-webkit-animation:loading-wave-dots-data-v-46b20d22 linear 2.8s infinite;animation:loading-wave-dots-data-v-46b20d22 linear 2.8s infinite}.loading-wave-dots[data-v-46b20d22] .wave-item:first-child{margin-left:-36px}.loading-wave-dots[data-v-46b20d22] .wave-item:nth-child(2){margin-left:-20px;-webkit-animation-delay:.14s;animation-delay:.14s}.loading-wave-dots[data-v-46b20d22] .wave-item:nth-child(3){margin-left:-4px;-webkit-animation-delay:.28s;animation-delay:.28s}.loading-wave-dots[data-v-46b20d22] .wave-item:nth-child(4){margin-left:12px;-webkit-animation-delay:.42s;animation-delay:.42s}.loading-wave-dots[data-v-46b20d22] .wave-item:last-child{margin-left:28px;-webkit-animation-delay:.56s;animation-delay:.56s}@-webkit-keyframes loading-wave-dots-data-v-46b20d22{0%{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}10%{-webkit-transform:translateY(-6px);transform:translateY(-6px);background:#999}20%{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}to{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}}@keyframes loading-wave-dots-data-v-46b20d22{0%{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}10%{-webkit-transform:translateY(-6px);transform:translateY(-6px);background:#999}20%{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}to{-webkit-transform:translateY(0);transform:translateY(0);background:#bbb}}.loading-circles[data-v-46b20d22] .circle-item{width:5px;height:5px;-webkit-animation:loading-circles-data-v-46b20d22 linear .75s infinite;animation:loading-circles-data-v-46b20d22 linear .75s infinite}.loading-circles[data-v-46b20d22] .circle-item:first-child{margin-top:-14.5px;margin-left:-2.5px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(2){margin-top:-11.26px;margin-left:6.26px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(3){margin-top:-2.5px;margin-left:9.5px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(4){margin-top:6.26px;margin-left:6.26px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(5){margin-top:9.5px;margin-left:-2.5px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(6){margin-top:6.26px;margin-left:-11.26px}.loading-circles[data-v-46b20d22] .circle-item:nth-child(7){margin-top:-2.5px;margin-left:-14.5px}.loading-circles[data-v-46b20d22] .circle-item:last-child{margin-top:-11.26px;margin-left:-11.26px}@-webkit-keyframes loading-circles-data-v-46b20d22{0%{background:#dfdfdf}90%{background:#505050}to{background:#dfdfdf}}@keyframes loading-circles-data-v-46b20d22{0%{background:#dfdfdf}90%{background:#505050}to{background:#dfdfdf}}.loading-bubbles[data-v-46b20d22] .bubble-item{background:#666;-webkit-animation:loading-bubbles-data-v-46b20d22 linear .75s infinite;animation:loading-bubbles-data-v-46b20d22 linear .75s infinite}.loading-bubbles[data-v-46b20d22] .bubble-item:first-child{margin-top:-12.5px;margin-left:-.5px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(2){margin-top:-9.26px;margin-left:8.26px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(3){margin-top:-.5px;margin-left:11.5px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(4){margin-top:8.26px;margin-left:8.26px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(5){margin-top:11.5px;margin-left:-.5px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(6){margin-top:8.26px;margin-left:-9.26px}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(7){margin-top:-.5px;margin-left:-12.5px}.loading-bubbles[data-v-46b20d22] .bubble-item:last-child{margin-top:-9.26px;margin-left:-9.26px}@-webkit-keyframes loading-bubbles-data-v-46b20d22{0%{width:1px;height:1px;box-shadow:0 0 0 3px #666}90%{width:1px;height:1px;box-shadow:0 0 0 0 #666}to{width:1px;height:1px;box-shadow:0 0 0 3px #666}}@keyframes loading-bubbles-data-v-46b20d22{0%{width:1px;height:1px;box-shadow:0 0 0 3px #666}90%{width:1px;height:1px;box-shadow:0 0 0 0 #666}to{width:1px;height:1px;box-shadow:0 0 0 3px #666}}.loading-default[data-v-46b20d22]{position:relative;border:1px solid #999;-webkit-animation:loading-rotating-data-v-46b20d22 ease 1.5s infinite;animation:loading-rotating-data-v-46b20d22 ease 1.5s infinite}.loading-default[data-v-46b20d22]:before{content:"";position:absolute;display:block;top:0;left:50%;margin-top:-3px;margin-left:-3px;width:6px;height:6px;background-color:#999;border-radius:50%}.loading-spiral[data-v-46b20d22]{border:2px solid #777;border-right-color:transparent;-webkit-animation:loading-rotating-data-v-46b20d22 linear .85s infinite;animation:loading-rotating-data-v-46b20d22 linear .85s infinite}@-webkit-keyframes loading-rotating-data-v-46b20d22{0%{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}@keyframes loading-rotating-data-v-46b20d22{0%{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}.loading-bubbles[data-v-46b20d22],.loading-circles[data-v-46b20d22]{position:relative}.loading-bubbles[data-v-46b20d22] .bubble-item,.loading-circles[data-v-46b20d22] .circle-item{position:absolute;top:50%;left:50%;display:inline-block;border-radius:50%}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(2),.loading-circles[data-v-46b20d22] .circle-item:nth-child(2){-webkit-animation-delay:93ms;animation-delay:93ms}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(3),.loading-circles[data-v-46b20d22] .circle-item:nth-child(3){-webkit-animation-delay:.186s;animation-delay:.186s}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(4),.loading-circles[data-v-46b20d22] .circle-item:nth-child(4){-webkit-animation-delay:.279s;animation-delay:.279s}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(5),.loading-circles[data-v-46b20d22] .circle-item:nth-child(5){-webkit-animation-delay:.372s;animation-delay:.372s}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(6),.loading-circles[data-v-46b20d22] .circle-item:nth-child(6){-webkit-animation-delay:.465s;animation-delay:.465s}.loading-bubbles[data-v-46b20d22] .bubble-item:nth-child(7),.loading-circles[data-v-46b20d22] .circle-item:nth-child(7){-webkit-animation-delay:.558s;animation-delay:.558s}.loading-bubbles[data-v-46b20d22] .bubble-item:last-child,.loading-circles[data-v-46b20d22] .circle-item:last-child{-webkit-animation-delay:.651s;animation-delay:.651s}',""])},function(t,e,n){"use strict";n.r(e);var i=n(1),r=n.n(i);for(var a in i)"default"!==a&&function(t){n.d(e,t,function(){return i[t]})}(a);e.default=r.a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,".infinite-loading-container[data-v-46b21138]{clear:both;text-align:center}.infinite-loading-container[data-v-46b21138] [class^=loading-]{display:inline-block;margin:5px 0;width:28px;height:28px;font-size:28px;line-height:28px;border-radius:50%}.btn-try-infinite[data-v-46b21138]{margin-top:5px;padding:5px 10px;color:#999;font-size:14px;line-height:1;background:transparent;border:1px solid #ccc;border-radius:3px;outline:none;cursor:pointer}.btn-try-infinite[data-v-46b21138]:not(:active):hover{opacity:.8}",""])},function(t,e,n){"use strict";n.r(e);var i={throttleLimit:50,loopCheckTimeout:1e3,loopCheckMaxCalls:10},r=function(){var t=!1;try{var e=Object.defineProperty({},"passive",{get:function(){return t={passive:!0},!0}});window.addEventListener("testpassive",e,e),window.remove("testpassive",e,e)}catch(t){}return t}(),a={STATE_CHANGER:["emit `loaded` and `complete` event through component instance of `$refs` may cause error, so it will be deprecated soon, please use the `$state` argument instead (`$state` just the special `$event` variable):","\ntemplate:",'<infinite-loading @infinite="infiniteHandler"></infinite-loading>',"\nscript:\n...\ninfiniteHandler($state) {\n  ajax('https://www.example.com/api/news')\n    .then((res) => {\n      if (res.data.length) {\n        $state.loaded();\n      } else {\n        $state.complete();\n      }\n    });\n}\n...","","more details: https://github.com/PeachScript/vue-infinite-loading/issues/57#issuecomment-324370549"].join("\n"),INFINITE_EVENT:"`:on-infinite` property will be deprecated soon, please use `@infinite` event instead.",IDENTIFIER:"the `reset` event will be deprecated soon, please reset this component by change the `identifier` property."},o={INFINITE_LOOP:["executed the callback function more than ".concat(i.loopCheckMaxCalls," times for a short time, it looks like searched a wrong scroll wrapper that doest not has fixed height or maximum height, please check it. If you want to force to set a element as scroll wrapper ranther than automatic searching, you can do this:"),'\n\x3c!-- add a special attribute for the real scroll wrapper --\x3e\n<div infinite-wrapper>\n  ...\n  \x3c!-- set force-use-infinite-wrapper --\x3e\n  <infinite-loading force-use-infinite-wrapper></infinite-loading>\n</div>\nor\n<div class="infinite-wrapper">\n  ...\n  \x3c!-- set force-use-infinite-wrapper as css selector of the real scroll wrapper --\x3e\n  <infinite-loading force-use-infinite-wrapper=".infinite-wrapper"></infinite-loading>\n</div>\n    ',"more details: https://github.com/PeachScript/vue-infinite-loading/issues/55#issuecomment-316934169"].join("\n")},s={READY:0,LOADING:1,COMPLETE:2,ERROR:3},l={color:"#666",fontSize:"14px",padding:"10px 0"},d={mode:"development",props:{spinner:"default",distance:100,forceUseInfiniteWrapper:!1},system:i,slots:{noResults:"No results :(",noMore:"No more data :)",error:"Opps, something went wrong :(",errorBtnText:"Retry",spinner:""},WARNINGS:a,ERRORS:o,STATUS:s},c=n(4),u=n.n(c),p={BUBBLES:{render:function(t){return t("span",{attrs:{class:"loading-bubbles"}},Array.apply(Array,Array(8)).map(function(){return t("span",{attrs:{class:"bubble-item"}})}))}},CIRCLES:{render:function(t){return t("span",{attrs:{class:"loading-circles"}},Array.apply(Array,Array(8)).map(function(){return t("span",{attrs:{class:"circle-item"}})}))}},DEFAULT:{render:function(t){return t("i",{attrs:{class:"loading-default"}})}},SPIRAL:{render:function(t){return t("i",{attrs:{class:"loading-spiral"}})}},WAVEDOTS:{render:function(t){return t("span",{attrs:{class:"loading-wave-dots"}},Array.apply(Array,Array(5)).map(function(){return t("span",{attrs:{class:"wave-item"}})}))}}};function f(t,e,n,i,r,a,o,s){var l,d="function"==typeof t?t.options:t;if(e&&(d.render=e,d.staticRenderFns=n,d._compiled=!0),i&&(d.functional=!0),a&&(d._scopeId="data-v-"+a),o?(l=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),r&&r.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(o)},d._ssrRegister=l):r&&(l=s?function(){r.call(this,this.$root.$options.shadowRoot)}:r),l)if(d.functional){d._injectStyles=l;var c=d.render;d.render=function(t,e){return l.call(e),c(t,e)}}else{var u=d.beforeCreate;d.beforeCreate=u?[].concat(u,l):[l]}return{exports:t,options:d}}var b=f({name:"Spinner",computed:{spinnerView:function(){return p[(this.$attrs.spinner||"").toUpperCase()]||this.spinnerInConfig},spinnerInConfig:function(){return d.slots.spinner&&"string"==typeof d.slots.spinner?{render:function(){return this._v(d.slots.spinner)}}:"object"===u()(d.slots.spinner)?d.slots.spinner:p[d.props.spinner.toUpperCase()]||p.DEFAULT}}},function(){var t=this.$createElement;return(this._self._c||t)(this.spinnerView,{tag:"component"})},[],!1,function(t){var e=n(5);e.__inject__&&e.__inject__(t)},"46b20d22",null);b.options.__file="Spinner.vue";var h=b.exports;function m(t){"production"!==d.mode&&console.warn("[Vue-infinite-loading warn]: ".concat(t))}function g(t){console.error("[Vue-infinite-loading error]: ".concat(t))}var v={timers:[],caches:[],throttle:function(t){var e=this;-1===this.caches.indexOf(t)&&(this.caches.push(t),this.timers.push(setTimeout(function(){t(),e.caches.splice(e.caches.indexOf(t),1),e.timers.shift()},d.system.throttleLimit)))},reset:function(){this.timers.forEach(function(t){clearTimeout(t)}),this.timers.length=0,this.caches=[]}},y={isChecked:!1,timer:null,times:0,track:function(){var t=this;this.times+=1,clearTimeout(this.timer),this.timer=setTimeout(function(){t.isChecked=!0},d.system.loopCheckTimeout),this.times>d.system.loopCheckMaxCalls&&(g(o.INFINITE_LOOP),this.isChecked=!0)}},w={key:"_infiniteScrollHeight",getScrollElm:function(t){return t===window?document.documentElement:t},save:function(t){var e=this.getScrollElm(t);e[this.key]=e.scrollHeight},restore:function(t){var e=this.getScrollElm(t);"number"==typeof e[this.key]&&(e.scrollTop=e.scrollHeight-e[this.key]+e.scrollTop),this.remove(e)},remove:function(t){void 0!==t[this.key]&&delete t[this.key]}};function x(t){return t.replace(/[A-Z]/g,function(t){return"-".concat(t.toLowerCase())})}function k(t){return t.offsetWidth+t.offsetHeight>0}var S=f({name:"InfiniteLoading",data:function(){return{scrollParent:null,scrollHandler:null,isFirstLoad:!0,status:s.READY,slots:d.slots}},components:{Spinner:h},computed:{isShowSpinner:function(){return this.status===s.LOADING},isShowError:function(){return this.status===s.ERROR},isShowNoResults:function(){return this.status===s.COMPLETE&&this.isFirstLoad},isShowNoMore:function(){return this.status===s.COMPLETE&&!this.isFirstLoad},slotStyles:function(){var t=this,e={};return Object.keys(d.slots).forEach(function(n){var i=x(n);(!t.$slots[i]&&!d.slots[n].render||t.$slots[i]&&!t.$slots[i][0].tag)&&(e[n]=l)}),e}},props:{distance:{type:Number,default:d.props.distance},spinner:String,direction:{type:String,default:"bottom"},forceUseInfiniteWrapper:{type:[Boolean,String],default:d.props.forceUseInfiniteWrapper},identifier:{default:+new Date},onInfinite:Function},watch:{identifier:function(){this.stateChanger.reset()}},mounted:function(){var t=this;this.$watch("forceUseInfiniteWrapper",function(){t.scrollParent=t.getScrollParent()},{immediate:!0}),this.scrollHandler=function(e){t.status===s.READY&&(e&&e.constructor===Event&&k(t.$el)?v.throttle(t.attemptLoad):t.attemptLoad())},setTimeout(function(){t.scrollHandler(),t.scrollParent.addEventListener("scroll",t.scrollHandler,r)},1),this.$on("$InfiniteLoading:loaded",function(e){t.isFirstLoad=!1,"top"===t.direction&&t.$nextTick(function(){w.restore(t.scrollParent)}),t.status===s.LOADING&&t.$nextTick(t.attemptLoad.bind(null,!0)),e&&e.target===t||m(a.STATE_CHANGER)}),this.$on("$InfiniteLoading:complete",function(e){t.status=s.COMPLETE,t.$nextTick(function(){t.$forceUpdate()}),t.scrollParent.removeEventListener("scroll",t.scrollHandler,r),e&&e.target===t||m(a.STATE_CHANGER)}),this.$on("$InfiniteLoading:reset",function(e){t.status=s.READY,t.isFirstLoad=!0,w.remove(t.scrollParent),t.scrollParent.addEventListener("scroll",t.scrollHandler,r),setTimeout(function(){v.reset(),t.scrollHandler()},1),e&&e.target===t||m(a.IDENTIFIER)}),this.stateChanger={loaded:function(){t.$emit("$InfiniteLoading:loaded",{target:t})},complete:function(){t.$emit("$InfiniteLoading:complete",{target:t})},reset:function(){t.$emit("$InfiniteLoading:reset",{target:t})},error:function(){t.status=s.ERROR,v.reset()}},this.onInfinite&&m(a.INFINITE_EVENT)},deactivated:function(){this.status===s.LOADING&&(this.status=s.READY),this.scrollParent.removeEventListener("scroll",this.scrollHandler,r)},activated:function(){this.scrollParent.addEventListener("scroll",this.scrollHandler,r)},methods:{attemptLoad:function(t){var e=this;this.status!==s.COMPLETE&&k(this.$el)&&this.getCurrentDistance()<=this.distance?(this.status=s.LOADING,"top"===this.direction&&this.$nextTick(function(){w.save(e.scrollParent)}),"function"==typeof this.onInfinite?this.onInfinite.call(null,this.stateChanger):this.$emit("infinite",this.stateChanger),!t||this.forceUseInfiniteWrapper||y.isChecked||y.track()):this.status===s.LOADING&&(this.status=s.READY)},getCurrentDistance:function(){var t;"top"===this.direction?t="number"==typeof this.scrollParent.scrollTop?this.scrollParent.scrollTop:this.scrollParent.pageYOffset:t=this.$el.getBoundingClientRect().top-(this.scrollParent===window?window.innerHeight:this.scrollParent.getBoundingClientRect().bottom);return t},getScrollParent:function(){var t,e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.$el;return"string"==typeof this.forceUseInfiniteWrapper&&(t=e.querySelector(this.forceUseInfiniteWrapper)),t||("BODY"===e.tagName?t=window:!this.forceUseInfiniteWrapper&&["scroll","auto"].indexOf(getComputedStyle(e).overflowY)>-1?t=e:(e.hasAttribute("infinite-wrapper")||e.hasAttribute("data-infinite-wrapper"))&&(t=e)),t||this.getScrollParent(e.parentNode)}},destroyed:function(){!this.status!==s.COMPLETE&&(v.reset(),w.remove(this.scrollParent),this.scrollParent.removeEventListener("scroll",this.scrollHandler,r))}},function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"infinite-loading-container"},[n("div",{directives:[{name:"show",rawName:"v-show",value:t.isShowSpinner,expression:"isShowSpinner"}],staticClass:"infinite-status-prompt",style:t.slotStyles.spinner},[t._t("spinner",[n("spinner",{attrs:{spinner:t.spinner}})])],2),t._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:t.isShowNoResults,expression:"isShowNoResults"}],staticClass:"infinite-status-prompt",style:t.slotStyles.noResults},[t._t("no-results",[t.slots.noResults.render?n(t.slots.noResults,{tag:"component"}):[t._v(t._s(t.slots.noResults))]])],2),t._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:t.isShowNoMore,expression:"isShowNoMore"}],staticClass:"infinite-status-prompt",style:t.slotStyles.noMore},[t._t("no-more",[t.slots.noMore.render?n(t.slots.noMore,{tag:"component"}):[t._v(t._s(t.slots.noMore))]])],2),t._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:t.isShowError,expression:"isShowError"}],staticClass:"infinite-status-prompt",style:t.slotStyles.error},[t._t("error",[t.slots.error.render?n(t.slots.error,{tag:"component",attrs:{trigger:t.attemptLoad}}):[t._v("\n        "+t._s(t.slots.error)+"\n        "),n("br"),t._v(" "),n("button",{staticClass:"btn-try-infinite",domProps:{textContent:t._s(t.slots.errorBtnText)},on:{click:t.attemptLoad}})]],{trigger:t.attemptLoad})],2)])},[],!1,function(t){var e=n(7);e.__inject__&&e.__inject__(t)},"46b21138",null);S.options.__file="InfiniteLoading.vue";var E=S.exports;function _(t){d.mode=t.config.productionTip?"development":"production"}Object.defineProperty(E,"install",{configurable:!1,enumerable:!1,value:function(t,e){Object.assign(d.props,e&&e.props),Object.assign(d.slots,e&&e.slots),Object.assign(d.system,e&&e.system),t.component("infinite-loading",E),_(t)}}),"undefined"!=typeof window&&window.Vue&&(window.Vue.component("infinite-loading",E),_(window.Vue));e.default=E}])});

/***/ })

}]);
//# sourceMappingURL=vue-1.js.map?v=75219e658e60f559bccd