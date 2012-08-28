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
/*global runtime: true, Runtime: true, core: true*/
/*jslint evil: true*/
/**
 * @interface
 */
core.UnitTest = function UnitTest() {"use strict";};
/**
 * @return {undefined}
 */
core.UnitTest.prototype.setUp = function () {"use strict";};
/**
 * @return {undefined}
 */
core.UnitTest.prototype.tearDown = function () {"use strict";};
/**
 * @return {!string}
 */
core.UnitTest.prototype.description = function () {"use strict";};
/**
 * @return {Array.<!function():undefined>}
 */
core.UnitTest.prototype.tests = function () {"use strict";};
/**
 * @return {Array.<!function(!function():undefined):undefined>}
 */
core.UnitTest.prototype.asyncTests = function () {"use strict";};

/**
 * @constructor
 */
core.UnitTestRunner = function UnitTestRunner() {
    "use strict";
    var failedTests = 0;
    function debug(msg) {
        runtime.log(msg);
    }
    function testFailed(msg) {
        failedTests += 1;
        runtime.log("fail", msg);
    }
    function testPassed(msg) {
        runtime.log("pass", msg);
    }
    function areArraysEqual(a, b) {
        var i;
        try {
            if (a.length !== b.length) {
                return false;
            }
            for (i = 0; i < a.length; i += 1) {
                if (a[i] !== b[i]) {
                    return false;
                }
            }
        } catch (ex) {
            return false;
        }
        return true;
    }
    function isResultCorrect(actual, expected) {
        if (expected === 0) {
            return actual === expected && (1 / actual) === (1 / expected);
        }
        if (actual === expected) {
            return true;
        }
        if (typeof expected === "number" && isNaN(expected)) {
            return typeof actual === "number" && isNaN(actual);
        }
        if (Object.prototype.toString.call(expected) ===
                Object.prototype.toString.call([])) {
            return areArraysEqual(actual, expected);
        }
        return false;
    }
    function stringify(v) {
        if (v === 0 && 1 / v < 0) {
            return "-0";
        }
        return String(v);
    }
    /**
     * @param {!Object} t
     * @param {!string} a
     * @param {!string} b
     * @return {undefined}
     */
    function shouldBe(t, a, b) {
        if (typeof a !== "string" || typeof b !== "string") {
            debug("WARN: shouldBe() expects string arguments");
        }
        var exception, av, bv;
        try {
            av = eval(a);
        } catch (e) {
            exception = e;
        }
        bv = eval(b);

        if (exception) {
            testFailed(a + " should be " + bv + ". Threw exception " +
                    exception);
        } else if (isResultCorrect(av, bv)) {
            testPassed(a + " is " + b);
        } else if (typeof av === typeof bv) {
            testFailed(a + " should be " + bv + ". Was " + stringify(av) + ".");
        } else {
            testFailed(a + " should be " + bv + " (of type " + typeof bv +
                    "). Was " + av + " (of type " + typeof av + ").");
        }
    }
    /**
     * @param {!Object} t context in which values to be tested are placed
     * @param {!string} a the value to be checked
     * @return {undefined}
     */
    function shouldBeNonNull(t, a) {
        var exception, av;
        try {
            av = eval(a);
        } catch (e) {
            exception = e;
        }

        if (exception) {
            testFailed(a + " should be non-null. Threw exception " + exception);
        } else if (av !== null) {
            testPassed(a + " is non-null.");
        } else {
            testFailed(a + " should be non-null. Was " + av);
        }
    }
    /**
     * @param {!Object} t context in which values to be tested are placed
     * @param {!string} a the value to be checked
     * @return {undefined}
     */
    function shouldBeNull(t, a) {
        shouldBe(t, a, "null");
    }
    this.shouldBeNull = shouldBeNull;
    this.shouldBeNonNull = shouldBeNonNull;
    this.shouldBe = shouldBe;
    this.countFailedTests = function () {
        return failedTests;
    };
};

/**
 * @constructor
 */
core.UnitTester = function UnitTester() {
    "use strict";
    var failedTests = 0,
        results = {};
    /**
     * @param {Function} TestClass the constructor for the test class
     * @param {!function():undefined} callback
     * @return {undefined}
     */
    this.runTests = function (TestClass, callback) {
        var testName = Runtime.getFunctionName(TestClass),
            tname,
            runner = new core.UnitTestRunner(),
            test = new TestClass(runner),
            testResults = {},
            i,
            t,
            tests,
            lastFailCount;

        // check that this test has not been run or started yet
        if (testName.hasOwnProperty(results)) {
            runtime.log("Test " + testName + " has already run.");
            return;
        }

        runtime.log("Running " + testName + ": " + test.description());
        tests = test.tests();
        for (i = 0; i < tests.length; i += 1) {
            t = tests[i];
            tname = Runtime.getFunctionName(t);
            runtime.log("Running " + tname);
            lastFailCount = runner.countFailedTests();
            test.setUp();
            t();
            test.tearDown();
            testResults[tname] = lastFailCount === runner.countFailedTests();
        }
        function runAsyncTests(todo) {
            if (todo.length === 0) {
                results[testName] = testResults;
                failedTests += runner.countFailedTests();
                callback();
                return;
            }
            t = todo[0];
            var tname = Runtime.getFunctionName(t);
            runtime.log("Running " + tname);
            lastFailCount = runner.countFailedTests();
            test.setUp();
            t(function () {
                test.tearDown();
                testResults[tname] = lastFailCount ===
                    runner.countFailedTests();
                runAsyncTests(todo.slice(1));
            });
        }
        runAsyncTests(test.asyncTests());
    };
    /**
     * @return {!number}
     **/
    this.countFailedTests = function () {
        return failedTests;
    };
    /**
     * @return {!Object}
     **/
    this.results = function () {
        return results;
    };
};
