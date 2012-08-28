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
runtime.loadClass("core.Zip");

/**
 * @constructor
 * @param {core.UnitTestRunner} runner
 * @implements {core.UnitTest}
 */
core.ZipTests = function ZipTests(runner) {
    "use strict";
    var r = runner,
        t;

    function testNonExistingFile(callback) {
        var zip = new core.Zip("whatever", function (err) {
            t.err = err;
            r.shouldBeNonNull(t, "t.err");
            callback();
        });
    }

    function testNonZipFile(callback) {
        var path = "core/ZipTests.js";
        // check that file exists
        runtime.isFile(path, function (exists) {
            t.exists = exists;
            r.shouldBe(t, "t.exists", "true");
            // check that zip file opening returns an error
            var zip = new core.Zip("core/ZipTests.js", function (err) {
                t.err = err;
                r.shouldBeNonNull(t, "t.err");
                callback();
            });
        });
    }

    function testHi(path, callback) {
        var zip = new core.Zip(path, function (err, zip) {
            t.err = err;
            t.zip = zip;
            r.shouldBeNull(t, "t.err");
            zip.load("hello", function (err, data) {
                t.err = err;
                r.shouldBeNull(t, "t.err");
                t.data = runtime.byteArrayToString(data, "utf8");
                r.shouldBe(t, "t.data.length", "16");
                r.shouldBe(t, "t.data", "'bonjour\\nbonjour\\n'");
                callback();
            });
        });
    }

    function testHiUncompressed(callback) {
        testHi("core/hi-uncompressed.zip", callback);
    }

    function testHiCompressed(callback) {
        testHi("core/hi-compressed.zip", callback);
    }

    function testCreateZip(callback) {
        var filename = "writetest.zip",
            zip = new core.Zip(filename, null),
            data = runtime.byteArrayFromString(
                    "application/vnd.oasis.opendocument.text", "utf8");
        zip.save("mimetype", data, false, new Date());
        zip.load("mimetype", function (err, newdata) {
            t.err = err;
            r.shouldBeNull(t, "t.err");
            t.data = data;
            t.newdata = newdata;
            r.shouldBe(t, "t.data", "t.newdata");
            zip.write(function (err) {
                t.err = err;
                r.shouldBeNull(t, "t.err");
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
        return [];
    };
    this.asyncTests = function () {
        return [
            testNonExistingFile,
            testNonZipFile,
            testHiUncompressed,
            testHiCompressed,
            testCreateZip
        ];
    };
};
core.ZipTests.prototype.description = function () {
    "use strict";
    return "Test the Zip class.";
};
(function () {
    "use strict";
    return core.ZipTests;
}());
