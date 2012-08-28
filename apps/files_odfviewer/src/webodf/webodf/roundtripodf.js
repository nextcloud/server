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
/*global runtime: true, core: true, odf: true*/
runtime.loadClass("odf.OdfContainer");

/**
 * If the state of the OdfContainer is invalid, exit with an error message.
 */
function exitOnInvalid(odffilepath, odfcontainer) {
    "use strict";
    if (odfcontainer.state === odf.OdfContainer.INVALID) {
        runtime.log("Document " + odffilepath + " is invalid.");
        runtime.exit(1);
    }
    if (odfcontainer.state === odf.OdfContainer.DONE) {
        odfcontainer.save(function (err) {
            if (err) {
                runtime.log(err);
                runtime.exit(1);
            }
        });
    }
}

/**
 * Load an ODF document. Report an error if there is a problem.
 */
function loadODF(odffilepath) {
    "use strict";
    var odfcontainer = new odf.OdfContainer(odffilepath);
    odfcontainer.onstatereadychange = function () {
        exitOnInvalid(odffilepath, odfcontainer);
    };
    exitOnInvalid(odffilepath, odfcontainer);
}

// loop over arguments to load ODF
var i;
for (i = 1; i < arguments.length; i += 1) {
    loadODF(arguments[i]);
}
