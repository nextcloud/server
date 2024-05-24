(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_files_sharing_src_views_SharingTab_vue-data_image_svg_xml_3csvg_20xmlns_27http_www_w3_or-753dbe"],{

/***/ "./node_modules/@chenfengyuan/vue-qrcode/dist/vue-qrcode.js":
/*!******************************************************************!*\
  !*** ./node_modules/@chenfengyuan/vue-qrcode/dist/vue-qrcode.js ***!
  \******************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/*!
 * vue-qrcode v1.0.2
 * https://fengyuanchen.github.io/vue-qrcode
 *
 * Copyright 2018-present Chen Fengyuan
 * Released under the MIT license
 *
 * Date: 2020-01-18T06:04:33.222Z
 */

(function (global, factory) {
	 true ? module.exports = factory() :
	0;
}(this, (function () { 'use strict';

	function commonjsRequire () {
		throw new Error('Dynamic requires are not currently supported by rollup-plugin-commonjs');
	}

	function createCommonjsModule(fn, module) {
		return module = { exports: {} }, fn(module, module.exports), module.exports;
	}

	var qrcode = createCommonjsModule(function (module, exports) {
	(function(f){{module.exports=f();}})(function(){return (function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof commonjsRequire&&commonjsRequire;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t);}return n[i].exports}for(var u="function"==typeof commonjsRequire&&commonjsRequire,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
	// can-promise has a crash in some versions of react native that dont have
	// standard global objects
	// https://github.com/soldair/node-qrcode/issues/157

	module.exports = function () {
	  return typeof Promise === 'function' && Promise.prototype && Promise.prototype.then
	};

	},{}],2:[function(require,module,exports){
	/**
	 * Alignment pattern are fixed reference pattern in defined positions
	 * in a matrix symbology, which enables the decode software to re-synchronise
	 * the coordinate mapping of the image modules in the event of moderate amounts
	 * of distortion of the image.
	 *
	 * Alignment patterns are present only in QR Code symbols of version 2 or larger
	 * and their number depends on the symbol version.
	 */

	var getSymbolSize = require('./utils').getSymbolSize;

	/**
	 * Calculate the row/column coordinates of the center module of each alignment pattern
	 * for the specified QR Code version.
	 *
	 * The alignment patterns are positioned symmetrically on either side of the diagonal
	 * running from the top left corner of the symbol to the bottom right corner.
	 *
	 * Since positions are simmetrical only half of the coordinates are returned.
	 * Each item of the array will represent in turn the x and y coordinate.
	 * @see {@link getPositions}
	 *
	 * @param  {Number} version QR Code version
	 * @return {Array}          Array of coordinate
	 */
	exports.getRowColCoords = function getRowColCoords (version) {
	  if (version === 1) return []

	  var posCount = Math.floor(version / 7) + 2;
	  var size = getSymbolSize(version);
	  var intervals = size === 145 ? 26 : Math.ceil((size - 13) / (2 * posCount - 2)) * 2;
	  var positions = [size - 7]; // Last coord is always (size - 7)

	  for (var i = 1; i < posCount - 1; i++) {
	    positions[i] = positions[i - 1] - intervals;
	  }

	  positions.push(6); // First coord is always 6

	  return positions.reverse()
	};

	/**
	 * Returns an array containing the positions of each alignment pattern.
	 * Each array's element represent the center point of the pattern as (x, y) coordinates
	 *
	 * Coordinates are calculated expanding the row/column coordinates returned by {@link getRowColCoords}
	 * and filtering out the items that overlaps with finder pattern
	 *
	 * @example
	 * For a Version 7 symbol {@link getRowColCoords} returns values 6, 22 and 38.
	 * The alignment patterns, therefore, are to be centered on (row, column)
	 * positions (6,22), (22,6), (22,22), (22,38), (38,22), (38,38).
	 * Note that the coordinates (6,6), (6,38), (38,6) are occupied by finder patterns
	 * and are not therefore used for alignment patterns.
	 *
	 * var pos = getPositions(7)
	 * // [[6,22], [22,6], [22,22], [22,38], [38,22], [38,38]]
	 *
	 * @param  {Number} version QR Code version
	 * @return {Array}          Array of coordinates
	 */
	exports.getPositions = function getPositions (version) {
	  var coords = [];
	  var pos = exports.getRowColCoords(version);
	  var posLength = pos.length;

	  for (var i = 0; i < posLength; i++) {
	    for (var j = 0; j < posLength; j++) {
	      // Skip if position is occupied by finder patterns
	      if ((i === 0 && j === 0) ||             // top-left
	          (i === 0 && j === posLength - 1) || // bottom-left
	          (i === posLength - 1 && j === 0)) { // top-right
	        continue
	      }

	      coords.push([pos[i], pos[j]]);
	    }
	  }

	  return coords
	};

	},{"./utils":21}],3:[function(require,module,exports){
	var Mode = require('./mode');

	/**
	 * Array of characters available in alphanumeric mode
	 *
	 * As per QR Code specification, to each character
	 * is assigned a value from 0 to 44 which in this case coincides
	 * with the array index
	 *
	 * @type {Array}
	 */
	var ALPHA_NUM_CHARS = [
	  '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
	  'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
	  'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
	  ' ', '$', '%', '*', '+', '-', '.', '/', ':'
	];

	function AlphanumericData (data) {
	  this.mode = Mode.ALPHANUMERIC;
	  this.data = data;
	}

	AlphanumericData.getBitsLength = function getBitsLength (length) {
	  return 11 * Math.floor(length / 2) + 6 * (length % 2)
	};

	AlphanumericData.prototype.getLength = function getLength () {
	  return this.data.length
	};

	AlphanumericData.prototype.getBitsLength = function getBitsLength () {
	  return AlphanumericData.getBitsLength(this.data.length)
	};

	AlphanumericData.prototype.write = function write (bitBuffer) {
	  var i;

	  // Input data characters are divided into groups of two characters
	  // and encoded as 11-bit binary codes.
	  for (i = 0; i + 2 <= this.data.length; i += 2) {
	    // The character value of the first character is multiplied by 45
	    var value = ALPHA_NUM_CHARS.indexOf(this.data[i]) * 45;

	    // The character value of the second digit is added to the product
	    value += ALPHA_NUM_CHARS.indexOf(this.data[i + 1]);

	    // The sum is then stored as 11-bit binary number
	    bitBuffer.put(value, 11);
	  }

	  // If the number of input data characters is not a multiple of two,
	  // the character value of the final character is encoded as a 6-bit binary number.
	  if (this.data.length % 2) {
	    bitBuffer.put(ALPHA_NUM_CHARS.indexOf(this.data[i]), 6);
	  }
	};

	module.exports = AlphanumericData;

	},{"./mode":14}],4:[function(require,module,exports){
	function BitBuffer () {
	  this.buffer = [];
	  this.length = 0;
	}

	BitBuffer.prototype = {

	  get: function (index) {
	    var bufIndex = Math.floor(index / 8);
	    return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) === 1
	  },

	  put: function (num, length) {
	    for (var i = 0; i < length; i++) {
	      this.putBit(((num >>> (length - i - 1)) & 1) === 1);
	    }
	  },

	  getLengthInBits: function () {
	    return this.length
	  },

	  putBit: function (bit) {
	    var bufIndex = Math.floor(this.length / 8);
	    if (this.buffer.length <= bufIndex) {
	      this.buffer.push(0);
	    }

	    if (bit) {
	      this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
	    }

	    this.length++;
	  }
	};

	module.exports = BitBuffer;

	},{}],5:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');

	/**
	 * Helper class to handle QR Code symbol modules
	 *
	 * @param {Number} size Symbol size
	 */
	function BitMatrix (size) {
	  if (!size || size < 1) {
	    throw new Error('BitMatrix size must be defined and greater than 0')
	  }

	  this.size = size;
	  this.data = BufferUtil.alloc(size * size);
	  this.reservedBit = BufferUtil.alloc(size * size);
	}

	/**
	 * Set bit value at specified location
	 * If reserved flag is set, this bit will be ignored during masking process
	 *
	 * @param {Number}  row
	 * @param {Number}  col
	 * @param {Boolean} value
	 * @param {Boolean} reserved
	 */
	BitMatrix.prototype.set = function (row, col, value, reserved) {
	  var index = row * this.size + col;
	  this.data[index] = value;
	  if (reserved) this.reservedBit[index] = true;
	};

	/**
	 * Returns bit value at specified location
	 *
	 * @param  {Number}  row
	 * @param  {Number}  col
	 * @return {Boolean}
	 */
	BitMatrix.prototype.get = function (row, col) {
	  return this.data[row * this.size + col]
	};

	/**
	 * Applies xor operator at specified location
	 * (used during masking process)
	 *
	 * @param {Number}  row
	 * @param {Number}  col
	 * @param {Boolean} value
	 */
	BitMatrix.prototype.xor = function (row, col, value) {
	  this.data[row * this.size + col] ^= value;
	};

	/**
	 * Check if bit at specified location is reserved
	 *
	 * @param {Number}   row
	 * @param {Number}   col
	 * @return {Boolean}
	 */
	BitMatrix.prototype.isReserved = function (row, col) {
	  return this.reservedBit[row * this.size + col]
	};

	module.exports = BitMatrix;

	},{"../utils/buffer":28}],6:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');
	var Mode = require('./mode');

	function ByteData (data) {
	  this.mode = Mode.BYTE;
	  this.data = BufferUtil.from(data);
	}

	ByteData.getBitsLength = function getBitsLength (length) {
	  return length * 8
	};

	ByteData.prototype.getLength = function getLength () {
	  return this.data.length
	};

	ByteData.prototype.getBitsLength = function getBitsLength () {
	  return ByteData.getBitsLength(this.data.length)
	};

	ByteData.prototype.write = function (bitBuffer) {
	  for (var i = 0, l = this.data.length; i < l; i++) {
	    bitBuffer.put(this.data[i], 8);
	  }
	};

	module.exports = ByteData;

	},{"../utils/buffer":28,"./mode":14}],7:[function(require,module,exports){
	var ECLevel = require('./error-correction-level');

	var EC_BLOCKS_TABLE = [
	// L  M  Q  H
	  1, 1, 1, 1,
	  1, 1, 1, 1,
	  1, 1, 2, 2,
	  1, 2, 2, 4,
	  1, 2, 4, 4,
	  2, 4, 4, 4,
	  2, 4, 6, 5,
	  2, 4, 6, 6,
	  2, 5, 8, 8,
	  4, 5, 8, 8,
	  4, 5, 8, 11,
	  4, 8, 10, 11,
	  4, 9, 12, 16,
	  4, 9, 16, 16,
	  6, 10, 12, 18,
	  6, 10, 17, 16,
	  6, 11, 16, 19,
	  6, 13, 18, 21,
	  7, 14, 21, 25,
	  8, 16, 20, 25,
	  8, 17, 23, 25,
	  9, 17, 23, 34,
	  9, 18, 25, 30,
	  10, 20, 27, 32,
	  12, 21, 29, 35,
	  12, 23, 34, 37,
	  12, 25, 34, 40,
	  13, 26, 35, 42,
	  14, 28, 38, 45,
	  15, 29, 40, 48,
	  16, 31, 43, 51,
	  17, 33, 45, 54,
	  18, 35, 48, 57,
	  19, 37, 51, 60,
	  19, 38, 53, 63,
	  20, 40, 56, 66,
	  21, 43, 59, 70,
	  22, 45, 62, 74,
	  24, 47, 65, 77,
	  25, 49, 68, 81
	];

	var EC_CODEWORDS_TABLE = [
	// L  M  Q  H
	  7, 10, 13, 17,
	  10, 16, 22, 28,
	  15, 26, 36, 44,
	  20, 36, 52, 64,
	  26, 48, 72, 88,
	  36, 64, 96, 112,
	  40, 72, 108, 130,
	  48, 88, 132, 156,
	  60, 110, 160, 192,
	  72, 130, 192, 224,
	  80, 150, 224, 264,
	  96, 176, 260, 308,
	  104, 198, 288, 352,
	  120, 216, 320, 384,
	  132, 240, 360, 432,
	  144, 280, 408, 480,
	  168, 308, 448, 532,
	  180, 338, 504, 588,
	  196, 364, 546, 650,
	  224, 416, 600, 700,
	  224, 442, 644, 750,
	  252, 476, 690, 816,
	  270, 504, 750, 900,
	  300, 560, 810, 960,
	  312, 588, 870, 1050,
	  336, 644, 952, 1110,
	  360, 700, 1020, 1200,
	  390, 728, 1050, 1260,
	  420, 784, 1140, 1350,
	  450, 812, 1200, 1440,
	  480, 868, 1290, 1530,
	  510, 924, 1350, 1620,
	  540, 980, 1440, 1710,
	  570, 1036, 1530, 1800,
	  570, 1064, 1590, 1890,
	  600, 1120, 1680, 1980,
	  630, 1204, 1770, 2100,
	  660, 1260, 1860, 2220,
	  720, 1316, 1950, 2310,
	  750, 1372, 2040, 2430
	];

	/**
	 * Returns the number of error correction block that the QR Code should contain
	 * for the specified version and error correction level.
	 *
	 * @param  {Number} version              QR Code version
	 * @param  {Number} errorCorrectionLevel Error correction level
	 * @return {Number}                      Number of error correction blocks
	 */
	exports.getBlocksCount = function getBlocksCount (version, errorCorrectionLevel) {
	  switch (errorCorrectionLevel) {
	    case ECLevel.L:
	      return EC_BLOCKS_TABLE[(version - 1) * 4 + 0]
	    case ECLevel.M:
	      return EC_BLOCKS_TABLE[(version - 1) * 4 + 1]
	    case ECLevel.Q:
	      return EC_BLOCKS_TABLE[(version - 1) * 4 + 2]
	    case ECLevel.H:
	      return EC_BLOCKS_TABLE[(version - 1) * 4 + 3]
	    default:
	      return undefined
	  }
	};

	/**
	 * Returns the number of error correction codewords to use for the specified
	 * version and error correction level.
	 *
	 * @param  {Number} version              QR Code version
	 * @param  {Number} errorCorrectionLevel Error correction level
	 * @return {Number}                      Number of error correction codewords
	 */
	exports.getTotalCodewordsCount = function getTotalCodewordsCount (version, errorCorrectionLevel) {
	  switch (errorCorrectionLevel) {
	    case ECLevel.L:
	      return EC_CODEWORDS_TABLE[(version - 1) * 4 + 0]
	    case ECLevel.M:
	      return EC_CODEWORDS_TABLE[(version - 1) * 4 + 1]
	    case ECLevel.Q:
	      return EC_CODEWORDS_TABLE[(version - 1) * 4 + 2]
	    case ECLevel.H:
	      return EC_CODEWORDS_TABLE[(version - 1) * 4 + 3]
	    default:
	      return undefined
	  }
	};

	},{"./error-correction-level":8}],8:[function(require,module,exports){
	exports.L = { bit: 1 };
	exports.M = { bit: 0 };
	exports.Q = { bit: 3 };
	exports.H = { bit: 2 };

	function fromString (string) {
	  if (typeof string !== 'string') {
	    throw new Error('Param is not a string')
	  }

	  var lcStr = string.toLowerCase();

	  switch (lcStr) {
	    case 'l':
	    case 'low':
	      return exports.L

	    case 'm':
	    case 'medium':
	      return exports.M

	    case 'q':
	    case 'quartile':
	      return exports.Q

	    case 'h':
	    case 'high':
	      return exports.H

	    default:
	      throw new Error('Unknown EC Level: ' + string)
	  }
	}

	exports.isValid = function isValid (level) {
	  return level && typeof level.bit !== 'undefined' &&
	    level.bit >= 0 && level.bit < 4
	};

	exports.from = function from (value, defaultValue) {
	  if (exports.isValid(value)) {
	    return value
	  }

	  try {
	    return fromString(value)
	  } catch (e) {
	    return defaultValue
	  }
	};

	},{}],9:[function(require,module,exports){
	var getSymbolSize = require('./utils').getSymbolSize;
	var FINDER_PATTERN_SIZE = 7;

	/**
	 * Returns an array containing the positions of each finder pattern.
	 * Each array's element represent the top-left point of the pattern as (x, y) coordinates
	 *
	 * @param  {Number} version QR Code version
	 * @return {Array}          Array of coordinates
	 */
	exports.getPositions = function getPositions (version) {
	  var size = getSymbolSize(version);

	  return [
	    // top-left
	    [0, 0],
	    // top-right
	    [size - FINDER_PATTERN_SIZE, 0],
	    // bottom-left
	    [0, size - FINDER_PATTERN_SIZE]
	  ]
	};

	},{"./utils":21}],10:[function(require,module,exports){
	var Utils = require('./utils');

	var G15 = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0);
	var G15_MASK = (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1);
	var G15_BCH = Utils.getBCHDigit(G15);

	/**
	 * Returns format information with relative error correction bits
	 *
	 * The format information is a 15-bit sequence containing 5 data bits,
	 * with 10 error correction bits calculated using the (15, 5) BCH code.
	 *
	 * @param  {Number} errorCorrectionLevel Error correction level
	 * @param  {Number} mask                 Mask pattern
	 * @return {Number}                      Encoded format information bits
	 */
	exports.getEncodedBits = function getEncodedBits (errorCorrectionLevel, mask) {
	  var data = ((errorCorrectionLevel.bit << 3) | mask);
	  var d = data << 10;

	  while (Utils.getBCHDigit(d) - G15_BCH >= 0) {
	    d ^= (G15 << (Utils.getBCHDigit(d) - G15_BCH));
	  }

	  // xor final data with mask pattern in order to ensure that
	  // no combination of Error Correction Level and data mask pattern
	  // will result in an all-zero data string
	  return ((data << 10) | d) ^ G15_MASK
	};

	},{"./utils":21}],11:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');

	var EXP_TABLE = BufferUtil.alloc(512);
	var LOG_TABLE = BufferUtil.alloc(256)
	/**
	 * Precompute the log and anti-log tables for faster computation later
	 *
	 * For each possible value in the galois field 2^8, we will pre-compute
	 * the logarithm and anti-logarithm (exponential) of this value
	 *
	 * ref {@link https://en.wikiversity.org/wiki/Reed%E2%80%93Solomon_codes_for_coders#Introduction_to_mathematical_fields}
	 */
	;(function initTables () {
	  var x = 1;
	  for (var i = 0; i < 255; i++) {
	    EXP_TABLE[i] = x;
	    LOG_TABLE[x] = i;

	    x <<= 1; // multiply by 2

	    // The QR code specification says to use byte-wise modulo 100011101 arithmetic.
	    // This means that when a number is 256 or larger, it should be XORed with 0x11D.
	    if (x & 0x100) { // similar to x >= 256, but a lot faster (because 0x100 == 256)
	      x ^= 0x11D;
	    }
	  }

	  // Optimization: double the size of the anti-log table so that we don't need to mod 255 to
	  // stay inside the bounds (because we will mainly use this table for the multiplication of
	  // two GF numbers, no more).
	  // @see {@link mul}
	  for (i = 255; i < 512; i++) {
	    EXP_TABLE[i] = EXP_TABLE[i - 255];
	  }
	}());

	/**
	 * Returns log value of n inside Galois Field
	 *
	 * @param  {Number} n
	 * @return {Number}
	 */
	exports.log = function log (n) {
	  if (n < 1) throw new Error('log(' + n + ')')
	  return LOG_TABLE[n]
	};

	/**
	 * Returns anti-log value of n inside Galois Field
	 *
	 * @param  {Number} n
	 * @return {Number}
	 */
	exports.exp = function exp (n) {
	  return EXP_TABLE[n]
	};

	/**
	 * Multiplies two number inside Galois Field
	 *
	 * @param  {Number} x
	 * @param  {Number} y
	 * @return {Number}
	 */
	exports.mul = function mul (x, y) {
	  if (x === 0 || y === 0) return 0

	  // should be EXP_TABLE[(LOG_TABLE[x] + LOG_TABLE[y]) % 255] if EXP_TABLE wasn't oversized
	  // @see {@link initTables}
	  return EXP_TABLE[LOG_TABLE[x] + LOG_TABLE[y]]
	};

	},{"../utils/buffer":28}],12:[function(require,module,exports){
	var Mode = require('./mode');
	var Utils = require('./utils');

	function KanjiData (data) {
	  this.mode = Mode.KANJI;
	  this.data = data;
	}

	KanjiData.getBitsLength = function getBitsLength (length) {
	  return length * 13
	};

	KanjiData.prototype.getLength = function getLength () {
	  return this.data.length
	};

	KanjiData.prototype.getBitsLength = function getBitsLength () {
	  return KanjiData.getBitsLength(this.data.length)
	};

	KanjiData.prototype.write = function (bitBuffer) {
	  var i;

	  // In the Shift JIS system, Kanji characters are represented by a two byte combination.
	  // These byte values are shifted from the JIS X 0208 values.
	  // JIS X 0208 gives details of the shift coded representation.
	  for (i = 0; i < this.data.length; i++) {
	    var value = Utils.toSJIS(this.data[i]);

	    // For characters with Shift JIS values from 0x8140 to 0x9FFC:
	    if (value >= 0x8140 && value <= 0x9FFC) {
	      // Subtract 0x8140 from Shift JIS value
	      value -= 0x8140;

	    // For characters with Shift JIS values from 0xE040 to 0xEBBF
	    } else if (value >= 0xE040 && value <= 0xEBBF) {
	      // Subtract 0xC140 from Shift JIS value
	      value -= 0xC140;
	    } else {
	      throw new Error(
	        'Invalid SJIS character: ' + this.data[i] + '\n' +
	        'Make sure your charset is UTF-8')
	    }

	    // Multiply most significant byte of result by 0xC0
	    // and add least significant byte to product
	    value = (((value >>> 8) & 0xff) * 0xC0) + (value & 0xff);

	    // Convert result to a 13-bit binary string
	    bitBuffer.put(value, 13);
	  }
	};

	module.exports = KanjiData;

	},{"./mode":14,"./utils":21}],13:[function(require,module,exports){
	/**
	 * Data mask pattern reference
	 * @type {Object}
	 */
	exports.Patterns = {
	  PATTERN000: 0,
	  PATTERN001: 1,
	  PATTERN010: 2,
	  PATTERN011: 3,
	  PATTERN100: 4,
	  PATTERN101: 5,
	  PATTERN110: 6,
	  PATTERN111: 7
	};

	/**
	 * Weighted penalty scores for the undesirable features
	 * @type {Object}
	 */
	var PenaltyScores = {
	  N1: 3,
	  N2: 3,
	  N3: 40,
	  N4: 10
	};

	/**
	 * Check if mask pattern value is valid
	 *
	 * @param  {Number}  mask    Mask pattern
	 * @return {Boolean}         true if valid, false otherwise
	 */
	exports.isValid = function isValid (mask) {
	  return mask != null && mask !== '' && !isNaN(mask) && mask >= 0 && mask <= 7
	};

	/**
	 * Returns mask pattern from a value.
	 * If value is not valid, returns undefined
	 *
	 * @param  {Number|String} value        Mask pattern value
	 * @return {Number}                     Valid mask pattern or undefined
	 */
	exports.from = function from (value) {
	  return exports.isValid(value) ? parseInt(value, 10) : undefined
	};

	/**
	* Find adjacent modules in row/column with the same color
	* and assign a penalty value.
	*
	* Points: N1 + i
	* i is the amount by which the number of adjacent modules of the same color exceeds 5
	*/
	exports.getPenaltyN1 = function getPenaltyN1 (data) {
	  var size = data.size;
	  var points = 0;
	  var sameCountCol = 0;
	  var sameCountRow = 0;
	  var lastCol = null;
	  var lastRow = null;

	  for (var row = 0; row < size; row++) {
	    sameCountCol = sameCountRow = 0;
	    lastCol = lastRow = null;

	    for (var col = 0; col < size; col++) {
	      var module = data.get(row, col);
	      if (module === lastCol) {
	        sameCountCol++;
	      } else {
	        if (sameCountCol >= 5) points += PenaltyScores.N1 + (sameCountCol - 5);
	        lastCol = module;
	        sameCountCol = 1;
	      }

	      module = data.get(col, row);
	      if (module === lastRow) {
	        sameCountRow++;
	      } else {
	        if (sameCountRow >= 5) points += PenaltyScores.N1 + (sameCountRow - 5);
	        lastRow = module;
	        sameCountRow = 1;
	      }
	    }

	    if (sameCountCol >= 5) points += PenaltyScores.N1 + (sameCountCol - 5);
	    if (sameCountRow >= 5) points += PenaltyScores.N1 + (sameCountRow - 5);
	  }

	  return points
	};

	/**
	 * Find 2x2 blocks with the same color and assign a penalty value
	 *
	 * Points: N2 * (m - 1) * (n - 1)
	 */
	exports.getPenaltyN2 = function getPenaltyN2 (data) {
	  var size = data.size;
	  var points = 0;

	  for (var row = 0; row < size - 1; row++) {
	    for (var col = 0; col < size - 1; col++) {
	      var last = data.get(row, col) +
	        data.get(row, col + 1) +
	        data.get(row + 1, col) +
	        data.get(row + 1, col + 1);

	      if (last === 4 || last === 0) points++;
	    }
	  }

	  return points * PenaltyScores.N2
	};

	/**
	 * Find 1:1:3:1:1 ratio (dark:light:dark:light:dark) pattern in row/column,
	 * preceded or followed by light area 4 modules wide
	 *
	 * Points: N3 * number of pattern found
	 */
	exports.getPenaltyN3 = function getPenaltyN3 (data) {
	  var size = data.size;
	  var points = 0;
	  var bitsCol = 0;
	  var bitsRow = 0;

	  for (var row = 0; row < size; row++) {
	    bitsCol = bitsRow = 0;
	    for (var col = 0; col < size; col++) {
	      bitsCol = ((bitsCol << 1) & 0x7FF) | data.get(row, col);
	      if (col >= 10 && (bitsCol === 0x5D0 || bitsCol === 0x05D)) points++;

	      bitsRow = ((bitsRow << 1) & 0x7FF) | data.get(col, row);
	      if (col >= 10 && (bitsRow === 0x5D0 || bitsRow === 0x05D)) points++;
	    }
	  }

	  return points * PenaltyScores.N3
	};

	/**
	 * Calculate proportion of dark modules in entire symbol
	 *
	 * Points: N4 * k
	 *
	 * k is the rating of the deviation of the proportion of dark modules
	 * in the symbol from 50% in steps of 5%
	 */
	exports.getPenaltyN4 = function getPenaltyN4 (data) {
	  var darkCount = 0;
	  var modulesCount = data.data.length;

	  for (var i = 0; i < modulesCount; i++) darkCount += data.data[i];

	  var k = Math.abs(Math.ceil((darkCount * 100 / modulesCount) / 5) - 10);

	  return k * PenaltyScores.N4
	};

	/**
	 * Return mask value at given position
	 *
	 * @param  {Number} maskPattern Pattern reference value
	 * @param  {Number} i           Row
	 * @param  {Number} j           Column
	 * @return {Boolean}            Mask value
	 */
	function getMaskAt (maskPattern, i, j) {
	  switch (maskPattern) {
	    case exports.Patterns.PATTERN000: return (i + j) % 2 === 0
	    case exports.Patterns.PATTERN001: return i % 2 === 0
	    case exports.Patterns.PATTERN010: return j % 3 === 0
	    case exports.Patterns.PATTERN011: return (i + j) % 3 === 0
	    case exports.Patterns.PATTERN100: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 === 0
	    case exports.Patterns.PATTERN101: return (i * j) % 2 + (i * j) % 3 === 0
	    case exports.Patterns.PATTERN110: return ((i * j) % 2 + (i * j) % 3) % 2 === 0
	    case exports.Patterns.PATTERN111: return ((i * j) % 3 + (i + j) % 2) % 2 === 0

	    default: throw new Error('bad maskPattern:' + maskPattern)
	  }
	}

	/**
	 * Apply a mask pattern to a BitMatrix
	 *
	 * @param  {Number}    pattern Pattern reference number
	 * @param  {BitMatrix} data    BitMatrix data
	 */
	exports.applyMask = function applyMask (pattern, data) {
	  var size = data.size;

	  for (var col = 0; col < size; col++) {
	    for (var row = 0; row < size; row++) {
	      if (data.isReserved(row, col)) continue
	      data.xor(row, col, getMaskAt(pattern, row, col));
	    }
	  }
	};

	/**
	 * Returns the best mask pattern for data
	 *
	 * @param  {BitMatrix} data
	 * @return {Number} Mask pattern reference number
	 */
	exports.getBestMask = function getBestMask (data, setupFormatFunc) {
	  var numPatterns = Object.keys(exports.Patterns).length;
	  var bestPattern = 0;
	  var lowerPenalty = Infinity;

	  for (var p = 0; p < numPatterns; p++) {
	    setupFormatFunc(p);
	    exports.applyMask(p, data);

	    // Calculate penalty
	    var penalty =
	      exports.getPenaltyN1(data) +
	      exports.getPenaltyN2(data) +
	      exports.getPenaltyN3(data) +
	      exports.getPenaltyN4(data);

	    // Undo previously applied mask
	    exports.applyMask(p, data);

	    if (penalty < lowerPenalty) {
	      lowerPenalty = penalty;
	      bestPattern = p;
	    }
	  }

	  return bestPattern
	};

	},{}],14:[function(require,module,exports){
	var VersionCheck = require('./version-check');
	var Regex = require('./regex');

	/**
	 * Numeric mode encodes data from the decimal digit set (0 - 9)
	 * (byte values 30HEX to 39HEX).
	 * Normally, 3 data characters are represented by 10 bits.
	 *
	 * @type {Object}
	 */
	exports.NUMERIC = {
	  id: 'Numeric',
	  bit: 1 << 0,
	  ccBits: [10, 12, 14]
	};

	/**
	 * Alphanumeric mode encodes data from a set of 45 characters,
	 * i.e. 10 numeric digits (0 - 9),
	 *      26 alphabetic characters (A - Z),
	 *   and 9 symbols (SP, $, %, *, +, -, ., /, :).
	 * Normally, two input characters are represented by 11 bits.
	 *
	 * @type {Object}
	 */
	exports.ALPHANUMERIC = {
	  id: 'Alphanumeric',
	  bit: 1 << 1,
	  ccBits: [9, 11, 13]
	};

	/**
	 * In byte mode, data is encoded at 8 bits per character.
	 *
	 * @type {Object}
	 */
	exports.BYTE = {
	  id: 'Byte',
	  bit: 1 << 2,
	  ccBits: [8, 16, 16]
	};

	/**
	 * The Kanji mode efficiently encodes Kanji characters in accordance with
	 * the Shift JIS system based on JIS X 0208.
	 * The Shift JIS values are shifted from the JIS X 0208 values.
	 * JIS X 0208 gives details of the shift coded representation.
	 * Each two-byte character value is compacted to a 13-bit binary codeword.
	 *
	 * @type {Object}
	 */
	exports.KANJI = {
	  id: 'Kanji',
	  bit: 1 << 3,
	  ccBits: [8, 10, 12]
	};

	/**
	 * Mixed mode will contain a sequences of data in a combination of any of
	 * the modes described above
	 *
	 * @type {Object}
	 */
	exports.MIXED = {
	  bit: -1
	};

	/**
	 * Returns the number of bits needed to store the data length
	 * according to QR Code specifications.
	 *
	 * @param  {Mode}   mode    Data mode
	 * @param  {Number} version QR Code version
	 * @return {Number}         Number of bits
	 */
	exports.getCharCountIndicator = function getCharCountIndicator (mode, version) {
	  if (!mode.ccBits) throw new Error('Invalid mode: ' + mode)

	  if (!VersionCheck.isValid(version)) {
	    throw new Error('Invalid version: ' + version)
	  }

	  if (version >= 1 && version < 10) return mode.ccBits[0]
	  else if (version < 27) return mode.ccBits[1]
	  return mode.ccBits[2]
	};

	/**
	 * Returns the most efficient mode to store the specified data
	 *
	 * @param  {String} dataStr Input data string
	 * @return {Mode}           Best mode
	 */
	exports.getBestModeForData = function getBestModeForData (dataStr) {
	  if (Regex.testNumeric(dataStr)) return exports.NUMERIC
	  else if (Regex.testAlphanumeric(dataStr)) return exports.ALPHANUMERIC
	  else if (Regex.testKanji(dataStr)) return exports.KANJI
	  else return exports.BYTE
	};

	/**
	 * Return mode name as string
	 *
	 * @param {Mode} mode Mode object
	 * @returns {String}  Mode name
	 */
	exports.toString = function toString (mode) {
	  if (mode && mode.id) return mode.id
	  throw new Error('Invalid mode')
	};

	/**
	 * Check if input param is a valid mode object
	 *
	 * @param   {Mode}    mode Mode object
	 * @returns {Boolean} True if valid mode, false otherwise
	 */
	exports.isValid = function isValid (mode) {
	  return mode && mode.bit && mode.ccBits
	};

	/**
	 * Get mode object from its name
	 *
	 * @param   {String} string Mode name
	 * @returns {Mode}          Mode object
	 */
	function fromString (string) {
	  if (typeof string !== 'string') {
	    throw new Error('Param is not a string')
	  }

	  var lcStr = string.toLowerCase();

	  switch (lcStr) {
	    case 'numeric':
	      return exports.NUMERIC
	    case 'alphanumeric':
	      return exports.ALPHANUMERIC
	    case 'kanji':
	      return exports.KANJI
	    case 'byte':
	      return exports.BYTE
	    default:
	      throw new Error('Unknown mode: ' + string)
	  }
	}

	/**
	 * Returns mode from a value.
	 * If value is not a valid mode, returns defaultValue
	 *
	 * @param  {Mode|String} value        Encoding mode
	 * @param  {Mode}        defaultValue Fallback value
	 * @return {Mode}                     Encoding mode
	 */
	exports.from = function from (value, defaultValue) {
	  if (exports.isValid(value)) {
	    return value
	  }

	  try {
	    return fromString(value)
	  } catch (e) {
	    return defaultValue
	  }
	};

	},{"./regex":19,"./version-check":22}],15:[function(require,module,exports){
	var Mode = require('./mode');

	function NumericData (data) {
	  this.mode = Mode.NUMERIC;
	  this.data = data.toString();
	}

	NumericData.getBitsLength = function getBitsLength (length) {
	  return 10 * Math.floor(length / 3) + ((length % 3) ? ((length % 3) * 3 + 1) : 0)
	};

	NumericData.prototype.getLength = function getLength () {
	  return this.data.length
	};

	NumericData.prototype.getBitsLength = function getBitsLength () {
	  return NumericData.getBitsLength(this.data.length)
	};

	NumericData.prototype.write = function write (bitBuffer) {
	  var i, group, value;

	  // The input data string is divided into groups of three digits,
	  // and each group is converted to its 10-bit binary equivalent.
	  for (i = 0; i + 3 <= this.data.length; i += 3) {
	    group = this.data.substr(i, 3);
	    value = parseInt(group, 10);

	    bitBuffer.put(value, 10);
	  }

	  // If the number of input digits is not an exact multiple of three,
	  // the final one or two digits are converted to 4 or 7 bits respectively.
	  var remainingNum = this.data.length - i;
	  if (remainingNum > 0) {
	    group = this.data.substr(i);
	    value = parseInt(group, 10);

	    bitBuffer.put(value, remainingNum * 3 + 1);
	  }
	};

	module.exports = NumericData;

	},{"./mode":14}],16:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');
	var GF = require('./galois-field');

	/**
	 * Multiplies two polynomials inside Galois Field
	 *
	 * @param  {Buffer} p1 Polynomial
	 * @param  {Buffer} p2 Polynomial
	 * @return {Buffer}    Product of p1 and p2
	 */
	exports.mul = function mul (p1, p2) {
	  var coeff = BufferUtil.alloc(p1.length + p2.length - 1);

	  for (var i = 0; i < p1.length; i++) {
	    for (var j = 0; j < p2.length; j++) {
	      coeff[i + j] ^= GF.mul(p1[i], p2[j]);
	    }
	  }

	  return coeff
	};

	/**
	 * Calculate the remainder of polynomials division
	 *
	 * @param  {Buffer} divident Polynomial
	 * @param  {Buffer} divisor  Polynomial
	 * @return {Buffer}          Remainder
	 */
	exports.mod = function mod (divident, divisor) {
	  var result = BufferUtil.from(divident);

	  while ((result.length - divisor.length) >= 0) {
	    var coeff = result[0];

	    for (var i = 0; i < divisor.length; i++) {
	      result[i] ^= GF.mul(divisor[i], coeff);
	    }

	    // remove all zeros from buffer head
	    var offset = 0;
	    while (offset < result.length && result[offset] === 0) offset++;
	    result = result.slice(offset);
	  }

	  return result
	};

	/**
	 * Generate an irreducible generator polynomial of specified degree
	 * (used by Reed-Solomon encoder)
	 *
	 * @param  {Number} degree Degree of the generator polynomial
	 * @return {Buffer}        Buffer containing polynomial coefficients
	 */
	exports.generateECPolynomial = function generateECPolynomial (degree) {
	  var poly = BufferUtil.from([1]);
	  for (var i = 0; i < degree; i++) {
	    poly = exports.mul(poly, [1, GF.exp(i)]);
	  }

	  return poly
	};

	},{"../utils/buffer":28,"./galois-field":11}],17:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');
	var Utils = require('./utils');
	var ECLevel = require('./error-correction-level');
	var BitBuffer = require('./bit-buffer');
	var BitMatrix = require('./bit-matrix');
	var AlignmentPattern = require('./alignment-pattern');
	var FinderPattern = require('./finder-pattern');
	var MaskPattern = require('./mask-pattern');
	var ECCode = require('./error-correction-code');
	var ReedSolomonEncoder = require('./reed-solomon-encoder');
	var Version = require('./version');
	var FormatInfo = require('./format-info');
	var Mode = require('./mode');
	var Segments = require('./segments');
	var isArray = require('isarray');

	/**
	 * QRCode for JavaScript
	 *
	 * modified by Ryan Day for nodejs support
	 * Copyright (c) 2011 Ryan Day
	 *
	 * Licensed under the MIT license:
	 *   http://www.opensource.org/licenses/mit-license.php
	 *
	//---------------------------------------------------------------------
	// QRCode for JavaScript
	//
	// Copyright (c) 2009 Kazuhiko Arase
	//
	// URL: http://www.d-project.com/
	//
	// Licensed under the MIT license:
	//   http://www.opensource.org/licenses/mit-license.php
	//
	// The word "QR Code" is registered trademark of
	// DENSO WAVE INCORPORATED
	//   http://www.denso-wave.com/qrcode/faqpatent-e.html
	//
	//---------------------------------------------------------------------
	*/

	/**
	 * Add finder patterns bits to matrix
	 *
	 * @param  {BitMatrix} matrix  Modules matrix
	 * @param  {Number}    version QR Code version
	 */
	function setupFinderPattern (matrix, version) {
	  var size = matrix.size;
	  var pos = FinderPattern.getPositions(version);

	  for (var i = 0; i < pos.length; i++) {
	    var row = pos[i][0];
	    var col = pos[i][1];

	    for (var r = -1; r <= 7; r++) {
	      if (row + r <= -1 || size <= row + r) continue

	      for (var c = -1; c <= 7; c++) {
	        if (col + c <= -1 || size <= col + c) continue

	        if ((r >= 0 && r <= 6 && (c === 0 || c === 6)) ||
	          (c >= 0 && c <= 6 && (r === 0 || r === 6)) ||
	          (r >= 2 && r <= 4 && c >= 2 && c <= 4)) {
	          matrix.set(row + r, col + c, true, true);
	        } else {
	          matrix.set(row + r, col + c, false, true);
	        }
	      }
	    }
	  }
	}

	/**
	 * Add timing pattern bits to matrix
	 *
	 * Note: this function must be called before {@link setupAlignmentPattern}
	 *
	 * @param  {BitMatrix} matrix Modules matrix
	 */
	function setupTimingPattern (matrix) {
	  var size = matrix.size;

	  for (var r = 8; r < size - 8; r++) {
	    var value = r % 2 === 0;
	    matrix.set(r, 6, value, true);
	    matrix.set(6, r, value, true);
	  }
	}

	/**
	 * Add alignment patterns bits to matrix
	 *
	 * Note: this function must be called after {@link setupTimingPattern}
	 *
	 * @param  {BitMatrix} matrix  Modules matrix
	 * @param  {Number}    version QR Code version
	 */
	function setupAlignmentPattern (matrix, version) {
	  var pos = AlignmentPattern.getPositions(version);

	  for (var i = 0; i < pos.length; i++) {
	    var row = pos[i][0];
	    var col = pos[i][1];

	    for (var r = -2; r <= 2; r++) {
	      for (var c = -2; c <= 2; c++) {
	        if (r === -2 || r === 2 || c === -2 || c === 2 ||
	          (r === 0 && c === 0)) {
	          matrix.set(row + r, col + c, true, true);
	        } else {
	          matrix.set(row + r, col + c, false, true);
	        }
	      }
	    }
	  }
	}

	/**
	 * Add version info bits to matrix
	 *
	 * @param  {BitMatrix} matrix  Modules matrix
	 * @param  {Number}    version QR Code version
	 */
	function setupVersionInfo (matrix, version) {
	  var size = matrix.size;
	  var bits = Version.getEncodedBits(version);
	  var row, col, mod;

	  for (var i = 0; i < 18; i++) {
	    row = Math.floor(i / 3);
	    col = i % 3 + size - 8 - 3;
	    mod = ((bits >> i) & 1) === 1;

	    matrix.set(row, col, mod, true);
	    matrix.set(col, row, mod, true);
	  }
	}

	/**
	 * Add format info bits to matrix
	 *
	 * @param  {BitMatrix} matrix               Modules matrix
	 * @param  {ErrorCorrectionLevel}    errorCorrectionLevel Error correction level
	 * @param  {Number}    maskPattern          Mask pattern reference value
	 */
	function setupFormatInfo (matrix, errorCorrectionLevel, maskPattern) {
	  var size = matrix.size;
	  var bits = FormatInfo.getEncodedBits(errorCorrectionLevel, maskPattern);
	  var i, mod;

	  for (i = 0; i < 15; i++) {
	    mod = ((bits >> i) & 1) === 1;

	    // vertical
	    if (i < 6) {
	      matrix.set(i, 8, mod, true);
	    } else if (i < 8) {
	      matrix.set(i + 1, 8, mod, true);
	    } else {
	      matrix.set(size - 15 + i, 8, mod, true);
	    }

	    // horizontal
	    if (i < 8) {
	      matrix.set(8, size - i - 1, mod, true);
	    } else if (i < 9) {
	      matrix.set(8, 15 - i - 1 + 1, mod, true);
	    } else {
	      matrix.set(8, 15 - i - 1, mod, true);
	    }
	  }

	  // fixed module
	  matrix.set(size - 8, 8, 1, true);
	}

	/**
	 * Add encoded data bits to matrix
	 *
	 * @param  {BitMatrix} matrix Modules matrix
	 * @param  {Buffer}    data   Data codewords
	 */
	function setupData (matrix, data) {
	  var size = matrix.size;
	  var inc = -1;
	  var row = size - 1;
	  var bitIndex = 7;
	  var byteIndex = 0;

	  for (var col = size - 1; col > 0; col -= 2) {
	    if (col === 6) col--;

	    while (true) {
	      for (var c = 0; c < 2; c++) {
	        if (!matrix.isReserved(row, col - c)) {
	          var dark = false;

	          if (byteIndex < data.length) {
	            dark = (((data[byteIndex] >>> bitIndex) & 1) === 1);
	          }

	          matrix.set(row, col - c, dark);
	          bitIndex--;

	          if (bitIndex === -1) {
	            byteIndex++;
	            bitIndex = 7;
	          }
	        }
	      }

	      row += inc;

	      if (row < 0 || size <= row) {
	        row -= inc;
	        inc = -inc;
	        break
	      }
	    }
	  }
	}

	/**
	 * Create encoded codewords from data input
	 *
	 * @param  {Number}   version              QR Code version
	 * @param  {ErrorCorrectionLevel}   errorCorrectionLevel Error correction level
	 * @param  {ByteData} data                 Data input
	 * @return {Buffer}                        Buffer containing encoded codewords
	 */
	function createData (version, errorCorrectionLevel, segments) {
	  // Prepare data buffer
	  var buffer = new BitBuffer();

	  segments.forEach(function (data) {
	    // prefix data with mode indicator (4 bits)
	    buffer.put(data.mode.bit, 4);

	    // Prefix data with character count indicator.
	    // The character count indicator is a string of bits that represents the
	    // number of characters that are being encoded.
	    // The character count indicator must be placed after the mode indicator
	    // and must be a certain number of bits long, depending on the QR version
	    // and data mode
	    // @see {@link Mode.getCharCountIndicator}.
	    buffer.put(data.getLength(), Mode.getCharCountIndicator(data.mode, version));

	    // add binary data sequence to buffer
	    data.write(buffer);
	  });

	  // Calculate required number of bits
	  var totalCodewords = Utils.getSymbolTotalCodewords(version);
	  var ecTotalCodewords = ECCode.getTotalCodewordsCount(version, errorCorrectionLevel);
	  var dataTotalCodewordsBits = (totalCodewords - ecTotalCodewords) * 8;

	  // Add a terminator.
	  // If the bit string is shorter than the total number of required bits,
	  // a terminator of up to four 0s must be added to the right side of the string.
	  // If the bit string is more than four bits shorter than the required number of bits,
	  // add four 0s to the end.
	  if (buffer.getLengthInBits() + 4 <= dataTotalCodewordsBits) {
	    buffer.put(0, 4);
	  }

	  // If the bit string is fewer than four bits shorter, add only the number of 0s that
	  // are needed to reach the required number of bits.

	  // After adding the terminator, if the number of bits in the string is not a multiple of 8,
	  // pad the string on the right with 0s to make the string's length a multiple of 8.
	  while (buffer.getLengthInBits() % 8 !== 0) {
	    buffer.putBit(0);
	  }

	  // Add pad bytes if the string is still shorter than the total number of required bits.
	  // Extend the buffer to fill the data capacity of the symbol corresponding to
	  // the Version and Error Correction Level by adding the Pad Codewords 11101100 (0xEC)
	  // and 00010001 (0x11) alternately.
	  var remainingByte = (dataTotalCodewordsBits - buffer.getLengthInBits()) / 8;
	  for (var i = 0; i < remainingByte; i++) {
	    buffer.put(i % 2 ? 0x11 : 0xEC, 8);
	  }

	  return createCodewords(buffer, version, errorCorrectionLevel)
	}

	/**
	 * Encode input data with Reed-Solomon and return codewords with
	 * relative error correction bits
	 *
	 * @param  {BitBuffer} bitBuffer            Data to encode
	 * @param  {Number}    version              QR Code version
	 * @param  {ErrorCorrectionLevel} errorCorrectionLevel Error correction level
	 * @return {Buffer}                         Buffer containing encoded codewords
	 */
	function createCodewords (bitBuffer, version, errorCorrectionLevel) {
	  // Total codewords for this QR code version (Data + Error correction)
	  var totalCodewords = Utils.getSymbolTotalCodewords(version);

	  // Total number of error correction codewords
	  var ecTotalCodewords = ECCode.getTotalCodewordsCount(version, errorCorrectionLevel);

	  // Total number of data codewords
	  var dataTotalCodewords = totalCodewords - ecTotalCodewords;

	  // Total number of blocks
	  var ecTotalBlocks = ECCode.getBlocksCount(version, errorCorrectionLevel);

	  // Calculate how many blocks each group should contain
	  var blocksInGroup2 = totalCodewords % ecTotalBlocks;
	  var blocksInGroup1 = ecTotalBlocks - blocksInGroup2;

	  var totalCodewordsInGroup1 = Math.floor(totalCodewords / ecTotalBlocks);

	  var dataCodewordsInGroup1 = Math.floor(dataTotalCodewords / ecTotalBlocks);
	  var dataCodewordsInGroup2 = dataCodewordsInGroup1 + 1;

	  // Number of EC codewords is the same for both groups
	  var ecCount = totalCodewordsInGroup1 - dataCodewordsInGroup1;

	  // Initialize a Reed-Solomon encoder with a generator polynomial of degree ecCount
	  var rs = new ReedSolomonEncoder(ecCount);

	  var offset = 0;
	  var dcData = new Array(ecTotalBlocks);
	  var ecData = new Array(ecTotalBlocks);
	  var maxDataSize = 0;
	  var buffer = BufferUtil.from(bitBuffer.buffer);

	  // Divide the buffer into the required number of blocks
	  for (var b = 0; b < ecTotalBlocks; b++) {
	    var dataSize = b < blocksInGroup1 ? dataCodewordsInGroup1 : dataCodewordsInGroup2;

	    // extract a block of data from buffer
	    dcData[b] = buffer.slice(offset, offset + dataSize);

	    // Calculate EC codewords for this data block
	    ecData[b] = rs.encode(dcData[b]);

	    offset += dataSize;
	    maxDataSize = Math.max(maxDataSize, dataSize);
	  }

	  // Create final data
	  // Interleave the data and error correction codewords from each block
	  var data = BufferUtil.alloc(totalCodewords);
	  var index = 0;
	  var i, r;

	  // Add data codewords
	  for (i = 0; i < maxDataSize; i++) {
	    for (r = 0; r < ecTotalBlocks; r++) {
	      if (i < dcData[r].length) {
	        data[index++] = dcData[r][i];
	      }
	    }
	  }

	  // Apped EC codewords
	  for (i = 0; i < ecCount; i++) {
	    for (r = 0; r < ecTotalBlocks; r++) {
	      data[index++] = ecData[r][i];
	    }
	  }

	  return data
	}

	/**
	 * Build QR Code symbol
	 *
	 * @param  {String} data                 Input string
	 * @param  {Number} version              QR Code version
	 * @param  {ErrorCorretionLevel} errorCorrectionLevel Error level
	 * @param  {MaskPattern} maskPattern     Mask pattern
	 * @return {Object}                      Object containing symbol data
	 */
	function createSymbol (data, version, errorCorrectionLevel, maskPattern) {
	  var segments;

	  if (isArray(data)) {
	    segments = Segments.fromArray(data);
	  } else if (typeof data === 'string') {
	    var estimatedVersion = version;

	    if (!estimatedVersion) {
	      var rawSegments = Segments.rawSplit(data);

	      // Estimate best version that can contain raw splitted segments
	      estimatedVersion = Version.getBestVersionForData(rawSegments,
	        errorCorrectionLevel);
	    }

	    // Build optimized segments
	    // If estimated version is undefined, try with the highest version
	    segments = Segments.fromString(data, estimatedVersion || 40);
	  } else {
	    throw new Error('Invalid data')
	  }

	  // Get the min version that can contain data
	  var bestVersion = Version.getBestVersionForData(segments,
	      errorCorrectionLevel);

	  // If no version is found, data cannot be stored
	  if (!bestVersion) {
	    throw new Error('The amount of data is too big to be stored in a QR Code')
	  }

	  // If not specified, use min version as default
	  if (!version) {
	    version = bestVersion;

	  // Check if the specified version can contain the data
	  } else if (version < bestVersion) {
	    throw new Error('\n' +
	      'The chosen QR Code version cannot contain this amount of data.\n' +
	      'Minimum version required to store current data is: ' + bestVersion + '.\n'
	    )
	  }

	  var dataBits = createData(version, errorCorrectionLevel, segments);

	  // Allocate matrix buffer
	  var moduleCount = Utils.getSymbolSize(version);
	  var modules = new BitMatrix(moduleCount);

	  // Add function modules
	  setupFinderPattern(modules, version);
	  setupTimingPattern(modules);
	  setupAlignmentPattern(modules, version);

	  // Add temporary dummy bits for format info just to set them as reserved.
	  // This is needed to prevent these bits from being masked by {@link MaskPattern.applyMask}
	  // since the masking operation must be performed only on the encoding region.
	  // These blocks will be replaced with correct values later in code.
	  setupFormatInfo(modules, errorCorrectionLevel, 0);

	  if (version >= 7) {
	    setupVersionInfo(modules, version);
	  }

	  // Add data codewords
	  setupData(modules, dataBits);

	  if (isNaN(maskPattern)) {
	    // Find best mask pattern
	    maskPattern = MaskPattern.getBestMask(modules,
	      setupFormatInfo.bind(null, modules, errorCorrectionLevel));
	  }

	  // Apply mask pattern
	  MaskPattern.applyMask(maskPattern, modules);

	  // Replace format info bits with correct values
	  setupFormatInfo(modules, errorCorrectionLevel, maskPattern);

	  return {
	    modules: modules,
	    version: version,
	    errorCorrectionLevel: errorCorrectionLevel,
	    maskPattern: maskPattern,
	    segments: segments
	  }
	}

	/**
	 * QR Code
	 *
	 * @param {String | Array} data                 Input data
	 * @param {Object} options                      Optional configurations
	 * @param {Number} options.version              QR Code version
	 * @param {String} options.errorCorrectionLevel Error correction level
	 * @param {Function} options.toSJISFunc         Helper func to convert utf8 to sjis
	 */
	exports.create = function create (data, options) {
	  if (typeof data === 'undefined' || data === '') {
	    throw new Error('No input text')
	  }

	  var errorCorrectionLevel = ECLevel.M;
	  var version;
	  var mask;

	  if (typeof options !== 'undefined') {
	    // Use higher error correction level as default
	    errorCorrectionLevel = ECLevel.from(options.errorCorrectionLevel, ECLevel.M);
	    version = Version.from(options.version);
	    mask = MaskPattern.from(options.maskPattern);

	    if (options.toSJISFunc) {
	      Utils.setToSJISFunction(options.toSJISFunc);
	    }
	  }

	  return createSymbol(data, version, errorCorrectionLevel, mask)
	};

	},{"../utils/buffer":28,"./alignment-pattern":2,"./bit-buffer":4,"./bit-matrix":5,"./error-correction-code":7,"./error-correction-level":8,"./finder-pattern":9,"./format-info":10,"./mask-pattern":13,"./mode":14,"./reed-solomon-encoder":18,"./segments":20,"./utils":21,"./version":23,"isarray":33}],18:[function(require,module,exports){
	var BufferUtil = require('../utils/buffer');
	var Polynomial = require('./polynomial');
	var Buffer = require('buffer').Buffer;

	function ReedSolomonEncoder (degree) {
	  this.genPoly = undefined;
	  this.degree = degree;

	  if (this.degree) this.initialize(this.degree);
	}

	/**
	 * Initialize the encoder.
	 * The input param should correspond to the number of error correction codewords.
	 *
	 * @param  {Number} degree
	 */
	ReedSolomonEncoder.prototype.initialize = function initialize (degree) {
	  // create an irreducible generator polynomial
	  this.degree = degree;
	  this.genPoly = Polynomial.generateECPolynomial(this.degree);
	};

	/**
	 * Encodes a chunk of data
	 *
	 * @param  {Buffer} data Buffer containing input data
	 * @return {Buffer}      Buffer containing encoded data
	 */
	ReedSolomonEncoder.prototype.encode = function encode (data) {
	  if (!this.genPoly) {
	    throw new Error('Encoder not initialized')
	  }

	  // Calculate EC for this data block
	  // extends data size to data+genPoly size
	  var pad = BufferUtil.alloc(this.degree);
	  var paddedData = Buffer.concat([data, pad], data.length + this.degree);

	  // The error correction codewords are the remainder after dividing the data codewords
	  // by a generator polynomial
	  var remainder = Polynomial.mod(paddedData, this.genPoly);

	  // return EC data blocks (last n byte, where n is the degree of genPoly)
	  // If coefficients number in remainder are less than genPoly degree,
	  // pad with 0s to the left to reach the needed number of coefficients
	  var start = this.degree - remainder.length;
	  if (start > 0) {
	    var buff = BufferUtil.alloc(this.degree);
	    remainder.copy(buff, start);

	    return buff
	  }

	  return remainder
	};

	module.exports = ReedSolomonEncoder;

	},{"../utils/buffer":28,"./polynomial":16,"buffer":30}],19:[function(require,module,exports){
	var numeric = '[0-9]+';
	var alphanumeric = '[A-Z $%*+\\-./:]+';
	var kanji = '(?:[u3000-u303F]|[u3040-u309F]|[u30A0-u30FF]|' +
	  '[uFF00-uFFEF]|[u4E00-u9FAF]|[u2605-u2606]|[u2190-u2195]|u203B|' +
	  '[u2010u2015u2018u2019u2025u2026u201Cu201Du2225u2260]|' +
	  '[u0391-u0451]|[u00A7u00A8u00B1u00B4u00D7u00F7])+';
	kanji = kanji.replace(/u/g, '\\u');

	var byte = '(?:(?![A-Z0-9 $%*+\\-./:]|' + kanji + ')(?:.|[\r\n]))+';

	exports.KANJI = new RegExp(kanji, 'g');
	exports.BYTE_KANJI = new RegExp('[^A-Z0-9 $%*+\\-./:]+', 'g');
	exports.BYTE = new RegExp(byte, 'g');
	exports.NUMERIC = new RegExp(numeric, 'g');
	exports.ALPHANUMERIC = new RegExp(alphanumeric, 'g');

	var TEST_KANJI = new RegExp('^' + kanji + '$');
	var TEST_NUMERIC = new RegExp('^' + numeric + '$');
	var TEST_ALPHANUMERIC = new RegExp('^[A-Z0-9 $%*+\\-./:]+$');

	exports.testKanji = function testKanji (str) {
	  return TEST_KANJI.test(str)
	};

	exports.testNumeric = function testNumeric (str) {
	  return TEST_NUMERIC.test(str)
	};

	exports.testAlphanumeric = function testAlphanumeric (str) {
	  return TEST_ALPHANUMERIC.test(str)
	};

	},{}],20:[function(require,module,exports){
	var Mode = require('./mode');
	var NumericData = require('./numeric-data');
	var AlphanumericData = require('./alphanumeric-data');
	var ByteData = require('./byte-data');
	var KanjiData = require('./kanji-data');
	var Regex = require('./regex');
	var Utils = require('./utils');
	var dijkstra = require('dijkstrajs');

	/**
	 * Returns UTF8 byte length
	 *
	 * @param  {String} str Input string
	 * @return {Number}     Number of byte
	 */
	function getStringByteLength (str) {
	  return unescape(encodeURIComponent(str)).length
	}

	/**
	 * Get a list of segments of the specified mode
	 * from a string
	 *
	 * @param  {Mode}   mode Segment mode
	 * @param  {String} str  String to process
	 * @return {Array}       Array of object with segments data
	 */
	function getSegments (regex, mode, str) {
	  var segments = [];
	  var result;

	  while ((result = regex.exec(str)) !== null) {
	    segments.push({
	      data: result[0],
	      index: result.index,
	      mode: mode,
	      length: result[0].length
	    });
	  }

	  return segments
	}

	/**
	 * Extracts a series of segments with the appropriate
	 * modes from a string
	 *
	 * @param  {String} dataStr Input string
	 * @return {Array}          Array of object with segments data
	 */
	function getSegmentsFromString (dataStr) {
	  var numSegs = getSegments(Regex.NUMERIC, Mode.NUMERIC, dataStr);
	  var alphaNumSegs = getSegments(Regex.ALPHANUMERIC, Mode.ALPHANUMERIC, dataStr);
	  var byteSegs;
	  var kanjiSegs;

	  if (Utils.isKanjiModeEnabled()) {
	    byteSegs = getSegments(Regex.BYTE, Mode.BYTE, dataStr);
	    kanjiSegs = getSegments(Regex.KANJI, Mode.KANJI, dataStr);
	  } else {
	    byteSegs = getSegments(Regex.BYTE_KANJI, Mode.BYTE, dataStr);
	    kanjiSegs = [];
	  }

	  var segs = numSegs.concat(alphaNumSegs, byteSegs, kanjiSegs);

	  return segs
	    .sort(function (s1, s2) {
	      return s1.index - s2.index
	    })
	    .map(function (obj) {
	      return {
	        data: obj.data,
	        mode: obj.mode,
	        length: obj.length
	      }
	    })
	}

	/**
	 * Returns how many bits are needed to encode a string of
	 * specified length with the specified mode
	 *
	 * @param  {Number} length String length
	 * @param  {Mode} mode     Segment mode
	 * @return {Number}        Bit length
	 */
	function getSegmentBitsLength (length, mode) {
	  switch (mode) {
	    case Mode.NUMERIC:
	      return NumericData.getBitsLength(length)
	    case Mode.ALPHANUMERIC:
	      return AlphanumericData.getBitsLength(length)
	    case Mode.KANJI:
	      return KanjiData.getBitsLength(length)
	    case Mode.BYTE:
	      return ByteData.getBitsLength(length)
	  }
	}

	/**
	 * Merges adjacent segments which have the same mode
	 *
	 * @param  {Array} segs Array of object with segments data
	 * @return {Array}      Array of object with segments data
	 */
	function mergeSegments (segs) {
	  return segs.reduce(function (acc, curr) {
	    var prevSeg = acc.length - 1 >= 0 ? acc[acc.length - 1] : null;
	    if (prevSeg && prevSeg.mode === curr.mode) {
	      acc[acc.length - 1].data += curr.data;
	      return acc
	    }

	    acc.push(curr);
	    return acc
	  }, [])
	}

	/**
	 * Generates a list of all possible nodes combination which
	 * will be used to build a segments graph.
	 *
	 * Nodes are divided by groups. Each group will contain a list of all the modes
	 * in which is possible to encode the given text.
	 *
	 * For example the text '12345' can be encoded as Numeric, Alphanumeric or Byte.
	 * The group for '12345' will contain then 3 objects, one for each
	 * possible encoding mode.
	 *
	 * Each node represents a possible segment.
	 *
	 * @param  {Array} segs Array of object with segments data
	 * @return {Array}      Array of object with segments data
	 */
	function buildNodes (segs) {
	  var nodes = [];
	  for (var i = 0; i < segs.length; i++) {
	    var seg = segs[i];

	    switch (seg.mode) {
	      case Mode.NUMERIC:
	        nodes.push([seg,
	          { data: seg.data, mode: Mode.ALPHANUMERIC, length: seg.length },
	          { data: seg.data, mode: Mode.BYTE, length: seg.length }
	        ]);
	        break
	      case Mode.ALPHANUMERIC:
	        nodes.push([seg,
	          { data: seg.data, mode: Mode.BYTE, length: seg.length }
	        ]);
	        break
	      case Mode.KANJI:
	        nodes.push([seg,
	          { data: seg.data, mode: Mode.BYTE, length: getStringByteLength(seg.data) }
	        ]);
	        break
	      case Mode.BYTE:
	        nodes.push([
	          { data: seg.data, mode: Mode.BYTE, length: getStringByteLength(seg.data) }
	        ]);
	    }
	  }

	  return nodes
	}

	/**
	 * Builds a graph from a list of nodes.
	 * All segments in each node group will be connected with all the segments of
	 * the next group and so on.
	 *
	 * At each connection will be assigned a weight depending on the
	 * segment's byte length.
	 *
	 * @param  {Array} nodes    Array of object with segments data
	 * @param  {Number} version QR Code version
	 * @return {Object}         Graph of all possible segments
	 */
	function buildGraph (nodes, version) {
	  var table = {};
	  var graph = {'start': {}};
	  var prevNodeIds = ['start'];

	  for (var i = 0; i < nodes.length; i++) {
	    var nodeGroup = nodes[i];
	    var currentNodeIds = [];

	    for (var j = 0; j < nodeGroup.length; j++) {
	      var node = nodeGroup[j];
	      var key = '' + i + j;

	      currentNodeIds.push(key);
	      table[key] = { node: node, lastCount: 0 };
	      graph[key] = {};

	      for (var n = 0; n < prevNodeIds.length; n++) {
	        var prevNodeId = prevNodeIds[n];

	        if (table[prevNodeId] && table[prevNodeId].node.mode === node.mode) {
	          graph[prevNodeId][key] =
	            getSegmentBitsLength(table[prevNodeId].lastCount + node.length, node.mode) -
	            getSegmentBitsLength(table[prevNodeId].lastCount, node.mode);

	          table[prevNodeId].lastCount += node.length;
	        } else {
	          if (table[prevNodeId]) table[prevNodeId].lastCount = node.length;

	          graph[prevNodeId][key] = getSegmentBitsLength(node.length, node.mode) +
	            4 + Mode.getCharCountIndicator(node.mode, version); // switch cost
	        }
	      }
	    }

	    prevNodeIds = currentNodeIds;
	  }

	  for (n = 0; n < prevNodeIds.length; n++) {
	    graph[prevNodeIds[n]]['end'] = 0;
	  }

	  return { map: graph, table: table }
	}

	/**
	 * Builds a segment from a specified data and mode.
	 * If a mode is not specified, the more suitable will be used.
	 *
	 * @param  {String} data             Input data
	 * @param  {Mode | String} modesHint Data mode
	 * @return {Segment}                 Segment
	 */
	function buildSingleSegment (data, modesHint) {
	  var mode;
	  var bestMode = Mode.getBestModeForData(data);

	  mode = Mode.from(modesHint, bestMode);

	  // Make sure data can be encoded
	  if (mode !== Mode.BYTE && mode.bit < bestMode.bit) {
	    throw new Error('"' + data + '"' +
	      ' cannot be encoded with mode ' + Mode.toString(mode) +
	      '.\n Suggested mode is: ' + Mode.toString(bestMode))
	  }

	  // Use Mode.BYTE if Kanji support is disabled
	  if (mode === Mode.KANJI && !Utils.isKanjiModeEnabled()) {
	    mode = Mode.BYTE;
	  }

	  switch (mode) {
	    case Mode.NUMERIC:
	      return new NumericData(data)

	    case Mode.ALPHANUMERIC:
	      return new AlphanumericData(data)

	    case Mode.KANJI:
	      return new KanjiData(data)

	    case Mode.BYTE:
	      return new ByteData(data)
	  }
	}

	/**
	 * Builds a list of segments from an array.
	 * Array can contain Strings or Objects with segment's info.
	 *
	 * For each item which is a string, will be generated a segment with the given
	 * string and the more appropriate encoding mode.
	 *
	 * For each item which is an object, will be generated a segment with the given
	 * data and mode.
	 * Objects must contain at least the property "data".
	 * If property "mode" is not present, the more suitable mode will be used.
	 *
	 * @param  {Array} array Array of objects with segments data
	 * @return {Array}       Array of Segments
	 */
	exports.fromArray = function fromArray (array) {
	  return array.reduce(function (acc, seg) {
	    if (typeof seg === 'string') {
	      acc.push(buildSingleSegment(seg, null));
	    } else if (seg.data) {
	      acc.push(buildSingleSegment(seg.data, seg.mode));
	    }

	    return acc
	  }, [])
	};

	/**
	 * Builds an optimized sequence of segments from a string,
	 * which will produce the shortest possible bitstream.
	 *
	 * @param  {String} data    Input string
	 * @param  {Number} version QR Code version
	 * @return {Array}          Array of segments
	 */
	exports.fromString = function fromString (data, version) {
	  var segs = getSegmentsFromString(data, Utils.isKanjiModeEnabled());

	  var nodes = buildNodes(segs);
	  var graph = buildGraph(nodes, version);
	  var path = dijkstra.find_path(graph.map, 'start', 'end');

	  var optimizedSegs = [];
	  for (var i = 1; i < path.length - 1; i++) {
	    optimizedSegs.push(graph.table[path[i]].node);
	  }

	  return exports.fromArray(mergeSegments(optimizedSegs))
	};

	/**
	 * Splits a string in various segments with the modes which
	 * best represent their content.
	 * The produced segments are far from being optimized.
	 * The output of this function is only used to estimate a QR Code version
	 * which may contain the data.
	 *
	 * @param  {string} data Input string
	 * @return {Array}       Array of segments
	 */
	exports.rawSplit = function rawSplit (data) {
	  return exports.fromArray(
	    getSegmentsFromString(data, Utils.isKanjiModeEnabled())
	  )
	};

	},{"./alphanumeric-data":3,"./byte-data":6,"./kanji-data":12,"./mode":14,"./numeric-data":15,"./regex":19,"./utils":21,"dijkstrajs":31}],21:[function(require,module,exports){
	var toSJISFunction;
	var CODEWORDS_COUNT = [
	  0, // Not used
	  26, 44, 70, 100, 134, 172, 196, 242, 292, 346,
	  404, 466, 532, 581, 655, 733, 815, 901, 991, 1085,
	  1156, 1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185,
	  2323, 2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706
	];

	/**
	 * Returns the QR Code size for the specified version
	 *
	 * @param  {Number} version QR Code version
	 * @return {Number}         size of QR code
	 */
	exports.getSymbolSize = function getSymbolSize (version) {
	  if (!version) throw new Error('"version" cannot be null or undefined')
	  if (version < 1 || version > 40) throw new Error('"version" should be in range from 1 to 40')
	  return version * 4 + 17
	};

	/**
	 * Returns the total number of codewords used to store data and EC information.
	 *
	 * @param  {Number} version QR Code version
	 * @return {Number}         Data length in bits
	 */
	exports.getSymbolTotalCodewords = function getSymbolTotalCodewords (version) {
	  return CODEWORDS_COUNT[version]
	};

	/**
	 * Encode data with Bose-Chaudhuri-Hocquenghem
	 *
	 * @param  {Number} data Value to encode
	 * @return {Number}      Encoded value
	 */
	exports.getBCHDigit = function (data) {
	  var digit = 0;

	  while (data !== 0) {
	    digit++;
	    data >>>= 1;
	  }

	  return digit
	};

	exports.setToSJISFunction = function setToSJISFunction (f) {
	  if (typeof f !== 'function') {
	    throw new Error('"toSJISFunc" is not a valid function.')
	  }

	  toSJISFunction = f;
	};

	exports.isKanjiModeEnabled = function () {
	  return typeof toSJISFunction !== 'undefined'
	};

	exports.toSJIS = function toSJIS (kanji) {
	  return toSJISFunction(kanji)
	};

	},{}],22:[function(require,module,exports){
	/**
	 * Check if QR Code version is valid
	 *
	 * @param  {Number}  version QR Code version
	 * @return {Boolean}         true if valid version, false otherwise
	 */
	exports.isValid = function isValid (version) {
	  return !isNaN(version) && version >= 1 && version <= 40
	};

	},{}],23:[function(require,module,exports){
	var Utils = require('./utils');
	var ECCode = require('./error-correction-code');
	var ECLevel = require('./error-correction-level');
	var Mode = require('./mode');
	var VersionCheck = require('./version-check');
	var isArray = require('isarray');

	// Generator polynomial used to encode version information
	var G18 = (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0);
	var G18_BCH = Utils.getBCHDigit(G18);

	function getBestVersionForDataLength (mode, length, errorCorrectionLevel) {
	  for (var currentVersion = 1; currentVersion <= 40; currentVersion++) {
	    if (length <= exports.getCapacity(currentVersion, errorCorrectionLevel, mode)) {
	      return currentVersion
	    }
	  }

	  return undefined
	}

	function getReservedBitsCount (mode, version) {
	  // Character count indicator + mode indicator bits
	  return Mode.getCharCountIndicator(mode, version) + 4
	}

	function getTotalBitsFromDataArray (segments, version) {
	  var totalBits = 0;

	  segments.forEach(function (data) {
	    var reservedBits = getReservedBitsCount(data.mode, version);
	    totalBits += reservedBits + data.getBitsLength();
	  });

	  return totalBits
	}

	function getBestVersionForMixedData (segments, errorCorrectionLevel) {
	  for (var currentVersion = 1; currentVersion <= 40; currentVersion++) {
	    var length = getTotalBitsFromDataArray(segments, currentVersion);
	    if (length <= exports.getCapacity(currentVersion, errorCorrectionLevel, Mode.MIXED)) {
	      return currentVersion
	    }
	  }

	  return undefined
	}

	/**
	 * Returns version number from a value.
	 * If value is not a valid version, returns defaultValue
	 *
	 * @param  {Number|String} value        QR Code version
	 * @param  {Number}        defaultValue Fallback value
	 * @return {Number}                     QR Code version number
	 */
	exports.from = function from (value, defaultValue) {
	  if (VersionCheck.isValid(value)) {
	    return parseInt(value, 10)
	  }

	  return defaultValue
	};

	/**
	 * Returns how much data can be stored with the specified QR code version
	 * and error correction level
	 *
	 * @param  {Number} version              QR Code version (1-40)
	 * @param  {Number} errorCorrectionLevel Error correction level
	 * @param  {Mode}   mode                 Data mode
	 * @return {Number}                      Quantity of storable data
	 */
	exports.getCapacity = function getCapacity (version, errorCorrectionLevel, mode) {
	  if (!VersionCheck.isValid(version)) {
	    throw new Error('Invalid QR Code version')
	  }

	  // Use Byte mode as default
	  if (typeof mode === 'undefined') mode = Mode.BYTE;

	  // Total codewords for this QR code version (Data + Error correction)
	  var totalCodewords = Utils.getSymbolTotalCodewords(version);

	  // Total number of error correction codewords
	  var ecTotalCodewords = ECCode.getTotalCodewordsCount(version, errorCorrectionLevel);

	  // Total number of data codewords
	  var dataTotalCodewordsBits = (totalCodewords - ecTotalCodewords) * 8;

	  if (mode === Mode.MIXED) return dataTotalCodewordsBits

	  var usableBits = dataTotalCodewordsBits - getReservedBitsCount(mode, version);

	  // Return max number of storable codewords
	  switch (mode) {
	    case Mode.NUMERIC:
	      return Math.floor((usableBits / 10) * 3)

	    case Mode.ALPHANUMERIC:
	      return Math.floor((usableBits / 11) * 2)

	    case Mode.KANJI:
	      return Math.floor(usableBits / 13)

	    case Mode.BYTE:
	    default:
	      return Math.floor(usableBits / 8)
	  }
	};

	/**
	 * Returns the minimum version needed to contain the amount of data
	 *
	 * @param  {Segment} data                    Segment of data
	 * @param  {Number} [errorCorrectionLevel=H] Error correction level
	 * @param  {Mode} mode                       Data mode
	 * @return {Number}                          QR Code version
	 */
	exports.getBestVersionForData = function getBestVersionForData (data, errorCorrectionLevel) {
	  var seg;

	  var ecl = ECLevel.from(errorCorrectionLevel, ECLevel.M);

	  if (isArray(data)) {
	    if (data.length > 1) {
	      return getBestVersionForMixedData(data, ecl)
	    }

	    if (data.length === 0) {
	      return 1
	    }

	    seg = data[0];
	  } else {
	    seg = data;
	  }

	  return getBestVersionForDataLength(seg.mode, seg.getLength(), ecl)
	};

	/**
	 * Returns version information with relative error correction bits
	 *
	 * The version information is included in QR Code symbols of version 7 or larger.
	 * It consists of an 18-bit sequence containing 6 data bits,
	 * with 12 error correction bits calculated using the (18, 6) Golay code.
	 *
	 * @param  {Number} version QR Code version
	 * @return {Number}         Encoded version info bits
	 */
	exports.getEncodedBits = function getEncodedBits (version) {
	  if (!VersionCheck.isValid(version) || version < 7) {
	    throw new Error('Invalid QR Code version')
	  }

	  var d = version << 12;

	  while (Utils.getBCHDigit(d) - G18_BCH >= 0) {
	    d ^= (G18 << (Utils.getBCHDigit(d) - G18_BCH));
	  }

	  return (version << 12) | d
	};

	},{"./error-correction-code":7,"./error-correction-level":8,"./mode":14,"./utils":21,"./version-check":22,"isarray":33}],24:[function(require,module,exports){

	var canPromise = require('./can-promise');

	var QRCode = require('./core/qrcode');
	var CanvasRenderer = require('./renderer/canvas');
	var SvgRenderer = require('./renderer/svg-tag.js');

	function renderCanvas (renderFunc, canvas, text, opts, cb) {
	  var args = [].slice.call(arguments, 1);
	  var argsNum = args.length;
	  var isLastArgCb = typeof args[argsNum - 1] === 'function';

	  if (!isLastArgCb && !canPromise()) {
	    throw new Error('Callback required as last argument')
	  }

	  if (isLastArgCb) {
	    if (argsNum < 2) {
	      throw new Error('Too few arguments provided')
	    }

	    if (argsNum === 2) {
	      cb = text;
	      text = canvas;
	      canvas = opts = undefined;
	    } else if (argsNum === 3) {
	      if (canvas.getContext && typeof cb === 'undefined') {
	        cb = opts;
	        opts = undefined;
	      } else {
	        cb = opts;
	        opts = text;
	        text = canvas;
	        canvas = undefined;
	      }
	    }
	  } else {
	    if (argsNum < 1) {
	      throw new Error('Too few arguments provided')
	    }

	    if (argsNum === 1) {
	      text = canvas;
	      canvas = opts = undefined;
	    } else if (argsNum === 2 && !canvas.getContext) {
	      opts = text;
	      text = canvas;
	      canvas = undefined;
	    }

	    return new Promise(function (resolve, reject) {
	      try {
	        var data = QRCode.create(text, opts);
	        resolve(renderFunc(data, canvas, opts));
	      } catch (e) {
	        reject(e);
	      }
	    })
	  }

	  try {
	    var data = QRCode.create(text, opts);
	    cb(null, renderFunc(data, canvas, opts));
	  } catch (e) {
	    cb(e);
	  }
	}

	exports.create = QRCode.create;
	exports.toCanvas = renderCanvas.bind(null, CanvasRenderer.render);
	exports.toDataURL = renderCanvas.bind(null, CanvasRenderer.renderToDataURL);

	// only svg for now.
	exports.toString = renderCanvas.bind(null, function (data, _, opts) {
	  return SvgRenderer.render(data, opts)
	});

	},{"./can-promise":1,"./core/qrcode":17,"./renderer/canvas":25,"./renderer/svg-tag.js":26}],25:[function(require,module,exports){
	var Utils = require('./utils');

	function clearCanvas (ctx, canvas, size) {
	  ctx.clearRect(0, 0, canvas.width, canvas.height);

	  if (!canvas.style) canvas.style = {};
	  canvas.height = size;
	  canvas.width = size;
	  canvas.style.height = size + 'px';
	  canvas.style.width = size + 'px';
	}

	function getCanvasElement () {
	  try {
	    return document.createElement('canvas')
	  } catch (e) {
	    throw new Error('You need to specify a canvas element')
	  }
	}

	exports.render = function render (qrData, canvas, options) {
	  var opts = options;
	  var canvasEl = canvas;

	  if (typeof opts === 'undefined' && (!canvas || !canvas.getContext)) {
	    opts = canvas;
	    canvas = undefined;
	  }

	  if (!canvas) {
	    canvasEl = getCanvasElement();
	  }

	  opts = Utils.getOptions(opts);
	  var size = Utils.getImageWidth(qrData.modules.size, opts);

	  var ctx = canvasEl.getContext('2d');
	  var image = ctx.createImageData(size, size);
	  Utils.qrToImageData(image.data, qrData, opts);

	  clearCanvas(ctx, canvasEl, size);
	  ctx.putImageData(image, 0, 0);

	  return canvasEl
	};

	exports.renderToDataURL = function renderToDataURL (qrData, canvas, options) {
	  var opts = options;

	  if (typeof opts === 'undefined' && (!canvas || !canvas.getContext)) {
	    opts = canvas;
	    canvas = undefined;
	  }

	  if (!opts) opts = {};

	  var canvasEl = exports.render(qrData, canvas, opts);

	  var type = opts.type || 'image/png';
	  var rendererOpts = opts.rendererOpts || {};

	  return canvasEl.toDataURL(type, rendererOpts.quality)
	};

	},{"./utils":27}],26:[function(require,module,exports){
	var Utils = require('./utils');

	function getColorAttrib (color, attrib) {
	  var alpha = color.a / 255;
	  var str = attrib + '="' + color.hex + '"';

	  return alpha < 1
	    ? str + ' ' + attrib + '-opacity="' + alpha.toFixed(2).slice(1) + '"'
	    : str
	}

	function svgCmd (cmd, x, y) {
	  var str = cmd + x;
	  if (typeof y !== 'undefined') str += ' ' + y;

	  return str
	}

	function qrToPath (data, size, margin) {
	  var path = '';
	  var moveBy = 0;
	  var newRow = false;
	  var lineLength = 0;

	  for (var i = 0; i < data.length; i++) {
	    var col = Math.floor(i % size);
	    var row = Math.floor(i / size);

	    if (!col && !newRow) newRow = true;

	    if (data[i]) {
	      lineLength++;

	      if (!(i > 0 && col > 0 && data[i - 1])) {
	        path += newRow
	          ? svgCmd('M', col + margin, 0.5 + row + margin)
	          : svgCmd('m', moveBy, 0);

	        moveBy = 0;
	        newRow = false;
	      }

	      if (!(col + 1 < size && data[i + 1])) {
	        path += svgCmd('h', lineLength);
	        lineLength = 0;
	      }
	    } else {
	      moveBy++;
	    }
	  }

	  return path
	}

	exports.render = function render (qrData, options, cb) {
	  var opts = Utils.getOptions(options);
	  var size = qrData.modules.size;
	  var data = qrData.modules.data;
	  var qrcodesize = size + opts.margin * 2;

	  var bg = !opts.color.light.a
	    ? ''
	    : '<path ' + getColorAttrib(opts.color.light, 'fill') +
	      ' d="M0 0h' + qrcodesize + 'v' + qrcodesize + 'H0z"/>';

	  var path =
	    '<path ' + getColorAttrib(opts.color.dark, 'stroke') +
	    ' d="' + qrToPath(data, size, opts.margin) + '"/>';

	  var viewBox = 'viewBox="' + '0 0 ' + qrcodesize + ' ' + qrcodesize + '"';

	  var width = !opts.width ? '' : 'width="' + opts.width + '" height="' + opts.width + '" ';

	  var svgTag = '<svg xmlns="http://www.w3.org/2000/svg" ' + width + viewBox + ' shape-rendering="crispEdges">' + bg + path + '</svg>\n';

	  if (typeof cb === 'function') {
	    cb(null, svgTag);
	  }

	  return svgTag
	};

	},{"./utils":27}],27:[function(require,module,exports){
	function hex2rgba (hex) {
	  if (typeof hex === 'number') {
	    hex = hex.toString();
	  }

	  if (typeof hex !== 'string') {
	    throw new Error('Color should be defined as hex string')
	  }

	  var hexCode = hex.slice().replace('#', '').split('');
	  if (hexCode.length < 3 || hexCode.length === 5 || hexCode.length > 8) {
	    throw new Error('Invalid hex color: ' + hex)
	  }

	  // Convert from short to long form (fff -> ffffff)
	  if (hexCode.length === 3 || hexCode.length === 4) {
	    hexCode = Array.prototype.concat.apply([], hexCode.map(function (c) {
	      return [c, c]
	    }));
	  }

	  // Add default alpha value
	  if (hexCode.length === 6) hexCode.push('F', 'F');

	  var hexValue = parseInt(hexCode.join(''), 16);

	  return {
	    r: (hexValue >> 24) & 255,
	    g: (hexValue >> 16) & 255,
	    b: (hexValue >> 8) & 255,
	    a: hexValue & 255,
	    hex: '#' + hexCode.slice(0, 6).join('')
	  }
	}

	exports.getOptions = function getOptions (options) {
	  if (!options) options = {};
	  if (!options.color) options.color = {};

	  var margin = typeof options.margin === 'undefined' ||
	    options.margin === null ||
	    options.margin < 0 ? 4 : options.margin;

	  var width = options.width && options.width >= 21 ? options.width : undefined;
	  var scale = options.scale || 4;

	  return {
	    width: width,
	    scale: width ? 4 : scale,
	    margin: margin,
	    color: {
	      dark: hex2rgba(options.color.dark || '#000000ff'),
	      light: hex2rgba(options.color.light || '#ffffffff')
	    },
	    type: options.type,
	    rendererOpts: options.rendererOpts || {}
	  }
	};

	exports.getScale = function getScale (qrSize, opts) {
	  return opts.width && opts.width >= qrSize + opts.margin * 2
	    ? opts.width / (qrSize + opts.margin * 2)
	    : opts.scale
	};

	exports.getImageWidth = function getImageWidth (qrSize, opts) {
	  var scale = exports.getScale(qrSize, opts);
	  return Math.floor((qrSize + opts.margin * 2) * scale)
	};

	exports.qrToImageData = function qrToImageData (imgData, qr, opts) {
	  var size = qr.modules.size;
	  var data = qr.modules.data;
	  var scale = exports.getScale(size, opts);
	  var symbolSize = Math.floor((size + opts.margin * 2) * scale);
	  var scaledMargin = opts.margin * scale;
	  var palette = [opts.color.light, opts.color.dark];

	  for (var i = 0; i < symbolSize; i++) {
	    for (var j = 0; j < symbolSize; j++) {
	      var posDst = (i * symbolSize + j) * 4;
	      var pxColor = opts.color.light;

	      if (i >= scaledMargin && j >= scaledMargin &&
	        i < symbolSize - scaledMargin && j < symbolSize - scaledMargin) {
	        var iSrc = Math.floor((i - scaledMargin) / scale);
	        var jSrc = Math.floor((j - scaledMargin) / scale);
	        pxColor = palette[data[iSrc * size + jSrc] ? 1 : 0];
	      }

	      imgData[posDst++] = pxColor.r;
	      imgData[posDst++] = pxColor.g;
	      imgData[posDst++] = pxColor.b;
	      imgData[posDst] = pxColor.a;
	    }
	  }
	};

	},{}],28:[function(require,module,exports){

	var isArray = require('isarray');

	function typedArraySupport () {
	  // Can typed array instances be augmented?
	  try {
	    var arr = new Uint8Array(1);
	    arr.__proto__ = {__proto__: Uint8Array.prototype, foo: function () { return 42 }};
	    return arr.foo() === 42
	  } catch (e) {
	    return false
	  }
	}

	Buffer.TYPED_ARRAY_SUPPORT = typedArraySupport();

	var K_MAX_LENGTH = Buffer.TYPED_ARRAY_SUPPORT
	    ? 0x7fffffff
	    : 0x3fffffff;

	function Buffer (arg, offset, length) {
	  if (!Buffer.TYPED_ARRAY_SUPPORT && !(this instanceof Buffer)) {
	    return new Buffer(arg, offset, length)
	  }

	  if (typeof arg === 'number') {
	    return allocUnsafe(this, arg)
	  }

	  return from(this, arg, offset, length)
	}

	if (Buffer.TYPED_ARRAY_SUPPORT) {
	  Buffer.prototype.__proto__ = Uint8Array.prototype;
	  Buffer.__proto__ = Uint8Array;

	  // Fix subarray() in ES2016. See: https://github.com/feross/buffer/pull/97
	  if (typeof Symbol !== 'undefined' && Symbol.species &&
	      Buffer[Symbol.species] === Buffer) {
	    Object.defineProperty(Buffer, Symbol.species, {
	      value: null,
	      configurable: true,
	      enumerable: false,
	      writable: false
	    });
	  }
	}

	function checked (length) {
	  // Note: cannot use `length < K_MAX_LENGTH` here because that fails when
	  // length is NaN (which is otherwise coerced to zero.)
	  if (length >= K_MAX_LENGTH) {
	    throw new RangeError('Attempt to allocate Buffer larger than maximum ' +
	                         'size: 0x' + K_MAX_LENGTH.toString(16) + ' bytes')
	  }
	  return length | 0
	}

	function isnan (val) {
	  return val !== val // eslint-disable-line no-self-compare
	}

	function createBuffer (that, length) {
	  var buf;
	  if (Buffer.TYPED_ARRAY_SUPPORT) {
	    buf = new Uint8Array(length);
	    buf.__proto__ = Buffer.prototype;
	  } else {
	    // Fallback: Return an object instance of the Buffer class
	    buf = that;
	    if (buf === null) {
	      buf = new Buffer(length);
	    }
	    buf.length = length;
	  }

	  return buf
	}

	function allocUnsafe (that, size) {
	  var buf = createBuffer(that, size < 0 ? 0 : checked(size) | 0);

	  if (!Buffer.TYPED_ARRAY_SUPPORT) {
	    for (var i = 0; i < size; ++i) {
	      buf[i] = 0;
	    }
	  }

	  return buf
	}

	function fromString (that, string) {
	  var length = byteLength(string) | 0;
	  var buf = createBuffer(that, length);

	  var actual = buf.write(string);

	  if (actual !== length) {
	    // Writing a hex string, for example, that contains invalid characters will
	    // cause everything after the first invalid character to be ignored. (e.g.
	    // 'abxxcd' will be treated as 'ab')
	    buf = buf.slice(0, actual);
	  }

	  return buf
	}

	function fromArrayLike (that, array) {
	  var length = array.length < 0 ? 0 : checked(array.length) | 0;
	  var buf = createBuffer(that, length);
	  for (var i = 0; i < length; i += 1) {
	    buf[i] = array[i] & 255;
	  }
	  return buf
	}

	function fromArrayBuffer (that, array, byteOffset, length) {
	  if (byteOffset < 0 || array.byteLength < byteOffset) {
	    throw new RangeError('\'offset\' is out of bounds')
	  }

	  if (array.byteLength < byteOffset + (length || 0)) {
	    throw new RangeError('\'length\' is out of bounds')
	  }

	  var buf;
	  if (byteOffset === undefined && length === undefined) {
	    buf = new Uint8Array(array);
	  } else if (length === undefined) {
	    buf = new Uint8Array(array, byteOffset);
	  } else {
	    buf = new Uint8Array(array, byteOffset, length);
	  }

	  if (Buffer.TYPED_ARRAY_SUPPORT) {
	    // Return an augmented `Uint8Array` instance, for best performance
	    buf.__proto__ = Buffer.prototype;
	  } else {
	    // Fallback: Return an object instance of the Buffer class
	    buf = fromArrayLike(that, buf);
	  }

	  return buf
	}

	function fromObject (that, obj) {
	  if (Buffer.isBuffer(obj)) {
	    var len = checked(obj.length) | 0;
	    var buf = createBuffer(that, len);

	    if (buf.length === 0) {
	      return buf
	    }

	    obj.copy(buf, 0, 0, len);
	    return buf
	  }

	  if (obj) {
	    if ((typeof ArrayBuffer !== 'undefined' &&
	        obj.buffer instanceof ArrayBuffer) || 'length' in obj) {
	      if (typeof obj.length !== 'number' || isnan(obj.length)) {
	        return createBuffer(that, 0)
	      }
	      return fromArrayLike(that, obj)
	    }

	    if (obj.type === 'Buffer' && Array.isArray(obj.data)) {
	      return fromArrayLike(that, obj.data)
	    }
	  }

	  throw new TypeError('First argument must be a string, Buffer, ArrayBuffer, Array, or array-like object.')
	}

	function utf8ToBytes (string, units) {
	  units = units || Infinity;
	  var codePoint;
	  var length = string.length;
	  var leadSurrogate = null;
	  var bytes = [];

	  for (var i = 0; i < length; ++i) {
	    codePoint = string.charCodeAt(i);

	    // is surrogate component
	    if (codePoint > 0xD7FF && codePoint < 0xE000) {
	      // last char was a lead
	      if (!leadSurrogate) {
	        // no lead yet
	        if (codePoint > 0xDBFF) {
	          // unexpected trail
	          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	          continue
	        } else if (i + 1 === length) {
	          // unpaired lead
	          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	          continue
	        }

	        // valid lead
	        leadSurrogate = codePoint;

	        continue
	      }

	      // 2 leads in a row
	      if (codePoint < 0xDC00) {
	        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	        leadSurrogate = codePoint;
	        continue
	      }

	      // valid surrogate pair
	      codePoint = (leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00) + 0x10000;
	    } else if (leadSurrogate) {
	      // valid bmp char, but last char was a lead
	      if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	    }

	    leadSurrogate = null;

	    // encode utf8
	    if (codePoint < 0x80) {
	      if ((units -= 1) < 0) break
	      bytes.push(codePoint);
	    } else if (codePoint < 0x800) {
	      if ((units -= 2) < 0) break
	      bytes.push(
	        codePoint >> 0x6 | 0xC0,
	        codePoint & 0x3F | 0x80
	      );
	    } else if (codePoint < 0x10000) {
	      if ((units -= 3) < 0) break
	      bytes.push(
	        codePoint >> 0xC | 0xE0,
	        codePoint >> 0x6 & 0x3F | 0x80,
	        codePoint & 0x3F | 0x80
	      );
	    } else if (codePoint < 0x110000) {
	      if ((units -= 4) < 0) break
	      bytes.push(
	        codePoint >> 0x12 | 0xF0,
	        codePoint >> 0xC & 0x3F | 0x80,
	        codePoint >> 0x6 & 0x3F | 0x80,
	        codePoint & 0x3F | 0x80
	      );
	    } else {
	      throw new Error('Invalid code point')
	    }
	  }

	  return bytes
	}

	function byteLength (string) {
	  if (Buffer.isBuffer(string)) {
	    return string.length
	  }
	  if (typeof ArrayBuffer !== 'undefined' && typeof ArrayBuffer.isView === 'function' &&
	      (ArrayBuffer.isView(string) || string instanceof ArrayBuffer)) {
	    return string.byteLength
	  }
	  if (typeof string !== 'string') {
	    string = '' + string;
	  }

	  var len = string.length;
	  if (len === 0) return 0

	  return utf8ToBytes(string).length
	}

	function blitBuffer (src, dst, offset, length) {
	  for (var i = 0; i < length; ++i) {
	    if ((i + offset >= dst.length) || (i >= src.length)) break
	    dst[i + offset] = src[i];
	  }
	  return i
	}

	function utf8Write (buf, string, offset, length) {
	  return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length)
	}

	function from (that, value, offset, length) {
	  if (typeof value === 'number') {
	    throw new TypeError('"value" argument must not be a number')
	  }

	  if (typeof ArrayBuffer !== 'undefined' && value instanceof ArrayBuffer) {
	    return fromArrayBuffer(that, value, offset, length)
	  }

	  if (typeof value === 'string') {
	    return fromString(that, value)
	  }

	  return fromObject(that, value)
	}

	Buffer.prototype.write = function write (string, offset, length) {
	  // Buffer#write(string)
	  if (offset === undefined) {
	    length = this.length;
	    offset = 0;
	  // Buffer#write(string, encoding)
	  } else if (length === undefined && typeof offset === 'string') {
	    length = this.length;
	    offset = 0;
	  // Buffer#write(string, offset[, length])
	  } else if (isFinite(offset)) {
	    offset = offset | 0;
	    if (isFinite(length)) {
	      length = length | 0;
	    } else {
	      length = undefined;
	    }
	  }

	  var remaining = this.length - offset;
	  if (length === undefined || length > remaining) length = remaining;

	  if ((string.length > 0 && (length < 0 || offset < 0)) || offset > this.length) {
	    throw new RangeError('Attempt to write outside buffer bounds')
	  }

	  return utf8Write(this, string, offset, length)
	};

	Buffer.prototype.slice = function slice (start, end) {
	  var len = this.length;
	  start = ~~start;
	  end = end === undefined ? len : ~~end;

	  if (start < 0) {
	    start += len;
	    if (start < 0) start = 0;
	  } else if (start > len) {
	    start = len;
	  }

	  if (end < 0) {
	    end += len;
	    if (end < 0) end = 0;
	  } else if (end > len) {
	    end = len;
	  }

	  if (end < start) end = start;

	  var newBuf;
	  if (Buffer.TYPED_ARRAY_SUPPORT) {
	    newBuf = this.subarray(start, end);
	    // Return an augmented `Uint8Array` instance
	    newBuf.__proto__ = Buffer.prototype;
	  } else {
	    var sliceLen = end - start;
	    newBuf = new Buffer(sliceLen, undefined);
	    for (var i = 0; i < sliceLen; ++i) {
	      newBuf[i] = this[i + start];
	    }
	  }

	  return newBuf
	};

	Buffer.prototype.copy = function copy (target, targetStart, start, end) {
	  if (!start) start = 0;
	  if (!end && end !== 0) end = this.length;
	  if (targetStart >= target.length) targetStart = target.length;
	  if (!targetStart) targetStart = 0;
	  if (end > 0 && end < start) end = start;

	  // Copy 0 bytes; we're done
	  if (end === start) return 0
	  if (target.length === 0 || this.length === 0) return 0

	  // Fatal error conditions
	  if (targetStart < 0) {
	    throw new RangeError('targetStart out of bounds')
	  }
	  if (start < 0 || start >= this.length) throw new RangeError('sourceStart out of bounds')
	  if (end < 0) throw new RangeError('sourceEnd out of bounds')

	  // Are we oob?
	  if (end > this.length) end = this.length;
	  if (target.length - targetStart < end - start) {
	    end = target.length - targetStart + start;
	  }

	  var len = end - start;
	  var i;

	  if (this === target && start < targetStart && targetStart < end) {
	    // descending copy from end
	    for (i = len - 1; i >= 0; --i) {
	      target[i + targetStart] = this[i + start];
	    }
	  } else if (len < 1000 || !Buffer.TYPED_ARRAY_SUPPORT) {
	    // ascending copy from start
	    for (i = 0; i < len; ++i) {
	      target[i + targetStart] = this[i + start];
	    }
	  } else {
	    Uint8Array.prototype.set.call(
	      target,
	      this.subarray(start, start + len),
	      targetStart
	    );
	  }

	  return len
	};

	Buffer.prototype.fill = function fill (val, start, end) {
	  // Handle string cases:
	  if (typeof val === 'string') {
	    if (typeof start === 'string') {
	      start = 0;
	      end = this.length;
	    } else if (typeof end === 'string') {
	      end = this.length;
	    }
	    if (val.length === 1) {
	      var code = val.charCodeAt(0);
	      if (code < 256) {
	        val = code;
	      }
	    }
	  } else if (typeof val === 'number') {
	    val = val & 255;
	  }

	  // Invalid ranges are not set to a default, so can range check early.
	  if (start < 0 || this.length < start || this.length < end) {
	    throw new RangeError('Out of range index')
	  }

	  if (end <= start) {
	    return this
	  }

	  start = start >>> 0;
	  end = end === undefined ? this.length : end >>> 0;

	  if (!val) val = 0;

	  var i;
	  if (typeof val === 'number') {
	    for (i = start; i < end; ++i) {
	      this[i] = val;
	    }
	  } else {
	    var bytes = Buffer.isBuffer(val)
	      ? val
	      : new Buffer(val);
	    var len = bytes.length;
	    for (i = 0; i < end - start; ++i) {
	      this[i + start] = bytes[i % len];
	    }
	  }

	  return this
	};

	Buffer.concat = function concat (list, length) {
	  if (!isArray(list)) {
	    throw new TypeError('"list" argument must be an Array of Buffers')
	  }

	  if (list.length === 0) {
	    return createBuffer(null, 0)
	  }

	  var i;
	  if (length === undefined) {
	    length = 0;
	    for (i = 0; i < list.length; ++i) {
	      length += list[i].length;
	    }
	  }

	  var buffer = allocUnsafe(null, length);
	  var pos = 0;
	  for (i = 0; i < list.length; ++i) {
	    var buf = list[i];
	    if (!Buffer.isBuffer(buf)) {
	      throw new TypeError('"list" argument must be an Array of Buffers')
	    }
	    buf.copy(buffer, pos);
	    pos += buf.length;
	  }
	  return buffer
	};

	Buffer.byteLength = byteLength;

	Buffer.prototype._isBuffer = true;
	Buffer.isBuffer = function isBuffer (b) {
	  return !!(b != null && b._isBuffer)
	};

	module.exports.alloc = function (size) {
	  var buffer = new Buffer(size);
	  buffer.fill(0);
	  return buffer
	};

	module.exports.from = function (data) {
	  return new Buffer(data)
	};

	},{"isarray":33}],29:[function(require,module,exports){

	exports.byteLength = byteLength;
	exports.toByteArray = toByteArray;
	exports.fromByteArray = fromByteArray;

	var lookup = [];
	var revLookup = [];
	var Arr = typeof Uint8Array !== 'undefined' ? Uint8Array : Array;

	var code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	for (var i = 0, len = code.length; i < len; ++i) {
	  lookup[i] = code[i];
	  revLookup[code.charCodeAt(i)] = i;
	}

	// Support decoding URL-safe base64 strings, as Node.js does.
	// See: https://en.wikipedia.org/wiki/Base64#URL_applications
	revLookup['-'.charCodeAt(0)] = 62;
	revLookup['_'.charCodeAt(0)] = 63;

	function getLens (b64) {
	  var len = b64.length;

	  if (len % 4 > 0) {
	    throw new Error('Invalid string. Length must be a multiple of 4')
	  }

	  // Trim off extra bytes after placeholder bytes are found
	  // See: https://github.com/beatgammit/base64-js/issues/42
	  var validLen = b64.indexOf('=');
	  if (validLen === -1) validLen = len;

	  var placeHoldersLen = validLen === len
	    ? 0
	    : 4 - (validLen % 4);

	  return [validLen, placeHoldersLen]
	}

	// base64 is 4/3 + up to two characters of the original data
	function byteLength (b64) {
	  var lens = getLens(b64);
	  var validLen = lens[0];
	  var placeHoldersLen = lens[1];
	  return ((validLen + placeHoldersLen) * 3 / 4) - placeHoldersLen
	}

	function _byteLength (b64, validLen, placeHoldersLen) {
	  return ((validLen + placeHoldersLen) * 3 / 4) - placeHoldersLen
	}

	function toByteArray (b64) {
	  var tmp;
	  var lens = getLens(b64);
	  var validLen = lens[0];
	  var placeHoldersLen = lens[1];

	  var arr = new Arr(_byteLength(b64, validLen, placeHoldersLen));

	  var curByte = 0;

	  // if there are placeholders, only get up to the last complete 4 chars
	  var len = placeHoldersLen > 0
	    ? validLen - 4
	    : validLen;

	  var i;
	  for (i = 0; i < len; i += 4) {
	    tmp =
	      (revLookup[b64.charCodeAt(i)] << 18) |
	      (revLookup[b64.charCodeAt(i + 1)] << 12) |
	      (revLookup[b64.charCodeAt(i + 2)] << 6) |
	      revLookup[b64.charCodeAt(i + 3)];
	    arr[curByte++] = (tmp >> 16) & 0xFF;
	    arr[curByte++] = (tmp >> 8) & 0xFF;
	    arr[curByte++] = tmp & 0xFF;
	  }

	  if (placeHoldersLen === 2) {
	    tmp =
	      (revLookup[b64.charCodeAt(i)] << 2) |
	      (revLookup[b64.charCodeAt(i + 1)] >> 4);
	    arr[curByte++] = tmp & 0xFF;
	  }

	  if (placeHoldersLen === 1) {
	    tmp =
	      (revLookup[b64.charCodeAt(i)] << 10) |
	      (revLookup[b64.charCodeAt(i + 1)] << 4) |
	      (revLookup[b64.charCodeAt(i + 2)] >> 2);
	    arr[curByte++] = (tmp >> 8) & 0xFF;
	    arr[curByte++] = tmp & 0xFF;
	  }

	  return arr
	}

	function tripletToBase64 (num) {
	  return lookup[num >> 18 & 0x3F] +
	    lookup[num >> 12 & 0x3F] +
	    lookup[num >> 6 & 0x3F] +
	    lookup[num & 0x3F]
	}

	function encodeChunk (uint8, start, end) {
	  var tmp;
	  var output = [];
	  for (var i = start; i < end; i += 3) {
	    tmp =
	      ((uint8[i] << 16) & 0xFF0000) +
	      ((uint8[i + 1] << 8) & 0xFF00) +
	      (uint8[i + 2] & 0xFF);
	    output.push(tripletToBase64(tmp));
	  }
	  return output.join('')
	}

	function fromByteArray (uint8) {
	  var tmp;
	  var len = uint8.length;
	  var extraBytes = len % 3; // if we have 1 byte left, pad 2 bytes
	  var parts = [];
	  var maxChunkLength = 16383; // must be multiple of 3

	  // go through the array every three bytes, we'll deal with trailing stuff later
	  for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
	    parts.push(encodeChunk(
	      uint8, i, (i + maxChunkLength) > len2 ? len2 : (i + maxChunkLength)
	    ));
	  }

	  // pad the end with zeros, but make sure to not forget the extra bytes
	  if (extraBytes === 1) {
	    tmp = uint8[len - 1];
	    parts.push(
	      lookup[tmp >> 2] +
	      lookup[(tmp << 4) & 0x3F] +
	      '=='
	    );
	  } else if (extraBytes === 2) {
	    tmp = (uint8[len - 2] << 8) + uint8[len - 1];
	    parts.push(
	      lookup[tmp >> 10] +
	      lookup[(tmp >> 4) & 0x3F] +
	      lookup[(tmp << 2) & 0x3F] +
	      '='
	    );
	  }

	  return parts.join('')
	}

	},{}],30:[function(require,module,exports){

	var base64 = require('base64-js');
	var ieee754 = require('ieee754');
	var customInspectSymbol =
	  (typeof Symbol === 'function' && typeof Symbol.for === 'function')
	    ? Symbol.for('nodejs.util.inspect.custom')
	    : null;

	exports.Buffer = Buffer;
	exports.SlowBuffer = SlowBuffer;
	exports.INSPECT_MAX_BYTES = 50;

	var K_MAX_LENGTH = 0x7fffffff;
	exports.kMaxLength = K_MAX_LENGTH;

	/**
	 * If `Buffer.TYPED_ARRAY_SUPPORT`:
	 *   === true    Use Uint8Array implementation (fastest)
	 *   === false   Print warning and recommend using `buffer` v4.x which has an Object
	 *               implementation (most compatible, even IE6)
	 *
	 * Browsers that support typed arrays are IE 10+, Firefox 4+, Chrome 7+, Safari 5.1+,
	 * Opera 11.6+, iOS 4.2+.
	 *
	 * We report that the browser does not support typed arrays if the are not subclassable
	 * using __proto__. Firefox 4-29 lacks support for adding new properties to `Uint8Array`
	 * (See: https://bugzilla.mozilla.org/show_bug.cgi?id=695438). IE 10 lacks support
	 * for __proto__ and has a buggy typed array implementation.
	 */
	Buffer.TYPED_ARRAY_SUPPORT = typedArraySupport();

	if (!Buffer.TYPED_ARRAY_SUPPORT && typeof console !== 'undefined' &&
	    typeof console.error === 'function') {
	  console.error(
	    'This browser lacks typed array (Uint8Array) support which is required by ' +
	    '`buffer` v5.x. Use `buffer` v4.x if you require old browser support.'
	  );
	}

	function typedArraySupport () {
	  // Can typed array instances can be augmented?
	  try {
	    var arr = new Uint8Array(1);
	    var proto = { foo: function () { return 42 } };
	    Object.setPrototypeOf(proto, Uint8Array.prototype);
	    Object.setPrototypeOf(arr, proto);
	    return arr.foo() === 42
	  } catch (e) {
	    return false
	  }
	}

	Object.defineProperty(Buffer.prototype, 'parent', {
	  enumerable: true,
	  get: function () {
	    if (!Buffer.isBuffer(this)) return undefined
	    return this.buffer
	  }
	});

	Object.defineProperty(Buffer.prototype, 'offset', {
	  enumerable: true,
	  get: function () {
	    if (!Buffer.isBuffer(this)) return undefined
	    return this.byteOffset
	  }
	});

	function createBuffer (length) {
	  if (length > K_MAX_LENGTH) {
	    throw new RangeError('The value "' + length + '" is invalid for option "size"')
	  }
	  // Return an augmented `Uint8Array` instance
	  var buf = new Uint8Array(length);
	  Object.setPrototypeOf(buf, Buffer.prototype);
	  return buf
	}

	/**
	 * The Buffer constructor returns instances of `Uint8Array` that have their
	 * prototype changed to `Buffer.prototype`. Furthermore, `Buffer` is a subclass of
	 * `Uint8Array`, so the returned instances will have all the node `Buffer` methods
	 * and the `Uint8Array` methods. Square bracket notation works as expected -- it
	 * returns a single octet.
	 *
	 * The `Uint8Array` prototype remains unmodified.
	 */

	function Buffer (arg, encodingOrOffset, length) {
	  // Common case.
	  if (typeof arg === 'number') {
	    if (typeof encodingOrOffset === 'string') {
	      throw new TypeError(
	        'The "string" argument must be of type string. Received type number'
	      )
	    }
	    return allocUnsafe(arg)
	  }
	  return from(arg, encodingOrOffset, length)
	}

	// Fix subarray() in ES2016. See: https://github.com/feross/buffer/pull/97
	if (typeof Symbol !== 'undefined' && Symbol.species != null &&
	    Buffer[Symbol.species] === Buffer) {
	  Object.defineProperty(Buffer, Symbol.species, {
	    value: null,
	    configurable: true,
	    enumerable: false,
	    writable: false
	  });
	}

	Buffer.poolSize = 8192; // not used by this implementation

	function from (value, encodingOrOffset, length) {
	  if (typeof value === 'string') {
	    return fromString(value, encodingOrOffset)
	  }

	  if (ArrayBuffer.isView(value)) {
	    return fromArrayLike(value)
	  }

	  if (value == null) {
	    throw new TypeError(
	      'The first argument must be one of type string, Buffer, ArrayBuffer, Array, ' +
	      'or Array-like Object. Received type ' + (typeof value)
	    )
	  }

	  if (isInstance(value, ArrayBuffer) ||
	      (value && isInstance(value.buffer, ArrayBuffer))) {
	    return fromArrayBuffer(value, encodingOrOffset, length)
	  }

	  if (typeof value === 'number') {
	    throw new TypeError(
	      'The "value" argument must not be of type number. Received type number'
	    )
	  }

	  var valueOf = value.valueOf && value.valueOf();
	  if (valueOf != null && valueOf !== value) {
	    return Buffer.from(valueOf, encodingOrOffset, length)
	  }

	  var b = fromObject(value);
	  if (b) return b

	  if (typeof Symbol !== 'undefined' && Symbol.toPrimitive != null &&
	      typeof value[Symbol.toPrimitive] === 'function') {
	    return Buffer.from(
	      value[Symbol.toPrimitive]('string'), encodingOrOffset, length
	    )
	  }

	  throw new TypeError(
	    'The first argument must be one of type string, Buffer, ArrayBuffer, Array, ' +
	    'or Array-like Object. Received type ' + (typeof value)
	  )
	}

	/**
	 * Functionally equivalent to Buffer(arg, encoding) but throws a TypeError
	 * if value is a number.
	 * Buffer.from(str[, encoding])
	 * Buffer.from(array)
	 * Buffer.from(buffer)
	 * Buffer.from(arrayBuffer[, byteOffset[, length]])
	 **/
	Buffer.from = function (value, encodingOrOffset, length) {
	  return from(value, encodingOrOffset, length)
	};

	// Note: Change prototype *after* Buffer.from is defined to workaround Chrome bug:
	// https://github.com/feross/buffer/pull/148
	Object.setPrototypeOf(Buffer.prototype, Uint8Array.prototype);
	Object.setPrototypeOf(Buffer, Uint8Array);

	function assertSize (size) {
	  if (typeof size !== 'number') {
	    throw new TypeError('"size" argument must be of type number')
	  } else if (size < 0) {
	    throw new RangeError('The value "' + size + '" is invalid for option "size"')
	  }
	}

	function alloc (size, fill, encoding) {
	  assertSize(size);
	  if (size <= 0) {
	    return createBuffer(size)
	  }
	  if (fill !== undefined) {
	    // Only pay attention to encoding if it's a string. This
	    // prevents accidentally sending in a number that would
	    // be interpretted as a start offset.
	    return typeof encoding === 'string'
	      ? createBuffer(size).fill(fill, encoding)
	      : createBuffer(size).fill(fill)
	  }
	  return createBuffer(size)
	}

	/**
	 * Creates a new filled Buffer instance.
	 * alloc(size[, fill[, encoding]])
	 **/
	Buffer.alloc = function (size, fill, encoding) {
	  return alloc(size, fill, encoding)
	};

	function allocUnsafe (size) {
	  assertSize(size);
	  return createBuffer(size < 0 ? 0 : checked(size) | 0)
	}

	/**
	 * Equivalent to Buffer(num), by default creates a non-zero-filled Buffer instance.
	 * */
	Buffer.allocUnsafe = function (size) {
	  return allocUnsafe(size)
	};
	/**
	 * Equivalent to SlowBuffer(num), by default creates a non-zero-filled Buffer instance.
	 */
	Buffer.allocUnsafeSlow = function (size) {
	  return allocUnsafe(size)
	};

	function fromString (string, encoding) {
	  if (typeof encoding !== 'string' || encoding === '') {
	    encoding = 'utf8';
	  }

	  if (!Buffer.isEncoding(encoding)) {
	    throw new TypeError('Unknown encoding: ' + encoding)
	  }

	  var length = byteLength(string, encoding) | 0;
	  var buf = createBuffer(length);

	  var actual = buf.write(string, encoding);

	  if (actual !== length) {
	    // Writing a hex string, for example, that contains invalid characters will
	    // cause everything after the first invalid character to be ignored. (e.g.
	    // 'abxxcd' will be treated as 'ab')
	    buf = buf.slice(0, actual);
	  }

	  return buf
	}

	function fromArrayLike (array) {
	  var length = array.length < 0 ? 0 : checked(array.length) | 0;
	  var buf = createBuffer(length);
	  for (var i = 0; i < length; i += 1) {
	    buf[i] = array[i] & 255;
	  }
	  return buf
	}

	function fromArrayBuffer (array, byteOffset, length) {
	  if (byteOffset < 0 || array.byteLength < byteOffset) {
	    throw new RangeError('"offset" is outside of buffer bounds')
	  }

	  if (array.byteLength < byteOffset + (length || 0)) {
	    throw new RangeError('"length" is outside of buffer bounds')
	  }

	  var buf;
	  if (byteOffset === undefined && length === undefined) {
	    buf = new Uint8Array(array);
	  } else if (length === undefined) {
	    buf = new Uint8Array(array, byteOffset);
	  } else {
	    buf = new Uint8Array(array, byteOffset, length);
	  }

	  // Return an augmented `Uint8Array` instance
	  Object.setPrototypeOf(buf, Buffer.prototype);

	  return buf
	}

	function fromObject (obj) {
	  if (Buffer.isBuffer(obj)) {
	    var len = checked(obj.length) | 0;
	    var buf = createBuffer(len);

	    if (buf.length === 0) {
	      return buf
	    }

	    obj.copy(buf, 0, 0, len);
	    return buf
	  }

	  if (obj.length !== undefined) {
	    if (typeof obj.length !== 'number' || numberIsNaN(obj.length)) {
	      return createBuffer(0)
	    }
	    return fromArrayLike(obj)
	  }

	  if (obj.type === 'Buffer' && Array.isArray(obj.data)) {
	    return fromArrayLike(obj.data)
	  }
	}

	function checked (length) {
	  // Note: cannot use `length < K_MAX_LENGTH` here because that fails when
	  // length is NaN (which is otherwise coerced to zero.)
	  if (length >= K_MAX_LENGTH) {
	    throw new RangeError('Attempt to allocate Buffer larger than maximum ' +
	                         'size: 0x' + K_MAX_LENGTH.toString(16) + ' bytes')
	  }
	  return length | 0
	}

	function SlowBuffer (length) {
	  if (+length != length) { // eslint-disable-line eqeqeq
	    length = 0;
	  }
	  return Buffer.alloc(+length)
	}

	Buffer.isBuffer = function isBuffer (b) {
	  return b != null && b._isBuffer === true &&
	    b !== Buffer.prototype // so Buffer.isBuffer(Buffer.prototype) will be false
	};

	Buffer.compare = function compare (a, b) {
	  if (isInstance(a, Uint8Array)) a = Buffer.from(a, a.offset, a.byteLength);
	  if (isInstance(b, Uint8Array)) b = Buffer.from(b, b.offset, b.byteLength);
	  if (!Buffer.isBuffer(a) || !Buffer.isBuffer(b)) {
	    throw new TypeError(
	      'The "buf1", "buf2" arguments must be one of type Buffer or Uint8Array'
	    )
	  }

	  if (a === b) return 0

	  var x = a.length;
	  var y = b.length;

	  for (var i = 0, len = Math.min(x, y); i < len; ++i) {
	    if (a[i] !== b[i]) {
	      x = a[i];
	      y = b[i];
	      break
	    }
	  }

	  if (x < y) return -1
	  if (y < x) return 1
	  return 0
	};

	Buffer.isEncoding = function isEncoding (encoding) {
	  switch (String(encoding).toLowerCase()) {
	    case 'hex':
	    case 'utf8':
	    case 'utf-8':
	    case 'ascii':
	    case 'latin1':
	    case 'binary':
	    case 'base64':
	    case 'ucs2':
	    case 'ucs-2':
	    case 'utf16le':
	    case 'utf-16le':
	      return true
	    default:
	      return false
	  }
	};

	Buffer.concat = function concat (list, length) {
	  if (!Array.isArray(list)) {
	    throw new TypeError('"list" argument must be an Array of Buffers')
	  }

	  if (list.length === 0) {
	    return Buffer.alloc(0)
	  }

	  var i;
	  if (length === undefined) {
	    length = 0;
	    for (i = 0; i < list.length; ++i) {
	      length += list[i].length;
	    }
	  }

	  var buffer = Buffer.allocUnsafe(length);
	  var pos = 0;
	  for (i = 0; i < list.length; ++i) {
	    var buf = list[i];
	    if (isInstance(buf, Uint8Array)) {
	      buf = Buffer.from(buf);
	    }
	    if (!Buffer.isBuffer(buf)) {
	      throw new TypeError('"list" argument must be an Array of Buffers')
	    }
	    buf.copy(buffer, pos);
	    pos += buf.length;
	  }
	  return buffer
	};

	function byteLength (string, encoding) {
	  if (Buffer.isBuffer(string)) {
	    return string.length
	  }
	  if (ArrayBuffer.isView(string) || isInstance(string, ArrayBuffer)) {
	    return string.byteLength
	  }
	  if (typeof string !== 'string') {
	    throw new TypeError(
	      'The "string" argument must be one of type string, Buffer, or ArrayBuffer. ' +
	      'Received type ' + typeof string
	    )
	  }

	  var len = string.length;
	  var mustMatch = (arguments.length > 2 && arguments[2] === true);
	  if (!mustMatch && len === 0) return 0

	  // Use a for loop to avoid recursion
	  var loweredCase = false;
	  for (;;) {
	    switch (encoding) {
	      case 'ascii':
	      case 'latin1':
	      case 'binary':
	        return len
	      case 'utf8':
	      case 'utf-8':
	        return utf8ToBytes(string).length
	      case 'ucs2':
	      case 'ucs-2':
	      case 'utf16le':
	      case 'utf-16le':
	        return len * 2
	      case 'hex':
	        return len >>> 1
	      case 'base64':
	        return base64ToBytes(string).length
	      default:
	        if (loweredCase) {
	          return mustMatch ? -1 : utf8ToBytes(string).length // assume utf8
	        }
	        encoding = ('' + encoding).toLowerCase();
	        loweredCase = true;
	    }
	  }
	}
	Buffer.byteLength = byteLength;

	function slowToString (encoding, start, end) {
	  var loweredCase = false;

	  // No need to verify that "this.length <= MAX_UINT32" since it's a read-only
	  // property of a typed array.

	  // This behaves neither like String nor Uint8Array in that we set start/end
	  // to their upper/lower bounds if the value passed is out of range.
	  // undefined is handled specially as per ECMA-262 6th Edition,
	  // Section 13.3.3.7 Runtime Semantics: KeyedBindingInitialization.
	  if (start === undefined || start < 0) {
	    start = 0;
	  }
	  // Return early if start > this.length. Done here to prevent potential uint32
	  // coercion fail below.
	  if (start > this.length) {
	    return ''
	  }

	  if (end === undefined || end > this.length) {
	    end = this.length;
	  }

	  if (end <= 0) {
	    return ''
	  }

	  // Force coersion to uint32. This will also coerce falsey/NaN values to 0.
	  end >>>= 0;
	  start >>>= 0;

	  if (end <= start) {
	    return ''
	  }

	  if (!encoding) encoding = 'utf8';

	  while (true) {
	    switch (encoding) {
	      case 'hex':
	        return hexSlice(this, start, end)

	      case 'utf8':
	      case 'utf-8':
	        return utf8Slice(this, start, end)

	      case 'ascii':
	        return asciiSlice(this, start, end)

	      case 'latin1':
	      case 'binary':
	        return latin1Slice(this, start, end)

	      case 'base64':
	        return base64Slice(this, start, end)

	      case 'ucs2':
	      case 'ucs-2':
	      case 'utf16le':
	      case 'utf-16le':
	        return utf16leSlice(this, start, end)

	      default:
	        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
	        encoding = (encoding + '').toLowerCase();
	        loweredCase = true;
	    }
	  }
	}

	// This property is used by `Buffer.isBuffer` (and the `is-buffer` npm package)
	// to detect a Buffer instance. It's not possible to use `instanceof Buffer`
	// reliably in a browserify context because there could be multiple different
	// copies of the 'buffer' package in use. This method works even for Buffer
	// instances that were created from another copy of the `buffer` package.
	// See: https://github.com/feross/buffer/issues/154
	Buffer.prototype._isBuffer = true;

	function swap (b, n, m) {
	  var i = b[n];
	  b[n] = b[m];
	  b[m] = i;
	}

	Buffer.prototype.swap16 = function swap16 () {
	  var len = this.length;
	  if (len % 2 !== 0) {
	    throw new RangeError('Buffer size must be a multiple of 16-bits')
	  }
	  for (var i = 0; i < len; i += 2) {
	    swap(this, i, i + 1);
	  }
	  return this
	};

	Buffer.prototype.swap32 = function swap32 () {
	  var len = this.length;
	  if (len % 4 !== 0) {
	    throw new RangeError('Buffer size must be a multiple of 32-bits')
	  }
	  for (var i = 0; i < len; i += 4) {
	    swap(this, i, i + 3);
	    swap(this, i + 1, i + 2);
	  }
	  return this
	};

	Buffer.prototype.swap64 = function swap64 () {
	  var len = this.length;
	  if (len % 8 !== 0) {
	    throw new RangeError('Buffer size must be a multiple of 64-bits')
	  }
	  for (var i = 0; i < len; i += 8) {
	    swap(this, i, i + 7);
	    swap(this, i + 1, i + 6);
	    swap(this, i + 2, i + 5);
	    swap(this, i + 3, i + 4);
	  }
	  return this
	};

	Buffer.prototype.toString = function toString () {
	  var length = this.length;
	  if (length === 0) return ''
	  if (arguments.length === 0) return utf8Slice(this, 0, length)
	  return slowToString.apply(this, arguments)
	};

	Buffer.prototype.toLocaleString = Buffer.prototype.toString;

	Buffer.prototype.equals = function equals (b) {
	  if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer')
	  if (this === b) return true
	  return Buffer.compare(this, b) === 0
	};

	Buffer.prototype.inspect = function inspect () {
	  var str = '';
	  var max = exports.INSPECT_MAX_BYTES;
	  str = this.toString('hex', 0, max).replace(/(.{2})/g, '$1 ').trim();
	  if (this.length > max) str += ' ... ';
	  return '<Buffer ' + str + '>'
	};
	if (customInspectSymbol) {
	  Buffer.prototype[customInspectSymbol] = Buffer.prototype.inspect;
	}

	Buffer.prototype.compare = function compare (target, start, end, thisStart, thisEnd) {
	  if (isInstance(target, Uint8Array)) {
	    target = Buffer.from(target, target.offset, target.byteLength);
	  }
	  if (!Buffer.isBuffer(target)) {
	    throw new TypeError(
	      'The "target" argument must be one of type Buffer or Uint8Array. ' +
	      'Received type ' + (typeof target)
	    )
	  }

	  if (start === undefined) {
	    start = 0;
	  }
	  if (end === undefined) {
	    end = target ? target.length : 0;
	  }
	  if (thisStart === undefined) {
	    thisStart = 0;
	  }
	  if (thisEnd === undefined) {
	    thisEnd = this.length;
	  }

	  if (start < 0 || end > target.length || thisStart < 0 || thisEnd > this.length) {
	    throw new RangeError('out of range index')
	  }

	  if (thisStart >= thisEnd && start >= end) {
	    return 0
	  }
	  if (thisStart >= thisEnd) {
	    return -1
	  }
	  if (start >= end) {
	    return 1
	  }

	  start >>>= 0;
	  end >>>= 0;
	  thisStart >>>= 0;
	  thisEnd >>>= 0;

	  if (this === target) return 0

	  var x = thisEnd - thisStart;
	  var y = end - start;
	  var len = Math.min(x, y);

	  var thisCopy = this.slice(thisStart, thisEnd);
	  var targetCopy = target.slice(start, end);

	  for (var i = 0; i < len; ++i) {
	    if (thisCopy[i] !== targetCopy[i]) {
	      x = thisCopy[i];
	      y = targetCopy[i];
	      break
	    }
	  }

	  if (x < y) return -1
	  if (y < x) return 1
	  return 0
	};

	// Finds either the first index of `val` in `buffer` at offset >= `byteOffset`,
	// OR the last index of `val` in `buffer` at offset <= `byteOffset`.
	//
	// Arguments:
	// - buffer - a Buffer to search
	// - val - a string, Buffer, or number
	// - byteOffset - an index into `buffer`; will be clamped to an int32
	// - encoding - an optional encoding, relevant is val is a string
	// - dir - true for indexOf, false for lastIndexOf
	function bidirectionalIndexOf (buffer, val, byteOffset, encoding, dir) {
	  // Empty buffer means no match
	  if (buffer.length === 0) return -1

	  // Normalize byteOffset
	  if (typeof byteOffset === 'string') {
	    encoding = byteOffset;
	    byteOffset = 0;
	  } else if (byteOffset > 0x7fffffff) {
	    byteOffset = 0x7fffffff;
	  } else if (byteOffset < -0x80000000) {
	    byteOffset = -0x80000000;
	  }
	  byteOffset = +byteOffset; // Coerce to Number.
	  if (numberIsNaN(byteOffset)) {
	    // byteOffset: it it's undefined, null, NaN, "foo", etc, search whole buffer
	    byteOffset = dir ? 0 : (buffer.length - 1);
	  }

	  // Normalize byteOffset: negative offsets start from the end of the buffer
	  if (byteOffset < 0) byteOffset = buffer.length + byteOffset;
	  if (byteOffset >= buffer.length) {
	    if (dir) return -1
	    else byteOffset = buffer.length - 1;
	  } else if (byteOffset < 0) {
	    if (dir) byteOffset = 0;
	    else return -1
	  }

	  // Normalize val
	  if (typeof val === 'string') {
	    val = Buffer.from(val, encoding);
	  }

	  // Finally, search either indexOf (if dir is true) or lastIndexOf
	  if (Buffer.isBuffer(val)) {
	    // Special case: looking for empty string/buffer always fails
	    if (val.length === 0) {
	      return -1
	    }
	    return arrayIndexOf(buffer, val, byteOffset, encoding, dir)
	  } else if (typeof val === 'number') {
	    val = val & 0xFF; // Search for a byte value [0-255]
	    if (typeof Uint8Array.prototype.indexOf === 'function') {
	      if (dir) {
	        return Uint8Array.prototype.indexOf.call(buffer, val, byteOffset)
	      } else {
	        return Uint8Array.prototype.lastIndexOf.call(buffer, val, byteOffset)
	      }
	    }
	    return arrayIndexOf(buffer, [val], byteOffset, encoding, dir)
	  }

	  throw new TypeError('val must be string, number or Buffer')
	}

	function arrayIndexOf (arr, val, byteOffset, encoding, dir) {
	  var indexSize = 1;
	  var arrLength = arr.length;
	  var valLength = val.length;

	  if (encoding !== undefined) {
	    encoding = String(encoding).toLowerCase();
	    if (encoding === 'ucs2' || encoding === 'ucs-2' ||
	        encoding === 'utf16le' || encoding === 'utf-16le') {
	      if (arr.length < 2 || val.length < 2) {
	        return -1
	      }
	      indexSize = 2;
	      arrLength /= 2;
	      valLength /= 2;
	      byteOffset /= 2;
	    }
	  }

	  function read (buf, i) {
	    if (indexSize === 1) {
	      return buf[i]
	    } else {
	      return buf.readUInt16BE(i * indexSize)
	    }
	  }

	  var i;
	  if (dir) {
	    var foundIndex = -1;
	    for (i = byteOffset; i < arrLength; i++) {
	      if (read(arr, i) === read(val, foundIndex === -1 ? 0 : i - foundIndex)) {
	        if (foundIndex === -1) foundIndex = i;
	        if (i - foundIndex + 1 === valLength) return foundIndex * indexSize
	      } else {
	        if (foundIndex !== -1) i -= i - foundIndex;
	        foundIndex = -1;
	      }
	    }
	  } else {
	    if (byteOffset + valLength > arrLength) byteOffset = arrLength - valLength;
	    for (i = byteOffset; i >= 0; i--) {
	      var found = true;
	      for (var j = 0; j < valLength; j++) {
	        if (read(arr, i + j) !== read(val, j)) {
	          found = false;
	          break
	        }
	      }
	      if (found) return i
	    }
	  }

	  return -1
	}

	Buffer.prototype.includes = function includes (val, byteOffset, encoding) {
	  return this.indexOf(val, byteOffset, encoding) !== -1
	};

	Buffer.prototype.indexOf = function indexOf (val, byteOffset, encoding) {
	  return bidirectionalIndexOf(this, val, byteOffset, encoding, true)
	};

	Buffer.prototype.lastIndexOf = function lastIndexOf (val, byteOffset, encoding) {
	  return bidirectionalIndexOf(this, val, byteOffset, encoding, false)
	};

	function hexWrite (buf, string, offset, length) {
	  offset = Number(offset) || 0;
	  var remaining = buf.length - offset;
	  if (!length) {
	    length = remaining;
	  } else {
	    length = Number(length);
	    if (length > remaining) {
	      length = remaining;
	    }
	  }

	  var strLen = string.length;

	  if (length > strLen / 2) {
	    length = strLen / 2;
	  }
	  for (var i = 0; i < length; ++i) {
	    var parsed = parseInt(string.substr(i * 2, 2), 16);
	    if (numberIsNaN(parsed)) return i
	    buf[offset + i] = parsed;
	  }
	  return i
	}

	function utf8Write (buf, string, offset, length) {
	  return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length)
	}

	function asciiWrite (buf, string, offset, length) {
	  return blitBuffer(asciiToBytes(string), buf, offset, length)
	}

	function latin1Write (buf, string, offset, length) {
	  return asciiWrite(buf, string, offset, length)
	}

	function base64Write (buf, string, offset, length) {
	  return blitBuffer(base64ToBytes(string), buf, offset, length)
	}

	function ucs2Write (buf, string, offset, length) {
	  return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length)
	}

	Buffer.prototype.write = function write (string, offset, length, encoding) {
	  // Buffer#write(string)
	  if (offset === undefined) {
	    encoding = 'utf8';
	    length = this.length;
	    offset = 0;
	  // Buffer#write(string, encoding)
	  } else if (length === undefined && typeof offset === 'string') {
	    encoding = offset;
	    length = this.length;
	    offset = 0;
	  // Buffer#write(string, offset[, length][, encoding])
	  } else if (isFinite(offset)) {
	    offset = offset >>> 0;
	    if (isFinite(length)) {
	      length = length >>> 0;
	      if (encoding === undefined) encoding = 'utf8';
	    } else {
	      encoding = length;
	      length = undefined;
	    }
	  } else {
	    throw new Error(
	      'Buffer.write(string, encoding, offset[, length]) is no longer supported'
	    )
	  }

	  var remaining = this.length - offset;
	  if (length === undefined || length > remaining) length = remaining;

	  if ((string.length > 0 && (length < 0 || offset < 0)) || offset > this.length) {
	    throw new RangeError('Attempt to write outside buffer bounds')
	  }

	  if (!encoding) encoding = 'utf8';

	  var loweredCase = false;
	  for (;;) {
	    switch (encoding) {
	      case 'hex':
	        return hexWrite(this, string, offset, length)

	      case 'utf8':
	      case 'utf-8':
	        return utf8Write(this, string, offset, length)

	      case 'ascii':
	        return asciiWrite(this, string, offset, length)

	      case 'latin1':
	      case 'binary':
	        return latin1Write(this, string, offset, length)

	      case 'base64':
	        // Warning: maxLength not taken into account in base64Write
	        return base64Write(this, string, offset, length)

	      case 'ucs2':
	      case 'ucs-2':
	      case 'utf16le':
	      case 'utf-16le':
	        return ucs2Write(this, string, offset, length)

	      default:
	        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
	        encoding = ('' + encoding).toLowerCase();
	        loweredCase = true;
	    }
	  }
	};

	Buffer.prototype.toJSON = function toJSON () {
	  return {
	    type: 'Buffer',
	    data: Array.prototype.slice.call(this._arr || this, 0)
	  }
	};

	function base64Slice (buf, start, end) {
	  if (start === 0 && end === buf.length) {
	    return base64.fromByteArray(buf)
	  } else {
	    return base64.fromByteArray(buf.slice(start, end))
	  }
	}

	function utf8Slice (buf, start, end) {
	  end = Math.min(buf.length, end);
	  var res = [];

	  var i = start;
	  while (i < end) {
	    var firstByte = buf[i];
	    var codePoint = null;
	    var bytesPerSequence = (firstByte > 0xEF) ? 4
	      : (firstByte > 0xDF) ? 3
	        : (firstByte > 0xBF) ? 2
	          : 1;

	    if (i + bytesPerSequence <= end) {
	      var secondByte, thirdByte, fourthByte, tempCodePoint;

	      switch (bytesPerSequence) {
	        case 1:
	          if (firstByte < 0x80) {
	            codePoint = firstByte;
	          }
	          break
	        case 2:
	          secondByte = buf[i + 1];
	          if ((secondByte & 0xC0) === 0x80) {
	            tempCodePoint = (firstByte & 0x1F) << 0x6 | (secondByte & 0x3F);
	            if (tempCodePoint > 0x7F) {
	              codePoint = tempCodePoint;
	            }
	          }
	          break
	        case 3:
	          secondByte = buf[i + 1];
	          thirdByte = buf[i + 2];
	          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80) {
	            tempCodePoint = (firstByte & 0xF) << 0xC | (secondByte & 0x3F) << 0x6 | (thirdByte & 0x3F);
	            if (tempCodePoint > 0x7FF && (tempCodePoint < 0xD800 || tempCodePoint > 0xDFFF)) {
	              codePoint = tempCodePoint;
	            }
	          }
	          break
	        case 4:
	          secondByte = buf[i + 1];
	          thirdByte = buf[i + 2];
	          fourthByte = buf[i + 3];
	          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80 && (fourthByte & 0xC0) === 0x80) {
	            tempCodePoint = (firstByte & 0xF) << 0x12 | (secondByte & 0x3F) << 0xC | (thirdByte & 0x3F) << 0x6 | (fourthByte & 0x3F);
	            if (tempCodePoint > 0xFFFF && tempCodePoint < 0x110000) {
	              codePoint = tempCodePoint;
	            }
	          }
	      }
	    }

	    if (codePoint === null) {
	      // we did not generate a valid codePoint so insert a
	      // replacement char (U+FFFD) and advance only 1 byte
	      codePoint = 0xFFFD;
	      bytesPerSequence = 1;
	    } else if (codePoint > 0xFFFF) {
	      // encode to utf16 (surrogate pair dance)
	      codePoint -= 0x10000;
	      res.push(codePoint >>> 10 & 0x3FF | 0xD800);
	      codePoint = 0xDC00 | codePoint & 0x3FF;
	    }

	    res.push(codePoint);
	    i += bytesPerSequence;
	  }

	  return decodeCodePointsArray(res)
	}

	// Based on http://stackoverflow.com/a/22747272/680742, the browser with
	// the lowest limit is Chrome, with 0x10000 args.
	// We go 1 magnitude less, for safety
	var MAX_ARGUMENTS_LENGTH = 0x1000;

	function decodeCodePointsArray (codePoints) {
	  var len = codePoints.length;
	  if (len <= MAX_ARGUMENTS_LENGTH) {
	    return String.fromCharCode.apply(String, codePoints) // avoid extra slice()
	  }

	  // Decode in chunks to avoid "call stack size exceeded".
	  var res = '';
	  var i = 0;
	  while (i < len) {
	    res += String.fromCharCode.apply(
	      String,
	      codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH)
	    );
	  }
	  return res
	}

	function asciiSlice (buf, start, end) {
	  var ret = '';
	  end = Math.min(buf.length, end);

	  for (var i = start; i < end; ++i) {
	    ret += String.fromCharCode(buf[i] & 0x7F);
	  }
	  return ret
	}

	function latin1Slice (buf, start, end) {
	  var ret = '';
	  end = Math.min(buf.length, end);

	  for (var i = start; i < end; ++i) {
	    ret += String.fromCharCode(buf[i]);
	  }
	  return ret
	}

	function hexSlice (buf, start, end) {
	  var len = buf.length;

	  if (!start || start < 0) start = 0;
	  if (!end || end < 0 || end > len) end = len;

	  var out = '';
	  for (var i = start; i < end; ++i) {
	    out += hexSliceLookupTable[buf[i]];
	  }
	  return out
	}

	function utf16leSlice (buf, start, end) {
	  var bytes = buf.slice(start, end);
	  var res = '';
	  for (var i = 0; i < bytes.length; i += 2) {
	    res += String.fromCharCode(bytes[i] + (bytes[i + 1] * 256));
	  }
	  return res
	}

	Buffer.prototype.slice = function slice (start, end) {
	  var len = this.length;
	  start = ~~start;
	  end = end === undefined ? len : ~~end;

	  if (start < 0) {
	    start += len;
	    if (start < 0) start = 0;
	  } else if (start > len) {
	    start = len;
	  }

	  if (end < 0) {
	    end += len;
	    if (end < 0) end = 0;
	  } else if (end > len) {
	    end = len;
	  }

	  if (end < start) end = start;

	  var newBuf = this.subarray(start, end);
	  // Return an augmented `Uint8Array` instance
	  Object.setPrototypeOf(newBuf, Buffer.prototype);

	  return newBuf
	};

	/*
	 * Need to make sure that buffer isn't trying to write out of bounds.
	 */
	function checkOffset (offset, ext, length) {
	  if ((offset % 1) !== 0 || offset < 0) throw new RangeError('offset is not uint')
	  if (offset + ext > length) throw new RangeError('Trying to access beyond buffer length')
	}

	Buffer.prototype.readUIntLE = function readUIntLE (offset, byteLength, noAssert) {
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) checkOffset(offset, byteLength, this.length);

	  var val = this[offset];
	  var mul = 1;
	  var i = 0;
	  while (++i < byteLength && (mul *= 0x100)) {
	    val += this[offset + i] * mul;
	  }

	  return val
	};

	Buffer.prototype.readUIntBE = function readUIntBE (offset, byteLength, noAssert) {
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) {
	    checkOffset(offset, byteLength, this.length);
	  }

	  var val = this[offset + --byteLength];
	  var mul = 1;
	  while (byteLength > 0 && (mul *= 0x100)) {
	    val += this[offset + --byteLength] * mul;
	  }

	  return val
	};

	Buffer.prototype.readUInt8 = function readUInt8 (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 1, this.length);
	  return this[offset]
	};

	Buffer.prototype.readUInt16LE = function readUInt16LE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 2, this.length);
	  return this[offset] | (this[offset + 1] << 8)
	};

	Buffer.prototype.readUInt16BE = function readUInt16BE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 2, this.length);
	  return (this[offset] << 8) | this[offset + 1]
	};

	Buffer.prototype.readUInt32LE = function readUInt32LE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);

	  return ((this[offset]) |
	      (this[offset + 1] << 8) |
	      (this[offset + 2] << 16)) +
	      (this[offset + 3] * 0x1000000)
	};

	Buffer.prototype.readUInt32BE = function readUInt32BE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);

	  return (this[offset] * 0x1000000) +
	    ((this[offset + 1] << 16) |
	    (this[offset + 2] << 8) |
	    this[offset + 3])
	};

	Buffer.prototype.readIntLE = function readIntLE (offset, byteLength, noAssert) {
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) checkOffset(offset, byteLength, this.length);

	  var val = this[offset];
	  var mul = 1;
	  var i = 0;
	  while (++i < byteLength && (mul *= 0x100)) {
	    val += this[offset + i] * mul;
	  }
	  mul *= 0x80;

	  if (val >= mul) val -= Math.pow(2, 8 * byteLength);

	  return val
	};

	Buffer.prototype.readIntBE = function readIntBE (offset, byteLength, noAssert) {
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) checkOffset(offset, byteLength, this.length);

	  var i = byteLength;
	  var mul = 1;
	  var val = this[offset + --i];
	  while (i > 0 && (mul *= 0x100)) {
	    val += this[offset + --i] * mul;
	  }
	  mul *= 0x80;

	  if (val >= mul) val -= Math.pow(2, 8 * byteLength);

	  return val
	};

	Buffer.prototype.readInt8 = function readInt8 (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 1, this.length);
	  if (!(this[offset] & 0x80)) return (this[offset])
	  return ((0xff - this[offset] + 1) * -1)
	};

	Buffer.prototype.readInt16LE = function readInt16LE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 2, this.length);
	  var val = this[offset] | (this[offset + 1] << 8);
	  return (val & 0x8000) ? val | 0xFFFF0000 : val
	};

	Buffer.prototype.readInt16BE = function readInt16BE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 2, this.length);
	  var val = this[offset + 1] | (this[offset] << 8);
	  return (val & 0x8000) ? val | 0xFFFF0000 : val
	};

	Buffer.prototype.readInt32LE = function readInt32LE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);

	  return (this[offset]) |
	    (this[offset + 1] << 8) |
	    (this[offset + 2] << 16) |
	    (this[offset + 3] << 24)
	};

	Buffer.prototype.readInt32BE = function readInt32BE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);

	  return (this[offset] << 24) |
	    (this[offset + 1] << 16) |
	    (this[offset + 2] << 8) |
	    (this[offset + 3])
	};

	Buffer.prototype.readFloatLE = function readFloatLE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);
	  return ieee754.read(this, offset, true, 23, 4)
	};

	Buffer.prototype.readFloatBE = function readFloatBE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 4, this.length);
	  return ieee754.read(this, offset, false, 23, 4)
	};

	Buffer.prototype.readDoubleLE = function readDoubleLE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 8, this.length);
	  return ieee754.read(this, offset, true, 52, 8)
	};

	Buffer.prototype.readDoubleBE = function readDoubleBE (offset, noAssert) {
	  offset = offset >>> 0;
	  if (!noAssert) checkOffset(offset, 8, this.length);
	  return ieee754.read(this, offset, false, 52, 8)
	};

	function checkInt (buf, value, offset, ext, max, min) {
	  if (!Buffer.isBuffer(buf)) throw new TypeError('"buffer" argument must be a Buffer instance')
	  if (value > max || value < min) throw new RangeError('"value" argument is out of bounds')
	  if (offset + ext > buf.length) throw new RangeError('Index out of range')
	}

	Buffer.prototype.writeUIntLE = function writeUIntLE (value, offset, byteLength, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) {
	    var maxBytes = Math.pow(2, 8 * byteLength) - 1;
	    checkInt(this, value, offset, byteLength, maxBytes, 0);
	  }

	  var mul = 1;
	  var i = 0;
	  this[offset] = value & 0xFF;
	  while (++i < byteLength && (mul *= 0x100)) {
	    this[offset + i] = (value / mul) & 0xFF;
	  }

	  return offset + byteLength
	};

	Buffer.prototype.writeUIntBE = function writeUIntBE (value, offset, byteLength, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  byteLength = byteLength >>> 0;
	  if (!noAssert) {
	    var maxBytes = Math.pow(2, 8 * byteLength) - 1;
	    checkInt(this, value, offset, byteLength, maxBytes, 0);
	  }

	  var i = byteLength - 1;
	  var mul = 1;
	  this[offset + i] = value & 0xFF;
	  while (--i >= 0 && (mul *= 0x100)) {
	    this[offset + i] = (value / mul) & 0xFF;
	  }

	  return offset + byteLength
	};

	Buffer.prototype.writeUInt8 = function writeUInt8 (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 1, 0xff, 0);
	  this[offset] = (value & 0xff);
	  return offset + 1
	};

	Buffer.prototype.writeUInt16LE = function writeUInt16LE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0);
	  this[offset] = (value & 0xff);
	  this[offset + 1] = (value >>> 8);
	  return offset + 2
	};

	Buffer.prototype.writeUInt16BE = function writeUInt16BE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0);
	  this[offset] = (value >>> 8);
	  this[offset + 1] = (value & 0xff);
	  return offset + 2
	};

	Buffer.prototype.writeUInt32LE = function writeUInt32LE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0);
	  this[offset + 3] = (value >>> 24);
	  this[offset + 2] = (value >>> 16);
	  this[offset + 1] = (value >>> 8);
	  this[offset] = (value & 0xff);
	  return offset + 4
	};

	Buffer.prototype.writeUInt32BE = function writeUInt32BE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0);
	  this[offset] = (value >>> 24);
	  this[offset + 1] = (value >>> 16);
	  this[offset + 2] = (value >>> 8);
	  this[offset + 3] = (value & 0xff);
	  return offset + 4
	};

	Buffer.prototype.writeIntLE = function writeIntLE (value, offset, byteLength, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) {
	    var limit = Math.pow(2, (8 * byteLength) - 1);

	    checkInt(this, value, offset, byteLength, limit - 1, -limit);
	  }

	  var i = 0;
	  var mul = 1;
	  var sub = 0;
	  this[offset] = value & 0xFF;
	  while (++i < byteLength && (mul *= 0x100)) {
	    if (value < 0 && sub === 0 && this[offset + i - 1] !== 0) {
	      sub = 1;
	    }
	    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF;
	  }

	  return offset + byteLength
	};

	Buffer.prototype.writeIntBE = function writeIntBE (value, offset, byteLength, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) {
	    var limit = Math.pow(2, (8 * byteLength) - 1);

	    checkInt(this, value, offset, byteLength, limit - 1, -limit);
	  }

	  var i = byteLength - 1;
	  var mul = 1;
	  var sub = 0;
	  this[offset + i] = value & 0xFF;
	  while (--i >= 0 && (mul *= 0x100)) {
	    if (value < 0 && sub === 0 && this[offset + i + 1] !== 0) {
	      sub = 1;
	    }
	    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF;
	  }

	  return offset + byteLength
	};

	Buffer.prototype.writeInt8 = function writeInt8 (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 1, 0x7f, -0x80);
	  if (value < 0) value = 0xff + value + 1;
	  this[offset] = (value & 0xff);
	  return offset + 1
	};

	Buffer.prototype.writeInt16LE = function writeInt16LE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000);
	  this[offset] = (value & 0xff);
	  this[offset + 1] = (value >>> 8);
	  return offset + 2
	};

	Buffer.prototype.writeInt16BE = function writeInt16BE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000);
	  this[offset] = (value >>> 8);
	  this[offset + 1] = (value & 0xff);
	  return offset + 2
	};

	Buffer.prototype.writeInt32LE = function writeInt32LE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000);
	  this[offset] = (value & 0xff);
	  this[offset + 1] = (value >>> 8);
	  this[offset + 2] = (value >>> 16);
	  this[offset + 3] = (value >>> 24);
	  return offset + 4
	};

	Buffer.prototype.writeInt32BE = function writeInt32BE (value, offset, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000);
	  if (value < 0) value = 0xffffffff + value + 1;
	  this[offset] = (value >>> 24);
	  this[offset + 1] = (value >>> 16);
	  this[offset + 2] = (value >>> 8);
	  this[offset + 3] = (value & 0xff);
	  return offset + 4
	};

	function checkIEEE754 (buf, value, offset, ext, max, min) {
	  if (offset + ext > buf.length) throw new RangeError('Index out of range')
	  if (offset < 0) throw new RangeError('Index out of range')
	}

	function writeFloat (buf, value, offset, littleEndian, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) {
	    checkIEEE754(buf, value, offset, 4);
	  }
	  ieee754.write(buf, value, offset, littleEndian, 23, 4);
	  return offset + 4
	}

	Buffer.prototype.writeFloatLE = function writeFloatLE (value, offset, noAssert) {
	  return writeFloat(this, value, offset, true, noAssert)
	};

	Buffer.prototype.writeFloatBE = function writeFloatBE (value, offset, noAssert) {
	  return writeFloat(this, value, offset, false, noAssert)
	};

	function writeDouble (buf, value, offset, littleEndian, noAssert) {
	  value = +value;
	  offset = offset >>> 0;
	  if (!noAssert) {
	    checkIEEE754(buf, value, offset, 8);
	  }
	  ieee754.write(buf, value, offset, littleEndian, 52, 8);
	  return offset + 8
	}

	Buffer.prototype.writeDoubleLE = function writeDoubleLE (value, offset, noAssert) {
	  return writeDouble(this, value, offset, true, noAssert)
	};

	Buffer.prototype.writeDoubleBE = function writeDoubleBE (value, offset, noAssert) {
	  return writeDouble(this, value, offset, false, noAssert)
	};

	// copy(targetBuffer, targetStart=0, sourceStart=0, sourceEnd=buffer.length)
	Buffer.prototype.copy = function copy (target, targetStart, start, end) {
	  if (!Buffer.isBuffer(target)) throw new TypeError('argument should be a Buffer')
	  if (!start) start = 0;
	  if (!end && end !== 0) end = this.length;
	  if (targetStart >= target.length) targetStart = target.length;
	  if (!targetStart) targetStart = 0;
	  if (end > 0 && end < start) end = start;

	  // Copy 0 bytes; we're done
	  if (end === start) return 0
	  if (target.length === 0 || this.length === 0) return 0

	  // Fatal error conditions
	  if (targetStart < 0) {
	    throw new RangeError('targetStart out of bounds')
	  }
	  if (start < 0 || start >= this.length) throw new RangeError('Index out of range')
	  if (end < 0) throw new RangeError('sourceEnd out of bounds')

	  // Are we oob?
	  if (end > this.length) end = this.length;
	  if (target.length - targetStart < end - start) {
	    end = target.length - targetStart + start;
	  }

	  var len = end - start;

	  if (this === target && typeof Uint8Array.prototype.copyWithin === 'function') {
	    // Use built-in when available, missing from IE11
	    this.copyWithin(targetStart, start, end);
	  } else if (this === target && start < targetStart && targetStart < end) {
	    // descending copy from end
	    for (var i = len - 1; i >= 0; --i) {
	      target[i + targetStart] = this[i + start];
	    }
	  } else {
	    Uint8Array.prototype.set.call(
	      target,
	      this.subarray(start, end),
	      targetStart
	    );
	  }

	  return len
	};

	// Usage:
	//    buffer.fill(number[, offset[, end]])
	//    buffer.fill(buffer[, offset[, end]])
	//    buffer.fill(string[, offset[, end]][, encoding])
	Buffer.prototype.fill = function fill (val, start, end, encoding) {
	  // Handle string cases:
	  if (typeof val === 'string') {
	    if (typeof start === 'string') {
	      encoding = start;
	      start = 0;
	      end = this.length;
	    } else if (typeof end === 'string') {
	      encoding = end;
	      end = this.length;
	    }
	    if (encoding !== undefined && typeof encoding !== 'string') {
	      throw new TypeError('encoding must be a string')
	    }
	    if (typeof encoding === 'string' && !Buffer.isEncoding(encoding)) {
	      throw new TypeError('Unknown encoding: ' + encoding)
	    }
	    if (val.length === 1) {
	      var code = val.charCodeAt(0);
	      if ((encoding === 'utf8' && code < 128) ||
	          encoding === 'latin1') {
	        // Fast path: If `val` fits into a single byte, use that numeric value.
	        val = code;
	      }
	    }
	  } else if (typeof val === 'number') {
	    val = val & 255;
	  } else if (typeof val === 'boolean') {
	    val = Number(val);
	  }

	  // Invalid ranges are not set to a default, so can range check early.
	  if (start < 0 || this.length < start || this.length < end) {
	    throw new RangeError('Out of range index')
	  }

	  if (end <= start) {
	    return this
	  }

	  start = start >>> 0;
	  end = end === undefined ? this.length : end >>> 0;

	  if (!val) val = 0;

	  var i;
	  if (typeof val === 'number') {
	    for (i = start; i < end; ++i) {
	      this[i] = val;
	    }
	  } else {
	    var bytes = Buffer.isBuffer(val)
	      ? val
	      : Buffer.from(val, encoding);
	    var len = bytes.length;
	    if (len === 0) {
	      throw new TypeError('The value "' + val +
	        '" is invalid for argument "value"')
	    }
	    for (i = 0; i < end - start; ++i) {
	      this[i + start] = bytes[i % len];
	    }
	  }

	  return this
	};

	// HELPER FUNCTIONS
	// ================

	var INVALID_BASE64_RE = /[^+/0-9A-Za-z-_]/g;

	function base64clean (str) {
	  // Node takes equal signs as end of the Base64 encoding
	  str = str.split('=')[0];
	  // Node strips out invalid characters like \n and \t from the string, base64-js does not
	  str = str.trim().replace(INVALID_BASE64_RE, '');
	  // Node converts strings with length < 2 to ''
	  if (str.length < 2) return ''
	  // Node allows for non-padded base64 strings (missing trailing ===), base64-js does not
	  while (str.length % 4 !== 0) {
	    str = str + '=';
	  }
	  return str
	}

	function utf8ToBytes (string, units) {
	  units = units || Infinity;
	  var codePoint;
	  var length = string.length;
	  var leadSurrogate = null;
	  var bytes = [];

	  for (var i = 0; i < length; ++i) {
	    codePoint = string.charCodeAt(i);

	    // is surrogate component
	    if (codePoint > 0xD7FF && codePoint < 0xE000) {
	      // last char was a lead
	      if (!leadSurrogate) {
	        // no lead yet
	        if (codePoint > 0xDBFF) {
	          // unexpected trail
	          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	          continue
	        } else if (i + 1 === length) {
	          // unpaired lead
	          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	          continue
	        }

	        // valid lead
	        leadSurrogate = codePoint;

	        continue
	      }

	      // 2 leads in a row
	      if (codePoint < 0xDC00) {
	        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	        leadSurrogate = codePoint;
	        continue
	      }

	      // valid surrogate pair
	      codePoint = (leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00) + 0x10000;
	    } else if (leadSurrogate) {
	      // valid bmp char, but last char was a lead
	      if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
	    }

	    leadSurrogate = null;

	    // encode utf8
	    if (codePoint < 0x80) {
	      if ((units -= 1) < 0) break
	      bytes.push(codePoint);
	    } else if (codePoint < 0x800) {
	      if ((units -= 2) < 0) break
	      bytes.push(
	        codePoint >> 0x6 | 0xC0,
	        codePoint & 0x3F | 0x80
	      );
	    } else if (codePoint < 0x10000) {
	      if ((units -= 3) < 0) break
	      bytes.push(
	        codePoint >> 0xC | 0xE0,
	        codePoint >> 0x6 & 0x3F | 0x80,
	        codePoint & 0x3F | 0x80
	      );
	    } else if (codePoint < 0x110000) {
	      if ((units -= 4) < 0) break
	      bytes.push(
	        codePoint >> 0x12 | 0xF0,
	        codePoint >> 0xC & 0x3F | 0x80,
	        codePoint >> 0x6 & 0x3F | 0x80,
	        codePoint & 0x3F | 0x80
	      );
	    } else {
	      throw new Error('Invalid code point')
	    }
	  }

	  return bytes
	}

	function asciiToBytes (str) {
	  var byteArray = [];
	  for (var i = 0; i < str.length; ++i) {
	    // Node's code seems to be doing this and not & 0x7F..
	    byteArray.push(str.charCodeAt(i) & 0xFF);
	  }
	  return byteArray
	}

	function utf16leToBytes (str, units) {
	  var c, hi, lo;
	  var byteArray = [];
	  for (var i = 0; i < str.length; ++i) {
	    if ((units -= 2) < 0) break

	    c = str.charCodeAt(i);
	    hi = c >> 8;
	    lo = c % 256;
	    byteArray.push(lo);
	    byteArray.push(hi);
	  }

	  return byteArray
	}

	function base64ToBytes (str) {
	  return base64.toByteArray(base64clean(str))
	}

	function blitBuffer (src, dst, offset, length) {
	  for (var i = 0; i < length; ++i) {
	    if ((i + offset >= dst.length) || (i >= src.length)) break
	    dst[i + offset] = src[i];
	  }
	  return i
	}

	// ArrayBuffer or Uint8Array objects from other contexts (i.e. iframes) do not pass
	// the `instanceof` check but they should be treated as of that type.
	// See: https://github.com/feross/buffer/issues/166
	function isInstance (obj, type) {
	  return obj instanceof type ||
	    (obj != null && obj.constructor != null && obj.constructor.name != null &&
	      obj.constructor.name === type.name)
	}
	function numberIsNaN (obj) {
	  // For IE11 support
	  return obj !== obj // eslint-disable-line no-self-compare
	}

	// Create lookup table for `toString('hex')`
	// See: https://github.com/feross/buffer/issues/219
	var hexSliceLookupTable = (function () {
	  var alphabet = '0123456789abcdef';
	  var table = new Array(256);
	  for (var i = 0; i < 16; ++i) {
	    var i16 = i * 16;
	    for (var j = 0; j < 16; ++j) {
	      table[i16 + j] = alphabet[i] + alphabet[j];
	    }
	  }
	  return table
	})();

	},{"base64-js":29,"ieee754":32}],31:[function(require,module,exports){

	/******************************************************************************
	 * Created 2008-08-19.
	 *
	 * Dijkstra path-finding functions. Adapted from the Dijkstar Python project.
	 *
	 * Copyright (C) 2008
	 *   Wyatt Baldwin <self@wyattbaldwin.com>
	 *   All rights reserved
	 *
	 * Licensed under the MIT license.
	 *
	 *   http://www.opensource.org/licenses/mit-license.php
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 *****************************************************************************/
	var dijkstra = {
	  single_source_shortest_paths: function(graph, s, d) {
	    // Predecessor map for each node that has been encountered.
	    // node ID => predecessor node ID
	    var predecessors = {};

	    // Costs of shortest paths from s to all nodes encountered.
	    // node ID => cost
	    var costs = {};
	    costs[s] = 0;

	    // Costs of shortest paths from s to all nodes encountered; differs from
	    // `costs` in that it provides easy access to the node that currently has
	    // the known shortest path from s.
	    // XXX: Do we actually need both `costs` and `open`?
	    var open = dijkstra.PriorityQueue.make();
	    open.push(s, 0);

	    var closest,
	        u, v,
	        cost_of_s_to_u,
	        adjacent_nodes,
	        cost_of_e,
	        cost_of_s_to_u_plus_cost_of_e,
	        cost_of_s_to_v,
	        first_visit;
	    while (!open.empty()) {
	      // In the nodes remaining in graph that have a known cost from s,
	      // find the node, u, that currently has the shortest path from s.
	      closest = open.pop();
	      u = closest.value;
	      cost_of_s_to_u = closest.cost;

	      // Get nodes adjacent to u...
	      adjacent_nodes = graph[u] || {};

	      // ...and explore the edges that connect u to those nodes, updating
	      // the cost of the shortest paths to any or all of those nodes as
	      // necessary. v is the node across the current edge from u.
	      for (v in adjacent_nodes) {
	        if (adjacent_nodes.hasOwnProperty(v)) {
	          // Get the cost of the edge running from u to v.
	          cost_of_e = adjacent_nodes[v];

	          // Cost of s to u plus the cost of u to v across e--this is *a*
	          // cost from s to v that may or may not be less than the current
	          // known cost to v.
	          cost_of_s_to_u_plus_cost_of_e = cost_of_s_to_u + cost_of_e;

	          // If we haven't visited v yet OR if the current known cost from s to
	          // v is greater than the new cost we just found (cost of s to u plus
	          // cost of u to v across e), update v's cost in the cost list and
	          // update v's predecessor in the predecessor list (it's now u).
	          cost_of_s_to_v = costs[v];
	          first_visit = (typeof costs[v] === 'undefined');
	          if (first_visit || cost_of_s_to_v > cost_of_s_to_u_plus_cost_of_e) {
	            costs[v] = cost_of_s_to_u_plus_cost_of_e;
	            open.push(v, cost_of_s_to_u_plus_cost_of_e);
	            predecessors[v] = u;
	          }
	        }
	      }
	    }

	    if (typeof d !== 'undefined' && typeof costs[d] === 'undefined') {
	      var msg = ['Could not find a path from ', s, ' to ', d, '.'].join('');
	      throw new Error(msg);
	    }

	    return predecessors;
	  },

	  extract_shortest_path_from_predecessor_list: function(predecessors, d) {
	    var nodes = [];
	    var u = d;
	    var predecessor;
	    while (u) {
	      nodes.push(u);
	      predecessor = predecessors[u];
	      u = predecessors[u];
	    }
	    nodes.reverse();
	    return nodes;
	  },

	  find_path: function(graph, s, d) {
	    var predecessors = dijkstra.single_source_shortest_paths(graph, s, d);
	    return dijkstra.extract_shortest_path_from_predecessor_list(
	      predecessors, d);
	  },

	  /**
	   * A very naive priority queue implementation.
	   */
	  PriorityQueue: {
	    make: function (opts) {
	      var T = dijkstra.PriorityQueue,
	          t = {},
	          key;
	      opts = opts || {};
	      for (key in T) {
	        if (T.hasOwnProperty(key)) {
	          t[key] = T[key];
	        }
	      }
	      t.queue = [];
	      t.sorter = opts.sorter || T.default_sorter;
	      return t;
	    },

	    default_sorter: function (a, b) {
	      return a.cost - b.cost;
	    },

	    /**
	     * Add a new item to the queue and ensure the highest priority element
	     * is at the front of the queue.
	     */
	    push: function (value, cost) {
	      var item = {value: value, cost: cost};
	      this.queue.push(item);
	      this.queue.sort(this.sorter);
	    },

	    /**
	     * Return the highest priority element in the queue.
	     */
	    pop: function () {
	      return this.queue.shift();
	    },

	    empty: function () {
	      return this.queue.length === 0;
	    }
	  }
	};


	// node.js module exports
	if (typeof module !== 'undefined') {
	  module.exports = dijkstra;
	}

	},{}],32:[function(require,module,exports){
	exports.read = function (buffer, offset, isLE, mLen, nBytes) {
	  var e, m;
	  var eLen = (nBytes * 8) - mLen - 1;
	  var eMax = (1 << eLen) - 1;
	  var eBias = eMax >> 1;
	  var nBits = -7;
	  var i = isLE ? (nBytes - 1) : 0;
	  var d = isLE ? -1 : 1;
	  var s = buffer[offset + i];

	  i += d;

	  e = s & ((1 << (-nBits)) - 1);
	  s >>= (-nBits);
	  nBits += eLen;
	  for (; nBits > 0; e = (e * 256) + buffer[offset + i], i += d, nBits -= 8) {}

	  m = e & ((1 << (-nBits)) - 1);
	  e >>= (-nBits);
	  nBits += mLen;
	  for (; nBits > 0; m = (m * 256) + buffer[offset + i], i += d, nBits -= 8) {}

	  if (e === 0) {
	    e = 1 - eBias;
	  } else if (e === eMax) {
	    return m ? NaN : ((s ? -1 : 1) * Infinity)
	  } else {
	    m = m + Math.pow(2, mLen);
	    e = e - eBias;
	  }
	  return (s ? -1 : 1) * m * Math.pow(2, e - mLen)
	};

	exports.write = function (buffer, value, offset, isLE, mLen, nBytes) {
	  var e, m, c;
	  var eLen = (nBytes * 8) - mLen - 1;
	  var eMax = (1 << eLen) - 1;
	  var eBias = eMax >> 1;
	  var rt = (mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0);
	  var i = isLE ? 0 : (nBytes - 1);
	  var d = isLE ? 1 : -1;
	  var s = value < 0 || (value === 0 && 1 / value < 0) ? 1 : 0;

	  value = Math.abs(value);

	  if (isNaN(value) || value === Infinity) {
	    m = isNaN(value) ? 1 : 0;
	    e = eMax;
	  } else {
	    e = Math.floor(Math.log(value) / Math.LN2);
	    if (value * (c = Math.pow(2, -e)) < 1) {
	      e--;
	      c *= 2;
	    }
	    if (e + eBias >= 1) {
	      value += rt / c;
	    } else {
	      value += rt * Math.pow(2, 1 - eBias);
	    }
	    if (value * c >= 2) {
	      e++;
	      c /= 2;
	    }

	    if (e + eBias >= eMax) {
	      m = 0;
	      e = eMax;
	    } else if (e + eBias >= 1) {
	      m = ((value * c) - 1) * Math.pow(2, mLen);
	      e = e + eBias;
	    } else {
	      m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen);
	      e = 0;
	    }
	  }

	  for (; mLen >= 8; buffer[offset + i] = m & 0xff, i += d, m /= 256, mLen -= 8) {}

	  e = (e << mLen) | m;
	  eLen += mLen;
	  for (; eLen > 0; buffer[offset + i] = e & 0xff, i += d, e /= 256, eLen -= 8) {}

	  buffer[offset + i - d] |= s * 128;
	};

	},{}],33:[function(require,module,exports){
	var toString = {}.toString;

	module.exports = Array.isArray || function (arr) {
	  return toString.call(arr) == '[object Array]';
	};

	},{}]},{},[24])(24)
	});


	});

	var index = {
	  name: 'qrcode',
	  props: {
	    /**
	     * The value of the QR code.
	     */
	    value: null,

	    /**
	     * The options for the QR code generator.
	     * {@link https://github.com/soldair/node-qrcode#qr-code-options}
	     */
	    options: Object,

	    /**
	     * The tag name of the component's root element.
	     */
	    tag: {
	      type: String,
	      default: 'canvas'
	    }
	  },
	  render: function render(createElement) {
	    return createElement(this.tag, this.$slots.default);
	  },
	  watch: {
	    $props: {
	      deep: true,
	      immediate: true,

	      /**
	       * Update the QR code when props changed.
	       */
	      handler: function handler() {
	        if (this.$el) {
	          this.generate();
	        }
	      }
	    }
	  },
	  methods: {
	    /**
	     * Generate QR code.
	     */
	    generate: function generate() {
	      var _this = this;

	      var options = this.options,
	          tag = this.tag;
	      var value = String(this.value);

	      if (tag === 'canvas') {
	        qrcode.toCanvas(this.$el, value, options, function (error) {
	          /* istanbul ignore if */
	          if (error) {
	            throw error;
	          }
	        });
	      } else if (tag === 'img') {
	        qrcode.toDataURL(value, options, function (error, url) {
	          /* istanbul ignore if */
	          if (error) {
	            throw error;
	          }

	          _this.$el.src = url;
	        });
	      } else {
	        qrcode.toString(value, options, function (error, string) {
	          /* istanbul ignore if */
	          if (error) {
	            throw error;
	          }

	          _this.$el.innerHTML = string;
	        });
	      }
	    }
	  },
	  mounted: function mounted() {
	    this.generate();
	  }
	};

	return index;

})));


/***/ }),

/***/ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js":
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/lib/SharePermissionsToolBox.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ATOMIC_PERMISSIONS: () => (/* binding */ ATOMIC_PERMISSIONS),
/* harmony export */   BUNDLED_PERMISSIONS: () => (/* binding */ BUNDLED_PERMISSIONS),
/* harmony export */   addPermissions: () => (/* binding */ addPermissions),
/* harmony export */   canTogglePermissions: () => (/* binding */ canTogglePermissions),
/* harmony export */   hasPermissions: () => (/* binding */ hasPermissions),
/* harmony export */   permissionsSetIsValid: () => (/* binding */ permissionsSetIsValid),
/* harmony export */   subtractPermissions: () => (/* binding */ subtractPermissions),
/* harmony export */   togglePermissions: () => (/* binding */ togglePermissions)
/* harmony export */ });
/**
 * @copyright 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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

const ATOMIC_PERMISSIONS = {
  NONE: 0,
  READ: 1,
  UPDATE: 2,
  CREATE: 4,
  DELETE: 8,
  SHARE: 16
};
const BUNDLED_PERMISSIONS = {
  READ_ONLY: ATOMIC_PERMISSIONS.READ,
  UPLOAD_AND_UPDATE: ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE,
  FILE_DROP: ATOMIC_PERMISSIONS.CREATE,
  ALL: ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE,
  ALL_FILE: ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.SHARE
};

/**
 * Return whether a given permissions set contains some permissions.
 *
 * @param {number} initialPermissionSet - the permissions set.
 * @param {number} permissionsToCheck - the permissions to check.
 * @return {boolean}
 */
function hasPermissions(initialPermissionSet, permissionsToCheck) {
  return initialPermissionSet !== ATOMIC_PERMISSIONS.NONE && (initialPermissionSet & permissionsToCheck) === permissionsToCheck;
}

/**
 * Return whether a given permissions set is valid.
 *
 * @param {number} permissionsSet - the permissions set.
 *
 * @return {boolean}
 */
function permissionsSetIsValid(permissionsSet) {
  // Must have at least READ or CREATE permission.
  if (!hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.READ) && !hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.CREATE)) {
    return false;
  }

  // Must have READ permission if have UPDATE or DELETE.
  if (!hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.READ) && (hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.UPDATE) || hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.DELETE))) {
    return false;
  }
  return true;
}

/**
 * Add some permissions to an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the initial permissions.
 * @param {number} permissionsToAdd - the permissions to add.
 *
 * @return {number}
 */
function addPermissions(initialPermissionSet, permissionsToAdd) {
  return initialPermissionSet | permissionsToAdd;
}

/**
 * Remove some permissions from an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the initial permissions.
 * @param {number} permissionsToSubtract - the permissions to remove.
 *
 * @return {number}
 */
function subtractPermissions(initialPermissionSet, permissionsToSubtract) {
  return initialPermissionSet & ~permissionsToSubtract;
}

/**
 * Toggle some permissions from  an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the permissions set.
 * @param {number} permissionsToToggle - the permissions to toggle.
 *
 * @return {number}
 */
function togglePermissions(initialPermissionSet, permissionsToToggle) {
  if (hasPermissions(initialPermissionSet, permissionsToToggle)) {
    return subtractPermissions(initialPermissionSet, permissionsToToggle);
  } else {
    return addPermissions(initialPermissionSet, permissionsToToggle);
  }
}

/**
 * Return whether some given permissions can be toggled from a permission set.
 *
 * @param {number} permissionSet - the initial permissions set.
 * @param {number} permissionsToToggle - the permissions to toggle.
 *
 * @return {boolean}
 */
function canTogglePermissions(permissionSet, permissionsToToggle) {
  return permissionsSetIsValid(togglePermissions(permissionSet, permissionsToToggle));
}

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareDetails.js":
/*!*******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareDetails.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  methods: {
    async openSharingDetails(shareRequestObject) {
      let share = {};
      // handle externalResults from OCA.Sharing.ShareSearch
      // TODO : Better name/interface for handler required
      // For example `externalAppCreateShareHook` with proper documentation
      if (shareRequestObject.handler) {
        if (this.suggestions) {
          shareRequestObject.suggestions = this.suggestions;
          shareRequestObject.fileInfo = this.fileInfo;
          shareRequestObject.query = this.query;
        }
        share = await shareRequestObject.handler(shareRequestObject);
        share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"](share);
      } else {
        share = this.mapShareRequestToShareObject(shareRequestObject);
      }
      const shareDetails = {
        fileInfo: this.fileInfo,
        share
      };
      this.$emit('open-sharing-details', shareDetails);
    },
    openShareDetailsForCustomSettings(share) {
      share.setCustomPermissions = true;
      this.openSharingDetails(share);
    },
    mapShareRequestToShareObject(shareRequestObject) {
      if (shareRequestObject.id) {
        return shareRequestObject;
      }
      const share = {
        attributes: [{
          enabled: true,
          key: 'download',
          scope: 'permissions'
        }],
        share_type: shareRequestObject.shareType,
        share_with: shareRequestObject.shareWith,
        is_no_user: shareRequestObject.isNoUser,
        user: shareRequestObject.shareWith,
        share_with_displayname: shareRequestObject.displayName,
        subtitle: shareRequestObject.subtitle,
        permissions: shareRequestObject.permissions,
        expiration: ''
      };
      return new _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"](share);
    }
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareRequests.js":
/*!********************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareRequests.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! url-search-params-polyfill */ "./node_modules/url-search-params-polyfill/index.js");
/* harmony import */ var url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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

// TODO: remove when ie not supported





const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  methods: {
    /**
     * Create a new share
     *
     * @param {object} data destructuring object
     * @param {string} data.path  path to the file/folder which should be shared
     * @param {number} data.shareType  0 = user; 1 = group; 3 = public link; 6 = federated cloud share
     * @param {string} data.shareWith  user/group id with which the file should be shared (optional for shareType > 1)
     * @param {boolean} [data.publicUpload]  allow public upload to a public shared folder
     * @param {string} [data.password]  password to protect public link Share with
     * @param {number} [data.permissions]  1 = read; 2 = update; 4 = create; 8 = delete; 16 = share; 31 = all (default: 31, for public shares: 1)
     * @param {boolean} [data.sendPasswordByTalk] send the password via a talk conversation
     * @param {string} [data.expireDate] expire the shareautomatically after
     * @param {string} [data.label] custom label
     * @param {string} [data.attributes] Share attributes encoded as json
     * @param data.note
     * @return {Share} the new share
     * @throws {Error}
     */
    async createShare(_ref) {
      let {
        path,
        permissions,
        shareType,
        shareWith,
        publicUpload,
        password,
        sendPasswordByTalk,
        expireDate,
        label,
        note,
        attributes
      } = _ref;
      try {
        var _request$data;
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post(shareUrl, {
          path,
          permissions,
          shareType,
          shareWith,
          publicUpload,
          password,
          sendPasswordByTalk,
          expireDate,
          label,
          note,
          attributes
        });
        if (!(request !== null && request !== void 0 && (_request$data = request.data) !== null && _request$data !== void 0 && _request$data.ocs)) {
          throw request;
        }
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_3__["default"](request.data.ocs.data);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:created', {
          share
        });
        return share;
      } catch (error) {
        var _error$response;
        console.error('Error while creating share', error);
        const errorMessage = error === null || error === void 0 || (_error$response = error.response) === null || _error$response === void 0 || (_error$response = _error$response.data) === null || _error$response === void 0 || (_error$response = _error$response.ocs) === null || _error$response === void 0 || (_error$response = _error$response.meta) === null || _error$response === void 0 ? void 0 : _error$response.message;
        OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error creating the share: {errorMessage}', {
          errorMessage
        }) : t('files_sharing', 'Error creating the share'), {
          type: 'error'
        });
        throw error;
      }
    },
    /**
     * Delete a share
     *
     * @param {number} id share id
     * @throws {Error}
     */
    async deleteShare(id) {
      try {
        var _request$data2;
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].delete(shareUrl + "/".concat(id));
        if (!(request !== null && request !== void 0 && (_request$data2 = request.data) !== null && _request$data2 !== void 0 && _request$data2.ocs)) {
          throw request;
        }
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:deleted', {
          id
        });
        return true;
      } catch (error) {
        var _error$response2;
        console.error('Error while deleting share', error);
        const errorMessage = error === null || error === void 0 || (_error$response2 = error.response) === null || _error$response2 === void 0 || (_error$response2 = _error$response2.data) === null || _error$response2 === void 0 || (_error$response2 = _error$response2.ocs) === null || _error$response2 === void 0 || (_error$response2 = _error$response2.meta) === null || _error$response2 === void 0 ? void 0 : _error$response2.message;
        OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error deleting the share: {errorMessage}', {
          errorMessage
        }) : t('files_sharing', 'Error deleting the share'), {
          type: 'error'
        });
        throw error;
      }
    },
    /**
     * Update a share
     *
     * @param {number} id share id
     * @param {object} properties key-value object of the properties to update
     */
    async updateShare(id, properties) {
      try {
        var _request$data3;
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].put(shareUrl + "/".concat(id), properties);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:updated', {
          id
        });
        if (!(request !== null && request !== void 0 && (_request$data3 = request.data) !== null && _request$data3 !== void 0 && _request$data3.ocs)) {
          throw request;
        } else {
          return request.data.ocs.data;
        }
      } catch (error) {
        console.error('Error while updating share', error);
        if (error.response.status !== 400) {
          var _error$response3;
          const errorMessage = error === null || error === void 0 || (_error$response3 = error.response) === null || _error$response3 === void 0 || (_error$response3 = _error$response3.data) === null || _error$response3 === void 0 || (_error$response3 = _error$response3.ocs) === null || _error$response3 === void 0 || (_error$response3 = _error$response3.meta) === null || _error$response3 === void 0 ? void 0 : _error$response3.message;
          OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error updating the share: {errorMessage}', {
            errorMessage
          }) : t('files_sharing', 'Error updating the share'), {
            type: 'error'
          });
        }
        const message = error.response.data.ocs.meta.message;
        throw new Error(message);
      }
    }
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareTypes.js":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareTypes.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  data() {
    return {
      SHARE_TYPES: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type
    };
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/SharesMixin.js":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/SharesMixin.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _ShareRequests_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _ShareTypes_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Gary Kim <gary@garykim.dev>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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



// eslint-disable-next-line import/no-unresolved, n/no-missing-import







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  mixins: [_ShareRequests_js__WEBPACK_IMPORTED_MODULE_5__["default"], _ShareTypes_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      default: null
    },
    isUnique: {
      type: Boolean,
      default: true
    }
  },
  data() {
    var _this$share;
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_7__["default"](),
      // errors helpers
      errors: {},
      // component status toggles
      loading: false,
      saving: false,
      open: false,
      // concurrency management queue
      // we want one queue per share
      updateQueue: new p_queue__WEBPACK_IMPORTED_MODULE_2__["default"]({
        concurrency: 1
      }),
      /**
       * ! This allow vue to make the Share class state reactive
       * ! do not remove it ot you'll lose all reactivity here
       */
      reactiveState: (_this$share = this.share) === null || _this$share === void 0 ? void 0 : _this$share.state
    };
  },
  computed: {
    /**
     * Does the current share have a note
     *
     * @return {boolean}
     */
    hasNote: {
      get() {
        return this.share.note !== '';
      },
      set(enabled) {
        this.share.note = enabled ? null // enabled but user did not changed the content yet
        : ''; // empty = no note = disabled
      }
    },
    dateTomorrow() {
      return new Date(new Date().setDate(new Date().getDate() + 1));
    },
    // Datepicker language
    lang() {
      const weekdaysShort = window.dayNamesShort ? window.dayNamesShort // provided by nextcloud
      : ['Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.'];
      const monthsShort = window.monthNamesShort ? window.monthNamesShort // provided by nextcloud
      : ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.'];
      const firstDayOfWeek = window.firstDay ? window.firstDay : 0;
      return {
        formatLocale: {
          firstDayOfWeek,
          monthsShort,
          weekdaysMin: weekdaysShort,
          weekdaysShort
        },
        monthFormat: 'MMM'
      };
    },
    isFolder() {
      return this.fileInfo.type === 'dir';
    },
    isPublicShare() {
      var _this$share$shareType;
      const shareType = (_this$share$shareType = this.share.shareType) !== null && _this$share$shareType !== void 0 ? _this$share$shareType : this.share.type;
      return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType);
    },
    isRemoteShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP || this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE;
    },
    isShareOwner() {
      return this.share && this.share.owner === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
    },
    isExpiryDateEnforced() {
      if (this.isPublicShare) {
        return this.config.isDefaultExpireDateEnforced;
      }
      if (this.isRemoteShare) {
        return this.config.isDefaultRemoteExpireDateEnforced;
      }
      return this.config.isDefaultInternalExpireDateEnforced;
    },
    hasCustomPermissions() {
      const bundledPermissions = [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.ALL, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.READ_ONLY, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.FILE_DROP];
      return !bundledPermissions.includes(this.share.permissions);
    },
    maxExpirationDateEnforced() {
      if (this.isExpiryDateEnforced) {
        if (this.isPublicShare) {
          return this.config.defaultExpirationDate;
        }
        if (this.isRemoteShare) {
          return this.config.defaultRemoteExpirationDateString;
        }
        // If it get's here then it must be an internal share
        return this.config.defaultInternalExpirationDate;
      }
      return null;
    }
  },
  methods: {
    /**
     * Check if a share is valid before
     * firing the request
     *
     * @param {Share} share the share to check
     * @return {boolean}
     */
    checkShare(share) {
      if (share.password) {
        if (typeof share.password !== 'string' || share.password.trim() === '') {
          return false;
        }
      }
      if (share.expirationDate) {
        const date = share.expirationDate;
        if (!date.isValid()) {
          return false;
        }
      }
      return true;
    },
    /**
     * @param {string} date a date with YYYY-MM-DD format
     * @return {Date} date
     */
    parseDateString(date) {
      var _date$match;
      if (!date) {
        return;
      }
      const regex = /([0-9]{4}-[0-9]{2}-[0-9]{2})/i;
      return new Date((_date$match = date.match(regex)) === null || _date$match === void 0 ? void 0 : _date$match.pop());
    },
    /**
     * @param {Date} date
     * @return {string} date a date with YYYY-MM-DD format
     */
    formatDateToString(date) {
      // Force utc time. Drop time information to be timezone-less
      const utcDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
      // Format to YYYY-MM-DD
      return utcDate.toISOString().split('T')[0];
    },
    /**
     * Save given value to expireDate and trigger queueUpdate
     *
     * @param {Date} date
     */
    onExpirationChange: debounce__WEBPACK_IMPORTED_MODULE_3___default()(function (date) {
      this.share.expireDate = this.formatDateToString(new Date(date));
    }, 500),
    /**
     * Uncheck expire date
     * We need this method because @update:checked
     * is ran simultaneously as @uncheck, so
     * so we cannot ensure data is up-to-date
     */
    onExpirationDisable() {
      this.share.expireDate = '';
    },
    /**
     * Note changed, let's save it to a different key
     *
     * @param {string} note the share note
     */
    onNoteChange(note) {
      this.$set(this.share, 'newNote', note.trim());
    },
    /**
     * When the note change, we trim, save and dispatch
     *
     */
    onNoteSubmit() {
      if (this.share.newNote) {
        this.share.note = this.share.newNote;
        this.$delete(this.share, 'newNote');
        this.queueUpdate('note');
      }
    },
    /**
     * Delete share button handler
     */
    async onDelete() {
      try {
        this.loading = true;
        this.open = false;
        await this.deleteShare(this.share.id);
        console.debug('Share deleted', this.share.id);
        const message = this.share.itemType === 'file' ? t('files_sharing', 'File "{path}" has been unshared', {
          path: this.share.path
        }) : t('files_sharing', 'Folder "{path}" has been unshared', {
          path: this.share.path
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(message);
        this.$emit('remove:share', this.share);
      } catch (error) {
        // re-open menu if error
        this.open = true;
      } finally {
        this.loading = false;
      }
    },
    /**
     * Send an update of the share to the queue
     *
     * @param {Array<string>} propertyNames the properties to sync
     */
    queueUpdate() {
      for (var _len = arguments.length, propertyNames = new Array(_len), _key = 0; _key < _len; _key++) {
        propertyNames[_key] = arguments[_key];
      }
      if (propertyNames.length === 0) {
        // Nothing to update
        return;
      }
      if (this.share.id) {
        const properties = {};
        // force value to string because that is what our
        // share api controller accepts
        propertyNames.forEach(name => {
          if (typeof this.share[name] === 'object') {
            properties[name] = JSON.stringify(this.share[name]);
          } else {
            properties[name] = this.share[name].toString();
          }
        });
        this.updateQueue.add(async () => {
          this.saving = true;
          this.errors = {};
          try {
            const updatedShare = await this.updateShare(this.share.id, properties);
            if (propertyNames.indexOf('password') >= 0) {
              // reset password state after sync
              this.$delete(this.share, 'newPassword');

              // updates password expiration time after sync
              this.share.passwordExpirationTime = updatedShare.password_expiration_time;
            }

            // clear any previous errors
            this.$delete(this.errors, propertyNames[0]);
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(t('files_sharing', 'Share {propertyName} saved', {
              propertyName: propertyNames[0]
            }));
          } catch ({
            message
          }) {
            if (message && message !== '') {
              this.onSyncError(propertyNames[0], message);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('files_sharing', message));
            }
          } finally {
            this.saving = false;
          }
        });
        return;
      }

      // This share does not exists on the server yet
      console.debug('Updated local share', this.share);
    },
    /**
     * Manage sync errors
     *
     * @param {string} property the errored property, e.g. 'password'
     * @param {string} message the error message
     */
    onSyncError(property, message) {
      // re-open menu if closed
      this.open = true;
      switch (property) {
        case 'password':
        case 'pending':
        case 'expireDate':
        case 'label':
        case 'note':
          {
            // show error
            this.$set(this.errors, property, message);
            let propertyEl = this.$refs[property];
            if (propertyEl) {
              if (propertyEl.$el) {
                propertyEl = propertyEl.$el;
              }
              // focus if there is a focusable action element
              const focusable = propertyEl.querySelector('.focusable');
              if (focusable) {
                focusable.focus();
              }
            }
            break;
          }
        case 'sendPasswordByTalk':
          {
            // show error
            this.$set(this.errors, property, message);

            // Restore previous state
            this.share.sendPasswordByTalk = !this.share.sendPasswordByTalk;
            break;
          }
      }
    },
    /**
     * Debounce queueUpdate to avoid requests spamming
     * more importantly for text data
     *
     * @param {string} property the property to sync
     */
    debounceQueueUpdate: debounce__WEBPACK_IMPORTED_MODULE_3___default()(function (property) {
      this.queueUpdate(property);
    }, 500)
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/models/Share.js":
/*!************************************************!*\
  !*** ./apps/files_sharing/src/models/Share.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Share)
/* harmony export */ });
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Gary Kim <gary@garykim.dev>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

class Share {
  /**
   * Create the share object
   *
   * @param {object} ocsData ocs request response
   */
  constructor(ocsData) {
    var _ocsData$attributes;
    _defineProperty(this, "_share", void 0);
    if (ocsData.ocs && ocsData.ocs.data && ocsData.ocs.data[0]) {
      ocsData = ocsData.ocs.data[0];
    }

    // convert int into boolean
    ocsData.hide_download = !!ocsData.hide_download;
    ocsData.mail_send = !!ocsData.mail_send;
    if (ocsData.attributes && typeof ocsData.attributes === 'string') {
      try {
        ocsData.attributes = JSON.parse(ocsData.attributes);
      } catch (e) {
        console.warn('Could not parse share attributes returned by server', ocsData.attributes);
      }
    }
    ocsData.attributes = (_ocsData$attributes = ocsData.attributes) !== null && _ocsData$attributes !== void 0 ? _ocsData$attributes : [];

    // store state
    this._share = ocsData;
  }

  /**
   * Get the share state
   * ! used for reactivity purpose
   * Do not remove. It allow vuejs to
   * inject its watchers into the #share
   * state and make the whole class reactive
   *
   * @return {object} the share raw state
   * @readonly
   * @memberof Sidebar
   */
  get state() {
    return this._share;
  }

  /**
   * get the share id
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get id() {
    return this._share.id;
  }

  /**
   * Get the share type
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get type() {
    return this._share.share_type;
  }

  /**
   * Get the share permissions
   * See OC.PERMISSION_* variables
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get permissions() {
    return this._share.permissions;
  }

  /**
   * Get the share attributes
   *
   * @return {Array}
   * @readonly
   * @memberof Share
   */
  get attributes() {
    return this._share.attributes;
  }

  /**
   * Set the share permissions
   * See OC.PERMISSION_* variables
   *
   * @param {number} permissions valid permission, See OC.PERMISSION_* variables
   * @memberof Share
   */
  set permissions(permissions) {
    this._share.permissions = permissions;
  }

  // SHARE OWNER --------------------------------------------------
  /**
   * Get the share owner uid
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get owner() {
    return this._share.uid_owner;
  }

  /**
   * Get the share owner's display name
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get ownerDisplayName() {
    return this._share.displayname_owner;
  }

  // SHARED WITH --------------------------------------------------
  /**
   * Get the share with entity uid
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWith() {
    return this._share.share_with;
  }

  /**
   * Get the share with entity display name
   * fallback to its uid if none
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithDisplayName() {
    return this._share.share_with_displayname || this._share.share_with;
  }

  /**
   * Unique display name in case of multiple
   * duplicates results with the same name.
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithDisplayNameUnique() {
    return this._share.share_with_displayname_unique || this._share.share_with;
  }

  /**
   * Get the share with entity link
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithLink() {
    return this._share.share_with_link;
  }

  /**
   * Get the share with avatar if any
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithAvatar() {
    return this._share.share_with_avatar;
  }

  // SHARED FILE OR FOLDER OWNER ----------------------------------
  /**
   * Get the shared item owner uid
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get uidFileOwner() {
    return this._share.uid_file_owner;
  }

  /**
   * Get the shared item display name
   * fallback to its uid if none
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get displaynameFileOwner() {
    return this._share.displayname_file_owner || this._share.uid_file_owner;
  }

  // TIME DATA ----------------------------------------------------
  /**
   * Get the share creation timestamp
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get createdTime() {
    return this._share.stime;
  }

  /**
   * Get the expiration date
   *
   * @return {string} date with YYYY-MM-DD format
   * @readonly
   * @memberof Share
   */
  get expireDate() {
    return this._share.expiration;
  }

  /**
   * Set the expiration date
   *
   * @param {string} date the share expiration date with YYYY-MM-DD format
   * @memberof Share
   */
  set expireDate(date) {
    this._share.expiration = date;
  }

  // EXTRA DATA ---------------------------------------------------
  /**
   * Get the public share token
   *
   * @return {string} the token
   * @readonly
   * @memberof Share
   */
  get token() {
    return this._share.token;
  }

  /**
   * Get the share note if any
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get note() {
    return this._share.note;
  }

  /**
   * Set the share note if any
   *
   * @param {string} note the note
   * @memberof Share
   */
  set note(note) {
    this._share.note = note;
  }

  /**
   * Get the share label if any
   * Should only exist on link shares
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get label() {
    var _this$_share$label;
    return (_this$_share$label = this._share.label) !== null && _this$_share$label !== void 0 ? _this$_share$label : '';
  }

  /**
   * Set the share label if any
   * Should only be set on link shares
   *
   * @param {string} label the label
   * @memberof Share
   */
  set label(label) {
    this._share.label = label;
  }

  /**
   * Have a mail been sent
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get mailSend() {
    return this._share.mail_send === true;
  }

  /**
   * Hide the download button on public page
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hideDownload() {
    return this._share.hide_download === true;
  }

  /**
   * Hide the download button on public page
   *
   * @param {boolean} state hide the button ?
   * @memberof Share
   */
  set hideDownload(state) {
    this._share.hide_download = state === true;
  }

  /**
   * Password protection of the share
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get password() {
    return this._share.password;
  }

  /**
   * Password protection of the share
   *
   * @param {string} password the share password
   * @memberof Share
   */
  set password(password) {
    this._share.password = password;
  }

  /**
   * Password expiration time
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get passwordExpirationTime() {
    return this._share.password_expiration_time;
  }

  /**
   * Password expiration time
   *
   * @param {string} password expiration time
   * @memberof Share
   */
  set passwordExpirationTime(passwordExpirationTime) {
    this._share.password_expiration_time = passwordExpirationTime;
  }

  /**
   * Password protection by Talk of the share
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get sendPasswordByTalk() {
    return this._share.send_password_by_talk;
  }

  /**
   * Password protection by Talk of the share
   *
   * @param {boolean} sendPasswordByTalk whether to send the password by Talk
   *        or not
   * @memberof Share
   */
  set sendPasswordByTalk(sendPasswordByTalk) {
    this._share.send_password_by_talk = sendPasswordByTalk;
  }

  // SHARED ITEM DATA ---------------------------------------------
  /**
   * Get the shared item absolute full path
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get path() {
    return this._share.path;
  }

  /**
   * Return the item type: file or folder
   *
   * @return {string} 'folder' or 'file'
   * @readonly
   * @memberof Share
   */
  get itemType() {
    return this._share.item_type;
  }

  /**
   * Get the shared item mimetype
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get mimetype() {
    return this._share.mimetype;
  }

  /**
   * Get the shared item id
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get fileSource() {
    return this._share.file_source;
  }

  /**
   * Get the target path on the receiving end
   * e.g the file /xxx/aaa will be shared in
   * the receiving root as /aaa, the fileTarget is /aaa
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get fileTarget() {
    return this._share.file_target;
  }

  /**
   * Get the parent folder id if any
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get fileParent() {
    return this._share.file_parent;
  }

  // PERMISSIONS Shortcuts

  /**
   * Does this share have READ permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasReadPermission() {
    return !!(this.permissions & OC.PERMISSION_READ);
  }

  /**
   * Does this share have CREATE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasCreatePermission() {
    return !!(this.permissions & OC.PERMISSION_CREATE);
  }

  /**
   * Does this share have DELETE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasDeletePermission() {
    return !!(this.permissions & OC.PERMISSION_DELETE);
  }

  /**
   * Does this share have UPDATE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasUpdatePermission() {
    return !!(this.permissions & OC.PERMISSION_UPDATE);
  }

  /**
   * Does this share have SHARE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasSharePermission() {
    return !!(this.permissions & OC.PERMISSION_SHARE);
  }

  /**
   * Does this share have download permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasDownloadPermission() {
    for (const i in this._share.attributes) {
      const attr = this._share.attributes[i];
      if (attr.scope === 'permissions' && attr.key === 'download') {
        return attr.enabled;
      }
    }
    return true;
  }
  set hasDownloadPermission(enabled) {
    this.setAttribute('permissions', 'download', !!enabled);
  }
  setAttribute(scope, key, enabled) {
    const attrUpdate = {
      scope,
      key,
      enabled
    };

    // try and replace existing
    for (const i in this._share.attributes) {
      const attr = this._share.attributes[i];
      if (attr.scope === attrUpdate.scope && attr.key === attrUpdate.key) {
        this._share.attributes.splice(i, 1, attrUpdate);
        return;
      }
    }
    this._share.attributes.push(attrUpdate);
  }

  // PERMISSIONS Shortcuts for the CURRENT USER
  // ! the permissions above are the share settings,
  // ! meaning the permissions for the recipient
  /**
   * Can the current user EDIT this share ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get canEdit() {
    return this._share.can_edit === true;
  }

  /**
   * Can the current user DELETE this share ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get canDelete() {
    return this._share.can_delete === true;
  }

  /**
   * Top level accessible shared folder fileid for the current user
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get viaFileid() {
    return this._share.via_fileid;
  }

  /**
   * Top level accessible shared folder path for the current user
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get viaPath() {
    return this._share.via_path;
  }

  // TODO: SORT THOSE PROPERTIES

  get parent() {
    return this._share.parent;
  }
  get storageId() {
    return this._share.storage_id;
  }
  get storage() {
    return this._share.storage;
  }
  get itemSource() {
    return this._share.item_source;
  }
  get status() {
    return this._share.status;
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/services/ConfigService.js":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/services/ConfigService.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Config)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

class Config {
  constructor() {
    this._capabilities = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)();
  }

  /**
   * Get default share permissions, if any
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get defaultPermissions() {
    var _this$_capabilities$f;
    return (_this$_capabilities$f = this._capabilities.files_sharing) === null || _this$_capabilities$f === void 0 ? void 0 : _this$_capabilities$f.default_permissions;
  }

  /**
   * Is public upload allowed on link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isPublicUploadEnabled() {
    var _this$_capabilities$f2;
    return (_this$_capabilities$f2 = this._capabilities.files_sharing) === null || _this$_capabilities$f2 === void 0 ? void 0 : _this$_capabilities$f2.public.upload;
  }

  /**
   * Are link share allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isShareWithLinkAllowed() {
    return document.getElementById('allowShareWithLink') && document.getElementById('allowShareWithLink').value === 'yes';
  }

  /**
   * Get the federated sharing documentation link
   *
   * @return {string}
   * @readonly
   * @memberof Config
   */
  get federatedShareDocLink() {
    return OC.appConfig.core.federatedCloudShareDoc;
  }

  /**
   * Get the default link share expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultExpirationDate() {
    if (this.isDefaultExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate));
    }
    return null;
  }

  /**
   * Get the default internal expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultInternalExpirationDate() {
    if (this.isDefaultInternalExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultInternalExpireDate));
    }
    return null;
  }

  /**
   * Get the default remote expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultRemoteExpirationDateString() {
    if (this.isDefaultRemoteExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultRemoteExpireDate));
    }
    return null;
  }

  /**
   * Are link shares password-enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get enforcePasswordForPublicLink() {
    return OC.appConfig.core.enforcePasswordForPublicLink === true;
  }

  /**
   * Is password asked by default on link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get enableLinkPasswordByDefault() {
    return OC.appConfig.core.enableLinkPasswordByDefault === true;
  }

  /**
   * Is link shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultExpireDateEnforced() {
    return OC.appConfig.core.defaultExpireDateEnforced === true;
  }

  /**
   * Is there a default expiration date for new link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultExpireDateEnabled() {
    return OC.appConfig.core.defaultExpireDateEnabled === true;
  }

  /**
   * Is internal shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultInternalExpireDateEnforced() {
    return OC.appConfig.core.defaultInternalExpireDateEnforced === true;
  }

  /**
   * Is remote shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultRemoteExpireDateEnforced() {
    return OC.appConfig.core.defaultRemoteExpireDateEnforced === true;
  }

  /**
   * Is there a default expiration date for new internal shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultInternalExpireDateEnabled() {
    return OC.appConfig.core.defaultInternalExpireDateEnabled === true;
  }

  /**
   * Is there a default expiration date for new remote shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultRemoteExpireDateEnabled() {
    return OC.appConfig.core.defaultRemoteExpireDateEnabled === true;
  }

  /**
   * Are users on this server allowed to send shares to other servers ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isRemoteShareAllowed() {
    return OC.appConfig.core.remoteShareAllowed === true;
  }

  /**
   * Is sharing my mail (link share) enabled ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isMailShareAllowed() {
    var _this$_capabilities, _this$_capabilities2;
    // eslint-disable-next-line camelcase
    return ((_this$_capabilities = this._capabilities) === null || _this$_capabilities === void 0 || (_this$_capabilities = _this$_capabilities.files_sharing) === null || _this$_capabilities === void 0 ? void 0 : _this$_capabilities.sharebymail) !== undefined
    // eslint-disable-next-line camelcase
    && ((_this$_capabilities2 = this._capabilities) === null || _this$_capabilities2 === void 0 || (_this$_capabilities2 = _this$_capabilities2.files_sharing) === null || _this$_capabilities2 === void 0 || (_this$_capabilities2 = _this$_capabilities2.public) === null || _this$_capabilities2 === void 0 ? void 0 : _this$_capabilities2.enabled) === true;
  }

  /**
   * Get the default days to link shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultExpireDate() {
    return OC.appConfig.core.defaultExpireDate;
  }

  /**
   * Get the default days to internal shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultInternalExpireDate() {
    return OC.appConfig.core.defaultInternalExpireDate;
  }

  /**
   * Get the default days to remote shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultRemoteExpireDate() {
    return OC.appConfig.core.defaultRemoteExpireDate;
  }

  /**
   * Is resharing allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isResharingAllowed() {
    return OC.appConfig.core.resharingAllowed === true;
  }

  /**
   * Is password enforced for mail shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isPasswordForMailSharesRequired() {
    return this._capabilities.files_sharing.sharebymail === undefined ? false : this._capabilities.files_sharing.sharebymail.password.enforced;
  }

  /**
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get shouldAlwaysShowUnique() {
    var _this$_capabilities$f3;
    return ((_this$_capabilities$f3 = this._capabilities.files_sharing) === null || _this$_capabilities$f3 === void 0 || (_this$_capabilities$f3 = _this$_capabilities$f3.sharee) === null || _this$_capabilities$f3 === void 0 ? void 0 : _this$_capabilities$f3.always_show_unique) === true;
  }

  /**
   * Is sharing with groups allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get allowGroupSharing() {
    return OC.appConfig.core.allowGroupSharing === true;
  }

  /**
   * Get the maximum results of a share search
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get maxAutocompleteResults() {
    return parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 25;
  }

  /**
   * Get the minimal string length
   * to initiate a share search
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get minSearchStringLength() {
    return parseInt(OC.config['sharing.minSearchStringLength'], 10) || 0;
  }

  /**
   * Get the password policy config
   *
   * @return {object}
   * @readonly
   * @memberof Config
   */
  get passwordPolicy() {
    return this._capabilities.password_policy ? this._capabilities.password_policy : {};
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/utils/GeneratePassword.js":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/utils/GeneratePassword.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
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




const config = new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_1__["default"]();
// note: some chars removed on purpose to make them human friendly when read out
const passwordSet = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';

/**
 * Generate a valid policy password or
 * request a valid password if password_policy
 * is enabled
 *
 * @return {string} a valid password
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__() {
  // password policy is enabled, let's request a pass
  if (config.passwordPolicy.api && config.passwordPolicy.api.generate) {
    try {
      const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(config.passwordPolicy.api.generate);
      if (request.data.ocs.data.password) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('files_sharing', 'Password created successfully'));
        return request.data.ocs.data.password;
      }
    } catch (error) {
      console.info('Error generating password from password_policy', error);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('files_sharing', 'Error generating password from password policy'));
    }
  }
  const array = new Uint8Array(10);
  const ratio = passwordSet.length / 255;
  self.crypto.getRandomValues(array);
  let password = '';
  for (let i = 0; i < array.length; i++) {
    password += passwordSet.charAt(array[i] * ratio);
  }
  return password;
}

/***/ }),

/***/ "./apps/files_sharing/src/utils/SharedWithMe.js":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/utils/SharedWithMe.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   shareWithTitle: () => (/* binding */ shareWithTitle)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
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


const shareWithTitle = function (share) {
  if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_GROUP) {
    return t('files_sharing', 'Shared with you and the group {group} by {owner}', {
      group: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_CIRCLE) {
    return t('files_sharing', 'Shared with you and {circle} by {owner}', {
      circle: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_ROOM) {
    if (share.shareWithDisplayName) {
      return t('files_sharing', 'Shared with you and the conversation {conversation} by {owner}', {
        conversation: share.shareWithDisplayName,
        owner: share.ownerDisplayName
      }, undefined, {
        escape: false
      });
    } else {
      return t('files_sharing', 'Shared with you in a conversation by {owner}', {
        owner: share.ownerDisplayName
      }, undefined, {
        escape: false
      });
    }
  } else {
    return t('files_sharing', 'Shared with you by {owner}', {
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  }
};


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js":
/*!***********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ExternalShareAction',
  props: {
    id: {
      type: String,
      required: true
    },
    action: {
      type: Object,
      default: () => ({})
    },
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"],
      default: null
    }
  },
  computed: {
    data() {
      return this.action.data(this);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntry',
  components: {
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    SharingEntryQuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_5__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  computed: {
    title() {
      let title = this.share.shareWithDisplayName;
      if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
        title += " (".concat(t('files_sharing', 'group'), ")");
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
        title += " (".concat(t('files_sharing', 'conversation'), ")");
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE) {
        title += " (".concat(t('files_sharing', 'remote'), ")");
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) {
        title += " (".concat(t('files_sharing', 'remote group'), ")");
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GUEST) {
        title += " (".concat(t('files_sharing', 'guest'), ")");
      }
      return title;
    },
    tooltip() {
      if (this.share.owner !== this.share.uidFileOwner) {
        const data = {
          // todo: strong or italic?
          // but the t function escape any html from the data :/
          user: this.share.shareWithDisplayName,
          owner: this.share.ownerDisplayName
        };
        if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
          return t('files_sharing', 'Shared with the group {user} by {owner}', data);
        } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
          return t('files_sharing', 'Shared with the conversation {user} by {owner}', data);
        }
        return t('files_sharing', 'Shared with {user} by {owner}', data);
      }
      return null;
    },
    /**
     * @return {boolean}
     */
    hasStatus() {
      if (this.share.type !== this.SHARE_TYPES.SHARE_TYPE_USER) {
        return false;
      }
      return typeof this.share.status === 'object' && !Array.isArray(this.share.status);
    }
  },
  methods: {
    /**
     * Save potential changed data on menu close
     */
    onMenuClose() {
      this.onNoteSubmit();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");







// eslint-disable-next-line no-unused-vars



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInherited',
  components: {
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcActionLink: _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcActionText: _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_7__["default"]],
  props: {
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_6__["default"],
      required: true
    }
  },
  computed: {
    viaFileTargetUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/f/{fileid}', {
        fileid: this.share.viaFileid
      });
    },
    viaFolderName() {
      return (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__.basename)(this.share.viaPath);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js":
/*!************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/CheckBold.vue */ "./node_modules/vue-material-design-icons/CheckBold.vue");
/* harmony import */ var vue_material_design_icons_ClipboardFlow_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/ClipboardFlow.vue */ "./node_modules/vue-material-design-icons/ClipboardFlow.vue");
/* harmony import */ var _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInternal',
  components: {
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    SharingEntrySimple: _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    CheckIcon: vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    ClipboardIcon: vue_material_design_icons_ClipboardFlow_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    }
  },
  data() {
    return {
      copied: false,
      copySuccess: false
    };
  },
  computed: {
    /**
     * Get the internal link to this file id
     *
     * @return {string}
     */
    internalLink() {
      return window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/f/') + this.fileInfo.id;
    },
    /**
     * Tooltip message
     *
     * @return {string}
     */
    copyLinkTooltip() {
      if (this.copied) {
        if (this.copySuccess) {
          return '';
        }
        return t('files_sharing', 'Cannot copy, please copy the link manually');
      }
      return t('files_sharing', 'Copy internal link to clipboard');
    },
    internalLinkSubtitle() {
      if (this.fileInfo.type === 'dir') {
        return t('files_sharing', 'Only works for people with access to this folder');
      }
      return t('files_sharing', 'Only works for people with access to this file');
    }
  },
  methods: {
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.internalLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link copied'));
        this.$refs.shareEntrySimple.$refs.actionsComponent.$el.focus();
        this.copySuccess = true;
        this.copied = true;
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        console.error(error);
      } finally {
        setTimeout(() => {
          this.copySuccess = false;
          this.copied = false;
        }, 4000);
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @chenfengyuan/vue-qrcode */ "./node_modules/@chenfengyuan/vue-qrcode/dist/vue-qrcode.js");
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionSeparator.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDialog.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Tune.vue */ "./node_modules/vue-material-design-icons/Tune.vue");
/* harmony import */ var vue_material_design_icons_Qrcode_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Qrcode.vue */ "./node_modules/vue-material-design-icons/Qrcode.vue");
/* harmony import */ var vue_material_design_icons_Exclamation_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/Exclamation.vue */ "./node_modules/vue-material-design-icons/Exclamation.vue");
/* harmony import */ var vue_material_design_icons_Lock_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/Lock.vue */ "./node_modules/vue-material-design-icons/Lock.vue");
/* harmony import */ var vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/CheckBold.vue */ "./node_modules/vue-material-design-icons/CheckBold.vue");
/* harmony import */ var vue_material_design_icons_ClipboardFlow_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/ClipboardFlow.vue */ "./node_modules/vue-material-design-icons/ClipboardFlow.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./ExternalShareAction.vue */ "./apps/files_sharing/src/components/ExternalShareAction.vue");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");



























/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryLink',
  components: {
    ExternalShareAction: _ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcActionInput: _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcActionLink: _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcActionText: _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcActionSeparator: _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcDialog: _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_11__["default"],
    VueQrcode: (_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_3___default()),
    Tune: vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    IconQr: vue_material_design_icons_Qrcode_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    ErrorIcon: vue_material_design_icons_Exclamation_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    LockIcon: vue_material_design_icons_Lock_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    CheckIcon: vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    ClipboardIcon: vue_material_design_icons_ClipboardFlow_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    CloseIcon: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
    PlusIcon: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    SharingEntryQuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_20__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_24__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_25__["default"]],
  props: {
    canReshare: {
      type: Boolean,
      default: true
    },
    index: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      copySuccess: true,
      copied: false,
      // Are we waiting for password/expiration date
      pending: false,
      ExternalLegacyLinkActions: OCA.Sharing.ExternalLinkActions.state,
      ExternalShareActions: OCA.Sharing.ExternalShareActions.state,
      // tracks whether modal should be opened or not
      showQRCode: false
    };
  },
  computed: {
    /**
     * Link share label
     *
     * @return {string}
     */
    title() {
      // if we have a valid existing share (not pending)
      if (this.share && this.share.id) {
        if (!this.isShareOwner && this.share.ownerDisplayName) {
          if (this.isEmailShareType) {
            return t('files_sharing', '{shareWith} by {initiator}', {
              shareWith: this.share.shareWith,
              initiator: this.share.ownerDisplayName
            });
          }
          return t('files_sharing', 'Shared via link by {initiator}', {
            initiator: this.share.ownerDisplayName
          });
        }
        if (this.share.label && this.share.label.trim() !== '') {
          if (this.isEmailShareType) {
            return t('files_sharing', 'Mail share ({label})', {
              label: this.share.label.trim()
            });
          }
          return t('files_sharing', 'Share link ({label})', {
            label: this.share.label.trim()
          });
        }
        if (this.isEmailShareType) {
          return this.share.shareWith;
        }
      }
      if (this.index > 1) {
        return t('files_sharing', 'Share link ({index})', {
          index: this.index
        });
      }
      return t('files_sharing', 'Share link');
    },
    /**
     * Show the email on a second line if a label is set for mail shares
     *
     * @return {string}
     */
    subtitle() {
      if (this.isEmailShareType && this.title !== this.share.shareWith) {
        return this.share.shareWith;
      }
      return null;
    },
    /**
     * Is the current share password protected ?
     *
     * @return {boolean}
     */
    isPasswordProtected: {
      get() {
        return this.config.enforcePasswordForPublicLink || !!this.share.password;
      },
      async set(enabled) {
        // TODO: directly save after generation to make sure the share is always protected
        vue__WEBPACK_IMPORTED_MODULE_26__["default"].set(this.share, 'password', enabled ? await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_22__["default"])() : '');
        vue__WEBPACK_IMPORTED_MODULE_26__["default"].set(this.share, 'newPassword', this.share.password);
      }
    },
    passwordExpirationTime() {
      if (this.share.passwordExpirationTime === null) {
        return null;
      }
      const expirationTime = moment(this.share.passwordExpirationTime);
      if (expirationTime.diff(moment()) < 0) {
        return false;
      }
      return expirationTime.fromNow();
    },
    /**
     * Is Talk enabled?
     *
     * @return {boolean}
     */
    isTalkEnabled() {
      return OC.appswebroots.spreed !== undefined;
    },
    /**
     * Is it possible to protect the password by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalkAvailable() {
      return this.isPasswordProtected && this.isTalkEnabled;
    },
    /**
     * Is the current share password protected by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalk: {
      get() {
        return this.share.sendPasswordByTalk;
      },
      async set(enabled) {
        this.share.sendPasswordByTalk = enabled;
      }
    },
    /**
     * Is the current share an email share ?
     *
     * @return {boolean}
     */
    isEmailShareType() {
      return this.share ? this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL : false;
    },
    canTogglePasswordProtectedByTalkAvailable() {
      if (!this.isPasswordProtected) {
        // Makes no sense
        return false;
      } else if (this.isEmailShareType && !this.hasUnsavedPassword) {
        // For email shares we need a new password in order to enable or
        // disable
        return false;
      }

      // Anything else should be fine
      return true;
    },
    /**
     * Pending data.
     * If the share still doesn't have an id, it is not synced
     * Therefore this is still not valid and requires user input
     *
     * @return {boolean}
     */
    pendingPassword() {
      return this.config.enableLinkPasswordByDefault && this.share && !this.share.id;
    },
    pendingEnforcedPassword() {
      return this.config.enforcePasswordForPublicLink && this.share && !this.share.id;
    },
    pendingExpirationDate() {
      return this.config.isDefaultExpireDateEnforced && this.share && !this.share.id;
    },
    // if newPassword exists, but is empty, it means
    // the user deleted the original password
    hasUnsavedPassword() {
      return this.share.newPassword !== undefined;
    },
    /**
     * Return the public share link
     *
     * @return {string}
     */
    shareLink() {
      return window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/s/') + this.share.token;
    },
    /**
     * Tooltip message for actions button
     *
     * @return {string}
     */
    actionsTooltip() {
      return t('files_sharing', 'Actions for "{title}"', {
        title: this.title
      });
    },
    /**
     * Tooltip message for copy button
     *
     * @return {string}
     */
    copyLinkTooltip() {
      if (this.copied) {
        if (this.copySuccess) {
          return '';
        }
        return t('files_sharing', 'Cannot copy, please copy the link manually');
      }
      return t('files_sharing', 'Copy public link of "{title}" to clipboard', {
        title: this.title
      });
    },
    /**
     * External additionnai actions for the menu
     *
     * @deprecated use OCA.Sharing.ExternalShareActions
     * @return {Array}
     */
    externalLegacyLinkActions() {
      return this.ExternalLegacyLinkActions.actions;
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    externalLinkActions() {
      const filterValidAction = action => (action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK) || action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_EMAIL)) && !action.advanced;
      // filter only the registered actions for said link
      return this.ExternalShareActions.actions.filter(filterValidAction);
    },
    isPasswordPolicyEnabled() {
      return typeof this.config.passwordPolicy === 'object';
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    }
  },
  methods: {
    /**
     * Create a new share link and append it to the list
     */
    async onNewLinkShare() {
      // do not run again if already loading
      if (this.loading) {
        return;
      }
      const shareDefaults = {
        share_type: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK
      };
      if (this.config.isDefaultExpireDateEnforced) {
        // default is empty string if not set
        // expiration is the share object key, not expireDate
        shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate);
      }

      // do not push yet if we need a password or an expiration date: show pending menu
      if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced) {
        this.pending = true;

        // if a share already exists, pushing it
        if (this.share && !this.share.id) {
          // if the share is valid, create it on the server
          if (this.checkShare(this.share)) {
            try {
              await this.pushNewLinkShare(this.share, true);
            } catch (e) {
              this.pending = false;
              console.error(e);
              return false;
            }
            return true;
          } else {
            this.open = true;
            OC.Notification.showTemporary(t('files_sharing', 'Error, please enter proper password and/or expiration date'));
            return false;
          }
        }

        // ELSE, show the pending popovermenu
        // if password default or enforced, pre-fill with random one
        if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
          shareDefaults.password = await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_22__["default"])();
        }

        // create share & close menu
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_23__["default"](shareDefaults);
        const component = await new Promise(resolve => {
          this.$emit('add:share', share, resolve);
        });

        // open the menu on the
        // freshly created share component
        this.open = false;
        this.pending = false;
        component.open = true;

        // Nothing is enforced, creating share directly
      } else {
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_23__["default"](shareDefaults);
        await this.pushNewLinkShare(share);
      }
    },
    /**
     * Push a new link share to the server
     * And update or append to the list
     * accordingly
     *
     * @param {Share} share the new share
     * @param {boolean} [update] do we update the current share ?
     */
    async pushNewLinkShare(share, update) {
      try {
        // do nothing if we're already pending creation
        if (this.loading) {
          return true;
        }
        this.loading = true;
        this.errors = {};
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');
        const options = {
          path,
          shareType: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK,
          password: share.password,
          expireDate: share.expireDate,
          attributes: JSON.stringify(this.fileInfo.shareAttributes)
          // we do not allow setting the publicUpload
          // before the share creation.
          // Todo: We also need to fix the createShare method in
          // lib/Controller/ShareAPIController.php to allow file drop
          // (currently not supported on create, only update)
        };
        console.debug('Creating link share with options', options);
        const newShare = await this.createShare(options);
        this.open = false;
        console.debug('Link share created', newShare);

        // if share already exists, copy link directly on next tick
        let component;
        if (update) {
          component = await new Promise(resolve => {
            this.$emit('update:share', newShare, resolve);
          });
        } else {
          // adding new share to the array and copying link to clipboard
          // using promise so that we can copy link in the same click function
          // and avoid firefox copy permissions issue
          component = await new Promise(resolve => {
            this.$emit('add:share', newShare, resolve);
          });
        }

        // Execute the copy link method
        // freshly created share component
        // ! somehow does not works on firefox !
        if (!this.config.enforcePasswordForPublicLink) {
          // Only copy the link when the password was not forced,
          // otherwise the user needs to copy/paste the password before finishing the share.
          component.copyLink();
        }
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link share created'));
      } catch (data) {
        var _data$response;
        const message = data === null || data === void 0 || (_data$response = data.response) === null || _data$response === void 0 || (_data$response = _data$response.data) === null || _data$response === void 0 || (_data$response = _data$response.ocs) === null || _data$response === void 0 || (_data$response = _data$response.meta) === null || _data$response === void 0 ? void 0 : _data$response.message;
        if (!message) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('files_sharing', 'Error while creating the share'));
          console.error(data);
          return;
        }
        if (message.match(/password/i)) {
          this.onSyncError('password', message);
        } else if (message.match(/date/i)) {
          this.onSyncError('expireDate', message);
        } else {
          this.onSyncError('pending', message);
        }
        throw data;
      } finally {
        this.loading = false;
      }
    },
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.shareLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link copied'));
        // focus and show the tooltip
        this.$refs.copyButton.$el.focus();
        this.copySuccess = true;
        this.copied = true;
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        console.error(error);
      } finally {
        setTimeout(() => {
          this.copySuccess = false;
          this.copied = false;
        }, 4000);
      }
    },
    /**
     * Update newPassword values
     * of share. If password is set but not newPassword
     * then the user did not changed the password
     * If both co-exists, the password have changed and
     * we show it in plain text.
     * Then on submit (or menu close), we sync it.
     *
     * @param {string} password the changed password
     */
    onPasswordChange(password) {
      this.$set(this.share, 'newPassword', password);
    },
    /**
     * Uncheck password protection
     * We need this method because @update:checked
     * is ran simultaneously as @uncheck, so we
     * cannot ensure data is up-to-date
     */
    onPasswordDisable() {
      this.share.password = '';

      // reset password state after sync
      this.$delete(this.share, 'newPassword');

      // only update if valid share.
      if (this.share.id) {
        this.queueUpdate('password');
      }
    },
    /**
     * Menu have been closed or password has been submitted.
     * The only property that does not get
     * synced automatically is the password
     * So let's check if we have an unsaved
     * password.
     * expireDate is saved on datepicker pick
     * or close.
     */
    onPasswordSubmit() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
        this.queueUpdate('password');
      }
    },
    /**
     * Update the password along with "sendPasswordByTalk".
     *
     * If the password was modified the new password is sent; otherwise
     * updating a mail share would fail, as in that case it is required that
     * a new password is set when enabling or disabling
     * "sendPasswordByTalk".
     */
    onPasswordProtectedByTalkChange() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
      }
      this.queueUpdate('sendPasswordByTalk', 'password');
    },
    /**
     * Save potential changed data on menu close
     */
    onMenuClose() {
      this.onPasswordSubmit();
      this.onNoteSubmit();
    },
    /**
     * Cancel the share creation
     * Used in the pending popover
     */
    onCancel() {
      // this.share already exists at this point,
      // but is incomplete as not pushed to server
      // YET. We can safely delete the share :)
      this.$emit('remove:share', this.share);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/TriangleSmallDown.vue */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var vue_material_design_icons_EyeOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/EyeOutline.vue */ "./node_modules/vue-material-design-icons/EyeOutline.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_FileUpload_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/FileUpload.vue */ "./node_modules/vue-material-design-icons/FileUpload.vue");
/* harmony import */ var vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/Tune.vue */ "./node_modules/vue-material-design-icons/Tune.vue");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryQuickShareSelect',
  components: {
    DropdownIcon: vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_1__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__["default"], _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    share: {
      type: Object,
      required: true
    }
  },
  emits: ['open-sharing-details'],
  data() {
    return {
      selectedOption: ''
    };
  },
  computed: {
    ariaLabel() {
      return t('files_sharing', 'Quick share options, the current selected is "{selectedOption}"', {
        selectedOption: this.selectedOption
      });
    },
    canViewText() {
      return t('files_sharing', 'View only');
    },
    canEditText() {
      return t('files_sharing', 'Can edit');
    },
    fileDropText() {
      return t('files_sharing', 'File drop');
    },
    customPermissionsText() {
      return t('files_sharing', 'Custom permissions');
    },
    preSelectedOption() {
      // We remove the share permission for the comparison as it is not relevant for bundled permissions.
      if ((this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.ATOMIC_PERMISSIONS.SHARE) === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.READ_ONLY) {
        return this.canViewText;
      } else if (this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.ALL || this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.ALL_FILE) {
        return this.canEditText;
      } else if ((this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.ATOMIC_PERMISSIONS.SHARE) === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.FILE_DROP) {
        return this.fileDropText;
      }
      return this.customPermissionsText;
    },
    options() {
      const options = [{
        label: this.canViewText,
        icon: vue_material_design_icons_EyeOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
      }, {
        label: this.canEditText,
        icon: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_7__["default"]
      }];
      if (this.supportsFileDrop) {
        options.push({
          label: this.fileDropText,
          icon: vue_material_design_icons_FileUpload_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
        });
      }
      options.push({
        label: this.customPermissionsText,
        icon: vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_9__["default"]
      });
      return options;
    },
    supportsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        var _this$share$type;
        const shareType = (_this$share$type = this.share.type) !== null && _this$share$type !== void 0 ? _this$share$type : this.share.shareType;
        return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType);
      }
      return false;
    },
    dropDownPermissionValue() {
      switch (this.selectedOption) {
        case this.canEditText:
          return this.isFolder ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.ALL : _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.ALL_FILE;
        case this.fileDropText:
          return _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.FILE_DROP;
        case this.customPermissionsText:
          return 'custom';
        case this.canViewText:
        default:
          return _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_10__.BUNDLED_PERMISSIONS.READ_ONLY;
      }
    }
  },
  created() {
    this.selectedOption = this.preSelectedOption;
  },
  methods: {
    selectOption(optionLabel) {
      this.selectedOption = optionLabel;
      if (optionLabel === this.customPermissionsText) {
        this.$emit('open-sharing-details');
      } else {
        this.share.permissions = this.dropDownPermissionValue;
        this.queueUpdate('permissions');
        // TODO: Add a focus method to NcActions or configurable returnFocus enabling to NcActionButton with closeAfterClick
        this.$refs.quickShareActions.$refs.menuButton.$el.focus();
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js":
/*!**********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntrySimple',
  components: {
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    title: {
      type: String,
      default: '',
      required: true
    },
    subtitle: {
      type: String,
      default: ''
    },
    isUnique: {
      type: Boolean,
      default: true
    },
    ariaExpanded: {
      type: Boolean,
      default: null
    }
  },
  computed: {
    ariaExpandedValue() {
      if (this.ariaExpanded === null) {
        return this.ariaExpanded;
      }
      return this.ariaExpanded ? 'true' : 'false';
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInput',
  components: {
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_10__["default"], _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_9__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_11__["default"]],
  props: {
    shares: {
      type: Array,
      default: () => [],
      required: true
    },
    linkShares: {
      type: Array,
      default: () => [],
      required: true
    },
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    reshare: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_8__["default"],
      default: null
    },
    canReshare: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_6__["default"](),
      loading: false,
      query: '',
      recommendations: [],
      ShareSearch: OCA.Sharing.ShareSearch.state,
      suggestions: [],
      value: null
    };
  },
  computed: {
    /**
     * Implement ShareSearch
     * allows external appas to inject new
     * results into the autocomplete dropdown
     * Used for the guests app
     *
     * @return {Array}
     */
    externalResults() {
      return this.ShareSearch.results;
    },
    inputPlaceholder() {
      const allowRemoteSharing = this.config.isRemoteShareAllowed;
      if (!this.canReshare) {
        return t('files_sharing', 'Resharing is not allowed');
      }
      // We can always search with email addresses for users too
      if (!allowRemoteSharing) {
        return t('files_sharing', 'Name or email …');
      }
      return t('files_sharing', 'Name, email, or Federated Cloud ID …');
    },
    isValidQuery() {
      return this.query && this.query.trim() !== '' && this.query.length > this.config.minSearchStringLength;
    },
    options() {
      if (this.isValidQuery) {
        return this.suggestions;
      }
      return this.recommendations;
    },
    noResultText() {
      if (this.loading) {
        return t('files_sharing', 'Searching …');
      }
      return t('files_sharing', 'No elements found.');
    }
  },
  mounted() {
    this.getRecommendations();
  },
  methods: {
    onSelected(option) {
      this.value = null; // Reset selected option
      this.openSharingDetails(option);
    },
    async asyncFind(query) {
      // save current query to check if we display
      // recommendations or search results
      this.query = query.trim();
      if (this.isValidQuery) {
        // start loading now to have proper ux feedback
        // during the debounce
        this.loading = true;
        await this.debounceGetSuggestions(query);
      }
    },
    /**
     * Get suggestions
     *
     * @param {string} search the search query
     * @param {boolean} [lookup] search on lookup server
     */
    async getSuggestions(search) {
      let lookup = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      this.loading = true;
      if ((0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing.sharee.query_lookup_default === true) {
        lookup = true;
      }
      const shareType = [this.SHARE_TYPES.SHARE_TYPE_USER, this.SHARE_TYPES.SHARE_TYPE_GROUP, this.SHARE_TYPES.SHARE_TYPE_REMOTE, this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP, this.SHARE_TYPES.SHARE_TYPE_CIRCLE, this.SHARE_TYPES.SHARE_TYPE_ROOM, this.SHARE_TYPES.SHARE_TYPE_GUEST, this.SHARE_TYPES.SHARE_TYPE_DECK, this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH];
      if ((0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing.public.enabled === true) {
        shareType.push(this.SHARE_TYPES.SHARE_TYPE_EMAIL);
      }
      let request = null;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/sharees'), {
          params: {
            format: 'json',
            itemType: this.fileInfo.type === 'dir' ? 'folder' : 'file',
            search,
            lookup,
            perPage: this.config.maxAutocompleteResults,
            shareType
          }
        });
      } catch (error) {
        console.error('Error fetching suggestions', error);
        return;
      }
      const data = request.data.ocs.data;
      const exact = request.data.ocs.data.exact;
      data.exact = []; // removing exact from general results

      // flatten array of arrays
      const rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), []);
      const rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), []);

      // remove invalid data and format to user-select layout
      const exactSuggestions = this.filterOutExistingShares(rawExactSuggestions).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);
      const suggestions = this.filterOutExistingShares(rawSuggestions).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);

      // lookup clickable entry
      // show if enabled and not already requested
      const lookupEntry = [];
      if (data.lookupEnabled && !lookup) {
        lookupEntry.push({
          id: 'global-lookup',
          isNoUser: true,
          displayName: t('files_sharing', 'Search globally'),
          lookup: true
        });
      }

      // if there is a condition specified, filter it
      const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this));
      const allSuggestions = exactSuggestions.concat(suggestions).concat(externalResults).concat(lookupEntry);

      // Count occurrences of display names in order to provide a distinguishable description if needed
      const nameCounts = allSuggestions.reduce((nameCounts, result) => {
        if (!result.displayName) {
          return nameCounts;
        }
        if (!nameCounts[result.displayName]) {
          nameCounts[result.displayName] = 0;
        }
        nameCounts[result.displayName]++;
        return nameCounts;
      }, {});
      this.suggestions = allSuggestions.map(item => {
        // Make sure that items with duplicate displayName get the shareWith applied as a description
        if (nameCounts[item.displayName] > 1 && !item.desc) {
          return {
            ...item,
            desc: item.shareWithDisplayNameUnique
          };
        }
        return item;
      });
      this.loading = false;
      console.info('suggestions', this.suggestions);
    },
    /**
     * Debounce getSuggestions
     *
     * @param {...*} args the arguments
     */
    debounceGetSuggestions: debounce__WEBPACK_IMPORTED_MODULE_4___default()(function () {
      this.getSuggestions(...arguments);
    }, 300),
    /**
     * Get the sharing recommendations
     */
    async getRecommendations() {
      this.loading = true;
      let request = null;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/sharees_recommended'), {
          params: {
            format: 'json',
            itemType: this.fileInfo.type
          }
        });
      } catch (error) {
        console.error('Error fetching recommendations', error);
        return;
      }

      // Add external results from the OCA.Sharing.ShareSearch api
      const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this));

      // flatten array of arrays
      const rawRecommendations = Object.values(request.data.ocs.data.exact).reduce((arr, elem) => arr.concat(elem), []);

      // remove invalid data and format to user-select layout
      this.recommendations = this.filterOutExistingShares(rawRecommendations).map(share => this.formatForMultiselect(share)).concat(externalResults);
      this.loading = false;
      console.info('recommendations', this.recommendations);
    },
    /**
     * Filter out existing shares from
     * the provided shares search results
     *
     * @param {object[]} shares the array of shares object
     * @return {object[]}
     */
    filterOutExistingShares(shares) {
      return shares.reduce((arr, share) => {
        // only check proper objects
        if (typeof share !== 'object') {
          return arr;
        }
        try {
          if (share.value.shareType === this.SHARE_TYPES.SHARE_TYPE_USER) {
            // filter out current user
            if (share.value.shareWith === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid) {
              return arr;
            }

            // filter out the owner of the share
            if (this.reshare && share.value.shareWith === this.reshare.owner) {
              return arr;
            }
          }

          // filter out existing mail shares
          if (share.value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
            const emails = this.linkShares.map(elem => elem.shareWith);
            if (emails.indexOf(share.value.shareWith.trim()) !== -1) {
              return arr;
            }
          } else {
            // filter out existing shares
            // creating an object of uid => type
            const sharesObj = this.shares.reduce((obj, elem) => {
              obj[elem.shareWith] = elem.type;
              return obj;
            }, {});

            // if shareWith is the same and the share type too, ignore it
            const key = share.value.shareWith.trim();
            if (key in sharesObj && sharesObj[key] === share.value.shareType) {
              return arr;
            }
          }

          // ALL GOOD
          // let's add the suggestion
          arr.push(share);
        } catch {
          return arr;
        }
        return arr;
      }, []);
    },
    /**
     * Get the icon based on the share type
     *
     * @param {number} type the share type
     * @return {string} the icon class
     */
    shareTypeToIcon(type) {
      switch (type) {
        case this.SHARE_TYPES.SHARE_TYPE_GUEST:
          // default is a user, other icons are here to differentiate
          // themselves from it, so let's not display the user icon
          // case this.SHARE_TYPES.SHARE_TYPE_REMOTE:
          // case this.SHARE_TYPES.SHARE_TYPE_USER:
          return {
            icon: 'icon-user',
            iconTitle: t('files_sharing', 'Guest')
          };
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
        case this.SHARE_TYPES.SHARE_TYPE_GROUP:
          return {
            icon: 'icon-group',
            iconTitle: t('files_sharing', 'Group')
          };
        case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
          return {
            icon: 'icon-mail',
            iconTitle: t('files_sharing', 'Email')
          };
        case this.SHARE_TYPES.SHARE_TYPE_CIRCLE:
          return {
            icon: 'icon-teams',
            iconTitle: t('files_sharing', 'Team')
          };
        case this.SHARE_TYPES.SHARE_TYPE_ROOM:
          return {
            icon: 'icon-room',
            iconTitle: t('files_sharing', 'Talk conversation')
          };
        case this.SHARE_TYPES.SHARE_TYPE_DECK:
          return {
            icon: 'icon-deck',
            iconTitle: t('files_sharing', 'Deck board')
          };
        case this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH:
          return {
            icon: 'icon-sciencemesh',
            iconTitle: t('files_sharing', 'ScienceMesh')
          };
        default:
          return {};
      }
    },
    /**
     * Format shares for the multiselect options
     *
     * @param {object} result select entry item
     * @return {object}
     */
    formatForMultiselect(result) {
      let subtitle;
      if (result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_USER && this.config.shouldAlwaysShowUnique) {
        var _result$shareWithDisp;
        subtitle = (_result$shareWithDisp = result.shareWithDisplayNameUnique) !== null && _result$shareWithDisp !== void 0 ? _result$shareWithDisp : '';
      } else if ((result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_REMOTE || result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) && result.value.server) {
        subtitle = t('files_sharing', 'on {server}', {
          server: result.value.server
        });
      } else if (result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
        subtitle = result.value.shareWith;
      } else {
        var _result$shareWithDesc;
        subtitle = (_result$shareWithDesc = result.shareWithDescription) !== null && _result$shareWithDesc !== void 0 ? _result$shareWithDesc : '';
      }
      return {
        shareWith: result.value.shareWith,
        shareType: result.value.shareType,
        user: result.uuid || result.value.shareWith,
        isNoUser: result.value.shareType !== this.SHARE_TYPES.SHARE_TYPE_USER,
        displayName: result.name || result.label,
        subtitle,
        shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
        ...this.shareTypeToIcon(result.value.shareType)
      };
    },
    /**
     * Process the new share request
     *
     * @param {object} value the multiselect option
     */
    async addShare(value) {
      // Clear the displayed selection
      this.value = null;
      if (value.lookup) {
        await this.getSuggestions(this.query, true);
        this.$nextTick(() => {
          // open the dropdown again
          this.$refs.select.$children[0].open = true;
        });
        return true;
      }

      // handle externalResults from OCA.Sharing.ShareSearch
      if (value.handler) {
        const share = await value.handler(this);
        this.$emit('add:share', new _models_Share_js__WEBPACK_IMPORTED_MODULE_8__["default"](share));
        return true;
      }
      this.loading = true;
      console.debug('Adding a new share from the input for', value);
      try {
        let password = null;
        if (this.config.enforcePasswordForPublicLink && value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
          password = await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_7__["default"])();
        }
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');
        const share = await this.createShare({
          path,
          shareType: value.shareType,
          shareWith: value.shareWith,
          password,
          permissions: this.fileInfo.sharePermissions & (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing.default_permissions,
          attributes: JSON.stringify(this.fileInfo.shareAttributes)
        });

        // If we had a password, we need to show it to the user as it was generated
        if (password) {
          share.newPassword = password;
          // Wait for the newly added share
          const component = await new Promise(resolve => {
            this.$emit('add:share', share, resolve);
          });

          // open the menu on the
          // freshly created share component
          component.open = true;
        } else {
          // Else we just add it normally
          this.$emit('add:share', share);
        }
        await this.getRecommendations();
      } catch (error) {
        this.$nextTick(() => {
          // open the dropdown again on error
          this.$refs.select.$children[0].open = true;
        });
        this.query = value.shareWith;
        console.error('Error while adding new share', error);
      } finally {
        this.loading = false;
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcInputField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcInputField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTimePickerNative.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTimePickerNative.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/CircleOutline.vue */ "./node_modules/vue-material-design-icons/CircleOutline.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Email.vue */ "./node_modules/vue-material-design-icons/Email.vue");
/* harmony import */ var vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Link.vue */ "./node_modules/vue-material-design-icons/Link.vue");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/ShareCircle.vue */ "./node_modules/vue-material-design-icons/ShareCircle.vue");
/* harmony import */ var vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/AccountCircleOutline.vue */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue");
/* harmony import */ var vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/Eye.vue */ "./node_modules/vue-material-design-icons/Eye.vue");
/* harmony import */ var vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-material-design-icons/Upload.vue */ "./node_modules/vue-material-design-icons/Upload.vue");
/* harmony import */ var vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue-material-design-icons/MenuDown.vue */ "./node_modules/vue-material-design-icons/MenuDown.vue");
/* harmony import */ var vue_material_design_icons_MenuUp_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! vue-material-design-icons/MenuUp.vue */ "./node_modules/vue-material-design-icons/MenuUp.vue");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var _components_ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../components/ExternalShareAction.vue */ "./apps/files_sharing/src/components/ExternalShareAction.vue");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");





























/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingDetailsTab',
  components: {
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcInputField: _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcPasswordField: _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcDateTimePickerNative: _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_8__["default"],
    CloseIcon: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    CircleIcon: vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    EditIcon: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    ExternalShareAction: _components_ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    LinkIcon: vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    GroupIcon: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    ShareIcon: vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    UserIcon: vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    UploadIcon: vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
    ViewIcon: vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    MenuDownIcon: vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    MenuUpIcon: vue_material_design_icons_MenuUp_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_21__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_26__["default"], _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_25__["default"], _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_27__["default"]],
  props: {
    shareRequestValue: {
      type: Object,
      required: false
    },
    fileInfo: {
      type: Object,
      required: true
    },
    share: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      writeNoteToRecipientIsChecked: false,
      sharingPermission: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.ALL.toString(),
      revertSharingPermission: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.ALL.toString(),
      setCustomPermissions: false,
      passwordError: false,
      advancedSectionAccordionExpanded: false,
      bundledPermissions: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS,
      isFirstComponentLoad: true,
      test: false,
      creating: false,
      ExternalShareActions: OCA.Sharing.ExternalShareActions.state
    };
  },
  computed: {
    title() {
      switch (this.share.type) {
        case this.SHARE_TYPES.SHARE_TYPE_USER:
          return t('files_sharing', 'Share with {userName}', {
            userName: this.share.shareWithDisplayName
          });
        case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
          return t('files_sharing', 'Share with email {email}', {
            email: this.share.shareWith
          });
        case this.SHARE_TYPES.SHARE_TYPE_LINK:
          return t('files_sharing', 'Share link');
        case this.SHARE_TYPES.SHARE_TYPE_GROUP:
          return t('files_sharing', 'Share with group');
        case this.SHARE_TYPES.SHARE_TYPE_ROOM:
          return t('files_sharing', 'Share in conversation');
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE:
          {
            const [user, server] = this.share.shareWith.split('@');
            return t('files_sharing', 'Share with {user} on remote server {server}', {
              user,
              server
            });
          }
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
          return t('files_sharing', 'Share with remote group');
        case this.SHARE_TYPES.SHARE_TYPE_GUEST:
          return t('files_sharing', 'Share with guest');
        default:
          {
            if (this.share.id) {
              // Share already exists
              return t('files_sharing', 'Update share');
            } else {
              return t('files_sharing', 'Create share');
            }
          }
      }
    },
    /**
     * Can the sharee edit the shared file ?
     */
    canEdit: {
      get() {
        return this.share.hasUpdatePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isEditChecked: checked
        });
      }
    },
    /**
     * Can the sharee create the shared file ?
     */
    canCreate: {
      get() {
        return this.share.hasCreatePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isCreateChecked: checked
        });
      }
    },
    /**
     * Can the sharee delete the shared file ?
     */
    canDelete: {
      get() {
        return this.share.hasDeletePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isDeleteChecked: checked
        });
      }
    },
    /**
     * Can the sharee reshare the file ?
     */
    canReshare: {
      get() {
        return this.share.hasSharePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isReshareChecked: checked
        });
      }
    },
    /**
     * Can the sharee download files or only view them ?
     */
    canDownload: {
      get() {
        var _this$share$attribute;
        return ((_this$share$attribute = this.share.attributes.find(attr => attr.key === 'download')) === null || _this$share$attribute === void 0 ? void 0 : _this$share$attribute.enabled) || false;
      },
      set(checked) {
        // Find the 'download' attribute and update its value
        const downloadAttr = this.share.attributes.find(attr => attr.key === 'download');
        if (downloadAttr) {
          downloadAttr.enabled = checked;
        }
      }
    },
    /**
     * Is this share readable
     * Needed for some federated shares that might have been added from file drop links
     */
    hasRead: {
      get() {
        return this.share.hasReadPermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isReadChecked: checked
        });
      }
    },
    /**
     * Does the current share have an expiration date
     *
     * @return {boolean}
     */
    hasExpirationDate: {
      get() {
        return this.isValidShareAttribute(this.share.expireDate);
      },
      set(enabled) {
        this.share.expireDate = enabled ? this.formatDateToString(this.defaultExpiryDate) : '';
      }
    },
    /**
     * Is the current share password protected ?
     *
     * @return {boolean}
     */
    isPasswordProtected: {
      get() {
        return this.config.enforcePasswordForPublicLink || !!this.share.password;
      },
      async set(enabled) {
        if (enabled) {
          this.share.password = await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_23__["default"])();
          this.$set(this.share, 'newPassword', this.share.password);
        } else {
          this.share.password = '';
          this.$delete(this.share, 'newPassword');
        }
      }
    },
    /**
     * Is the current share a folder ?
     *
     * @return {boolean}
     */
    isFolder() {
      return this.fileInfo.type === 'dir';
    },
    /**
     * @return {boolean}
     */
    isSetDownloadButtonVisible() {
      const allowedMimetypes = [
      // Office documents
      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation'];
      return this.isFolder || allowedMimetypes.includes(this.fileInfo.mimetype);
    },
    isPasswordEnforced() {
      return this.isPublicShare && this.config.enforcePasswordForPublicLink;
    },
    defaultExpiryDate() {
      if ((this.isGroupShare || this.isUserShare) && this.config.isDefaultInternalExpireDateEnabled) {
        return new Date(this.config.defaultInternalExpirationDate);
      } else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
        return new Date(this.config.defaultRemoteExpireDateEnabled);
      } else if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
        return new Date(this.config.defaultExpirationDate);
      }
      return new Date(new Date().setDate(new Date().getDate() + 1));
    },
    isUserShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_USER;
    },
    isGroupShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP;
    },
    isNewShare() {
      return !this.share.id;
    },
    allowsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
          return true;
        }
      }
      return false;
    },
    hasFileDropPermissions() {
      return this.share.permissions === this.bundledPermissions.FILE_DROP;
    },
    shareButtonText() {
      if (this.isNewShare) {
        return t('files_sharing', 'Save share');
      }
      return t('files_sharing', 'Update share');
    },
    /**
     * Can the sharer set whether the sharee can edit the file ?
     *
     * @return {boolean}
     */
    canSetEdit() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_UPDATE || this.canEdit;
    },
    /**
     * Can the sharer set whether the sharee can create the file ?
     *
     * @return {boolean}
     */
    canSetCreate() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_CREATE || this.canCreate;
    },
    /**
     * Can the sharer set whether the sharee can delete the file ?
     *
     * @return {boolean}
     */
    canSetDelete() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_DELETE || this.canDelete;
    },
    /**
     * Can the sharer set whether the sharee can reshare the file ?
     *
     * @return {boolean}
     */
    canSetReshare() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_SHARE || this.canReshare;
    },
    /**
     * Can the sharer set whether the sharee can download the file ?
     *
     * @return {boolean}
     */
    canSetDownload() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.canDownload() || this.canDownload;
    },
    // if newPassword exists, but is empty, it means
    // the user deleted the original password
    hasUnsavedPassword() {
      return this.share.newPassword !== undefined;
    },
    passwordExpirationTime() {
      if (!this.isValidShareAttribute(this.share.passwordExpirationTime)) {
        return null;
      }
      const expirationTime = moment(this.share.passwordExpirationTime);
      if (expirationTime.diff(moment()) < 0) {
        return false;
      }
      return expirationTime.fromNow();
    },
    /**
     * Is Talk enabled?
     *
     * @return {boolean}
     */
    isTalkEnabled() {
      return OC.appswebroots.spreed !== undefined;
    },
    /**
     * Is it possible to protect the password by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalkAvailable() {
      return this.isPasswordProtected && this.isTalkEnabled;
    },
    /**
     * Is the current share password protected by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalk: {
      get() {
        return this.share.sendPasswordByTalk;
      },
      async set(enabled) {
        this.share.sendPasswordByTalk = enabled;
      }
    },
    /**
     * Is the current share an email share ?
     *
     * @return {boolean}
     */
    isEmailShareType() {
      return this.share ? this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL : false;
    },
    canTogglePasswordProtectedByTalkAvailable() {
      if (!this.isPublicShare || !this.isPasswordProtected) {
        // Makes no sense
        return false;
      } else if (this.isEmailShareType && !this.hasUnsavedPassword) {
        // For email shares we need a new password in order to enable or
        // disable
        return false;
      }

      // Is Talk enabled?
      return OC.appswebroots.spreed !== undefined;
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    },
    customPermissionsList() {
      // Key order will be different, because ATOMIC_PERMISSIONS are numbers
      const translatedPermissions = {
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.READ]: this.t('files_sharing', 'Read'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.CREATE]: this.t('files_sharing', 'Create'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.UPDATE]: this.t('files_sharing', 'Edit'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.SHARE]: this.t('files_sharing', 'Share'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.DELETE]: this.t('files_sharing', 'Delete')
      };
      return [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.READ, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.CREATE, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.UPDATE, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.SHARE, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.DELETE].filter(permission => (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.hasPermissions)(this.share.permissions, permission)).map((permission, index) => index === 0 ? translatedPermissions[permission] : translatedPermissions[permission].toLocaleLowerCase((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getLanguage)())).join(', ');
    },
    advancedControlExpandedValue() {
      return this.advancedSectionAccordionExpanded ? 'true' : 'false';
    },
    errorPasswordLabel() {
      if (this.passwordError) {
        return t('files_sharing', "Password field can't be empty");
      }
      return undefined;
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    externalLinkActions() {
      const filterValidAction = action => (action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.Type.SHARE_TYPE_LINK) || action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.Type.SHARE_TYPE_EMAIL)) && action.advanced;
      // filter only the advanced registered actions for said link
      return this.ExternalShareActions.actions.filter(filterValidAction);
    }
  },
  watch: {
    setCustomPermissions(isChecked) {
      if (isChecked) {
        this.sharingPermission = 'custom';
      } else {
        this.sharingPermission = this.revertSharingPermission;
      }
    }
  },
  beforeMount() {
    this.initializePermissions();
    this.initializeAttributes();
    console.debug('shareSentIn', this.share);
    console.debug('config', this.config);
  },
  mounted() {
    var _this$$refs$quickPerm;
    (_this$$refs$quickPerm = this.$refs.quickPermissions) === null || _this$$refs$quickPerm === void 0 || (_this$$refs$quickPerm = _this$$refs$quickPerm.querySelector('input:checked')) === null || _this$$refs$quickPerm === void 0 || _this$$refs$quickPerm.focus();
  },
  methods: {
    updateAtomicPermissions() {
      let {
        isReadChecked = this.hasRead,
        isEditChecked = this.canEdit,
        isCreateChecked = this.canCreate,
        isDeleteChecked = this.canDelete,
        isReshareChecked = this.canReshare
      } = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      // calc permissions if checked
      const permissions = 0 | (isReadChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.READ : 0) | (isCreateChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.CREATE : 0) | (isDeleteChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.DELETE : 0) | (isEditChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.UPDATE : 0) | (isReshareChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.ATOMIC_PERMISSIONS.SHARE : 0);
      this.share.permissions = permissions;
    },
    expandCustomPermissions() {
      if (!this.advancedSectionAccordionExpanded) {
        this.advancedSectionAccordionExpanded = true;
      }
      this.toggleCustomPermissions();
    },
    toggleCustomPermissions(selectedPermission) {
      const isCustomPermissions = this.sharingPermission === 'custom';
      this.revertSharingPermission = !isCustomPermissions ? selectedPermission : 'custom';
      this.setCustomPermissions = isCustomPermissions;
    },
    async initializeAttributes() {
      if (this.isNewShare) {
        if (this.isPasswordEnforced && this.isPublicShare) {
          this.$set(this.share, 'newPassword', await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_23__["default"])());
          this.advancedSectionAccordionExpanded = true;
        }
        /* Set default expiration dates if configured */
        if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
          this.share.expireDate = this.config.defaultExpirationDate.toDateString();
        } else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
          this.share.expireDate = this.config.defaultRemoteExpirationDateString.toDateString();
        } else if (this.config.isDefaultInternalExpireDateEnabled) {
          this.share.expireDate = this.config.defaultInternalExpirationDate.toDateString();
        }
        if (this.isValidShareAttribute(this.share.expireDate)) {
          this.advancedSectionAccordionExpanded = true;
        }
        return;
      }

      // If there is an enforced expiry date, then existing shares created before enforcement
      // have no expiry date, hence we set it here.
      if (!this.isValidShareAttribute(this.share.expireDate) && this.isExpiryDateEnforced) {
        this.hasExpirationDate = true;
      }
      if (this.isValidShareAttribute(this.share.password) || this.isValidShareAttribute(this.share.expireDate) || this.isValidShareAttribute(this.share.label)) {
        this.advancedSectionAccordionExpanded = true;
      }
    },
    handleShareType() {
      if ('shareType' in this.share) {
        this.share.type = this.share.shareType;
      } else if (this.share.share_type) {
        this.share.type = this.share.share_type;
      }
    },
    handleDefaultPermissions() {
      if (this.isNewShare) {
        const defaultPermissions = this.config.defaultPermissions;
        if (defaultPermissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.READ_ONLY || defaultPermissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.ALL) {
          this.sharingPermission = defaultPermissions.toString();
        } else {
          this.sharingPermission = 'custom';
          this.share.permissions = defaultPermissions;
          this.advancedSectionAccordionExpanded = true;
          this.setCustomPermissions = true;
        }
      }
    },
    handleCustomPermissions() {
      if (!this.isNewShare && (this.hasCustomPermissions || this.share.setCustomPermissions)) {
        this.sharingPermission = 'custom';
        this.advancedSectionAccordionExpanded = true;
        this.setCustomPermissions = true;
      } else if (this.share.permissions) {
        this.sharingPermission = this.share.permissions.toString();
      }
    },
    initializePermissions() {
      this.handleShareType();
      this.handleDefaultPermissions();
      this.handleCustomPermissions();
    },
    async saveShare() {
      var _this$$refs$externalL;
      const permissionsAndAttributes = ['permissions', 'attributes', 'note', 'expireDate'];
      const publicShareAttributes = ['label', 'password', 'hideDownload'];
      if (this.isPublicShare) {
        permissionsAndAttributes.push(...publicShareAttributes);
      }
      const sharePermissionsSet = parseInt(this.sharingPermission);
      if (this.setCustomPermissions) {
        this.updateAtomicPermissions();
      } else {
        this.share.permissions = sharePermissionsSet;
      }
      if (!this.isFolder && this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.ALL) {
        // It's not possible to create an existing file.
        this.share.permissions = _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_28__.BUNDLED_PERMISSIONS.ALL_FILE;
      }
      if (!this.writeNoteToRecipientIsChecked) {
        this.share.note = '';
      }
      if (this.isPasswordProtected) {
        if (this.hasUnsavedPassword && this.isValidShareAttribute(this.share.newPassword)) {
          this.share.password = this.share.newPassword;
          this.$delete(this.share, 'newPassword');
        } else if (this.isPasswordEnforced && !this.isValidShareAttribute(this.share.password)) {
          this.passwordError = true;
        }
      } else {
        this.share.password = '';
      }
      if (!this.hasExpirationDate) {
        this.share.expireDate = '';
      }
      if (this.isNewShare) {
        const incomingShare = {
          permissions: this.share.permissions,
          shareType: this.share.type,
          shareWith: this.share.shareWith,
          attributes: this.share.attributes,
          note: this.share.note,
          fileInfo: this.fileInfo
        };
        incomingShare.expireDate = this.hasExpirationDate ? this.share.expireDate : '';
        if (this.isPasswordProtected) {
          incomingShare.password = this.share.password;
        }
        this.creating = true;
        const share = await this.addShare(incomingShare, this.fileInfo);
        this.creating = false;
        this.share = share;
        this.$emit('add:share', this.share);
      } else {
        this.queueUpdate(...permissionsAndAttributes);
      }
      if (((_this$$refs$externalL = this.$refs.externalLinkActions) === null || _this$$refs$externalL === void 0 ? void 0 : _this$$refs$externalL.length) > 0) {
        await Promise.allSettled(this.$refs.externalLinkActions.map(action => {
          var _action$$children$at, _action$$children$at2, _action$$children$at3;
          if (typeof ((_action$$children$at = action.$children.at(0)) === null || _action$$children$at === void 0 ? void 0 : _action$$children$at.onSave) !== 'function') {
            return Promise.resolve();
          }
          return (_action$$children$at2 = action.$children.at(0)) === null || _action$$children$at2 === void 0 || (_action$$children$at3 = _action$$children$at2.onSave) === null || _action$$children$at3 === void 0 ? void 0 : _action$$children$at3.call(_action$$children$at2);
        }));
      }
      this.$emit('close-sharing-details');
    },
    /**
     * Process the new share request
     *
     * @param {Share} share incoming share object
     * @param {object} fileInfo file data
     */
    async addShare(share, fileInfo) {
      console.debug('Adding a new share from the input for', share);
      try {
        const path = (fileInfo.path + '/' + fileInfo.name).replace('//', '/');
        const resultingShare = await this.createShare({
          path,
          shareType: share.shareType,
          shareWith: share.shareWith,
          permissions: share.permissions,
          expireDate: share.expireDate,
          attributes: JSON.stringify(share.attributes),
          ...(share.note ? {
            note: share.note
          } : {}),
          ...(share.password ? {
            password: share.password
          } : {})
        });
        return resultingShare;
      } catch (error) {
        console.error('Error while adding new share', error);
      } finally {
        // this.loading = false // No loader here yet
      }
    },
    async removeShare() {
      await this.onDelete();
      this.$emit('close-sharing-details');
    },
    /**
     * Update newPassword values
     * of share. If password is set but not newPassword
     * then the user did not changed the password
     * If both co-exists, the password have changed and
     * we show it in plain text.
     * Then on submit (or menu close), we sync it.
     *
     * @param {string} password the changed password
     */
    onPasswordChange(password) {
      this.passwordError = !this.isValidShareAttribute(password);
      this.$set(this.share, 'newPassword', password);
    },
    /**
     * Update the password along with "sendPasswordByTalk".
     *
     * If the password was modified the new password is sent; otherwise
     * updating a mail share would fail, as in that case it is required that
     * a new password is set when enabling or disabling
     * "sendPasswordByTalk".
     */
    onPasswordProtectedByTalkChange() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
      }
      this.queueUpdate('sendPasswordByTalk', 'password');
    },
    isValidShareAttribute(value) {
      if ([null, undefined].includes(value)) {
        return false;
      }
      if (!(value.trim().length > 0)) {
        return false;
      }
      return true;
    },
    getShareTypeIcon(type) {
      switch (type) {
        case this.SHARE_TYPES.SHARE_TYPE_LINK:
          return vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_13__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_GUEST:
          return vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_16__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
        case this.SHARE_TYPES.SHARE_TYPE_GROUP:
          return vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_14__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
          return vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_12__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_CIRCLE:
          return vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_9__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_ROOM:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_15__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_DECK:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_15__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_15__["default"];
        default:
          return null;
        // Or a default icon component if needed
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/SharingEntryInherited.vue */ "./apps/files_sharing/src/components/SharingEntryInherited.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInherited',
  components: {
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    SharingEntryInherited: _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    }
  },
  data() {
    return {
      loaded: false,
      loading: false,
      showInheritedShares: false,
      shares: []
    };
  },
  computed: {
    showInheritedSharesIcon() {
      if (this.loading) {
        return 'icon-loading-small';
      }
      if (this.showInheritedShares) {
        return 'icon-triangle-n';
      }
      return 'icon-triangle-s';
    },
    mainTitle() {
      return t('files_sharing', 'Others with access');
    },
    subTitle() {
      return this.showInheritedShares && this.shares.length === 0 ? t('files_sharing', 'No other accounts with access found') : '';
    },
    toggleTooltip() {
      return this.fileInfo.type === 'dir' ? t('files_sharing', 'Toggle list of others with access to this directory') : t('files_sharing', 'Toggle list of others with access to this file');
    },
    fullPath() {
      const path = "".concat(this.fileInfo.path, "/").concat(this.fileInfo.name);
      return path.replace('//', '/');
    }
  },
  watch: {
    fileInfo() {
      this.resetState();
    }
  },
  methods: {
    /**
     * Toggle the list view and fetch/reset the state
     */
    toggleInheritedShares() {
      this.showInheritedShares = !this.showInheritedShares;
      if (this.showInheritedShares) {
        this.fetchInheritedShares();
      } else {
        this.resetState();
      }
    },
    /**
     * Fetch the Inherited Shares array
     */
    async fetchInheritedShares() {
      this.loading = true;
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', {
          path: this.fullPath
        });
        const shares = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get(url);
        this.shares = shares.data.ocs.data.map(share => new _models_Share_js__WEBPACK_IMPORTED_MODULE_3__["default"](share)).sort((a, b) => b.createdTime - a.createdTime);
        console.info(this.shares);
        this.loaded = true;
      } catch (error) {
        OC.Notification.showTemporary(t('files_sharing', 'Unable to fetch inherited shares'), {
          type: 'error'
        });
      } finally {
        this.loading = false;
      }
    },
    /**
     * Reset current component state
     */
    resetState() {
      this.loaded = false;
      this.loading = false;
      this.showInheritedShares = false;
      this.shares = [];
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      const index = this.shares.findIndex(item => item === share);
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.splice(index, 1);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js":
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SharingEntryLink.vue */ "./apps/files_sharing/src/components/SharingEntryLink.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");


// eslint-disable-next-line no-unused-vars




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingLinkList',
  components: {
    SharingEntryLink: _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_2__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_4__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    shares: {
      type: Array,
      default: () => [],
      required: true
    },
    canReshare: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      canLinkShare: (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)().files_sharing.public.enabled
    };
  },
  computed: {
    /**
     * Do we have link shares?
     * Using this to still show the `new link share`
     * button regardless of mail shares
     *
     * @return {Array}
     */
    hasLinkShares() {
      return this.shares.filter(share => share.type === this.SHARE_TYPES.SHARE_TYPE_LINK).length > 0;
    },
    /**
     * Do we have any link or email shares?
     *
     * @return {boolean}
     */
    hasShares() {
      return this.shares.length > 0;
    }
  },
  methods: {
    /**
     * Add a new share into the link shares list
     * and return the newly created share component
     *
     * @param {Share} share the share to add to the array
     * @param {Function} resolve a function to run after the share is added and its component initialized
     */
    addShare(share, resolve) {
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.unshift(share);
      this.awaitForShare(share, resolve);
    },
    /**
     * Await for next tick and render after the list updated
     * Then resolve with the matched vue component of the
     * provided share object
     *
     * @param {Share} share newly created share
     * @param {Function} resolve a function to execute after
     */
    awaitForShare(share, resolve) {
      this.$nextTick(() => {
        const newShare = this.$children.find(component => component.share === share);
        if (newShare) {
          resolve(newShare);
        }
      });
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      const index = this.shares.findIndex(item => item === share);
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.splice(index, 1);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../components/SharingEntry.vue */ "./apps/files_sharing/src/components/SharingEntry.vue");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
// eslint-disable-next-line no-unused-vars



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingList',
  components: {
    SharingEntry: _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    shares: {
      type: Array,
      default: () => [],
      required: true
    }
  },
  computed: {
    hasShares() {
      return this.shares.length === 0;
    },
    isUnique() {
      return share => {
        return [...this.shares].filter(item => {
          return share.type === this.SHARE_TYPES.SHARE_TYPE_USER && share.shareWithDisplayName === item.shareWithDisplayName;
        }).length <= 1;
      };
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js":
/*!*********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! nextcloud-vue-collections */ "./node_modules/nextcloud-vue-collections/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/SharedWithMe.js */ "./apps/files_sharing/src/utils/SharedWithMe.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../components/SharingEntryInternal.vue */ "./apps/files_sharing/src/components/SharingEntryInternal.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/SharingInput.vue */ "./apps/files_sharing/src/components/SharingInput.vue");
/* harmony import */ var _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./SharingInherited.vue */ "./apps/files_sharing/src/views/SharingInherited.vue");
/* harmony import */ var _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./SharingLinkList.vue */ "./apps/files_sharing/src/views/SharingLinkList.vue");
/* harmony import */ var _SharingList_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./SharingList.vue */ "./apps/files_sharing/src/views/SharingList.vue");
/* harmony import */ var _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./SharingDetailsTab.vue */ "./apps/files_sharing/src/views/SharingDetailsTab.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingTab',
  components: {
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    CollectionList: nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__.CollectionList,
    SharingEntryInternal: _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    SharingInherited: _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    SharingInput: _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    SharingLinkList: _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    SharingList: _SharingList_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    SharingDetailsTab: _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_15__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_8__["default"]],
  data() {
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__["default"](),
      deleteEvent: null,
      error: '',
      expirationInterval: null,
      loading: true,
      fileInfo: null,
      // reshare Share object
      reshare: null,
      sharedWithMe: {},
      shares: [],
      linkShares: [],
      sections: OCA.Sharing.ShareTabSections.getSections(),
      projectsEnabled: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('core', 'projects_enabled', false),
      showSharingDetailsView: false,
      shareDetailsData: {},
      returnFocusElement: null
    };
  },
  computed: {
    /**
     * Is this share shared with me?
     *
     * @return {boolean}
     */
    isSharedWithMe() {
      return Object.keys(this.sharedWithMe).length > 0;
    },
    canReshare() {
      return !!(this.fileInfo.permissions & OC.PERMISSION_SHARE) || !!(this.reshare && this.reshare.hasSharePermission && this.config.isResharingAllowed);
    }
  },
  methods: {
    /**
     * Update current fileInfo and fetch new data
     *
     * @param {object} fileInfo the current file FileInfo
     */
    async update(fileInfo) {
      this.fileInfo = fileInfo;
      this.resetState();
      this.getShares();
    },
    /**
     * Get the existing shares infos
     */
    async getShares() {
      try {
        this.loading = true;

        // init params
        const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
        const format = 'json';
        // TODO: replace with proper getFUllpath implementation of our own FileInfo model
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');

        // fetch shares
        const fetchShares = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(shareUrl, {
          params: {
            format,
            path,
            reshares: true
          }
        });
        const fetchSharedWithMe = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(shareUrl, {
          params: {
            format,
            path,
            shared_with_me: true
          }
        });

        // wait for data
        const [shares, sharedWithMe] = await Promise.all([fetchShares, fetchSharedWithMe]);
        this.loading = false;

        // process results
        this.processSharedWithMe(sharedWithMe);
        this.processShares(shares);
      } catch (error) {
        var _error$response$data;
        if ((_error$response$data = error.response.data) !== null && _error$response$data !== void 0 && (_error$response$data = _error$response$data.ocs) !== null && _error$response$data !== void 0 && (_error$response$data = _error$response$data.meta) !== null && _error$response$data !== void 0 && _error$response$data.message) {
          this.error = error.response.data.ocs.meta.message;
        } else {
          this.error = t('files_sharing', 'Unable to load the shares list');
        }
        this.loading = false;
        console.error('Error loading the shares list', error);
      }
    },
    /**
     * Reset the current view to its default state
     */
    resetState() {
      clearInterval(this.expirationInterval);
      this.loading = true;
      this.error = '';
      this.sharedWithMe = {};
      this.shares = [];
      this.linkShares = [];
      this.showSharingDetailsView = false;
      this.shareDetailsData = {};
    },
    /**
     * Update sharedWithMe.subtitle with the appropriate
     * expiration time left
     *
     * @param {Share} share the sharedWith Share object
     */
    updateExpirationSubtitle(share) {
      const expiration = moment(share.expireDate).unix();
      this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'Expires {relativetime}', {
        relativetime: OC.Util.relativeModifiedDate(expiration * 1000)
      }));

      // share have expired
      if (moment().unix() > expiration) {
        clearInterval(this.expirationInterval);
        // TODO: clear ui if share is expired
        this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'this share just expired.'));
      }
    },
    /**
     * Process the current shares data
     * and init shares[]
     *
     * @param {object} share the share ocs api request data
     * @param {object} share.data the request data
     */
    processShares(_ref) {
      let {
        data
      } = _ref;
      if (data.ocs && data.ocs.data && data.ocs.data.length > 0) {
        // create Share objects and sort by newest
        const shares = data.ocs.data.map(share => new _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"](share)).sort((a, b) => b.createdTime - a.createdTime);
        this.linkShares = shares.filter(share => share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL);
        this.shares = shares.filter(share => share.type !== this.SHARE_TYPES.SHARE_TYPE_LINK && share.type !== this.SHARE_TYPES.SHARE_TYPE_EMAIL);
        console.debug('Processed', this.linkShares.length, 'link share(s)');
        console.debug('Processed', this.shares.length, 'share(s)');
      }
    },
    /**
     * Process the sharedWithMe share data
     * and init sharedWithMe
     *
     * @param {object} share the share ocs api request data
     * @param {object} share.data the request data
     */
    processSharedWithMe(_ref2) {
      let {
        data
      } = _ref2;
      if (data.ocs && data.ocs.data && data.ocs.data[0]) {
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"](data);
        const title = (0,_utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_6__.shareWithTitle)(share);
        const displayName = share.ownerDisplayName;
        const user = share.owner;
        this.sharedWithMe = {
          displayName,
          title,
          user
        };
        this.reshare = share;

        // If we have an expiration date, use it as subtitle
        // Refresh the status every 10s and clear if expired
        if (share.expireDate && moment(share.expireDate).unix() > moment().unix()) {
          // first update
          this.updateExpirationSubtitle(share);
          // interval update
          this.expirationInterval = setInterval(this.updateExpirationSubtitle, 10000, share);
        }
      } else if (this.fileInfo && this.fileInfo.shareOwnerId !== undefined ? this.fileInfo.shareOwnerId !== OC.currentUser : false) {
        // Fallback to compare owner and current user.
        this.sharedWithMe = {
          displayName: this.fileInfo.shareOwner,
          title: t('files_sharing', 'Shared with you by {owner}', {
            owner: this.fileInfo.shareOwner
          }, undefined, {
            escape: false
          }),
          user: this.fileInfo.shareOwnerId
        };
      }
    },
    /**
     * Add a new share into the shares list
     * and return the newly created share component
     *
     * @param {Share} share the share to add to the array
     * @param {Function} [resolve] a function to run after the share is added and its component initialized
     */
    addShare(share) {
      let resolve = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : () => {};
      // only catching share type MAIL as link shares are added differently
      // meaning: not from the ShareInput
      if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
        this.linkShares.unshift(share);
      } else {
        this.shares.unshift(share);
      }
      this.awaitForShare(share, resolve);
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      // Get reference for this.linkShares or this.shares
      const shareList = share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL || share.type === this.SHARE_TYPES.SHARE_TYPE_LINK ? this.linkShares : this.shares;
      const index = shareList.findIndex(item => item.id === share.id);
      if (index !== -1) {
        shareList.splice(index, 1);
      }
    },
    /**
     * Await for next tick and render after the list updated
     * Then resolve with the matched vue component of the
     * provided share object
     *
     * @param {Share} share newly created share
     * @param {Function} resolve a function to execute after
     */
    awaitForShare(share, resolve) {
      this.$nextTick(() => {
        let listComponent = this.$refs.shareList;
        // Only mail shares comes from the input, link shares
        // are managed internally in the SharingLinkList component
        if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
          listComponent = this.$refs.linkShareList;
        }
        const newShare = listComponent.$children.find(component => component.share === share);
        if (newShare) {
          resolve(newShare);
        }
      });
    },
    toggleShareDetailsView(eventData) {
      if (!this.showSharingDetailsView) {
        const isAction = Array.from(document.activeElement.classList).some(className => className.startsWith('action-'));
        if (isAction) {
          var _document$activeEleme;
          const menuId = (_document$activeEleme = document.activeElement.closest('[role="menu"]')) === null || _document$activeEleme === void 0 ? void 0 : _document$activeEleme.id;
          this.returnFocusElement = document.querySelector("[aria-controls=\"".concat(menuId, "\"]"));
        } else {
          this.returnFocusElement = document.activeElement;
        }
      }
      if (eventData) {
        this.shareDetailsData = eventData;
      }
      this.showSharingDetailsView = !this.showSharingDetailsView;
      if (!this.showSharingDetailsView) {
        this.$nextTick(() => {
          var _this$returnFocusElem;
          // Wait for next tick as the element must be visible to be focused
          (_this$returnFocusElem = this.returnFocusElement) === null || _this$returnFocusElem === void 0 || _this$returnFocusElem.focus();
          this.returnFocusElement = null;
        });
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356":
/*!**********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356 ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.data.is, _vm._g(_vm._b({
    tag: "Component"
  }, "Component", _vm.data, false), _vm.action.handlers), [_vm._v("\n\t" + _vm._s(_vm.data.text) + "\n")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry"
  }, [_c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": _vm.share.type !== _vm.SHARE_TYPES.SHARE_TYPE_USER,
      user: _vm.share.shareWith,
      "display-name": _vm.share.shareWithDisplayName,
      "menu-position": "left",
      url: _vm.share.shareWithAvatar
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__summary"
  }, [_c(_vm.share.shareWithLink ? "a" : "div", {
    tag: "component",
    staticClass: "sharing-entry__summary__desc",
    attrs: {
      title: _vm.tooltip,
      "aria-label": _vm.tooltip,
      href: _vm.share.shareWithLink
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.title) + "\n\t\t\t\t"), !_vm.isUnique ? _c("span", {
    staticClass: "sharing-entry__summary__desc-unique"
  }, [_vm._v(" (" + _vm._s(_vm.share.shareWithDisplayNameUnique) + ")")]) : _vm._e(), _vm._v(" "), _vm.hasStatus && _vm.share.status.message ? _c("small", [_vm._v("(" + _vm._s(_vm.share.status.message) + ")")]) : _vm._e()])]), _vm._v(" "), _c("SharingEntryQuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  })], 1), _vm._v(" "), _c("NcButton", {
    staticClass: "sharing-entry__action",
    attrs: {
      "data-cy-files-sharing-share-actions": "",
      "aria-label": _vm.t("files_sharing", "Open Sharing Details"),
      type: "tertiary"
    },
    on: {
      click: function ($event) {
        return _vm.openSharingDetails(_vm.share);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DotsHorizontalIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("SharingEntrySimple", {
    key: _vm.share.id,
    staticClass: "sharing-entry__inherited",
    attrs: {
      title: _vm.share.shareWithDisplayName
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("NcAvatar", {
          staticClass: "sharing-entry__avatar",
          attrs: {
            user: _vm.share.shareWith,
            "display-name": _vm.share.shareWithDisplayName
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionText", {
    attrs: {
      icon: "icon-user"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Added by {initiator}", {
    initiator: _vm.share.ownerDisplayName
  })) + "\n\t")]), _vm._v(" "), _vm.share.viaPath && _vm.share.viaFileid ? _c("NcActionLink", {
    attrs: {
      icon: "icon-folder",
      href: _vm.viaFileTargetUrl
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Via “{folder}”", {
    folder: _vm.viaFolderName
  })) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.share.canDelete ? _c("NcActionButton", {
    attrs: {
      icon: "icon-close"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.onDelete.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Unshare")) + "\n\t")]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", [_c("SharingEntrySimple", {
    ref: "shareEntrySimple",
    staticClass: "sharing-entry__internal",
    attrs: {
      title: _vm.t("files_sharing", "Internal link"),
      subtitle: _vm.internalLinkSubtitle
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("div", {
          staticClass: "avatar-external icon-external-white"
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionButton", {
    attrs: {
      title: _vm.copyLinkTooltip,
      "aria-label": _vm.copyLinkTooltip
    },
    on: {
      click: _vm.copyLink
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.copied && _vm.copySuccess ? _c("CheckIcon", {
          staticClass: "icon-checkmark-color",
          attrs: {
            size: 20
          }
        }) : _c("ClipboardIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  })], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry sharing-entry__link",
    class: {
      "sharing-entry--share": _vm.share
    }
  }, [_c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": true,
      "icon-class": _vm.isEmailShareType ? "avatar-link-share icon-mail-white" : "avatar-link-share icon-public-white"
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__summary"
  }, [_c("div", {
    staticClass: "sharing-entry__desc"
  }, [_c("span", {
    staticClass: "sharing-entry__title",
    attrs: {
      title: _vm.title
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.title) + "\n\t\t\t")]), _vm._v(" "), _vm.subtitle ? _c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.subtitle) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share && _vm.share.permissions !== undefined ? _c("SharingEntryQuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  }) : _vm._e()], 1), _vm._v(" "), _vm.share && !_vm.isEmailShareType && _vm.share.token ? _c("NcActions", {
    ref: "copyButton",
    staticClass: "sharing-entry__copy"
  }, [_c("NcActionButton", {
    attrs: {
      title: _vm.copyLinkTooltip,
      "aria-label": _vm.copyLinkTooltip
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.copyLink.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.copied && _vm.copySuccess ? _c("CheckIcon", {
          staticClass: "icon-checkmark-color",
          attrs: {
            size: 20
          }
        }) : _c("ClipboardIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 4269614823)
  })], 1) : _vm._e()], 1), _vm._v(" "), !_vm.pending && (_vm.pendingPassword || _vm.pendingEnforcedPassword || _vm.pendingExpirationDate) ? _c("NcActions", {
    staticClass: "sharing-entry__actions",
    attrs: {
      "aria-label": _vm.actionsTooltip,
      "menu-align": "right",
      open: _vm.open
    },
    on: {
      "update:open": function ($event) {
        _vm.open = $event;
      },
      close: _vm.onCancel
    }
  }, [_vm.errors.pending ? _c("NcActionText", {
    staticClass: "error",
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("ErrorIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1966124155)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.errors.pending) + "\n\t\t")]) : _c("NcActionText", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Please enter the following required information before creating the share")) + "\n\t\t")]), _vm._v(" "), _vm.pendingEnforcedPassword ? _c("NcActionText", [_c("LockIcon", {
    attrs: {
      size: 20
    }
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Password protection (enforced)")) + "\n\t\t")], 1) : _vm.pendingPassword ? _c("NcActionCheckbox", {
    staticClass: "share-link-password-checkbox",
    attrs: {
      checked: _vm.isPasswordProtected,
      disabled: _vm.config.enforcePasswordForPublicLink || _vm.saving
    },
    on: {
      "update:checked": function ($event) {
        _vm.isPasswordProtected = $event;
      },
      uncheck: _vm.onPasswordDisable
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Password protection")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingEnforcedPassword || _vm.share.password ? _c("NcActionInput", {
    staticClass: "share-link-password",
    attrs: {
      value: _vm.share.password,
      disabled: _vm.saving,
      required: _vm.config.enableLinkPasswordByDefault || _vm.config.enforcePasswordForPublicLink,
      minlength: _vm.isPasswordPolicyEnabled && _vm.config.passwordPolicy.minLength,
      icon: "",
      autocomplete: "new-password"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.share, "password", $event);
      },
      submit: _vm.onNewLinkShare
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Enter a password")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingExpirationDate ? _c("NcActionText", {
    attrs: {
      icon: "icon-calendar-dark"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Expiration date (enforced)")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingExpirationDate ? _c("NcActionInput", {
    staticClass: "share-link-expire-date",
    attrs: {
      disabled: _vm.saving,
      "is-native-picker": true,
      "hide-label": true,
      value: new Date(_vm.share.expireDate),
      type: "date",
      min: _vm.dateTomorrow,
      max: _vm.maxExpirationDateEnforced
    },
    on: {
      input: _vm.onExpirationChange
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Enter a date")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CheckIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2630571749)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Create share")) + "\n\t\t")]), _vm._v(" "), _c("NcActionButton", {
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onCancel.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2428343285)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Cancel")) + "\n\t\t")])], 1) : !_vm.loading ? _c("NcActions", {
    staticClass: "sharing-entry__actions",
    attrs: {
      "aria-label": _vm.actionsTooltip,
      "menu-align": "right",
      open: _vm.open
    },
    on: {
      "update:open": function ($event) {
        _vm.open = $event;
      },
      close: _vm.onMenuClose
    }
  }, [_vm.share ? [_vm.share.canEdit && _vm.canReshare ? [_c("NcActionButton", {
    attrs: {
      disabled: _vm.saving,
      "close-after-click": true
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.openSharingDetails.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("Tune", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1300586850)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Customize link")) + "\n\t\t\t\t")])] : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      "close-after-click": true
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        _vm.showQRCode = true;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconQr", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1082198240)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Generate QR code")) + "\n\t\t\t")]), _vm._v(" "), _c("NcActionSeparator"), _vm._v(" "), _vm._l(_vm.externalLinkActions, function (action) {
    return _c("ExternalShareAction", {
      key: action.id,
      attrs: {
        id: action.id,
        action: action,
        "file-info": _vm.fileInfo,
        share: _vm.share
      }
    });
  }), _vm._v(" "), _vm._l(_vm.externalLegacyLinkActions, function (_ref, index) {
    let {
      icon,
      url,
      name
    } = _ref;
    return _c("NcActionLink", {
      key: index,
      attrs: {
        href: url(_vm.shareLink),
        icon: icon,
        target: "_blank"
      }
    }, [_vm._v("\n\t\t\t\t" + _vm._s(name) + "\n\t\t\t")]);
  }), _vm._v(" "), !_vm.isEmailShareType && _vm.canReshare ? _c("NcActionButton", {
    staticClass: "new-share-link",
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("PlusIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2953566425)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Add another link")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share.canDelete ? _c("NcActionButton", {
    attrs: {
      disabled: _vm.saving
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.onDelete.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2428343285)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Unshare")) + "\n\t\t\t")]) : _vm._e()] : _vm.canReshare ? _c("NcActionButton", {
    staticClass: "new-share-link",
    attrs: {
      title: _vm.t("files_sharing", "Create a new share link"),
      "aria-label": _vm.t("files_sharing", "Create a new share link"),
      icon: _vm.loading ? "icon-loading-small" : "icon-add"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    }
  }) : _vm._e()], 2) : _c("div", {
    staticClass: "icon-loading-small sharing-entry__loading"
  }), _vm._v(" "), _vm.showQRCode ? _c("NcDialog", {
    attrs: {
      size: "normal",
      open: _vm.showQRCode,
      name: _vm.title,
      "close-on-click-outside": true
    },
    on: {
      "update:open": function ($event) {
        _vm.showQRCode = $event;
      },
      close: function ($event) {
        _vm.showQRCode = false;
      }
    }
  }, [_c("div", {
    staticClass: "qr-code-dialog"
  }, [_c("VueQrcode", {
    staticClass: "qr-code-dialog__img",
    attrs: {
      tag: "img",
      value: _vm.shareLink
    }
  })], 1)]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcActions", {
    ref: "quickShareActions",
    staticClass: "share-select",
    attrs: {
      "menu-name": _vm.selectedOption,
      "aria-label": _vm.ariaLabel,
      type: "tertiary-no-background",
      "force-name": ""
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DropdownIcon", {
          attrs: {
            size: 15
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _vm._l(_vm.options, function (option) {
    return _c("NcActionButton", {
      key: option.label,
      attrs: {
        type: "radio",
        "model-value": option.label === _vm.selectedOption,
        "close-after-click": ""
      },
      on: {
        click: function ($event) {
          return _vm.selectOption(option.label);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c(option.icon, {
            tag: "component"
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(option.label) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry"
  }, [_vm._t("avatar"), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__desc"
  }, [_c("span", {
    staticClass: "sharing-entry__title"
  }, [_vm._v(_vm._s(_vm.title))]), _vm._v(" "), _vm.subtitle ? _c("p", [_vm._v("\n\t\t\t" + _vm._s(_vm.subtitle) + "\n\t\t")]) : _vm._e()]), _vm._v(" "), _vm.$slots["default"] ? _c("NcActions", {
    ref: "actionsComponent",
    staticClass: "sharing-entry__actions",
    attrs: {
      "menu-align": "right",
      "aria-expanded": _vm.ariaExpandedValue
    }
  }, [_vm._t("default")], 2) : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c":
/*!***************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c ***!
  \***************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharing-search"
  }, [_c("label", {
    attrs: {
      for: "sharing-search-input"
    }
  }, [_vm._v(_vm._s(_vm.t("files_sharing", "Search for share recipients")))]), _vm._v(" "), _c("NcSelect", {
    ref: "select",
    staticClass: "sharing-search__input",
    attrs: {
      "input-id": "sharing-search-input",
      disabled: !_vm.canReshare,
      loading: _vm.loading,
      filterable: false,
      placeholder: _vm.inputPlaceholder,
      "clear-search-on-blur": () => false,
      "user-select": true,
      options: _vm.options
    },
    on: {
      search: _vm.asyncFind,
      "option:selected": _vm.onSelected
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function (_ref) {
        let {
          search
        } = _ref;
        return [_vm._v("\n\t\t\t" + _vm._s(search ? _vm.noResultText : _vm.t("files_sharing", "No recommendations. Start typing.")) + "\n\t\t")];
      }
    }]),
    model: {
      value: _vm.value,
      callback: function ($$v) {
        _vm.value = $$v;
      },
      expression: "value"
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm$share$expireDate;
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharingTabDetailsView"
  }, [_c("div", {
    staticClass: "sharingTabDetailsView__header"
  }, [_c("span", [_vm.isUserShare ? _c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": _vm.share.shareType !== _vm.SHARE_TYPES.SHARE_TYPE_USER,
      user: _vm.share.shareWith,
      "display-name": _vm.share.shareWithDisplayName,
      "menu-position": "left",
      url: _vm.share.shareWithAvatar
    }
  }) : _vm._e(), _vm._v(" "), _c(_vm.getShareTypeIcon(_vm.share.type), {
    tag: "component",
    attrs: {
      size: 32
    }
  })], 1), _vm._v(" "), _c("span", [_c("h1", [_vm._v(_vm._s(_vm.title))])])]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__wrapper"
  }, [_c("div", {
    ref: "quickPermissions",
    staticClass: "sharingTabDetailsView__quick-permissions"
  }, [_c("div", [_c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "read-only",
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.READ_ONLY.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("ViewIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "View only")) + "\n\t\t\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "upload-edit",
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.ALL.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("EditIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm.allowsFileDrop ? [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow upload and editing")) + "\n\t\t\t\t\t")] : [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow editing")) + "\n\t\t\t\t\t")]], 2), _vm._v(" "), _vm.allowsFileDrop ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      "data-cy-files-sharing-share-permissions-bundle": "file-drop",
      "button-variant": true,
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.FILE_DROP.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("UploadIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1083194048)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "File drop")) + "\n\t\t\t\t\t"), _c("small", {
    staticClass: "subline"
  }, [_vm._v(_vm._s(_vm.t("files_sharing", "Upload only")))])]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "custom",
      checked: _vm.sharingPermission,
      value: "custom",
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.expandCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DotsHorizontalIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Custom permissions")) + "\n\t\t\t\t\t"), _c("small", {
    staticClass: "subline"
  }, [_vm._v(_vm._s(_vm.customPermissionsList))])])], 1)]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__advanced-control"
  }, [_c("NcButton", {
    attrs: {
      id: "advancedSectionAccordionAdvancedControl",
      type: "tertiary",
      alignment: "end-reverse",
      "aria-controls": "advancedSectionAccordionAdvanced",
      "aria-expanded": _vm.advancedControlExpandedValue
    },
    on: {
      click: function ($event) {
        _vm.advancedSectionAccordionExpanded = !_vm.advancedSectionAccordionExpanded;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [!_vm.advancedSectionAccordionExpanded ? _c("MenuDownIcon") : _c("MenuUpIcon")];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Advanced settings")) + "\n\t\t\t\t")])], 1), _vm._v(" "), _vm.advancedSectionAccordionExpanded ? _c("div", {
    staticClass: "sharingTabDetailsView__advanced",
    attrs: {
      id: "advancedSectionAccordionAdvanced",
      "aria-labelledby": "advancedSectionAccordionAdvancedControl",
      role: "region"
    }
  }, [_c("section", [_vm.isPublicShare ? _c("NcInputField", {
    attrs: {
      autocomplete: "off",
      label: _vm.t("files_sharing", "Share label"),
      value: _vm.share.label
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.share, "label", $event);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? [_c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.isPasswordProtected,
      disabled: _vm.isPasswordEnforced
    },
    on: {
      "update:checked": function ($event) {
        _vm.isPasswordProtected = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Set password")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.isPasswordProtected ? _c("NcPasswordField", {
    attrs: {
      autocomplete: "new-password",
      value: _vm.hasUnsavedPassword ? _vm.share.newPassword : "",
      error: _vm.passwordError,
      "helper-text": _vm.errorPasswordLabel,
      required: _vm.isPasswordEnforced,
      label: _vm.t("files_sharing", "Password")
    },
    on: {
      "update:value": _vm.onPasswordChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isEmailShareType && _vm.passwordExpirationTime ? _c("span", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expires {passwordExpirationTime}", {
    passwordExpirationTime: _vm.passwordExpirationTime
  })) + "\n\t\t\t\t\t")]) : _vm.isEmailShareType && _vm.passwordExpirationTime !== null ? _c("span", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expired")) + "\n\t\t\t\t\t")]) : _vm._e()] : _vm._e(), _vm._v(" "), _vm.canTogglePasswordProtectedByTalkAvailable ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.isPasswordProtectedByTalk
    },
    on: {
      "update:checked": [function ($event) {
        _vm.isPasswordProtectedByTalk = $event;
      }, _vm.onPasswordProtectedByTalkChange]
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Video verification")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.hasExpirationDate,
      disabled: _vm.isExpiryDateEnforced
    },
    on: {
      "update:checked": function ($event) {
        _vm.hasExpirationDate = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.isExpiryDateEnforced ? _vm.t("files_sharing", "Expiration date (enforced)") : _vm.t("files_sharing", "Set expiration date")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.hasExpirationDate ? _c("NcDateTimePickerNative", {
    attrs: {
      id: "share-date-picker",
      value: new Date((_vm$share$expireDate = _vm.share.expireDate) !== null && _vm$share$expireDate !== void 0 ? _vm$share$expireDate : _vm.dateTomorrow),
      min: _vm.dateTomorrow,
      max: _vm.maxExpirationDateEnforced,
      "hide-label": true,
      placeholder: _vm.t("files_sharing", "Expiration date"),
      type: "date"
    },
    on: {
      input: _vm.onExpirationChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: _vm.canChangeHideDownload,
      checked: _vm.share.hideDownload
    },
    on: {
      "update:checked": [function ($event) {
        return _vm.$set(_vm.share, "hideDownload", $event);
      }, function ($event) {
        return _vm.queueUpdate("hideDownload");
      }]
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Hide download")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), !_vm.isPublicShare ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDownload,
      checked: _vm.canDownload,
      "data-cy-files-sharing-share-permissions-checkbox": "download"
    },
    on: {
      "update:checked": function ($event) {
        _vm.canDownload = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow download")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.writeNoteToRecipientIsChecked
    },
    on: {
      "update:checked": function ($event) {
        _vm.writeNoteToRecipientIsChecked = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Note to recipient")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.writeNoteToRecipientIsChecked ? [_c("label", {
    attrs: {
      for: "share-note-textarea"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Enter a note for the share recipient")) + "\n\t\t\t\t\t")]), _vm._v(" "), _c("textarea", {
    attrs: {
      id: "share-note-textarea"
    },
    domProps: {
      value: _vm.share.note
    },
    on: {
      input: function ($event) {
        _vm.share.note = $event.target.value;
      }
    }
  })] : _vm._e(), _vm._v(" "), _vm._l(_vm.externalLinkActions, function (action) {
    return _c("ExternalShareAction", {
      key: action.id,
      ref: "externalLinkActions",
      refInFor: true,
      attrs: {
        id: action.id,
        action: action,
        "file-info": _vm.fileInfo,
        share: _vm.share
      }
    });
  }), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.setCustomPermissions
    },
    on: {
      "update:checked": function ($event) {
        _vm.setCustomPermissions = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Custom permissions")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.setCustomPermissions ? _c("section", {
    staticClass: "custom-permissions-group"
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.allowsFileDrop && _vm.share.type === _vm.SHARE_TYPES.SHARE_TYPE_LINK,
      checked: _vm.hasRead,
      "data-cy-files-sharing-share-permissions-checkbox": "read"
    },
    on: {
      "update:checked": function ($event) {
        _vm.hasRead = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Read")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.isFolder ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetCreate,
      checked: _vm.canCreate,
      "data-cy-files-sharing-share-permissions-checkbox": "create"
    },
    on: {
      "update:checked": function ($event) {
        _vm.canCreate = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Create")) + "\n\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetEdit,
      checked: _vm.canEdit,
      "data-cy-files-sharing-share-permissions-checkbox": "update"
    },
    on: {
      "update:checked": function ($event) {
        _vm.canEdit = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Edit")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.config.isResharingAllowed && _vm.share.type !== _vm.SHARE_TYPES.SHARE_TYPE_LINK ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetReshare,
      checked: _vm.canReshare,
      "data-cy-files-sharing-share-permissions-checkbox": "share"
    },
    on: {
      "update:checked": function ($event) {
        _vm.canReshare = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Share")) + "\n\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDelete,
      checked: _vm.canDelete,
      "data-cy-files-sharing-share-permissions-checkbox": "delete"
    },
    on: {
      "update:checked": function ($event) {
        _vm.canDelete = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Delete")) + "\n\t\t\t\t\t")])], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__delete"
  }, [!_vm.isNewShare ? _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("files_sharing", "Delete share"),
      disabled: false,
      readonly: false,
      type: "tertiary"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.removeShare.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 16
          }
        })];
      },
      proxy: true
    }], null, false, 2746485232)
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Delete share")) + "\n\t\t\t\t\t")]) : _vm._e()], 1)], 2)]) : _vm._e()]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__footer"
  }, [_c("div", {
    staticClass: "button-group"
  }, [_c("NcButton", {
    attrs: {
      "data-cy-files-sharing-share-editor-action": "cancel"
    },
    on: {
      click: function ($event) {
        return _vm.$emit("close-sharing-details");
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Cancel")) + "\n\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      type: "primary",
      "data-cy-files-sharing-share-editor-action": "save"
    },
    on: {
      click: _vm.saveShare
    },
    scopedSlots: _vm._u([_vm.creating ? {
      key: "icon",
      fn: function () {
        return [_c("NcLoadingIcon")];
      },
      proxy: true
    } : null], null, true)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.shareButtonText) + "\n\t\t\t\t")])], 1)])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", {
    attrs: {
      id: "sharing-inherited-shares"
    }
  }, [_c("SharingEntrySimple", {
    staticClass: "sharing-entry__inherited",
    attrs: {
      title: _vm.mainTitle,
      subtitle: _vm.subTitle,
      "aria-expanded": _vm.showInheritedShares
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("div", {
          staticClass: "avatar-shared icon-more-white"
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: _vm.showInheritedSharesIcon,
      "aria-label": _vm.toggleTooltip,
      title: _vm.toggleTooltip
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.toggleInheritedShares.apply(null, arguments);
      }
    }
  })], 1), _vm._v(" "), _vm._l(_vm.shares, function (share) {
    return _c("SharingEntryInherited", {
      key: share.id,
      attrs: {
        "file-info": _vm.fileInfo,
        share: share
      },
      on: {
        "remove:share": _vm.removeShare
      }
    });
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84":
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84 ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _vm.canLinkShare ? _c("ul", {
    staticClass: "sharing-link-list"
  }, [!_vm.hasLinkShares && _vm.canReshare ? _c("SharingEntryLink", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo
    },
    on: {
      "add:share": _vm.addShare
    }
  }) : _vm._e(), _vm._v(" "), _vm.hasShares ? _vm._l(_vm.shares, function (share, index) {
    return _c("SharingEntryLink", {
      key: share.id,
      attrs: {
        index: _vm.shares.length > 1 ? index + 1 : null,
        "can-reshare": _vm.canReshare,
        share: _vm.shares[index],
        "file-info": _vm.fileInfo
      },
      on: {
        "update:share": [function ($event) {
          return _vm.$set(_vm.shares, index, $event);
        }, function ($event) {
          return _vm.awaitForShare(...arguments);
        }],
        "add:share": function ($event) {
          return _vm.addShare(...arguments);
        },
        "remove:share": _vm.removeShare,
        "open-sharing-details": function ($event) {
          return _vm.openSharingDetails(share);
        }
      }
    });
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4":
/*!*********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4 ***!
  \*********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", {
    staticClass: "sharing-sharee-list"
  }, _vm._l(_vm.shares, function (share) {
    return _c("SharingEntry", {
      key: share.id,
      attrs: {
        "file-info": _vm.fileInfo,
        share: share,
        "is-unique": _vm.isUnique(share)
      },
      on: {
        "open-sharing-details": function ($event) {
          return _vm.openSharingDetails(share);
        }
      }
    });
  }), 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharingTab",
    class: {
      "icon-loading": _vm.loading
    }
  }, [_vm.error ? _c("div", {
    staticClass: "emptycontent",
    class: {
      emptyContentWithSections: _vm.sections.length > 0
    }
  }, [_c("div", {
    staticClass: "icon icon-error"
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.error))])]) : _vm._e(), _vm._v(" "), _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.showSharingDetailsView,
      expression: "!showSharingDetailsView"
    }],
    staticClass: "sharingTab__content"
  }, [_c("ul", [_vm.isSharedWithMe ? _c("SharingEntrySimple", _vm._b({
    staticClass: "sharing-entry__reshare",
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("NcAvatar", {
          staticClass: "sharing-entry__avatar",
          attrs: {
            user: _vm.sharedWithMe.user,
            "display-name": _vm.sharedWithMe.displayName
          }
        })];
      },
      proxy: true
    }], null, false, 3197855346)
  }, "SharingEntrySimple", _vm.sharedWithMe, false)) : _vm._e()], 1), _vm._v(" "), !_vm.loading ? _c("SharingInput", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      "link-shares": _vm.linkShares,
      reshare: _vm.reshare,
      shares: _vm.shares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingLinkList", {
    ref: "linkShareList",
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      shares: _vm.linkShares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingList", {
    ref: "shareList",
    attrs: {
      shares: _vm.shares,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), _vm.canReshare && !_vm.loading ? _c("SharingInherited", {
    attrs: {
      "file-info": _vm.fileInfo
    }
  }) : _vm._e(), _vm._v(" "), _c("SharingEntryInternal", {
    attrs: {
      "file-info": _vm.fileInfo
    }
  }), _vm._v(" "), _vm.projectsEnabled && _vm.fileInfo ? _c("CollectionList", {
    attrs: {
      id: "".concat(_vm.fileInfo.id),
      type: "file",
      name: _vm.fileInfo.name
    }
  }) : _vm._e()], 1), _vm._v(" "), _vm._l(_vm.sections, function (section, index) {
    return _c("div", {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: !_vm.showSharingDetailsView,
        expression: "!showSharingDetailsView"
      }],
      key: index,
      ref: "section-" + index,
      refInFor: true,
      staticClass: "sharingTab__additionalContent"
    }, [_c(section(_vm.$refs["section-" + index], _vm.fileInfo), {
      tag: "component",
      attrs: {
        "file-info": _vm.fileInfo
      }
    })], 1);
  }), _vm._v(" "), _vm.showSharingDetailsView ? _c("SharingDetailsTab", {
    attrs: {
      "file-info": _vm.shareDetailsData.fileInfo,
      share: _vm.shareDetailsData.share
    },
    on: {
      "close-sharing-details": _vm.toggleShareDetailsView,
      "add:share": _vm.addShare,
      "remove:share": _vm.removeShare
    }
  }) : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css ***!
  \*********************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.fade-enter-active[data-v-8e58e0a5],
.fade-leave-active[data-v-8e58e0a5] {
  transition: opacity .3s ease;
}
.fade-enter[data-v-8e58e0a5],
.fade-leave-to[data-v-8e58e0a5] {
  opacity: 0;
}
.linked-icons[data-v-8e58e0a5] {
  display: flex;
}
.linked-icons img[data-v-8e58e0a5] {
  padding: 12px;
  height: 44px;
  display: block;
  background-repeat: no-repeat;
  background-position: center;
  opacity: .7;
}
.linked-icons img[data-v-8e58e0a5]:hover {
  opacity: 1;
}
.popovermenu[data-v-8e58e0a5] {
  display: none;
}
.popovermenu.open[data-v-8e58e0a5] {
  display: block;
}
li.collection-list-item[data-v-8e58e0a5] {
  flex-wrap: wrap;
  height: auto;
  cursor: pointer;
  margin-bottom: 0 !important;
}
li.collection-list-item .collection-avatar[data-v-8e58e0a5] {
  margin-top: 6px;
}
li.collection-list-item form[data-v-8e58e0a5],
li.collection-list-item .collection-item-name[data-v-8e58e0a5] {
  flex-basis: 10%;
  flex-grow: 1;
  display: flex;
}
li.collection-list-item .collection-item-name[data-v-8e58e0a5] {
  padding: 12px 9px;
}
li.collection-list-item input[data-v-8e58e0a5] {
  margin-top: 4px;
  border-color: var(--color-border-maxcontrast);
}
li.collection-list-item input[type=text][data-v-8e58e0a5] {
  flex-grow: 1;
}
li.collection-list-item .error[data-v-8e58e0a5],
li.collection-list-item .resource-list-details[data-v-8e58e0a5] {
  flex-basis: 100%;
  width: 100%;
}
li.collection-list-item .resource-list-details li[data-v-8e58e0a5] {
  display: flex;
  margin-left: 44px;
  border-radius: 3px;
  cursor: pointer;
}
li.collection-list-item .resource-list-details li[data-v-8e58e0a5]:hover {
  background-color: var(--color-background-dark);
}
li.collection-list-item .resource-list-details li a[data-v-8e58e0a5] {
  flex-grow: 1;
  padding: 3px;
  max-width: calc(100% - 30px);
  display: flex;
}
li.collection-list-item .resource-list-details span[data-v-8e58e0a5] {
  display: inline-block;
  vertical-align: top;
  margin-right: 10px;
}
li.collection-list-item .resource-list-details span.resource-name[data-v-8e58e0a5] {
  text-overflow: ellipsis;
  overflow: hidden;
  position: relative;
  vertical-align: top;
  white-space: nowrap;
  flex-grow: 1;
  padding: 4px;
}
li.collection-list-item .resource-list-details img[data-v-8e58e0a5] {
  width: 24px;
  height: 24px;
}
li.collection-list-item .resource-list-details .icon-close[data-v-8e58e0a5] {
  opacity: .7;
}
li.collection-list-item .resource-list-details .icon-close[data-v-8e58e0a5]:hover,
li.collection-list-item .resource-list-details .icon-close[data-v-8e58e0a5]:focus {
  opacity: 1;
}
.shouldshake[data-v-8e58e0a5] {
  animation: shake-8e58e0a5 .6s 1 linear;
}
@keyframes shake-8e58e0a5 {
  0% {
    transform: translate(15px);
  }
  20% {
    transform: translate(-15px);
  }
  40% {
    transform: translate(7px);
  }
  60% {
    transform: translate(-7px);
  }
  80% {
    transform: translate(3px);
  }
  to {
    transform: translate(0);
  }
}
.collection-list *[data-v-75a4370b] {
  box-sizing: border-box;
}
.collection-list > li[data-v-75a4370b] {
  display: flex;
  align-items: start;
  gap: 12px;
}
.collection-list > li > .avatar[data-v-75a4370b] {
  margin-top: auto;
}
#collection-select-container[data-v-75a4370b] {
  display: flex;
  flex-direction: column;
}
.v-select span.avatar[data-v-75a4370b] {
  display: block;
  padding: 16px;
  opacity: .7;
  background-repeat: no-repeat;
  background-position: center;
}
.v-select span.avatar[data-v-75a4370b]:hover {
  opacity: 1;
}
p.hint[data-v-75a4370b] {
  z-index: 1;
  margin-top: -16px;
  padding: 8px;
  color: var(--color-text-maxcontrast);
  line-height: normal;
}
div.avatar[data-v-75a4370b] {
  width: 32px;
  height: 32px;
  margin: 30px 0 0;
  padding: 8px;
  background-color: var(--color-background-dark);
}
.icon-projects[data-v-75a4370b] {
  display: block;
  padding: 8px;
  background-repeat: no-repeat;
  background-position: center;
}
.option__wrapper[data-v-75a4370b] {
  display: flex;
}
.option__wrapper .avatar[data-v-75a4370b] {
  display: block;
  background-color: var(--color-background-darker) !important;
}
.option__wrapper .option__title[data-v-75a4370b] {
  padding: 4px;
}
.fade-enter-active[data-v-75a4370b],
.fade-leave-active[data-v-75a4370b] {
  transition: opacity .5s;
}
.fade-enter[data-v-75a4370b],
.fade-leave-to[data-v-75a4370b] {
  opacity: 0;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-61240f7a] {
  display: flex;
  align-items: center;
  height: 44px;
}
.sharing-entry__summary[data-v-61240f7a] {
  padding: 8px;
  padding-left: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  flex: 1 0;
  min-width: 0;
}
.sharing-entry__summary__desc[data-v-61240f7a] {
  display: inline-block;
  padding-bottom: 0;
  line-height: 1.2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sharing-entry__summary__desc p[data-v-61240f7a],
.sharing-entry__summary__desc small[data-v-61240f7a] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__summary__desc-unique[data-v-61240f7a] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-06bd31b0] {
  display: flex;
  align-items: center;
  height: 44px;
}
.sharing-entry__desc[data-v-06bd31b0] {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 8px;
  padding-left: 10px;
  line-height: 1.2em;
}
.sharing-entry__desc p[data-v-06bd31b0] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__actions[data-v-06bd31b0] {
  margin-left: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry__internal .avatar-external[data-v-f55cfc52] {
  width: 32px;
  height: 32px;
  line-height: 32px;
  font-size: 18px;
  background-color: var(--color-text-maxcontrast);
  border-radius: 50%;
  flex-shrink: 0;
}
.sharing-entry__internal .icon-checkmark-color[data-v-f55cfc52] {
  opacity: 1;
  color: var(--color-success);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-7a675594] {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.sharing-entry__summary[data-v-7a675594] {
  padding: 8px;
  padding-left: 10px;
  display: flex;
  justify-content: space-between;
  flex: 1 0;
  min-width: 0;
}
.sharing-entry__desc[data-v-7a675594] {
  display: flex;
  flex-direction: column;
  line-height: 1.2em;
}
.sharing-entry__desc p[data-v-7a675594] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__desc__title[data-v-7a675594] {
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.sharing-entry:not(.sharing-entry--share) .sharing-entry__actions .new-share-link[data-v-7a675594] {
  border-top: 1px solid var(--color-border);
}
.sharing-entry[data-v-7a675594] .avatar-link-share {
  background-color: var(--color-primary-element);
}
.sharing-entry .sharing-entry__action--public-upload[data-v-7a675594] {
  border-bottom: 1px solid var(--color-border);
}
.sharing-entry__loading[data-v-7a675594] {
  width: 44px;
  height: 44px;
  margin: 0;
  padding: 14px;
  margin-left: auto;
}
.sharing-entry .action-item ~ .action-item[data-v-7a675594],
.sharing-entry .action-item ~ .sharing-entry__loading[data-v-7a675594] {
  margin-left: 0;
}
.sharing-entry .icon-checkmark-color[data-v-7a675594] {
  opacity: 1;
  color: var(--color-success);
}
.qr-code-dialog[data-v-7a675594] {
  display: flex;
  width: 100%;
  justify-content: center;
}
.qr-code-dialog__img[data-v-7a675594] {
  width: 100%;
  height: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.share-select[data-v-62b9dbb0] {
  display: block;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle {
  color: var(--color-primary-element) !important;
  font-size: 12.5px !important;
  height: auto !important;
  min-height: auto !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__text {
  font-weight: normal !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__icon {
  height: 24px !important;
  min-height: 24px !important;
  width: 24px !important;
  min-width: 24px !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__wrapper {
  flex-direction: row-reverse !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-354542cc] {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.sharing-entry__desc[data-v-354542cc] {
  padding: 8px;
  padding-left: 10px;
  line-height: 1.2em;
  position: relative;
  flex: 1 1;
  min-width: 0;
}
.sharing-entry__desc p[data-v-354542cc] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__title[data-v-354542cc] {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: inherit;
}
.sharing-entry__actions[data-v-354542cc] {
  margin-left: auto !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-search {
  display: flex;
  flex-direction: column;
  margin-bottom: 4px;
}
.sharing-search label[for=sharing-search-input] {
  margin-bottom: 2px;
}
.sharing-search__input {
  width: 100%;
  margin: 10px 0;
}
.vs__dropdown-menu span[lookup] .avatardiv {
  background-image: var(--icon-search-white);
  background-repeat: no-repeat;
  background-position: center;
  background-color: var(--color-text-maxcontrast) !important;
}
.vs__dropdown-menu span[lookup] .avatardiv .avatardiv__initials-wrapper {
  display: none;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharingTabDetailsView[data-v-b968620e] {
  display: flex;
  flex-direction: column;
  width: 100%;
  margin: 0 auto;
  position: relative;
  height: 100%;
  overflow: hidden;
}
.sharingTabDetailsView__header[data-v-b968620e] {
  display: flex;
  align-items: center;
  box-sizing: border-box;
  margin: 0.2em;
}
.sharingTabDetailsView__header span[data-v-b968620e] {
  display: flex;
  align-items: center;
}
.sharingTabDetailsView__header span h1[data-v-b968620e] {
  font-size: 15px;
  padding-left: 0.3em;
}
.sharingTabDetailsView__wrapper[data-v-b968620e] {
  position: relative;
  overflow: scroll;
  flex-shrink: 1;
  padding: 4px;
  padding-right: 12px;
}
.sharingTabDetailsView__quick-permissions[data-v-b968620e] {
  display: flex;
  justify-content: center;
  width: 100%;
  margin: 0 auto;
  border-radius: 0;
}
.sharingTabDetailsView__quick-permissions div[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] {
  width: 100%;
  /* Target component based style in NcCheckboxRadioSwitch slot content*/
}
.sharingTabDetailsView__quick-permissions div span span[data-v-b968620e]:nth-child(1) {
  align-items: center;
  justify-content: center;
  padding: 0.1em;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] label span {
  display: flex;
  flex-direction: column;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] span.checkbox-content__text.checkbox-radio-switch__text {
  flex-wrap: wrap;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] span.checkbox-content__text.checkbox-radio-switch__text .subline {
  display: block;
  flex-basis: 100%;
}
.sharingTabDetailsView__advanced-control[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__advanced-control button[data-v-b968620e] {
  margin-top: 0.5em;
}
.sharingTabDetailsView__advanced[data-v-b968620e] {
  width: 100%;
  margin-bottom: 0.5em;
  text-align: left;
  padding-left: 0;
}
.sharingTabDetailsView__advanced section[data-v-b968620e] {
  /*
             The following style is applied out of the component's scope
             to remove padding from the label.checkbox-radio-switch__label,
             which is used to group radio checkbox items. The use of ::v-deep
             ensures that the padding is modified without being affected by
             the component's scoping.
             Without this achieving left alignment for the checkboxes would not
             be possible.
           */
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e],
.sharingTabDetailsView__advanced section div.mx-datepicker[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e] {
  height: 80px;
  margin: 0;
}
.sharingTabDetailsView__advanced section span[data-v-b968620e] label {
  padding-left: 0 !important;
  background-color: initial !important;
  border: none !important;
}
.sharingTabDetailsView__advanced section section.custom-permissions-group[data-v-b968620e] {
  padding-left: 1.5em;
}
.sharingTabDetailsView__delete > button[data-v-b968620e]:first-child {
  color: rgb(223, 7, 7);
}
.sharingTabDetailsView__footer[data-v-b968620e] {
  width: 100%;
  display: flex;
  position: sticky;
  bottom: 0;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-start;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0), var(--color-main-background));
}
.sharingTabDetailsView__footer .button-group[data-v-b968620e] {
  display: flex;
  justify-content: space-between;
  width: 100%;
  margin-top: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e] {
  margin-left: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e]:first-child {
  margin-left: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry__inherited .avatar-shared[data-v-3f1bda78] {
  width: 32px;
  height: 32px;
  line-height: 32px;
  font-size: 18px;
  background-color: var(--color-text-maxcontrast);
  border-radius: 50%;
  flex-shrink: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.emptyContentWithSections[data-v-0f81577f] {
  margin: 1rem auto;
}
.sharingTab[data-v-0f81577f] {
  position: relative;
  height: 100%;
}
.sharingTab__content[data-v-0f81577f] {
  padding: 0 6px;
}
.sharingTab__additionalContent[data-v-0f81577f] {
  margin: 44px 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/nextcloud-vue-collections/node_modules/@nextcloud/router/dist/index.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/nextcloud-vue-collections/node_modules/@nextcloud/router/dist/index.js ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";


Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.generateUrl = exports.generateRemoteUrl = exports.generateOcsUrl = exports.generateFilePath = void 0;
exports.getAppRootUrl = getAppRootUrl;
exports.getRootUrl = getRootUrl;
exports.linkTo = exports.imagePath = void 0;
__webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/**
 * Get an url with webroot to a file in an app
 *
 * @param {string} app the id of the app the file belongs to
 * @param {string} file the file path relative to the app folder
 * @return {string} URL with webroot to a file
 */
const linkTo = (app, file) => generateFilePath(app, '', file);

/**
 * Creates a relative url for remote use
 *
 * @param {string} service id
 * @return {string} the url
 */
exports.linkTo = linkTo;
const linkToRemoteBase = service => getRootUrl() + '/remote.php/' + service;

/**
 * @brief Creates an absolute url for remote use
 * @param {string} service id
 * @return {string} the url
 */
const generateRemoteUrl = service => window.location.protocol + '//' + window.location.host + linkToRemoteBase(service);

/**
 * Get the base path for the given OCS API service
 *
 * @param {string} url OCS API service url
 * @param {object} params parameters to be replaced into the service url
 * @param {UrlOptions} options options for the parameter replacement
 * @param {boolean} options.escape Set to false if parameters should not be URL encoded (default true)
 * @param {Number} options.ocsVersion OCS version to use (defaults to 2)
 * @return {string} Absolute path for the OCS URL
 */
exports.generateRemoteUrl = generateRemoteUrl;
const generateOcsUrl = (url, params, options) => {
  const allOptions = Object.assign({
    ocsVersion: 2
  }, options || {});
  const version = allOptions.ocsVersion === 1 ? 1 : 2;
  return window.location.protocol + '//' + window.location.host + getRootUrl() + '/ocs/v' + version + '.php' + _generateUrlPath(url, params, options);
};
exports.generateOcsUrl = generateOcsUrl;
/**
 * Generate a url path, which can contain parameters
 *
 * Parameters will be URL encoded automatically
 *
 * @param {string} url address (can contain placeholders e.g. /call/{token} would replace {token} with the value of params.token
 * @param {object} params parameters to be replaced into the address
 * @param {UrlOptions} options options for the parameter replacement
 * @return {string} Path part for the given URL
 */
const _generateUrlPath = (url, params, options) => {
  const allOptions = Object.assign({
    escape: true
  }, options || {});
  const _build = function (text, vars) {
    vars = vars || {};
    return text.replace(/{([^{}]*)}/g, function (a, b) {
      var r = vars[b];
      if (allOptions.escape) {
        return typeof r === 'string' || typeof r === 'number' ? encodeURIComponent(r.toString()) : encodeURIComponent(a);
      } else {
        return typeof r === 'string' || typeof r === 'number' ? r.toString() : a;
      }
    });
  };
  if (url.charAt(0) !== '/') {
    url = '/' + url;
  }
  return _build(url, params || {});
};

/**
 * Generate the url with webroot for the given relative url, which can contain parameters
 *
 * Parameters will be URL encoded automatically
 *
 * @param {string} url address (can contain placeholders e.g. /call/{token} would replace {token} with the value of params.token
 * @param {object} params parameters to be replaced into the url
 * @param {UrlOptions} options options for the parameter replacement
 * @param {boolean} options.noRewrite True if you want to force index.php being added
 * @param {boolean} options.escape Set to false if parameters should not be URL encoded (default true)
 * @return {string} URL with webroot for the given relative URL
 */
const generateUrl = (url, params, options) => {
  var _window;
  const allOptions = Object.assign({
    noRewrite: false
  }, options || {});
  if (((_window = window) === null || _window === void 0 || (_window = _window.OC) === null || _window === void 0 || (_window = _window.config) === null || _window === void 0 ? void 0 : _window.modRewriteWorking) === true && !allOptions.noRewrite) {
    return getRootUrl() + _generateUrlPath(url, params, options);
  }
  return getRootUrl() + '/index.php' + _generateUrlPath(url, params, options);
};

/**
 * Get the path with webroot to an image file
 * if no extension is given for the image, it will automatically decide
 * between .png and .svg based on what the browser supports
 *
 * @param {string} app the app id to which the image belongs
 * @param {string} file the name of the image file
 * @return {string}
 */
exports.generateUrl = generateUrl;
const imagePath = (app, file) => {
  if (file.indexOf('.') === -1) {
    //if no extension is given, use svg
    return generateFilePath(app, 'img', file + '.svg');
  }
  return generateFilePath(app, 'img', file);
};

/**
 * Get the url with webroot for a file in an app
 *
 * @param {string} app the id of the app
 * @param {string} type the type of the file to link to (e.g. css,img,ajax.template)
 * @param {string} file the filename
 * @return {string} URL with webroot for a file in an app
 */
exports.imagePath = imagePath;
const generateFilePath = (app, type, file) => {
  var _window2;
  const isCore = ((_window2 = window) === null || _window2 === void 0 || (_window2 = _window2.OC) === null || _window2 === void 0 || (_window2 = _window2.coreApps) === null || _window2 === void 0 ? void 0 : _window2.indexOf(app)) !== -1;
  let link = getRootUrl();
  if (file.substring(file.length - 3) === 'php' && !isCore) {
    link += '/index.php/apps/' + app;
    if (file !== 'index.php') {
      link += '/';
      if (type) {
        link += encodeURI(type + '/');
      }
      link += file;
    }
  } else if (file.substring(file.length - 3) !== 'php' && !isCore) {
    link = getAppRootUrl(app);
    if (type) {
      link += '/' + type + '/';
    }
    if (link.substring(link.length - 1) !== '/') {
      link += '/';
    }
    link += file;
  } else {
    if ((app === 'settings' || app === 'core' || app === 'search') && type === 'ajax') {
      link += '/index.php/';
    } else {
      link += '/';
    }
    if (!isCore) {
      link += 'apps/';
    }
    if (app !== '') {
      app += '/';
      link += app;
    }
    if (type) {
      link += type + '/';
    }
    link += file;
  }
  return link;
};

/**
 * Return the web root path where this Nextcloud instance
 * is accessible, with a leading slash.
 * For example "/nextcloud".
 *
 * @return {string} web root path
 */
exports.generateFilePath = generateFilePath;
function getRootUrl() {
  let webroot = window._oc_webroot;
  if (typeof webroot === 'undefined') {
    webroot = location.pathname;
    const pos = webroot.indexOf('/index.php/');
    if (pos !== -1) {
      webroot = webroot.substr(0, pos);
    } else {
      webroot = webroot.substr(0, webroot.lastIndexOf('/'));
    }
  }
  return webroot;
}

/**
 * Return the web root path for a given app
 * @param {string} app The ID of the app
 */
function getAppRootUrl(app) {
  var _window$_oc_appswebro, _webroots$app;
  const webroots = (_window$_oc_appswebro = window._oc_appswebroots) !== null && _window$_oc_appswebro !== void 0 ? _window$_oc_appswebro : {};
  return (_webroots$app = webroots[app]) !== null && _webroots$app !== void 0 ? _webroots$app : '';
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css":
/*!*******************************************************************************!*\
  !*** ./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _css_loader_dist_cjs_js_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../css-loader/dist/cjs.js!./index-Au1Gr_G6.css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_css_loader_dist_cjs_js_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_css_loader_dist_cjs_js_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _css_loader_dist_cjs_js_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _css_loader_dist_cjs_js_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/url-search-params-polyfill/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/url-search-params-polyfill/index.js ***!
  \**********************************************************/
/***/ (function(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

/**!
 * url-search-params-polyfill
 *
 * @author Jerry Bendy (https://github.com/jerrybendy)
 * @licence MIT
 */
(function(self) {
    'use strict';

    var nativeURLSearchParams = (function() {
            // #41 Fix issue in RN
            try {
                if (self.URLSearchParams && (new self.URLSearchParams('foo=bar')).get('foo') === 'bar') {
                    return self.URLSearchParams;
                }
            } catch (e) {}
            return null;
        })(),
        isSupportObjectConstructor = nativeURLSearchParams && (new nativeURLSearchParams({a: 1})).toString() === 'a=1',
        // There is a bug in safari 10.1 (and earlier) that incorrectly decodes `%2B` as an empty space and not a plus.
        decodesPlusesCorrectly = nativeURLSearchParams && (new nativeURLSearchParams('s=%2B').get('s') === '+'),
        isSupportSize = nativeURLSearchParams && 'size' in nativeURLSearchParams.prototype,
        __URLSearchParams__ = "__URLSearchParams__",
        // Fix bug in Edge which cannot encode ' &' correctly
        encodesAmpersandsCorrectly = nativeURLSearchParams ? (function() {
            var ampersandTest = new nativeURLSearchParams();
            ampersandTest.append('s', ' &');
            return ampersandTest.toString() === 's=+%26';
        })() : true,
        prototype = URLSearchParamsPolyfill.prototype,
        iterable = !!(self.Symbol && self.Symbol.iterator);

    if (nativeURLSearchParams && isSupportObjectConstructor && decodesPlusesCorrectly && encodesAmpersandsCorrectly && isSupportSize) {
        return;
    }


    /**
     * Make a URLSearchParams instance
     *
     * @param {object|string|URLSearchParams} search
     * @constructor
     */
    function URLSearchParamsPolyfill(search) {
        search = search || "";

        // support construct object with another URLSearchParams instance
        if (search instanceof URLSearchParams || search instanceof URLSearchParamsPolyfill) {
            search = search.toString();
        }
        this [__URLSearchParams__] = parseToDict(search);
    }


    /**
     * Appends a specified key/value pair as a new search parameter.
     *
     * @param {string} name
     * @param {string} value
     */
    prototype.append = function(name, value) {
        appendTo(this [__URLSearchParams__], name, value);
    };

    /**
     * Deletes the given search parameter, and its associated value,
     * from the list of all search parameters.
     *
     * @param {string} name
     */
    prototype['delete'] = function(name) {
        delete this [__URLSearchParams__] [name];
    };

    /**
     * Returns the first value associated to the given search parameter.
     *
     * @param {string} name
     * @returns {string|null}
     */
    prototype.get = function(name) {
        var dict = this [__URLSearchParams__];
        return this.has(name) ? dict[name][0] : null;
    };

    /**
     * Returns all the values association with a given search parameter.
     *
     * @param {string} name
     * @returns {Array}
     */
    prototype.getAll = function(name) {
        var dict = this [__URLSearchParams__];
        return this.has(name) ? dict [name].slice(0) : [];
    };

    /**
     * Returns a Boolean indicating if such a search parameter exists.
     *
     * @param {string} name
     * @returns {boolean}
     */
    prototype.has = function(name) {
        return hasOwnProperty(this [__URLSearchParams__], name);
    };

    /**
     * Sets the value associated to a given search parameter to
     * the given value. If there were several values, delete the
     * others.
     *
     * @param {string} name
     * @param {string} value
     */
    prototype.set = function set(name, value) {
        this [__URLSearchParams__][name] = ['' + value];
    };

    /**
     * Returns a string containg a query string suitable for use in a URL.
     *
     * @returns {string}
     */
    prototype.toString = function() {
        var dict = this[__URLSearchParams__], query = [], i, key, name, value;
        for (key in dict) {
            name = encode(key);
            for (i = 0, value = dict[key]; i < value.length; i++) {
                query.push(name + '=' + encode(value[i]));
            }
        }
        return query.join('&');
    };

    // There is a bug in Safari 10.1 and `Proxy`ing it is not enough.
    var useProxy = self.Proxy && nativeURLSearchParams && (!decodesPlusesCorrectly || !encodesAmpersandsCorrectly || !isSupportObjectConstructor || !isSupportSize);
    var propValue;
    if (useProxy) {
        // Safari 10.0 doesn't support Proxy, so it won't extend URLSearchParams on safari 10.0
        propValue = new Proxy(nativeURLSearchParams, {
            construct: function (target, args) {
                return new target((new URLSearchParamsPolyfill(args[0]).toString()));
            }
        })
        // Chrome <=60 .toString() on a function proxy got error "Function.prototype.toString is not generic"
        propValue.toString = Function.prototype.toString.bind(URLSearchParamsPolyfill);
    } else {
        propValue = URLSearchParamsPolyfill;
    }

    /*
     * Apply polyfill to global object and append other prototype into it
     */
    Object.defineProperty(self, 'URLSearchParams', {
        value: propValue
    });

    var USPProto = self.URLSearchParams.prototype;

    USPProto.polyfill = true;

    // Fix #54, `toString.call(new URLSearchParams)` will return correct value when Proxy not used
    if (!useProxy && self.Symbol) {
        USPProto[self.Symbol.toStringTag] = 'URLSearchParams';
    }

    /**
     *
     * @param {function} callback
     * @param {object} thisArg
     */
    if (!('forEach' in USPProto)) {
        USPProto.forEach = function(callback, thisArg) {
            var dict = parseToDict(this.toString());
            Object.getOwnPropertyNames(dict).forEach(function(name) {
                dict[name].forEach(function(value) {
                    callback.call(thisArg, value, name, this);
                }, this);
            }, this);
        };
    }

    /**
     * Sort all name-value pairs
     */
    if (!('sort' in USPProto)) {
        USPProto.sort = function() {
            var dict = parseToDict(this.toString()), keys = [], k, i, j;
            for (k in dict) {
                keys.push(k);
            }
            keys.sort();

            for (i = 0; i < keys.length; i++) {
                this['delete'](keys[i]);
            }
            for (i = 0; i < keys.length; i++) {
                var key = keys[i], values = dict[key];
                for (j = 0; j < values.length; j++) {
                    this.append(key, values[j]);
                }
            }
        };
    }

    /**
     * Returns an iterator allowing to go through all keys of
     * the key/value pairs contained in this object.
     *
     * @returns {function}
     */
    if (!('keys' in USPProto)) {
        USPProto.keys = function() {
            var items = [];
            this.forEach(function(item, name) {
                items.push(name);
            });
            return makeIterator(items);
        };
    }

    /**
     * Returns an iterator allowing to go through all values of
     * the key/value pairs contained in this object.
     *
     * @returns {function}
     */
    if (!('values' in USPProto)) {
        USPProto.values = function() {
            var items = [];
            this.forEach(function(item) {
                items.push(item);
            });
            return makeIterator(items);
        };
    }

    /**
     * Returns an iterator allowing to go through all key/value
     * pairs contained in this object.
     *
     * @returns {function}
     */
    if (!('entries' in USPProto)) {
        USPProto.entries = function() {
            var items = [];
            this.forEach(function(item, name) {
                items.push([name, item]);
            });
            return makeIterator(items);
        };
    }

    if (iterable) {
        USPProto[self.Symbol.iterator] = USPProto[self.Symbol.iterator] || USPProto.entries;
    }

    if (!('size' in USPProto)) {
        Object.defineProperty(USPProto, 'size', {
            get: function () {
                var dict = parseToDict(this.toString())
                if (USPProto === this) {
                    throw new TypeError('Illegal invocation at URLSearchParams.invokeGetter')
                }
                return Object.keys(dict).reduce(function (prev, cur) {
                    return prev + dict[cur].length;
                }, 0);
            }
        });
    }

    function encode(str) {
        var replace = {
            '!': '%21',
            "'": '%27',
            '(': '%28',
            ')': '%29',
            '~': '%7E',
            '%20': '+',
            '%00': '\x00'
        };
        return encodeURIComponent(str).replace(/[!'\(\)~]|%20|%00/g, function(match) {
            return replace[match];
        });
    }

    function decode(str) {
        return str
            .replace(/[ +]/g, '%20')
            .replace(/(%[a-f0-9]{2})+/ig, function(match) {
                return decodeURIComponent(match);
            });
    }

    function makeIterator(arr) {
        var iterator = {
            next: function() {
                var value = arr.shift();
                return {done: value === undefined, value: value};
            }
        };

        if (iterable) {
            iterator[self.Symbol.iterator] = function() {
                return iterator;
            };
        }

        return iterator;
    }

    function parseToDict(search) {
        var dict = {};

        if (typeof search === "object") {
            // if `search` is an array, treat it as a sequence
            if (isArray(search)) {
                for (var i = 0; i < search.length; i++) {
                    var item = search[i];
                    if (isArray(item) && item.length === 2) {
                        appendTo(dict, item[0], item[1]);
                    } else {
                        throw new TypeError("Failed to construct 'URLSearchParams': Sequence initializer must only contain pair elements");
                    }
                }

            } else {
                for (var key in search) {
                    if (search.hasOwnProperty(key)) {
                        appendTo(dict, key, search[key]);
                    }
                }
            }

        } else {
            // remove first '?'
            if (search.indexOf("?") === 0) {
                search = search.slice(1);
            }

            var pairs = search.split("&");
            for (var j = 0; j < pairs.length; j++) {
                var value = pairs [j],
                    index = value.indexOf('=');

                if (-1 < index) {
                    appendTo(dict, decode(value.slice(0, index)), decode(value.slice(index + 1)));

                } else {
                    if (value) {
                        appendTo(dict, decode(value), '');
                    }
                }
            }
        }

        return dict;
    }

    function appendTo(dict, name, value) {
        var val = typeof value === 'string' ? value : (
            value !== null && value !== undefined && typeof value.toString === 'function' ? value.toString() : JSON.stringify(value)
        );

        // #47 Prevent using `hasOwnProperty` as a property name
        if (hasOwnProperty(dict, name)) {
            dict[name].push(val);
        } else {
            dict[name] = [val];
        }
    }

    function isArray(val) {
        return !!val && '[object Array]' === Object.prototype.toString.call(val);
    }

    function hasOwnProperty(obj, prop) {
        return Object.prototype.hasOwnProperty.call(obj, prop);
    }

})(typeof __webpack_require__.g !== 'undefined' ? __webpack_require__.g : (typeof window !== 'undefined' ? window : this));


/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue":
/*!*******************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ExternalShareAction.vue?vue&type=template&id=27835356 */ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356");
/* harmony import */ var _ExternalShareAction_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ExternalShareAction.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ExternalShareAction_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__.render,
  _ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/ExternalShareAction.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true");
/* harmony import */ var _SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "61240f7a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntry.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue":
/*!*********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "06bd31b0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue":
/*!********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f55cfc52",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInternal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue":
/*!****************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true");
/* harmony import */ var _SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7a675594",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryLink.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue":
/*!****************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "62b9dbb0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue":
/*!******************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "354542cc",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntrySimple.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=template&id=39161a5c */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c");
/* harmony import */ var _SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingInput.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b968620e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingDetailsTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true");
/* harmony import */ var _SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3f1bda78",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=template&id=dd248c84 */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84");
/* harmony import */ var _SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingLinkList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingList.vue?vue&type=template&id=698e26a4 */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4");
/* harmony import */ var _SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingList.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true");
/* harmony import */ var _SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0f81577f",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue":
/*!*************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AccountCircleOutline.vue?vue&type=template&id=4f5873d1 */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1");
/* harmony import */ var _AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AccountCircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.render,
  _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/AccountCircleOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js":
/*!************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "AccountCircleOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue":
/*!**************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CheckBold.vue?vue&type=template&id=486b2cb1 */ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1");
/* harmony import */ var _CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CheckBold.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.render,
  _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/CheckBold.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CheckBoldIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue":
/*!******************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CircleOutline.vue?vue&type=template&id=ad0ef454 */ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454");
/* harmony import */ var _CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.render,
  _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/CircleOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CircleOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardFlow.vue":
/*!******************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardFlow.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ClipboardFlow.vue?vue&type=template&id=2676ab68 */ "./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68");
/* harmony import */ var _ClipboardFlow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ClipboardFlow.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ClipboardFlow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__.render,
  _ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/ClipboardFlow.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ClipboardFlowIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/DotsHorizontal.vue":
/*!*******************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/DotsHorizontal.vue ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DotsHorizontal.vue?vue&type=template&id=3174c38c */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c");
/* harmony import */ var _DotsHorizontal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DotsHorizontal.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _DotsHorizontal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__.render,
  _DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/DotsHorizontal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "DotsHorizontalIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Email.vue":
/*!**********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Email.vue?vue&type=template&id=503121c0 */ "./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0");
/* harmony import */ var _Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Email.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.render,
  _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Email.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js":
/*!*********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EmailIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue":
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Exclamation.vue?vue&type=template&id=34aa771e */ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e");
/* harmony import */ var _Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Exclamation.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.render,
  _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Exclamation.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ExclamationIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Eye.vue":
/*!********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Eye.vue?vue&type=template&id=6cfe2635 */ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635");
/* harmony import */ var _Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Eye.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.render,
  _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Eye.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js":
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EyeIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue":
/*!***************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./EyeOutline.vue?vue&type=template&id=7b68237d */ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d");
/* harmony import */ var _EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EyeOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.render,
  _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/EyeOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js":
/*!**************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EyeOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/FileUpload.vue":
/*!***************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/FileUpload.vue ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileUpload.vue?vue&type=template&id=78fe596e */ "./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e");
/* harmony import */ var _FileUpload_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileUpload.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _FileUpload_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/FileUpload.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js":
/*!**************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "FileUploadIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Lock.vue":
/*!*********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Lock.vue?vue&type=template&id=0e7c8452 */ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452");
/* harmony import */ var _Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Lock.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.render,
  _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Lock.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LockIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue":
/*!***********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Qrcode.vue?vue&type=template&id=ff95848c */ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c");
/* harmony import */ var _Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Qrcode.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.render,
  _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Qrcode.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js":
/*!**********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "QrcodeIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue":
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ShareCircle.vue?vue&type=template&id=5c5332da */ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da");
/* harmony import */ var _ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ShareCircle.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.render,
  _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/ShareCircle.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ShareCircleIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue":
/*!**********************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TriangleSmallDown.vue?vue&type=template&id=7ca50825 */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825");
/* harmony import */ var _TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TriangleSmallDown.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.render,
  _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/TriangleSmallDown.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js":
/*!*********************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TriangleSmallDownIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Tune.vue":
/*!*********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Tune.vue?vue&type=template&id=f0bd6bb8 */ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8");
/* harmony import */ var _Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Tune.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.render,
  _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Tune.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TuneIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js":
/*!*******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ExternalShareAction.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js":
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js":
/*!*********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js":
/*!********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js":
/*!****************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js":
/*!****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js":
/*!******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js":
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js":
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js":
/*!***********************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js":
/*!**********************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js":
/*!******************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js":
/*!*****************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356":
/*!*************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356 ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ExternalShareAction.vue?vue&type=template&id=27835356 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true":
/*!******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true":
/*!***************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true":
/*!**************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true ***!
  \**************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true":
/*!**********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true":
/*!**********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true":
/*!************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c":
/*!******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=template&id=39161a5c */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true":
/*!******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true":
/*!*****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84":
/*!****************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84 ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=template&id=dd248c84 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4":
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4 ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=template&id=698e26a4 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true":
/*!***********************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true":
/*!*********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true":
/*!******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \******************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true":
/*!*************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \*************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true":
/*!*************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true":
/*!***************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \***************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss":
/*!*********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \*********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true":
/*!*********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true":
/*!********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss":
/*!**************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \**************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js":
/*!*************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./AccountCircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js":
/*!**************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./CheckBold.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./CircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ClipboardFlow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ClipboardFlow.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ClipboardFlow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js":
/*!*******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_DotsHorizontal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./DotsHorizontal.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_DotsHorizontal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js":
/*!**********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js":
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Exclamation.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js":
/*!********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Eye.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js":
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./EyeOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js":
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_FileUpload_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./FileUpload.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_FileUpload_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js":
/*!*********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Lock.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js":
/*!***********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Qrcode.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js":
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ShareCircle.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./TriangleSmallDown.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js":
/*!*********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Tune.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1":
/*!*******************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1 ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./AccountCircleOutline.vue?vue&type=template&id=4f5873d1 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1":
/*!********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1 ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./CheckBold.vue?vue&type=template&id=486b2cb1 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454":
/*!************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454 ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./CircleOutline.vue?vue&type=template&id=ad0ef454 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68":
/*!************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68 ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardFlow_vue_vue_type_template_id_2676ab68__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ClipboardFlow.vue?vue&type=template&id=2676ab68 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c":
/*!*************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_DotsHorizontal_vue_vue_type_template_id_3174c38c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./DotsHorizontal.vue?vue&type=template&id=3174c38c */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0":
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0 ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=template&id=503121c0 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e":
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Exclamation.vue?vue&type=template&id=34aa771e */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635":
/*!**************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635 ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Eye.vue?vue&type=template&id=6cfe2635 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d":
/*!*********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./EyeOutline.vue?vue&type=template&id=7b68237d */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e":
/*!*********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_FileUpload_vue_vue_type_template_id_78fe596e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./FileUpload.vue?vue&type=template&id=78fe596e */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452":
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Lock.vue?vue&type=template&id=0e7c8452 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c":
/*!*****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Qrcode.vue?vue&type=template&id=ff95848c */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da":
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ShareCircle.vue?vue&type=template&id=5c5332da */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825":
/*!****************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825 ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./TriangleSmallDown.vue?vue&type=template&id=7ca50825 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8":
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Tune.vue?vue&type=template&id=f0bd6bb8 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1":
/*!***********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1 ***!
  \***********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon account-circle-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M7.07,18.28C7.5,17.38 10.12,16.5 12,16.5C13.88,16.5 16.5,17.38 16.93,18.28C15.57,19.36 13.86,20 12,20C10.14,20 8.43,19.36 7.07,18.28M18.36,16.83C16.93,15.09 13.46,14.5 12,14.5C10.54,14.5 7.07,15.09 5.64,16.83C4.62,15.5 4,13.82 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,13.82 19.38,15.5 18.36,16.83M12,6C10.06,6 8.5,7.56 8.5,9.5C8.5,11.44 10.06,13 12,13C13.94,13 15.5,11.44 15.5,9.5C15.5,7.56 13.94,6 12,6M12,11A1.5,1.5 0 0,1 10.5,9.5A1.5,1.5 0 0,1 12,8A1.5,1.5 0 0,1 13.5,9.5A1.5,1.5 0 0,1 12,11Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1":
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1 ***!
  \************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon check-bold-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454":
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454 ***!
  \****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon circle-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68":
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardFlow.vue?vue&type=template&id=2676ab68 ***!
  \****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon clipboard-flow-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M12,3A1,1 0 0,1 13,4A1,1 0 0,1 12,5A1,1 0 0,1 11,4A1,1 0 0,1 12,3M15,19L11,15H14V8H16V15H19L15,19M10,16H8V9H5L9,5L13,9H10V16",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c":
/*!*****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/DotsHorizontal.vue?vue&type=template&id=3174c38c ***!
  \*****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon dots-horizontal-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M16,12A2,2 0 0,1 18,10A2,2 0 0,1 20,12A2,2 0 0,1 18,14A2,2 0 0,1 16,12M10,12A2,2 0 0,1 12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12M4,12A2,2 0 0,1 6,10A2,2 0 0,1 8,12A2,2 0 0,1 6,14A2,2 0 0,1 4,12Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0":
/*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0 ***!
  \********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon email-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon exclamation-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M 11,4L 13,4L 13,15L 11,15L 11,4 Z M 13,18L 13,20L 11,20L 11,18L 13,18 Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635":
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635 ***!
  \******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon eye-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d":
/*!*************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d ***!
  \*************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon eye-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e":
/*!*************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/FileUpload.vue?vue&type=template&id=78fe596e ***!
  \*************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon file-upload-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13.5,16V19H10.5V16H8L12,12L16,16H13.5M13,9V3.5L18.5,9H13Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452":
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452 ***!
  \*******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon lock-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c":
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c ***!
  \*********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon qrcode-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M3,11H5V13H3V11M11,5H13V9H11V5M9,11H13V15H11V13H9V11M15,11H17V13H19V11H21V13H19V15H21V19H19V21H17V19H13V21H11V17H15V15H17V13H15V11M19,19V15H17V19H19M15,3H21V9H15V3M17,5V7H19V5H17M3,3H9V9H3V3M5,5V7H7V5H5M3,15H9V21H3V15M5,17V19H7V17H5Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon share-circle-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M14 16V13C10.39 13 7.81 14.43 6 17C6.72 13.33 8.94 9.73 14 9V6L19 11L14 16Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825":
/*!********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825 ***!
  \********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon triangle-small-down-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c("path", { attrs: { d: "M8 9H16L12 16" } }, [
            _vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e(),
          ]),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8":
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8 ***!
  \*******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon tune-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M3,17V19H9V17H3M3,5V7H13V5H3M13,21V19H21V17H13V15H11V21H13M7,9V11H3V13H7V15H9V9H7M21,13V11H11V13H21M15,9H17V7H21V5H17V3H15V9Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e":
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \***************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "./node_modules/lodash-es/_Symbol.js":
/*!*******************************************!*\
  !*** ./node_modules/lodash-es/_Symbol.js ***!
  \*******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _root_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_root.js */ "./node_modules/lodash-es/_root.js");


/** Built-in value references. */
var Symbol = _root_js__WEBPACK_IMPORTED_MODULE_0__["default"].Symbol;

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Symbol);


/***/ }),

/***/ "./node_modules/lodash-es/_baseGetTag.js":
/*!***********************************************!*\
  !*** ./node_modules/lodash-es/_baseGetTag.js ***!
  \***********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_Symbol.js */ "./node_modules/lodash-es/_Symbol.js");
/* harmony import */ var _getRawTag_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getRawTag.js */ "./node_modules/lodash-es/_getRawTag.js");
/* harmony import */ var _objectToString_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_objectToString.js */ "./node_modules/lodash-es/_objectToString.js");




/** `Object#toString` result references. */
var nullTag = '[object Null]',
    undefinedTag = '[object Undefined]';

/** Built-in value references. */
var symToStringTag = _Symbol_js__WEBPACK_IMPORTED_MODULE_0__["default"] ? _Symbol_js__WEBPACK_IMPORTED_MODULE_0__["default"].toStringTag : undefined;

/**
 * The base implementation of `getTag` without fallbacks for buggy environments.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the `toStringTag`.
 */
function baseGetTag(value) {
  if (value == null) {
    return value === undefined ? undefinedTag : nullTag;
  }
  return (symToStringTag && symToStringTag in Object(value))
    ? (0,_getRawTag_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value)
    : (0,_objectToString_js__WEBPACK_IMPORTED_MODULE_2__["default"])(value);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (baseGetTag);


/***/ }),

/***/ "./node_modules/lodash-es/_baseTrim.js":
/*!*********************************************!*\
  !*** ./node_modules/lodash-es/_baseTrim.js ***!
  \*********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _trimmedEndIndex_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_trimmedEndIndex.js */ "./node_modules/lodash-es/_trimmedEndIndex.js");


/** Used to match leading whitespace. */
var reTrimStart = /^\s+/;

/**
 * The base implementation of `_.trim`.
 *
 * @private
 * @param {string} string The string to trim.
 * @returns {string} Returns the trimmed string.
 */
function baseTrim(string) {
  return string
    ? string.slice(0, (0,_trimmedEndIndex_js__WEBPACK_IMPORTED_MODULE_0__["default"])(string) + 1).replace(reTrimStart, '')
    : string;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (baseTrim);


/***/ }),

/***/ "./node_modules/lodash-es/_freeGlobal.js":
/*!***********************************************!*\
  !*** ./node_modules/lodash-es/_freeGlobal.js ***!
  \***********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof global == 'object' && global && global.Object === Object && global;

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (freeGlobal);


/***/ }),

/***/ "./node_modules/lodash-es/_getRawTag.js":
/*!**********************************************!*\
  !*** ./node_modules/lodash-es/_getRawTag.js ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_Symbol.js */ "./node_modules/lodash-es/_Symbol.js");


/** Used for built-in method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString = objectProto.toString;

/** Built-in value references. */
var symToStringTag = _Symbol_js__WEBPACK_IMPORTED_MODULE_0__["default"] ? _Symbol_js__WEBPACK_IMPORTED_MODULE_0__["default"].toStringTag : undefined;

/**
 * A specialized version of `baseGetTag` which ignores `Symbol.toStringTag` values.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the raw `toStringTag`.
 */
function getRawTag(value) {
  var isOwn = hasOwnProperty.call(value, symToStringTag),
      tag = value[symToStringTag];

  try {
    value[symToStringTag] = undefined;
    var unmasked = true;
  } catch (e) {}

  var result = nativeObjectToString.call(value);
  if (unmasked) {
    if (isOwn) {
      value[symToStringTag] = tag;
    } else {
      delete value[symToStringTag];
    }
  }
  return result;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (getRawTag);


/***/ }),

/***/ "./node_modules/lodash-es/_objectToString.js":
/*!***************************************************!*\
  !*** ./node_modules/lodash-es/_objectToString.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString = objectProto.toString;

/**
 * Converts `value` to a string using `Object.prototype.toString`.
 *
 * @private
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 */
function objectToString(value) {
  return nativeObjectToString.call(value);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (objectToString);


/***/ }),

/***/ "./node_modules/lodash-es/_root.js":
/*!*****************************************!*\
  !*** ./node_modules/lodash-es/_root.js ***!
  \*****************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _freeGlobal_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_freeGlobal.js */ "./node_modules/lodash-es/_freeGlobal.js");


/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = _freeGlobal_js__WEBPACK_IMPORTED_MODULE_0__["default"] || freeSelf || Function('return this')();

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (root);


/***/ }),

/***/ "./node_modules/lodash-es/_trimmedEndIndex.js":
/*!****************************************************!*\
  !*** ./node_modules/lodash-es/_trimmedEndIndex.js ***!
  \****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/** Used to match a single whitespace character. */
var reWhitespace = /\s/;

/**
 * Used by `_.trim` and `_.trimEnd` to get the index of the last non-whitespace
 * character of `string`.
 *
 * @private
 * @param {string} string The string to inspect.
 * @returns {number} Returns the index of the last non-whitespace character.
 */
function trimmedEndIndex(string) {
  var index = string.length;

  while (index-- && reWhitespace.test(string.charAt(index))) {}
  return index;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (trimmedEndIndex);


/***/ }),

/***/ "./node_modules/lodash-es/debounce.js":
/*!********************************************!*\
  !*** ./node_modules/lodash-es/debounce.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/lodash-es/isObject.js");
/* harmony import */ var _now_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./now.js */ "./node_modules/lodash-es/now.js");
/* harmony import */ var _toNumber_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toNumber.js */ "./node_modules/lodash-es/toNumber.js");




/** Error message constants. */
var FUNC_ERROR_TEXT = 'Expected a function';

/* Built-in method references for those with the same name as other `lodash` methods. */
var nativeMax = Math.max,
    nativeMin = Math.min;

/**
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel
 * delayed `func` invocations and a `flush` method to immediately invoke them.
 * Provide `options` to indicate whether `func` should be invoked on the
 * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
 * with the last arguments provided to the debounced function. Subsequent
 * calls to the debounced function return the result of the last `func`
 * invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
 * for details over the differences between `_.debounce` and `_.throttle`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to debounce.
 * @param {number} [wait=0] The number of milliseconds to delay.
 * @param {Object} [options={}] The options object.
 * @param {boolean} [options.leading=false]
 *  Specify invoking on the leading edge of the timeout.
 * @param {number} [options.maxWait]
 *  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean} [options.trailing=true]
 *  Specify invoking on the trailing edge of the timeout.
 * @returns {Function} Returns the new debounced function.
 * @example
 *
 * // Avoid costly calculations while the window size is in flux.
 * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
 *
 * // Invoke `sendMail` when clicked, debouncing subsequent calls.
 * jQuery(element).on('click', _.debounce(sendMail, 300, {
 *   'leading': true,
 *   'trailing': false
 * }));
 *
 * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
 * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
 * var source = new EventSource('/stream');
 * jQuery(source).on('message', debounced);
 *
 * // Cancel the trailing debounced invocation.
 * jQuery(window).on('popstate', debounced.cancel);
 */
function debounce(func, wait, options) {
  var lastArgs,
      lastThis,
      maxWait,
      result,
      timerId,
      lastCallTime,
      lastInvokeTime = 0,
      leading = false,
      maxing = false,
      trailing = true;

  if (typeof func != 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  wait = (0,_toNumber_js__WEBPACK_IMPORTED_MODULE_0__["default"])(wait) || 0;
  if ((0,_isObject_js__WEBPACK_IMPORTED_MODULE_1__["default"])(options)) {
    leading = !!options.leading;
    maxing = 'maxWait' in options;
    maxWait = maxing ? nativeMax((0,_toNumber_js__WEBPACK_IMPORTED_MODULE_0__["default"])(options.maxWait) || 0, wait) : maxWait;
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }

  function invokeFunc(time) {
    var args = lastArgs,
        thisArg = lastThis;

    lastArgs = lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    timerId = setTimeout(timerExpired, wait);
    // Invoke the leading edge.
    return leading ? invokeFunc(time) : result;
  }

  function remainingWait(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime,
        timeWaiting = wait - timeSinceLastCall;

    return maxing
      ? nativeMin(timeWaiting, maxWait - timeSinceLastInvoke)
      : timeWaiting;
  }

  function shouldInvoke(time) {
    var timeSinceLastCall = time - lastCallTime,
        timeSinceLastInvoke = time - lastInvokeTime;

    // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.
    return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||
      (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
  }

  function timerExpired() {
    var time = (0,_now_js__WEBPACK_IMPORTED_MODULE_2__["default"])();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    timerId = setTimeout(timerExpired, remainingWait(time));
  }

  function trailingEdge(time) {
    timerId = undefined;

    // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
    lastInvokeTime = 0;
    lastArgs = lastCallTime = lastThis = timerId = undefined;
  }

  function flush() {
    return timerId === undefined ? result : trailingEdge((0,_now_js__WEBPACK_IMPORTED_MODULE_2__["default"])());
  }

  function debounced() {
    var time = (0,_now_js__WEBPACK_IMPORTED_MODULE_2__["default"])(),
        isInvoking = shouldInvoke(time);

    lastArgs = arguments;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (timerId === undefined) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        // Handle invocations in a tight loop.
        clearTimeout(timerId);
        timerId = setTimeout(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (timerId === undefined) {
      timerId = setTimeout(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  return debounced;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (debounce);


/***/ }),

/***/ "./node_modules/lodash-es/isObject.js":
/*!********************************************!*\
  !*** ./node_modules/lodash-es/isObject.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return value != null && (type == 'object' || type == 'function');
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isObject);


/***/ }),

/***/ "./node_modules/lodash-es/isObjectLike.js":
/*!************************************************!*\
  !*** ./node_modules/lodash-es/isObjectLike.js ***!
  \************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return value != null && typeof value == 'object';
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isObjectLike);


/***/ }),

/***/ "./node_modules/lodash-es/isSymbol.js":
/*!********************************************!*\
  !*** ./node_modules/lodash-es/isSymbol.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _baseGetTag_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_baseGetTag.js */ "./node_modules/lodash-es/_baseGetTag.js");
/* harmony import */ var _isObjectLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isObjectLike.js */ "./node_modules/lodash-es/isObjectLike.js");



/** `Object#toString` result references. */
var symbolTag = '[object Symbol]';

/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */
function isSymbol(value) {
  return typeof value == 'symbol' ||
    ((0,_isObjectLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(value) && (0,_baseGetTag_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value) == symbolTag);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isSymbol);


/***/ }),

/***/ "./node_modules/lodash-es/now.js":
/*!***************************************!*\
  !*** ./node_modules/lodash-es/now.js ***!
  \***************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _root_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_root.js */ "./node_modules/lodash-es/_root.js");


/**
 * Gets the timestamp of the number of milliseconds that have elapsed since
 * the Unix epoch (1 January 1970 00:00:00 UTC).
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Date
 * @returns {number} Returns the timestamp.
 * @example
 *
 * _.defer(function(stamp) {
 *   console.log(_.now() - stamp);
 * }, _.now());
 * // => Logs the number of milliseconds it took for the deferred invocation.
 */
var now = function() {
  return _root_js__WEBPACK_IMPORTED_MODULE_0__["default"].Date.now();
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (now);


/***/ }),

/***/ "./node_modules/lodash-es/toNumber.js":
/*!********************************************!*\
  !*** ./node_modules/lodash-es/toNumber.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _baseTrim_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_baseTrim.js */ "./node_modules/lodash-es/_baseTrim.js");
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/lodash-es/isObject.js");
/* harmony import */ var _isSymbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isSymbol.js */ "./node_modules/lodash-es/isSymbol.js");




/** Used as references for various `Number` constants. */
var NAN = 0 / 0;

/** Used to detect bad signed hexadecimal string values. */
var reIsBadHex = /^[-+]0x[0-9a-f]+$/i;

/** Used to detect binary string values. */
var reIsBinary = /^0b[01]+$/i;

/** Used to detect octal string values. */
var reIsOctal = /^0o[0-7]+$/i;

/** Built-in method references without a dependency on `root`. */
var freeParseInt = parseInt;

/**
 * Converts `value` to a number.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to process.
 * @returns {number} Returns the number.
 * @example
 *
 * _.toNumber(3.2);
 * // => 3.2
 *
 * _.toNumber(Number.MIN_VALUE);
 * // => 5e-324
 *
 * _.toNumber(Infinity);
 * // => Infinity
 *
 * _.toNumber('3.2');
 * // => 3.2
 */
function toNumber(value) {
  if (typeof value == 'number') {
    return value;
  }
  if ((0,_isSymbol_js__WEBPACK_IMPORTED_MODULE_0__["default"])(value)) {
    return NAN;
  }
  if ((0,_isObject_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value)) {
    var other = typeof value.valueOf == 'function' ? value.valueOf() : value;
    value = (0,_isObject_js__WEBPACK_IMPORTED_MODULE_1__["default"])(other) ? (other + '') : other;
  }
  if (typeof value != 'string') {
    return value === 0 ? value : +value;
  }
  value = (0,_baseTrim_js__WEBPACK_IMPORTED_MODULE_2__["default"])(value);
  var isBinary = reIsBinary.test(value);
  return (isBinary || reIsOctal.test(value))
    ? freeParseInt(value.slice(2), isBinary ? 2 : 8)
    : (reIsBadHex.test(value) ? NAN : +value);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (toNumber);


/***/ }),

/***/ "./node_modules/nextcloud-vue-collections/dist/index.mjs":
/*!***************************************************************!*\
  !*** ./node_modules/nextcloud-vue-collections/dist/index.mjs ***!
  \***************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CollectionList: () => (/* binding */ Q),
/* harmony export */   CollectionListItem: () => (/* binding */ j)
/* harmony export */ });
/* harmony import */ var _assets_index_Au1Gr_G6_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./assets/index-Au1Gr_G6.css */ "./node_modules/nextcloud-vue-collections/dist/assets/index-Au1Gr_G6.css");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var lodash_es_debounce_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! lodash-es/debounce.js */ "./node_modules/lodash-es/debounce.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/nextcloud-vue-collections/node_modules/@nextcloud/router/dist/index.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");









/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
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
 */
class D {
  constructor() {
    this.http = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_5__["default"];
  }
  listCollection(e) {
    return this.http.get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/collections/{collectionId}", { collectionId: e }));
  }
  renameCollection(e, o) {
    return this.http.put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/collections/{collectionId}", { collectionId: e }), {
      collectionName: o
    }).then((n) => n.data.ocs.data);
  }
  getCollectionsByResource(e, o) {
    return this.http.get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/{resourceType}/{resourceId}", { resourceType: e, resourceId: o })).then((n) => n.data.ocs.data);
  }
  createCollection(e, o, n) {
    return this.http.post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/{resourceType}/{resourceId}", { resourceType: e, resourceId: o }), {
      name: n
    }).then((r) => r.data.ocs.data);
  }
  addResource(e, o, n) {
    return n = "" + n, this.http.post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/collections/{collectionId}", { collectionId: e }), {
      resourceType: o,
      resourceId: n
    }).then((r) => r.data.ocs.data);
  }
  removeResource(e, o, n) {
    return this.http.delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/collections/{collectionId}", { collectionId: e }), { params: { resourceType: o, resourceId: n } }).then((r) => r.data.ocs.data);
  }
  search(e) {
    return this.http.get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)("collaboration/resources/collections/search/{query}", { query: e })).then((o) => o.data.ocs.data);
  }
}
const p = new D();
/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
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
 */
const u = vue__WEBPACK_IMPORTED_MODULE_7__["default"].observable({
  collections: []
}), h = {
  addCollections(s) {
    (0,vue__WEBPACK_IMPORTED_MODULE_7__.set)(u, "collections", s);
  },
  addCollection(s) {
    u.collections.push(s);
  },
  removeCollection(s) {
    (0,vue__WEBPACK_IMPORTED_MODULE_7__.set)(u, "collections", u.collections.filter((e) => e.id !== s));
  },
  updateCollection(s) {
    const e = u.collections.findIndex((o) => o.id === s.id);
    e !== -1 ? (0,vue__WEBPACK_IMPORTED_MODULE_7__.set)(u.collections, e, s) : u.collections.push(s);
  }
}, l = {
  fetchCollectionsByResource({ resourceType: s, resourceId: e }) {
    return p.getCollectionsByResource(s, e).then((o) => (h.addCollections(o), o));
  },
  createCollection({ baseResourceType: s, baseResourceId: e, resourceType: o, resourceId: n, name: r }) {
    return p.createCollection(s, e, r).then((m) => {
      h.addCollection(m), l.addResourceToCollection({
        collectionId: m.id,
        resourceType: o,
        resourceId: n
      });
    });
  },
  renameCollection({ collectionId: s, name: e }) {
    return p.renameCollection(s, e).then((o) => (h.updateCollection(o), o));
  },
  addResourceToCollection({ collectionId: s, resourceType: e, resourceId: o }) {
    return p.addResource(s, e, o).then((n) => (h.updateCollection(n), n));
  },
  removeResource({ collectionId: s, resourceType: e, resourceId: o }) {
    return p.removeResource(s, e, o).then((n) => {
      n.resources.length > 0 ? h.updateCollection(n) : h.removeCollection(n);
    });
  },
  search(s) {
    return p.search(s);
  }
};
function R(s, e, o, n, r, m, _, I) {
  var i = typeof s == "function" ? s.options : s;
  e && (i.render = e, i.staticRenderFns = o, i._compiled = !0), n && (i.functional = !0), m && (i._scopeId = "data-v-" + m);
  var a;
  if (_ ? (a = function(c) {
    c = c || // cached call
    this.$vnode && this.$vnode.ssrContext || // stateful
    this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext, !c && typeof __VUE_SSR_CONTEXT__ < "u" && (c = __VUE_SSR_CONTEXT__), r && r.call(this, c), c && c._registeredComponents && c._registeredComponents.add(_);
  }, i._ssrRegister = a) : r && (a = I ? function() {
    r.call(
      this,
      (i.functional ? this.parent : this).$root.$options.shadowRoot
    );
  } : r), a)
    if (i.functional) {
      i._injectStyles = a;
      var b = i.render;
      i.render = function(N, v) {
        return a.call(v), b(N, v);
      };
    } else {
      var C = i.beforeCreate;
      i.beforeCreate = C ? [].concat(C, a) : [a];
    }
  return {
    exports: s,
    options: i
  };
}
const B = {
  name: "CollectionListItem",
  components: {
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    collection: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      detailsOpen: !1,
      newName: null,
      error: {}
    };
  },
  computed: {
    getIcon() {
      return (s) => [s.iconClass];
    },
    typeClass() {
      return (s) => "resource-type-" + s.type;
    },
    limitedResources() {
      return (s) => s.resources ? s.resources.slice(0, 2) : [];
    },
    iconUrl() {
      return (s) => s.mimetype ? OC.MimeType.getIconUrl(s.mimetype) : s.iconUrl ? s.iconUrl : "";
    }
  },
  methods: {
    toggleDetails() {
      this.detailsOpen = !this.detailsOpen;
    },
    showDetails() {
      this.detailsOpen = !0;
    },
    hideDetails() {
      this.detailsOpen = !1;
    },
    removeResource(s, e) {
      l.removeResource({
        collectionId: s.id,
        resourceType: e.type,
        resourceId: e.id
      });
    },
    openRename() {
      this.newName = this.collection.name;
    },
    renameCollection() {
      if (this.newName === "") {
        this.newName = null;
        return;
      }
      l.renameCollection({
        collectionId: this.collection.id,
        name: this.newName
      }).then((s) => {
        this.newName = null;
      }).catch((s) => {
        this.$set(this.error, "rename", t("core", "Failed to rename the project")), console.error(s), setTimeout(() => {
          (0,vue__WEBPACK_IMPORTED_MODULE_7__.set)(this.error, "rename", null);
        }, 3e3);
      });
    }
  }
};
var E = function() {
  var e = this, o = e._self._c;
  return o("li", { staticClass: "collection-list-item" }, [o("NcAvatar", { staticClass: "collection-avatar", attrs: { "display-name": e.collection.name, "allow-placeholder": "" } }), e.newName === null ? o("span", { staticClass: "collection-item-name", attrs: { title: "" }, on: { click: e.showDetails } }, [e._v(e._s(e.collection.name))]) : o("form", { class: { shouldshake: e.error.rename }, on: { submit: function(n) {
    return n.preventDefault(), e.renameCollection.apply(null, arguments);
  } } }, [o("input", { directives: [{ name: "model", rawName: "v-model", value: e.newName, expression: "newName" }], attrs: { type: "text", autocomplete: "off", autocapitalize: "off" }, domProps: { value: e.newName }, on: { input: function(n) {
    n.target.composing || (e.newName = n.target.value);
  } } }), o("input", { staticClass: "icon-confirm", attrs: { type: "submit", value: "" } })]), !e.detailsOpen && e.newName === null ? o("div", { staticClass: "linked-icons" }, e._l(e.limitedResources(e.collection), function(n) {
    return o("a", { key: n.type + "|" + n.id, class: e.typeClass(n), attrs: { title: n.name, href: n.link } }, [o("img", { attrs: { src: e.iconUrl(n) } })]);
  }), 0) : e._e(), e.newName === null ? o("span", { staticClass: "sharingOptionsGroup" }, [o("NcActions", [o("NcActionButton", { attrs: { icon: "icon-info" }, on: { click: function(n) {
    return n.preventDefault(), e.toggleDetails.apply(null, arguments);
  } } }, [e._v(" " + e._s(e.detailsOpen ? e.t("core", "Hide details") : e.t("core", "Show details")) + " ")]), o("NcActionButton", { attrs: { icon: "icon-rename" }, on: { click: function(n) {
    return n.preventDefault(), e.openRename.apply(null, arguments);
  } } }, [e._v(" " + e._s(e.t("core", "Rename project")) + " ")])], 1)], 1) : e._e(), o("transition", { attrs: { name: "fade" } }, [e.error.rename ? o("div", { staticClass: "error" }, [e._v(" " + e._s(e.error.rename) + " ")]) : e._e()]), o("transition", { attrs: { name: "fade" } }, [e.detailsOpen ? o("ul", { staticClass: "resource-list-details" }, e._l(e.collection.resources, function(n) {
    return o("li", { key: n.type + "|" + n.id, class: e.typeClass(n) }, [o("a", { attrs: { href: n.link } }, [o("img", { attrs: { src: e.iconUrl(n) } }), o("span", { staticClass: "resource-name" }, [e._v(e._s(n.name || ""))])]), o("span", { staticClass: "icon-close", on: { click: function(r) {
      return e.removeResource(e.collection, n);
    } } })]);
  }), 0) : e._e()])], 1);
}, L = [], U = /* @__PURE__ */ R(
  B,
  E,
  L,
  !1,
  null,
  "8e58e0a5",
  null,
  null
);
const j = U.exports, y = 0, g = 1, F = (0,lodash_es_debounce_js__WEBPACK_IMPORTED_MODULE_8__["default"])(
  function(s, e) {
    s !== "" && (e(!0), l.search(s).then((o) => {
      this.searchCollections = o;
    }).catch((o) => {
      console.error("Failed to search for collections", o);
    }).finally(() => {
      e(!1);
    }));
  },
  500,
  {}
), P = {
  name: "CollectionList",
  components: {
    CollectionListItem: j,
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  props: {
    /**
     * Resource type identifier
     */
    type: {
      type: String,
      default: null
    },
    /**
     * Unique id of the resource
     */
    id: {
      type: String,
      default: null
    },
    /**
     * Name of the resource
     */
    name: {
      type: String,
      default: ""
    },
    isActive: {
      type: Boolean,
      default: !0
    }
  },
  data() {
    return {
      selectIsOpen: !1,
      generatingCodes: !1,
      codes: void 0,
      value: null,
      model: {},
      searchCollections: [],
      error: null,
      state: u,
      isSelectOpen: !1
    };
  },
  computed: {
    collections() {
      return this.state.collections.filter((s) => typeof s.resources.find((e) => e && e.id === "" + this.id && e.type === this.type) < "u");
    },
    placeholder() {
      return this.isSelectOpen ? t("core", "Type to search for existing projects") : t("core", "Add to a project");
    },
    options() {
      const s = [];
      window.OCP.Collaboration.getTypes().sort().forEach((e) => {
        s.push({
          method: y,
          type: e,
          title: window.OCP.Collaboration.getLabel(e),
          class: window.OCP.Collaboration.getIcon(e),
          action: () => window.OCP.Collaboration.trigger(e)
        });
      });
      for (const e in this.searchCollections)
        this.collections.findIndex((o) => o.id === this.searchCollections[e].id) === -1 && s.push({
          method: g,
          title: this.searchCollections[e].name,
          collectionId: this.searchCollections[e].id
        });
      return s;
    }
  },
  watch: {
    type() {
      this.isActive && l.fetchCollectionsByResource({
        resourceType: this.type,
        resourceId: this.id
      });
    },
    id() {
      this.isActive && l.fetchCollectionsByResource({
        resourceType: this.type,
        resourceId: this.id
      });
    },
    isActive(s) {
      s && l.fetchCollectionsByResource({
        resourceType: this.type,
        resourceId: this.id
      });
    }
  },
  mounted() {
    l.fetchCollectionsByResource({
      resourceType: this.type,
      resourceId: this.id
    });
  },
  methods: {
    select(s, e) {
      s.method === y && s.action().then((o) => {
        l.createCollection({
          baseResourceType: this.type,
          baseResourceId: this.id,
          resourceType: s.type,
          resourceId: o,
          name: this.name
        }).catch((n) => {
          this.setError(t("core", "Failed to create a project"), n);
        });
      }).catch((o) => {
        console.error("No resource selected", o);
      }), s.method === g && l.addResourceToCollection({
        collectionId: s.collectionId,
        resourceType: this.type,
        resourceId: this.id
      }).catch((o) => {
        this.setError(t("core", "Failed to add the item to the project"), o);
      });
    },
    search(s, e) {
      F.bind(this)(s, e);
    },
    showSelect() {
      this.selectIsOpen = !0, this.$refs.select.$el.focus();
    },
    hideSelect() {
      this.selectIsOpen = !1;
    },
    isVueComponent(s) {
      return s._isVue;
    },
    setError(s, e) {
      console.error(s, e), this.error = s, setTimeout(() => {
        this.error = null;
      }, 5e3);
    }
  }
};
var V = function() {
  var e = this, o = e._self._c;
  return e.collections && e.type && e.id ? o("ul", { staticClass: "collection-list", attrs: { id: "collection-list" } }, [o("li", { on: { click: e.showSelect } }, [e._m(0), o("div", { attrs: { id: "collection-select-container" } }, [o("NcSelect", { ref: "select", attrs: { "aria-label-combobox": e.t("core", "Add to a project"), options: e.options, placeholder: e.placeholder, label: "title", limit: 5 }, on: { close: function(n) {
    e.isSelectOpen = !1;
  }, open: function(n) {
    e.isSelectOpen = !0;
  }, "option:selected": e.select, search: e.search }, scopedSlots: e._u([{ key: "selected-option", fn: function(n) {
    return [o("span", { staticClass: "option__desc" }, [o("span", { staticClass: "option__title" }, [e._v(e._s(n.title))])])];
  } }, { key: "option", fn: function(n) {
    return [o("span", { staticClass: "option__wrapper" }, [n.class ? o("span", { staticClass: "avatar", class: n.class }) : n.method !== 2 ? o("NcAvatar", { attrs: { "allow-placeholder": "", "display-name": n.title } }) : e._e(), o("span", { staticClass: "option__title" }, [e._v(e._s(n.title))])], 1)];
  } }], null, !1, 2397208459), model: { value: e.value, callback: function(n) {
    e.value = n;
  }, expression: "value" } }, [o("p", { staticClass: "hint" }, [e._v(" " + e._s(e.t("core", "Connect items to a project to make them easier to find")) + " ")])])], 1)]), o("transition", { attrs: { name: "fade" } }, [e.error ? o("li", { staticClass: "error" }, [e._v(" " + e._s(e.error) + " ")]) : e._e()]), e._l(e.collections, function(n) {
    return o("CollectionListItem", { key: n.id, attrs: { collection: n } });
  })], 2) : e._e();
}, x = [function() {
  var s = this, e = s._self._c;
  return e("div", { staticClass: "avatar" }, [e("span", { staticClass: "icon-projects" })]);
}], H = /* @__PURE__ */ R(
  P,
  V,
  x,
  !1,
  null,
  "75a4370b",
  null,
  null
);
const Q = H.exports;



/***/ })

}]);
//# sourceMappingURL=apps_files_sharing_src_views_SharingTab_vue-data_image_svg_xml_3csvg_20xmlns_27http_www_w3_or-753dbe-apps_files_sharing_src_views_SharingTab_vue-data_image_svg_xml_3csvg_20xmlns_27http_www_w3_or-753dbe.js.map?v=3a7850c191f4d3187d23