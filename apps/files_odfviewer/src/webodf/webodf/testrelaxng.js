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
/*global runtime: true, xmldom: true*/
runtime.loadClass("xmldom.RelaxNG");
runtime.loadClass("xmldom.RelaxNG2");

function validate(relaxng, relaxng2, url) {
    "use strict";
    runtime.loadXML(url, function (err, dom) {
        var walker;
        if (err) {
            runtime.log("Could not read " + url + ": " + err);
        } else {
            walker = dom.createTreeWalker(dom.firstChild, 0xFFFFFFFF);
            relaxng.validate(walker, function (err) {
                if (err) {
                    var i;
                    runtime.log("Found " + String(err.length) +
                            " error validating " + url + ":");
                    for (i = 0; i < err.length; i += 1) {
                        runtime.log(err[i].message());
                    }
                }
            });
            relaxng2.validate(walker, function (err) {
                if (err) {
                    var i;
                    runtime.log("Found " + String(err.length) +
                            " error validating " + url + ":");
                    for (i = 0; i < err.length; i += 1) {
                        runtime.log(err[i].message());
                    }
                }
            });
        }
    });
}

var args = arguments,
    relaxngurl = args[1];

// load and parse the Relax NG
runtime.loadXML(relaxngurl, function (err, dom) {
    "use strict";
    var parser, i, relaxng, relaxng2;
    if (err) {
        return;
    }
    parser = new xmldom.RelaxNGParser();
    relaxng = new xmldom.RelaxNG();
    relaxng2 = new xmldom.RelaxNG2();
    err = parser.parseRelaxNGDOM(dom, relaxng.makePattern);
    relaxng.init(parser.rootPattern);
    relaxng2.init(parser.start, parser.nsmap);

    // loop over arguments to load ODF
    for (i = 2; i < args.length; i += 1) {
        runtime.log("Validating " + args[i] + " from " + relaxngurl);
        validate(relaxng, relaxng2, args[i]);
    }
});
