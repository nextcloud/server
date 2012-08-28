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
/*global document: true, core: true, gui: true, runtime: true*/
runtime.loadClass("core.PointWalker");
runtime.loadClass("gui.XMLEdit");

/**
 * @constructor
 * @param runner {core.UnitTestRunner}
 * @implements {core.UnitTest}
 */
gui.XMLEditTests = function XMLEditTests(runner) {
    "use strict";
    var t, r = runner, tests;

    function checkWalker(node, count, endpos) {
        t = {};
        t.node = node;
        t.walker = new core.PointWalker(node);
        t.count = count;
        t.countForward = 0;
        t.countBackward = 0;
        t.endpos = endpos;
        t.walker.setPoint(t.node, 0);
        while (t.walker.stepForward()) {
            t.countForward += 1;
        }
        r.shouldBe("t.countForward", "t.count");
        r.shouldBe("t.walker.precedingSibling()", "t.node.lastChild");
        r.shouldBe("t.walker.followingSibling()", "null");
        if (endpos !== null) {
            r.shouldBe("t.walker.position()", "t.endpos");
        }
        t.walker.setPoint(t.node, endpos);
        while (t.walker.stepBackward()) {
            t.countBackward += 1;
        }
        r.shouldBe("t.countBackward", "t.count");
        r.shouldBe("t.walker.precedingSibling()", "null");
        r.shouldBe("t.walker.followingSibling()", "t.node.firstChild");
        r.shouldBe("t.walker.position()", "0");
    }

    function testSimpleDocument(xmledit) {
        var maindoc = xmledit.ownerDocument,
            doc = maindoc.implementation.createDocument(null, "p", null),
            p = doc.firstChild,
            textnode1,
            textnode2,
            textnode3,
            em;

        xmledit.setXML(doc);

        checkWalker(doc, 2, 1);
        checkWalker(p, 0, 0);

        t = {};
        t.doc = doc;
        t.walker = new core.PointWalker(t.doc);
        r.shouldBe("t.walker.position()", "0");
        r.shouldBe("t.walker.stepForward()", "true");
        r.shouldBe("t.walker.position()", "0");
        r.shouldBe("t.walker.stepForward()", "true");
        r.shouldBe("t.walker.position()", "1");
        r.shouldBe("t.walker.stepForward()", "false");
        r.shouldBe("t.walker.position()", "1");
        r.shouldBe("t.walker.stepBackward()", "true");
        r.shouldBe("t.walker.position()", "0");
        r.shouldBe("t.walker.stepBackward()", "true");
        r.shouldBe("t.walker.position()", "0");
        r.shouldBe("t.walker.stepBackward()", "false");
        r.shouldBe("t.walker.position()", "0");

        textnode1 = doc.createTextNode("hello, ");
        textnode2 = doc.createTextNode("big ");
        textnode3 = doc.createTextNode("world.");
        em = doc.createElement('em');
        p.appendChild(textnode1);
        p.appendChild(em);
        em.appendChild(textnode2);
        p.appendChild(textnode3);

        checkWalker(textnode1, 7, 7);
        checkWalker(textnode2, 4, 4);
        checkWalker(textnode3, 6, 6);
        checkWalker(em, 6, 1);
        checkWalker(p, 25, 3);
        checkWalker(doc, 27, 1);
    }

    function testXmlEdit(document) {
        var head = document.getElementsByTagName("head")[0],
            css = document.createElement("style"),
            testarea = document.createElement("div"),
            xmledit;

        // the xml edit requires an element to put the content and a sheet to put
        // the style
        css.type = "text/css";
        head.appendChild(css);
        document.body.appendChild(testarea);
        xmledit = new gui.XMLEdit(testarea, css);

        testSimpleDocument(xmledit);

        css.parentNode.removeChild(css);
        testarea.parentNode.removeChild(testarea);
    }

    this.setUp = function () {
        t = {};
    };
    this.tearDown = function () {
        t = {};
    };
    this.tests = function () {
        return [ testSimpleDocument ];
    };
    this.asyncTests = function () {
        return [];
    };
    this.description = function () {
        return "Test the XML editor class.";
    };
};
