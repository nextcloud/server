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
/*global xmldom*/
/*jslint sub: true*/
if (typeof Object.create !== 'function') {
    Object['create'] = function (o) {
        "use strict";
        /**
         * @constructor
         */
        var F = function () {};
        F.prototype = o;
        return new F();
    };
}

/**
 * Partial implementation of LSSerializer
 * @constructor
 */
xmldom.LSSerializer = function LSSerializer() {
    "use strict";
    var /**@const@type{!LSSerializer}*/ self = this;

    /**
     * @param {!string} prefix
     * @param {!Attr} attr
     * @return {!string}
     */
    function serializeAttribute(prefix, attr) {
        var /**@type{!string}*/ s = prefix + attr.localName + "=\"" +
            attr.nodeValue + "\"";
        return s;
    }
    /**
     * @param {!Object.<string,string>} nsmap
     * @param {string} prefix
     * @param {string} ns
     * @return {!string}
     */
    function attributePrefix(nsmap, prefix, ns) {
        // TODO: check for double prefix definitions, this needs a special class
        if (nsmap.hasOwnProperty(ns)) {
            return nsmap[ns] + ":";
        }
        if (nsmap[ns] !== prefix) {
            nsmap[ns] = prefix;
        }
        return prefix + ":";
    }
    /**
     * @param {!Object.<string,string>} nsmap
     * @param {!Node} node
     * @return {!string}
     */
    function startNode(nsmap, node) {
        var /**@type{!string}*/ s = "",
            /**@const@type{!NamedNodeMap}*/ atts = node.attributes,
            /**@const@type{!number}*/ length,
            /**@type{!number}*/ i,
            /**@type{!Attr}*/ attr,
            /**@type{!string}*/ attstr = "",
            /**@type{!number}*/ accept,
            /**@type{!string}*/ prefix;
        if (atts) { // ELEMENT
            if (nsmap[node.namespaceURI] !== node.prefix) {
                nsmap[node.namespaceURI] = node.prefix;
            }
            s += "<" + node.nodeName;
            length = atts.length;
            for (i = 0; i < length; i += 1) {
                attr = /**@type{!Attr}*/(atts.item(i));
                if (attr.namespaceURI !== "http://www.w3.org/2000/xmlns/") {
                    accept = (self.filter) ? self.filter.acceptNode(attr) : 1;
                    if (accept === 1) {
                        // xml attributes always need a prefix for a namespace
                        if (attr.namespaceURI) {
                           prefix = attributePrefix(nsmap, attr.prefix,
                                   attr.namespaceURI);
                        } else {
                           prefix = "";
                        }
                        attstr += " " + serializeAttribute(prefix, attr);
                    }
                }
            }
            for (i in nsmap) {
                if (nsmap.hasOwnProperty(i)) {
                    prefix = nsmap[i];
                    if (!prefix) {
                        s += " xmlns=\"" + i + "\"";
                    } else if (prefix !== "xmlns") {
                        s += " xmlns:" + nsmap[i] + "=\"" + i + "\"";
                    }
                }
            }
            s += attstr + ">";
        }
        return s;
    }
    /**
     * @param {!Node} node
     * @return {!string}
     */
    function endNode(node) {
        var /**@type{!string}*/ s = "";
        if (node.nodeType === 1) { // ELEMENT
            s += "</" + node.nodeName + ">";
        }
        return s;
    }
    /**
     * @param {!Object.<string,string>} parentnsmap
     * @param {!Node} node
     * @return {!string}
     */
    function serializeNode(parentnsmap, node) {
        var /**@type{!string}*/ s = "",
            /**@const@type{!Object.<string,string>}*/ nsmap
                = Object.create(parentnsmap),
            /**@const@type{!number}*/ accept
                = (self.filter) ? self.filter.acceptNode(node) : 1,
            /**@type{Node}*/child;
        if (accept === 1) {
            s += startNode(nsmap, node);
        }
        if (accept === 1 || accept === 3) {
            child = node.firstChild;
            while (child) {
                s += serializeNode(nsmap, child);
                child = child.nextSibling;
            }
            if (node.nodeValue) {
                s += node.nodeValue;
            }
        }
        if (accept === 1) {
            s += endNode(node);
        }
        return s;
    }
    function invertMap(map) {
        var m = {}, i;
        for (i in map) {
            if (map.hasOwnProperty(i)) {
                m[map[i]] = i;
            }
        }
        return m;
    }
    /**
     * @type {xmldom.LSSerializerFilter}
     */
    this.filter = null;
    /**
     * @param {!Node} node
     * @param {!Object.<string,string>} nsmap
     * @return {!string}
     */
    this.writeToString = function (node, nsmap) {
        if (!node) {
            return "";
        }
        nsmap = nsmap ? invertMap(nsmap) : {};
        return serializeNode(nsmap, node);
    };
};
