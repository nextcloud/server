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
/*global runtime: true, core: true, gui: true*/
runtime.loadClass("gui.SelectionMover");

/**
 * @constructor
 * @param {core.UnitTestRunner} runner
 * @implements {core.UnitTest}
 */
gui.SelectionMoverTests = function SelectionMoverTests(runner) {
    "use strict";
    var r = runner,
        t, testarea = runtime.getWindow().document.getElementById("testarea");

    function setupDoc() {
        var doc = testarea.ownerDocument,
            selection = runtime.getWindow().getSelection(),
            p = doc.createElement("p"),
            walker = new core.PointWalker(p),
            mover = new gui.SelectionMover(selection, walker);
        testarea.appendChild(p);
        p.appendChild(doc.createTextNode("MMMMM MMMMM MMMMM MMMMM MMMMM"));
        p.style.width = "5em";// break line after each 'MMMMM'
        selection.removeAllRanges();
        selection.addRange(doc.createRange());
        t = { doc: doc, p: p, selection: selection, mover: mover };
    }
    function testUpDownTraversal() {
        setupDoc();
        r.shouldBe(t, "t.selection.rangeCount", "1");
        t.r = t.selection.getRangeAt(0);
        r.shouldBeNonNull(t, "t.r");
        t.r.setStart(t.p.firstChild, 0);
        r.shouldBe(t, "t.r.startContainer", "t.p.firstChild");
        r.shouldBe(t, "t.r.startOffset", "0");
        t.mover.movePointForward();
        t.r = t.selection.getRangeAt(0);
        r.shouldBe(t, "t.r.startContainer", "t.p.firstChild");
        r.shouldBe(t, "t.r.startOffset", "1");
        t.mover.movePointBackward();
        t.r = t.selection.getRangeAt(0);
        r.shouldBe(t, "t.r.startContainer", "t.p.firstChild");
        r.shouldBe(t, "t.r.startOffset", "0");
        t.mover.moveLineForward();
//        t.selection.modify("move", "forward", "line");
        t.r = t.selection.getRangeAt(0);
        r.shouldBe(t, "t.r.startContainer", "t.p.firstChild");
        r.shouldBe(t, "t.r.startOffset", "6");
    }

    this.setUp = function () {
        t = {};
        while (testarea.firstChild) {
            testarea.removeChild(testarea.firstChild);
        }
    };
    this.tearDown = function () {
        t = {};
        while (testarea.firstChild) {
            testarea.removeChild(testarea.firstChild);
        }
    };
    this.tests = function () {
        return [ testUpDownTraversal ];
    };
    this.asyncTests = function () {
        return [
        ];
    };
};
gui.SelectionMoverTests.prototype.description = function () {
    "use strict";
    return "Test the SelectionMover class.";
};
(function () {
    "use strict";
    return gui.SelectionMoverTests;
}());
