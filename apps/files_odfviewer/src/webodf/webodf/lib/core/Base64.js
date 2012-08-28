/**
 * Copyright (C) 2011 KO GmbH <jos.van.den.oever@kogmbh.com>
 * @licstart
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Affero General Public License
 * (GNU AGPL) as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.  The code is distributed
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU AGPL for more details.
 *
 * As additional permission under GNU AGPL version 3 section 7, you
 * may distribute non-source (e.g., minimized or compacted) forms of
 * that code without the copy of the GNU GPL normally required by
 * section 4, provided you include this license notice and a URL
 * through which recipients can access the Corresponding Source.
 *
 * As a special exception to the AGPL, any HTML file which merely makes function
 * calls to this code, and for that purpose includes it by reference shall be
 * deemed a separate work for copyright law purposes. In addition, the copyright
 * holders of this code give you permission to combine this code with free
 * software libraries that are released under the GNU LGPL. You may copy and
 * distribute such a system following the terms of the GNU AGPL for this code
 * and the LGPL for the libraries. If you modify this code, you may extend this
 * exception to your version of the code, but you are not obligated to do so.
 * If you do not wish to do so, delete this exception statement from your
 * version.
 *
 * This license applies to this entire compilation.
 * @licend
 * @source: http://www.webodf.org/
 * @source: http://gitorious.org/odfkit/webodf/
 */
/*jslint bitwise: true, regexp: true*/
/*global core: true, runtime: true*/
/*
 * $Id: base64.js,v 0.9 2009/03/01 20:51:18 dankogai Exp dankogai $
 */
/**
 * @namespace
 */
core.Base64 = (function () {
    "use strict";
    var b64chars
        = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',

        b64charcodes = (function () {
            var a = [], i,
                codeA = 'A'.charCodeAt(0),
                codea = 'a'.charCodeAt(0),
                code0 = '0'.charCodeAt(0);
            for (i = 0; i < 26; i += 1) {
                a.push(codeA + i);
            }
            for (i = 0; i < 26; i += 1) {
                a.push(codea + i);
            }
            for (i = 0; i < 10; i += 1) {
                a.push(code0 + i);
            }
            a.push('+'.charCodeAt(0));
            a.push('/'.charCodeAt(0));
            return a;
        }()),

        b64tab = (function (bin) {
            var t = {}, i, l;
            for (i = 0, l = bin.length; i < l; i += 1) {
                t[bin.charAt(i)] = i;
            }
            return t;
        }(b64chars)),
        convertUTF16StringToBase64,
        convertBase64ToUTF16String,
        btoa, atob;

    /**
     * @param {!string} s
     * @return {!Array}
     */
    function stringToArray(s) {
        var a = [], i, l = s.length;
        for (i = 0; i < l; i += 1) {
            a[i] = s.charCodeAt(i) & 0xff;
        }
        return a;
    }

    function convertUTF8ArrayToBase64(bin) {
        var n,
            b64 = "",
            i,
            l = bin.length - 2;
        for (i = 0; i < l; i += 3) {
            n = (bin[i] << 16) | (bin[i + 1] << 8) | bin[i + 2];
            b64 += b64chars[n >>> 18];
            b64 += b64chars[(n >>> 12) & 63];
            b64 += b64chars[(n >>>  6) & 63];
            b64 += b64chars[n          & 63];
        }
        if (i === l + 1) { // 1 byte left
            n = bin[i] << 4;
            b64 += b64chars[n >>> 6];
            b64 += b64chars[n & 63];
            b64 += "==";
        } else if (i === l) { // 2 bytes left
            n = (bin[i] << 10) | (bin[i + 1] << 2);
            b64 += b64chars[n >>> 12];
            b64 += b64chars[(n >>> 6) & 63];
            b64 += b64chars[n & 63];
            b64 += "=";
        }
        return b64;
    }

    function convertBase64ToUTF8Array(b64) {
        b64 = b64.replace(/[^A-Za-z0-9+\/]+/g, '');
        var bin = [],
            padlen = b64.length % 4,
            i,
            l = b64.length,
            n;
        for (i = 0; i < l; i += 4) {
            n = ((b64tab[b64.charAt(i)]     || 0) << 18) |
                ((b64tab[b64.charAt(i + 1)] || 0) << 12) |
                ((b64tab[b64.charAt(i + 2)] || 0) <<  6) |
                ((b64tab[b64.charAt(i + 3)] || 0));
            bin.push(
                (n >> 16),
                ((n >>  8) & 0xff),
                (n         & 0xff)
            );
        }
        bin.length -= [0, 0, 2, 1][padlen];
        return bin;
    }

    function convertUTF16ArrayToUTF8Array(uni) {
        var bin = [], i, l = uni.length, n;
        for (i = 0; i < l; i += 1) {
            n = uni[i];
            if (n < 0x80) {
                bin.push(n);
            } else if (n < 0x800) {
                bin.push(
                    0xc0 | (n >>>  6),
                    0x80 | (n & 0x3f)
                );
            } else {
                bin.push(
                    0xe0 | ((n >>> 12) & 0x0f),
                    0x80 | ((n >>>  6) & 0x3f),
                    0x80 |  (n         & 0x3f)
                );
            }
        }
        return bin;
    }

    function convertUTF8ArrayToUTF16Array(bin) {
        var uni = [], i, l = bin.length,
            c0, c1, c2;
        for (i = 0; i < l; i += 1) {
            c0 = bin[i];
            if (c0 < 0x80) {
                uni.push(c0);
            } else {
                i += 1;
                c1 = bin[i];
                if (c0 < 0xe0) {
                    uni.push(((c0 & 0x1f) << 6) | (c1 & 0x3f));
                } else {
                    i += 1;
                    c2 = bin[i];
                    uni.push(((c0 & 0x0f) << 12) | ((c1 & 0x3f) << 6) |
                            (c2 & 0x3f)
                        );
                }
            }
        }
        return uni;
    }

    function convertUTF8StringToBase64(bin) {
        return convertUTF8ArrayToBase64(stringToArray(bin));
    }

    function convertBase64ToUTF8String(b64) {
        return String.fromCharCode.apply(String, convertBase64ToUTF8Array(b64));
    }

    function convertUTF8StringToUTF16Array(bin) {
        return convertUTF8ArrayToUTF16Array(stringToArray(bin));
    }

    function convertUTF8ArrayToUTF16String(bin) {
        // this conversion is done in chunks to avoid a stack overflow in
        // apply()
        var b = convertUTF8ArrayToUTF16Array(bin),
            r = "",
            i = 0,
            chunksize = 45000;
        while (i < b.length) {
            r += String.fromCharCode.apply(String, b.slice(i, i + chunksize));
            i += chunksize;
        }
        return r;
    }
    /**
     * @param {!Array.<number>|!string} bin
     * @param {!number} i
     * @param {!number} end
     * @return {!string}
     */
    function convertUTF8StringToUTF16String_internal(bin, i, end) {
        var str = "", c0, c1, c2, j;
        for (j = i; j < end; j += 1) {
            c0 = bin.charCodeAt(j) & 0xff;
            if (c0 < 0x80) {
                str += String.fromCharCode(c0);
            } else {
                j += 1;
                c1 = bin.charCodeAt(j) & 0xff;
                if (c0 < 0xe0) {
                    str += String.fromCharCode(((c0 & 0x1f) << 6) |
                        (c1 & 0x3f));
                } else {
                    j += 1;
                    c2 = bin.charCodeAt(j) & 0xff;
                    str += String.fromCharCode(((c0 & 0x0f) << 12) |
                            ((c1 & 0x3f) << 6) | (c2 & 0x3f));
                }
            }
        }
        return str;
    }

    /**
     * Convert a utf-8 array into a utf-16 string.
     * The input array is treated as a list of values between 0 and 255.
     * This function works with a callback and splits the work up in parts
     * between which it yields to the main thread.
     * After each part the progress is reported with the callback function that
     * also passes a booleant that indicates if the job has finished.
     * If the conversion should stop, the callback should return false.
     *
     * @param {!Array.<number>|!string} bin
     * @param {!function(!string, boolean):boolean} callback
     * @return {undefined}
     */
    function convertUTF8StringToUTF16String(bin, callback) {
        var partsize = 100000,
            numparts = bin.length / partsize,
            str = "",
            pos = 0;
        if (bin.length < partsize) {
            callback(convertUTF8StringToUTF16String_internal(bin, 0,
                    bin.length), true);
            return;
        }
        // make a local copy if the input is a string, to avoid modification
        if (typeof bin !== "string") {
            bin = bin.slice();
        }
        function f() {
            var end = pos + partsize;
            if (end > bin.length) {
                end = bin.length;
            }
            str += convertUTF8StringToUTF16String_internal(bin, pos, end);
            pos = end;
            end = pos === bin.length;
            if (callback(str, end) && !end) {
                runtime.setTimeout(f, 0);
            }
        }
        f();
    }

    function convertUTF16StringToUTF8Array(uni) {
        return convertUTF16ArrayToUTF8Array(stringToArray(uni));
    }

    function convertUTF16ArrayToUTF8String(uni) {
        return String.fromCharCode.apply(String,
                 convertUTF16ArrayToUTF8Array(uni));
    }

    function convertUTF16StringToUTF8String(uni) {
        return String.fromCharCode.apply(String,
                 convertUTF16ArrayToUTF8Array(stringToArray(uni)));
    }

    btoa = runtime.getWindow() && runtime.getWindow().btoa;
    if (btoa) {
        convertUTF16StringToBase64 = function (uni) {
            return btoa(convertUTF16StringToUTF8String(uni));
        };
    } else {
        btoa = convertUTF8StringToBase64;
        convertUTF16StringToBase64 = function (uni) {
            return convertUTF8ArrayToBase64(convertUTF16StringToUTF8Array(uni));
        };
    }
    atob = runtime.getWindow() && runtime.getWindow().atob;
    if (atob) {
        convertBase64ToUTF16String = function (b64) {
            var b = atob(b64);
            return convertUTF8StringToUTF16String_internal(b, 0, b.length);
        };
    } else {
        atob = convertBase64ToUTF8String;
        convertBase64ToUTF16String = function (b64) {
            return convertUTF8ArrayToUTF16String(convertBase64ToUTF8Array(b64));
        };
    }

    /**
     * @constructor
     */
    function Base64() {
        this.convertUTF8ArrayToBase64 = convertUTF8ArrayToBase64;
        this.convertByteArrayToBase64 = convertUTF8ArrayToBase64;
        this.convertBase64ToUTF8Array = convertBase64ToUTF8Array;
        this.convertBase64ToByteArray = convertBase64ToUTF8Array;
        this.convertUTF16ArrayToUTF8Array = convertUTF16ArrayToUTF8Array;
        this.convertUTF16ArrayToByteArray = convertUTF16ArrayToUTF8Array;
        this.convertUTF8ArrayToUTF16Array = convertUTF8ArrayToUTF16Array;
        this.convertByteArrayToUTF16Array = convertUTF8ArrayToUTF16Array;
        this.convertUTF8StringToBase64 = convertUTF8StringToBase64;
        this.convertBase64ToUTF8String = convertBase64ToUTF8String;
        this.convertUTF8StringToUTF16Array = convertUTF8StringToUTF16Array;
        this.convertUTF8ArrayToUTF16String = convertUTF8ArrayToUTF16String;
        this.convertByteArrayToUTF16String = convertUTF8ArrayToUTF16String;
        this.convertUTF8StringToUTF16String = convertUTF8StringToUTF16String;
        this.convertUTF16StringToUTF8Array = convertUTF16StringToUTF8Array;
        this.convertUTF16StringToByteArray = convertUTF16StringToUTF8Array;
        this.convertUTF16ArrayToUTF8String = convertUTF16ArrayToUTF8String;
        this.convertUTF16StringToUTF8String = convertUTF16StringToUTF8String;
        this.convertUTF16StringToBase64 = convertUTF16StringToBase64;
        this.convertBase64ToUTF16String = convertBase64ToUTF16String;
        this.fromBase64 = convertBase64ToUTF8String;
        this.toBase64 = convertUTF8StringToBase64;
        this.atob = atob;
        this.btoa = btoa;
        this.utob = convertUTF16StringToUTF8String;
        this.btou = convertUTF8StringToUTF16String;
        this.encode = convertUTF16StringToBase64;
        this.encodeURI = function (u) {
            return convertUTF16StringToBase64(u).replace(/[+\/]/g,
                    function (m0) {
                    return m0 === '+' ? '-' : '_';
                }).replace(/\\=+$/, '');
        };
        this.decode = function (a) {
            return convertBase64ToUTF16String(a.replace(/[\-_]/g,
                function (m0) {
                    return m0 === '-' ? '+' : '/';
                }));
        };
    }
    return Base64;
}());
