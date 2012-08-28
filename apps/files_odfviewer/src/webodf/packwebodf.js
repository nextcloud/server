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
/*global runtime: true, core: true*/
runtime.loadClass("core.Zip");
runtime.loadClass("core.Base64");

function addFiles(zip, pos, inputfiles, zipfiles, callback) {
    "use strict";
    if (inputfiles.length !== zipfiles.length) {
        return callback(
                "Arrays inputfiles and zipfiles should have the same length.");
    }
    if (pos >= inputfiles.length) {
        zip.write(function (err) {
            return callback(err);
        });
        return;
    }
    var inputfile = inputfiles[pos],
        zipmemberpath = zipfiles[pos];
    runtime.readFile(inputfile, "binary", function (err, data) {
        var base64;
        if (err) {
            return callback(err);
        }
        zip.save(zipmemberpath, data, false, new Date());
        addFiles(zip, pos + 1, inputfiles, zipfiles, callback);
    });
}

function usage() {
    "use strict";
    runtime.log("Usage:");
}

/**
 * This script takes 1+2n arguments
 * First argument is the name of the target zip file.
 * The next n arguments are the input files. The last n arguments are the
 * names of the files in the zip file.
 */
if (arguments.length % 2 !== 0) {
    runtime.log("Wrong number of arguments.");
    usage();
    runtime.exit(1);
}
var args = arguments,
    n = (args.length - 2) / 2,
    zipfilename = args[1],
    inputmembers = [],
    zipmembers = [],
    i,
    zip = new core.Zip(zipfilename, null);
for (i = 0; i < n; i += 1) {
    inputmembers[i] = args[2 + i];
    zipmembers[i] = args[2 + n + i];
}
addFiles(zip, 0, inputmembers, zipmembers, function (err) {
    "use strict";
    if (err) {
        runtime.log(err);
    }
});
