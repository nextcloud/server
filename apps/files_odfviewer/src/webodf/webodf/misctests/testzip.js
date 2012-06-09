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
/*global runtime core*/
runtime.loadClass("core.Zip");
runtime.loadClass("core.Async");

var async = new core.Async();

/**
 * @param {!core.Zip.ZipEntry} entry
 * @param {!core.Zip} zip
 * @param {function(?string):undefined} callback
 * @return {undefined}
 */
function copyEntry(entry, zip, callback) {
    entry.load(function (err, data) {
        if (err) {
            callback(err);
        } else {
            zip.save(entry.filename, data, false, entry.date);
            callback(null);
        }
    });
}

/**
 * @param {!core.Zip} zipa
 * @param {!core.Zip} zipb
 * @param {function(?string):undefined} callback
 * @return {undefined}
 */
function compareZips(zipa, zipb, callback) {
    var entriesa = zipa.getEntries(),
        l = entriesa.length,
        entriesb = zipb.getEntries(),
        i,
        j,
        entrya,
        entryb;
    // compare the number of entries
    if (entriesb.length !== l) {
        callback("Number of entries is not equal.");
        return;
    }
    // compare the meta data of the entries
    for (i = 0; i < l; i += 1) {
        entrya = entriesa[i];
        for (j = 0; j < l; j += 1) {
            entryb = entriesb[j];
            if (entrya.filename === entryb.filename) {
                break;
            }
        }
        if (j === l) {
            callback("Entry " + entrya.filename + " is not present in the " +
                    "second zip file.");
            return;
        }
        if (entrya.date.valueOf() !== entryb.date.valueOf()) {
            callback("Dates for entry " + entrya.filename + " is not equal: " +
                entrya.date + " vs " + entryb.date);
            return;
        }
    }
    // compare the data in the entries
    async.forEach(entriesa, function (entry, callback) {
        entry.load(function (err, dataa) {
            if (err) {
                callback(err);
                return;
            }
            zipb.load(entry.filename, function (err, datab) {
                if (err) {
                    callback(err);
                    return;
                }
                var i = 0, l = dataa.length;
                if (dataa !== datab) {
                    for (i = 0; i < l && dataa[i] === datab[i];) {
                        i += 1;
                    }
                    callback("Data is not equal for " + entry.filename +
                            " at position " + i + ": " + dataa.charCodeAt(i) +
                            " vs " + datab.charCodeAt(i) + ".");
                } else {
                    callback(null);
                }
            });
        });
    }, function (err) {
        callback(err);
    });
}

function testZip(filepatha, callback) {
    var zipa = new core.Zip(filepatha, function (err, zipa) {
        if (err) {
            runtime.log(err);
            runtime.exit(1);
            return;
        }
        // open a new zip file and copy all entries from zipa to zipb
        var filepathb = "tmp323.zip",
            zipb = new core.Zip(filepathb, null),
            entries = zipa.getEntries(),
            i,
            entriesDone = 0;
        async.forEach(entries, function (entry, callback) {
            copyEntry(entry, zipb, callback);
        }, function (err) {
            if (err) {
                callback(err);
                return;
            }
            zipb.write(function (err) {
                if (err) {
                    callback(err);
                    return;
                }
                zipb = new core.Zip(filepathb, function (err, zipb) {
                    if (err) {
                        callback(err);
                        return;
                    }
                    compareZips(zipa, zipb, callback);
                });
            });
        });
    });
}

var args = arguments;
// open the arguments one by one, save them to a file, then open again and see
// if the contents matches
function doit(i) {
    if (i >= args.length) {
        return;
    }
    testZip(args[i], function (err) {
        runtime.log(args[i]);
        if (err) {
            runtime.log(err);
            return;
        }
        i += 1;
        if (i < args.length) {
            doit(i);
        }
    });
}
doit(1);
