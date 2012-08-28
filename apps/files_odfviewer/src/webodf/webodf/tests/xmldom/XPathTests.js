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
/*global runtime: true, core: true, xmldom: true, odf: true, XMLSerializer*/
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");

/**
 * @constructor
 * @param {core.UnitTestRunner} runner
 * @implements {core.UnitTest}
 */
xmldom.XPathTests = function XPathTests(runner) {
    "use strict";
    var r = runner,
        style2CSS = new odf.Style2CSS(),
        t;

    function setupDoc() {
        var stylens = style2CSS.namespaceResolver("style"),
            svgns = style2CSS.namespaceResolver("svg"),
            drawns = style2CSS.namespaceResolver("draw"),
            presentationns = style2CSS.namespaceResolver("presentation"),
            textns = style2CSS.namespaceResolver("text"),
            doc = runtime.getDOMImplementation().createDocument("", "a", null),
            r = doc.documentElement,
            fontFace = doc.createElementNS(stylens, "font-face"),
            fontFaceSrc = doc.createElementNS(svgns, "font-face-src"),
            drawFrame = doc.createElementNS(drawns, "frame"),
            p = doc.createElementNS(textns, "p");
        r.appendChild(p);
        r.appendChild(fontFace);
        fontFace = doc.createElementNS(stylens, "font-face");
        fontFace.appendChild(fontFaceSrc);
        fontFaceSrc.setAttributeNS(textns, "anchor-type", "paragraph");
        r.appendChild(fontFace);
        r.appendChild(drawFrame);
        drawFrame = doc.createElementNS(drawns, "frame");
        drawFrame.setAttributeNS(presentationns, "class", "title");
        r.appendChild(drawFrame);

        t = { doc: doc, fontFace: fontFace, drawFrame: drawFrame };
    }
    function test1() {
        setupDoc();
        var xpath = new xmldom.XPath(),
            xpaths = {
                "style:font-face[svg:font-face-src]": "t.fontFace",
                ".//*[*[@text:anchor-type='paragraph']]": "t.fontFace",
                "./draw:frame[@presentation:class='title']": "t.drawFrame"
            },
            x;
        for (x in xpaths) {
            if (xpaths.hasOwnProperty(x)) {
                t.result = xpath.getODFElementsWithXPath(t.doc.documentElement,
                        x, style2CSS.namespaceResolver);
                r.shouldBe(t, "t.result.length", "1");
                r.shouldBe(t, "t.result[0]", xpaths[x]);
            }
        }
    }
    this.setUp = function () {
        t = {};
    };
    this.tearDown = function () {
        t = {};
    };
    this.tests = function () {
        return [ test1 ];
    };
    this.asyncTests = function () {
        return [
        ];
    };
};
xmldom.XPathTests.prototype.description = function () {
    "use strict";
    return "Test the XPath class.";
};
(function () {
    "use strict";
    return xmldom.XPathTests;
}());
