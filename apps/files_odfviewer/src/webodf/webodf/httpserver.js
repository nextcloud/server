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
/*global require: true, console: true, process: true, Buffer: true,
   unescape: true */
/* A Node.JS http server*/
var http = require("http"),
    url = require("url"),
    path = require("path"),
    fs = require("fs"),
    lookForIndexHtml = true,
    ipaddress = "127.0.0.1",
    //ipaddress = "192.168.1.105",
    port = 8124;

function statFile(dir, filelist, position, callback) {
    "use strict";
    if (position >= filelist.length) {
        return callback(null, filelist);
    }
    fs.stat(dir + "/" + filelist[position], function (err, stats) {
        if (stats && stats.isDirectory()) {
            filelist[position] = filelist[position] + "/";
        }
        statFile(dir, filelist, position + 1, callback);
    });
}

function listFiles(dir, callback) {
    "use strict";
    fs.readdir(dir, function (err, files) {
        if (err) {
            return callback(err);
        }
        statFile(dir, files, 0, callback);
    });
}

http.createServer(function (request, response) {
    "use strict";
    var uri = unescape(url.parse(request.url).pathname),
        filename = path.join(process.cwd(), uri);
    if (uri !== '/favicon.ico') {
        console.log(request.method + " " + uri);
    }
    function put() {
        var contentlength = parseInt(request.headers["content-length"], 10),
            alldata = new Buffer(contentlength),
            sum = 0;
        request.on("data", function (data) {
            data.copy(alldata, sum, 0);
            sum += data.length;
        });
        request.on("end", function () {
            fs.writeFile(filename, alldata, "binary", function (err) {
                if (err) {
                    response.writeHead(500);
                    response.write(err);
                } else {
                    response.writeHead(200);
                }
                response.end();
            });
        });
    }
    if (request.method === "PUT") {
        put(request, response);
        return;
    }
    if (request.method === "DELETE") {
        fs.unlink(filename, function (err) {
            if (err) {
                response.writeHead(500);
            } else {
                response.writeHead(200);
            }
            response.end();
        });
        return;
    }
    function handleStat(err, stats, lookForIndexHtml) {
        if (!err && stats.isFile()) {
            fs.readFile(filename, "binary", function (err, file) {
                if (err) {
                    response.writeHead(500, {"Content-Type": "text/plain"});
                    if (request.method !== "HEAD") {
                        response.write(err + "\n");
                    }
                    response.end();
                    return;
                }
                var head = {"Content-Length": stats.size};
                if (filename.substr(-3) === ".js") {
                    head["Content-Type"] = "text/javascript";
                } else if (filename.substr(-4) === ".css") {
                    head["Content-Type"] = "text/css";
                } else if (filename.substr(-4) === ".odt" ||
                        filename.substr(-5) === ".fodt") {
                    head["Content-Type"] = "application/vnd.oasis.opendocument.text";
                } else if (filename.substr(-4) === ".ods" ||
                        filename.substr(-5) === ".fods") {
                    head["Content-Type"] = "application/vnd.oasis.opendocument.presentation";
                } else if (filename.substr(-4) === ".odp" ||
                        filename.substr(-5) === ".fodp") {
                    head["Content-Type"] = "application/vnd.oasis.opendocument.spreadsheet";
                }
                response.writeHead(200, head);
                if (request.method !== "HEAD") {
                    response.write(file, "binary");
                }
                response.end();
            });
        } else if (!err && stats.isDirectory()) {
            if (lookForIndexHtml) {
                fs.stat(filename + "/index.html", function (err, stats) {
                    if (err) {
                        fs.stat(filename, handleStat);
                    } else {
                        filename = filename + "/index.html";
                        handleStat(err, stats);
                    }
                });
                return;
            }
            if (uri.length === 0 || uri[uri.length - 1] !== "/") {
                response.writeHead(301, {"Content-Type": "text/plain",
                        "Location": uri + "/"});
                if (request.method !== "HEAD") {
                    response.write("Moved permanently\n");
                }
                response.end();
                return;
            }
            listFiles(filename, function (err, files) {
                if (err) {
                    response.writeHead(500, {"Content-Type": "text/plain"});
                    if (request.method !== "HEAD") {
                        response.write(err + "\n");
                    }
                    response.end();
                    return;
                }
                response.writeHead(200);
                if (request.method !== "HEAD") {
                    files.sort();
                    response.write("<html><head><title></title></head><body>");
                    response.write("<table>");
                    var i, l = files.length, file;
                    for (i = 0; i < l; i += 1) {
                        file = files[i];
                        if (file.length > 0 && file[file.length - 1] === '/') {
                            file = encodeURIComponent(file.slice(0, file.length - 1)) + "/";
                        } else {
                            file = encodeURIComponent(file);
                        }
                        response.write("<tr><td><a href=\"");
                        response.write(file);
                        response.write("\">");
                        file = files[i].replace("&", "&amp;")
                                .replace("<", "&gt;");
                        response.write(file.replace("\"", "\\\""));
                        response.write("</a></td></tr>\n");
                    }
                    response.write("</table></body></html>\n");
                }
                response.end();
            });
        } else {
            if (uri !== '/favicon.ico') {
                console.log("Not found: " + uri);
            }
            response.writeHead(404, {"Content-Type": "text/plain"});
            if (request.method !== "HEAD") {
                response.write("404 Not Found\n");
            }
            response.end();
        }
    }
    fs.stat(filename, function (err, stats) {
        handleStat(err, stats, lookForIndexHtml);
    });
}).listen(port, ipaddress);

console.log('Server running at ' + ipaddress + ':' + port + '/');
