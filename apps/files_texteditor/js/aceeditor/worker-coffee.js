"no use strict";

var console = {
    log: function(msg) {
        postMessage({type: "log", data: msg});
    }
};
var window = {
    console: console
};

var normalizeModule = function(parentId, moduleName) {
    // normalize plugin requires
    if (moduleName.indexOf("!") !== -1) {
        var chunks = moduleName.split("!");
        return normalizeModule(parentId, chunks[0]) + "!" + normalizeModule(parentId, chunks[1]);
    }
    // normalize relative requires
    if (moduleName.charAt(0) == ".") {
        var base = parentId.split("/").slice(0, -1).join("/");
        var moduleName = base + "/" + moduleName;
        
        while(moduleName.indexOf(".") !== -1 && previous != moduleName) {
            var previous = moduleName;
            var moduleName = moduleName.replace(/\/\.\//, "/").replace(/[^\/]+\/\.\.\//, "");
        }
    }
    
    return moduleName;
};

var require = function(parentId, id) {
    var id = normalizeModule(parentId, id);
    
    var module = require.modules[id];
    if (module) {
        if (!module.initialized) {
            module.exports = module.factory().exports;
            module.initialized = true;
        }
        return module.exports;
    }
    
    var chunks = id.split("/");
    chunks[0] = require.tlns[chunks[0]] || chunks[0];
    var path = chunks.join("/") + ".js";
    
    require.id = id;
    importScripts(path);
    return require(parentId, id);    
};

require.modules = {};
require.tlns = {};

var define = function(id, deps, factory) {
    if (arguments.length == 2) {
        factory = deps;
    } else if (arguments.length == 1) {
        factory = id;
        id = require.id;
    }

    if (id.indexOf("text!") === 0) 
        return;
    
    var req = function(deps, factory) {
        return require(id, deps, factory);
    };

    require.modules[id] = {
        factory: function() {
            var module = {
                exports: {}
            };
            var returnExports = factory(req, module.exports, module);
            if (returnExports)
                module.exports = returnExports;
            return module;
        }
    };
};

function initBaseUrls(topLevelNamespaces) {
    require.tlns = topLevelNamespaces;
}

function initSender() {

    var EventEmitter = require(null, "ace/lib/event_emitter").EventEmitter;
    var oop = require(null, "ace/lib/oop");
    
    var Sender = function() {};
    
    (function() {
        
        oop.implement(this, EventEmitter);
                
        this.callback = function(data, callbackId) {
            postMessage({
                type: "call",
                id: callbackId,
                data: data
            });
        };
    
        this.emit = function(name, data) {
            postMessage({
                type: "event",
                name: name,
                data: data
            });
        };
        
    }).call(Sender.prototype);
    
    return new Sender();
}

var main;
var sender;

onmessage = function(e) {
    var msg = e.data;
    if (msg.command) {
        main[msg.command].apply(main, msg.args);
    }
    else if (msg.init) {        
        initBaseUrls(msg.tlns);
        require(null, "ace/lib/fixoldbrowsers");
        sender = initSender();
        var clazz = require(null, msg.module)[msg.classname];
        main = new clazz(sender);
    } 
    else if (msg.event && sender) {
        sender._emit(msg.event, msg.data);
    }
};
// vim:set ts=4 sts=4 sw=4 st:
// -- kriskowal Kris Kowal Copyright (C) 2009-2010 MIT License
// -- tlrobinson Tom Robinson Copyright (C) 2009-2010 MIT License (Narwhal Project)
// -- dantman Daniel Friesen Copyright(C) 2010 XXX No License Specified
// -- fschaefer Florian Schäfer Copyright (C) 2010 MIT License
// -- Irakli Gozalishvili Copyright (C) 2010 MIT License

/*!
    Copyright (c) 2009, 280 North Inc. http://280north.com/
    MIT License. http://github.com/280north/narwhal/blob/master/README.md
*/

define('ace/lib/fixoldbrowsers', ['require', 'exports', 'module' , 'ace/lib/regexp', 'ace/lib/es5-shim'], function(require, exports, module) {
"use strict";

require("./regexp");
require("./es5-shim");

});/**
 *  Based on code from:
 *
 * XRegExp 1.5.0
 * (c) 2007-2010 Steven Levithan
 * MIT License
 * <http://xregexp.com>
 * Provides an augmented, extensible, cross-browser implementation of regular expressions,
 * including support for additional syntax, flags, and methods
 */
 
define('ace/lib/regexp', ['require', 'exports', 'module' ], function(require, exports, module) {
"use strict";

    //---------------------------------
    //  Private variables
    //---------------------------------

    var real = {
            exec: RegExp.prototype.exec,
            test: RegExp.prototype.test,
            match: String.prototype.match,
            replace: String.prototype.replace,
            split: String.prototype.split
        },
        compliantExecNpcg = real.exec.call(/()??/, "")[1] === undefined, // check `exec` handling of nonparticipating capturing groups
        compliantLastIndexIncrement = function () {
            var x = /^/g;
            real.test.call(x, "");
            return !x.lastIndex;
        }();

    //---------------------------------
    //  Overriden native methods
    //---------------------------------

    // Adds named capture support (with backreferences returned as `result.name`), and fixes two
    // cross-browser issues per ES3:
    // - Captured values for nonparticipating capturing groups should be returned as `undefined`,
    //   rather than the empty string.
    // - `lastIndex` should not be incremented after zero-length matches.
    RegExp.prototype.exec = function (str) {
        var match = real.exec.apply(this, arguments),
            name, r2;
        if (match) {
            // Fix browsers whose `exec` methods don't consistently return `undefined` for
            // nonparticipating capturing groups
            if (!compliantExecNpcg && match.length > 1 && indexOf(match, "") > -1) {
                r2 = RegExp(this.source, real.replace.call(getNativeFlags(this), "g", ""));
                // Using `str.slice(match.index)` rather than `match[0]` in case lookahead allowed
                // matching due to characters outside the match
                real.replace.call(str.slice(match.index), r2, function () {
                    for (var i = 1; i < arguments.length - 2; i++) {
                        if (arguments[i] === undefined)
                            match[i] = undefined;
                    }
                });
            }
            // Attach named capture properties
            if (this._xregexp && this._xregexp.captureNames) {
                for (var i = 1; i < match.length; i++) {
                    name = this._xregexp.captureNames[i - 1];
                    if (name)
                       match[name] = match[i];
                }
            }
            // Fix browsers that increment `lastIndex` after zero-length matches
            if (!compliantLastIndexIncrement && this.global && !match[0].length && (this.lastIndex > match.index))
                this.lastIndex--;
        }
        return match;
    };

    // Don't override `test` if it won't change anything
    if (!compliantLastIndexIncrement) {
        // Fix browser bug in native method
        RegExp.prototype.test = function (str) {
            // Use the native `exec` to skip some processing overhead, even though the overriden
            // `exec` would take care of the `lastIndex` fix
            var match = real.exec.call(this, str);
            // Fix browsers that increment `lastIndex` after zero-length matches
            if (match && this.global && !match[0].length && (this.lastIndex > match.index))
                this.lastIndex--;
            return !!match;
        };
    }

    //---------------------------------
    //  Private helper functions
    //---------------------------------

    function getNativeFlags (regex) {
        return (regex.global     ? "g" : "") +
               (regex.ignoreCase ? "i" : "") +
               (regex.multiline  ? "m" : "") +
               (regex.extended   ? "x" : "") + // Proposed for ES4; included in AS3
               (regex.sticky     ? "y" : "");
    };

    function indexOf (array, item, from) {
        if (Array.prototype.indexOf) // Use the native array method if available
            return array.indexOf(item, from);
        for (var i = from || 0; i < array.length; i++) {
            if (array[i] === item)
                return i;
        }
        return -1;
    };

});// vim: ts=4 sts=4 sw=4 expandtab
// -- kriskowal Kris Kowal Copyright (C) 2009-2011 MIT License
// -- tlrobinson Tom Robinson Copyright (C) 2009-2010 MIT License (Narwhal Project)
// -- dantman Daniel Friesen Copyright (C) 2010 XXX TODO License or CLA
// -- fschaefer Florian Schäfer Copyright (C) 2010 MIT License
// -- Gozala Irakli Gozalishvili Copyright (C) 2010 MIT License
// -- kitcambridge Kit Cambridge Copyright (C) 2011 MIT License
// -- kossnocorp Sasha Koss XXX TODO License or CLA
// -- bryanforbes Bryan Forbes XXX TODO License or CLA
// -- killdream Quildreen Motta Copyright (C) 2011 MIT Licence
// -- michaelficarra Michael Ficarra Copyright (C) 2011 3-clause BSD License
// -- sharkbrainguy Gerard Paapu Copyright (C) 2011 MIT License
// -- bbqsrc Brendan Molloy (C) 2011 Creative Commons Zero (public domain)
// -- iwyg XXX TODO License or CLA
// -- DomenicDenicola Domenic Denicola Copyright (C) 2011 MIT License
// -- xavierm02 Montillet Xavier XXX TODO License or CLA
// -- Raynos Raynos XXX TODO License or CLA
// -- samsonjs Sami Samhuri Copyright (C) 2010 MIT License
// -- rwldrn Rick Waldron Copyright (C) 2011 MIT License
// -- lexer Alexey Zakharov XXX TODO License or CLA

/*!
    Copyright (c) 2009, 280 North Inc. http://280north.com/
    MIT License. http://github.com/280north/narwhal/blob/master/README.md
*/

define('ace/lib/es5-shim', ['require', 'exports', 'module' ], function(require, exports, module) {

/**
 * Brings an environment as close to ECMAScript 5 compliance
 * as is possible with the facilities of erstwhile engines.
 *
 * Annotated ES5: http://es5.github.com/ (specific links below)
 * ES5 Spec: http://www.ecma-international.org/publications/files/ECMA-ST/Ecma-262.pdf
 *
 * @module
 */

/*whatsupdoc*/

//
// Function
// ========
//

// ES-5 15.3.4.5
// http://es5.github.com/#x15.3.4.5

if (!Function.prototype.bind) {
    Function.prototype.bind = function bind(that) { // .length is 1
        // 1. Let Target be the this value.
        var target = this;
        // 2. If IsCallable(Target) is false, throw a TypeError exception.
        if (typeof target != "function")
            throw new TypeError(); // TODO message
        // 3. Let A be a new (possibly empty) internal list of all of the
        //   argument values provided after thisArg (arg1, arg2 etc), in order.
        // XXX slicedArgs will stand in for "A" if used
        var args = slice.call(arguments, 1); // for normal call
        // 4. Let F be a new native ECMAScript object.
        // 11. Set the [[Prototype]] internal property of F to the standard
        //   built-in Function prototype object as specified in 15.3.3.1.
        // 12. Set the [[Call]] internal property of F as described in
        //   15.3.4.5.1.
        // 13. Set the [[Construct]] internal property of F as described in
        //   15.3.4.5.2.
        // 14. Set the [[HasInstance]] internal property of F as described in
        //   15.3.4.5.3.
        var bound = function () {

            if (this instanceof bound) {
                // 15.3.4.5.2 [[Construct]]
                // When the [[Construct]] internal method of a function object,
                // F that was created using the bind function is called with a
                // list of arguments ExtraArgs, the following steps are taken:
                // 1. Let target be the value of F's [[TargetFunction]]
                //   internal property.
                // 2. If target has no [[Construct]] internal method, a
                //   TypeError exception is thrown.
                // 3. Let boundArgs be the value of F's [[BoundArgs]] internal
                //   property.
                // 4. Let args be a new list containing the same values as the
                //   list boundArgs in the same order followed by the same
                //   values as the list ExtraArgs in the same order.
                // 5. Return the result of calling the [[Construct]] internal 
                //   method of target providing args as the arguments.

                var F = function(){};
                F.prototype = target.prototype;
                var self = new F;

                var result = target.apply(
                    self,
                    args.concat(slice.call(arguments))
                );
                if (result !== null && Object(result) === result)
                    return result;
                return self;

            } else {
                // 15.3.4.5.1 [[Call]]
                // When the [[Call]] internal method of a function object, F,
                // which was created using the bind function is called with a
                // this value and a list of arguments ExtraArgs, the following
                // steps are taken:
                // 1. Let boundArgs be the value of F's [[BoundArgs]] internal
                //   property.
                // 2. Let boundThis be the value of F's [[BoundThis]] internal
                //   property.
                // 3. Let target be the value of F's [[TargetFunction]] internal
                //   property.
                // 4. Let args be a new list containing the same values as the 
                //   list boundArgs in the same order followed by the same 
                //   values as the list ExtraArgs in the same order.
                // 5. Return the result of calling the [[Call]] internal method 
                //   of target providing boundThis as the this value and 
                //   providing args as the arguments.

                // equiv: target.call(this, ...boundArgs, ...args)
                return target.apply(
                    that,
                    args.concat(slice.call(arguments))
                );

            }

        };
        // XXX bound.length is never writable, so don't even try
        //
        // 15. If the [[Class]] internal property of Target is "Function", then
        //     a. Let L be the length property of Target minus the length of A.
        //     b. Set the length own property of F to either 0 or L, whichever is 
        //       larger.
        // 16. Else set the length own property of F to 0.
        // 17. Set the attributes of the length own property of F to the values
        //   specified in 15.3.5.1.

        // TODO
        // 18. Set the [[Extensible]] internal property of F to true.
        
        // TODO
        // 19. Let thrower be the [[ThrowTypeError]] function Object (13.2.3).
        // 20. Call the [[DefineOwnProperty]] internal method of F with 
        //   arguments "caller", PropertyDescriptor {[[Get]]: thrower, [[Set]]:
        //   thrower, [[Enumerable]]: false, [[Configurable]]: false}, and 
        //   false.
        // 21. Call the [[DefineOwnProperty]] internal method of F with 
        //   arguments "arguments", PropertyDescriptor {[[Get]]: thrower, 
        //   [[Set]]: thrower, [[Enumerable]]: false, [[Configurable]]: false},
        //   and false.

        // TODO
        // NOTE Function objects created using Function.prototype.bind do not 
        // have a prototype property or the [[Code]], [[FormalParameters]], and
        // [[Scope]] internal properties.
        // XXX can't delete prototype in pure-js.

        // 22. Return F.
        return bound;
    };
}

// Shortcut to an often accessed properties, in order to avoid multiple
// dereference that costs universally.
// _Please note: Shortcuts are defined after `Function.prototype.bind` as we
// us it in defining shortcuts.
var call = Function.prototype.call;
var prototypeOfArray = Array.prototype;
var prototypeOfObject = Object.prototype;
var slice = prototypeOfArray.slice;
var toString = call.bind(prototypeOfObject.toString);
var owns = call.bind(prototypeOfObject.hasOwnProperty);

// If JS engine supports accessors creating shortcuts.
var defineGetter;
var defineSetter;
var lookupGetter;
var lookupSetter;
var supportsAccessors;
if ((supportsAccessors = owns(prototypeOfObject, "__defineGetter__"))) {
    defineGetter = call.bind(prototypeOfObject.__defineGetter__);
    defineSetter = call.bind(prototypeOfObject.__defineSetter__);
    lookupGetter = call.bind(prototypeOfObject.__lookupGetter__);
    lookupSetter = call.bind(prototypeOfObject.__lookupSetter__);
}

//
// Array
// =====
//

// ES5 15.4.3.2
// http://es5.github.com/#x15.4.3.2
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/isArray
if (!Array.isArray) {
    Array.isArray = function isArray(obj) {
        return toString(obj) == "[object Array]";
    };
}

// The IsCallable() check in the Array functions
// has been replaced with a strict check on the
// internal class of the object to trap cases where
// the provided function was actually a regular
// expression literal, which in V8 and
// JavaScriptCore is a typeof "function".  Only in
// V8 are regular expression literals permitted as
// reduce parameters, so it is desirable in the
// general case for the shim to match the more
// strict and common behavior of rejecting regular
// expressions.

// ES5 15.4.4.18
// http://es5.github.com/#x15.4.4.18
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/array/forEach
if (!Array.prototype.forEach) {
    Array.prototype.forEach = function forEach(fun /*, thisp*/) {
        var self = toObject(this),
            thisp = arguments[1],
            i = 0,
            length = self.length >>> 0;

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        while (i < length) {
            if (i in self) {
                // Invoke the callback function with call, passing arguments:
                // context, property value, property key, thisArg object context
                fun.call(thisp, self[i], i, self);
            }
            i++;
        }
    };
}

// ES5 15.4.4.19
// http://es5.github.com/#x15.4.4.19
// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Objects/Array/map
if (!Array.prototype.map) {
    Array.prototype.map = function map(fun /*, thisp*/) {
        var self = toObject(this),
            length = self.length >>> 0,
            result = Array(length),
            thisp = arguments[1];

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        for (var i = 0; i < length; i++) {
            if (i in self)
                result[i] = fun.call(thisp, self[i], i, self);
        }
        return result;
    };
}

// ES5 15.4.4.20
// http://es5.github.com/#x15.4.4.20
// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Objects/Array/filter
if (!Array.prototype.filter) {
    Array.prototype.filter = function filter(fun /*, thisp */) {
        var self = toObject(this),
            length = self.length >>> 0,
            result = [],
            thisp = arguments[1];

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        for (var i = 0; i < length; i++) {
            if (i in self && fun.call(thisp, self[i], i, self))
                result.push(self[i]);
        }
        return result;
    };
}

// ES5 15.4.4.16
// http://es5.github.com/#x15.4.4.16
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/every
if (!Array.prototype.every) {
    Array.prototype.every = function every(fun /*, thisp */) {
        var self = toObject(this),
            length = self.length >>> 0,
            thisp = arguments[1];

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        for (var i = 0; i < length; i++) {
            if (i in self && !fun.call(thisp, self[i], i, self))
                return false;
        }
        return true;
    };
}

// ES5 15.4.4.17
// http://es5.github.com/#x15.4.4.17
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/some
if (!Array.prototype.some) {
    Array.prototype.some = function some(fun /*, thisp */) {
        var self = toObject(this),
            length = self.length >>> 0,
            thisp = arguments[1];

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        for (var i = 0; i < length; i++) {
            if (i in self && fun.call(thisp, self[i], i, self))
                return true;
        }
        return false;
    };
}

// ES5 15.4.4.21
// http://es5.github.com/#x15.4.4.21
// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Objects/Array/reduce
if (!Array.prototype.reduce) {
    Array.prototype.reduce = function reduce(fun /*, initial*/) {
        var self = toObject(this),
            length = self.length >>> 0;

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        // no value to return if no initial value and an empty array
        if (!length && arguments.length == 1)
            throw new TypeError(); // TODO message

        var i = 0;
        var result;
        if (arguments.length >= 2) {
            result = arguments[1];
        } else {
            do {
                if (i in self) {
                    result = self[i++];
                    break;
                }

                // if array contains no values, no initial value to return
                if (++i >= length)
                    throw new TypeError(); // TODO message
            } while (true);
        }

        for (; i < length; i++) {
            if (i in self)
                result = fun.call(void 0, result, self[i], i, self);
        }

        return result;
    };
}

// ES5 15.4.4.22
// http://es5.github.com/#x15.4.4.22
// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Objects/Array/reduceRight
if (!Array.prototype.reduceRight) {
    Array.prototype.reduceRight = function reduceRight(fun /*, initial*/) {
        var self = toObject(this),
            length = self.length >>> 0;

        // If no callback function or if callback is not a callable function
        if (toString(fun) != "[object Function]") {
            throw new TypeError(); // TODO message
        }

        // no value to return if no initial value, empty array
        if (!length && arguments.length == 1)
            throw new TypeError(); // TODO message

        var result, i = length - 1;
        if (arguments.length >= 2) {
            result = arguments[1];
        } else {
            do {
                if (i in self) {
                    result = self[i--];
                    break;
                }

                // if array contains no values, no initial value to return
                if (--i < 0)
                    throw new TypeError(); // TODO message
            } while (true);
        }

        do {
            if (i in this)
                result = fun.call(void 0, result, self[i], i, self);
        } while (i--);

        return result;
    };
}

// ES5 15.4.4.14
// http://es5.github.com/#x15.4.4.14
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/indexOf
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function indexOf(sought /*, fromIndex */ ) {
        var self = toObject(this),
            length = self.length >>> 0;

        if (!length)
            return -1;

        var i = 0;
        if (arguments.length > 1)
            i = toInteger(arguments[1]);

        // handle negative indices
        i = i >= 0 ? i : Math.max(0, length + i);
        for (; i < length; i++) {
            if (i in self && self[i] === sought) {
                return i;
            }
        }
        return -1;
    };
}

// ES5 15.4.4.15
// http://es5.github.com/#x15.4.4.15
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/lastIndexOf
if (!Array.prototype.lastIndexOf) {
    Array.prototype.lastIndexOf = function lastIndexOf(sought /*, fromIndex */) {
        var self = toObject(this),
            length = self.length >>> 0;

        if (!length)
            return -1;
        var i = length - 1;
        if (arguments.length > 1)
            i = Math.min(i, toInteger(arguments[1]));
        // handle negative indices
        i = i >= 0 ? i : length - Math.abs(i);
        for (; i >= 0; i--) {
            if (i in self && sought === self[i])
                return i;
        }
        return -1;
    };
}

//
// Object
// ======
//

// ES5 15.2.3.2
// http://es5.github.com/#x15.2.3.2
if (!Object.getPrototypeOf) {
    // https://github.com/kriskowal/es5-shim/issues#issue/2
    // http://ejohn.org/blog/objectgetprototypeof/
    // recommended by fschaefer on github
    Object.getPrototypeOf = function getPrototypeOf(object) {
        return object.__proto__ || (
            object.constructor ?
            object.constructor.prototype :
            prototypeOfObject
        );
    };
}

// ES5 15.2.3.3
// http://es5.github.com/#x15.2.3.3
if (!Object.getOwnPropertyDescriptor) {
    var ERR_NON_OBJECT = "Object.getOwnPropertyDescriptor called on a " +
                         "non-object: ";
    Object.getOwnPropertyDescriptor = function getOwnPropertyDescriptor(object, property) {
        if ((typeof object != "object" && typeof object != "function") || object === null)
            throw new TypeError(ERR_NON_OBJECT + object);
        // If object does not owns property return undefined immediately.
        if (!owns(object, property))
            return;

        var descriptor, getter, setter;

        // If object has a property then it's for sure both `enumerable` and
        // `configurable`.
        descriptor =  { enumerable: true, configurable: true };

        // If JS engine supports accessor properties then property may be a
        // getter or setter.
        if (supportsAccessors) {
            // Unfortunately `__lookupGetter__` will return a getter even
            // if object has own non getter property along with a same named
            // inherited getter. To avoid misbehavior we temporary remove
            // `__proto__` so that `__lookupGetter__` will return getter only
            // if it's owned by an object.
            var prototype = object.__proto__;
            object.__proto__ = prototypeOfObject;

            var getter = lookupGetter(object, property);
            var setter = lookupSetter(object, property);

            // Once we have getter and setter we can put values back.
            object.__proto__ = prototype;

            if (getter || setter) {
                if (getter) descriptor.get = getter;
                if (setter) descriptor.set = setter;

                // If it was accessor property we're done and return here
                // in order to avoid adding `value` to the descriptor.
                return descriptor;
            }
        }

        // If we got this far we know that object has an own property that is
        // not an accessor so we set it as a value and return descriptor.
        descriptor.value = object[property];
        return descriptor;
    };
}

// ES5 15.2.3.4
// http://es5.github.com/#x15.2.3.4
if (!Object.getOwnPropertyNames) {
    Object.getOwnPropertyNames = function getOwnPropertyNames(object) {
        return Object.keys(object);
    };
}

// ES5 15.2.3.5
// http://es5.github.com/#x15.2.3.5
if (!Object.create) {
    Object.create = function create(prototype, properties) {
        var object;
        if (prototype === null) {
            object = { "__proto__": null };
        } else {
            if (typeof prototype != "object")
                throw new TypeError("typeof prototype["+(typeof prototype)+"] != 'object'");
            var Type = function () {};
            Type.prototype = prototype;
            object = new Type();
            // IE has no built-in implementation of `Object.getPrototypeOf`
            // neither `__proto__`, but this manually setting `__proto__` will
            // guarantee that `Object.getPrototypeOf` will work as expected with
            // objects created using `Object.create`
            object.__proto__ = prototype;
        }
        if (properties !== void 0)
            Object.defineProperties(object, properties);
        return object;
    };
}

// ES5 15.2.3.6
// http://es5.github.com/#x15.2.3.6

// Patch for WebKit and IE8 standard mode
// Designed by hax <hax.github.com>
// related issue: https://github.com/kriskowal/es5-shim/issues#issue/5
// IE8 Reference:
//     http://msdn.microsoft.com/en-us/library/dd282900.aspx
//     http://msdn.microsoft.com/en-us/library/dd229916.aspx
// WebKit Bugs:
//     https://bugs.webkit.org/show_bug.cgi?id=36423

function doesDefinePropertyWork(object) {
    try {
        Object.defineProperty(object, "sentinel", {});
        return "sentinel" in object;
    } catch (exception) {
        // returns falsy
    }
}

// check whether defineProperty works if it's given. Otherwise,
// shim partially.
if (Object.defineProperty) {
    var definePropertyWorksOnObject = doesDefinePropertyWork({});
    var definePropertyWorksOnDom = typeof document == "undefined" ||
        doesDefinePropertyWork(document.createElement("div"));
    if (!definePropertyWorksOnObject || !definePropertyWorksOnDom) {
        var definePropertyFallback = Object.defineProperty;
    }
}

if (!Object.defineProperty || definePropertyFallback) {
    var ERR_NON_OBJECT_DESCRIPTOR = "Property description must be an object: ";
    var ERR_NON_OBJECT_TARGET = "Object.defineProperty called on non-object: "
    var ERR_ACCESSORS_NOT_SUPPORTED = "getters & setters can not be defined " +
                                      "on this javascript engine";

    Object.defineProperty = function defineProperty(object, property, descriptor) {
        if ((typeof object != "object" && typeof object != "function") || object === null)
            throw new TypeError(ERR_NON_OBJECT_TARGET + object);
        if ((typeof descriptor != "object" && typeof descriptor != "function") || descriptor === null)
            throw new TypeError(ERR_NON_OBJECT_DESCRIPTOR + descriptor);

        // make a valiant attempt to use the real defineProperty
        // for I8's DOM elements.
        if (definePropertyFallback) {
            try {
                return definePropertyFallback.call(Object, object, property, descriptor);
            } catch (exception) {
                // try the shim if the real one doesn't work
            }
        }

        // If it's a data property.
        if (owns(descriptor, "value")) {
            // fail silently if "writable", "enumerable", or "configurable"
            // are requested but not supported
            /*
            // alternate approach:
            if ( // can't implement these features; allow false but not true
                !(owns(descriptor, "writable") ? descriptor.writable : true) ||
                !(owns(descriptor, "enumerable") ? descriptor.enumerable : true) ||
                !(owns(descriptor, "configurable") ? descriptor.configurable : true)
            )
                throw new RangeError(
                    "This implementation of Object.defineProperty does not " +
                    "support configurable, enumerable, or writable."
                );
            */

            if (supportsAccessors && (lookupGetter(object, property) ||
                                      lookupSetter(object, property)))
            {
                // As accessors are supported only on engines implementing
                // `__proto__` we can safely override `__proto__` while defining
                // a property to make sure that we don't hit an inherited
                // accessor.
                var prototype = object.__proto__;
                object.__proto__ = prototypeOfObject;
                // Deleting a property anyway since getter / setter may be
                // defined on object itself.
                delete object[property];
                object[property] = descriptor.value;
                // Setting original `__proto__` back now.
                object.__proto__ = prototype;
            } else {
                object[property] = descriptor.value;
            }
        } else {
            if (!supportsAccessors)
                throw new TypeError(ERR_ACCESSORS_NOT_SUPPORTED);
            // If we got that far then getters and setters can be defined !!
            if (owns(descriptor, "get"))
                defineGetter(object, property, descriptor.get);
            if (owns(descriptor, "set"))
                defineSetter(object, property, descriptor.set);
        }

        return object;
    };
}

// ES5 15.2.3.7
// http://es5.github.com/#x15.2.3.7
if (!Object.defineProperties) {
    Object.defineProperties = function defineProperties(object, properties) {
        for (var property in properties) {
            if (owns(properties, property))
                Object.defineProperty(object, property, properties[property]);
        }
        return object;
    };
}

// ES5 15.2.3.8
// http://es5.github.com/#x15.2.3.8
if (!Object.seal) {
    Object.seal = function seal(object) {
        // this is misleading and breaks feature-detection, but
        // allows "securable" code to "gracefully" degrade to working
        // but insecure code.
        return object;
    };
}

// ES5 15.2.3.9
// http://es5.github.com/#x15.2.3.9
if (!Object.freeze) {
    Object.freeze = function freeze(object) {
        // this is misleading and breaks feature-detection, but
        // allows "securable" code to "gracefully" degrade to working
        // but insecure code.
        return object;
    };
}

// detect a Rhino bug and patch it
try {
    Object.freeze(function () {});
} catch (exception) {
    Object.freeze = (function freeze(freezeObject) {
        return function freeze(object) {
            if (typeof object == "function") {
                return object;
            } else {
                return freezeObject(object);
            }
        };
    })(Object.freeze);
}

// ES5 15.2.3.10
// http://es5.github.com/#x15.2.3.10
if (!Object.preventExtensions) {
    Object.preventExtensions = function preventExtensions(object) {
        // this is misleading and breaks feature-detection, but
        // allows "securable" code to "gracefully" degrade to working
        // but insecure code.
        return object;
    };
}

// ES5 15.2.3.11
// http://es5.github.com/#x15.2.3.11
if (!Object.isSealed) {
    Object.isSealed = function isSealed(object) {
        return false;
    };
}

// ES5 15.2.3.12
// http://es5.github.com/#x15.2.3.12
if (!Object.isFrozen) {
    Object.isFrozen = function isFrozen(object) {
        return false;
    };
}

// ES5 15.2.3.13
// http://es5.github.com/#x15.2.3.13
if (!Object.isExtensible) {
    Object.isExtensible = function isExtensible(object) {
        // 1. If Type(O) is not Object throw a TypeError exception.
        if (Object(object) === object) {
            throw new TypeError(); // TODO message
        }
        // 2. Return the Boolean value of the [[Extensible]] internal property of O.
        var name = '';
        while (owns(object, name)) {
            name += '?';
        }
        object[name] = true;
        var returnValue = owns(object, name);
        delete object[name];
        return returnValue;
    };
}

// ES5 15.2.3.14
// http://es5.github.com/#x15.2.3.14
if (!Object.keys) {
    // http://whattheheadsaid.com/2010/10/a-safer-object-keys-compatibility-implementation
    var hasDontEnumBug = true,
        dontEnums = [
            "toString",
            "toLocaleString",
            "valueOf",
            "hasOwnProperty",
            "isPrototypeOf",
            "propertyIsEnumerable",
            "constructor"
        ],
        dontEnumsLength = dontEnums.length;

    for (var key in {"toString": null})
        hasDontEnumBug = false;

    Object.keys = function keys(object) {

        if ((typeof object != "object" && typeof object != "function") || object === null)
            throw new TypeError("Object.keys called on a non-object");

        var keys = [];
        for (var name in object) {
            if (owns(object, name)) {
                keys.push(name);
            }
        }

        if (hasDontEnumBug) {
            for (var i = 0, ii = dontEnumsLength; i < ii; i++) {
                var dontEnum = dontEnums[i];
                if (owns(object, dontEnum)) {
                    keys.push(dontEnum);
                }
            }
        }

        return keys;
    };

}

//
// Date
// ====
//

// ES5 15.9.5.43
// http://es5.github.com/#x15.9.5.43
// This function returns a String value represent the instance in time 
// represented by this Date object. The format of the String is the Date Time 
// string format defined in 15.9.1.15. All fields are present in the String. 
// The time zone is always UTC, denoted by the suffix Z. If the time value of 
// this object is not a finite Number a RangeError exception is thrown.
if (!Date.prototype.toISOString || (new Date(-62198755200000).toISOString().indexOf('-000001') === -1)) {
    Date.prototype.toISOString = function toISOString() {
        var result, length, value, year;
        if (!isFinite(this))
            throw new RangeError;

        // the date time string format is specified in 15.9.1.15.
        result = [this.getUTCMonth() + 1, this.getUTCDate(),
            this.getUTCHours(), this.getUTCMinutes(), this.getUTCSeconds()];
        year = this.getUTCFullYear();
        year = (year < 0 ? '-' : (year > 9999 ? '+' : '')) + ('00000' + Math.abs(year)).slice(0 <= year && year <= 9999 ? -4 : -6);

        length = result.length;
        while (length--) {
            value = result[length];
            // pad months, days, hours, minutes, and seconds to have two digits.
            if (value < 10)
                result[length] = "0" + value;
        }
        // pad milliseconds to have three digits.
        return year + "-" + result.slice(0, 2).join("-") + "T" + result.slice(2).join(":") + "." +
            ("000" + this.getUTCMilliseconds()).slice(-3) + "Z";
    }
}

// ES5 15.9.4.4
// http://es5.github.com/#x15.9.4.4
if (!Date.now) {
    Date.now = function now() {
        return new Date().getTime();
    };
}

// ES5 15.9.5.44
// http://es5.github.com/#x15.9.5.44
// This function provides a String representation of a Date object for use by 
// JSON.stringify (15.12.3).
if (!Date.prototype.toJSON) {
    Date.prototype.toJSON = function toJSON(key) {
        // When the toJSON method is called with argument key, the following 
        // steps are taken:

        // 1.  Let O be the result of calling ToObject, giving it the this
        // value as its argument.
        // 2. Let tv be ToPrimitive(O, hint Number).
        // 3. If tv is a Number and is not finite, return null.
        // XXX
        // 4. Let toISO be the result of calling the [[Get]] internal method of
        // O with argument "toISOString".
        // 5. If IsCallable(toISO) is false, throw a TypeError exception.
        if (typeof this.toISOString != "function")
            throw new TypeError(); // TODO message
        // 6. Return the result of calling the [[Call]] internal method of
        //  toISO with O as the this value and an empty argument list.
        return this.toISOString();

        // NOTE 1 The argument is ignored.

        // NOTE 2 The toJSON function is intentionally generic; it does not
        // require that its this value be a Date object. Therefore, it can be
        // transferred to other kinds of objects for use as a method. However,
        // it does require that any such object have a toISOString method. An
        // object is free to use the argument key to filter its
        // stringification.
    };
}

// ES5 15.9.4.2
// http://es5.github.com/#x15.9.4.2
// based on work shared by Daniel Friesen (dantman)
// http://gist.github.com/303249
if (Date.parse("+275760-09-13T00:00:00.000Z") !== 8.64e15) {
    // XXX global assignment won't work in embeddings that use
    // an alternate object for the context.
    Date = (function(NativeDate) {

        // Date.length === 7
        var Date = function Date(Y, M, D, h, m, s, ms) {
            var length = arguments.length;
            if (this instanceof NativeDate) {
                var date = length == 1 && String(Y) === Y ? // isString(Y)
                    // We explicitly pass it through parse:
                    new NativeDate(Date.parse(Y)) :
                    // We have to manually make calls depending on argument
                    // length here
                    length >= 7 ? new NativeDate(Y, M, D, h, m, s, ms) :
                    length >= 6 ? new NativeDate(Y, M, D, h, m, s) :
                    length >= 5 ? new NativeDate(Y, M, D, h, m) :
                    length >= 4 ? new NativeDate(Y, M, D, h) :
                    length >= 3 ? new NativeDate(Y, M, D) :
                    length >= 2 ? new NativeDate(Y, M) :
                    length >= 1 ? new NativeDate(Y) :
                                  new NativeDate();
                // Prevent mixups with unfixed Date object
                date.constructor = Date;
                return date;
            }
            return NativeDate.apply(this, arguments);
        };

        // 15.9.1.15 Date Time String Format.
        var isoDateExpression = new RegExp("^" +
            "(\\d{4}|[\+\-]\\d{6})" + // four-digit year capture or sign + 6-digit extended year
            "(?:-(\\d{2})" + // optional month capture
            "(?:-(\\d{2})" + // optional day capture
            "(?:" + // capture hours:minutes:seconds.milliseconds
                "T(\\d{2})" + // hours capture
                ":(\\d{2})" + // minutes capture
                "(?:" + // optional :seconds.milliseconds
                    ":(\\d{2})" + // seconds capture
                    "(?:\\.(\\d{3}))?" + // milliseconds capture
                ")?" +
            "(?:" + // capture UTC offset component
                "Z|" + // UTC capture
                "(?:" + // offset specifier +/-hours:minutes
                    "([-+])" + // sign capture
                    "(\\d{2})" + // hours offset capture
                    ":(\\d{2})" + // minutes offset capture
                ")" +
            ")?)?)?)?" +
        "$");

        // Copy any custom methods a 3rd party library may have added
        for (var key in NativeDate)
            Date[key] = NativeDate[key];

        // Copy "native" methods explicitly; they may be non-enumerable
        Date.now = NativeDate.now;
        Date.UTC = NativeDate.UTC;
        Date.prototype = NativeDate.prototype;
        Date.prototype.constructor = Date;

        // Upgrade Date.parse to handle simplified ISO 8601 strings
        Date.parse = function parse(string) {
            var match = isoDateExpression.exec(string);
            if (match) {
                match.shift(); // kill match[0], the full match
                // parse months, days, hours, minutes, seconds, and milliseconds
                for (var i = 1; i < 7; i++) {
                    // provide default values if necessary
                    match[i] = +(match[i] || (i < 3 ? 1 : 0));
                    // match[1] is the month. Months are 0-11 in JavaScript
                    // `Date` objects, but 1-12 in ISO notation, so we
                    // decrement.
                    if (i == 1)
                        match[i]--;
                }

                // parse the UTC offset component
                var minuteOffset = +match.pop(), hourOffset = +match.pop(), sign = match.pop();

                // compute the explicit time zone offset if specified
                var offset = 0;
                if (sign) {
                    // detect invalid offsets and return early
                    if (hourOffset > 23 || minuteOffset > 59)
                        return NaN;

                    // express the provided time zone offset in minutes. The offset is
                    // negative for time zones west of UTC; positive otherwise.
                    offset = (hourOffset * 60 + minuteOffset) * 6e4 * (sign == "+" ? -1 : 1);
                }

                // Date.UTC for years between 0 and 99 converts year to 1900 + year
                // The Gregorian calendar has a 400-year cycle, so 
                // to Date.UTC(year + 400, .... ) - 12622780800000 == Date.UTC(year, ...),
                // where 12622780800000 - number of milliseconds in Gregorian calendar 400 years
                var year = +match[0];
                if (0 <= year && year <= 99) {
                    match[0] = year + 400;
                    return NativeDate.UTC.apply(this, match) + offset - 12622780800000;
                }

                // compute a new UTC date value, accounting for the optional offset
                return NativeDate.UTC.apply(this, match) + offset;
            }
            return NativeDate.parse.apply(this, arguments);
        };

        return Date;
    })(Date);
}

//
// String
// ======
//

// ES5 15.5.4.20
// http://es5.github.com/#x15.5.4.20
var ws = "\x09\x0A\x0B\x0C\x0D\x20\xA0\u1680\u180E\u2000\u2001\u2002\u2003" +
    "\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028" +
    "\u2029\uFEFF";
if (!String.prototype.trim || ws.trim()) {
    // http://blog.stevenlevithan.com/archives/faster-trim-javascript
    // http://perfectionkills.com/whitespace-deviations/
    ws = "[" + ws + "]";
    var trimBeginRegexp = new RegExp("^" + ws + ws + "*"),
        trimEndRegexp = new RegExp(ws + ws + "*$");
    String.prototype.trim = function trim() {
        return String(this).replace(trimBeginRegexp, "").replace(trimEndRegexp, "");
    };
}

//
// Util
// ======
//

// ES5 9.4
// http://es5.github.com/#x9.4
// http://jsperf.com/to-integer
var toInteger = function (n) {
    n = +n;
    if (n !== n) // isNaN
        n = 0;
    else if (n !== 0 && n !== (1/0) && n !== -(1/0))
        n = (n > 0 || -1) * Math.floor(Math.abs(n));
    return n;
};

var prepareString = "a"[0] != "a",
    // ES5 9.9
    // http://es5.github.com/#x9.9
    toObject = function (o) {
        if (o == null) { // this matches both null and undefined
            throw new TypeError(); // TODO message
        }
        // If the implementation doesn't support by-index access of
        // string characters (ex. IE < 7), split the string
        if (prepareString && typeof o == "string" && o) {
            return o.split("");
        }
        return Object(o);
    };
});/* vim:ts=4:sts=4:sw=4:
 * ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *      Irakli Gozalishvili <rfobic@gmail.com> (http://jeditoolkit.com)
 *      Mike de Boer <mike AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/lib/event_emitter', ['require', 'exports', 'module' ], function(require, exports, module) {
"use strict";

var EventEmitter = {};

EventEmitter._emit =
EventEmitter._dispatchEvent = function(eventName, e) {
    this._eventRegistry = this._eventRegistry || {};
    this._defaultHandlers = this._defaultHandlers || {};

    var listeners = this._eventRegistry[eventName] || [];
    var defaultHandler = this._defaultHandlers[eventName];
    if (!listeners.length && !defaultHandler)
        return;

    e = e || {};
    e.type = eventName;
    
    if (!e.stopPropagation) {
        e.stopPropagation = function() {
            this.propagationStopped = true;
        };
    }
    
    if (!e.preventDefault) {
        e.preventDefault = function() {
            this.defaultPrevented = true;
        };
    }

    for (var i=0; i<listeners.length; i++) {
        listeners[i](e);
        if (e.propagationStopped)
            break;
    }
    
    if (defaultHandler && !e.defaultPrevented)
        defaultHandler(e);
};

EventEmitter.setDefaultHandler = function(eventName, callback) {
    this._defaultHandlers = this._defaultHandlers || {};
    
    if (this._defaultHandlers[eventName])
        throw new Error("The default handler for '" + eventName + "' is already set");
        
    this._defaultHandlers[eventName] = callback;
};

EventEmitter.on =
EventEmitter.addEventListener = function(eventName, callback) {
    this._eventRegistry = this._eventRegistry || {};

    var listeners = this._eventRegistry[eventName];
    if (!listeners)
        var listeners = this._eventRegistry[eventName] = [];

    if (listeners.indexOf(callback) == -1)
        listeners.push(callback);
};

EventEmitter.removeListener =
EventEmitter.removeEventListener = function(eventName, callback) {
    this._eventRegistry = this._eventRegistry || {};

    var listeners = this._eventRegistry[eventName];
    if (!listeners)
        return;

    var index = listeners.indexOf(callback);
    if (index !== -1)
        listeners.splice(index, 1);
};

EventEmitter.removeAllListeners = function(eventName) {
    if (this._eventRegistry) this._eventRegistry[eventName] = [];
};

exports.EventEmitter = EventEmitter;

});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/lib/oop', ['require', 'exports', 'module' ], function(require, exports, module) {
"use strict";

exports.inherits = (function() {
    var tempCtor = function() {};
    return function(ctor, superCtor) {
        tempCtor.prototype = superCtor.prototype;
        ctor.super_ = superCtor.prototype;
        ctor.prototype = new tempCtor();
        ctor.prototype.constructor = ctor;
    };
}());

exports.mixin = function(obj, mixin) {
    for (var key in mixin) {
        obj[key] = mixin[key];
    }
};

exports.implement = function(proto, mixin) {
    exports.mixin(proto, mixin);
};

});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */
 
define('ace/mode/coffee_worker', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/worker/mirror', 'ace/mode/coffee/coffee-script'], function(require, exports, module) {
"use strict";
    
var oop = require("../lib/oop");
var Mirror = require("../worker/mirror").Mirror;
var coffee = require("../mode/coffee/coffee-script");

window.addEventListener = function() {};


var Worker = exports.Worker = function(sender) {
    Mirror.call(this, sender);
    this.setTimeout(200);
};

oop.inherits(Worker, Mirror);

(function() {
    
    this.onUpdate = function() {
        var value = this.doc.getValue();
        
        try {
            coffee.parse(value);
        } catch(e) {
            var m = e.message.match(/Parse error on line (\d+): (.*)/);
            if (m) {
                this.sender.emit("error", {
                    row: parseInt(m[1]) - 1,
                    column: null,
                    text: m[2],
                    type: "error"
                });
                return;
            }
            
            if (e instanceof SyntaxError) {
                var m = e.message.match(/ on line (\d+)/);
                if (m) {                    
                    this.sender.emit("error", {
                        row: parseInt(m[1]) - 1,
                        column: null,
                        text: e.message.replace(m[0], ""),
                        type: "error"
                    });
                }
            }
            return;
        }
        this.sender.emit("ok");
    };
    
}).call(Worker.prototype);

});define('ace/worker/mirror', ['require', 'exports', 'module' , 'ace/document', 'ace/lib/lang'], function(require, exports, module) {
"use strict";

var Document = require("../document").Document;
var lang = require("../lib/lang");
    
var Mirror = exports.Mirror = function(sender) {
    this.sender = sender;
    var doc = this.doc = new Document("");
    
    var deferredUpdate = this.deferredUpdate = lang.deferredCall(this.onUpdate.bind(this));
    
    var _self = this;
    sender.on("change", function(e) {
        doc.applyDeltas([e.data]);        
        deferredUpdate.schedule(_self.$timeout);
    });
};

(function() {
    
    this.$timeout = 500;
    
    this.setTimeout = function(timeout) {
        this.$timeout = timeout;
    };
    
    this.setValue = function(value) {
        this.doc.setValue(value);
        this.deferredUpdate.schedule(this.$timeout);
    };
    
    this.getValue = function(callbackId) {
        this.sender.callback(this.doc.getValue(), callbackId);
    };
    
    this.onUpdate = function() {
        // abstract method
    };
    
}).call(Mirror.prototype);

});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/document', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/event_emitter', 'ace/range', 'ace/anchor'], function(require, exports, module) {
"use strict";

var oop = require("./lib/oop");
var EventEmitter = require("./lib/event_emitter").EventEmitter;
var Range = require("./range").Range;
var Anchor = require("./anchor").Anchor;

var Document = function(text) {
    this.$lines = [];

    if (Array.isArray(text)) {
        this.insertLines(0, text);
    }
    // There has to be one line at least in the document. If you pass an empty
    // string to the insert function, nothing will happen. Workaround.
    else if (text.length == 0) {
        this.$lines = [""];
    } else {
        this.insert({row: 0, column:0}, text);
    }
};

(function() {

    oop.implement(this, EventEmitter);

    this.setValue = function(text) {
        var len = this.getLength();
        this.remove(new Range(0, 0, len, this.getLine(len-1).length));
        this.insert({row: 0, column:0}, text);
    };

    this.getValue = function() {
        return this.getAllLines().join(this.getNewLineCharacter());
    };

    this.createAnchor = function(row, column) {
        return new Anchor(this, row, column);
    };

    // check for IE split bug
    if ("aaa".split(/a/).length == 0)
        this.$split = function(text) {
            return text.replace(/\r\n|\r/g, "\n").split("\n");
        }
    else
        this.$split = function(text) {
            return text.split(/\r\n|\r|\n/);
        };


    this.$detectNewLine = function(text) {
        var match = text.match(/^.*?(\r\n|\r|\n)/m);
        if (match) {
            this.$autoNewLine = match[1];
        } else {
            this.$autoNewLine = "\n";
        }
    };

    this.getNewLineCharacter = function() {
      switch (this.$newLineMode) {
          case "windows":
              return "\r\n";

          case "unix":
              return "\n";

          case "auto":
              return this.$autoNewLine;
      }
    };

    this.$autoNewLine = "\n";
    this.$newLineMode = "auto";
    this.setNewLineMode = function(newLineMode) {
        if (this.$newLineMode === newLineMode)
            return;

        this.$newLineMode = newLineMode;
    };

    this.getNewLineMode = function() {
        return this.$newLineMode;
    };

    this.isNewLine = function(text) {
        return (text == "\r\n" || text == "\r" || text == "\n");
    };

    /**
     * Get a verbatim copy of the given line as it is in the document
     */
    this.getLine = function(row) {
        return this.$lines[row] || "";
    };

    this.getLines = function(firstRow, lastRow) {
        return this.$lines.slice(firstRow, lastRow + 1);
    };

    /**
     * Returns all lines in the document as string array. Warning: The caller
     * should not modify this array!
     */
    this.getAllLines = function() {
        return this.getLines(0, this.getLength());
    };

    this.getLength = function() {
        return this.$lines.length;
    };

    this.getTextRange = function(range) {
        if (range.start.row == range.end.row) {
            return this.$lines[range.start.row].substring(range.start.column,
                                                         range.end.column);
        }
        else {
            var lines = [];
            lines.push(this.$lines[range.start.row].substring(range.start.column));
            lines.push.apply(lines, this.getLines(range.start.row+1, range.end.row-1));
            lines.push(this.$lines[range.end.row].substring(0, range.end.column));
            return lines.join(this.getNewLineCharacter());
        }
    };

    this.$clipPosition = function(position) {
        var length = this.getLength();
        if (position.row >= length) {
            position.row = Math.max(0, length - 1);
            position.column = this.getLine(length-1).length;
        }
        return position;
    };

    this.insert = function(position, text) {
        if (text.length == 0)
            return position;

        position = this.$clipPosition(position);

        if (this.getLength() <= 1)
            this.$detectNewLine(text);

        var lines = this.$split(text);
        var firstLine = lines.splice(0, 1)[0];
        var lastLine = lines.length == 0 ? null : lines.splice(lines.length - 1, 1)[0];

        position = this.insertInLine(position, firstLine);
        if (lastLine !== null) {
            position = this.insertNewLine(position); // terminate first line
            position = this.insertLines(position.row, lines);
            position = this.insertInLine(position, lastLine || "");
        }
        return position;
    };

    this.insertLines = function(row, lines) {
        if (lines.length == 0)
            return {row: row, column: 0};

        var args = [row, 0];
        args.push.apply(args, lines);
        this.$lines.splice.apply(this.$lines, args);

        var range = new Range(row, 0, row + lines.length, 0);
        var delta = {
            action: "insertLines",
            range: range,
            lines: lines
        };
        this._emit("change", { data: delta });
        return range.end;
    };

    this.insertNewLine = function(position) {
        position = this.$clipPosition(position);
        var line = this.$lines[position.row] || "";

        this.$lines[position.row] = line.substring(0, position.column);
        this.$lines.splice(position.row + 1, 0, line.substring(position.column, line.length));

        var end = {
            row : position.row + 1,
            column : 0
        };

        var delta = {
            action: "insertText",
            range: Range.fromPoints(position, end),
            text: this.getNewLineCharacter()
        };
        this._emit("change", { data: delta });

        return end;
    };

    this.insertInLine = function(position, text) {
        if (text.length == 0)
            return position;

        var line = this.$lines[position.row] || "";

        this.$lines[position.row] = line.substring(0, position.column) + text
                + line.substring(position.column);

        var end = {
            row : position.row,
            column : position.column + text.length
        };

        var delta = {
            action: "insertText",
            range: Range.fromPoints(position, end),
            text: text
        };
        this._emit("change", { data: delta });

        return end;
    };

    this.remove = function(range) {
        // clip to document
        range.start = this.$clipPosition(range.start);
        range.end = this.$clipPosition(range.end);

        if (range.isEmpty())
            return range.start;

        var firstRow = range.start.row;
        var lastRow = range.end.row;

        if (range.isMultiLine()) {
            var firstFullRow = range.start.column == 0 ? firstRow : firstRow + 1;
            var lastFullRow = lastRow - 1;

            if (range.end.column > 0)
                this.removeInLine(lastRow, 0, range.end.column);

            if (lastFullRow >= firstFullRow)
                this.removeLines(firstFullRow, lastFullRow);

            if (firstFullRow != firstRow) {
                this.removeInLine(firstRow, range.start.column, this.getLine(firstRow).length);
                this.removeNewLine(range.start.row);
            }
        }
        else {
            this.removeInLine(firstRow, range.start.column, range.end.column);
        }
        return range.start;
    };

    this.removeInLine = function(row, startColumn, endColumn) {
        if (startColumn == endColumn)
            return;

        var range = new Range(row, startColumn, row, endColumn);
        var line = this.getLine(row);
        var removed = line.substring(startColumn, endColumn);
        var newLine = line.substring(0, startColumn) + line.substring(endColumn, line.length);
        this.$lines.splice(row, 1, newLine);

        var delta = {
            action: "removeText",
            range: range,
            text: removed
        };
        this._emit("change", { data: delta });
        return range.start;
    };

    /**
     * Removes a range of full lines
     *
     * @param firstRow {Integer} The first row to be removed
     * @param lastRow {Integer} The last row to be removed
     * @return {String[]} The removed lines
     */
    this.removeLines = function(firstRow, lastRow) {
        var range = new Range(firstRow, 0, lastRow + 1, 0);
        var removed = this.$lines.splice(firstRow, lastRow - firstRow + 1);

        var delta = {
            action: "removeLines",
            range: range,
            nl: this.getNewLineCharacter(),
            lines: removed
        };
        this._emit("change", { data: delta });
        return removed;
    };

    this.removeNewLine = function(row) {
        var firstLine = this.getLine(row);
        var secondLine = this.getLine(row+1);

        var range = new Range(row, firstLine.length, row+1, 0);
        var line = firstLine + secondLine;

        this.$lines.splice(row, 2, line);

        var delta = {
            action: "removeText",
            range: range,
            text: this.getNewLineCharacter()
        };
        this._emit("change", { data: delta });
    };

    this.replace = function(range, text) {
        if (text.length == 0 && range.isEmpty())
            return range.start;

        // Shortcut: If the text we want to insert is the same as it is already
        // in the document, we don't have to replace anything.
        if (text == this.getTextRange(range))
            return range.end;

        this.remove(range);
        if (text) {
            var end = this.insert(range.start, text);
        }
        else {
            end = range.start;
        }

        return end;
    };

    this.applyDeltas = function(deltas) {
        for (var i=0; i<deltas.length; i++) {
            var delta = deltas[i];
            var range = Range.fromPoints(delta.range.start, delta.range.end);

            if (delta.action == "insertLines")
                this.insertLines(range.start.row, delta.lines);
            else if (delta.action == "insertText")
                this.insert(range.start, delta.text);
            else if (delta.action == "removeLines")
                this.removeLines(range.start.row, range.end.row - 1);
            else if (delta.action == "removeText")
                this.remove(range);
        }
    };

    this.revertDeltas = function(deltas) {
        for (var i=deltas.length-1; i>=0; i--) {
            var delta = deltas[i];

            var range = Range.fromPoints(delta.range.start, delta.range.end);

            if (delta.action == "insertLines")
                this.removeLines(range.start.row, range.end.row - 1);
            else if (delta.action == "insertText")
                this.remove(range);
            else if (delta.action == "removeLines")
                this.insertLines(range.start.row, delta.lines);
            else if (delta.action == "removeText")
                this.insert(range.start, delta.text);
        }
    };

}).call(Document.prototype);

exports.Document = Document;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/range', ['require', 'exports', 'module' ], function(require, exports, module) {
"use strict";

var Range = function(startRow, startColumn, endRow, endColumn) {
    this.start = {
        row: startRow,
        column: startColumn
    };

    this.end = {
        row: endRow,
        column: endColumn
    };
};

(function() {
    this.isEequal = function(range) {
        return this.start.row == range.start.row &&
            this.end.row == range.end.row &&
            this.start.column == range.start.column &&
            this.end.column == range.end.column
    };

    this.toString = function() {
        return ("Range: [" + this.start.row + "/" + this.start.column +
            "] -> [" + this.end.row + "/" + this.end.column + "]");
    };

    this.contains = function(row, column) {
        return this.compare(row, column) == 0;
    };

    /**
     * Compares this range (A) with another range (B), where B is the passed in
     * range.
     *
     * Return values:
     *  -2: (B) is infront of (A) and doesn't intersect with (A)
     *  -1: (B) begins before (A) but ends inside of (A)
     *   0: (B) is completly inside of (A) OR (A) is complety inside of (B)
     *  +1: (B) begins inside of (A) but ends outside of (A)
     *  +2: (B) is after (A) and doesn't intersect with (A)
     *
     *  42: FTW state: (B) ends in (A) but starts outside of (A)
     */
    this.compareRange = function(range) {
        var cmp,
            end = range.end,
            start = range.start;

        cmp = this.compare(end.row, end.column);
        if (cmp == 1) {
            cmp = this.compare(start.row, start.column);
            if (cmp == 1) {
                return 2;
            } else if (cmp == 0) {
                return 1;
            } else {
                return 0;
            }
        } else if (cmp == -1) {
            return -2;
        } else {
            cmp = this.compare(start.row, start.column);
            if (cmp == -1) {
                return -1;
            } else if (cmp == 1) {
                return 42;
            } else {
                return 0;
            }
        }
    }

    this.comparePoint = function(p) {
        return this.compare(p.row, p.column);
    }

    this.containsRange = function(range) {
        return this.comparePoint(range.start) == 0 && this.comparePoint(range.end) == 0;
    }

    this.isEnd = function(row, column) {
        return this.end.row == row && this.end.column == column;
    }

    this.isStart = function(row, column) {
        return this.start.row == row && this.start.column == column;
    }

    this.setStart = function(row, column) {
        if (typeof row == "object") {
            this.start.column = row.column;
            this.start.row = row.row;
        } else {
            this.start.row = row;
            this.start.column = column;
        }
    }

    this.setEnd = function(row, column) {
        if (typeof row == "object") {
            this.end.column = row.column;
            this.end.row = row.row;
        } else {
            this.end.row = row;
            this.end.column = column;
        }
    }

    this.inside = function(row, column) {
        if (this.compare(row, column) == 0) {
            if (this.isEnd(row, column) || this.isStart(row, column)) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    this.insideStart = function(row, column) {
        if (this.compare(row, column) == 0) {
            if (this.isEnd(row, column)) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    this.insideEnd = function(row, column) {
        if (this.compare(row, column) == 0) {
            if (this.isStart(row, column)) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    this.compare = function(row, column) {
        if (!this.isMultiLine()) {
            if (row === this.start.row) {
                return column < this.start.column ? -1 : (column > this.end.column ? 1 : 0);
            };
        }

        if (row < this.start.row)
            return -1;

        if (row > this.end.row)
            return 1;

        if (this.start.row === row)
            return column >= this.start.column ? 0 : -1;

        if (this.end.row === row)
            return column <= this.end.column ? 0 : 1;

        return 0;
    };

    /**
     * Like .compare(), but if isStart is true, return -1;
     */
    this.compareStart = function(row, column) {
        if (this.start.row == row && this.start.column == column) {
            return -1;
        } else {
            return this.compare(row, column);
        }
    }

    /**
     * Like .compare(), but if isEnd is true, return 1;
     */
    this.compareEnd = function(row, column) {
        if (this.end.row == row && this.end.column == column) {
            return 1;
        } else {
            return this.compare(row, column);
        }
    }

    this.compareInside = function(row, column) {
        if (this.end.row == row && this.end.column == column) {
            return 1;
        } else if (this.start.row == row && this.start.column == column) {
            return -1;
        } else {
            return this.compare(row, column);
        }
    }

    this.clipRows = function(firstRow, lastRow) {
        if (this.end.row > lastRow) {
            var end = {
                row: lastRow+1,
                column: 0
            };
        }

        if (this.start.row > lastRow) {
            var start = {
                row: lastRow+1,
                column: 0
            };
        }

        if (this.start.row < firstRow) {
            var start = {
                row: firstRow,
                column: 0
            };
        }

        if (this.end.row < firstRow) {
            var end = {
                row: firstRow,
                column: 0
            };
        }
        return Range.fromPoints(start || this.start, end || this.end);
    };

    this.extend = function(row, column) {
        var cmp = this.compare(row, column);

        if (cmp == 0)
            return this;
        else if (cmp == -1)
            var start = {row: row, column: column};
        else
            var end = {row: row, column: column};

        return Range.fromPoints(start || this.start, end || this.end);
    };

    this.isEmpty = function() {
        return (this.start.row == this.end.row && this.start.column == this.end.column);
    };

    this.isMultiLine = function() {
        return (this.start.row !== this.end.row);
    };

    this.clone = function() {
        return Range.fromPoints(this.start, this.end);
    };

    this.collapseRows = function() {
        if (this.end.column == 0)
            return new Range(this.start.row, 0, Math.max(this.start.row, this.end.row-1), 0)
        else
            return new Range(this.start.row, 0, this.end.row, 0)
    };

    this.toScreenRange = function(session) {
        var screenPosStart =
            session.documentToScreenPosition(this.start);
        var screenPosEnd =
            session.documentToScreenPosition(this.end);

        return new Range(
            screenPosStart.row, screenPosStart.column,
            screenPosEnd.row, screenPosEnd.column
        );
    };

}).call(Range.prototype);


Range.fromPoints = function(start, end) {
    return new Range(start.row, start.column, end.row, end.column);
};

exports.Range = Range;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/anchor', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/event_emitter'], function(require, exports, module) {
"use strict";

var oop = require("./lib/oop");
var EventEmitter = require("./lib/event_emitter").EventEmitter;

/**
 * An Anchor is a floating pointer in the document. Whenever text is inserted or
 * deleted before the cursor, the position of the cursor is updated
 */
var Anchor = exports.Anchor = function(doc, row, column) {
    this.document = doc;
    
    if (typeof column == "undefined")
        this.setPosition(row.row, row.column);
    else
        this.setPosition(row, column);

    this.$onChange = this.onChange.bind(this);
    doc.on("change", this.$onChange);
};

(function() {

    oop.implement(this, EventEmitter);
    
    this.getPosition = function() {
        return this.$clipPositionToDocument(this.row, this.column);
    };
    
    this.getDocument = function() {
        return this.document;
    };
    
    this.onChange = function(e) {
        var delta = e.data;
        var range = delta.range;
            
        if (range.start.row == range.end.row && range.start.row != this.row)
            return;
            
        if (range.start.row > this.row)
            return;
            
        if (range.start.row == this.row && range.start.column > this.column)
            return;
    
        var row = this.row;
        var column = this.column;
        
        if (delta.action === "insertText") {
            if (range.start.row === row && range.start.column <= column) {
                if (range.start.row === range.end.row) {
                    column += range.end.column - range.start.column;
                }
                else {
                    column -= range.start.column;
                    row += range.end.row - range.start.row;
                }
            }
            else if (range.start.row !== range.end.row && range.start.row < row) {
                row += range.end.row - range.start.row;
            }
        } else if (delta.action === "insertLines") {
            if (range.start.row <= row) {
                row += range.end.row - range.start.row;
            }
        }
        else if (delta.action == "removeText") {
            if (range.start.row == row && range.start.column < column) {
                if (range.end.column >= column)
                    column = range.start.column;
                else
                    column = Math.max(0, column - (range.end.column - range.start.column));
                
            } else if (range.start.row !== range.end.row && range.start.row < row) {
                if (range.end.row == row) {
                    column = Math.max(0, column - range.end.column) + range.start.column;
                }
                row -= (range.end.row - range.start.row);
            }
            else if (range.end.row == row) {
                row -= range.end.row - range.start.row;
                column = Math.max(0, column - range.end.column) + range.start.column;
            }
        } else if (delta.action == "removeLines") {
            if (range.start.row <= row) {
                if (range.end.row <= row)
                    row -= range.end.row - range.start.row;
                else {
                    row = range.start.row;
                    column = 0;
                }
            }
        }

        this.setPosition(row, column, true);
    };

    this.setPosition = function(row, column, noClip) {
        var pos;
        if (noClip) {
            pos = {
                row: row,
                column: column
            };
        }
        else {
            pos = this.$clipPositionToDocument(row, column);
        }
        
        if (this.row == pos.row && this.column == pos.column)
            return;
            
        var old = {
            row: this.row,
            column: this.column
        };
        
        this.row = pos.row;
        this.column = pos.column;
        this._emit("change", {
            old: old,
            value: pos
        });
    };
    
    this.detach = function() {
        this.document.removeEventListener("change", this.$onChange);
    };
    
    this.$clipPositionToDocument = function(row, column) {
        var pos = {};
    
        if (row >= this.document.getLength()) {
            pos.row = Math.max(0, this.document.getLength() - 1);
            pos.column = this.document.getLine(pos.row).length;
        }
        else if (row < 0) {
            pos.row = 0;
            pos.column = 0;
        }
        else {
            pos.row = row;
            pos.column = Math.min(this.document.getLine(pos.row).length, Math.max(0, column));
        }
        
        if (column < 0)
            pos.column = 0;
            
        return pos;
    };
    
}).call(Anchor.prototype);

});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/lib/lang', ['require', 'exports', 'module' ], function(require, exports, module) {
"use strict";

exports.stringReverse = function(string) {
    return string.split("").reverse().join("");
};

exports.stringRepeat = function (string, count) {
     return new Array(count + 1).join(string);
};

var trimBeginRegexp = /^\s\s*/;
var trimEndRegexp = /\s\s*$/;

exports.stringTrimLeft = function (string) {
    return string.replace(trimBeginRegexp, '');
};

exports.stringTrimRight = function (string) {
    return string.replace(trimEndRegexp, '');
};

exports.copyObject = function(obj) {
    var copy = {};
    for (var key in obj) {
        copy[key] = obj[key];
    }
    return copy;
};

exports.copyArray = function(array){
    var copy = [];
    for (var i=0, l=array.length; i<l; i++) {
        if (array[i] && typeof array[i] == "object")
            copy[i] = this.copyObject( array[i] );
        else 
            copy[i] = array[i];
    }
    return copy;
};

exports.deepCopy = function (obj) {
    if (typeof obj != "object") {
        return obj;
    }
    
    var copy = obj.constructor();
    for (var key in obj) {
        if (typeof obj[key] == "object") {
            copy[key] = this.deepCopy(obj[key]);
        } else {
            copy[key] = obj[key];
        }
    }
    return copy;
};

exports.arrayToMap = function(arr) {
    var map = {};
    for (var i=0; i<arr.length; i++) {
        map[arr[i]] = 1;
    }
    return map;

};

/**
 * splice out of 'array' anything that === 'value'
 */
exports.arrayRemove = function(array, value) {
  for (var i = 0; i <= array.length; i++) {
    if (value === array[i]) {
      array.splice(i, 1);
    }
  }
};

exports.escapeRegExp = function(str) {
    return str.replace(/([.*+?^${}()|[\]\/\\])/g, '\\$1');
};

exports.deferredCall = function(fcn) {

    var timer = null;
    var callback = function() {
        timer = null;
        fcn();
    };

    var deferred = function(timeout) {
        deferred.cancel();
        timer = setTimeout(callback, timeout || 0);
        return deferred;
    };

    deferred.schedule = deferred;

    deferred.call = function() {
        this.cancel();
        fcn();
        return deferred;
    };

    deferred.cancel = function() {
        clearTimeout(timer);
        timer = null;
        return deferred;
    };

    return deferred;
};

});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */
 
define('ace/mode/coffee/coffee-script', ['require', 'exports', 'module' , 'ace/mode/coffee/lexer', 'ace/mode/coffee/parser', 'ace/mode/coffee/nodes'], function(require, exports, module) {
    
    var Lexer = require("./lexer").Lexer;
    var parser = require("./parser");

    var lexer = new Lexer();
    parser.lexer = {
        lex: function() {
            var tag, _ref2;
            _ref2 = this.tokens[this.pos++] || [''], tag = _ref2[0], this.yytext = _ref2[1], this.yylineno = _ref2[2];
            return tag;
        },
        setInput: function(tokens) {
            this.tokens = tokens;
            return this.pos = 0;
        },
        upcomingInput: function() {
            return "";
        }
    };
    parser.yy = require('./nodes');
    
    exports.parse = function(code) {
        return parser.parse(lexer.tokenize(code));
    };
});/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/lexer', ['require', 'exports', 'module' , 'ace/mode/coffee/rewriter', 'ace/mode/coffee/helpers'], function(require, exports, module) {
    
  var ASSIGNED, BOOL, CALLABLE, CODE, COFFEE_ALIASES, COFFEE_ALIAS_MAP, COFFEE_KEYWORDS, COMMENT, COMPARE, COMPOUND_ASSIGN, HEREDOC, HEREDOC_ILLEGAL, HEREDOC_INDENT, HEREGEX, HEREGEX_OMIT, IDENTIFIER, INDEXABLE, JSTOKEN, JS_FORBIDDEN, JS_KEYWORDS, LINE_BREAK, LINE_CONTINUER, LOGIC, Lexer, MATH, MULTILINER, MULTI_DENT, NOT_REGEX, NOT_SPACED_REGEX, NO_NEWLINE, NUMBER, OPERATOR, REGEX, RELATION, RESERVED, Rewriter, SHIFT, SIMPLESTR, TRAILING_SPACES, UNARY, WHITESPACE, compact, count, key, last, starts, _ref;
  var __indexOf = Array.prototype.indexOf || function(item) {
    for (var i = 0, l = this.length; i < l; i++) {
      if (this[i] === item) return i;
    }
    return -1;
  };
  Rewriter = require('./rewriter').Rewriter;
  _ref = require('./helpers'), count = _ref.count, starts = _ref.starts, compact = _ref.compact, last = _ref.last;
  exports.Lexer = Lexer = (function() {
    function Lexer() {}
    Lexer.prototype.tokenize = function(code, opts) {
      var i;
      if (opts == null) {
        opts = {};
      }
      if (WHITESPACE.test(code)) {
        code = "\n" + code;
      }
      code = code.replace(/\r/g, '').replace(TRAILING_SPACES, '');
      this.code = code;
      this.line = opts.line || 0;
      this.indent = 0;
      this.indebt = 0;
      this.outdebt = 0;
      this.indents = [];
      this.tokens = [];
      i = 0;
      while (this.chunk = code.slice(i)) {
        i += this.identifierToken() || this.commentToken() || this.whitespaceToken() || this.lineToken() || this.heredocToken() || this.stringToken() || this.numberToken() || this.regexToken() || this.jsToken() || this.literalToken();
      }
      this.closeIndentation();
      if (opts.rewrite === false) {
        return this.tokens;
      }
      return (new Rewriter).rewrite(this.tokens);
    };
    Lexer.prototype.identifierToken = function() {
      var colon, forcedIdentifier, id, input, match, prev, tag, _ref2, _ref3;
      if (!(match = IDENTIFIER.exec(this.chunk))) {
        return 0;
      }
      input = match[0], id = match[1], colon = match[2];
      if (id === 'own' && this.tag() === 'FOR') {
        this.token('OWN', id);
        return id.length;
      }
      forcedIdentifier = colon || (prev = last(this.tokens)) && (((_ref2 = prev[0]) === '.' || _ref2 === '?.' || _ref2 === '::') || !prev.spaced && prev[0] === '@');
      tag = 'IDENTIFIER';
      if (__indexOf.call(JS_KEYWORDS, id) >= 0 || !forcedIdentifier && __indexOf.call(COFFEE_KEYWORDS, id) >= 0) {
        tag = id.toUpperCase();
        if (tag === 'WHEN' && (_ref3 = this.tag(), __indexOf.call(LINE_BREAK, _ref3) >= 0)) {
          tag = 'LEADING_WHEN';
        } else if (tag === 'FOR') {
          this.seenFor = true;
        } else if (tag === 'UNLESS') {
          tag = 'IF';
        } else if (__indexOf.call(UNARY, tag) >= 0) {
          tag = 'UNARY';
        } else if (__indexOf.call(RELATION, tag) >= 0) {
          if (tag !== 'INSTANCEOF' && this.seenFor) {
            tag = 'FOR' + tag;
            this.seenFor = false;
          } else {
            tag = 'RELATION';
            if (this.value() === '!') {
              this.tokens.pop();
              id = '!' + id;
            }
          }
        }
      }
      if (__indexOf.call(JS_FORBIDDEN, id) >= 0) {
        if (forcedIdentifier) {
          tag = 'IDENTIFIER';
          id = new String(id);
          id.reserved = true;
        } else if (__indexOf.call(RESERVED, id) >= 0) {
          this.identifierError(id);
        }
      }
      if (!forcedIdentifier) {
        if (__indexOf.call(COFFEE_ALIASES, id) >= 0) {
          id = COFFEE_ALIAS_MAP[id];
        }
        tag = (function() {
          switch (id) {
            case '!':
              return 'UNARY';
            case '==':
            case '!=':
              return 'COMPARE';
            case '&&':
            case '||':
              return 'LOGIC';
            case 'true':
            case 'false':
            case 'null':
            case 'undefined':
              return 'BOOL';
            case 'break':
            case 'continue':
            case 'debugger':
              return 'STATEMENT';
            default:
              return tag;
          }
        })();
      }
      this.token(tag, id);
      if (colon) {
        this.token(':', ':');
      }
      return input.length;
    };
    Lexer.prototype.numberToken = function() {
      var match, number;
      if (!(match = NUMBER.exec(this.chunk))) {
        return 0;
      }
      number = match[0];
      this.token('NUMBER', number);
      return number.length;
    };
    Lexer.prototype.stringToken = function() {
      var match, string;
      switch (this.chunk.charAt(0)) {
        case "'":
          if (!(match = SIMPLESTR.exec(this.chunk))) {
            return 0;
          }
          this.token('STRING', (string = match[0]).replace(MULTILINER, '\\\n'));
          break;
        case '"':
          if (!(string = this.balancedString(this.chunk, '"'))) {
            return 0;
          }
          if (0 < string.indexOf('#{', 1)) {
            this.interpolateString(string.slice(1, -1));
          } else {
            this.token('STRING', this.escapeLines(string));
          }
          break;
        default:
          return 0;
      }
      this.line += count(string, '\n');
      return string.length;
    };
    Lexer.prototype.heredocToken = function() {
      var doc, heredoc, match, quote;
      if (!(match = HEREDOC.exec(this.chunk))) {
        return 0;
      }
      heredoc = match[0];
      quote = heredoc.charAt(0);
      doc = this.sanitizeHeredoc(match[2], {
        quote: quote,
        indent: null
      });
      if (quote === '"' && 0 <= doc.indexOf('#{')) {
        this.interpolateString(doc, {
          heredoc: true
        });
      } else {
        this.token('STRING', this.makeString(doc, quote, true));
      }
      this.line += count(heredoc, '\n');
      return heredoc.length;
    };
    Lexer.prototype.commentToken = function() {
      var comment, here, match;
      if (!(match = this.chunk.match(COMMENT))) {
        return 0;
      }
      comment = match[0], here = match[1];
      if (here) {
        this.token('HERECOMMENT', this.sanitizeHeredoc(here, {
          herecomment: true,
          indent: Array(this.indent + 1).join(' ')
        }));
        this.token('TERMINATOR', '\n');
      }
      this.line += count(comment, '\n');
      return comment.length;
    };
    Lexer.prototype.jsToken = function() {
      var match, script;
      if (!(this.chunk.charAt(0) === '`' && (match = JSTOKEN.exec(this.chunk)))) {
        return 0;
      }
      this.token('JS', (script = match[0]).slice(1, -1));
      return script.length;
    };
    Lexer.prototype.regexToken = function() {
      var match, prev, regex, _ref2;
      if (this.chunk.charAt(0) !== '/') {
        return 0;
      }
      if (match = HEREGEX.exec(this.chunk)) {
        this.line += count(match[0], '\n');        
        return this.heregexToken(match);
      }
      prev = last(this.tokens);
      if (prev && (_ref2 = prev[0], __indexOf.call((prev.spaced ? NOT_REGEX : NOT_SPACED_REGEX), _ref2) >= 0)) {
        return 0;
      }
      if (!(match = REGEX.exec(this.chunk))) {
        return 0;
      }
      regex = match[0];
      this.token('REGEX', regex === '//' ? '/(?:)/' : regex);
      return regex.length;
    };
    Lexer.prototype.heregexToken = function(match) {
      var body, flags, heregex, re, tag, tokens, value, _i, _len, _ref2, _ref3, _ref4, _ref5;
      heregex = match[0], body = match[1], flags = match[2];
      if (0 > body.indexOf('#{')) {
        re = body.replace(HEREGEX_OMIT, '').replace(/\//g, '\\/');
        this.token('REGEX', "/" + (re || '(?:)') + "/" + flags);
        return heregex.length;
      }
      this.token('IDENTIFIER', 'RegExp');
      this.tokens.push(['CALL_START', '(']);
      tokens = [];
      _ref2 = this.interpolateString(body, {
        regex: true
      });
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        _ref3 = _ref2[_i], tag = _ref3[0], value = _ref3[1];
        if (tag === 'TOKENS') {
          tokens.push.apply(tokens, value);
        } else {
          if (!(value = value.replace(HEREGEX_OMIT, ''))) {
            continue;
          }
          value = value.replace(/\\/g, '\\\\');
          tokens.push(['STRING', this.makeString(value, '"', true)]);
        }
        tokens.push(['+', '+']);
      }
      tokens.pop();
      if (((_ref4 = tokens[0]) != null ? _ref4[0] : void 0) !== 'STRING') {
        this.tokens.push(['STRING', '""'], ['+', '+']);
      }
      (_ref5 = this.tokens).push.apply(_ref5, tokens);
      if (flags) {
        this.tokens.push([',', ','], ['STRING', '"' + flags + '"']);
      }
      this.token(')', ')');
      return heregex.length;
    };
    Lexer.prototype.lineToken = function() {
      var diff, indent, match, noNewlines, prev, size;
      if (!(match = MULTI_DENT.exec(this.chunk))) {
        return 0;
      }
      indent = match[0];
      this.line += count(indent, '\n');
      prev = last(this.tokens, 1);
      size = indent.length - 1 - indent.lastIndexOf('\n');
      noNewlines = this.unfinished();
      if (size - this.indebt === this.indent) {
        if (noNewlines) {
          this.suppressNewlines();
        } else {
          this.newlineToken();
        }
        return indent.length;
      }
      if (size > this.indent) {
        if (noNewlines) {
          this.indebt = size - this.indent;
          this.suppressNewlines();
          return indent.length;
        }
        diff = size - this.indent + this.outdebt;
        this.token('INDENT', diff);
        this.indents.push(diff);
        this.outdebt = this.indebt = 0;
      } else {
        this.indebt = 0;
        this.outdentToken(this.indent - size, noNewlines);
      }
      this.indent = size;
      return indent.length;
    };
    Lexer.prototype.outdentToken = function(moveOut, noNewlines, close) {
      var dent, len;
      while (moveOut > 0) {
        len = this.indents.length - 1;
        if (this.indents[len] === void 0) {
          moveOut = 0;
        } else if (this.indents[len] === this.outdebt) {
          moveOut -= this.outdebt;
          this.outdebt = 0;
        } else if (this.indents[len] < this.outdebt) {
          this.outdebt -= this.indents[len];
          moveOut -= this.indents[len];
        } else {
          dent = this.indents.pop() - this.outdebt;
          moveOut -= dent;
          this.outdebt = 0;
          this.token('OUTDENT', dent);
        }
      }
      if (dent) {
        this.outdebt -= moveOut;
      }
      if (!(this.tag() === 'TERMINATOR' || noNewlines)) {
        this.token('TERMINATOR', '\n');
      }
      return this;
    };
    Lexer.prototype.whitespaceToken = function() {
      var match, nline, prev;
      if (!((match = WHITESPACE.exec(this.chunk)) || (nline = this.chunk.charAt(0) === '\n'))) {
        return 0;
      }
      prev = last(this.tokens);
      if (prev) {
        prev[match ? 'spaced' : 'newLine'] = true;
      }
      if (match) {
        return match[0].length;
      } else {
        return 0;
      }
    };
    Lexer.prototype.newlineToken = function() {
      if (this.tag() !== 'TERMINATOR') {
        this.token('TERMINATOR', '\n');
      }
      return this;
    };
    Lexer.prototype.suppressNewlines = function() {
      if (this.value() === '\\') {
        this.tokens.pop();
      }
      return this;
    };
    Lexer.prototype.literalToken = function() {
      var match, prev, tag, value, _ref2, _ref3, _ref4, _ref5;
      if (match = OPERATOR.exec(this.chunk)) {
        value = match[0];
        if (CODE.test(value)) {
          this.tagParameters();
        }
      } else {
        value = this.chunk.charAt(0);
      }
      tag = value;
      prev = last(this.tokens);
      if (value === '=' && prev) {
        if (!prev[1].reserved && (_ref2 = prev[1], __indexOf.call(JS_FORBIDDEN, _ref2) >= 0)) {
          this.assignmentError();
        }
        if ((_ref3 = prev[1]) === '||' || _ref3 === '&&') {
          prev[0] = 'COMPOUND_ASSIGN';
          prev[1] += '=';
          return value.length;
        }
      }
      if (value === ';') {
        tag = 'TERMINATOR';
      } else if (__indexOf.call(MATH, value) >= 0) {
        tag = 'MATH';
      } else if (__indexOf.call(COMPARE, value) >= 0) {
        tag = 'COMPARE';
      } else if (__indexOf.call(COMPOUND_ASSIGN, value) >= 0) {
        tag = 'COMPOUND_ASSIGN';
      } else if (__indexOf.call(UNARY, value) >= 0) {
        tag = 'UNARY';
      } else if (__indexOf.call(SHIFT, value) >= 0) {
        tag = 'SHIFT';
      } else if (__indexOf.call(LOGIC, value) >= 0 || value === '?' && (prev != null ? prev.spaced : void 0)) {
        tag = 'LOGIC';
      } else if (prev && !prev.spaced) {
        if (value === '(' && (_ref4 = prev[0], __indexOf.call(CALLABLE, _ref4) >= 0)) {
          if (prev[0] === '?') {
            prev[0] = 'FUNC_EXIST';
          }
          tag = 'CALL_START';
        } else if (value === '[' && (_ref5 = prev[0], __indexOf.call(INDEXABLE, _ref5) >= 0)) {
          tag = 'INDEX_START';
          switch (prev[0]) {
            case '?':
              prev[0] = 'INDEX_SOAK';
              break;
            case '::':
              prev[0] = 'INDEX_PROTO';
          }
        }
      }
      this.token(tag, value);
      return value.length;
    };
    Lexer.prototype.sanitizeHeredoc = function(doc, options) {
      var attempt, herecomment, indent, match, _ref2;
      indent = options.indent, herecomment = options.herecomment;
      if (herecomment) {
        if (HEREDOC_ILLEGAL.test(doc)) {
          throw new Error("block comment cannot contain \"*/\", starting on line " + (this.line + 1));
        }
        if (doc.indexOf('\n') <= 0) {
          return doc;
        }
      } else {
        while (match = HEREDOC_INDENT.exec(doc)) {
          attempt = match[1];
          if (indent === null || (0 < (_ref2 = attempt.length) && _ref2 < indent.length)) {
            indent = attempt;
          }
        }
      }
      if (indent) {
        doc = doc.replace(RegExp("\\n" + indent, "g"), '\n');
      }
      if (!herecomment) {
        doc = doc.replace(/^\n/, '');
      }
      return doc;
    };
    Lexer.prototype.tagParameters = function() {
      var i, stack, tok, tokens;
      if (this.tag() !== ')') {
        return this;
      }
      stack = [];
      tokens = this.tokens;
      i = tokens.length;
      tokens[--i][0] = 'PARAM_END';
      while (tok = tokens[--i]) {
        switch (tok[0]) {
          case ')':
            stack.push(tok);
            break;
          case '(':
          case 'CALL_START':
            if (stack.length) {
              stack.pop();
            } else if (tok[0] === '(') {
              tok[0] = 'PARAM_START';
              return this;
            }
        }
      }
      return this;
    };
    Lexer.prototype.closeIndentation = function() {
      return this.outdentToken(this.indent);
    };
    Lexer.prototype.identifierError = function(word) {
      throw SyntaxError("Reserved word \"" + word + "\" on line " + (this.line + 1));
    };
    Lexer.prototype.assignmentError = function() {
      throw SyntaxError("Reserved word \"" + (this.value()) + "\" on line " + (this.line + 1) + " can't be assigned");
    };
    Lexer.prototype.balancedString = function(str, end) {
      var i, letter, prev, stack, _ref2;
      stack = [end];
      for (i = 1, _ref2 = str.length; 1 <= _ref2 ? i < _ref2 : i > _ref2; 1 <= _ref2 ? i++ : i--) {
        switch (letter = str.charAt(i)) {
          case '\\':
            i++;
            continue;
          case end:
            stack.pop();
            if (!stack.length) {
              return str.slice(0, i + 1);
            }
            end = stack[stack.length - 1];
            continue;
        }
        if (end === '}' && (letter === '"' || letter === "'")) {
          stack.push(end = letter);
        } else if (end === '}' && letter === '{') {
          stack.push(end = '}');
        } else if (end === '"' && prev === '#' && letter === '{') {
          stack.push(end = '}');
        }
        prev = letter;
      }
      throw new Error("missing " + (stack.pop()) + ", starting on line " + (this.line + 1));
    };
    Lexer.prototype.interpolateString = function(str, options) {
      var expr, heredoc, i, inner, interpolated, len, letter, nested, pi, regex, tag, tokens, value, _len, _ref2, _ref3, _ref4;
      if (options == null) {
        options = {};
      }
      heredoc = options.heredoc, regex = options.regex;
      tokens = [];
      pi = 0;
      i = -1;
      while (letter = str.charAt(i += 1)) {
        if (letter === '\\') {
          i += 1;
          continue;
        }
        if (!(letter === '#' && str.charAt(i + 1) === '{' && (expr = this.balancedString(str.slice(i + 1), '}')))) {
          continue;
        }
        if (pi < i) {
          tokens.push(['NEOSTRING', str.slice(pi, i)]);
        }
        inner = expr.slice(1, -1);
        if (inner.length) {
          nested = new Lexer().tokenize(inner, {
            line: this.line,
            rewrite: false
          });
          nested.pop();
          if (((_ref2 = nested[0]) != null ? _ref2[0] : void 0) === 'TERMINATOR') {
            nested.shift();
          }
          if (len = nested.length) {
            if (len > 1) {
              nested.unshift(['(', '(']);
              nested.push([')', ')']);
            }
            tokens.push(['TOKENS', nested]);
          }
        }
        i += expr.length;
        pi = i + 1;
      }
      if ((i > pi && pi < str.length)) {
        tokens.push(['NEOSTRING', str.slice(pi)]);
      }
      if (regex) {
        return tokens;
      }
      if (!tokens.length) {
        return this.token('STRING', '""');
      }
      if (tokens[0][0] !== 'NEOSTRING') {
        tokens.unshift(['', '']);
      }
      if (interpolated = tokens.length > 1) {
        this.token('(', '(');
      }
      for (i = 0, _len = tokens.length; i < _len; i++) {
        _ref3 = tokens[i], tag = _ref3[0], value = _ref3[1];
        if (i) {
          this.token('+', '+');
        }
        if (tag === 'TOKENS') {
          (_ref4 = this.tokens).push.apply(_ref4, value);
        } else {
          this.token('STRING', this.makeString(value, '"', heredoc));
        }
      }
      if (interpolated) {
        this.token(')', ')');
      }
      return tokens;
    };
    Lexer.prototype.token = function(tag, value) {
      return this.tokens.push([tag, value, this.line]);
    };
    Lexer.prototype.tag = function(index, tag) {
      var tok;
      return (tok = last(this.tokens, index)) && (tag ? tok[0] = tag : tok[0]);
    };
    Lexer.prototype.value = function(index, val) {
      var tok;
      return (tok = last(this.tokens, index)) && (val ? tok[1] = val : tok[1]);
    };
    Lexer.prototype.unfinished = function() {
      var prev, value;
      return LINE_CONTINUER.test(this.chunk) || (prev = last(this.tokens, 1)) && prev[0] !== '.' && (value = this.value()) && !value.reserved && NO_NEWLINE.test(value) && !CODE.test(value) && !ASSIGNED.test(this.chunk);
    };
    Lexer.prototype.escapeLines = function(str, heredoc) {
      return str.replace(MULTILINER, heredoc ? '\\n' : '');
    };
    Lexer.prototype.makeString = function(body, quote, heredoc) {
      if (!body) {
        return quote + quote;
      }
      body = body.replace(/\\([\s\S])/g, function(match, contents) {
        if (contents === '\n' || contents === quote) {
          return contents;
        } else {
          return match;
        }
      });
      body = body.replace(RegExp("" + quote, "g"), '\\$&');
      return quote + this.escapeLines(body, heredoc) + quote;
    };
    return Lexer;
  })();
  JS_KEYWORDS = ['true', 'false', 'null', 'this', 'new', 'delete', 'typeof', 'in', 'instanceof', 'return', 'throw', 'break', 'continue', 'debugger', 'if', 'else', 'switch', 'for', 'while', 'do', 'try', 'catch', 'finally', 'class', 'extends', 'super'];
  COFFEE_KEYWORDS = ['undefined', 'then', 'unless', 'until', 'loop', 'of', 'by', 'when'];
  COFFEE_ALIAS_MAP = {
    and: '&&',
    or: '||',
    is: '==',
    isnt: '!=',
    not: '!',
    yes: 'true',
    no: 'false',
    on: 'true',
    off: 'false'
  };
  COFFEE_ALIASES = (function() {
    var _results;
    _results = [];
    for (key in COFFEE_ALIAS_MAP) {
      _results.push(key);
    }
    return _results;
  })();
  COFFEE_KEYWORDS = COFFEE_KEYWORDS.concat(COFFEE_ALIASES);
  RESERVED = ['case', 'default', 'function', 'var', 'void', 'with', 'const', 'let', 'enum', 'export', 'import', 'native', '__hasProp', '__extends', '__slice', '__bind', '__indexOf'];
  JS_FORBIDDEN = JS_KEYWORDS.concat(RESERVED);
  exports.RESERVED = RESERVED.concat(JS_KEYWORDS).concat(COFFEE_KEYWORDS);
  IDENTIFIER = /^([$A-Za-z_\x7f-\uffff][$\w\x7f-\uffff]*)([^\n\S]*:(?!:))?/;
  NUMBER = /^0x[\da-f]+|^(?:\d+(\.\d+)?|\.\d+)(?:e[+-]?\d+)?/i;
  HEREDOC = /^("""|''')([\s\S]*?)(?:\n[^\n\S]*)?\1/;
  OPERATOR = /^(?:[-=]>|[-+*\/%<>&|^!?=]=|>>>=?|([-+:])\1|([&|<>])\2=?|\?\.|\.{2,3})/;
  WHITESPACE = /^[^\n\S]+/;
  COMMENT = /^###([^#][\s\S]*?)(?:###[^\n\S]*|(?:###)?$)|^(?:\s*#(?!##[^#]).*)+/;
  CODE = /^[-=]>/;
  MULTI_DENT = /^(?:\n[^\n\S]*)+/;
  SIMPLESTR = /^'[^\\']*(?:\\.[^\\']*)*'/;
  JSTOKEN = /^`[^\\`]*(?:\\.[^\\`]*)*`/;
  REGEX = /^\/(?![\s=])[^[\/\n\\]*(?:(?:\\[\s\S]|\[[^\]\n\\]*(?:\\[\s\S][^\]\n\\]*)*])[^[\/\n\\]*)*\/[imgy]{0,4}(?!\w)/;
  HEREGEX = /^\/{3}([\s\S]+?)\/{3}([imgy]{0,4})(?!\w)/;
  HEREGEX_OMIT = /\s+(?:#.*)?/g;
  MULTILINER = /\n/g;
  HEREDOC_INDENT = /\n+([^\n\S]*)/g;
  HEREDOC_ILLEGAL = /\*\//;
  ASSIGNED = /^\s*@?([$A-Za-z_][$\w\x7f-\uffff]*|['"].*['"])[^\n\S]*?[:=][^:=>]/;
  LINE_CONTINUER = /^\s*(?:,|\??\.(?![.\d])|::)/;
  TRAILING_SPACES = /\s+$/;
  NO_NEWLINE = /^(?:[-+*&|\/%=<>!.\\][<>=&|]*|and|or|is(?:nt)?|n(?:ot|ew)|delete|typeof|instanceof)$/;
  COMPOUND_ASSIGN = ['-=', '+=', '/=', '*=', '%=', '||=', '&&=', '?=', '<<=', '>>=', '>>>=', '&=', '^=', '|='];
  UNARY = ['!', '~', 'NEW', 'TYPEOF', 'DELETE', 'DO'];
  LOGIC = ['&&', '||', '&', '|', '^'];
  SHIFT = ['<<', '>>', '>>>'];
  COMPARE = ['==', '!=', '<', '>', '<=', '>='];
  MATH = ['*', '/', '%'];
  RELATION = ['IN', 'OF', 'INSTANCEOF'];
  BOOL = ['TRUE', 'FALSE', 'NULL', 'UNDEFINED'];
  NOT_REGEX = ['NUMBER', 'REGEX', 'BOOL', '++', '--', ']'];
  NOT_SPACED_REGEX = NOT_REGEX.concat(')', '}', 'THIS', 'IDENTIFIER', 'STRING');
  CALLABLE = ['IDENTIFIER', 'STRING', 'REGEX', ')', ']', '}', '?', '::', '@', 'THIS', 'SUPER'];
  INDEXABLE = CALLABLE.concat('NUMBER', 'BOOL');
  LINE_BREAK = ['INDENT', 'OUTDENT', 'TERMINATOR'];
});
/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/rewriter', ['require', 'exports', 'module' ], function(require, exports, module) {
    
  var BALANCED_PAIRS, EXPRESSION_CLOSE, EXPRESSION_END, EXPRESSION_START, IMPLICIT_BLOCK, IMPLICIT_CALL, IMPLICIT_END, IMPLICIT_FUNC, IMPLICIT_UNSPACED_CALL, INVERSES, LINEBREAKS, SINGLE_CLOSERS, SINGLE_LINERS, left, rite, _i, _len, _ref;
  var __indexOf = Array.prototype.indexOf || function(item) {
    for (var i = 0, l = this.length; i < l; i++) {
      if (this[i] === item) return i;
    }
    return -1;
  }, __slice = Array.prototype.slice;
  exports.Rewriter = (function() {
    function Rewriter() {}
    Rewriter.prototype.rewrite = function(tokens) {
      this.tokens = tokens;
      this.removeLeadingNewlines();
      this.removeMidExpressionNewlines();
      this.closeOpenCalls();
      this.closeOpenIndexes();
      this.addImplicitIndentation();
      this.tagPostfixConditionals();
      this.addImplicitBraces();
      this.addImplicitParentheses();
      this.ensureBalance(BALANCED_PAIRS);
      this.rewriteClosingParens();
      return this.tokens;
    };
    Rewriter.prototype.scanTokens = function(block) {
      var i, token, tokens;
      tokens = this.tokens;
      i = 0;
      while (token = tokens[i]) {
        i += block.call(this, token, i, tokens);
      }
      return true;
    };
    Rewriter.prototype.detectEnd = function(i, condition, action) {
      var levels, token, tokens, _ref, _ref2;
      tokens = this.tokens;
      levels = 0;
      while (token = tokens[i]) {
        if (levels === 0 && condition.call(this, token, i)) {
          return action.call(this, token, i);
        }
        if (!token || levels < 0) {
          return action.call(this, token, i - 1);
        }
        if (_ref = token[0], __indexOf.call(EXPRESSION_START, _ref) >= 0) {
          levels += 1;
        } else if (_ref2 = token[0], __indexOf.call(EXPRESSION_END, _ref2) >= 0) {
          levels -= 1;
        }
        i += 1;
      }
      return i - 1;
    };
    Rewriter.prototype.removeLeadingNewlines = function() {
      var i, tag, _len, _ref;
      _ref = this.tokens;
      for (i = 0, _len = _ref.length; i < _len; i++) {
        tag = _ref[i][0];
        if (tag !== 'TERMINATOR') {
          break;
        }
      }
      if (i) {
        return this.tokens.splice(0, i);
      }
    };
    Rewriter.prototype.removeMidExpressionNewlines = function() {
      return this.scanTokens(function(token, i, tokens) {
        var _ref;
        if (!(token[0] === 'TERMINATOR' && (_ref = this.tag(i + 1), __indexOf.call(EXPRESSION_CLOSE, _ref) >= 0))) {
          return 1;
        }
        tokens.splice(i, 1);
        return 0;
      });
    };
    Rewriter.prototype.closeOpenCalls = function() {
      var action, condition;
      condition = function(token, i) {
        var _ref;
        return ((_ref = token[0]) === ')' || _ref === 'CALL_END') || token[0] === 'OUTDENT' && this.tag(i - 1) === ')';
      };
      action = function(token, i) {
        return this.tokens[token[0] === 'OUTDENT' ? i - 1 : i][0] = 'CALL_END';
      };
      return this.scanTokens(function(token, i) {
        if (token[0] === 'CALL_START') {
          this.detectEnd(i + 1, condition, action);
        }
        return 1;
      });
    };
    Rewriter.prototype.closeOpenIndexes = function() {
      var action, condition;
      condition = function(token, i) {
        var _ref;
        return (_ref = token[0]) === ']' || _ref === 'INDEX_END';
      };
      action = function(token, i) {
        return token[0] = 'INDEX_END';
      };
      return this.scanTokens(function(token, i) {
        if (token[0] === 'INDEX_START') {
          this.detectEnd(i + 1, condition, action);
        }
        return 1;
      });
    };
    Rewriter.prototype.addImplicitBraces = function() {
      var action, condition, stack, start, startIndent;
      stack = [];
      start = null;
      startIndent = 0;
      condition = function(token, i) {
        var one, tag, three, two, _ref, _ref2;
        _ref = this.tokens.slice(i + 1, (i + 3 + 1) || 9e9), one = _ref[0], two = _ref[1], three = _ref[2];
        if ('HERECOMMENT' === (one != null ? one[0] : void 0)) {
          return false;
        }
        tag = token[0];
        return ((tag === 'TERMINATOR' || tag === 'OUTDENT') && !((two != null ? two[0] : void 0) === ':' || (one != null ? one[0] : void 0) === '@' && (three != null ? three[0] : void 0) === ':')) || (tag === ',' && one && ((_ref2 = one[0]) !== 'IDENTIFIER' && _ref2 !== 'NUMBER' && _ref2 !== 'STRING' && _ref2 !== '@' && _ref2 !== 'TERMINATOR' && _ref2 !== 'OUTDENT'));
      };
      action = function(token, i) {
        var tok;
        tok = ['}', '}', token[2]];
        tok.generated = true;
        return this.tokens.splice(i, 0, tok);
      };
      return this.scanTokens(function(token, i, tokens) {
        var ago, idx, tag, tok, value, _ref, _ref2;
        if (_ref = (tag = token[0]), __indexOf.call(EXPRESSION_START, _ref) >= 0) {
          stack.push([(tag === 'INDENT' && this.tag(i - 1) === '{' ? '{' : tag), i]);
          return 1;
        }
        if (__indexOf.call(EXPRESSION_END, tag) >= 0) {
          start = stack.pop();
          return 1;
        }
        if (!(tag === ':' && ((ago = this.tag(i - 2)) === ':' || ((_ref2 = stack[stack.length - 1]) != null ? _ref2[0] : void 0) !== '{'))) {
          return 1;
        }
        stack.push(['{']);
        idx = ago === '@' ? i - 2 : i - 1;
        while (this.tag(idx - 2) === 'HERECOMMENT') {
          idx -= 2;
        }
        value = new String('{');
        value.generated = true;
        tok = ['{', value, token[2]];
        tok.generated = true;
        tokens.splice(idx, 0, tok);
        this.detectEnd(i + 2, condition, action);
        return 2;
      });
    };
    Rewriter.prototype.addImplicitParentheses = function() {
      var action, noCall;
      noCall = false;
      action = function(token, i) {
        var idx;
        idx = token[0] === 'OUTDENT' ? i + 1 : i;
        return this.tokens.splice(idx, 0, ['CALL_END', ')', token[2]]);
      };
      return this.scanTokens(function(token, i, tokens) {
        var callObject, current, next, prev, seenControl, seenSingle, tag, _ref, _ref2, _ref3;
        tag = token[0];
        if (tag === 'CLASS' || tag === 'IF') {
          noCall = true;
        }
        _ref = tokens.slice(i - 1, (i + 1 + 1) || 9e9), prev = _ref[0], current = _ref[1], next = _ref[2];
        callObject = !noCall && tag === 'INDENT' && next && next.generated && next[0] === '{' && prev && (_ref2 = prev[0], __indexOf.call(IMPLICIT_FUNC, _ref2) >= 0);
        seenSingle = false;
        seenControl = false;
        if (__indexOf.call(LINEBREAKS, tag) >= 0) {
          noCall = false;
        }
        if (prev && !prev.spaced && tag === '?') {
          token.call = true;
        }
        if (token.fromThen) {
          return 1;
        }
        if (!(callObject || (prev != null ? prev.spaced : void 0) && (prev.call || (_ref3 = prev[0], __indexOf.call(IMPLICIT_FUNC, _ref3) >= 0)) && (__indexOf.call(IMPLICIT_CALL, tag) >= 0 || !(token.spaced || token.newLine) && __indexOf.call(IMPLICIT_UNSPACED_CALL, tag) >= 0))) {
          return 1;
        }
        tokens.splice(i, 0, ['CALL_START', '(', token[2]]);
        this.detectEnd(i + 1, function(token, i) {
          var post, _ref4;
          tag = token[0];
          if (!seenSingle && token.fromThen) {
            return true;
          }
          if (tag === 'IF' || tag === 'ELSE' || tag === 'CATCH' || tag === '->' || tag === '=>') {
            seenSingle = true;
          }
          if (tag === 'IF' || tag === 'ELSE' || tag === 'SWITCH' || tag === 'TRY') {
            seenControl = true;
          }
          if ((tag === '.' || tag === '?.' || tag === '::') && this.tag(i - 1) === 'OUTDENT') {
            return true;
          }
          return !token.generated && this.tag(i - 1) !== ',' && (__indexOf.call(IMPLICIT_END, tag) >= 0 || (tag === 'INDENT' && !seenControl)) && (tag !== 'INDENT' || (this.tag(i - 2) !== 'CLASS' && (_ref4 = this.tag(i - 1), __indexOf.call(IMPLICIT_BLOCK, _ref4) < 0) && !((post = this.tokens[i + 1]) && post.generated && post[0] === '{')));
        }, action);
        if (prev[0] === '?') {
          prev[0] = 'FUNC_EXIST';
        }
        return 2;
      });
    };
    Rewriter.prototype.addImplicitIndentation = function() {
      return this.scanTokens(function(token, i, tokens) {
        var action, condition, indent, outdent, starter, tag, _ref, _ref2;
        tag = token[0];
        if (tag === 'TERMINATOR' && this.tag(i + 1) === 'THEN') {
          tokens.splice(i, 1);
          return 0;
        }
        if (tag === 'ELSE' && this.tag(i - 1) !== 'OUTDENT') {
          tokens.splice.apply(tokens, [i, 0].concat(__slice.call(this.indentation(token))));
          return 2;
        }
        if (tag === 'CATCH' && ((_ref = this.tag(i + 2)) === 'OUTDENT' || _ref === 'TERMINATOR' || _ref === 'FINALLY')) {
          tokens.splice.apply(tokens, [i + 2, 0].concat(__slice.call(this.indentation(token))));
          return 4;
        }
        if (__indexOf.call(SINGLE_LINERS, tag) >= 0 && this.tag(i + 1) !== 'INDENT' && !(tag === 'ELSE' && this.tag(i + 1) === 'IF')) {
          starter = tag;
          _ref2 = this.indentation(token), indent = _ref2[0], outdent = _ref2[1];
          if (starter === 'THEN') {
            indent.fromThen = true;
          }
          indent.generated = outdent.generated = true;
          tokens.splice(i + 1, 0, indent);
          condition = function(token, i) {
            var _ref3;
            return token[1] !== ';' && (_ref3 = token[0], __indexOf.call(SINGLE_CLOSERS, _ref3) >= 0) && !(token[0] === 'ELSE' && (starter !== 'IF' && starter !== 'THEN'));
          };
          action = function(token, i) {
            return this.tokens.splice((this.tag(i - 1) === ',' ? i - 1 : i), 0, outdent);
          };
          this.detectEnd(i + 2, condition, action);
          if (tag === 'THEN') {
            tokens.splice(i, 1);
          }
          return 1;
        }
        return 1;
      });
    };
    Rewriter.prototype.tagPostfixConditionals = function() {
      var condition;
      condition = function(token, i) {
        var _ref;
        return (_ref = token[0]) === 'TERMINATOR' || _ref === 'INDENT';
      };
      return this.scanTokens(function(token, i) {
        var original;
        if (token[0] !== 'IF') {
          return 1;
        }
        original = token;
        this.detectEnd(i + 1, condition, function(token, i) {
          if (token[0] !== 'INDENT') {
            return original[0] = 'POST_' + original[0];
          }
        });
        return 1;
      });
    };
    Rewriter.prototype.ensureBalance = function(pairs) {
      var close, level, levels, open, openLine, tag, token, _i, _j, _len, _len2, _ref, _ref2;
      levels = {};
      openLine = {};
      _ref = this.tokens;
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        token = _ref[_i];
        tag = token[0];
        for (_j = 0, _len2 = pairs.length; _j < _len2; _j++) {
          _ref2 = pairs[_j], open = _ref2[0], close = _ref2[1];
          levels[open] |= 0;
          if (tag === open) {
            if (levels[open]++ === 0) {
              openLine[open] = token[2];
            }
          } else if (tag === close && --levels[open] < 0) {
            throw Error("too many " + token[1] + " on line " + (token[2] + 1));
          }
        }
      }
      for (open in levels) {
        level = levels[open];
        if (level > 0) {
          throw Error("unclosed " + open + " on line " + (openLine[open] + 1));
        }
      }
      return this;
    };
    Rewriter.prototype.rewriteClosingParens = function() {
      var debt, key, stack;
      stack = [];
      debt = {};
      for (key in INVERSES) {
        debt[key] = 0;
      }
      return this.scanTokens(function(token, i, tokens) {
        var inv, match, mtag, oppos, tag, val, _ref;
        if (_ref = (tag = token[0]), __indexOf.call(EXPRESSION_START, _ref) >= 0) {
          stack.push(token);
          return 1;
        }
        if (__indexOf.call(EXPRESSION_END, tag) < 0) {
          return 1;
        }
        if (debt[inv = INVERSES[tag]] > 0) {
          debt[inv] -= 1;
          tokens.splice(i, 1);
          return 0;
        }
        match = stack.pop();
        mtag = match[0];
        oppos = INVERSES[mtag];
        if (tag === oppos) {
          return 1;
        }
        debt[mtag] += 1;
        val = [oppos, mtag === 'INDENT' ? match[1] : oppos];
        if (this.tag(i + 2) === mtag) {
          tokens.splice(i + 3, 0, val);
          stack.push(match);
        } else {
          tokens.splice(i, 0, val);
        }
        return 1;
      });
    };
    Rewriter.prototype.indentation = function(token) {
      return [['INDENT', 2, token[2]], ['OUTDENT', 2, token[2]]];
    };
    Rewriter.prototype.tag = function(i) {
      var _ref;
      return (_ref = this.tokens[i]) != null ? _ref[0] : void 0;
    };
    return Rewriter;
  })();
  BALANCED_PAIRS = [['(', ')'], ['[', ']'], ['{', '}'], ['INDENT', 'OUTDENT'], ['CALL_START', 'CALL_END'], ['PARAM_START', 'PARAM_END'], ['INDEX_START', 'INDEX_END']];
  INVERSES = {};
  EXPRESSION_START = [];
  EXPRESSION_END = [];
  for (_i = 0, _len = BALANCED_PAIRS.length; _i < _len; _i++) {
    _ref = BALANCED_PAIRS[_i], left = _ref[0], rite = _ref[1];
    EXPRESSION_START.push(INVERSES[rite] = left);
    EXPRESSION_END.push(INVERSES[left] = rite);
  }
  EXPRESSION_CLOSE = ['CATCH', 'WHEN', 'ELSE', 'FINALLY'].concat(EXPRESSION_END);
  IMPLICIT_FUNC = ['IDENTIFIER', 'SUPER', ')', 'CALL_END', ']', 'INDEX_END', '@', 'THIS'];
  IMPLICIT_CALL = ['IDENTIFIER', 'NUMBER', 'STRING', 'JS', 'REGEX', 'NEW', 'PARAM_START', 'CLASS', 'IF', 'TRY', 'SWITCH', 'THIS', 'BOOL', 'UNARY', 'SUPER', '@', '->', '=>', '[', '(', '{', '--', '++'];
  IMPLICIT_UNSPACED_CALL = ['+', '-'];
  IMPLICIT_BLOCK = ['->', '=>', '{', '[', ','];
  IMPLICIT_END = ['POST_IF', 'FOR', 'WHILE', 'UNTIL', 'WHEN', 'BY', 'LOOP', 'TERMINATOR'];
  SINGLE_LINERS = ['ELSE', '->', '=>', 'TRY', 'FINALLY', 'THEN'];
  SINGLE_CLOSERS = ['TERMINATOR', 'CATCH', 'FINALLY', 'ELSE', 'OUTDENT', 'LEADING_WHEN'];
  LINEBREAKS = ['TERMINATOR', 'INDENT', 'OUTDENT'];
});
/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/helpers', ['require', 'exports', 'module' ], function(require, exports, module) {
    
  var extend, flatten;
  exports.starts = function(string, literal, start) {
    return literal === string.substr(start, literal.length);
  };
  exports.ends = function(string, literal, back) {
    var len;
    len = literal.length;
    return literal === string.substr(string.length - len - (back || 0), len);
  };
  exports.compact = function(array) {
    var item, _i, _len, _results;
    _results = [];
    for (_i = 0, _len = array.length; _i < _len; _i++) {
      item = array[_i];
      if (item) {
        _results.push(item);
      }
    }
    return _results;
  };
  exports.count = function(string, substr) {
    var num, pos;
    num = pos = 0;
    if (!substr.length) {
      return 1 / 0;
    }
    while (pos = 1 + string.indexOf(substr, pos)) {
      num++;
    }
    return num;
  };
  exports.merge = function(options, overrides) {
    return extend(extend({}, options), overrides);
  };
  extend = exports.extend = function(object, properties) {
    var key, val;
    for (key in properties) {
      val = properties[key];
      object[key] = val;
    }
    return object;
  };
  exports.flatten = flatten = function(array) {
    var element, flattened, _i, _len;
    flattened = [];
    for (_i = 0, _len = array.length; _i < _len; _i++) {
      element = array[_i];
      if (element instanceof Array) {
        flattened = flattened.concat(flatten(element));
      } else {
        flattened.push(element);
      }
    }
    return flattened;
  };
  exports.del = function(obj, key) {
    var val;
    val = obj[key];
    delete obj[key];
    return val;
  };
  exports.last = function(array, back) {
    return array[array.length - (back || 0) - 1];
  };
});
/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/parser', ['require', 'exports', 'module' ], function(require, exports, module) {

/* Jison generated parser */
var parser = {trace: function trace() { },
yy: {},
symbols_: {"error":2,"Root":3,"Body":4,"Block":5,"TERMINATOR":6,"Line":7,"Expression":8,"Statement":9,"Return":10,"Throw":11,"Comment":12,"STATEMENT":13,"Value":14,"Invocation":15,"Code":16,"Operation":17,"Assign":18,"If":19,"Try":20,"While":21,"For":22,"Switch":23,"Class":24,"INDENT":25,"OUTDENT":26,"Identifier":27,"IDENTIFIER":28,"AlphaNumeric":29,"NUMBER":30,"STRING":31,"Literal":32,"JS":33,"REGEX":34,"BOOL":35,"Assignable":36,"=":37,"AssignObj":38,"ObjAssignable":39,":":40,"ThisProperty":41,"RETURN":42,"HERECOMMENT":43,"PARAM_START":44,"ParamList":45,"PARAM_END":46,"FuncGlyph":47,"->":48,"=>":49,"OptComma":50,",":51,"Param":52,"ParamVar":53,"...":54,"Array":55,"Object":56,"Splat":57,"SimpleAssignable":58,"Accessor":59,"Parenthetical":60,"Range":61,"This":62,".":63,"?.":64,"::":65,"Index":66,"INDEX_START":67,"IndexValue":68,"INDEX_END":69,"INDEX_SOAK":70,"INDEX_PROTO":71,"Slice":72,"{":73,"AssignList":74,"}":75,"CLASS":76,"EXTENDS":77,"OptFuncExist":78,"Arguments":79,"SUPER":80,"FUNC_EXIST":81,"CALL_START":82,"CALL_END":83,"ArgList":84,"THIS":85,"@":86,"[":87,"]":88,"RangeDots":89,"..":90,"Arg":91,"SimpleArgs":92,"TRY":93,"Catch":94,"FINALLY":95,"CATCH":96,"THROW":97,"(":98,")":99,"WhileSource":100,"WHILE":101,"WHEN":102,"UNTIL":103,"Loop":104,"LOOP":105,"ForBody":106,"FOR":107,"ForStart":108,"ForSource":109,"ForVariables":110,"OWN":111,"ForValue":112,"FORIN":113,"FOROF":114,"BY":115,"SWITCH":116,"Whens":117,"ELSE":118,"When":119,"LEADING_WHEN":120,"IfBlock":121,"IF":122,"POST_IF":123,"UNARY":124,"-":125,"+":126,"--":127,"++":128,"?":129,"MATH":130,"SHIFT":131,"COMPARE":132,"LOGIC":133,"RELATION":134,"COMPOUND_ASSIGN":135,"$accept":0,"$end":1},
terminals_: {2:"error",6:"TERMINATOR",13:"STATEMENT",25:"INDENT",26:"OUTDENT",28:"IDENTIFIER",30:"NUMBER",31:"STRING",33:"JS",34:"REGEX",35:"BOOL",37:"=",40:":",42:"RETURN",43:"HERECOMMENT",44:"PARAM_START",46:"PARAM_END",48:"->",49:"=>",51:",",54:"...",63:".",64:"?.",65:"::",67:"INDEX_START",69:"INDEX_END",70:"INDEX_SOAK",71:"INDEX_PROTO",73:"{",75:"}",76:"CLASS",77:"EXTENDS",80:"SUPER",81:"FUNC_EXIST",82:"CALL_START",83:"CALL_END",85:"THIS",86:"@",87:"[",88:"]",90:"..",93:"TRY",95:"FINALLY",96:"CATCH",97:"THROW",98:"(",99:")",101:"WHILE",102:"WHEN",103:"UNTIL",105:"LOOP",107:"FOR",111:"OWN",113:"FORIN",114:"FOROF",115:"BY",116:"SWITCH",118:"ELSE",120:"LEADING_WHEN",122:"IF",123:"POST_IF",124:"UNARY",125:"-",126:"+",127:"--",128:"++",129:"?",130:"MATH",131:"SHIFT",132:"COMPARE",133:"LOGIC",134:"RELATION",135:"COMPOUND_ASSIGN"},
productions_: [0,[3,0],[3,1],[3,2],[4,1],[4,3],[4,2],[7,1],[7,1],[9,1],[9,1],[9,1],[9,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[5,2],[5,3],[27,1],[29,1],[29,1],[32,1],[32,1],[32,1],[32,1],[18,3],[18,5],[38,1],[38,3],[38,5],[38,1],[39,1],[39,1],[39,1],[10,2],[10,1],[12,1],[16,5],[16,2],[47,1],[47,1],[50,0],[50,1],[45,0],[45,1],[45,3],[52,1],[52,2],[52,3],[53,1],[53,1],[53,1],[53,1],[57,2],[58,1],[58,2],[58,2],[58,1],[36,1],[36,1],[36,1],[14,1],[14,1],[14,1],[14,1],[14,1],[59,2],[59,2],[59,2],[59,1],[59,1],[66,3],[66,2],[66,2],[68,1],[68,1],[56,4],[74,0],[74,1],[74,3],[74,4],[74,6],[24,1],[24,2],[24,3],[24,4],[24,2],[24,3],[24,4],[24,5],[15,3],[15,3],[15,1],[15,2],[78,0],[78,1],[79,2],[79,4],[62,1],[62,1],[41,2],[55,2],[55,4],[89,1],[89,1],[61,5],[72,3],[72,2],[72,2],[84,1],[84,3],[84,4],[84,4],[84,6],[91,1],[91,1],[92,1],[92,3],[20,2],[20,3],[20,4],[20,5],[94,3],[11,2],[60,3],[60,5],[100,2],[100,4],[100,2],[100,4],[21,2],[21,2],[21,2],[21,1],[104,2],[104,2],[22,2],[22,2],[22,2],[106,2],[106,2],[108,2],[108,3],[112,1],[112,1],[112,1],[110,1],[110,3],[109,2],[109,2],[109,4],[109,4],[109,4],[109,6],[109,6],[23,5],[23,7],[23,4],[23,6],[117,1],[117,2],[119,3],[119,4],[121,3],[121,5],[19,1],[19,3],[19,3],[19,3],[17,2],[17,2],[17,2],[17,2],[17,2],[17,2],[17,2],[17,2],[17,3],[17,3],[17,3],[17,3],[17,3],[17,3],[17,3],[17,3],[17,5],[17,3]],
performAction: function anonymous(yytext,yyleng,yylineno,yy,yystate,$$,_$) {

var $0 = $$.length - 1;
switch (yystate) {
case 1:return this.$ = new yy.Block;
break;
case 2:return this.$ = $$[$0];
break;
case 3:return this.$ = $$[$0-1];
break;
case 4:this.$ = yy.Block.wrap([$$[$0]]);
break;
case 5:this.$ = $$[$0-2].push($$[$0]);
break;
case 6:this.$ = $$[$0-1];
break;
case 7:this.$ = $$[$0];
break;
case 8:this.$ = $$[$0];
break;
case 9:this.$ = $$[$0];
break;
case 10:this.$ = $$[$0];
break;
case 11:this.$ = $$[$0];
break;
case 12:this.$ = new yy.Literal($$[$0]);
break;
case 13:this.$ = $$[$0];
break;
case 14:this.$ = $$[$0];
break;
case 15:this.$ = $$[$0];
break;
case 16:this.$ = $$[$0];
break;
case 17:this.$ = $$[$0];
break;
case 18:this.$ = $$[$0];
break;
case 19:this.$ = $$[$0];
break;
case 20:this.$ = $$[$0];
break;
case 21:this.$ = $$[$0];
break;
case 22:this.$ = $$[$0];
break;
case 23:this.$ = $$[$0];
break;
case 24:this.$ = new yy.Block;
break;
case 25:this.$ = $$[$0-1];
break;
case 26:this.$ = new yy.Literal($$[$0]);
break;
case 27:this.$ = new yy.Literal($$[$0]);
break;
case 28:this.$ = new yy.Literal($$[$0]);
break;
case 29:this.$ = $$[$0];
break;
case 30:this.$ = new yy.Literal($$[$0]);
break;
case 31:this.$ = new yy.Literal($$[$0]);
break;
case 32:this.$ = (function () {
        var val;
        val = new yy.Literal($$[$0]);
        if ($$[$0] === 'undefined') {
          val.isUndefined = true;
        }
        return val;
      }());
break;
case 33:this.$ = new yy.Assign($$[$0-2], $$[$0]);
break;
case 34:this.$ = new yy.Assign($$[$0-4], $$[$0-1]);
break;
case 35:this.$ = new yy.Value($$[$0]);
break;
case 36:this.$ = new yy.Assign(new yy.Value($$[$0-2]), $$[$0], 'object');
break;
case 37:this.$ = new yy.Assign(new yy.Value($$[$0-4]), $$[$0-1], 'object');
break;
case 38:this.$ = $$[$0];
break;
case 39:this.$ = $$[$0];
break;
case 40:this.$ = $$[$0];
break;
case 41:this.$ = $$[$0];
break;
case 42:this.$ = new yy.Return($$[$0]);
break;
case 43:this.$ = new yy.Return;
break;
case 44:this.$ = new yy.Comment($$[$0]);
break;
case 45:this.$ = new yy.Code($$[$0-3], $$[$0], $$[$0-1]);
break;
case 46:this.$ = new yy.Code([], $$[$0], $$[$0-1]);
break;
case 47:this.$ = 'func';
break;
case 48:this.$ = 'boundfunc';
break;
case 49:this.$ = $$[$0];
break;
case 50:this.$ = $$[$0];
break;
case 51:this.$ = [];
break;
case 52:this.$ = [$$[$0]];
break;
case 53:this.$ = $$[$0-2].concat($$[$0]);
break;
case 54:this.$ = new yy.Param($$[$0]);
break;
case 55:this.$ = new yy.Param($$[$0-1], null, true);
break;
case 56:this.$ = new yy.Param($$[$0-2], $$[$0]);
break;
case 57:this.$ = $$[$0];
break;
case 58:this.$ = $$[$0];
break;
case 59:this.$ = $$[$0];
break;
case 60:this.$ = $$[$0];
break;
case 61:this.$ = new yy.Splat($$[$0-1]);
break;
case 62:this.$ = new yy.Value($$[$0]);
break;
case 63:this.$ = $$[$0-1].push($$[$0]);
break;
case 64:this.$ = new yy.Value($$[$0-1], [$$[$0]]);
break;
case 65:this.$ = $$[$0];
break;
case 66:this.$ = $$[$0];
break;
case 67:this.$ = new yy.Value($$[$0]);
break;
case 68:this.$ = new yy.Value($$[$0]);
break;
case 69:this.$ = $$[$0];
break;
case 70:this.$ = new yy.Value($$[$0]);
break;
case 71:this.$ = new yy.Value($$[$0]);
break;
case 72:this.$ = new yy.Value($$[$0]);
break;
case 73:this.$ = $$[$0];
break;
case 74:this.$ = new yy.Access($$[$0]);
break;
case 75:this.$ = new yy.Access($$[$0], 'soak');
break;
case 76:this.$ = new yy.Access($$[$0], 'proto');
break;
case 77:this.$ = new yy.Access(new yy.Literal('prototype'));
break;
case 78:this.$ = $$[$0];
break;
case 79:this.$ = $$[$0-1];
break;
case 80:this.$ = yy.extend($$[$0], {
          soak: true
        });
break;
case 81:this.$ = yy.extend($$[$0], {
          proto: true
        });
break;
case 82:this.$ = new yy.Index($$[$0]);
break;
case 83:this.$ = new yy.Slice($$[$0]);
break;
case 84:this.$ = new yy.Obj($$[$0-2], $$[$0-3].generated);
break;
case 85:this.$ = [];
break;
case 86:this.$ = [$$[$0]];
break;
case 87:this.$ = $$[$0-2].concat($$[$0]);
break;
case 88:this.$ = $$[$0-3].concat($$[$0]);
break;
case 89:this.$ = $$[$0-5].concat($$[$0-2]);
break;
case 90:this.$ = new yy.Class;
break;
case 91:this.$ = new yy.Class(null, null, $$[$0]);
break;
case 92:this.$ = new yy.Class(null, $$[$0]);
break;
case 93:this.$ = new yy.Class(null, $$[$0-1], $$[$0]);
break;
case 94:this.$ = new yy.Class($$[$0]);
break;
case 95:this.$ = new yy.Class($$[$0-1], null, $$[$0]);
break;
case 96:this.$ = new yy.Class($$[$0-2], $$[$0]);
break;
case 97:this.$ = new yy.Class($$[$0-3], $$[$0-1], $$[$0]);
break;
case 98:this.$ = new yy.Call($$[$0-2], $$[$0], $$[$0-1]);
break;
case 99:this.$ = new yy.Call($$[$0-2], $$[$0], $$[$0-1]);
break;
case 100:this.$ = new yy.Call('super', [new yy.Splat(new yy.Literal('arguments'))]);
break;
case 101:this.$ = new yy.Call('super', $$[$0]);
break;
case 102:this.$ = false;
break;
case 103:this.$ = true;
break;
case 104:this.$ = [];
break;
case 105:this.$ = $$[$0-2];
break;
case 106:this.$ = new yy.Value(new yy.Literal('this'));
break;
case 107:this.$ = new yy.Value(new yy.Literal('this'));
break;
case 108:this.$ = new yy.Value(new yy.Literal('this'), [new yy.Access($$[$0])], 'this');
break;
case 109:this.$ = new yy.Arr([]);
break;
case 110:this.$ = new yy.Arr($$[$0-2]);
break;
case 111:this.$ = 'inclusive';
break;
case 112:this.$ = 'exclusive';
break;
case 113:this.$ = new yy.Range($$[$0-3], $$[$0-1], $$[$0-2]);
break;
case 114:this.$ = new yy.Range($$[$0-2], $$[$0], $$[$0-1]);
break;
case 115:this.$ = new yy.Range($$[$0-1], null, $$[$0]);
break;
case 116:this.$ = new yy.Range(null, $$[$0], $$[$0-1]);
break;
case 117:this.$ = [$$[$0]];
break;
case 118:this.$ = $$[$0-2].concat($$[$0]);
break;
case 119:this.$ = $$[$0-3].concat($$[$0]);
break;
case 120:this.$ = $$[$0-2];
break;
case 121:this.$ = $$[$0-5].concat($$[$0-2]);
break;
case 122:this.$ = $$[$0];
break;
case 123:this.$ = $$[$0];
break;
case 124:this.$ = $$[$0];
break;
case 125:this.$ = [].concat($$[$0-2], $$[$0]);
break;
case 126:this.$ = new yy.Try($$[$0]);
break;
case 127:this.$ = new yy.Try($$[$0-1], $$[$0][0], $$[$0][1]);
break;
case 128:this.$ = new yy.Try($$[$0-2], null, null, $$[$0]);
break;
case 129:this.$ = new yy.Try($$[$0-3], $$[$0-2][0], $$[$0-2][1], $$[$0]);
break;
case 130:this.$ = [$$[$0-1], $$[$0]];
break;
case 131:this.$ = new yy.Throw($$[$0]);
break;
case 132:this.$ = new yy.Parens($$[$0-1]);
break;
case 133:this.$ = new yy.Parens($$[$0-2]);
break;
case 134:this.$ = new yy.While($$[$0]);
break;
case 135:this.$ = new yy.While($$[$0-2], {
          guard: $$[$0]
        });
break;
case 136:this.$ = new yy.While($$[$0], {
          invert: true
        });
break;
case 137:this.$ = new yy.While($$[$0-2], {
          invert: true,
          guard: $$[$0]
        });
break;
case 138:this.$ = $$[$0-1].addBody($$[$0]);
break;
case 139:this.$ = $$[$0].addBody(yy.Block.wrap([$$[$0-1]]));
break;
case 140:this.$ = $$[$0].addBody(yy.Block.wrap([$$[$0-1]]));
break;
case 141:this.$ = $$[$0];
break;
case 142:this.$ = new yy.While(new yy.Literal('true')).addBody($$[$0]);
break;
case 143:this.$ = new yy.While(new yy.Literal('true')).addBody(yy.Block.wrap([$$[$0]]));
break;
case 144:this.$ = new yy.For($$[$0-1], $$[$0]);
break;
case 145:this.$ = new yy.For($$[$0-1], $$[$0]);
break;
case 146:this.$ = new yy.For($$[$0], $$[$0-1]);
break;
case 147:this.$ = {
          source: new yy.Value($$[$0])
        };
break;
case 148:this.$ = (function () {
        $$[$0].own = $$[$0-1].own;
        $$[$0].name = $$[$0-1][0];
        $$[$0].index = $$[$0-1][1];
        return $$[$0];
      }());
break;
case 149:this.$ = $$[$0];
break;
case 150:this.$ = (function () {
        $$[$0].own = true;
        return $$[$0];
      }());
break;
case 151:this.$ = $$[$0];
break;
case 152:this.$ = new yy.Value($$[$0]);
break;
case 153:this.$ = new yy.Value($$[$0]);
break;
case 154:this.$ = [$$[$0]];
break;
case 155:this.$ = [$$[$0-2], $$[$0]];
break;
case 156:this.$ = {
          source: $$[$0]
        };
break;
case 157:this.$ = {
          source: $$[$0],
          object: true
        };
break;
case 158:this.$ = {
          source: $$[$0-2],
          guard: $$[$0]
        };
break;
case 159:this.$ = {
          source: $$[$0-2],
          guard: $$[$0],
          object: true
        };
break;
case 160:this.$ = {
          source: $$[$0-2],
          step: $$[$0]
        };
break;
case 161:this.$ = {
          source: $$[$0-4],
          guard: $$[$0-2],
          step: $$[$0]
        };
break;
case 162:this.$ = {
          source: $$[$0-4],
          step: $$[$0-2],
          guard: $$[$0]
        };
break;
case 163:this.$ = new yy.Switch($$[$0-3], $$[$0-1]);
break;
case 164:this.$ = new yy.Switch($$[$0-5], $$[$0-3], $$[$0-1]);
break;
case 165:this.$ = new yy.Switch(null, $$[$0-1]);
break;
case 166:this.$ = new yy.Switch(null, $$[$0-3], $$[$0-1]);
break;
case 167:this.$ = $$[$0];
break;
case 168:this.$ = $$[$0-1].concat($$[$0]);
break;
case 169:this.$ = [[$$[$0-1], $$[$0]]];
break;
case 170:this.$ = [[$$[$0-2], $$[$0-1]]];
break;
case 171:this.$ = new yy.If($$[$0-1], $$[$0], {
          type: $$[$0-2]
        });
break;
case 172:this.$ = $$[$0-4].addElse(new yy.If($$[$0-1], $$[$0], {
          type: $$[$0-2]
        }));
break;
case 173:this.$ = $$[$0];
break;
case 174:this.$ = $$[$0-2].addElse($$[$0]);
break;
case 175:this.$ = new yy.If($$[$0], yy.Block.wrap([$$[$0-2]]), {
          type: $$[$0-1],
          statement: true
        });
break;
case 176:this.$ = new yy.If($$[$0], yy.Block.wrap([$$[$0-2]]), {
          type: $$[$0-1],
          statement: true
        });
break;
case 177:this.$ = new yy.Op($$[$0-1], $$[$0]);
break;
case 178:this.$ = new yy.Op('-', $$[$0]);
break;
case 179:this.$ = new yy.Op('+', $$[$0]);
break;
case 180:this.$ = new yy.Op('--', $$[$0]);
break;
case 181:this.$ = new yy.Op('++', $$[$0]);
break;
case 182:this.$ = new yy.Op('--', $$[$0-1], null, true);
break;
case 183:this.$ = new yy.Op('++', $$[$0-1], null, true);
break;
case 184:this.$ = new yy.Existence($$[$0-1]);
break;
case 185:this.$ = new yy.Op('+', $$[$0-2], $$[$0]);
break;
case 186:this.$ = new yy.Op('-', $$[$0-2], $$[$0]);
break;
case 187:this.$ = new yy.Op($$[$0-1], $$[$0-2], $$[$0]);
break;
case 188:this.$ = new yy.Op($$[$0-1], $$[$0-2], $$[$0]);
break;
case 189:this.$ = new yy.Op($$[$0-1], $$[$0-2], $$[$0]);
break;
case 190:this.$ = new yy.Op($$[$0-1], $$[$0-2], $$[$0]);
break;
case 191:this.$ = (function () {
        if ($$[$0-1].charAt(0) === '!') {
          return new yy.Op($$[$0-1].slice(1), $$[$0-2], $$[$0]).invert();
        } else {
          return new yy.Op($$[$0-1], $$[$0-2], $$[$0]);
        }
      }());
break;
case 192:this.$ = new yy.Assign($$[$0-2], $$[$0], $$[$0-1]);
break;
case 193:this.$ = new yy.Assign($$[$0-4], $$[$0-1], $$[$0-3]);
break;
case 194:this.$ = new yy.Extends($$[$0-2], $$[$0]);
break;
}
},
table: [{1:[2,1],3:1,4:2,5:3,7:4,8:6,9:7,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,5],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[3]},{1:[2,2],6:[1,71]},{6:[1,72]},{1:[2,4],6:[2,4],26:[2,4],99:[2,4]},{4:74,7:4,8:6,9:7,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,26:[1,73],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,7],6:[2,7],26:[2,7],99:[2,7],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,8],6:[2,8],26:[2,8],99:[2,8],100:87,101:[1,62],103:[1,63],106:88,107:[1,65],108:66,123:[1,86]},{1:[2,13],6:[2,13],25:[2,13],26:[2,13],46:[2,13],51:[2,13],54:[2,13],59:90,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],69:[2,13],70:[1,97],71:[1,98],75:[2,13],78:89,81:[1,91],82:[2,102],83:[2,13],88:[2,13],90:[2,13],99:[2,13],101:[2,13],102:[2,13],103:[2,13],107:[2,13],115:[2,13],123:[2,13],125:[2,13],126:[2,13],129:[2,13],130:[2,13],131:[2,13],132:[2,13],133:[2,13],134:[2,13]},{1:[2,14],6:[2,14],25:[2,14],26:[2,14],46:[2,14],51:[2,14],54:[2,14],59:100,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],69:[2,14],70:[1,97],71:[1,98],75:[2,14],78:99,81:[1,91],82:[2,102],83:[2,14],88:[2,14],90:[2,14],99:[2,14],101:[2,14],102:[2,14],103:[2,14],107:[2,14],115:[2,14],123:[2,14],125:[2,14],126:[2,14],129:[2,14],130:[2,14],131:[2,14],132:[2,14],133:[2,14],134:[2,14]},{1:[2,15],6:[2,15],25:[2,15],26:[2,15],46:[2,15],51:[2,15],54:[2,15],69:[2,15],75:[2,15],83:[2,15],88:[2,15],90:[2,15],99:[2,15],101:[2,15],102:[2,15],103:[2,15],107:[2,15],115:[2,15],123:[2,15],125:[2,15],126:[2,15],129:[2,15],130:[2,15],131:[2,15],132:[2,15],133:[2,15],134:[2,15]},{1:[2,16],6:[2,16],25:[2,16],26:[2,16],46:[2,16],51:[2,16],54:[2,16],69:[2,16],75:[2,16],83:[2,16],88:[2,16],90:[2,16],99:[2,16],101:[2,16],102:[2,16],103:[2,16],107:[2,16],115:[2,16],123:[2,16],125:[2,16],126:[2,16],129:[2,16],130:[2,16],131:[2,16],132:[2,16],133:[2,16],134:[2,16]},{1:[2,17],6:[2,17],25:[2,17],26:[2,17],46:[2,17],51:[2,17],54:[2,17],69:[2,17],75:[2,17],83:[2,17],88:[2,17],90:[2,17],99:[2,17],101:[2,17],102:[2,17],103:[2,17],107:[2,17],115:[2,17],123:[2,17],125:[2,17],126:[2,17],129:[2,17],130:[2,17],131:[2,17],132:[2,17],133:[2,17],134:[2,17]},{1:[2,18],6:[2,18],25:[2,18],26:[2,18],46:[2,18],51:[2,18],54:[2,18],69:[2,18],75:[2,18],83:[2,18],88:[2,18],90:[2,18],99:[2,18],101:[2,18],102:[2,18],103:[2,18],107:[2,18],115:[2,18],123:[2,18],125:[2,18],126:[2,18],129:[2,18],130:[2,18],131:[2,18],132:[2,18],133:[2,18],134:[2,18]},{1:[2,19],6:[2,19],25:[2,19],26:[2,19],46:[2,19],51:[2,19],54:[2,19],69:[2,19],75:[2,19],83:[2,19],88:[2,19],90:[2,19],99:[2,19],101:[2,19],102:[2,19],103:[2,19],107:[2,19],115:[2,19],123:[2,19],125:[2,19],126:[2,19],129:[2,19],130:[2,19],131:[2,19],132:[2,19],133:[2,19],134:[2,19]},{1:[2,20],6:[2,20],25:[2,20],26:[2,20],46:[2,20],51:[2,20],54:[2,20],69:[2,20],75:[2,20],83:[2,20],88:[2,20],90:[2,20],99:[2,20],101:[2,20],102:[2,20],103:[2,20],107:[2,20],115:[2,20],123:[2,20],125:[2,20],126:[2,20],129:[2,20],130:[2,20],131:[2,20],132:[2,20],133:[2,20],134:[2,20]},{1:[2,21],6:[2,21],25:[2,21],26:[2,21],46:[2,21],51:[2,21],54:[2,21],69:[2,21],75:[2,21],83:[2,21],88:[2,21],90:[2,21],99:[2,21],101:[2,21],102:[2,21],103:[2,21],107:[2,21],115:[2,21],123:[2,21],125:[2,21],126:[2,21],129:[2,21],130:[2,21],131:[2,21],132:[2,21],133:[2,21],134:[2,21]},{1:[2,22],6:[2,22],25:[2,22],26:[2,22],46:[2,22],51:[2,22],54:[2,22],69:[2,22],75:[2,22],83:[2,22],88:[2,22],90:[2,22],99:[2,22],101:[2,22],102:[2,22],103:[2,22],107:[2,22],115:[2,22],123:[2,22],125:[2,22],126:[2,22],129:[2,22],130:[2,22],131:[2,22],132:[2,22],133:[2,22],134:[2,22]},{1:[2,23],6:[2,23],25:[2,23],26:[2,23],46:[2,23],51:[2,23],54:[2,23],69:[2,23],75:[2,23],83:[2,23],88:[2,23],90:[2,23],99:[2,23],101:[2,23],102:[2,23],103:[2,23],107:[2,23],115:[2,23],123:[2,23],125:[2,23],126:[2,23],129:[2,23],130:[2,23],131:[2,23],132:[2,23],133:[2,23],134:[2,23]},{1:[2,9],6:[2,9],26:[2,9],99:[2,9],101:[2,9],103:[2,9],107:[2,9],123:[2,9]},{1:[2,10],6:[2,10],26:[2,10],99:[2,10],101:[2,10],103:[2,10],107:[2,10],123:[2,10]},{1:[2,11],6:[2,11],26:[2,11],99:[2,11],101:[2,11],103:[2,11],107:[2,11],123:[2,11]},{1:[2,12],6:[2,12],26:[2,12],99:[2,12],101:[2,12],103:[2,12],107:[2,12],123:[2,12]},{1:[2,69],6:[2,69],25:[2,69],26:[2,69],37:[1,101],46:[2,69],51:[2,69],54:[2,69],63:[2,69],64:[2,69],65:[2,69],67:[2,69],69:[2,69],70:[2,69],71:[2,69],75:[2,69],81:[2,69],82:[2,69],83:[2,69],88:[2,69],90:[2,69],99:[2,69],101:[2,69],102:[2,69],103:[2,69],107:[2,69],115:[2,69],123:[2,69],125:[2,69],126:[2,69],129:[2,69],130:[2,69],131:[2,69],132:[2,69],133:[2,69],134:[2,69]},{1:[2,70],6:[2,70],25:[2,70],26:[2,70],46:[2,70],51:[2,70],54:[2,70],63:[2,70],64:[2,70],65:[2,70],67:[2,70],69:[2,70],70:[2,70],71:[2,70],75:[2,70],81:[2,70],82:[2,70],83:[2,70],88:[2,70],90:[2,70],99:[2,70],101:[2,70],102:[2,70],103:[2,70],107:[2,70],115:[2,70],123:[2,70],125:[2,70],126:[2,70],129:[2,70],130:[2,70],131:[2,70],132:[2,70],133:[2,70],134:[2,70]},{1:[2,71],6:[2,71],25:[2,71],26:[2,71],46:[2,71],51:[2,71],54:[2,71],63:[2,71],64:[2,71],65:[2,71],67:[2,71],69:[2,71],70:[2,71],71:[2,71],75:[2,71],81:[2,71],82:[2,71],83:[2,71],88:[2,71],90:[2,71],99:[2,71],101:[2,71],102:[2,71],103:[2,71],107:[2,71],115:[2,71],123:[2,71],125:[2,71],126:[2,71],129:[2,71],130:[2,71],131:[2,71],132:[2,71],133:[2,71],134:[2,71]},{1:[2,72],6:[2,72],25:[2,72],26:[2,72],46:[2,72],51:[2,72],54:[2,72],63:[2,72],64:[2,72],65:[2,72],67:[2,72],69:[2,72],70:[2,72],71:[2,72],75:[2,72],81:[2,72],82:[2,72],83:[2,72],88:[2,72],90:[2,72],99:[2,72],101:[2,72],102:[2,72],103:[2,72],107:[2,72],115:[2,72],123:[2,72],125:[2,72],126:[2,72],129:[2,72],130:[2,72],131:[2,72],132:[2,72],133:[2,72],134:[2,72]},{1:[2,73],6:[2,73],25:[2,73],26:[2,73],46:[2,73],51:[2,73],54:[2,73],63:[2,73],64:[2,73],65:[2,73],67:[2,73],69:[2,73],70:[2,73],71:[2,73],75:[2,73],81:[2,73],82:[2,73],83:[2,73],88:[2,73],90:[2,73],99:[2,73],101:[2,73],102:[2,73],103:[2,73],107:[2,73],115:[2,73],123:[2,73],125:[2,73],126:[2,73],129:[2,73],130:[2,73],131:[2,73],132:[2,73],133:[2,73],134:[2,73]},{1:[2,100],6:[2,100],25:[2,100],26:[2,100],46:[2,100],51:[2,100],54:[2,100],63:[2,100],64:[2,100],65:[2,100],67:[2,100],69:[2,100],70:[2,100],71:[2,100],75:[2,100],79:102,81:[2,100],82:[1,103],83:[2,100],88:[2,100],90:[2,100],99:[2,100],101:[2,100],102:[2,100],103:[2,100],107:[2,100],115:[2,100],123:[2,100],125:[2,100],126:[2,100],129:[2,100],130:[2,100],131:[2,100],132:[2,100],133:[2,100],134:[2,100]},{27:107,28:[1,70],41:108,45:104,46:[2,51],51:[2,51],52:105,53:106,55:109,56:110,73:[1,67],86:[1,111],87:[1,112]},{5:113,25:[1,5]},{8:114,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:116,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:117,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{14:119,15:120,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:121,41:60,55:47,56:48,58:118,60:25,61:26,62:27,73:[1,67],80:[1,28],85:[1,55],86:[1,56],87:[1,54],98:[1,53]},{14:119,15:120,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:121,41:60,55:47,56:48,58:122,60:25,61:26,62:27,73:[1,67],80:[1,28],85:[1,55],86:[1,56],87:[1,54],98:[1,53]},{1:[2,66],6:[2,66],25:[2,66],26:[2,66],37:[2,66],46:[2,66],51:[2,66],54:[2,66],63:[2,66],64:[2,66],65:[2,66],67:[2,66],69:[2,66],70:[2,66],71:[2,66],75:[2,66],77:[1,126],81:[2,66],82:[2,66],83:[2,66],88:[2,66],90:[2,66],99:[2,66],101:[2,66],102:[2,66],103:[2,66],107:[2,66],115:[2,66],123:[2,66],125:[2,66],126:[2,66],127:[1,123],128:[1,124],129:[2,66],130:[2,66],131:[2,66],132:[2,66],133:[2,66],134:[2,66],135:[1,125]},{1:[2,173],6:[2,173],25:[2,173],26:[2,173],46:[2,173],51:[2,173],54:[2,173],69:[2,173],75:[2,173],83:[2,173],88:[2,173],90:[2,173],99:[2,173],101:[2,173],102:[2,173],103:[2,173],107:[2,173],115:[2,173],118:[1,127],123:[2,173],125:[2,173],126:[2,173],129:[2,173],130:[2,173],131:[2,173],132:[2,173],133:[2,173],134:[2,173]},{5:128,25:[1,5]},{5:129,25:[1,5]},{1:[2,141],6:[2,141],25:[2,141],26:[2,141],46:[2,141],51:[2,141],54:[2,141],69:[2,141],75:[2,141],83:[2,141],88:[2,141],90:[2,141],99:[2,141],101:[2,141],102:[2,141],103:[2,141],107:[2,141],115:[2,141],123:[2,141],125:[2,141],126:[2,141],129:[2,141],130:[2,141],131:[2,141],132:[2,141],133:[2,141],134:[2,141]},{5:130,25:[1,5]},{8:131,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,132],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,90],5:133,6:[2,90],14:119,15:120,25:[1,5],26:[2,90],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:121,41:60,46:[2,90],51:[2,90],54:[2,90],55:47,56:48,58:135,60:25,61:26,62:27,69:[2,90],73:[1,67],75:[2,90],77:[1,134],80:[1,28],83:[2,90],85:[1,55],86:[1,56],87:[1,54],88:[2,90],90:[2,90],98:[1,53],99:[2,90],101:[2,90],102:[2,90],103:[2,90],107:[2,90],115:[2,90],123:[2,90],125:[2,90],126:[2,90],129:[2,90],130:[2,90],131:[2,90],132:[2,90],133:[2,90],134:[2,90]},{1:[2,43],6:[2,43],8:136,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,26:[2,43],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],99:[2,43],100:39,101:[2,43],103:[2,43],104:40,105:[1,64],106:41,107:[2,43],108:66,116:[1,42],121:37,122:[1,61],123:[2,43],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:137,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,44],6:[2,44],25:[2,44],26:[2,44],51:[2,44],75:[2,44],99:[2,44],101:[2,44],103:[2,44],107:[2,44],123:[2,44]},{1:[2,67],6:[2,67],25:[2,67],26:[2,67],37:[2,67],46:[2,67],51:[2,67],54:[2,67],63:[2,67],64:[2,67],65:[2,67],67:[2,67],69:[2,67],70:[2,67],71:[2,67],75:[2,67],81:[2,67],82:[2,67],83:[2,67],88:[2,67],90:[2,67],99:[2,67],101:[2,67],102:[2,67],103:[2,67],107:[2,67],115:[2,67],123:[2,67],125:[2,67],126:[2,67],129:[2,67],130:[2,67],131:[2,67],132:[2,67],133:[2,67],134:[2,67]},{1:[2,68],6:[2,68],25:[2,68],26:[2,68],37:[2,68],46:[2,68],51:[2,68],54:[2,68],63:[2,68],64:[2,68],65:[2,68],67:[2,68],69:[2,68],70:[2,68],71:[2,68],75:[2,68],81:[2,68],82:[2,68],83:[2,68],88:[2,68],90:[2,68],99:[2,68],101:[2,68],102:[2,68],103:[2,68],107:[2,68],115:[2,68],123:[2,68],125:[2,68],126:[2,68],129:[2,68],130:[2,68],131:[2,68],132:[2,68],133:[2,68],134:[2,68]},{1:[2,29],6:[2,29],25:[2,29],26:[2,29],46:[2,29],51:[2,29],54:[2,29],63:[2,29],64:[2,29],65:[2,29],67:[2,29],69:[2,29],70:[2,29],71:[2,29],75:[2,29],81:[2,29],82:[2,29],83:[2,29],88:[2,29],90:[2,29],99:[2,29],101:[2,29],102:[2,29],103:[2,29],107:[2,29],115:[2,29],123:[2,29],125:[2,29],126:[2,29],129:[2,29],130:[2,29],131:[2,29],132:[2,29],133:[2,29],134:[2,29]},{1:[2,30],6:[2,30],25:[2,30],26:[2,30],46:[2,30],51:[2,30],54:[2,30],63:[2,30],64:[2,30],65:[2,30],67:[2,30],69:[2,30],70:[2,30],71:[2,30],75:[2,30],81:[2,30],82:[2,30],83:[2,30],88:[2,30],90:[2,30],99:[2,30],101:[2,30],102:[2,30],103:[2,30],107:[2,30],115:[2,30],123:[2,30],125:[2,30],126:[2,30],129:[2,30],130:[2,30],131:[2,30],132:[2,30],133:[2,30],134:[2,30]},{1:[2,31],6:[2,31],25:[2,31],26:[2,31],46:[2,31],51:[2,31],54:[2,31],63:[2,31],64:[2,31],65:[2,31],67:[2,31],69:[2,31],70:[2,31],71:[2,31],75:[2,31],81:[2,31],82:[2,31],83:[2,31],88:[2,31],90:[2,31],99:[2,31],101:[2,31],102:[2,31],103:[2,31],107:[2,31],115:[2,31],123:[2,31],125:[2,31],126:[2,31],129:[2,31],130:[2,31],131:[2,31],132:[2,31],133:[2,31],134:[2,31]},{1:[2,32],6:[2,32],25:[2,32],26:[2,32],46:[2,32],51:[2,32],54:[2,32],63:[2,32],64:[2,32],65:[2,32],67:[2,32],69:[2,32],70:[2,32],71:[2,32],75:[2,32],81:[2,32],82:[2,32],83:[2,32],88:[2,32],90:[2,32],99:[2,32],101:[2,32],102:[2,32],103:[2,32],107:[2,32],115:[2,32],123:[2,32],125:[2,32],126:[2,32],129:[2,32],130:[2,32],131:[2,32],132:[2,32],133:[2,32],134:[2,32]},{4:138,7:4,8:6,9:7,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,139],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:140,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,144],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],84:142,85:[1,55],86:[1,56],87:[1,54],88:[1,141],91:143,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,106],6:[2,106],25:[2,106],26:[2,106],46:[2,106],51:[2,106],54:[2,106],63:[2,106],64:[2,106],65:[2,106],67:[2,106],69:[2,106],70:[2,106],71:[2,106],75:[2,106],81:[2,106],82:[2,106],83:[2,106],88:[2,106],90:[2,106],99:[2,106],101:[2,106],102:[2,106],103:[2,106],107:[2,106],115:[2,106],123:[2,106],125:[2,106],126:[2,106],129:[2,106],130:[2,106],131:[2,106],132:[2,106],133:[2,106],134:[2,106]},{1:[2,107],6:[2,107],25:[2,107],26:[2,107],27:146,28:[1,70],46:[2,107],51:[2,107],54:[2,107],63:[2,107],64:[2,107],65:[2,107],67:[2,107],69:[2,107],70:[2,107],71:[2,107],75:[2,107],81:[2,107],82:[2,107],83:[2,107],88:[2,107],90:[2,107],99:[2,107],101:[2,107],102:[2,107],103:[2,107],107:[2,107],115:[2,107],123:[2,107],125:[2,107],126:[2,107],129:[2,107],130:[2,107],131:[2,107],132:[2,107],133:[2,107],134:[2,107]},{25:[2,47]},{25:[2,48]},{1:[2,62],6:[2,62],25:[2,62],26:[2,62],37:[2,62],46:[2,62],51:[2,62],54:[2,62],63:[2,62],64:[2,62],65:[2,62],67:[2,62],69:[2,62],70:[2,62],71:[2,62],75:[2,62],77:[2,62],81:[2,62],82:[2,62],83:[2,62],88:[2,62],90:[2,62],99:[2,62],101:[2,62],102:[2,62],103:[2,62],107:[2,62],115:[2,62],123:[2,62],125:[2,62],126:[2,62],127:[2,62],128:[2,62],129:[2,62],130:[2,62],131:[2,62],132:[2,62],133:[2,62],134:[2,62],135:[2,62]},{1:[2,65],6:[2,65],25:[2,65],26:[2,65],37:[2,65],46:[2,65],51:[2,65],54:[2,65],63:[2,65],64:[2,65],65:[2,65],67:[2,65],69:[2,65],70:[2,65],71:[2,65],75:[2,65],77:[2,65],81:[2,65],82:[2,65],83:[2,65],88:[2,65],90:[2,65],99:[2,65],101:[2,65],102:[2,65],103:[2,65],107:[2,65],115:[2,65],123:[2,65],125:[2,65],126:[2,65],127:[2,65],128:[2,65],129:[2,65],130:[2,65],131:[2,65],132:[2,65],133:[2,65],134:[2,65],135:[2,65]},{8:147,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:148,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:149,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{5:150,8:151,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,5],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{27:156,28:[1,70],55:157,56:158,61:152,73:[1,67],87:[1,54],110:153,111:[1,154],112:155},{109:159,113:[1,160],114:[1,161]},{6:[2,85],12:165,25:[2,85],27:166,28:[1,70],29:167,30:[1,68],31:[1,69],38:163,39:164,41:168,43:[1,46],51:[2,85],74:162,75:[2,85],86:[1,111]},{1:[2,27],6:[2,27],25:[2,27],26:[2,27],40:[2,27],46:[2,27],51:[2,27],54:[2,27],63:[2,27],64:[2,27],65:[2,27],67:[2,27],69:[2,27],70:[2,27],71:[2,27],75:[2,27],81:[2,27],82:[2,27],83:[2,27],88:[2,27],90:[2,27],99:[2,27],101:[2,27],102:[2,27],103:[2,27],107:[2,27],115:[2,27],123:[2,27],125:[2,27],126:[2,27],129:[2,27],130:[2,27],131:[2,27],132:[2,27],133:[2,27],134:[2,27]},{1:[2,28],6:[2,28],25:[2,28],26:[2,28],40:[2,28],46:[2,28],51:[2,28],54:[2,28],63:[2,28],64:[2,28],65:[2,28],67:[2,28],69:[2,28],70:[2,28],71:[2,28],75:[2,28],81:[2,28],82:[2,28],83:[2,28],88:[2,28],90:[2,28],99:[2,28],101:[2,28],102:[2,28],103:[2,28],107:[2,28],115:[2,28],123:[2,28],125:[2,28],126:[2,28],129:[2,28],130:[2,28],131:[2,28],132:[2,28],133:[2,28],134:[2,28]},{1:[2,26],6:[2,26],25:[2,26],26:[2,26],37:[2,26],40:[2,26],46:[2,26],51:[2,26],54:[2,26],63:[2,26],64:[2,26],65:[2,26],67:[2,26],69:[2,26],70:[2,26],71:[2,26],75:[2,26],77:[2,26],81:[2,26],82:[2,26],83:[2,26],88:[2,26],90:[2,26],99:[2,26],101:[2,26],102:[2,26],103:[2,26],107:[2,26],113:[2,26],114:[2,26],115:[2,26],123:[2,26],125:[2,26],126:[2,26],127:[2,26],128:[2,26],129:[2,26],130:[2,26],131:[2,26],132:[2,26],133:[2,26],134:[2,26],135:[2,26]},{1:[2,6],6:[2,6],7:169,8:6,9:7,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,26:[2,6],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],99:[2,6],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,3]},{1:[2,24],6:[2,24],25:[2,24],26:[2,24],46:[2,24],51:[2,24],54:[2,24],69:[2,24],75:[2,24],83:[2,24],88:[2,24],90:[2,24],95:[2,24],96:[2,24],99:[2,24],101:[2,24],102:[2,24],103:[2,24],107:[2,24],115:[2,24],118:[2,24],120:[2,24],123:[2,24],125:[2,24],126:[2,24],129:[2,24],130:[2,24],131:[2,24],132:[2,24],133:[2,24],134:[2,24]},{6:[1,71],26:[1,170]},{1:[2,184],6:[2,184],25:[2,184],26:[2,184],46:[2,184],51:[2,184],54:[2,184],69:[2,184],75:[2,184],83:[2,184],88:[2,184],90:[2,184],99:[2,184],101:[2,184],102:[2,184],103:[2,184],107:[2,184],115:[2,184],123:[2,184],125:[2,184],126:[2,184],129:[2,184],130:[2,184],131:[2,184],132:[2,184],133:[2,184],134:[2,184]},{8:171,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:172,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:173,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:174,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:175,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:176,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:177,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:178,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,140],6:[2,140],25:[2,140],26:[2,140],46:[2,140],51:[2,140],54:[2,140],69:[2,140],75:[2,140],83:[2,140],88:[2,140],90:[2,140],99:[2,140],101:[2,140],102:[2,140],103:[2,140],107:[2,140],115:[2,140],123:[2,140],125:[2,140],126:[2,140],129:[2,140],130:[2,140],131:[2,140],132:[2,140],133:[2,140],134:[2,140]},{1:[2,145],6:[2,145],25:[2,145],26:[2,145],46:[2,145],51:[2,145],54:[2,145],69:[2,145],75:[2,145],83:[2,145],88:[2,145],90:[2,145],99:[2,145],101:[2,145],102:[2,145],103:[2,145],107:[2,145],115:[2,145],123:[2,145],125:[2,145],126:[2,145],129:[2,145],130:[2,145],131:[2,145],132:[2,145],133:[2,145],134:[2,145]},{8:179,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,139],6:[2,139],25:[2,139],26:[2,139],46:[2,139],51:[2,139],54:[2,139],69:[2,139],75:[2,139],83:[2,139],88:[2,139],90:[2,139],99:[2,139],101:[2,139],102:[2,139],103:[2,139],107:[2,139],115:[2,139],123:[2,139],125:[2,139],126:[2,139],129:[2,139],130:[2,139],131:[2,139],132:[2,139],133:[2,139],134:[2,139]},{1:[2,144],6:[2,144],25:[2,144],26:[2,144],46:[2,144],51:[2,144],54:[2,144],69:[2,144],75:[2,144],83:[2,144],88:[2,144],90:[2,144],99:[2,144],101:[2,144],102:[2,144],103:[2,144],107:[2,144],115:[2,144],123:[2,144],125:[2,144],126:[2,144],129:[2,144],130:[2,144],131:[2,144],132:[2,144],133:[2,144],134:[2,144]},{79:180,82:[1,103]},{1:[2,63],6:[2,63],25:[2,63],26:[2,63],37:[2,63],46:[2,63],51:[2,63],54:[2,63],63:[2,63],64:[2,63],65:[2,63],67:[2,63],69:[2,63],70:[2,63],71:[2,63],75:[2,63],77:[2,63],81:[2,63],82:[2,63],83:[2,63],88:[2,63],90:[2,63],99:[2,63],101:[2,63],102:[2,63],103:[2,63],107:[2,63],115:[2,63],123:[2,63],125:[2,63],126:[2,63],127:[2,63],128:[2,63],129:[2,63],130:[2,63],131:[2,63],132:[2,63],133:[2,63],134:[2,63],135:[2,63]},{82:[2,103]},{27:181,28:[1,70]},{27:182,28:[1,70]},{1:[2,77],6:[2,77],25:[2,77],26:[2,77],27:183,28:[1,70],37:[2,77],46:[2,77],51:[2,77],54:[2,77],63:[2,77],64:[2,77],65:[2,77],67:[2,77],69:[2,77],70:[2,77],71:[2,77],75:[2,77],77:[2,77],81:[2,77],82:[2,77],83:[2,77],88:[2,77],90:[2,77],99:[2,77],101:[2,77],102:[2,77],103:[2,77],107:[2,77],115:[2,77],123:[2,77],125:[2,77],126:[2,77],127:[2,77],128:[2,77],129:[2,77],130:[2,77],131:[2,77],132:[2,77],133:[2,77],134:[2,77],135:[2,77]},{1:[2,78],6:[2,78],25:[2,78],26:[2,78],37:[2,78],46:[2,78],51:[2,78],54:[2,78],63:[2,78],64:[2,78],65:[2,78],67:[2,78],69:[2,78],70:[2,78],71:[2,78],75:[2,78],77:[2,78],81:[2,78],82:[2,78],83:[2,78],88:[2,78],90:[2,78],99:[2,78],101:[2,78],102:[2,78],103:[2,78],107:[2,78],115:[2,78],123:[2,78],125:[2,78],126:[2,78],127:[2,78],128:[2,78],129:[2,78],130:[2,78],131:[2,78],132:[2,78],133:[2,78],134:[2,78],135:[2,78]},{8:185,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],54:[1,189],55:47,56:48,58:36,60:25,61:26,62:27,68:184,72:186,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],89:187,90:[1,188],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{66:190,67:[1,96],70:[1,97],71:[1,98]},{66:191,67:[1,96],70:[1,97],71:[1,98]},{79:192,82:[1,103]},{1:[2,64],6:[2,64],25:[2,64],26:[2,64],37:[2,64],46:[2,64],51:[2,64],54:[2,64],63:[2,64],64:[2,64],65:[2,64],67:[2,64],69:[2,64],70:[2,64],71:[2,64],75:[2,64],77:[2,64],81:[2,64],82:[2,64],83:[2,64],88:[2,64],90:[2,64],99:[2,64],101:[2,64],102:[2,64],103:[2,64],107:[2,64],115:[2,64],123:[2,64],125:[2,64],126:[2,64],127:[2,64],128:[2,64],129:[2,64],130:[2,64],131:[2,64],132:[2,64],133:[2,64],134:[2,64],135:[2,64]},{8:193,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,194],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,101],6:[2,101],25:[2,101],26:[2,101],46:[2,101],51:[2,101],54:[2,101],63:[2,101],64:[2,101],65:[2,101],67:[2,101],69:[2,101],70:[2,101],71:[2,101],75:[2,101],81:[2,101],82:[2,101],83:[2,101],88:[2,101],90:[2,101],99:[2,101],101:[2,101],102:[2,101],103:[2,101],107:[2,101],115:[2,101],123:[2,101],125:[2,101],126:[2,101],129:[2,101],130:[2,101],131:[2,101],132:[2,101],133:[2,101],134:[2,101]},{8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,144],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],83:[1,195],84:196,85:[1,55],86:[1,56],87:[1,54],91:143,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{46:[1,198],51:[1,199]},{46:[2,52],51:[2,52]},{37:[1,201],46:[2,54],51:[2,54],54:[1,200]},{37:[2,57],46:[2,57],51:[2,57],54:[2,57]},{37:[2,58],46:[2,58],51:[2,58],54:[2,58]},{37:[2,59],46:[2,59],51:[2,59],54:[2,59]},{37:[2,60],46:[2,60],51:[2,60],54:[2,60]},{27:146,28:[1,70]},{8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,144],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],84:142,85:[1,55],86:[1,56],87:[1,54],88:[1,141],91:143,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,46],6:[2,46],25:[2,46],26:[2,46],46:[2,46],51:[2,46],54:[2,46],69:[2,46],75:[2,46],83:[2,46],88:[2,46],90:[2,46],99:[2,46],101:[2,46],102:[2,46],103:[2,46],107:[2,46],115:[2,46],123:[2,46],125:[2,46],126:[2,46],129:[2,46],130:[2,46],131:[2,46],132:[2,46],133:[2,46],134:[2,46]},{1:[2,177],6:[2,177],25:[2,177],26:[2,177],46:[2,177],51:[2,177],54:[2,177],69:[2,177],75:[2,177],83:[2,177],88:[2,177],90:[2,177],99:[2,177],100:84,101:[2,177],102:[2,177],103:[2,177],106:85,107:[2,177],108:66,115:[2,177],123:[2,177],125:[2,177],126:[2,177],129:[1,75],130:[2,177],131:[2,177],132:[2,177],133:[2,177],134:[2,177]},{100:87,101:[1,62],103:[1,63],106:88,107:[1,65],108:66,123:[1,86]},{1:[2,178],6:[2,178],25:[2,178],26:[2,178],46:[2,178],51:[2,178],54:[2,178],69:[2,178],75:[2,178],83:[2,178],88:[2,178],90:[2,178],99:[2,178],100:84,101:[2,178],102:[2,178],103:[2,178],106:85,107:[2,178],108:66,115:[2,178],123:[2,178],125:[2,178],126:[2,178],129:[1,75],130:[2,178],131:[2,178],132:[2,178],133:[2,178],134:[2,178]},{1:[2,179],6:[2,179],25:[2,179],26:[2,179],46:[2,179],51:[2,179],54:[2,179],69:[2,179],75:[2,179],83:[2,179],88:[2,179],90:[2,179],99:[2,179],100:84,101:[2,179],102:[2,179],103:[2,179],106:85,107:[2,179],108:66,115:[2,179],123:[2,179],125:[2,179],126:[2,179],129:[1,75],130:[2,179],131:[2,179],132:[2,179],133:[2,179],134:[2,179]},{1:[2,180],6:[2,180],25:[2,180],26:[2,180],46:[2,180],51:[2,180],54:[2,180],63:[2,66],64:[2,66],65:[2,66],67:[2,66],69:[2,180],70:[2,66],71:[2,66],75:[2,180],81:[2,66],82:[2,66],83:[2,180],88:[2,180],90:[2,180],99:[2,180],101:[2,180],102:[2,180],103:[2,180],107:[2,180],115:[2,180],123:[2,180],125:[2,180],126:[2,180],129:[2,180],130:[2,180],131:[2,180],132:[2,180],133:[2,180],134:[2,180]},{59:90,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],70:[1,97],71:[1,98],78:89,81:[1,91],82:[2,102]},{59:100,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],70:[1,97],71:[1,98],78:99,81:[1,91],82:[2,102]},{1:[2,69],6:[2,69],25:[2,69],26:[2,69],46:[2,69],51:[2,69],54:[2,69],63:[2,69],64:[2,69],65:[2,69],67:[2,69],69:[2,69],70:[2,69],71:[2,69],75:[2,69],81:[2,69],82:[2,69],83:[2,69],88:[2,69],90:[2,69],99:[2,69],101:[2,69],102:[2,69],103:[2,69],107:[2,69],115:[2,69],123:[2,69],125:[2,69],126:[2,69],129:[2,69],130:[2,69],131:[2,69],132:[2,69],133:[2,69],134:[2,69]},{1:[2,181],6:[2,181],25:[2,181],26:[2,181],46:[2,181],51:[2,181],54:[2,181],63:[2,66],64:[2,66],65:[2,66],67:[2,66],69:[2,181],70:[2,66],71:[2,66],75:[2,181],81:[2,66],82:[2,66],83:[2,181],88:[2,181],90:[2,181],99:[2,181],101:[2,181],102:[2,181],103:[2,181],107:[2,181],115:[2,181],123:[2,181],125:[2,181],126:[2,181],129:[2,181],130:[2,181],131:[2,181],132:[2,181],133:[2,181],134:[2,181]},{1:[2,182],6:[2,182],25:[2,182],26:[2,182],46:[2,182],51:[2,182],54:[2,182],69:[2,182],75:[2,182],83:[2,182],88:[2,182],90:[2,182],99:[2,182],101:[2,182],102:[2,182],103:[2,182],107:[2,182],115:[2,182],123:[2,182],125:[2,182],126:[2,182],129:[2,182],130:[2,182],131:[2,182],132:[2,182],133:[2,182],134:[2,182]},{1:[2,183],6:[2,183],25:[2,183],26:[2,183],46:[2,183],51:[2,183],54:[2,183],69:[2,183],75:[2,183],83:[2,183],88:[2,183],90:[2,183],99:[2,183],101:[2,183],102:[2,183],103:[2,183],107:[2,183],115:[2,183],123:[2,183],125:[2,183],126:[2,183],129:[2,183],130:[2,183],131:[2,183],132:[2,183],133:[2,183],134:[2,183]},{8:202,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,203],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:204,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{5:205,25:[1,5],122:[1,206]},{1:[2,126],6:[2,126],25:[2,126],26:[2,126],46:[2,126],51:[2,126],54:[2,126],69:[2,126],75:[2,126],83:[2,126],88:[2,126],90:[2,126],94:207,95:[1,208],96:[1,209],99:[2,126],101:[2,126],102:[2,126],103:[2,126],107:[2,126],115:[2,126],123:[2,126],125:[2,126],126:[2,126],129:[2,126],130:[2,126],131:[2,126],132:[2,126],133:[2,126],134:[2,126]},{1:[2,138],6:[2,138],25:[2,138],26:[2,138],46:[2,138],51:[2,138],54:[2,138],69:[2,138],75:[2,138],83:[2,138],88:[2,138],90:[2,138],99:[2,138],101:[2,138],102:[2,138],103:[2,138],107:[2,138],115:[2,138],123:[2,138],125:[2,138],126:[2,138],129:[2,138],130:[2,138],131:[2,138],132:[2,138],133:[2,138],134:[2,138]},{1:[2,146],6:[2,146],25:[2,146],26:[2,146],46:[2,146],51:[2,146],54:[2,146],69:[2,146],75:[2,146],83:[2,146],88:[2,146],90:[2,146],99:[2,146],101:[2,146],102:[2,146],103:[2,146],107:[2,146],115:[2,146],123:[2,146],125:[2,146],126:[2,146],129:[2,146],130:[2,146],131:[2,146],132:[2,146],133:[2,146],134:[2,146]},{25:[1,210],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{117:211,119:212,120:[1,213]},{1:[2,91],6:[2,91],25:[2,91],26:[2,91],46:[2,91],51:[2,91],54:[2,91],69:[2,91],75:[2,91],83:[2,91],88:[2,91],90:[2,91],99:[2,91],101:[2,91],102:[2,91],103:[2,91],107:[2,91],115:[2,91],123:[2,91],125:[2,91],126:[2,91],129:[2,91],130:[2,91],131:[2,91],132:[2,91],133:[2,91],134:[2,91]},{14:214,15:120,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:121,41:60,55:47,56:48,58:215,60:25,61:26,62:27,73:[1,67],80:[1,28],85:[1,55],86:[1,56],87:[1,54],98:[1,53]},{1:[2,94],5:216,6:[2,94],25:[1,5],26:[2,94],46:[2,94],51:[2,94],54:[2,94],63:[2,66],64:[2,66],65:[2,66],67:[2,66],69:[2,94],70:[2,66],71:[2,66],75:[2,94],77:[1,217],81:[2,66],82:[2,66],83:[2,94],88:[2,94],90:[2,94],99:[2,94],101:[2,94],102:[2,94],103:[2,94],107:[2,94],115:[2,94],123:[2,94],125:[2,94],126:[2,94],129:[2,94],130:[2,94],131:[2,94],132:[2,94],133:[2,94],134:[2,94]},{1:[2,42],6:[2,42],26:[2,42],99:[2,42],100:84,101:[2,42],103:[2,42],106:85,107:[2,42],108:66,123:[2,42],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,131],6:[2,131],26:[2,131],99:[2,131],100:84,101:[2,131],103:[2,131],106:85,107:[2,131],108:66,123:[2,131],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[1,71],99:[1,218]},{4:219,7:4,8:6,9:7,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,122],25:[2,122],51:[2,122],54:[1,221],88:[2,122],89:220,90:[1,188],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,109],6:[2,109],25:[2,109],26:[2,109],37:[2,109],46:[2,109],51:[2,109],54:[2,109],63:[2,109],64:[2,109],65:[2,109],67:[2,109],69:[2,109],70:[2,109],71:[2,109],75:[2,109],81:[2,109],82:[2,109],83:[2,109],88:[2,109],90:[2,109],99:[2,109],101:[2,109],102:[2,109],103:[2,109],107:[2,109],113:[2,109],114:[2,109],115:[2,109],123:[2,109],125:[2,109],126:[2,109],129:[2,109],130:[2,109],131:[2,109],132:[2,109],133:[2,109],134:[2,109]},{6:[2,49],25:[2,49],50:222,51:[1,223],88:[2,49]},{6:[2,117],25:[2,117],26:[2,117],51:[2,117],83:[2,117],88:[2,117]},{8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,144],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],84:224,85:[1,55],86:[1,56],87:[1,54],91:143,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,123],25:[2,123],26:[2,123],51:[2,123],83:[2,123],88:[2,123]},{1:[2,108],6:[2,108],25:[2,108],26:[2,108],37:[2,108],40:[2,108],46:[2,108],51:[2,108],54:[2,108],63:[2,108],64:[2,108],65:[2,108],67:[2,108],69:[2,108],70:[2,108],71:[2,108],75:[2,108],77:[2,108],81:[2,108],82:[2,108],83:[2,108],88:[2,108],90:[2,108],99:[2,108],101:[2,108],102:[2,108],103:[2,108],107:[2,108],115:[2,108],123:[2,108],125:[2,108],126:[2,108],127:[2,108],128:[2,108],129:[2,108],130:[2,108],131:[2,108],132:[2,108],133:[2,108],134:[2,108],135:[2,108]},{5:225,25:[1,5],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,134],6:[2,134],25:[2,134],26:[2,134],46:[2,134],51:[2,134],54:[2,134],69:[2,134],75:[2,134],83:[2,134],88:[2,134],90:[2,134],99:[2,134],100:84,101:[1,62],102:[1,226],103:[1,63],106:85,107:[1,65],108:66,115:[2,134],123:[2,134],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,136],6:[2,136],25:[2,136],26:[2,136],46:[2,136],51:[2,136],54:[2,136],69:[2,136],75:[2,136],83:[2,136],88:[2,136],90:[2,136],99:[2,136],100:84,101:[1,62],102:[1,227],103:[1,63],106:85,107:[1,65],108:66,115:[2,136],123:[2,136],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,142],6:[2,142],25:[2,142],26:[2,142],46:[2,142],51:[2,142],54:[2,142],69:[2,142],75:[2,142],83:[2,142],88:[2,142],90:[2,142],99:[2,142],101:[2,142],102:[2,142],103:[2,142],107:[2,142],115:[2,142],123:[2,142],125:[2,142],126:[2,142],129:[2,142],130:[2,142],131:[2,142],132:[2,142],133:[2,142],134:[2,142]},{1:[2,143],6:[2,143],25:[2,143],26:[2,143],46:[2,143],51:[2,143],54:[2,143],69:[2,143],75:[2,143],83:[2,143],88:[2,143],90:[2,143],99:[2,143],100:84,101:[1,62],102:[2,143],103:[1,63],106:85,107:[1,65],108:66,115:[2,143],123:[2,143],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,147],6:[2,147],25:[2,147],26:[2,147],46:[2,147],51:[2,147],54:[2,147],69:[2,147],75:[2,147],83:[2,147],88:[2,147],90:[2,147],99:[2,147],101:[2,147],102:[2,147],103:[2,147],107:[2,147],115:[2,147],123:[2,147],125:[2,147],126:[2,147],129:[2,147],130:[2,147],131:[2,147],132:[2,147],133:[2,147],134:[2,147]},{113:[2,149],114:[2,149]},{27:156,28:[1,70],55:157,56:158,73:[1,67],87:[1,112],110:228,112:155},{51:[1,229],113:[2,154],114:[2,154]},{51:[2,151],113:[2,151],114:[2,151]},{51:[2,152],113:[2,152],114:[2,152]},{51:[2,153],113:[2,153],114:[2,153]},{1:[2,148],6:[2,148],25:[2,148],26:[2,148],46:[2,148],51:[2,148],54:[2,148],69:[2,148],75:[2,148],83:[2,148],88:[2,148],90:[2,148],99:[2,148],101:[2,148],102:[2,148],103:[2,148],107:[2,148],115:[2,148],123:[2,148],125:[2,148],126:[2,148],129:[2,148],130:[2,148],131:[2,148],132:[2,148],133:[2,148],134:[2,148]},{8:230,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:231,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,49],25:[2,49],50:232,51:[1,233],75:[2,49]},{6:[2,86],25:[2,86],26:[2,86],51:[2,86],75:[2,86]},{6:[2,35],25:[2,35],26:[2,35],40:[1,234],51:[2,35],75:[2,35]},{6:[2,38],25:[2,38],26:[2,38],51:[2,38],75:[2,38]},{6:[2,39],25:[2,39],26:[2,39],40:[2,39],51:[2,39],75:[2,39]},{6:[2,40],25:[2,40],26:[2,40],40:[2,40],51:[2,40],75:[2,40]},{6:[2,41],25:[2,41],26:[2,41],40:[2,41],51:[2,41],75:[2,41]},{1:[2,5],6:[2,5],26:[2,5],99:[2,5]},{1:[2,25],6:[2,25],25:[2,25],26:[2,25],46:[2,25],51:[2,25],54:[2,25],69:[2,25],75:[2,25],83:[2,25],88:[2,25],90:[2,25],95:[2,25],96:[2,25],99:[2,25],101:[2,25],102:[2,25],103:[2,25],107:[2,25],115:[2,25],118:[2,25],120:[2,25],123:[2,25],125:[2,25],126:[2,25],129:[2,25],130:[2,25],131:[2,25],132:[2,25],133:[2,25],134:[2,25]},{1:[2,185],6:[2,185],25:[2,185],26:[2,185],46:[2,185],51:[2,185],54:[2,185],69:[2,185],75:[2,185],83:[2,185],88:[2,185],90:[2,185],99:[2,185],100:84,101:[2,185],102:[2,185],103:[2,185],106:85,107:[2,185],108:66,115:[2,185],123:[2,185],125:[2,185],126:[2,185],129:[1,75],130:[1,78],131:[2,185],132:[2,185],133:[2,185],134:[2,185]},{1:[2,186],6:[2,186],25:[2,186],26:[2,186],46:[2,186],51:[2,186],54:[2,186],69:[2,186],75:[2,186],83:[2,186],88:[2,186],90:[2,186],99:[2,186],100:84,101:[2,186],102:[2,186],103:[2,186],106:85,107:[2,186],108:66,115:[2,186],123:[2,186],125:[2,186],126:[2,186],129:[1,75],130:[1,78],131:[2,186],132:[2,186],133:[2,186],134:[2,186]},{1:[2,187],6:[2,187],25:[2,187],26:[2,187],46:[2,187],51:[2,187],54:[2,187],69:[2,187],75:[2,187],83:[2,187],88:[2,187],90:[2,187],99:[2,187],100:84,101:[2,187],102:[2,187],103:[2,187],106:85,107:[2,187],108:66,115:[2,187],123:[2,187],125:[2,187],126:[2,187],129:[1,75],130:[2,187],131:[2,187],132:[2,187],133:[2,187],134:[2,187]},{1:[2,188],6:[2,188],25:[2,188],26:[2,188],46:[2,188],51:[2,188],54:[2,188],69:[2,188],75:[2,188],83:[2,188],88:[2,188],90:[2,188],99:[2,188],100:84,101:[2,188],102:[2,188],103:[2,188],106:85,107:[2,188],108:66,115:[2,188],123:[2,188],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[2,188],132:[2,188],133:[2,188],134:[2,188]},{1:[2,189],6:[2,189],25:[2,189],26:[2,189],46:[2,189],51:[2,189],54:[2,189],69:[2,189],75:[2,189],83:[2,189],88:[2,189],90:[2,189],99:[2,189],100:84,101:[2,189],102:[2,189],103:[2,189],106:85,107:[2,189],108:66,115:[2,189],123:[2,189],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[2,189],133:[2,189],134:[1,82]},{1:[2,190],6:[2,190],25:[2,190],26:[2,190],46:[2,190],51:[2,190],54:[2,190],69:[2,190],75:[2,190],83:[2,190],88:[2,190],90:[2,190],99:[2,190],100:84,101:[2,190],102:[2,190],103:[2,190],106:85,107:[2,190],108:66,115:[2,190],123:[2,190],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[2,190],134:[1,82]},{1:[2,191],6:[2,191],25:[2,191],26:[2,191],46:[2,191],51:[2,191],54:[2,191],69:[2,191],75:[2,191],83:[2,191],88:[2,191],90:[2,191],99:[2,191],100:84,101:[2,191],102:[2,191],103:[2,191],106:85,107:[2,191],108:66,115:[2,191],123:[2,191],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[2,191],133:[2,191],134:[2,191]},{1:[2,176],6:[2,176],25:[2,176],26:[2,176],46:[2,176],51:[2,176],54:[2,176],69:[2,176],75:[2,176],83:[2,176],88:[2,176],90:[2,176],99:[2,176],100:84,101:[1,62],102:[2,176],103:[1,63],106:85,107:[1,65],108:66,115:[2,176],123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,175],6:[2,175],25:[2,175],26:[2,175],46:[2,175],51:[2,175],54:[2,175],69:[2,175],75:[2,175],83:[2,175],88:[2,175],90:[2,175],99:[2,175],100:84,101:[1,62],102:[2,175],103:[1,63],106:85,107:[1,65],108:66,115:[2,175],123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,98],6:[2,98],25:[2,98],26:[2,98],46:[2,98],51:[2,98],54:[2,98],63:[2,98],64:[2,98],65:[2,98],67:[2,98],69:[2,98],70:[2,98],71:[2,98],75:[2,98],81:[2,98],82:[2,98],83:[2,98],88:[2,98],90:[2,98],99:[2,98],101:[2,98],102:[2,98],103:[2,98],107:[2,98],115:[2,98],123:[2,98],125:[2,98],126:[2,98],129:[2,98],130:[2,98],131:[2,98],132:[2,98],133:[2,98],134:[2,98]},{1:[2,74],6:[2,74],25:[2,74],26:[2,74],37:[2,74],46:[2,74],51:[2,74],54:[2,74],63:[2,74],64:[2,74],65:[2,74],67:[2,74],69:[2,74],70:[2,74],71:[2,74],75:[2,74],77:[2,74],81:[2,74],82:[2,74],83:[2,74],88:[2,74],90:[2,74],99:[2,74],101:[2,74],102:[2,74],103:[2,74],107:[2,74],115:[2,74],123:[2,74],125:[2,74],126:[2,74],127:[2,74],128:[2,74],129:[2,74],130:[2,74],131:[2,74],132:[2,74],133:[2,74],134:[2,74],135:[2,74]},{1:[2,75],6:[2,75],25:[2,75],26:[2,75],37:[2,75],46:[2,75],51:[2,75],54:[2,75],63:[2,75],64:[2,75],65:[2,75],67:[2,75],69:[2,75],70:[2,75],71:[2,75],75:[2,75],77:[2,75],81:[2,75],82:[2,75],83:[2,75],88:[2,75],90:[2,75],99:[2,75],101:[2,75],102:[2,75],103:[2,75],107:[2,75],115:[2,75],123:[2,75],125:[2,75],126:[2,75],127:[2,75],128:[2,75],129:[2,75],130:[2,75],131:[2,75],132:[2,75],133:[2,75],134:[2,75],135:[2,75]},{1:[2,76],6:[2,76],25:[2,76],26:[2,76],37:[2,76],46:[2,76],51:[2,76],54:[2,76],63:[2,76],64:[2,76],65:[2,76],67:[2,76],69:[2,76],70:[2,76],71:[2,76],75:[2,76],77:[2,76],81:[2,76],82:[2,76],83:[2,76],88:[2,76],90:[2,76],99:[2,76],101:[2,76],102:[2,76],103:[2,76],107:[2,76],115:[2,76],123:[2,76],125:[2,76],126:[2,76],127:[2,76],128:[2,76],129:[2,76],130:[2,76],131:[2,76],132:[2,76],133:[2,76],134:[2,76],135:[2,76]},{69:[1,235]},{54:[1,189],69:[2,82],89:236,90:[1,188],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{69:[2,83]},{8:237,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{13:[2,111],28:[2,111],30:[2,111],31:[2,111],33:[2,111],34:[2,111],35:[2,111],42:[2,111],43:[2,111],44:[2,111],48:[2,111],49:[2,111],69:[2,111],73:[2,111],76:[2,111],80:[2,111],85:[2,111],86:[2,111],87:[2,111],93:[2,111],97:[2,111],98:[2,111],101:[2,111],103:[2,111],105:[2,111],107:[2,111],116:[2,111],122:[2,111],124:[2,111],125:[2,111],126:[2,111],127:[2,111],128:[2,111]},{13:[2,112],28:[2,112],30:[2,112],31:[2,112],33:[2,112],34:[2,112],35:[2,112],42:[2,112],43:[2,112],44:[2,112],48:[2,112],49:[2,112],69:[2,112],73:[2,112],76:[2,112],80:[2,112],85:[2,112],86:[2,112],87:[2,112],93:[2,112],97:[2,112],98:[2,112],101:[2,112],103:[2,112],105:[2,112],107:[2,112],116:[2,112],122:[2,112],124:[2,112],125:[2,112],126:[2,112],127:[2,112],128:[2,112]},{1:[2,80],6:[2,80],25:[2,80],26:[2,80],37:[2,80],46:[2,80],51:[2,80],54:[2,80],63:[2,80],64:[2,80],65:[2,80],67:[2,80],69:[2,80],70:[2,80],71:[2,80],75:[2,80],77:[2,80],81:[2,80],82:[2,80],83:[2,80],88:[2,80],90:[2,80],99:[2,80],101:[2,80],102:[2,80],103:[2,80],107:[2,80],115:[2,80],123:[2,80],125:[2,80],126:[2,80],127:[2,80],128:[2,80],129:[2,80],130:[2,80],131:[2,80],132:[2,80],133:[2,80],134:[2,80],135:[2,80]},{1:[2,81],6:[2,81],25:[2,81],26:[2,81],37:[2,81],46:[2,81],51:[2,81],54:[2,81],63:[2,81],64:[2,81],65:[2,81],67:[2,81],69:[2,81],70:[2,81],71:[2,81],75:[2,81],77:[2,81],81:[2,81],82:[2,81],83:[2,81],88:[2,81],90:[2,81],99:[2,81],101:[2,81],102:[2,81],103:[2,81],107:[2,81],115:[2,81],123:[2,81],125:[2,81],126:[2,81],127:[2,81],128:[2,81],129:[2,81],130:[2,81],131:[2,81],132:[2,81],133:[2,81],134:[2,81],135:[2,81]},{1:[2,99],6:[2,99],25:[2,99],26:[2,99],46:[2,99],51:[2,99],54:[2,99],63:[2,99],64:[2,99],65:[2,99],67:[2,99],69:[2,99],70:[2,99],71:[2,99],75:[2,99],81:[2,99],82:[2,99],83:[2,99],88:[2,99],90:[2,99],99:[2,99],101:[2,99],102:[2,99],103:[2,99],107:[2,99],115:[2,99],123:[2,99],125:[2,99],126:[2,99],129:[2,99],130:[2,99],131:[2,99],132:[2,99],133:[2,99],134:[2,99]},{1:[2,33],6:[2,33],25:[2,33],26:[2,33],46:[2,33],51:[2,33],54:[2,33],69:[2,33],75:[2,33],83:[2,33],88:[2,33],90:[2,33],99:[2,33],100:84,101:[2,33],102:[2,33],103:[2,33],106:85,107:[2,33],108:66,115:[2,33],123:[2,33],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{8:238,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,104],6:[2,104],25:[2,104],26:[2,104],46:[2,104],51:[2,104],54:[2,104],63:[2,104],64:[2,104],65:[2,104],67:[2,104],69:[2,104],70:[2,104],71:[2,104],75:[2,104],81:[2,104],82:[2,104],83:[2,104],88:[2,104],90:[2,104],99:[2,104],101:[2,104],102:[2,104],103:[2,104],107:[2,104],115:[2,104],123:[2,104],125:[2,104],126:[2,104],129:[2,104],130:[2,104],131:[2,104],132:[2,104],133:[2,104],134:[2,104]},{6:[2,49],25:[2,49],50:239,51:[1,223],83:[2,49]},{6:[2,122],25:[2,122],26:[2,122],51:[2,122],54:[1,240],83:[2,122],88:[2,122],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{47:241,48:[1,57],49:[1,58]},{27:107,28:[1,70],41:108,52:242,53:106,55:109,56:110,73:[1,67],86:[1,111],87:[1,112]},{46:[2,55],51:[2,55]},{8:243,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,192],6:[2,192],25:[2,192],26:[2,192],46:[2,192],51:[2,192],54:[2,192],69:[2,192],75:[2,192],83:[2,192],88:[2,192],90:[2,192],99:[2,192],100:84,101:[2,192],102:[2,192],103:[2,192],106:85,107:[2,192],108:66,115:[2,192],123:[2,192],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{8:244,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,194],6:[2,194],25:[2,194],26:[2,194],46:[2,194],51:[2,194],54:[2,194],69:[2,194],75:[2,194],83:[2,194],88:[2,194],90:[2,194],99:[2,194],100:84,101:[2,194],102:[2,194],103:[2,194],106:85,107:[2,194],108:66,115:[2,194],123:[2,194],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,174],6:[2,174],25:[2,174],26:[2,174],46:[2,174],51:[2,174],54:[2,174],69:[2,174],75:[2,174],83:[2,174],88:[2,174],90:[2,174],99:[2,174],101:[2,174],102:[2,174],103:[2,174],107:[2,174],115:[2,174],123:[2,174],125:[2,174],126:[2,174],129:[2,174],130:[2,174],131:[2,174],132:[2,174],133:[2,174],134:[2,174]},{8:245,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,127],6:[2,127],25:[2,127],26:[2,127],46:[2,127],51:[2,127],54:[2,127],69:[2,127],75:[2,127],83:[2,127],88:[2,127],90:[2,127],95:[1,246],99:[2,127],101:[2,127],102:[2,127],103:[2,127],107:[2,127],115:[2,127],123:[2,127],125:[2,127],126:[2,127],129:[2,127],130:[2,127],131:[2,127],132:[2,127],133:[2,127],134:[2,127]},{5:247,25:[1,5]},{27:248,28:[1,70]},{117:249,119:212,120:[1,213]},{26:[1,250],118:[1,251],119:252,120:[1,213]},{26:[2,167],118:[2,167],120:[2,167]},{8:254,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],92:253,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,92],5:255,6:[2,92],25:[1,5],26:[2,92],46:[2,92],51:[2,92],54:[2,92],59:90,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],69:[2,92],70:[1,97],71:[1,98],75:[2,92],78:89,81:[1,91],82:[2,102],83:[2,92],88:[2,92],90:[2,92],99:[2,92],101:[2,92],102:[2,92],103:[2,92],107:[2,92],115:[2,92],123:[2,92],125:[2,92],126:[2,92],129:[2,92],130:[2,92],131:[2,92],132:[2,92],133:[2,92],134:[2,92]},{1:[2,66],6:[2,66],25:[2,66],26:[2,66],46:[2,66],51:[2,66],54:[2,66],63:[2,66],64:[2,66],65:[2,66],67:[2,66],69:[2,66],70:[2,66],71:[2,66],75:[2,66],81:[2,66],82:[2,66],83:[2,66],88:[2,66],90:[2,66],99:[2,66],101:[2,66],102:[2,66],103:[2,66],107:[2,66],115:[2,66],123:[2,66],125:[2,66],126:[2,66],129:[2,66],130:[2,66],131:[2,66],132:[2,66],133:[2,66],134:[2,66]},{1:[2,95],6:[2,95],25:[2,95],26:[2,95],46:[2,95],51:[2,95],54:[2,95],69:[2,95],75:[2,95],83:[2,95],88:[2,95],90:[2,95],99:[2,95],101:[2,95],102:[2,95],103:[2,95],107:[2,95],115:[2,95],123:[2,95],125:[2,95],126:[2,95],129:[2,95],130:[2,95],131:[2,95],132:[2,95],133:[2,95],134:[2,95]},{14:256,15:120,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:121,41:60,55:47,56:48,58:215,60:25,61:26,62:27,73:[1,67],80:[1,28],85:[1,55],86:[1,56],87:[1,54],98:[1,53]},{1:[2,132],6:[2,132],25:[2,132],26:[2,132],46:[2,132],51:[2,132],54:[2,132],63:[2,132],64:[2,132],65:[2,132],67:[2,132],69:[2,132],70:[2,132],71:[2,132],75:[2,132],81:[2,132],82:[2,132],83:[2,132],88:[2,132],90:[2,132],99:[2,132],101:[2,132],102:[2,132],103:[2,132],107:[2,132],115:[2,132],123:[2,132],125:[2,132],126:[2,132],129:[2,132],130:[2,132],131:[2,132],132:[2,132],133:[2,132],134:[2,132]},{6:[1,71],26:[1,257]},{8:258,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,61],13:[2,112],25:[2,61],28:[2,112],30:[2,112],31:[2,112],33:[2,112],34:[2,112],35:[2,112],42:[2,112],43:[2,112],44:[2,112],48:[2,112],49:[2,112],51:[2,61],73:[2,112],76:[2,112],80:[2,112],85:[2,112],86:[2,112],87:[2,112],88:[2,61],93:[2,112],97:[2,112],98:[2,112],101:[2,112],103:[2,112],105:[2,112],107:[2,112],116:[2,112],122:[2,112],124:[2,112],125:[2,112],126:[2,112],127:[2,112],128:[2,112]},{6:[1,260],25:[1,261],88:[1,259]},{6:[2,50],8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[2,50],26:[2,50],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],83:[2,50],85:[1,55],86:[1,56],87:[1,54],88:[2,50],91:262,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,49],25:[2,49],26:[2,49],50:263,51:[1,223]},{1:[2,171],6:[2,171],25:[2,171],26:[2,171],46:[2,171],51:[2,171],54:[2,171],69:[2,171],75:[2,171],83:[2,171],88:[2,171],90:[2,171],99:[2,171],101:[2,171],102:[2,171],103:[2,171],107:[2,171],115:[2,171],118:[2,171],123:[2,171],125:[2,171],126:[2,171],129:[2,171],130:[2,171],131:[2,171],132:[2,171],133:[2,171],134:[2,171]},{8:264,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:265,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{113:[2,150],114:[2,150]},{27:156,28:[1,70],55:157,56:158,73:[1,67],87:[1,112],112:266},{1:[2,156],6:[2,156],25:[2,156],26:[2,156],46:[2,156],51:[2,156],54:[2,156],69:[2,156],75:[2,156],83:[2,156],88:[2,156],90:[2,156],99:[2,156],100:84,101:[2,156],102:[1,267],103:[2,156],106:85,107:[2,156],108:66,115:[1,268],123:[2,156],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,157],6:[2,157],25:[2,157],26:[2,157],46:[2,157],51:[2,157],54:[2,157],69:[2,157],75:[2,157],83:[2,157],88:[2,157],90:[2,157],99:[2,157],100:84,101:[2,157],102:[1,269],103:[2,157],106:85,107:[2,157],108:66,115:[2,157],123:[2,157],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[1,271],25:[1,272],75:[1,270]},{6:[2,50],12:165,25:[2,50],26:[2,50],27:166,28:[1,70],29:167,30:[1,68],31:[1,69],38:273,39:164,41:168,43:[1,46],75:[2,50],86:[1,111]},{8:274,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,275],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,79],6:[2,79],25:[2,79],26:[2,79],37:[2,79],46:[2,79],51:[2,79],54:[2,79],63:[2,79],64:[2,79],65:[2,79],67:[2,79],69:[2,79],70:[2,79],71:[2,79],75:[2,79],77:[2,79],81:[2,79],82:[2,79],83:[2,79],88:[2,79],90:[2,79],99:[2,79],101:[2,79],102:[2,79],103:[2,79],107:[2,79],115:[2,79],123:[2,79],125:[2,79],126:[2,79],127:[2,79],128:[2,79],129:[2,79],130:[2,79],131:[2,79],132:[2,79],133:[2,79],134:[2,79],135:[2,79]},{8:276,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,69:[2,115],73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{69:[2,116],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{26:[1,277],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[1,260],25:[1,261],83:[1,278]},{6:[2,61],25:[2,61],26:[2,61],51:[2,61],83:[2,61],88:[2,61]},{5:279,25:[1,5]},{46:[2,53],51:[2,53]},{46:[2,56],51:[2,56],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{26:[1,280],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{5:281,25:[1,5],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{5:282,25:[1,5]},{1:[2,128],6:[2,128],25:[2,128],26:[2,128],46:[2,128],51:[2,128],54:[2,128],69:[2,128],75:[2,128],83:[2,128],88:[2,128],90:[2,128],99:[2,128],101:[2,128],102:[2,128],103:[2,128],107:[2,128],115:[2,128],123:[2,128],125:[2,128],126:[2,128],129:[2,128],130:[2,128],131:[2,128],132:[2,128],133:[2,128],134:[2,128]},{5:283,25:[1,5]},{26:[1,284],118:[1,285],119:252,120:[1,213]},{1:[2,165],6:[2,165],25:[2,165],26:[2,165],46:[2,165],51:[2,165],54:[2,165],69:[2,165],75:[2,165],83:[2,165],88:[2,165],90:[2,165],99:[2,165],101:[2,165],102:[2,165],103:[2,165],107:[2,165],115:[2,165],123:[2,165],125:[2,165],126:[2,165],129:[2,165],130:[2,165],131:[2,165],132:[2,165],133:[2,165],134:[2,165]},{5:286,25:[1,5]},{26:[2,168],118:[2,168],120:[2,168]},{5:287,25:[1,5],51:[1,288]},{25:[2,124],51:[2,124],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,93],6:[2,93],25:[2,93],26:[2,93],46:[2,93],51:[2,93],54:[2,93],69:[2,93],75:[2,93],83:[2,93],88:[2,93],90:[2,93],99:[2,93],101:[2,93],102:[2,93],103:[2,93],107:[2,93],115:[2,93],123:[2,93],125:[2,93],126:[2,93],129:[2,93],130:[2,93],131:[2,93],132:[2,93],133:[2,93],134:[2,93]},{1:[2,96],5:289,6:[2,96],25:[1,5],26:[2,96],46:[2,96],51:[2,96],54:[2,96],59:90,63:[1,92],64:[1,93],65:[1,94],66:95,67:[1,96],69:[2,96],70:[1,97],71:[1,98],75:[2,96],78:89,81:[1,91],82:[2,102],83:[2,96],88:[2,96],90:[2,96],99:[2,96],101:[2,96],102:[2,96],103:[2,96],107:[2,96],115:[2,96],123:[2,96],125:[2,96],126:[2,96],129:[2,96],130:[2,96],131:[2,96],132:[2,96],133:[2,96],134:[2,96]},{99:[1,290]},{88:[1,291],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,110],6:[2,110],25:[2,110],26:[2,110],37:[2,110],46:[2,110],51:[2,110],54:[2,110],63:[2,110],64:[2,110],65:[2,110],67:[2,110],69:[2,110],70:[2,110],71:[2,110],75:[2,110],81:[2,110],82:[2,110],83:[2,110],88:[2,110],90:[2,110],99:[2,110],101:[2,110],102:[2,110],103:[2,110],107:[2,110],113:[2,110],114:[2,110],115:[2,110],123:[2,110],125:[2,110],126:[2,110],129:[2,110],130:[2,110],131:[2,110],132:[2,110],133:[2,110],134:[2,110]},{8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],91:292,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:197,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,25:[1,144],27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,57:145,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],84:293,85:[1,55],86:[1,56],87:[1,54],91:143,93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[2,118],25:[2,118],26:[2,118],51:[2,118],83:[2,118],88:[2,118]},{6:[1,260],25:[1,261],26:[1,294]},{1:[2,135],6:[2,135],25:[2,135],26:[2,135],46:[2,135],51:[2,135],54:[2,135],69:[2,135],75:[2,135],83:[2,135],88:[2,135],90:[2,135],99:[2,135],100:84,101:[1,62],102:[2,135],103:[1,63],106:85,107:[1,65],108:66,115:[2,135],123:[2,135],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,137],6:[2,137],25:[2,137],26:[2,137],46:[2,137],51:[2,137],54:[2,137],69:[2,137],75:[2,137],83:[2,137],88:[2,137],90:[2,137],99:[2,137],100:84,101:[1,62],102:[2,137],103:[1,63],106:85,107:[1,65],108:66,115:[2,137],123:[2,137],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{113:[2,155],114:[2,155]},{8:295,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:296,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:297,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,84],6:[2,84],25:[2,84],26:[2,84],37:[2,84],46:[2,84],51:[2,84],54:[2,84],63:[2,84],64:[2,84],65:[2,84],67:[2,84],69:[2,84],70:[2,84],71:[2,84],75:[2,84],81:[2,84],82:[2,84],83:[2,84],88:[2,84],90:[2,84],99:[2,84],101:[2,84],102:[2,84],103:[2,84],107:[2,84],113:[2,84],114:[2,84],115:[2,84],123:[2,84],125:[2,84],126:[2,84],129:[2,84],130:[2,84],131:[2,84],132:[2,84],133:[2,84],134:[2,84]},{12:165,27:166,28:[1,70],29:167,30:[1,68],31:[1,69],38:298,39:164,41:168,43:[1,46],86:[1,111]},{6:[2,85],12:165,25:[2,85],26:[2,85],27:166,28:[1,70],29:167,30:[1,68],31:[1,69],38:163,39:164,41:168,43:[1,46],51:[2,85],74:299,86:[1,111]},{6:[2,87],25:[2,87],26:[2,87],51:[2,87],75:[2,87]},{6:[2,36],25:[2,36],26:[2,36],51:[2,36],75:[2,36],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{8:300,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{69:[2,114],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,34],6:[2,34],25:[2,34],26:[2,34],46:[2,34],51:[2,34],54:[2,34],69:[2,34],75:[2,34],83:[2,34],88:[2,34],90:[2,34],99:[2,34],101:[2,34],102:[2,34],103:[2,34],107:[2,34],115:[2,34],123:[2,34],125:[2,34],126:[2,34],129:[2,34],130:[2,34],131:[2,34],132:[2,34],133:[2,34],134:[2,34]},{1:[2,105],6:[2,105],25:[2,105],26:[2,105],46:[2,105],51:[2,105],54:[2,105],63:[2,105],64:[2,105],65:[2,105],67:[2,105],69:[2,105],70:[2,105],71:[2,105],75:[2,105],81:[2,105],82:[2,105],83:[2,105],88:[2,105],90:[2,105],99:[2,105],101:[2,105],102:[2,105],103:[2,105],107:[2,105],115:[2,105],123:[2,105],125:[2,105],126:[2,105],129:[2,105],130:[2,105],131:[2,105],132:[2,105],133:[2,105],134:[2,105]},{1:[2,45],6:[2,45],25:[2,45],26:[2,45],46:[2,45],51:[2,45],54:[2,45],69:[2,45],75:[2,45],83:[2,45],88:[2,45],90:[2,45],99:[2,45],101:[2,45],102:[2,45],103:[2,45],107:[2,45],115:[2,45],123:[2,45],125:[2,45],126:[2,45],129:[2,45],130:[2,45],131:[2,45],132:[2,45],133:[2,45],134:[2,45]},{1:[2,193],6:[2,193],25:[2,193],26:[2,193],46:[2,193],51:[2,193],54:[2,193],69:[2,193],75:[2,193],83:[2,193],88:[2,193],90:[2,193],99:[2,193],101:[2,193],102:[2,193],103:[2,193],107:[2,193],115:[2,193],123:[2,193],125:[2,193],126:[2,193],129:[2,193],130:[2,193],131:[2,193],132:[2,193],133:[2,193],134:[2,193]},{1:[2,172],6:[2,172],25:[2,172],26:[2,172],46:[2,172],51:[2,172],54:[2,172],69:[2,172],75:[2,172],83:[2,172],88:[2,172],90:[2,172],99:[2,172],101:[2,172],102:[2,172],103:[2,172],107:[2,172],115:[2,172],118:[2,172],123:[2,172],125:[2,172],126:[2,172],129:[2,172],130:[2,172],131:[2,172],132:[2,172],133:[2,172],134:[2,172]},{1:[2,129],6:[2,129],25:[2,129],26:[2,129],46:[2,129],51:[2,129],54:[2,129],69:[2,129],75:[2,129],83:[2,129],88:[2,129],90:[2,129],99:[2,129],101:[2,129],102:[2,129],103:[2,129],107:[2,129],115:[2,129],123:[2,129],125:[2,129],126:[2,129],129:[2,129],130:[2,129],131:[2,129],132:[2,129],133:[2,129],134:[2,129]},{1:[2,130],6:[2,130],25:[2,130],26:[2,130],46:[2,130],51:[2,130],54:[2,130],69:[2,130],75:[2,130],83:[2,130],88:[2,130],90:[2,130],95:[2,130],99:[2,130],101:[2,130],102:[2,130],103:[2,130],107:[2,130],115:[2,130],123:[2,130],125:[2,130],126:[2,130],129:[2,130],130:[2,130],131:[2,130],132:[2,130],133:[2,130],134:[2,130]},{1:[2,163],6:[2,163],25:[2,163],26:[2,163],46:[2,163],51:[2,163],54:[2,163],69:[2,163],75:[2,163],83:[2,163],88:[2,163],90:[2,163],99:[2,163],101:[2,163],102:[2,163],103:[2,163],107:[2,163],115:[2,163],123:[2,163],125:[2,163],126:[2,163],129:[2,163],130:[2,163],131:[2,163],132:[2,163],133:[2,163],134:[2,163]},{5:301,25:[1,5]},{26:[1,302]},{6:[1,303],26:[2,169],118:[2,169],120:[2,169]},{8:304,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{1:[2,97],6:[2,97],25:[2,97],26:[2,97],46:[2,97],51:[2,97],54:[2,97],69:[2,97],75:[2,97],83:[2,97],88:[2,97],90:[2,97],99:[2,97],101:[2,97],102:[2,97],103:[2,97],107:[2,97],115:[2,97],123:[2,97],125:[2,97],126:[2,97],129:[2,97],130:[2,97],131:[2,97],132:[2,97],133:[2,97],134:[2,97]},{1:[2,133],6:[2,133],25:[2,133],26:[2,133],46:[2,133],51:[2,133],54:[2,133],63:[2,133],64:[2,133],65:[2,133],67:[2,133],69:[2,133],70:[2,133],71:[2,133],75:[2,133],81:[2,133],82:[2,133],83:[2,133],88:[2,133],90:[2,133],99:[2,133],101:[2,133],102:[2,133],103:[2,133],107:[2,133],115:[2,133],123:[2,133],125:[2,133],126:[2,133],129:[2,133],130:[2,133],131:[2,133],132:[2,133],133:[2,133],134:[2,133]},{1:[2,113],6:[2,113],25:[2,113],26:[2,113],46:[2,113],51:[2,113],54:[2,113],63:[2,113],64:[2,113],65:[2,113],67:[2,113],69:[2,113],70:[2,113],71:[2,113],75:[2,113],81:[2,113],82:[2,113],83:[2,113],88:[2,113],90:[2,113],99:[2,113],101:[2,113],102:[2,113],103:[2,113],107:[2,113],115:[2,113],123:[2,113],125:[2,113],126:[2,113],129:[2,113],130:[2,113],131:[2,113],132:[2,113],133:[2,113],134:[2,113]},{6:[2,119],25:[2,119],26:[2,119],51:[2,119],83:[2,119],88:[2,119]},{6:[2,49],25:[2,49],26:[2,49],50:305,51:[1,223]},{6:[2,120],25:[2,120],26:[2,120],51:[2,120],83:[2,120],88:[2,120]},{1:[2,158],6:[2,158],25:[2,158],26:[2,158],46:[2,158],51:[2,158],54:[2,158],69:[2,158],75:[2,158],83:[2,158],88:[2,158],90:[2,158],99:[2,158],100:84,101:[2,158],102:[2,158],103:[2,158],106:85,107:[2,158],108:66,115:[1,306],123:[2,158],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,160],6:[2,160],25:[2,160],26:[2,160],46:[2,160],51:[2,160],54:[2,160],69:[2,160],75:[2,160],83:[2,160],88:[2,160],90:[2,160],99:[2,160],100:84,101:[2,160],102:[1,307],103:[2,160],106:85,107:[2,160],108:66,115:[2,160],123:[2,160],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,159],6:[2,159],25:[2,159],26:[2,159],46:[2,159],51:[2,159],54:[2,159],69:[2,159],75:[2,159],83:[2,159],88:[2,159],90:[2,159],99:[2,159],100:84,101:[2,159],102:[2,159],103:[2,159],106:85,107:[2,159],108:66,115:[2,159],123:[2,159],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[2,88],25:[2,88],26:[2,88],51:[2,88],75:[2,88]},{6:[2,49],25:[2,49],26:[2,49],50:308,51:[1,233]},{26:[1,309],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{26:[1,310]},{1:[2,166],6:[2,166],25:[2,166],26:[2,166],46:[2,166],51:[2,166],54:[2,166],69:[2,166],75:[2,166],83:[2,166],88:[2,166],90:[2,166],99:[2,166],101:[2,166],102:[2,166],103:[2,166],107:[2,166],115:[2,166],123:[2,166],125:[2,166],126:[2,166],129:[2,166],130:[2,166],131:[2,166],132:[2,166],133:[2,166],134:[2,166]},{26:[2,170],118:[2,170],120:[2,170]},{25:[2,125],51:[2,125],100:84,101:[1,62],103:[1,63],106:85,107:[1,65],108:66,123:[1,83],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[1,260],25:[1,261],26:[1,311]},{8:312,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{8:313,9:115,10:19,11:20,12:21,13:[1,22],14:8,15:9,16:10,17:11,18:12,19:13,20:14,21:15,22:16,23:17,24:18,27:59,28:[1,70],29:49,30:[1,68],31:[1,69],32:24,33:[1,50],34:[1,51],35:[1,52],36:23,41:60,42:[1,44],43:[1,46],44:[1,29],47:30,48:[1,57],49:[1,58],55:47,56:48,58:36,60:25,61:26,62:27,73:[1,67],76:[1,43],80:[1,28],85:[1,55],86:[1,56],87:[1,54],93:[1,38],97:[1,45],98:[1,53],100:39,101:[1,62],103:[1,63],104:40,105:[1,64],106:41,107:[1,65],108:66,116:[1,42],121:37,122:[1,61],124:[1,31],125:[1,32],126:[1,33],127:[1,34],128:[1,35]},{6:[1,271],25:[1,272],26:[1,314]},{6:[2,37],25:[2,37],26:[2,37],51:[2,37],75:[2,37]},{1:[2,164],6:[2,164],25:[2,164],26:[2,164],46:[2,164],51:[2,164],54:[2,164],69:[2,164],75:[2,164],83:[2,164],88:[2,164],90:[2,164],99:[2,164],101:[2,164],102:[2,164],103:[2,164],107:[2,164],115:[2,164],123:[2,164],125:[2,164],126:[2,164],129:[2,164],130:[2,164],131:[2,164],132:[2,164],133:[2,164],134:[2,164]},{6:[2,121],25:[2,121],26:[2,121],51:[2,121],83:[2,121],88:[2,121]},{1:[2,161],6:[2,161],25:[2,161],26:[2,161],46:[2,161],51:[2,161],54:[2,161],69:[2,161],75:[2,161],83:[2,161],88:[2,161],90:[2,161],99:[2,161],100:84,101:[2,161],102:[2,161],103:[2,161],106:85,107:[2,161],108:66,115:[2,161],123:[2,161],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{1:[2,162],6:[2,162],25:[2,162],26:[2,162],46:[2,162],51:[2,162],54:[2,162],69:[2,162],75:[2,162],83:[2,162],88:[2,162],90:[2,162],99:[2,162],100:84,101:[2,162],102:[2,162],103:[2,162],106:85,107:[2,162],108:66,115:[2,162],123:[2,162],125:[1,77],126:[1,76],129:[1,75],130:[1,78],131:[1,79],132:[1,80],133:[1,81],134:[1,82]},{6:[2,89],25:[2,89],26:[2,89],51:[2,89],75:[2,89]}],
defaultActions: {57:[2,47],58:[2,48],72:[2,3],91:[2,103],186:[2,83]},
parseError: function parseError(str, hash) {
    throw new Error(str);
},
parse: function parse(input) {
    var self = this,
        stack = [0],
        vstack = [null], // semantic value stack
        lstack = [], // location stack
        table = this.table,
        yytext = '',
        yylineno = 0,
        yyleng = 0,
        recovering = 0,
        TERROR = 2,
        EOF = 1;

    //this.reductionCount = this.shiftCount = 0;

    this.lexer.setInput(input);
    this.lexer.yy = this.yy;
    this.yy.lexer = this.lexer;
    if (typeof this.lexer.yylloc == 'undefined')
        this.lexer.yylloc = {};
    var yyloc = this.lexer.yylloc;
    lstack.push(yyloc);

    if (typeof this.yy.parseError === 'function')
        this.parseError = this.yy.parseError;

    function popStack (n) {
        stack.length = stack.length - 2*n;
        vstack.length = vstack.length - n;
        lstack.length = lstack.length - n;
    }

    function lex() {
        var token;
        token = self.lexer.lex() || 1; // $end = 1
        // if token isn't its numeric value, convert
        if (typeof token !== 'number') {
            token = self.symbols_[token] || token;
        }
        return token;
    };

    var symbol, preErrorSymbol, state, action, a, r, yyval={},p,len,newState, expected;
    while (true) {
        // retreive state number from top of stack
        state = stack[stack.length-1];

        // use default actions if available
        if (this.defaultActions[state]) {
            action = this.defaultActions[state];
        } else {
            if (symbol == null)
                symbol = lex();
            // read action for current state and first input
            action = table[state] && table[state][symbol];
        }

        // handle parse error
        if (typeof action === 'undefined' || !action.length || !action[0]) {

            if (!recovering) {
                // Report error
                expected = [];
                for (p in table[state]) if (this.terminals_[p] && p > 2) {
                    expected.push("'"+this.terminals_[p]+"'");
                }
                var errStr = '';
                if (this.lexer.showPosition) {
                    errStr = 'Parse error on line '+(yylineno+1)+":\n"+this.lexer.showPosition()+'\nExpecting '+expected.join(', ');
                } else {
                    errStr = 'Parse error on line '+(yylineno+1)+": Unexpected " +
                                  (symbol == 1 /*EOF*/ ? "end of input" :
                                              ("'"+(this.terminals_[symbol] || symbol)+"'"));
                }
                this.parseError(errStr,
                    {text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected});
            }

            // just recovered from another error
            if (recovering == 3) {
                if (symbol == EOF) {
                    throw new Error(errStr || 'Parsing halted.');
                }

                // discard current lookahead and grab another
                yyleng = this.lexer.yyleng;
                yytext = this.lexer.yytext;
                yylineno = this.lexer.yylineno;
                yyloc = this.lexer.yylloc;
                symbol = lex();
            }

            // try to recover from error
            while (1) {
                // check for error recovery rule in this state
                if ((TERROR.toString()) in table[state]) {
                    break;
                }
                if (state == 0) {
                    throw new Error(errStr || 'Parsing halted.');
                }
                popStack(1);
                state = stack[stack.length-1];
            }

            preErrorSymbol = symbol; // save the lookahead token
            symbol = TERROR;         // insert generic error symbol as new lookahead
            state = stack[stack.length-1];
            action = table[state] && table[state][TERROR];
            recovering = 3; // allow 3 real symbols to be shifted before reporting a new error
        }

        // this shouldn't happen, unless resolve defaults are off
        if (action[0] instanceof Array && action.length > 1) {
            throw new Error('Parse Error: multiple actions possible at state: '+state+', token: '+symbol);
        }

        switch (action[0]) {

            case 1: // shift
                //this.shiftCount++;

                stack.push(symbol);
                vstack.push(this.lexer.yytext);
                lstack.push(this.lexer.yylloc);
                stack.push(action[1]); // push state
                symbol = null;
                if (!preErrorSymbol) { // normal execution/no error
                    yyleng = this.lexer.yyleng;
                    yytext = this.lexer.yytext;
                    yylineno = this.lexer.yylineno;
                    yyloc = this.lexer.yylloc;
                    if (recovering > 0)
                        recovering--;
                } else { // error just occurred, resume old lookahead f/ before error
                    symbol = preErrorSymbol;
                    preErrorSymbol = null;
                }
                break;

            case 2: // reduce
                //this.reductionCount++;

                len = this.productions_[action[1]][1];

                // perform semantic action
                yyval.$ = vstack[vstack.length-len]; // default to $$ = $1
                // default location, uses first token for firsts, last for lasts
                yyval._$ = {
                    first_line: lstack[lstack.length-(len||1)].first_line,
                    last_line: lstack[lstack.length-1].last_line,
                    first_column: lstack[lstack.length-(len||1)].first_column,
                    last_column: lstack[lstack.length-1].last_column
                };
                r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);

                if (typeof r !== 'undefined') {
                    return r;
                }

                // pop off stack
                if (len) {
                    stack = stack.slice(0,-1*len*2);
                    vstack = vstack.slice(0, -1*len);
                    lstack = lstack.slice(0, -1*len);
                }

                stack.push(this.productions_[action[1]][0]);    // push nonterminal (reduce)
                vstack.push(yyval.$);
                lstack.push(yyval._$);
                // goto new state = table[STATE][NONTERMINAL]
                newState = table[stack[stack.length-2]][stack[stack.length-1]];
                stack.push(newState);
                break;

            case 3: // accept
                return true;
        }

    }

    return true;
}};

module.exports = parser;
});/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/nodes', ['require', 'exports', 'module' , 'ace/mode/coffee/scope', 'ace/mode/coffee/helpers'], function(require, exports, module) {
  var Access, Arr, Assign, Base, Block, Call, Class, Closure, Code, Comment, Existence, Extends, For, IDENTIFIER, IS_STRING, If, In, Index, LEVEL_ACCESS, LEVEL_COND, LEVEL_LIST, LEVEL_OP, LEVEL_PAREN, LEVEL_TOP, Literal, METHOD_DEF, NEGATE, NO, Obj, Op, Param, Parens, Push, Range, Return, SIMPLENUM, Scope, Slice, Splat, Switch, TAB, THIS, Throw, Try, UTILITIES, Value, While, YES, compact, del, ends, extend, flatten, last, merge, multident, starts, unfoldSoak, utility, _ref;
  var __hasProp = Object.prototype.hasOwnProperty, __extends = function(child, parent) {
    for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; }
    function ctor() { this.constructor = child; }
    ctor.prototype = parent.prototype;
    child.prototype = new ctor;
    child.__super__ = parent.prototype;
    return child;
  }, __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; }, __indexOf = Array.prototype.indexOf || function(item) {
    for (var i = 0, l = this.length; i < l; i++) {
      if (this[i] === item) return i;
    }
    return -1;
  };
  Scope = require('./scope').Scope;
  _ref = require('./helpers'), compact = _ref.compact, flatten = _ref.flatten, extend = _ref.extend, merge = _ref.merge, del = _ref.del, starts = _ref.starts, ends = _ref.ends, last = _ref.last;
  exports.extend = extend;
  YES = function() {
    return true;
  };
  NO = function() {
    return false;
  };
  THIS = function() {
    return this;
  };
  NEGATE = function() {
    this.negated = !this.negated;
    return this;
  };
  exports.Base = Base = (function() {
    function Base() {}
    Base.prototype.compile = function(o, lvl) {
      var node;
      o = extend({}, o);
      if (lvl) {
        o.level = lvl;
      }
      node = this.unfoldSoak(o) || this;
      node.tab = o.indent;
      if (o.level === LEVEL_TOP || !node.isStatement(o)) {
        return node.compileNode(o);
      } else {
        return node.compileClosure(o);
      }
    };
    Base.prototype.compileClosure = function(o) {
      if (this.jumps() || this instanceof Throw) {
        throw SyntaxError('cannot use a pure statement in an expression.');
      }
      o.sharedScope = true;
      return Closure.wrap(this).compileNode(o);
    };
    Base.prototype.cache = function(o, level, reused) {
      var ref, sub;
      if (!this.isComplex()) {
        ref = level ? this.compile(o, level) : this;
        return [ref, ref];
      } else {
        ref = new Literal(reused || o.scope.freeVariable('ref'));
        sub = new Assign(ref, this);
        if (level) {
          return [sub.compile(o, level), ref.value];
        } else {
          return [sub, ref];
        }
      }
    };
    Base.prototype.compileLoopReference = function(o, name) {
      var src, tmp;
      src = tmp = this.compile(o, LEVEL_LIST);
      if (!((-Infinity < +src && +src < Infinity) || IDENTIFIER.test(src) && o.scope.check(src, true))) {
        src = "" + (tmp = o.scope.freeVariable(name)) + " = " + src;
      }
      return [src, tmp];
    };
    Base.prototype.makeReturn = function() {
      return new Return(this);
    };
    Base.prototype.contains = function(pred) {
      var contains;
      contains = false;
      this.traverseChildren(false, function(node) {
        if (pred(node)) {
          contains = true;
          return false;
        }
      });
      return contains;
    };
    Base.prototype.containsType = function(type) {
      return this instanceof type || this.contains(function(node) {
        return node instanceof type;
      });
    };
    Base.prototype.lastNonComment = function(list) {
      var i;
      i = list.length;
      while (i--) {
        if (!(list[i] instanceof Comment)) {
          return list[i];
        }
      }
      return null;
    };
    Base.prototype.toString = function(idt, name) {
      var tree;
      if (idt == null) {
        idt = '';
      }
      if (name == null) {
        name = this.constructor.name;
      }
      tree = '\n' + idt + name;
      if (this.soak) {
        tree += '?';
      }
      this.eachChild(function(node) {
        return tree += node.toString(idt + TAB);
      });
      return tree;
    };
    Base.prototype.eachChild = function(func) {
      var attr, child, _i, _j, _len, _len2, _ref2, _ref3;
      if (!this.children) {
        return this;
      }
      _ref2 = this.children;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        attr = _ref2[_i];
        if (this[attr]) {
          _ref3 = flatten([this[attr]]);
          for (_j = 0, _len2 = _ref3.length; _j < _len2; _j++) {
            child = _ref3[_j];
            if (func(child) === false) {
              return this;
            }
          }
        }
      }
      return this;
    };
    Base.prototype.traverseChildren = function(crossScope, func) {
      return this.eachChild(function(child) {
        if (func(child) === false) {
          return false;
        }
        return child.traverseChildren(crossScope, func);
      });
    };
    Base.prototype.invert = function() {
      return new Op('!', this);
    };
    Base.prototype.unwrapAll = function() {
      var node;
      node = this;
      while (node !== (node = node.unwrap())) {
        continue;
      }
      return node;
    };
    Base.prototype.children = [];
    Base.prototype.isStatement = NO;
    Base.prototype.jumps = NO;
    Base.prototype.isComplex = YES;
    Base.prototype.isChainable = NO;
    Base.prototype.isAssignable = NO;
    Base.prototype.unwrap = THIS;
    Base.prototype.unfoldSoak = NO;
    Base.prototype.assigns = NO;
    return Base;
  })();
  exports.Block = Block = (function() {
    __extends(Block, Base);
    function Block(nodes) {
      this.expressions = compact(flatten(nodes || []));
    }
    Block.prototype.children = ['expressions'];
    Block.prototype.push = function(node) {
      this.expressions.push(node);
      return this;
    };
    Block.prototype.pop = function() {
      return this.expressions.pop();
    };
    Block.prototype.unshift = function(node) {
      this.expressions.unshift(node);
      return this;
    };
    Block.prototype.unwrap = function() {
      if (this.expressions.length === 1) {
        return this.expressions[0];
      } else {
        return this;
      }
    };
    Block.prototype.isEmpty = function() {
      return !this.expressions.length;
    };
    Block.prototype.isStatement = function(o) {
      var exp, _i, _len, _ref2;
      _ref2 = this.expressions;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        exp = _ref2[_i];
        if (exp.isStatement(o)) {
          return true;
        }
      }
      return false;
    };
    Block.prototype.jumps = function(o) {
      var exp, _i, _len, _ref2;
      _ref2 = this.expressions;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        exp = _ref2[_i];
        if (exp.jumps(o)) {
          return exp;
        }
      }
    };
    Block.prototype.makeReturn = function() {
      var expr, len;
      len = this.expressions.length;
      while (len--) {
        expr = this.expressions[len];
        if (!(expr instanceof Comment)) {
          this.expressions[len] = expr.makeReturn();
          if (expr instanceof Return && !expr.expression) {
            this.expressions.splice(len, 1);
          }
          break;
        }
      }
      return this;
    };
    Block.prototype.compile = function(o, level) {
      if (o == null) {
        o = {};
      }
      if (o.scope) {
        return Block.__super__.compile.call(this, o, level);
      } else {
        return this.compileRoot(o);
      }
    };
    Block.prototype.compileNode = function(o) {
      var code, codes, node, top, _i, _len, _ref2;
      this.tab = o.indent;
      top = o.level === LEVEL_TOP;
      codes = [];
      _ref2 = this.expressions;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        node = _ref2[_i];
        node = node.unwrapAll();
        node = node.unfoldSoak(o) || node;
        if (top) {
          node.front = true;
          code = node.compile(o);
          codes.push(node.isStatement(o) ? code : this.tab + code + ';');
        } else {
          codes.push(node.compile(o, LEVEL_LIST));
        }
      }
      if (top) {
        return codes.join('\n');
      }
      code = codes.join(', ') || 'void 0';
      if (codes.length > 1 && o.level >= LEVEL_LIST) {
        return "(" + code + ")";
      } else {
        return code;
      }
    };
    Block.prototype.compileRoot = function(o) {
      var code;
      o.indent = this.tab = o.bare ? '' : TAB;
      o.scope = new Scope(null, this, null);
      o.level = LEVEL_TOP;
      code = this.compileWithDeclarations(o);
      if (o.bare) {
        return code;
      } else {
        return "(function() {\n" + code + "\n}).call(this);\n";
      }
    };
    Block.prototype.compileWithDeclarations = function(o) {
      var assigns, code, declars, exp, i, post, rest, scope, _len, _ref2;
      code = post = '';
      _ref2 = this.expressions;
      for (i = 0, _len = _ref2.length; i < _len; i++) {
        exp = _ref2[i];
        exp = exp.unwrap();
        if (!(exp instanceof Comment || exp instanceof Literal)) {
          break;
        }
      }
      o = merge(o, {
        level: LEVEL_TOP
      });
      if (i) {
        rest = this.expressions.splice(i, this.expressions.length);
        code = this.compileNode(o);
        this.expressions = rest;
      }
      post = this.compileNode(o);
      scope = o.scope;
      if (scope.expressions === this) {
        declars = o.scope.hasDeclarations();
        assigns = scope.hasAssignments;
        if ((declars || assigns) && i) {
          code += '\n';
        }
        if (declars) {
          code += "" + this.tab + "var " + (scope.declaredVariables().join(', ')) + ";\n";
        }
        if (assigns) {
          code += "" + this.tab + "var " + (multident(scope.assignedVariables().join(', '), this.tab)) + ";\n";
        }
      }
      return code + post;
    };
    Block.wrap = function(nodes) {
      if (nodes.length === 1 && nodes[0] instanceof Block) {
        return nodes[0];
      }
      return new Block(nodes);
    };
    return Block;
  })();
  exports.Literal = Literal = (function() {
    __extends(Literal, Base);
    function Literal(value) {
      this.value = value;
    }
    Literal.prototype.makeReturn = function() {
      if (this.isStatement()) {
        return this;
      } else {
        return new Return(this);
      }
    };
    Literal.prototype.isAssignable = function() {
      return IDENTIFIER.test(this.value);
    };
    Literal.prototype.isStatement = function() {
      var _ref2;
      return (_ref2 = this.value) === 'break' || _ref2 === 'continue' || _ref2 === 'debugger';
    };
    Literal.prototype.isComplex = NO;
    Literal.prototype.assigns = function(name) {
      return name === this.value;
    };
    Literal.prototype.jumps = function(o) {
      if (!this.isStatement()) {
        return false;
      }
      if (!(o && (o.loop || o.block && (this.value !== 'continue')))) {
        return this;
      } else {
        return false;
      }
    };
    Literal.prototype.compileNode = function(o) {
      var code;
      code = this.isUndefined ? o.level >= LEVEL_ACCESS ? '(void 0)' : 'void 0' : this.value.reserved ? "\"" + this.value + "\"" : this.value;
      if (this.isStatement()) {
        return "" + this.tab + code + ";";
      } else {
        return code;
      }
    };
    Literal.prototype.toString = function() {
      return ' "' + this.value + '"';
    };
    return Literal;
  })();
  exports.Return = Return = (function() {
    __extends(Return, Base);
    function Return(expr) {
      if (expr && !expr.unwrap().isUndefined) {
        this.expression = expr;
      }
    }
    Return.prototype.children = ['expression'];
    Return.prototype.isStatement = YES;
    Return.prototype.makeReturn = THIS;
    Return.prototype.jumps = THIS;
    Return.prototype.compile = function(o, level) {
      var expr, _ref2;
      expr = (_ref2 = this.expression) != null ? _ref2.makeReturn() : void 0;
      if (expr && !(expr instanceof Return)) {
        return expr.compile(o, level);
      } else {
        return Return.__super__.compile.call(this, o, level);
      }
    };
    Return.prototype.compileNode = function(o) {
      return this.tab + ("return" + (this.expression ? ' ' + this.expression.compile(o, LEVEL_PAREN) : '') + ";");
    };
    return Return;
  })();
  exports.Value = Value = (function() {
    __extends(Value, Base);
    function Value(base, props, tag) {
      if (!props && base instanceof Value) {
        return base;
      }
      this.base = base;
      this.properties = props || [];
      if (tag) {
        this[tag] = true;
      }
      return this;
    }
    Value.prototype.children = ['base', 'properties'];
    Value.prototype.push = function(prop) {
      this.properties.push(prop);
      return this;
    };
    Value.prototype.hasProperties = function() {
      return !!this.properties.length;
    };
    Value.prototype.isArray = function() {
      return !this.properties.length && this.base instanceof Arr;
    };
    Value.prototype.isComplex = function() {
      return this.hasProperties() || this.base.isComplex();
    };
    Value.prototype.isAssignable = function() {
      return this.hasProperties() || this.base.isAssignable();
    };
    Value.prototype.isSimpleNumber = function() {
      return this.base instanceof Literal && SIMPLENUM.test(this.base.value);
    };
    Value.prototype.isAtomic = function() {
      var node, _i, _len, _ref2;
      _ref2 = this.properties.concat(this.base);
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        node = _ref2[_i];
        if (node.soak || node instanceof Call) {
          return false;
        }
      }
      return true;
    };
    Value.prototype.isStatement = function(o) {
      return !this.properties.length && this.base.isStatement(o);
    };
    Value.prototype.assigns = function(name) {
      return !this.properties.length && this.base.assigns(name);
    };
    Value.prototype.jumps = function(o) {
      return !this.properties.length && this.base.jumps(o);
    };
    Value.prototype.isObject = function(onlyGenerated) {
      if (this.properties.length) {
        return false;
      }
      return (this.base instanceof Obj) && (!onlyGenerated || this.base.generated);
    };
    Value.prototype.isSplice = function() {
      return last(this.properties) instanceof Slice;
    };
    Value.prototype.makeReturn = function() {
      if (this.properties.length) {
        return Value.__super__.makeReturn.call(this);
      } else {
        return this.base.makeReturn();
      }
    };
    Value.prototype.unwrap = function() {
      if (this.properties.length) {
        return this;
      } else {
        return this.base;
      }
    };
    Value.prototype.cacheReference = function(o) {
      var base, bref, name, nref;
      name = last(this.properties);
      if (this.properties.length < 2 && !this.base.isComplex() && !(name != null ? name.isComplex() : void 0)) {
        return [this, this];
      }
      base = new Value(this.base, this.properties.slice(0, -1));
      if (base.isComplex()) {
        bref = new Literal(o.scope.freeVariable('base'));
        base = new Value(new Parens(new Assign(bref, base)));
      }
      if (!name) {
        return [base, bref];
      }
      if (name.isComplex()) {
        nref = new Literal(o.scope.freeVariable('name'));
        name = new Index(new Assign(nref, name.index));
        nref = new Index(nref);
      }
      return [base.push(name), new Value(bref || base.base, [nref || name])];
    };
    Value.prototype.compileNode = function(o) {
      var code, prop, props, _i, _len;
      this.base.front = this.front;
      props = this.properties;
      code = this.base.compile(o, props.length ? LEVEL_ACCESS : null);
      if (props[0] instanceof Access && this.isSimpleNumber()) {
        code = "(" + code + ")";
      }
      for (_i = 0, _len = props.length; _i < _len; _i++) {
        prop = props[_i];
        code += prop.compile(o);
      }
      return code;
    };
    Value.prototype.unfoldSoak = function(o) {
      var result;
      if (this.unfoldedSoak != null) {
        return this.unfoldedSoak;
      }
      result = __bind(function() {
        var fst, i, ifn, prop, ref, snd, _len, _ref2;
        if (ifn = this.base.unfoldSoak(o)) {
          Array.prototype.push.apply(ifn.body.properties, this.properties);
          return ifn;
        }
        _ref2 = this.properties;
        for (i = 0, _len = _ref2.length; i < _len; i++) {
          prop = _ref2[i];
          if (prop.soak) {
            prop.soak = false;
            fst = new Value(this.base, this.properties.slice(0, i));
            snd = new Value(this.base, this.properties.slice(i));
            if (fst.isComplex()) {
              ref = new Literal(o.scope.freeVariable('ref'));
              fst = new Parens(new Assign(ref, fst));
              snd.base = ref;
            }
            return new If(new Existence(fst), snd, {
              soak: true
            });
          }
        }
        return null;
      }, this)();
      return this.unfoldedSoak = result || false;
    };
    return Value;
  })();
  exports.Comment = Comment = (function() {
    __extends(Comment, Base);
    function Comment(comment) {
      this.comment = comment;
    }
    Comment.prototype.isStatement = YES;
    Comment.prototype.makeReturn = THIS;
    Comment.prototype.compileNode = function(o, level) {
      var code;
      code = '/*' + multident(this.comment, this.tab) + '*/';
      if ((level || o.level) === LEVEL_TOP) {
        code = o.indent + code;
      }
      return code;
    };
    return Comment;
  })();
  exports.Call = Call = (function() {
    __extends(Call, Base);
    function Call(variable, args, soak) {
      this.args = args != null ? args : [];
      this.soak = soak;
      this.isNew = false;
      this.isSuper = variable === 'super';
      this.variable = this.isSuper ? null : variable;
    }
    Call.prototype.children = ['variable', 'args'];
    Call.prototype.newInstance = function() {
      var base;
      base = this.variable.base || this.variable;
      if (base instanceof Call) {
        base.newInstance();
      } else {
        this.isNew = true;
      }
      return this;
    };
    Call.prototype.superReference = function(o) {
      var method, name;
      method = o.scope.method;
      if (!method) {
        throw SyntaxError('cannot call super outside of a function.');
      }
      name = method.name;
      if (!name) {
        throw SyntaxError('cannot call super on an anonymous function.');
      }
      if (method.klass) {
        return "" + method.klass + ".__super__." + name;
      } else {
        return "" + name + ".__super__.constructor";
      }
    };
    Call.prototype.unfoldSoak = function(o) {
      var call, ifn, left, list, rite, _i, _len, _ref2, _ref3;
      if (this.soak) {
        if (this.variable) {
          if (ifn = unfoldSoak(o, this, 'variable')) {
            return ifn;
          }
          _ref2 = new Value(this.variable).cacheReference(o), left = _ref2[0], rite = _ref2[1];
        } else {
          left = new Literal(this.superReference(o));
          rite = new Value(left);
        }
        rite = new Call(rite, this.args);
        rite.isNew = this.isNew;
        left = new Literal("typeof " + (left.compile(o)) + " === \"function\"");
        return new If(left, new Value(rite), {
          soak: true
        });
      }
      call = this;
      list = [];
      while (true) {
        if (call.variable instanceof Call) {
          list.push(call);
          call = call.variable;
          continue;
        }
        if (!(call.variable instanceof Value)) {
          break;
        }
        list.push(call);
        if (!((call = call.variable.base) instanceof Call)) {
          break;
        }
      }
      _ref3 = list.reverse();
      for (_i = 0, _len = _ref3.length; _i < _len; _i++) {
        call = _ref3[_i];
        if (ifn) {
          if (call.variable instanceof Call) {
            call.variable = ifn;
          } else {
            call.variable.base = ifn;
          }
        }
        ifn = unfoldSoak(o, call, 'variable');
      }
      return ifn;
    };
    Call.prototype.filterImplicitObjects = function(list) {
      var node, nodes, obj, prop, properties, _i, _j, _len, _len2, _ref2;
      nodes = [];
      for (_i = 0, _len = list.length; _i < _len; _i++) {
        node = list[_i];
        if (!((typeof node.isObject === "function" ? node.isObject() : void 0) && node.base.generated)) {
          nodes.push(node);
          continue;
        }
        obj = null;
        _ref2 = node.base.properties;
        for (_j = 0, _len2 = _ref2.length; _j < _len2; _j++) {
          prop = _ref2[_j];
          if (prop instanceof Assign) {
            if (!obj) {
              nodes.push(obj = new Obj(properties = [], true));
            }
            properties.push(prop);
          } else {
            nodes.push(prop);
            obj = null;
          }
        }
      }
      return nodes;
    };
    Call.prototype.compileNode = function(o) {
      var arg, args, code, _ref2;
      if ((_ref2 = this.variable) != null) {
        _ref2.front = this.front;
      }
      if (code = Splat.compileSplattedArray(o, this.args, true)) {
        return this.compileSplat(o, code);
      }
      args = this.filterImplicitObjects(this.args);
      args = ((function() {
        var _i, _len, _results;
        _results = [];
        for (_i = 0, _len = args.length; _i < _len; _i++) {
          arg = args[_i];
          _results.push(arg.compile(o, LEVEL_LIST));
        }
        return _results;
      })()).join(', ');
      if (this.isSuper) {
        return this.superReference(o) + (".call(this" + (args && ', ' + args) + ")");
      } else {
        return (this.isNew ? 'new ' : '') + this.variable.compile(o, LEVEL_ACCESS) + ("(" + args + ")");
      }
    };
    Call.prototype.compileSuper = function(args, o) {
      return "" + (this.superReference(o)) + ".call(this" + (args.length ? ', ' : '') + args + ")";
    };
    Call.prototype.compileSplat = function(o, splatArgs) {
      var base, fun, idt, name, ref;
      if (this.isSuper) {
        return "" + (this.superReference(o)) + ".apply(this, " + splatArgs + ")";
      }
      if (this.isNew) {
        idt = this.tab + TAB;
        return "(function(func, args, ctor) {\n" + idt + "ctor.prototype = func.prototype;\n" + idt + "var child = new ctor, result = func.apply(child, args);\n" + idt + "return typeof result === \"object\" ? result : child;\n" + this.tab + "})(" + (this.variable.compile(o, LEVEL_LIST)) + ", " + splatArgs + ", function() {})";
      }
      base = new Value(this.variable);
      if ((name = base.properties.pop()) && base.isComplex()) {
        ref = o.scope.freeVariable('ref');
        fun = "(" + ref + " = " + (base.compile(o, LEVEL_LIST)) + ")" + (name.compile(o));
      } else {
        fun = base.compile(o, LEVEL_ACCESS);
        if (SIMPLENUM.test(fun)) {
          fun = "(" + fun + ")";
        }
        if (name) {
          ref = fun;
          fun += name.compile(o);
        } else {
          ref = 'null';
        }
      }
      return "" + fun + ".apply(" + ref + ", " + splatArgs + ")";
    };
    return Call;
  })();
  exports.Extends = Extends = (function() {
    __extends(Extends, Base);
    function Extends(child, parent) {
      this.child = child;
      this.parent = parent;
    }
    Extends.prototype.children = ['child', 'parent'];
    Extends.prototype.compile = function(o) {
      utility('hasProp');
      return new Call(new Value(new Literal(utility('extends'))), [this.child, this.parent]).compile(o);
    };
    return Extends;
  })();
  exports.Access = Access = (function() {
    __extends(Access, Base);
    function Access(name, tag) {
      this.name = name;
      this.name.asKey = true;
      this.proto = tag === 'proto' ? '.prototype' : '';
      this.soak = tag === 'soak';
    }
    Access.prototype.children = ['name'];
    Access.prototype.compile = function(o) {
      var name;
      name = this.name.compile(o);
      return this.proto + (IS_STRING.test(name) ? "[" + name + "]" : "." + name);
    };
    Access.prototype.isComplex = NO;
    return Access;
  })();
  exports.Index = Index = (function() {
    __extends(Index, Base);
    function Index(index) {
      this.index = index;
    }
    Index.prototype.children = ['index'];
    Index.prototype.compile = function(o) {
      return (this.proto ? '.prototype' : '') + ("[" + (this.index.compile(o, LEVEL_PAREN)) + "]");
    };
    Index.prototype.isComplex = function() {
      return this.index.isComplex();
    };
    return Index;
  })();
  exports.Range = Range = (function() {
    __extends(Range, Base);
    Range.prototype.children = ['from', 'to'];
    function Range(from, to, tag) {
      this.from = from;
      this.to = to;
      this.exclusive = tag === 'exclusive';
      this.equals = this.exclusive ? '' : '=';
    }
    Range.prototype.compileVariables = function(o) {
      var step, _ref2, _ref3, _ref4, _ref5;
      o = merge(o, {
        top: true
      });
      _ref2 = this.from.cache(o, LEVEL_LIST), this.from = _ref2[0], this.fromVar = _ref2[1];
      _ref3 = this.to.cache(o, LEVEL_LIST), this.to = _ref3[0], this.toVar = _ref3[1];
      if (step = del(o, 'step')) {
        _ref4 = step.cache(o, LEVEL_LIST), this.step = _ref4[0], this.stepVar = _ref4[1];
      }
      _ref5 = [this.fromVar.match(SIMPLENUM), this.toVar.match(SIMPLENUM)], this.fromNum = _ref5[0], this.toNum = _ref5[1];
      if (this.stepVar) {
        return this.stepNum = this.stepVar.match(SIMPLENUM);
      }
    };
    Range.prototype.compileNode = function(o) {
      var cond, condPart, from, gt, idx, known, lt, stepPart, to, varPart, _ref2, _ref3;
      if (!this.fromVar) {
        this.compileVariables(o);
      }
      if (!o.index) {
        return this.compileArray(o);
      }
      known = this.fromNum && this.toNum;
      idx = del(o, 'index');
      varPart = "" + idx + " = " + this.from;
      if (this.to !== this.toVar) {
        varPart += ", " + this.to;
      }
      if (this.step !== this.stepVar) {
        varPart += ", " + this.step;
      }
      _ref2 = ["" + idx + " <" + this.equals, "" + idx + " >" + this.equals], lt = _ref2[0], gt = _ref2[1];
      condPart = this.stepNum ? condPart = +this.stepNum > 0 ? "" + lt + " " + this.toVar : "" + gt + " " + this.toVar : known ? ((_ref3 = [+this.fromNum, +this.toNum], from = _ref3[0], to = _ref3[1], _ref3), condPart = from <= to ? "" + lt + " " + to : "" + gt + " " + to) : (cond = "" + this.fromVar + " <= " + this.toVar, condPart = "" + cond + " ? " + lt + " " + this.toVar + " : " + gt + " " + this.toVar);
      stepPart = this.stepVar ? "" + idx + " += " + this.stepVar : known ? from <= to ? "" + idx + "++" : "" + idx + "--" : "" + cond + " ? " + idx + "++ : " + idx + "--";
      return "" + varPart + "; " + condPart + "; " + stepPart;
    };
    Range.prototype.compileArray = function(o) {
      var body, cond, i, idt, post, pre, range, result, vars, _i, _ref2, _ref3, _results;
      if (this.fromNum && this.toNum && Math.abs(this.fromNum - this.toNum) <= 20) {
        range = (function() {
          _results = [];
          for (var _i = _ref2 = +this.fromNum, _ref3 = +this.toNum; _ref2 <= _ref3 ? _i <= _ref3 : _i >= _ref3; _ref2 <= _ref3 ? _i++ : _i--){ _results.push(_i); }
          return _results;
        }).apply(this, arguments);
        if (this.exclusive) {
          range.pop();
        }
        return "[" + (range.join(', ')) + "]";
      }
      idt = this.tab + TAB;
      i = o.scope.freeVariable('i');
      result = o.scope.freeVariable('results');
      pre = "\n" + idt + result + " = [];";
      if (this.fromNum && this.toNum) {
        o.index = i;
        body = this.compileNode(o);
      } else {
        vars = ("" + i + " = " + this.from) + (this.to !== this.toVar ? ", " + this.to : '');
        cond = "" + this.fromVar + " <= " + this.toVar;
        body = "var " + vars + "; " + cond + " ? " + i + " <" + this.equals + " " + this.toVar + " : " + i + " >" + this.equals + " " + this.toVar + "; " + cond + " ? " + i + "++ : " + i + "--";
      }
      post = "{ " + result + ".push(" + i + "); }\n" + idt + "return " + result + ";\n" + o.indent;
      return "(function() {" + pre + "\n" + idt + "for (" + body + ")" + post + "}).apply(this, arguments)";
    };
    return Range;
  })();
  exports.Slice = Slice = (function() {
    __extends(Slice, Base);
    Slice.prototype.children = ['range'];
    function Slice(range) {
      this.range = range;
      Slice.__super__.constructor.call(this);
    }
    Slice.prototype.compileNode = function(o) {
      var compiled, from, fromStr, to, toStr, _ref2;
      _ref2 = this.range, to = _ref2.to, from = _ref2.from;
      fromStr = from && from.compile(o, LEVEL_PAREN) || '0';
      compiled = to && to.compile(o, LEVEL_PAREN);
      if (to && !(!this.range.exclusive && +compiled === -1)) {
        toStr = ', ' + (this.range.exclusive ? compiled : SIMPLENUM.test(compiled) ? (+compiled + 1).toString() : "(" + compiled + " + 1) || 9e9");
      }
      return ".slice(" + fromStr + (toStr || '') + ")";
    };
    return Slice;
  })();
  exports.Obj = Obj = (function() {
    __extends(Obj, Base);
    function Obj(props, generated) {
      this.generated = generated != null ? generated : false;
      this.objects = this.properties = props || [];
    }
    Obj.prototype.children = ['properties'];
    Obj.prototype.compileNode = function(o) {
      var i, idt, indent, join, lastNoncom, node, obj, prop, props, _i, _len;
      props = this.properties;
      if (!props.length) {
        if (this.front) {
          return '({})';
        } else {
          return '{}';
        }
      }
      if (this.generated) {
        for (_i = 0, _len = props.length; _i < _len; _i++) {
          node = props[_i];
          if (node instanceof Value) {
            throw new Error('cannot have an implicit value in an implicit object');
          }
        }
      }
      idt = o.indent += TAB;
      lastNoncom = this.lastNonComment(this.properties);
      props = (function() {
        var _len2, _results;
        _results = [];
        for (i = 0, _len2 = props.length; i < _len2; i++) {
          prop = props[i];
          join = i === props.length - 1 ? '' : prop === lastNoncom || prop instanceof Comment ? '\n' : ',\n';
          indent = prop instanceof Comment ? '' : idt;
          if (prop instanceof Value && prop["this"]) {
            prop = new Assign(prop.properties[0].name, prop, 'object');
          }
          if (!(prop instanceof Comment)) {
            if (!(prop instanceof Assign)) {
              prop = new Assign(prop, prop, 'object');
            }
            (prop.variable.base || prop.variable).asKey = true;
          }
          _results.push(indent + prop.compile(o, LEVEL_TOP) + join);
        }
        return _results;
      })();
      props = props.join('');
      obj = "{" + (props && '\n' + props + '\n' + this.tab) + "}";
      if (this.front) {
        return "(" + obj + ")";
      } else {
        return obj;
      }
    };
    Obj.prototype.assigns = function(name) {
      var prop, _i, _len, _ref2;
      _ref2 = this.properties;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        prop = _ref2[_i];
        if (prop.assigns(name)) {
          return true;
        }
      }
      return false;
    };
    return Obj;
  })();
  exports.Arr = Arr = (function() {
    __extends(Arr, Base);
    function Arr(objs) {
      this.objects = objs || [];
    }
    Arr.prototype.children = ['objects'];
    Arr.prototype.filterImplicitObjects = Call.prototype.filterImplicitObjects;
    Arr.prototype.compileNode = function(o) {
      var code, obj, objs;
      if (!this.objects.length) {
        return '[]';
      }
      o.indent += TAB;
      objs = this.filterImplicitObjects(this.objects);
      if (code = Splat.compileSplattedArray(o, objs)) {
        return code;
      }
      code = ((function() {
        var _i, _len, _results;
        _results = [];
        for (_i = 0, _len = objs.length; _i < _len; _i++) {
          obj = objs[_i];
          _results.push(obj.compile(o, LEVEL_LIST));
        }
        return _results;
      })()).join(', ');
      if (code.indexOf('\n') >= 0) {
        return "[\n" + o.indent + code + "\n" + this.tab + "]";
      } else {
        return "[" + code + "]";
      }
    };
    Arr.prototype.assigns = function(name) {
      var obj, _i, _len, _ref2;
      _ref2 = this.objects;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        obj = _ref2[_i];
        if (obj.assigns(name)) {
          return true;
        }
      }
      return false;
    };
    return Arr;
  })();
  exports.Class = Class = (function() {
    __extends(Class, Base);
    function Class(variable, parent, body) {
      this.variable = variable;
      this.parent = parent;
      this.body = body != null ? body : new Block;
      this.boundFuncs = [];
      this.body.classBody = true;
    }
    Class.prototype.children = ['variable', 'parent', 'body'];
    Class.prototype.determineName = function() {
      var decl, tail;
      if (!this.variable) {
        return null;
      }
      decl = (tail = last(this.variable.properties)) ? tail instanceof Access && tail.name.value : this.variable.base.value;
      return decl && (decl = IDENTIFIER.test(decl) && decl);
    };
    Class.prototype.setContext = function(name) {
      return this.body.traverseChildren(false, function(node) {
        if (node.classBody) {
          return false;
        }
        if (node instanceof Literal && node.value === 'this') {
          return node.value = name;
        } else if (node instanceof Code) {
          node.klass = name;
          if (node.bound) {
            return node.context = name;
          }
        }
      });
    };
    Class.prototype.addBoundFunctions = function(o) {
      var bname, bvar, _i, _len, _ref2, _results;
      if (this.boundFuncs.length) {
        _ref2 = this.boundFuncs;
        _results = [];
        for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
          bvar = _ref2[_i];
          bname = bvar.compile(o);
          _results.push(this.ctor.body.unshift(new Literal("this." + bname + " = " + (utility('bind')) + "(this." + bname + ", this)")));
        }
        return _results;
      }
    };
    Class.prototype.addProperties = function(node, name, o) {
      var assign, base, exprs, func, props;
      props = node.base.properties.slice(0);
      exprs = (function() {
        var _results;
        _results = [];
        while (assign = props.shift()) {
          if (assign instanceof Assign) {
            base = assign.variable.base;
            delete assign.context;
            func = assign.value;
            if (base.value === 'constructor') {
              if (this.ctor) {
                throw new Error('cannot define more than one constructor in a class');
              }
              if (func.bound) {
                throw new Error('cannot define a constructor as a bound function');
              }
              if (func instanceof Code) {
                assign = this.ctor = func;
              } else {
                this.externalCtor = o.scope.freeVariable('class');
                assign = new Assign(new Literal(this.externalCtor), func);
              }
            } else {
              if (!assign.variable["this"]) {
                assign.variable = new Value(new Literal(name), [new Access(base, 'proto')]);
              }
              if (func instanceof Code && func.bound) {
                this.boundFuncs.push(base);
                func.bound = false;
              }
            }
          }
          _results.push(assign);
        }
        return _results;
      }).call(this);
      return compact(exprs);
    };
    Class.prototype.walkBody = function(name, o) {
      return this.traverseChildren(false, __bind(function(child) {
        var exps, i, node, _len, _ref2;
        if (child instanceof Class) {
          return false;
        }
        if (child instanceof Block) {
          _ref2 = exps = child.expressions;
          for (i = 0, _len = _ref2.length; i < _len; i++) {
            node = _ref2[i];
            if (node instanceof Value && node.isObject(true)) {
              exps[i] = this.addProperties(node, name, o);
            }
          }
          return child.expressions = exps = flatten(exps);
        }
      }, this));
    };
    Class.prototype.ensureConstructor = function(name) {
      if (!this.ctor) {
        this.ctor = new Code;
        if (this.parent) {
          this.ctor.body.push(new Literal("" + name + ".__super__.constructor.apply(this, arguments)"));
        }
        if (this.externalCtor) {
          this.ctor.body.push(new Literal("" + this.externalCtor + ".apply(this, arguments)"));
        }
        this.body.expressions.unshift(this.ctor);
      }
      this.ctor.ctor = this.ctor.name = name;
      this.ctor.klass = null;
      return this.ctor.noReturn = true;
    };
    Class.prototype.compileNode = function(o) {
      var decl, klass, lname, name;
      decl = this.determineName();
      name = decl || this.name || '_Class';
      lname = new Literal(name);
      this.setContext(name);
      this.walkBody(name, o);
      this.ensureConstructor(name);
      if (this.parent) {
        this.body.expressions.unshift(new Extends(lname, this.parent));
      }
      if (!(this.ctor instanceof Code)) {
        this.body.expressions.unshift(this.ctor);
      }
      this.body.expressions.push(lname);
      this.addBoundFunctions(o);
      klass = new Parens(Closure.wrap(this.body), true);
      if (this.variable) {
        klass = new Assign(this.variable, klass);
      }
      return klass.compile(o);
    };
    return Class;
  })();
  exports.Assign = Assign = (function() {
    __extends(Assign, Base);
    function Assign(variable, value, context, options) {
      this.variable = variable;
      this.value = value;
      this.context = context;
      this.param = options && options.param;
    }
    Assign.prototype.children = ['variable', 'value'];
    Assign.prototype.assigns = function(name) {
      return this[this.context === 'object' ? 'value' : 'variable'].assigns(name);
    };
    Assign.prototype.unfoldSoak = function(o) {
      return unfoldSoak(o, this, 'variable');
    };
    Assign.prototype.compileNode = function(o) {
      var isValue, match, name, val, _ref2;
      if (isValue = this.variable instanceof Value) {
        if (this.variable.isArray() || this.variable.isObject()) {
          return this.compilePatternMatch(o);
        }
        if (this.variable.isSplice()) {
          return this.compileSplice(o);
        }
        if ((_ref2 = this.context) === '||=' || _ref2 === '&&=' || _ref2 === '?=') {
          return this.compileConditional(o);
        }
      }
      name = this.variable.compile(o, LEVEL_LIST);
      if (!(this.context || this.variable.isAssignable())) {
        throw SyntaxError("\"" + (this.variable.compile(o)) + "\" cannot be assigned.");
      }
      if (!(this.context || isValue && (this.variable.namespaced || this.variable.hasProperties()))) {
        if (this.param) {
          o.scope.add(name, 'var');
        } else {
          o.scope.find(name);
        }
      }
      if (this.value instanceof Code && (match = METHOD_DEF.exec(name))) {
        this.value.name = match[2];
        if (match[1]) {
          this.value.klass = match[1];
        }
      }
      val = this.value.compile(o, LEVEL_LIST);
      if (this.context === 'object') {
        return "" + name + ": " + val;
      }
      val = name + (" " + (this.context || '=') + " ") + val;
      if (o.level <= LEVEL_LIST) {
        return val;
      } else {
        return "(" + val + ")";
      }
    };
    Assign.prototype.compilePatternMatch = function(o) {
      var acc, assigns, code, i, idx, isObject, ivar, obj, objects, olen, ref, rest, splat, top, val, value, vvar, _len, _ref2, _ref3, _ref4, _ref5;
      top = o.level === LEVEL_TOP;
      value = this.value;
      objects = this.variable.base.objects;
      if (!(olen = objects.length)) {
        code = value.compile(o);
        if (o.level >= LEVEL_OP) {
          return "(" + code + ")";
        } else {
          return code;
        }
      }
      isObject = this.variable.isObject();
      if (top && olen === 1 && !((obj = objects[0]) instanceof Splat)) {
        if (obj instanceof Assign) {
          _ref2 = obj, idx = _ref2.variable.base, obj = _ref2.value;
        } else {
          if (obj.base instanceof Parens) {
            _ref3 = new Value(obj.unwrapAll()).cacheReference(o), obj = _ref3[0], idx = _ref3[1];
          } else {
            idx = isObject ? obj["this"] ? obj.properties[0].name : obj : new Literal(0);
          }
        }
        acc = IDENTIFIER.test(idx.unwrap().value || 0);
        value = new Value(value);
        value.properties.push(new (acc ? Access : Index)(idx));
        return new Assign(obj, value, null, {
          param: this.param
        }).compile(o, LEVEL_TOP);
      }
      vvar = value.compile(o, LEVEL_LIST);
      assigns = [];
      splat = false;
      if (!IDENTIFIER.test(vvar) || this.variable.assigns(vvar)) {
        assigns.push("" + (ref = o.scope.freeVariable('ref')) + " = " + vvar);
        vvar = ref;
      }
      for (i = 0, _len = objects.length; i < _len; i++) {
        obj = objects[i];
        idx = i;
        if (isObject) {
          if (obj instanceof Assign) {
            _ref4 = obj, idx = _ref4.variable.base, obj = _ref4.value;
          } else {
            if (obj.base instanceof Parens) {
              _ref5 = new Value(obj.unwrapAll()).cacheReference(o), obj = _ref5[0], idx = _ref5[1];
            } else {
              idx = obj["this"] ? obj.properties[0].name : obj;
            }
          }
        }
        if (!splat && obj instanceof Splat) {
          val = "" + olen + " <= " + vvar + ".length ? " + (utility('slice')) + ".call(" + vvar + ", " + i;
          if (rest = olen - i - 1) {
            ivar = o.scope.freeVariable('i');
            val += ", " + ivar + " = " + vvar + ".length - " + rest + ") : (" + ivar + " = " + i + ", [])";
          } else {
            val += ") : []";
          }
          val = new Literal(val);
          splat = "" + ivar + "++";
        } else {
          if (obj instanceof Splat) {
            obj = obj.name.compile(o);
            throw SyntaxError("multiple splats are disallowed in an assignment: " + obj + " ...");
          }
          if (typeof idx === 'number') {
            idx = new Literal(splat || idx);
            acc = false;
          } else {
            acc = isObject && IDENTIFIER.test(idx.unwrap().value || 0);
          }
          val = new Value(new Literal(vvar), [new (acc ? Access : Index)(idx)]);
        }
        assigns.push(new Assign(obj, val, null, {
          param: this.param
        }).compile(o, LEVEL_TOP));
      }
      if (!top) {
        assigns.push(vvar);
      }
      code = assigns.join(', ');
      if (o.level < LEVEL_LIST) {
        return code;
      } else {
        return "(" + code + ")";
      }
    };
    Assign.prototype.compileConditional = function(o) {
      var left, rite, _ref2;
      _ref2 = this.variable.cacheReference(o), left = _ref2[0], rite = _ref2[1];
      if (__indexOf.call(this.context, "?") >= 0) {
        o.isExistentialEquals = true;
      }
      return new Op(this.context.slice(0, -1), left, new Assign(rite, this.value, '=')).compile(o);
    };
    Assign.prototype.compileSplice = function(o) {
      var code, exclusive, from, fromDecl, fromRef, name, to, valDef, valRef, _ref2, _ref3, _ref4;
      _ref2 = this.variable.properties.pop().range, from = _ref2.from, to = _ref2.to, exclusive = _ref2.exclusive;
      name = this.variable.compile(o);
      _ref3 = (from != null ? from.cache(o, LEVEL_OP) : void 0) || ['0', '0'], fromDecl = _ref3[0], fromRef = _ref3[1];
      if (to) {
        if ((from != null ? from.isSimpleNumber() : void 0) && to.isSimpleNumber()) {
          to = +to.compile(o) - +fromRef;
          if (!exclusive) {
            to += 1;
          }
        } else {
          to = to.compile(o) + ' - ' + fromRef;
          if (!exclusive) {
            to += ' + 1';
          }
        }
      } else {
        to = "9e9";
      }
      _ref4 = this.value.cache(o, LEVEL_LIST), valDef = _ref4[0], valRef = _ref4[1];
      code = "[].splice.apply(" + name + ", [" + fromDecl + ", " + to + "].concat(" + valDef + ")), " + valRef;
      if (o.level > LEVEL_TOP) {
        return "(" + code + ")";
      } else {
        return code;
      }
    };
    return Assign;
  })();
  exports.Code = Code = (function() {
    __extends(Code, Base);
    function Code(params, body, tag) {
      this.params = params || [];
      this.body = body || new Block;
      this.bound = tag === 'boundfunc';
      if (this.bound) {
        this.context = 'this';
      }
    }
    Code.prototype.children = ['params', 'body'];
    Code.prototype.isStatement = function() {
      return !!this.ctor;
    };
    Code.prototype.jumps = NO;
    Code.prototype.compileNode = function(o) {
      var code, exprs, i, idt, lit, p, param, ref, splats, v, val, vars, wasEmpty, _i, _j, _k, _len, _len2, _len3, _len4, _ref2, _ref3, _ref4, _ref5;
      o.scope = new Scope(o.scope, this.body, this);
      o.scope.shared = del(o, 'sharedScope');
      o.indent += TAB;
      delete o.bare;
      vars = [];
      exprs = [];
      _ref2 = this.params;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        param = _ref2[_i];
        if (param.splat) {
          _ref3 = this.params;
          for (_j = 0, _len2 = _ref3.length; _j < _len2; _j++) {
            p = _ref3[_j];
            if (p.name.value) {
              o.scope.add(p.name.value, 'var', true);
            }
          }
          splats = new Assign(new Value(new Arr((function() {
            var _k, _len3, _ref4, _results;
            _ref4 = this.params;
            _results = [];
            for (_k = 0, _len3 = _ref4.length; _k < _len3; _k++) {
              p = _ref4[_k];
              _results.push(p.asReference(o));
            }
            return _results;
          }).call(this))), new Value(new Literal('arguments')));
          break;
        }
      }
      _ref4 = this.params;
      for (_k = 0, _len3 = _ref4.length; _k < _len3; _k++) {
        param = _ref4[_k];
        if (param.isComplex()) {
          val = ref = param.asReference(o);
          if (param.value) {
            val = new Op('?', ref, param.value);
          }
          exprs.push(new Assign(new Value(param.name), val, '=', {
            param: true
          }));
        } else {
          ref = param;
          if (param.value) {
            lit = new Literal(ref.name.value + ' == null');
            val = new Assign(new Value(param.name), param.value, '=');
            exprs.push(new If(lit, val));
          }
        }
        if (!splats) {
          vars.push(ref);
        }
      }
      wasEmpty = this.body.isEmpty();
      if (splats) {
        exprs.unshift(splats);
      }
      if (exprs.length) {
        (_ref5 = this.body.expressions).unshift.apply(_ref5, exprs);
      }
      if (!splats) {
        for (i = 0, _len4 = vars.length; i < _len4; i++) {
          v = vars[i];
          o.scope.parameter(vars[i] = v.compile(o));
        }
      }
      if (!(wasEmpty || this.noReturn)) {
        this.body.makeReturn();
      }
      idt = o.indent;
      code = 'function';
      if (this.ctor) {
        code += ' ' + this.name;
      }
      code += '(' + vars.join(', ') + ') {';
      if (!this.body.isEmpty()) {
        code += "\n" + (this.body.compileWithDeclarations(o)) + "\n" + this.tab;
      }
      code += '}';
      if (this.ctor) {
        return this.tab + code;
      }
      if (this.bound) {
        return utility('bind') + ("(" + code + ", " + this.context + ")");
      }
      if (this.front || (o.level >= LEVEL_ACCESS)) {
        return "(" + code + ")";
      } else {
        return code;
      }
    };
    Code.prototype.traverseChildren = function(crossScope, func) {
      if (crossScope) {
        return Code.__super__.traverseChildren.call(this, crossScope, func);
      }
    };
    return Code;
  })();
  exports.Param = Param = (function() {
    __extends(Param, Base);
    function Param(name, value, splat) {
      this.name = name;
      this.value = value;
      this.splat = splat;
    }
    Param.prototype.children = ['name', 'value'];
    Param.prototype.compile = function(o) {
      return this.name.compile(o, LEVEL_LIST);
    };
    Param.prototype.asReference = function(o) {
      var node;
      if (this.reference) {
        return this.reference;
      }
      node = this.name;
      if (node["this"]) {
        node = node.properties[0].name;
        if (node.value.reserved) {
          node = new Literal('_' + node.value);
        }
      } else if (node.isComplex()) {
        node = new Literal(o.scope.freeVariable('arg'));
      }
      node = new Value(node);
      if (this.splat) {
        node = new Splat(node);
      }
      return this.reference = node;
    };
    Param.prototype.isComplex = function() {
      return this.name.isComplex();
    };
    return Param;
  })();
  exports.Splat = Splat = (function() {
    __extends(Splat, Base);
    Splat.prototype.children = ['name'];
    Splat.prototype.isAssignable = YES;
    function Splat(name) {
      this.name = name.compile ? name : new Literal(name);
    }
    Splat.prototype.assigns = function(name) {
      return this.name.assigns(name);
    };
    Splat.prototype.compile = function(o) {
      if (this.index != null) {
        return this.compileParam(o);
      } else {
        return this.name.compile(o);
      }
    };
    Splat.compileSplattedArray = function(o, list, apply) {
      var args, base, code, i, index, node, _len;
      index = -1;
      while ((node = list[++index]) && !(node instanceof Splat)) {
        continue;
      }
      if (index >= list.length) {
        return '';
      }
      if (list.length === 1) {
        code = list[0].compile(o, LEVEL_LIST);
        if (apply) {
          return code;
        }
        return "" + (utility('slice')) + ".call(" + code + ")";
      }
      args = list.slice(index);
      for (i = 0, _len = args.length; i < _len; i++) {
        node = args[i];
        code = node.compile(o, LEVEL_LIST);
        args[i] = node instanceof Splat ? "" + (utility('slice')) + ".call(" + code + ")" : "[" + code + "]";
      }
      if (index === 0) {
        return args[0] + (".concat(" + (args.slice(1).join(', ')) + ")");
      }
      base = (function() {
        var _i, _len2, _ref2, _results;
        _ref2 = list.slice(0, index);
        _results = [];
        for (_i = 0, _len2 = _ref2.length; _i < _len2; _i++) {
          node = _ref2[_i];
          _results.push(node.compile(o, LEVEL_LIST));
        }
        return _results;
      })();
      return "[" + (base.join(', ')) + "].concat(" + (args.join(', ')) + ")";
    };
    return Splat;
  })();
  exports.While = While = (function() {
    __extends(While, Base);
    function While(condition, options) {
      this.condition = (options != null ? options.invert : void 0) ? condition.invert() : condition;
      this.guard = options != null ? options.guard : void 0;
    }
    While.prototype.children = ['condition', 'guard', 'body'];
    While.prototype.isStatement = YES;
    While.prototype.makeReturn = function() {
      this.returns = true;
      return this;
    };
    While.prototype.addBody = function(body) {
      this.body = body;
      return this;
    };
    While.prototype.jumps = function() {
      var expressions, node, _i, _len;
      expressions = this.body.expressions;
      if (!expressions.length) {
        return false;
      }
      for (_i = 0, _len = expressions.length; _i < _len; _i++) {
        node = expressions[_i];
        if (node.jumps({
          loop: true
        })) {
          return node;
        }
      }
      return false;
    };
    While.prototype.compileNode = function(o) {
      var body, code, rvar, set;
      o.indent += TAB;
      set = '';
      body = this.body;
      if (body.isEmpty()) {
        body = '';
      } else {
        if (o.level > LEVEL_TOP || this.returns) {
          rvar = o.scope.freeVariable('results');
          set = "" + this.tab + rvar + " = [];\n";
          if (body) {
            body = Push.wrap(rvar, body);
          }
        }
        if (this.guard) {
          body = Block.wrap([new If(this.guard, body)]);
        }
        body = "\n" + (body.compile(o, LEVEL_TOP)) + "\n" + this.tab;
      }
      code = set + this.tab + ("while (" + (this.condition.compile(o, LEVEL_PAREN)) + ") {" + body + "}");
      if (this.returns) {
        code += "\n" + this.tab + "return " + rvar + ";";
      }
      return code;
    };
    return While;
  })();
  exports.Op = Op = (function() {
    var CONVERSIONS, INVERSIONS;
    __extends(Op, Base);
    function Op(op, first, second, flip) {
      var call;
      if (op === 'in') {
        return new In(first, second);
      }
      if (op === 'do') {
        call = new Call(first, first.params || []);
        call["do"] = true;
        return call;
      }
      if (op === 'new') {
        if (first instanceof Call && !first["do"]) {
          return first.newInstance();
        }
        if (first instanceof Code && first.bound || first["do"]) {
          first = new Parens(first);
        }
      }
      this.operator = CONVERSIONS[op] || op;
      this.first = first;
      this.second = second;
      this.flip = !!flip;
      return this;
    }
    CONVERSIONS = {
      '==': '===',
      '!=': '!==',
      'of': 'in'
    };
    INVERSIONS = {
      '!==': '===',
      '===': '!=='
    };
    Op.prototype.children = ['first', 'second'];
    Op.prototype.isSimpleNumber = NO;
    Op.prototype.isUnary = function() {
      return !this.second;
    };
    Op.prototype.isComplex = function() {
      var _ref2;
      return !(this.isUnary() && ((_ref2 = this.operator) === '+' || _ref2 === '-')) || this.first.isComplex();
    };
    Op.prototype.isChainable = function() {
      var _ref2;
      return (_ref2 = this.operator) === '<' || _ref2 === '>' || _ref2 === '>=' || _ref2 === '<=' || _ref2 === '===' || _ref2 === '!==';
    };
    Op.prototype.invert = function() {
      var allInvertable, curr, fst, op, _ref2;
      if (this.isChainable() && this.first.isChainable()) {
        allInvertable = true;
        curr = this;
        while (curr && curr.operator) {
          allInvertable && (allInvertable = curr.operator in INVERSIONS);
          curr = curr.first;
        }
        if (!allInvertable) {
          return new Parens(this).invert();
        }
        curr = this;
        while (curr && curr.operator) {
          curr.invert = !curr.invert;
          curr.operator = INVERSIONS[curr.operator];
          curr = curr.first;
        }
        return this;
      } else if (op = INVERSIONS[this.operator]) {
        this.operator = op;
        if (this.first.unwrap() instanceof Op) {
          this.first.invert();
        }
        return this;
      } else if (this.second) {
        return new Parens(this).invert();
      } else if (this.operator === '!' && (fst = this.first.unwrap()) instanceof Op && ((_ref2 = fst.operator) === '!' || _ref2 === 'in' || _ref2 === 'instanceof')) {
        return fst;
      } else {
        return new Op('!', this);
      }
    };
    Op.prototype.unfoldSoak = function(o) {
      var _ref2;
      return ((_ref2 = this.operator) === '++' || _ref2 === '--' || _ref2 === 'delete') && unfoldSoak(o, this, 'first');
    };
    Op.prototype.compileNode = function(o) {
      var code;
      if (this.isUnary()) {
        return this.compileUnary(o);
      }
      if (this.isChainable() && this.first.isChainable()) {
        return this.compileChain(o);
      }
      if (this.operator === '?') {
        return this.compileExistence(o);
      }
      this.first.front = this.front;
      code = this.first.compile(o, LEVEL_OP) + ' ' + this.operator + ' ' + this.second.compile(o, LEVEL_OP);
      if (o.level <= LEVEL_OP) {
        return code;
      } else {
        return "(" + code + ")";
      }
    };
    Op.prototype.compileChain = function(o) {
      var code, fst, shared, _ref2;
      _ref2 = this.first.second.cache(o), this.first.second = _ref2[0], shared = _ref2[1];
      fst = this.first.compile(o, LEVEL_OP);
      code = "" + fst + " " + (this.invert ? '&&' : '||') + " " + (shared.compile(o)) + " " + this.operator + " " + (this.second.compile(o, LEVEL_OP));
      return "(" + code + ")";
    };
    Op.prototype.compileExistence = function(o) {
      var fst, ref;
      if (this.first.isComplex()) {
        ref = new Literal(o.scope.freeVariable('ref'));
        fst = new Parens(new Assign(ref, this.first));
      } else {
        fst = this.first;
        ref = fst;
      }
      return new If(new Existence(fst), ref, {
        type: 'if'
      }).addElse(this.second).compile(o);
    };
    Op.prototype.compileUnary = function(o) {
      var op, parts;
      parts = [op = this.operator];
      if ((op === 'new' || op === 'typeof' || op === 'delete') || (op === '+' || op === '-') && this.first instanceof Op && this.first.operator === op) {
        parts.push(' ');
      }
      if (op === 'new' && this.first.isStatement(o)) {
        this.first = new Parens(this.first);
      }
      parts.push(this.first.compile(o, LEVEL_OP));
      if (this.flip) {
        parts.reverse();
      }
      return parts.join('');
    };
    Op.prototype.toString = function(idt) {
      return Op.__super__.toString.call(this, idt, this.constructor.name + ' ' + this.operator);
    };
    return Op;
  })();
  exports.In = In = (function() {
    __extends(In, Base);
    function In(object, array) {
      this.object = object;
      this.array = array;
    }
    In.prototype.children = ['object', 'array'];
    In.prototype.invert = NEGATE;
    In.prototype.compileNode = function(o) {
      var hasSplat, obj, _i, _len, _ref2;
      if (this.array instanceof Value && this.array.isArray()) {
        _ref2 = this.array.base.objects;
        for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
          obj = _ref2[_i];
          if (obj instanceof Splat) {
            hasSplat = true;
            break;
          }
        }
        if (!hasSplat) {
          return this.compileOrTest(o);
        }
      }
      return this.compileLoopTest(o);
    };
    In.prototype.compileOrTest = function(o) {
      var cmp, cnj, i, item, ref, sub, tests, _ref2, _ref3;
      _ref2 = this.object.cache(o, LEVEL_OP), sub = _ref2[0], ref = _ref2[1];
      _ref3 = this.negated ? [' !== ', ' && '] : [' === ', ' || '], cmp = _ref3[0], cnj = _ref3[1];
      tests = (function() {
        var _len, _ref4, _results;
        _ref4 = this.array.base.objects;
        _results = [];
        for (i = 0, _len = _ref4.length; i < _len; i++) {
          item = _ref4[i];
          _results.push((i ? ref : sub) + cmp + item.compile(o, LEVEL_OP));
        }
        return _results;
      }).call(this);
      if (tests.length === 0) {
        return 'false';
      }
      tests = tests.join(cnj);
      if (o.level < LEVEL_OP) {
        return tests;
      } else {
        return "(" + tests + ")";
      }
    };
    In.prototype.compileLoopTest = function(o) {
      var code, ref, sub, _ref2;
      _ref2 = this.object.cache(o, LEVEL_LIST), sub = _ref2[0], ref = _ref2[1];
      code = utility('indexOf') + (".call(" + (this.array.compile(o, LEVEL_LIST)) + ", " + ref + ") ") + (this.negated ? '< 0' : '>= 0');
      if (sub === ref) {
        return code;
      }
      code = sub + ', ' + code;
      if (o.level < LEVEL_LIST) {
        return code;
      } else {
        return "(" + code + ")";
      }
    };
    In.prototype.toString = function(idt) {
      return In.__super__.toString.call(this, idt, this.constructor.name + (this.negated ? '!' : ''));
    };
    return In;
  })();
  exports.Try = Try = (function() {
    __extends(Try, Base);
    function Try(attempt, error, recovery, ensure) {
      this.attempt = attempt;
      this.error = error;
      this.recovery = recovery;
      this.ensure = ensure;
    }
    Try.prototype.children = ['attempt', 'recovery', 'ensure'];
    Try.prototype.isStatement = YES;
    Try.prototype.jumps = function(o) {
      var _ref2;
      return this.attempt.jumps(o) || ((_ref2 = this.recovery) != null ? _ref2.jumps(o) : void 0);
    };
    Try.prototype.makeReturn = function() {
      if (this.attempt) {
        this.attempt = this.attempt.makeReturn();
      }
      if (this.recovery) {
        this.recovery = this.recovery.makeReturn();
      }
      return this;
    };
    Try.prototype.compileNode = function(o) {
      var catchPart, errorPart;
      o.indent += TAB;
      errorPart = this.error ? " (" + (this.error.compile(o)) + ") " : ' ';
      catchPart = this.recovery ? " catch" + errorPart + "{\n" + (this.recovery.compile(o, LEVEL_TOP)) + "\n" + this.tab + "}" : !(this.ensure || this.recovery) ? ' catch (_e) {}' : void 0;
      return ("" + this.tab + "try {\n" + (this.attempt.compile(o, LEVEL_TOP)) + "\n" + this.tab + "}" + (catchPart || '')) + (this.ensure ? " finally {\n" + (this.ensure.compile(o, LEVEL_TOP)) + "\n" + this.tab + "}" : '');
    };
    return Try;
  })();
  exports.Throw = Throw = (function() {
    __extends(Throw, Base);
    function Throw(expression) {
      this.expression = expression;
    }
    Throw.prototype.children = ['expression'];
    Throw.prototype.isStatement = YES;
    Throw.prototype.jumps = NO;
    Throw.prototype.makeReturn = THIS;
    Throw.prototype.compileNode = function(o) {
      return this.tab + ("throw " + (this.expression.compile(o)) + ";");
    };
    return Throw;
  })();
  exports.Existence = Existence = (function() {
    __extends(Existence, Base);
    function Existence(expression) {
      this.expression = expression;
    }
    Existence.prototype.children = ['expression'];
    Existence.prototype.invert = NEGATE;
    Existence.prototype.compileNode = function(o) {
      var cmp, cnj, code, _ref2;
      code = this.expression.compile(o, LEVEL_OP);
      code = IDENTIFIER.test(code) && !o.scope.check(code) ? ((_ref2 = this.negated ? ['===', '||'] : ['!==', '&&'], cmp = _ref2[0], cnj = _ref2[1], _ref2), "typeof " + code + " " + cmp + " \"undefined\" " + cnj + " " + code + " " + cmp + " null") : "" + code + " " + (this.negated ? '==' : '!=') + " null";
      if (o.level <= LEVEL_COND) {
        return code;
      } else {
        return "(" + code + ")";
      }
    };
    return Existence;
  })();
  exports.Parens = Parens = (function() {
    __extends(Parens, Base);
    function Parens(body) {
      this.body = body;
    }
    Parens.prototype.children = ['body'];
    Parens.prototype.unwrap = function() {
      return this.body;
    };
    Parens.prototype.isComplex = function() {
      return this.body.isComplex();
    };
    Parens.prototype.makeReturn = function() {
      return this.body.makeReturn();
    };
    Parens.prototype.compileNode = function(o) {
      var bare, code, expr;
      expr = this.body.unwrap();
      if (expr instanceof Value && expr.isAtomic()) {
        expr.front = this.front;
        return expr.compile(o);
      }
      code = expr.compile(o, LEVEL_PAREN);
      bare = o.level < LEVEL_OP && (expr instanceof Op || expr instanceof Call || (expr instanceof For && expr.returns));
      if (bare) {
        return code;
      } else {
        return "(" + code + ")";
      }
    };
    return Parens;
  })();
  exports.For = For = (function() {
    __extends(For, Base);
    function For(body, source) {
      var _ref2;
      this.source = source.source, this.guard = source.guard, this.step = source.step, this.name = source.name, this.index = source.index;
      this.body = Block.wrap([body]);
      this.own = !!source.own;
      this.object = !!source.object;
      if (this.object) {
        _ref2 = [this.index, this.name], this.name = _ref2[0], this.index = _ref2[1];
      }
      if (this.index instanceof Value) {
        throw SyntaxError('index cannot be a pattern matching expression');
      }
      this.range = this.source instanceof Value && this.source.base instanceof Range && !this.source.properties.length;
      this.pattern = this.name instanceof Value;
      if (this.range && this.index) {
        throw SyntaxError('indexes do not apply to range loops');
      }
      if (this.range && this.pattern) {
        throw SyntaxError('cannot pattern match over range loops');
      }
      this.returns = false;
    }
    For.prototype.children = ['body', 'source', 'guard', 'step'];
    For.prototype.isStatement = YES;
    For.prototype.jumps = While.prototype.jumps;
    For.prototype.makeReturn = function() {
      this.returns = true;
      return this;
    };
    For.prototype.compileNode = function(o) {
      var body, defPart, forPart, forVarPart, guardPart, idt1, index, ivar, lastJumps, lvar, name, namePart, ref, resultPart, returnResult, rvar, scope, source, stepPart, stepvar, svar, varPart, _ref2;
      body = Block.wrap([this.body]);
      lastJumps = (_ref2 = last(body.expressions)) != null ? _ref2.jumps() : void 0;
      if (lastJumps && lastJumps instanceof Return) {
        this.returns = false;
      }
      source = this.range ? this.source.base : this.source;
      scope = o.scope;
      name = this.name && this.name.compile(o, LEVEL_LIST);
      index = this.index && this.index.compile(o, LEVEL_LIST);
      if (name && !this.pattern) {
        scope.find(name, {
          immediate: true
        });
      }
      if (index) {
        scope.find(index, {
          immediate: true
        });
      }
      if (this.returns) {
        rvar = scope.freeVariable('results');
      }
      ivar = (this.range ? name : index) || scope.freeVariable('i');
      if (this.step && !this.range) {
        stepvar = scope.freeVariable("step");
      }
      if (this.pattern) {
        name = ivar;
      }
      varPart = '';
      guardPart = '';
      defPart = '';
      idt1 = this.tab + TAB;
      if (this.range) {
        forPart = source.compile(merge(o, {
          index: ivar,
          step: this.step
        }));
      } else {
        svar = this.source.compile(o, LEVEL_LIST);
        if ((name || this.own) && !IDENTIFIER.test(svar)) {
          defPart = "" + this.tab + (ref = scope.freeVariable('ref')) + " = " + svar + ";\n";
          svar = ref;
        }
        if (name && !this.pattern) {
          namePart = "" + name + " = " + svar + "[" + ivar + "]";
        }
        if (!this.object) {
          lvar = scope.freeVariable('len');
          forVarPart = ("" + ivar + " = 0, " + lvar + " = " + svar + ".length") + (this.step ? ", " + stepvar + " = " + (this.step.compile(o, LEVEL_OP)) : '');
          stepPart = this.step ? "" + ivar + " += " + stepvar : "" + ivar + "++";
          forPart = "" + forVarPart + "; " + ivar + " < " + lvar + "; " + stepPart;
        }
      }
      if (this.returns) {
        resultPart = "" + this.tab + rvar + " = [];\n";
        returnResult = "\n" + this.tab + "return " + rvar + ";";
        body = Push.wrap(rvar, body);
      }
      if (this.guard) {
        body = Block.wrap([new If(this.guard, body)]);
      }
      if (this.pattern) {
        body.expressions.unshift(new Assign(this.name, new Literal("" + svar + "[" + ivar + "]")));
      }
      defPart += this.pluckDirectCall(o, body);
      if (namePart) {
        varPart = "\n" + idt1 + namePart + ";";
      }
      if (this.object) {
        forPart = "" + ivar + " in " + svar;
        if (this.own) {
          guardPart = "\n" + idt1 + "if (!" + (utility('hasProp')) + ".call(" + svar + ", " + ivar + ")) continue;";
        }
      }
      body = body.compile(merge(o, {
        indent: idt1
      }), LEVEL_TOP);
      if (body) {
        body = '\n' + body + '\n';
      }
      return "" + defPart + (resultPart || '') + this.tab + "for (" + forPart + ") {" + guardPart + varPart + body + this.tab + "}" + (returnResult || '');
    };
    For.prototype.pluckDirectCall = function(o, body) {
      var base, defs, expr, fn, idx, ref, val, _len, _ref2, _ref3, _ref4, _ref5, _ref6, _ref7;
      defs = '';
      _ref2 = body.expressions;
      for (idx = 0, _len = _ref2.length; idx < _len; idx++) {
        expr = _ref2[idx];
        expr = expr.unwrapAll();
        if (!(expr instanceof Call)) {
          continue;
        }
        val = expr.variable.unwrapAll();
        if (!((val instanceof Code) || (val instanceof Value && ((_ref3 = val.base) != null ? _ref3.unwrapAll() : void 0) instanceof Code && val.properties.length === 1 && ((_ref4 = (_ref5 = val.properties[0].name) != null ? _ref5.value : void 0) === 'call' || _ref4 === 'apply')))) {
          continue;
        }
        fn = ((_ref6 = val.base) != null ? _ref6.unwrapAll() : void 0) || val;
        ref = new Literal(o.scope.freeVariable('fn'));
        base = new Value(ref);
        if (val.base) {
          _ref7 = [base, val], val.base = _ref7[0], base = _ref7[1];
          args.unshift(new Literal('this'));
        }
        body.expressions[idx] = new Call(base, expr.args);
        defs += this.tab + new Assign(ref, fn).compile(o, LEVEL_TOP) + ';\n';
      }
      return defs;
    };
    return For;
  })();
  exports.Switch = Switch = (function() {
    __extends(Switch, Base);
    function Switch(subject, cases, otherwise) {
      this.subject = subject;
      this.cases = cases;
      this.otherwise = otherwise;
    }
    Switch.prototype.children = ['subject', 'cases', 'otherwise'];
    Switch.prototype.isStatement = YES;
    Switch.prototype.jumps = function(o) {
      var block, conds, _i, _len, _ref2, _ref3, _ref4;
      if (o == null) {
        o = {
          block: true
        };
      }
      _ref2 = this.cases;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        _ref3 = _ref2[_i], conds = _ref3[0], block = _ref3[1];
        if (block.jumps(o)) {
          return block;
        }
      }
      return (_ref4 = this.otherwise) != null ? _ref4.jumps(o) : void 0;
    };
    Switch.prototype.makeReturn = function() {
      var pair, _i, _len, _ref2, _ref3;
      _ref2 = this.cases;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        pair = _ref2[_i];
        pair[1].makeReturn();
      }
      if ((_ref3 = this.otherwise) != null) {
        _ref3.makeReturn();
      }
      return this;
    };
    Switch.prototype.compileNode = function(o) {
      var block, body, code, cond, conditions, expr, i, idt1, idt2, _i, _len, _len2, _ref2, _ref3, _ref4, _ref5;
      idt1 = o.indent + TAB;
      idt2 = o.indent = idt1 + TAB;
      code = this.tab + ("switch (" + (((_ref2 = this.subject) != null ? _ref2.compile(o, LEVEL_PAREN) : void 0) || false) + ") {\n");
      _ref3 = this.cases;
      for (i = 0, _len = _ref3.length; i < _len; i++) {
        _ref4 = _ref3[i], conditions = _ref4[0], block = _ref4[1];
        _ref5 = flatten([conditions]);
        for (_i = 0, _len2 = _ref5.length; _i < _len2; _i++) {
          cond = _ref5[_i];
          if (!this.subject) {
            cond = cond.invert();
          }
          code += idt1 + ("case " + (cond.compile(o, LEVEL_PAREN)) + ":\n");
        }
        if (body = block.compile(o, LEVEL_TOP)) {
          code += body + '\n';
        }
        if (i === this.cases.length - 1 && !this.otherwise) {
          break;
        }
        expr = this.lastNonComment(block.expressions);
        if (expr instanceof Return || (expr instanceof Literal && expr.jumps() && expr.value !== 'debugger')) {
          continue;
        }
        code += idt2 + 'break;\n';
      }
      if (this.otherwise && this.otherwise.expressions.length) {
        code += idt1 + ("default:\n" + (this.otherwise.compile(o, LEVEL_TOP)) + "\n");
      }
      return code + this.tab + '}';
    };
    return Switch;
  })();
  exports.If = If = (function() {
    __extends(If, Base);
    function If(condition, body, options) {
      this.body = body;
      if (options == null) {
        options = {};
      }
      this.condition = options.type === 'unless' ? condition.invert() : condition;
      this.elseBody = null;
      this.isChain = false;
      this.soak = options.soak;
    }
    If.prototype.children = ['condition', 'body', 'elseBody'];
    If.prototype.bodyNode = function() {
      var _ref2;
      return (_ref2 = this.body) != null ? _ref2.unwrap() : void 0;
    };
    If.prototype.elseBodyNode = function() {
      var _ref2;
      return (_ref2 = this.elseBody) != null ? _ref2.unwrap() : void 0;
    };
    If.prototype.addElse = function(elseBody) {
      if (this.isChain) {
        this.elseBodyNode().addElse(elseBody);
      } else {
        this.isChain = elseBody instanceof If;
        this.elseBody = this.ensureBlock(elseBody);
      }
      return this;
    };
    If.prototype.isStatement = function(o) {
      var _ref2;
      return (o != null ? o.level : void 0) === LEVEL_TOP || this.bodyNode().isStatement(o) || ((_ref2 = this.elseBodyNode()) != null ? _ref2.isStatement(o) : void 0);
    };
    If.prototype.jumps = function(o) {
      var _ref2;
      return this.body.jumps(o) || ((_ref2 = this.elseBody) != null ? _ref2.jumps(o) : void 0);
    };
    If.prototype.compileNode = function(o) {
      if (this.isStatement(o)) {
        return this.compileStatement(o);
      } else {
        return this.compileExpression(o);
      }
    };
    If.prototype.makeReturn = function() {
      this.body && (this.body = new Block([this.body.makeReturn()]));
      this.elseBody && (this.elseBody = new Block([this.elseBody.makeReturn()]));
      return this;
    };
    If.prototype.ensureBlock = function(node) {
      if (node instanceof Block) {
        return node;
      } else {
        return new Block([node]);
      }
    };
    If.prototype.compileStatement = function(o) {
      var body, child, cond, exeq, ifPart;
      child = del(o, 'chainChild');
      exeq = del(o, 'isExistentialEquals');
      if (exeq) {
        return new If(this.condition.invert(), this.elseBodyNode(), {
          type: 'if'
        }).compile(o);
      }
      cond = this.condition.compile(o, LEVEL_PAREN);
      o.indent += TAB;
      body = this.ensureBlock(this.body).compile(o);
      if (body) {
        body = "\n" + body + "\n" + this.tab;
      }
      ifPart = "if (" + cond + ") {" + body + "}";
      if (!child) {
        ifPart = this.tab + ifPart;
      }
      if (!this.elseBody) {
        return ifPart;
      }
      return ifPart + ' else ' + (this.isChain ? (o.indent = this.tab, o.chainChild = true, this.elseBody.unwrap().compile(o, LEVEL_TOP)) : "{\n" + (this.elseBody.compile(o, LEVEL_TOP)) + "\n" + this.tab + "}");
    };
    If.prototype.compileExpression = function(o) {
      var alt, body, code, cond;
      cond = this.condition.compile(o, LEVEL_COND);
      body = this.bodyNode().compile(o, LEVEL_LIST);
      alt = this.elseBodyNode() ? this.elseBodyNode().compile(o, LEVEL_LIST) : 'void 0';
      code = "" + cond + " ? " + body + " : " + alt;
      if (o.level >= LEVEL_COND) {
        return "(" + code + ")";
      } else {
        return code;
      }
    };
    If.prototype.unfoldSoak = function() {
      return this.soak && this;
    };
    return If;
  })();
  Push = {
    wrap: function(name, exps) {
      if (exps.isEmpty() || last(exps.expressions).jumps()) {
        return exps;
      }
      return exps.push(new Call(new Value(new Literal(name), [new Access(new Literal('push'))]), [exps.pop()]));
    }
  };
  Closure = {
    wrap: function(expressions, statement, noReturn) {
      var args, call, func, mentionsArgs, meth;
      if (expressions.jumps()) {
        return expressions;
      }
      func = new Code([], Block.wrap([expressions]));
      args = [];
      if ((mentionsArgs = expressions.contains(this.literalArgs)) || expressions.contains(this.literalThis)) {
        meth = new Literal(mentionsArgs ? 'apply' : 'call');
        args = [new Literal('this')];
        if (mentionsArgs) {
          args.push(new Literal('arguments'));
        }
        func = new Value(func, [new Access(meth)]);
      }
      func.noReturn = noReturn;
      call = new Call(func, args);
      if (statement) {
        return Block.wrap([call]);
      } else {
        return call;
      }
    },
    literalArgs: function(node) {
      return node instanceof Literal && node.value === 'arguments' && !node.asKey;
    },
    literalThis: function(node) {
      return (node instanceof Literal && node.value === 'this' && !node.asKey) || (node instanceof Code && node.bound);
    }
  };
  unfoldSoak = function(o, parent, name) {
    var ifn;
    if (!(ifn = parent[name].unfoldSoak(o))) {
      return;
    }
    parent[name] = ifn.body;
    ifn.body = new Value(parent);
    return ifn;
  };
  UTILITIES = {
    "extends": 'function(child, parent) {\n  for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; }\n  function ctor() { this.constructor = child; }\n  ctor.prototype = parent.prototype;\n  child.prototype = new ctor;\n  child.__super__ = parent.prototype;\n  return child;\n}',
    bind: 'function(fn, me){ return function(){ return fn.apply(me, arguments); }; }',
    indexOf: 'Array.prototype.indexOf || function(item) {\n  for (var i = 0, l = this.length; i < l; i++) {\n    if (this[i] === item) return i;\n  }\n  return -1;\n}',
    hasProp: 'Object.prototype.hasOwnProperty',
    slice: 'Array.prototype.slice'
  };
  LEVEL_TOP = 1;
  LEVEL_PAREN = 2;
  LEVEL_LIST = 3;
  LEVEL_COND = 4;
  LEVEL_OP = 5;
  LEVEL_ACCESS = 6;
  TAB = '  ';
  IDENTIFIER = /^[$A-Za-z_\x7f-\uffff][$\w\x7f-\uffff]*$/;
  SIMPLENUM = /^[+-]?\d+$/;
  METHOD_DEF = /^(?:([$A-Za-z_][$\w\x7f-\uffff]*)\.prototype\.)?([$A-Za-z_][$\w\x7f-\uffff]*)$/;
  IS_STRING = /^['"]/;
  utility = function(name) {
    var ref;
    ref = "__" + name;
    Scope.root.assign(ref, UTILITIES[name]);
    return ref;
  };
  multident = function(code, tab) {
    return code.replace(/\n/g, '$&' + tab);
  };
});
/**
 * Copyright (c) 2011 Jeremy Ashkenas
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

define('ace/mode/coffee/scope', ['require', 'exports', 'module' , 'ace/mode/coffee/helpers'], function(require, exports, module) {
  var Scope, extend, last, _ref;
  _ref = require('./helpers'), extend = _ref.extend, last = _ref.last;
  exports.Scope = Scope = (function() {
    Scope.root = null;
    function Scope(parent, expressions, method) {
      this.parent = parent;
      this.expressions = expressions;
      this.method = method;
      this.variables = [
        {
          name: 'arguments',
          type: 'arguments'
        }
      ];
      this.positions = {};
      if (!this.parent) {
        Scope.root = this;
      }
    }
    Scope.prototype.add = function(name, type, immediate) {
      var pos;
      if (this.shared && !immediate) {
        return this.parent.add(name, type, immediate);
      }
      if (typeof (pos = this.positions[name]) === 'number') {
        return this.variables[pos].type = type;
      } else {
        return this.positions[name] = this.variables.push({
          name: name,
          type: type
        }) - 1;
      }
    };
    Scope.prototype.find = function(name, options) {
      if (this.check(name, options)) {
        return true;
      }
      this.add(name, 'var');
      return false;
    };
    Scope.prototype.parameter = function(name) {
      if (this.shared && this.parent.check(name, true)) {
        return;
      }
      return this.add(name, 'param');
    };
    Scope.prototype.check = function(name, immediate) {
      var found, _ref2;
      found = !!this.type(name);
      if (found || immediate) {
        return found;
      }
      return !!((_ref2 = this.parent) != null ? _ref2.check(name) : void 0);
    };
    Scope.prototype.temporary = function(name, index) {
      if (name.length > 1) {
        return '_' + name + (index > 1 ? index : '');
      } else {
        return '_' + (index + parseInt(name, 36)).toString(36).replace(/\d/g, 'a');
      }
    };
    Scope.prototype.type = function(name) {
      var v, _i, _len, _ref2;
      _ref2 = this.variables;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        v = _ref2[_i];
        if (v.name === name) {
          return v.type;
        }
      }
      return null;
    };
    Scope.prototype.freeVariable = function(type) {
      var index, temp;
      index = 0;
      while (this.check((temp = this.temporary(type, index)))) {
        index++;
      }
      this.add(temp, 'var', true);
      return temp;
    };
    Scope.prototype.assign = function(name, value) {
      this.add(name, {
        value: value,
        assigned: true
      });
      return this.hasAssignments = true;
    };
    Scope.prototype.hasDeclarations = function() {
      return !!this.declaredVariables().length;
    };
    Scope.prototype.declaredVariables = function() {
      var realVars, tempVars, v, _i, _len, _ref2;
      realVars = [];
      tempVars = [];
      _ref2 = this.variables;
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        v = _ref2[_i];
        if (v.type === 'var') {
          (v.name.charAt(0) === '_' ? tempVars : realVars).push(v.name);
        }
      }
      return realVars.sort().concat(tempVars.sort());
    };
    Scope.prototype.assignedVariables = function() {
      var v, _i, _len, _ref2, _results;
      _ref2 = this.variables;
      _results = [];
      for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
        v = _ref2[_i];
        if (v.type.assigned) {
          _results.push("" + v.name + " = " + v.type.value);
        }
      }
      return _results;
    };
    return Scope;
  })();
});
