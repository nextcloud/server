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
/*global core: true, runtime: true*/
runtime.loadClass("core.Base64");
/**
 * @constructor
 * @param runner {UnitTestRunner}
 * @implements {core.UnitTest}
 */
core.Base64Tests = function Base64Tests(runner) {
    "use strict";
    var t, r = runner, base64 = new core.Base64();

    function testConvertByteArrayToBase64() {
        t.encoded = base64.convertByteArrayToBase64([65]);
        r.shouldBe(t, "t.encoded", "'QQ=='");
        t.encoded = base64.convertByteArrayToBase64([65, 65]);
        r.shouldBe(t, "t.encoded", "'QUE='");
        t.encoded = base64.convertByteArrayToBase64([65, 65, 65]);
        r.shouldBe(t, "t.encoded", "'QUFB'");
    }

    function testToBase64() {
        t.encoded = base64.toBase64("A");
        r.shouldBe(t, "t.encoded", "'QQ=='");
        t.encoded = base64.toBase64("AA");
        r.shouldBe(t, "t.encoded", "'QUE='");
        t.encoded = base64.toBase64("AAA");
        r.shouldBe(t, "t.encoded", "'QUFB'");
    }

    function testConvertUTF8StringToUTF16String(callback) {
        var bin = "1234567890";
        while (bin.length < 100000) {
            bin += bin;
        }
        t.numcallbacks = 0;
        base64.convertUTF8StringToUTF16String(bin, function (str, done) {
            t.numcallbacks += 1;
            t.done = done;
            if (t.numcallbacks === 1) {
                r.shouldBe(t, "t.done", "false");
            } else {
                r.shouldBe(t, "t.done", "true");
            }
            if (done) {
                r.shouldBe(t, "t.numcallbacks", "2");
                t.str = str;
                t.bin = bin;
                r.shouldBe(t, "t.str.length", "t.bin.length");
                callback();
            }
            return true;
        });
    }

    this.setUp = function () {
        t = {};
    };
    this.tearDown = function () {
        t = {};
    };
    this.tests = function () {
        return [
            testConvertByteArrayToBase64,
            testToBase64
        ];
    };
    this.asyncTests = function () {
        return [ testConvertUTF8StringToUTF16String ];
    };
    this.description = function () {
        return "Test the Base64 class.";
    };
};
