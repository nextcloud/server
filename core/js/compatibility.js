/**
 * implement Object.create for browsers without native support
 */
if (typeof Object.create !== 'function') {
	Object.create = function (o) {
		function F() {}
		F.prototype = o;
		return new F();
	};
}

/**
 * implement Object.keys for browsers without native support
 */
if (typeof Object.keys !== 'function') {
	Object.keys = function(o) {
		if (o !== Object(o)) {
			throw new TypeError('Object.keys called on a non-object');
		}
		var k=[],p;
		for (p in o) {
			if (Object.prototype.hasOwnProperty.call(o,p)) {
				k.push(p);
			}
		}
		return k;
	};
}

/**
 * implement Array.filter for browsers without native support
 */
if (!Array.prototype.filter) {
	Array.prototype.filter = function(fun /*, thisp*/) {
		var len = this.length >>> 0;
		if (typeof fun !== "function"){
			throw new TypeError();
		}

		var res = [];
		var thisp = arguments[1];
		for (var i = 0; i < len; i++) {
			if (i in this) {
				var val = this[i]; // in case fun mutates this
				if (fun.call(thisp, val, i, this)) {
					res.push(val);
				}
			}
		}
		return res;
	};
}

/**
 * implement Array.indexOf for browsers without native support
 */
if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/)
	{
		var len = this.length;

		var from = Number(arguments[1]) || 0;
		from = (from < 0) ? Math.ceil(from) : Math.floor(from);
		if (from < 0){
			from += len;
		}

		for (; from < len; from++)
		{
			if (from in this && this[from] === elt){
				return from;
			}
		}
		return -1;
	};
}

/**
 * implement Array.map for browsers without native support
 */
if (!Array.prototype.map){
	Array.prototype.map = function(fun /*, thisp */){
		"use strict";

		if (this === void 0 || this === null){
			throw new TypeError();
		}

		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== "function"){
			throw new TypeError();
		}

		var res = new Array(len);
		var thisp = arguments[1];
		for (var i = 0; i < len; i++){
			if (i in t){
				res[i] = fun.call(thisp, t[i], i, t);
			}
		}

		return res;
	};
}

//https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Function/bind
if (!Function.prototype.bind) {
	Function.prototype.bind = function (oThis) {
		if (typeof this !== "function") {
			// closest thing possible to the ECMAScript 5 internal IsCallable function
			throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
		}

		var aArgs = Array.prototype.slice.call(arguments, 1),
			fToBind = this,
			fNOP = function () {},
			fBound = function () {
				return fToBind.apply(this instanceof fNOP && oThis
					? this
					: oThis,
					aArgs.concat(Array.prototype.slice.call(arguments)));
			};

		fNOP.prototype = this.prototype;
		fBound.prototype = new fNOP();

		return fBound;
	};
}

//https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/String/Trim
if(!String.prototype.trim) {
	String.prototype.trim = function () {
		return this.replace(/^\s+|\s+$/g,'');
	};
}

// Older Firefoxes doesn't support outerHTML
// From http://stackoverflow.com/questions/1700870/how-do-i-do-outerhtml-in-firefox#answer-3819589
function outerHTML(node){
	// In newer browsers use the internal property otherwise build a wrapper.
	return node.outerHTML || (
	function(n){
		var div = document.createElement('div'), h;
		div.appendChild( n.cloneNode(true) );
		h = div.innerHTML;
		div = null;
		return h;
	})(node);
}

// devicePixelRatio for IE10
window.devicePixelRatio = window.devicePixelRatio ||
	window.screen.deviceXDPI / window.screen.logicalXDPI || 1;
