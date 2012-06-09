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

/**
 * RelaxNG can check a DOM tree against a Relax NG schema
 * The RelaxNG implementation is currently not complete. Relax NG should not
 * report errors on valid DOM trees, but it will not check all constraints that
 * a Relax NG file can define. The current implementation does not load external
 * parts of a Relax NG file.
 * The main purpose of this Relax NG engine is to validate runtime ODF
 * documents. The DOM tree is traversed via a TreeWalker. A custom TreeWalker
 * implementation can hide parts of a DOM tree. This is useful in WebODF, where
 * special elements and attributes in the runtime DOM tree.
 * @constructor
 */
xmldom.RelaxNGParser = function RelaxNGParser() {
    "use strict";
    var self = this,
        rngns = "http://relaxng.org/ns/structure/1.0",
        xmlnsns = "http://www.w3.org/2000/xmlns/",
        start,
        nsmap = { "http://www.w3.org/XML/1998/namespace": "xml" },
        parse;

    /**
     * @constructor
     * @param {!string} error
     * @param {Node=} context
     */
    function RelaxNGParseError(error, context) {
        /**
         * return {!string}
         */
        this.message = function () {
            if (context) {
                error += (context.nodeType === 1) ? " Element " : " Node ";
                error += context.nodeName;
                if (context.nodeValue) {
                    error += " with value '" + context.nodeValue + "'";
                }
                error += ".";
            }
            return error;
        };
    }
    function splitToDuos(e) {
        if (e.e.length <= 2) {
            return e;
        }
        var o = { name: e.name, e: e.e.slice(0, 2) };
        return splitToDuos({
            name: e.name,
            e: [ o ].concat(e.e.slice(2))
        });
    }
    /**
     * @param {!string} name
     * @return {!Array.<string>}
     */
    function splitQName(name) {
        var r = name.split(":", 2),
            prefix = "", i;
        if (r.length === 1) {
            r = ["", r[0]];
        } else {
            prefix = r[0];
        }
        for (i in nsmap) {
            if (nsmap[i] === prefix) {
                r[0] = i;
            }
        }
        return r;
    }

    function splitQNames(def) {
        var i, l = (def.names) ? def.names.length : 0, name,
            localnames = def.localnames = [l],
            namespaces = def.namespaces = [l];
        for (i = 0; i < l; i += 1) {
            name = splitQName(def.names[i]);
            namespaces[i] = name[0];
            localnames[i] = name[1];
        }
    }

    /**
     * @param {!string} str
     * @return {!string}
     */
    function trim(str) {
        str = str.replace(/^\s\s*/, '');
		var ws = /\s/,
            i = str.length - 1;
        while (ws.test(str.charAt(i))) {
            i -= 1;
        }
        return str.slice(0, i + 1);
    }

    /**
     * @param {!Object.<string,string>} atts
     * @param {!string} name
     * @param {!Array.<string>} names
     * @return {!Object}
     */
    function copyAttributes(atts, name, names) {
        var a = {}, i, att;
        for (i = 0; i < atts.length; i += 1) {
            att = atts.item(i);
            if (!att.namespaceURI) {
                if (att.localName === "name" &&
                        (name === "element" || name === "attribute")) {
                    names.push(att.value);
                }
                if (att.localName === "name" || att.localName === "combine" ||
                        att.localName === "type") {
                    att.value = trim(att.value);
                }
                a[att.localName] = att.value;
            } else if (att.namespaceURI === xmlnsns) {
                nsmap[att.value] = att.localName;
            }
        }
        return a;
    }

    function parseChildren(c, e, elements, names) {
        var text = "", ce;
        while (c) {
            if (c.nodeType === 1 && c.namespaceURI === rngns) {
                ce = parse(c, elements, e);
                if (ce) {
                    if (ce.name === "name") {
                        names.push(nsmap[ce.a.ns] + ":" + ce.text);
                        e.push(ce);
                    } else if (ce.name === "choice" && ce.names &&
                            ce.names.length) {
                        names = names.concat(ce.names);
                        delete ce.names;
                        e.push(ce);
                    } else {
                        e.push(ce);
                    }
                }
            } else if (c.nodeType === 3) {
                text += c.nodeValue;
            }
            c = c.nextSibling;
        }
        return text;
    }

    function combineDefines(combine, name, e, siblings) {
        // combineDefines is called often enough that there can only be one
        // other element with the same name
        var i, ce;
        for (i = 0; siblings && i < siblings.length; i += 1) {
            ce = siblings[i];
            if (ce.name === "define" && ce.a && ce.a.name === name) {
                ce.e = [ { name: combine, e: ce.e.concat(e) } ];
                return ce;
            }
        }
        return null;
    }

    parse = function parse(element, elements, siblings) {
        // parse all elements from the Relax NG namespace into JavaScript
        // objects
        var e = [],
            /**@type{Object}*/a,
            ce,
            i, text, name = element.localName, names = [];
        a = copyAttributes(element.attributes, name, names);
        a.combine = a.combine || undefined;
        text = parseChildren(element.firstChild, e, elements, names);

        // 4.2 strip leading and trailing whitespace
        if (name !== "value" && name !== "param") {
            text = /^\s*([\s\S]*\S)?\s*$/.exec(text)[1];
        }
        // 4.3 datatypeLibrary attribute
        // 4.4 type attribute of value element
        if (name === "value" && a.type === undefined) {
            a.type = "token";
            a.datatypeLibrary = "";
        }
        // 4.5 href attribute
        // 4.6 externalRef element
        // 4.7 include element
        // 4.8 name attribute of element and attribute elements
        if ((name === "attribute" || name === "element") &&
                a.name !== undefined) {
           i = splitQName(a.name);
           e = [{name: "name", text: i[1], a: {ns: i[0]}}].concat(e);
           delete a.name;
        }
        // 4.9 ns attribute
        if (name === "name" || name === "nsName" || name === "value") {
            if (a.ns === undefined) {
                a.ns = ""; // TODO
            }
        } else {
            delete a.ns;
        }
        // 4.10 QNames
        if (name === "name") {
            i = splitQName(text);
            a.ns = i[0];
            text = i[1];
        }
        // 4.11 div element
        // 4.12 Number of child elements
        if (e.length > 1 && (name === "define" || name === "oneOrMore" ||
                name === "zeroOrMore" || name === "optional" ||
                name === "list" || name === "mixed")) {
            e = [{name: "group", e: splitToDuos({name: "group", e: e}).e}];
        }
        if (e.length > 2 && name === "element") {
            e = [e[0]].concat(
                {name: "group", e: splitToDuos(
                    {name: "group", e: e.slice(1)}).e});
        }
        if (e.length === 1 && name === "attribute") {
            e.push({name: "text", text: text});
        }
        // if node has only one child, replace node with child
        if (e.length === 1 && (name === "choice" || name === "group" ||
                name === "interleave")) {
            name = e[0].name;
            names = e[0].names;
            a = e[0].a;
            text = e[0].text;
            e = e[0].e;
        } else if (e.length > 2 && (name === "choice" || name === "group" ||
                name === "interleave")) {
            e = splitToDuos({name: name, e: e}).e;
        }
        // 4.13 mixed element
        if (name === "mixed") {
            name = "interleave";
            e = [ e[0], { name: "text" } ];
        }
        // 4.14 optional element
        if (name === "optional") {
            name = "choice";
            e = [ e[0], { name: "empty" } ];
        }
        // 4.15 zeroOrMore element
        if (name === "zeroOrMore") {
            name = "choice";
            e = [ {name: "oneOrMore", e: [ e[0] ] }, { name: "empty" } ];
        }
        // 4.17 combine attribute
        if (name === "define" && a.combine) {
            ce = combineDefines(a.combine, a.name, e, siblings);
            if (ce) {
                return;
            }
        }

        // create the definition
        ce = { name: name };
        if (e && e.length > 0) { ce.e = e; }
        for (i in a) {
            if (a.hasOwnProperty(i)) {
                ce.a = a;
                break;
            }
        }
        if (text !== undefined) { ce.text = text; }
        if (names && names.length > 0) { ce.names = names; }

        // part one of 4.19
        if (name === "element") {
            ce.id = elements.length;
            elements.push(ce);
            ce = { name: "elementref", id: ce.id };
        }
        return ce;
    };

    function resolveDefines(def, defines) {
        var i = 0, e, defs, end, name = def.name;
        while (def.e && i < def.e.length) {
            e = def.e[i];
            if (e.name === "ref") {
                defs = defines[e.a.name];
                if (!defs) {
                    throw e.a.name + " was not defined.";
                }
                end = def.e.slice(i + 1);
                def.e = def.e.slice(0, i);
                def.e = def.e.concat(defs.e);
                def.e = def.e.concat(end);
            } else {
                i += 1;
                resolveDefines(e, defines);
            }
        }
        e = def.e;
        // 4.20 notAllowed element
        // 4.21 empty element
        if (name === "choice") {
            if (!e || !e[1] || e[1].name === "empty") {
                if (!e || !e[0] || e[0].name === "empty") {
                    delete def.e;
                    def.name = "empty";
                } else {
                    e[1] = e[0];
                    e[0] = { name: "empty" };
                }
            }
        }
        if (name === "group" || name === "interleave") {
            if (e[0].name === "empty") {
                if (e[1].name === "empty") {
                    delete def.e;
                    def.name = "empty";
                } else {
                    name = def.name = e[1].name;
                    def.names = e[1].names;
                    e = def.e = e[1].e;
                }
            } else if (e[1].name === "empty") {
                name = def.name = e[0].name;
                def.names = e[0].names;
                e = def.e = e[0].e;
            }
        }
        if (name === "oneOrMore" && e[0].name === "empty") {
            delete def.e;
            def.name = "empty";
        }
        // for attributes we need to have the list of namespaces and
        // localnames readily available, so we split up the qnames
        if (name === "attribute") {
            splitQNames(def);
        }
        // for interleaving validation, it is convenient to join all
        // interleave elements that touch into one element
        if (name === "interleave") {
            // at this point the interleave will have two child elements,
            // but the child interleave elements may have a different number
            if (e[0].name === "interleave") {
                if (e[1].name === "interleave") {
                    e = def.e = e[0].e.concat(e[1].e);
                } else {
                    e = def.e = [e[1]].concat(e[0].e);
                }
            } else if (e[1].name === "interleave") {
                e = def.e = [e[0]].concat(e[1].e);
            }
        }
    }

    function resolveElements(def, elements) {
        var i = 0, e, name;
        while (def.e && i < def.e.length) {
            e = def.e[i];
            if (e.name === "elementref") {
                e.id = e.id || 0;
                def.e[i] = elements[e.id];
            } else if (e.name !== "element") {
                resolveElements(e, elements);
            }
            i += 1;
        }
    }

    /**
     * @param {!Document} dom
     * @param {!Function} callback
     * @return {?Array}
     */
    function main(dom, callback) {
        var elements = [],
            grammar = parse(dom && dom.documentElement, elements, undefined),
            i, e, defines = {};

        for (i = 0; i < grammar.e.length; i += 1) {
            e = grammar.e[i];
            if (e.name === "define") {
                defines[e.a.name] = e;
            } else if (e.name === "start") {
                start = e;
            }
        }
        if (!start) {
            return [new RelaxNGParseError(
                    "No Relax NG start element was found.")];
        }
        resolveDefines(start, defines);
        for (i in defines) {
            if (defines.hasOwnProperty(i)) {
                resolveDefines(defines[i], defines);
            }
        }
        for (i = 0; i < elements.length; i += 1) {
            resolveDefines(elements[i], defines);
        }
        if (callback) {
            self.rootPattern = callback(start.e[0], elements);
        }
        resolveElements(start, elements);
        for (i = 0; i < elements.length; i += 1) {
            resolveElements(elements[i], elements);
        }
        self.start = start;
        self.elements = elements;
        self.nsmap = nsmap;
        return null;
    }
    /**
     * @param {!Document} dom
     * @param {!Function} callback
     * @return {?Array}
     */
    this.parseRelaxNGDOM = main;
};
