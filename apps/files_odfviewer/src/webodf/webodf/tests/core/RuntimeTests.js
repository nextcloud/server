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
/*jslint bitwise: true*/

/**
 * @constructor
 * @param runner {UnitTestRunner}
 * @implements {core.UnitTest}
 */
core.RuntimeTests = function RuntimeTests(runner) {
    "use strict";
    var t, r = runner;

    function testRead(callback) {
        runtime.read("tests.js", 2, 6, function (err, data) {
            t.err = err;
            r.shouldBeNull(t, "t.err");
            t.data = runtime.byteArrayToString(data, "utf8");
            r.shouldBe(t, "t.data", "'global'");
            callback();
        });
    }

    /**
     * Test writing a binary file and reading it back.
     */
    function testWrite(callback) {
        var content = new core.ByteArrayWriter("utf8"),
            i, max = 1024, filename, clean;
        for (i = 0; i < max; i += 1) {
            content.appendArray([i]);
        }
        content = content.getByteArray();
        filename = "tmp" + Math.random();
        clean = new core.ByteArrayWriter("utf8");
        for (i = 0; i < max; i += 1) {
            clean.appendArray([content[i] & 0xff]);
        }
        clean = clean.getByteArray();
        // now content has content different from what is on the server
        runtime.writeFile(filename, content, function (err) {
            t.err = err;
            r.shouldBeNull(t, "t.err");
            runtime.readFile(filename, "binary", function (err, data) {
                t.err = err;
                r.shouldBeNull(t, "t.err");
                t.data = data;
                t.clean = clean;
                r.shouldBe(t, "t.data.length", "t.clean.length");
                i = 0;
                while (i < max && data[i] === clean[i]) {
                    i += 1;
                }
                if (i !== max) {
                    runtime.log("at " + String(i) + " " + data[i] + " vs " +
                            clean[i]);
                }
                t.i = i;
                t.max = max;
                r.shouldBe(t, "t.i", "t.max");
                // cleanup
                runtime.deleteFile(filename, function (err) {
                    callback();
                });
            });
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
        ];
    };
    this.asyncTests = function () {
        return [
            testRead,
            testWrite
        ];
    };
    this.description = function () {
        return "Test the runtime.";
    };
};
