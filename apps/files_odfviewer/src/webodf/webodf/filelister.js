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
/*global XMLHttpRequest*/
/*jslint regexp: true*/
/** asynchroneous function that lists all files **/
function listFiles(startdir, filepattern, fileCallback, doneCallback) {
    "use strict";

    var todoList = [],
        doneList = [],
        dirpattern = /\/$/,
        hasWEBDAV = false;

    function getHref(responseElement) {
        var n = responseElement.firstChild;
        while (n && !(n.namespaceURI === 'DAV:' && n.localName === 'href')) {
            n = n.nextSibling;
        }
        return n && n.firstChild && n.firstChild.nodeValue;
    }

    function isDirectory(responseElement) {
        var n = responseElement.firstChild;
        while (n &&
                !(n.namespaceURI === 'DAV:' && n.localName === 'propstat')) {
            n = n.nextSibling;
        }
        n = n && n.firstChild;
        while (n &&
                !(n.namespaceURI === 'DAV:' && n.localName === 'prop')) {
            n = n.nextSibling;
        }
        n = n && n.firstChild;
        while (n && !(n.namespaceURI === 'DAV:' &&
                      n.localName === 'resourcetype')) {
            n = n.nextSibling;
        }
        n = n && n.firstChild;
        while (n &&
                !(n.namespaceURI === 'DAV:' && n.localName === 'collection')) {
            n = n.nextSibling;
        }
        return n;
    }

    function processWebDavResponse(xml) {
        if (!xml) {
            throw new Error('No proper XML response.');
        }
        
        var refs = xml.getElementsByTagNameNS('DAV:', 'response'),
            directories = [],
            files = [],
            i,
            d,
            href;
        if (refs.length === 0) {
            throw new Error('No proper XML response.');
        }
        for (i = 0; i < refs.length; i += 1) {
            href = getHref(refs[i]);
            if (isDirectory(refs[i])) {
                directories.push(href);
            } else if (filepattern.test(href)) {
                files.push(href);
            }
        }
        for (i = 0; i < directories.length; i += 1) {
            d = directories[i];
            if (doneList.indexOf(d) === -1 && todoList.indexOf(d) === -1) {
                todoList.push(d);
            }
        }
        fileCallback(directories, files);
    }

    function processIndexHtmlResponse(base, text) {
        // use regex because index.html is usually not valid xml
        var re = /href="([^\/\?"][^"]*)"/ig,
            matches,
            files = [],
            directories = [],
            name,
            d,
            i;
        while ((matches = re.exec(text)) !== null) {
            name = matches[1];
            if (dirpattern.test(name)) {
                directories.push(base + name);
            } else if (filepattern.test(name)) {
                files.push(base + name);
            }
        }
        for (i = 0; i < directories.length; i += 1) {
            d = directories[i];
            if (doneList.indexOf(d) === -1 && todoList.indexOf(d) === -1) {
                todoList.push(d);
            }
        }
        fileCallback(directories, files);
    }

    function getNextFileListWithIndexHtml() {
        var url = todoList.shift(),
            req;
        while (url && typeof url !== 'string') {
            url = todoList.shift();
        }
        if (!url) {
            if (doneCallback) {
                doneCallback();
            }
            return;
        }
        req = new XMLHttpRequest();
        req.open('GET', url, true);
        req.onreadystatechange = function (evt) {
            if (req.readyState !== 4) {
                return;
            }
            if (req.status >= 200 && req.status < 300) {
                processIndexHtmlResponse(url, req.responseText);
            }
            getNextFileListWithIndexHtml();
        };
        req.send(null);

        doneList.push(url);
    }

    function getNextFileListWithWebDav() {
        var url = todoList.shift(),
            req;
        if (!url) {
            if (doneCallback) {
                doneCallback();
            }
            return;
        }

        req = new XMLHttpRequest();
        req.open('PROPFIND', url, true);
        req.onreadystatechange = function (evt) {
            if (req.readyState !== 4) {
                return;
            }
            if (req.status >= 200 && req.status < 300) {
                try {
                    processWebDavResponse(req.responseXML);
                    hasWEBDAV = true;
                } catch (e) {
                }
            }
            if (hasWEBDAV) {
                getNextFileListWithWebDav();
            } else {
                todoList.push(url);
                doneList = [];
                getNextFileListWithIndexHtml();
            }
        };
        req.setRequestHeader('Depth', '1');
        req.send(null);

        doneList.push(url);
    }

    todoList.push(startdir);
    getNextFileListWithWebDav();
}
