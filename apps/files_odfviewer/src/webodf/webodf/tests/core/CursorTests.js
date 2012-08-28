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
runtime.loadClass("core.Cursor");

/**
 * @constructor
 * @param {core.UnitTestRunner} runner
 * @implements {core.UnitTest}
 */
core.CursorTests = function CursorTests(runner) {
    "use strict";
    var r = runner, tests, t = {},
        maindoc = runtime.getWindow().document,
        testarea = maindoc.getElementById("testarea");
    /**
     * @param {Selection} selection
     * @param {Node} startnode
     * @param {number} startoffset
     * @param {Node=} endnode
     * @param {number=} endoffset
     * @return {undefined}
     */
    function setSelection(selection, startnode, startoffset, endnode,
            endoffset) {
        // call createRange() on the document, even if startnode is the document
        var range = (startnode.ownerDocument || startnode).createRange();
        selection.removeAllRanges();
        range.setStart(startnode, startoffset);
        if (endnode) {
            range.setEnd(endnode, endoffset);
        } else {
            range.setEnd(startnode, startoffset);
        }
        selection.addRange(range);
        if (range.startContainer !== startnode) {
            runtime.log("EVIL");
        }
    }

    function setupEmptyRootNode() {
        var selection = runtime.getWindow().getSelection(),
            root = maindoc.createElementNS("", "p"),
            cursor = new core.Cursor(selection, maindoc);
        testarea.appendChild(root);
        t = { selection: selection, root: root, cursor: cursor };
        runner.shouldBeNonNull(t, "t.selection");
    }

    function setupSimpleTextDoc() {
        setupEmptyRootNode();
        t.textnode = maindoc.createTextNode("abc");
        t.root.appendChild(t.textnode);
    }

    tests = [
        // create a document, add a cursor and check that the cursor is present
        function testOnEmptyNode1() {
            // if the document is the container of the selection, the cursor
            // can not be in the DOM
            setupEmptyRootNode();
            setSelection(t.selection, t.root, 0);
            t.cursor.updateToSelection();
            //r.shouldBeNull(t, "t.cursor.getNode().parentNode");
        },
        function testOnEmptyNode2() {
            setupEmptyRootNode();
            setSelection(t.selection, t.root, 0);
       //     t.selection.focusNode = r.root;
            var range = t.selection.getRangeAt(0);
            t.cursor.updateToSelection();
            r.shouldBeNonNull(t, "t.cursor.getNode().parentNode");
            r.shouldBeNull(t, "t.cursor.getNode().previousSibling");
            r.shouldBeNull(t, "t.cursor.getNode().nextSibling");
        },
        function testOnSimpleText() {
            setupSimpleTextDoc();
            // put the cursor at the start of the text node 
            setSelection(t.selection, t.textnode, 0);
            t.cursor.updateToSelection();
            r.shouldBeNonNull(t, "t.cursor.getNode().parentNode");
            r.shouldBeNull(t, "t.cursor.getNode().previousSibling");
            r.shouldBe(t, "t.cursor.getNode().nextSibling.nodeValue", "'abc'");
        },
        function testOnSimpleText2() {
            setupSimpleTextDoc();
            // put the cursor in the middle of the text node 
            setSelection(t.selection, t.textnode, 1);
            t.cursor.updateToSelection();
            r.shouldBeNonNull(t, "t.cursor.getNode().parentNode");
            r.shouldBe(t, "t.cursor.getNode().previousSibling.nodeValue", "'a'");
            r.shouldBe(t, "t.cursor.getNode().nextSibling.nodeValue", "'bc'");
        },
        function testOnSimpleText3() {
            setupSimpleTextDoc();
            // put the cursor at the end of the text node
            setSelection(t.selection, t.textnode, 3);
            t.cursor.updateToSelection();
            r.shouldBeNonNull("t.cursor.getNode().parentNode");
            r.shouldBe(t, "t.cursor.getNode().previousSibling.nodeValue", "'abc'");
            r.shouldBeNull(t, "t.cursor.getNode().nextSibling");
        },
        function testOnSimpleText4() {
            var textnode2;
            setupSimpleTextDoc();
            // put the cursor between 'a' and 'b', then change the selection to
            // be between 'b' and 'c' and update the cursor
            setSelection(t.selection, t.textnode, 1);
            t.cursor.updateToSelection();
            textnode2 = t.cursor.getNode().nextSibling;
            setSelection(t.selection, textnode2, 1);
            t.cursor.updateToSelection();
            r.shouldBeNonNull(t, "t.cursor.getNode().parentNode");
            r.shouldBe(t, "t.cursor.getNode().previousSibling.nodeValue", "'ab'");
            r.shouldBe(t, "t.cursor.getNode().nextSibling.nodeValue", "'c'");
        },
        function testOnSimpleText5() {
            var textnode2;
            setupSimpleTextDoc();
            // put the cursor between 'a' and 'b', then change the selection to
            // span the entire text and update the cursor
            setSelection(t.selection, t.textnode, 1);
            t.cursor.updateToSelection();
            textnode2 = t.cursor.getNode().nextSibling;
            setSelection(t.selection, t.textnode, 0, textnode2, 2);
            t.cursor.updateToSelection();
            r.shouldBe(t, "t.selection.rangeCount", "1");
// only null if working on a separate document
//            r.shouldBeNull(t, "t.cursor.getNode().parentNode");
            t.range = t.selection.getRangeAt(0);
            r.shouldBe(t, "t.range.startContainer", "t.textnode");
            r.shouldBe(t, "t.range.startOffset", "0");
            r.shouldBe(t, "t.range.endContainer", "t.textnode");
            r.shouldBe(t, "t.range.endOffset", "3");
        },
        function testOnSimpleText5b() {
            var textnode2;
            setupSimpleTextDoc();
            setSelection(t.selection, t.textnode, 1);
            t.cursor.updateToSelection();
            textnode2 = t.cursor.getNode().nextSibling;
            setSelection(t.selection, t.textnode.parentNode, 1, textnode2, 2);
            t.cursor.updateToSelection();
            r.shouldBe(t, "t.selection.rangeCount", "1");
// only null if working on a separate document
//            r.shouldBeNull(t, "t.cursor.getNode().parentNode");
            t.range = t.selection.getRangeAt(0);
            r.shouldBe(t, "t.range.startContainer", "t.textnode");
            r.shouldBe(t, "t.range.startOffset", "1");
            r.shouldBe(t, "t.range.endContainer", "t.textnode");
            r.shouldBe(t, "t.range.endOffset", "3");
        },
        function testOnSimpleText6() {
            var somenode, textnode2;
            setupSimpleTextDoc();
            // add a child node to the cursor
            somenode = maindoc.createElement("p");
            t.cursor.getNode().appendChild(somenode);
            // select a single position so the cursor is put in the document
            setSelection(t.selection, t.textnode, 1);
            t.cursor.updateToSelection();
            r.shouldBeNonNull(t, "t.cursor.getNode().parentNode");
            textnode2 = t.cursor.getNode().nextSibling;
            // select a range starting at the node in the cursor, but extends
            // out of the the cursor
            // this should have the result that the cursor is removed from the
            // document and that the text nodes around the cursor are
            // merged
            setSelection(t.selection, somenode, 0, textnode2, 2);
            t.cursor.updateToSelection();
// only null if working on a separate document
//            r.shouldBeNull(t, "t.cursor.getNode().parentNode");
            t.range = t.selection.getRangeAt(0);
            r.shouldBe(t, "t.range.startContainer", "t.textnode");
            r.shouldBe(t, "t.range.startOffset", "1");
            r.shouldBe(t, "t.range.endContainer", "t.textnode");
            r.shouldBe(t, "t.range.endOffset", "3");
            r.shouldBe(t, "t.range.collapsed", "false");
        }
    ];
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
        return tests;
    };
    this.asyncTests = function () {
        return [];
    };
};
core.CursorTests.name = "CursorTests";
core.CursorTests.prototype.description = function () {
    "use strict";
    return "Test the Cursor class.";
};
(function () {
    "use strict";
    return core.CursorTests;
}());
