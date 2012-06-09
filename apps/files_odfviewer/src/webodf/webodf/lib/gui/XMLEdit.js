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
/*global runtime: true, core: true, gui: true*/
runtime.loadClass("core.PointWalker");
runtime.loadClass("core.Cursor");
//runtime.loadClass("gui.Caret");
/**
 * @constructor
 */
gui.XMLEdit = function XMLEdit(element, stylesheet) {
    "use strict";
    var simplecss,
        cssprefix,
        documentElement,
        customNS = "customns",
        walker = null;

    if (!element.id) {
        element.id = "xml" + String(Math.random()).substring(2);
    }
//    element.contentEditable = true;
    cssprefix = "#" + element.id + " ";

    function installHandlers() {
    }

    // generic css for doing xml formatting: color tags and do indentation
    simplecss = cssprefix + "*," + cssprefix + ":visited, " + cssprefix + ":link {display:block; margin: 0px; margin-left: 10px; font-size: medium; color: black; background: white; font-variant: normal; font-weight: normal; font-style: normal; font-family: sans-serif; text-decoration: none; white-space: pre-wrap; height: auto; width: auto}\n" +
        cssprefix + ":before {color: blue; content: '<' attr(customns_name) attr(customns_atts) '>';}\n" +
        cssprefix + ":after {color: blue; content: '</' attr(customns_name) '>';}\n" +
        cssprefix + "{overflow: auto;}\n";

    function listenEvent(eventTarget, eventType, eventHandler) {
        if (eventTarget.addEventListener) {
            eventTarget.addEventListener(eventType, eventHandler, false);
        } else if (eventTarget.attachEvent) {
            eventType = "on" + eventType;
            eventTarget.attachEvent(eventType, eventHandler);
        } else {
            eventTarget["on" + eventType] = eventHandler;
        }
    }
    function cancelEvent(event) {
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false;
        }
    }

    function isCaretMoveCommand(charCode) {
        if (charCode >= 16 && charCode <= 20) {
            return true;
        }
        if (charCode >= 33 && charCode <= 40) { //arrows,home,end,pgup,pgdown
            return true;
        }
        return false;
    }

    function syncSelectionWithWalker() {
        var sel = element.ownerDocument.defaultView.getSelection(),
            r;
        if (!sel || sel.rangeCount <= 0 || !walker) {
            return;
        }
        r = sel.getRangeAt(0);
        walker.setPoint(r.startContainer, r.startOffset);
    }

    function syncWalkerWithSelection() {
        var sel = element.ownerDocument.defaultView.getSelection(),
            n, r;
        sel.removeAllRanges();
        if (!walker || !walker.node()) {
            return;
        }
        n = walker.node();
        r = n.ownerDocument.createRange();
        r.setStart(n, walker.position());
        r.collapse(true);
        sel.addRange(r);
    }

    function handleKeyDown(event) {
        var charCode = event.charCode || event.keyCode;
        // cursor movement
        walker = null;
        if (walker && charCode === 39) { // right arrow
            syncSelectionWithWalker();
            walker.stepForward();
            syncWalkerWithSelection();
        } else if (walker && charCode === 37) { //left arrow
            syncSelectionWithWalker();
            walker.stepBackward();
            syncWalkerWithSelection();
        } else if (isCaretMoveCommand(charCode)) {
            return;
        }
        cancelEvent(event);
    }

    function handleKeyPress(event) {
//        handleKeyDown(event);
    }

    function handleClick(event) {
//        alert(event.target.nodeName);
        var sel = element.ownerDocument.defaultView.getSelection(),
            r = sel.getRangeAt(0),
            n = r.startContainer;
        // if cursor is in customns node, move up to the top one
        /*
        if (n.parentNode.namespaceURI === customNS) {
            while (n.parentNode.namespaceURI === customNS) {
                n = n.parentNode;
            }
            r = n.ownerDocument.createRange();
            r.setStart(n.nextSibling, 0);
            r.collapse(true);
            sel.removeAllRanges();
            sel.addRange(r);
        }
*/
/*
            r = element.ownerDocument.createRange();
            r.setStart(event.target.nodeName, 0);
            r.collapse(true);
            sel.removeAllRanges();
            sel.addRange(r);
*/
//alert(sel.getRangeAt(0).startContainer.nodeName + " " + sel.getRangeAt(0).startOffset);

        cancelEvent(event);
    }

    function initElement(element) {
        listenEvent(element, "click", handleClick);
        listenEvent(element, "keydown", handleKeyDown);
        listenEvent(element, "keypress", handleKeyPress);
        //listenEvent(element, "mouseup", handleMouseUp);
        // ignore drop events, dragstart, drag, dragenter, dragover are ok for now
        listenEvent(element, "drop", cancelEvent);
        listenEvent(element, "dragend", cancelEvent);
        // pasting is also disallowed for now
        listenEvent(element, "beforepaste", cancelEvent);
        listenEvent(element, "paste", cancelEvent);
    }

    // remove all textnodes that contain only whitespace
    function cleanWhitespace(node) {
        var n = node.firstChild, p,
            re = /^\s*$/;
        while (n && n !== node) {
            p = n;
            n = n.nextSibling || n.parentNode;
            if (p.nodeType === 3 && re.test(p.nodeValue)) {
                p.parentNode.removeChild(p);
            }
        }
    }
    /**
     * @param {!Node} node
     * @return {undefined}
     */
    function setCssHelperAttributes(node) {
        var atts, attsv, a, i;
        // write all attributes in a string that is shown via the css
        atts = node.attributes;
        attsv = "";
        for (i = atts.length - 1; i >= 0; i -= 1) {
            a = atts.item(i);
            attsv = attsv + " " + a.nodeName + "=\"" + a.nodeValue + "\"";
        }
        node.setAttribute("customns_name", node.nodeName);
        node.setAttribute("customns_atts", attsv);
    }
    /**
     * @param {!Node} node
     * @return {undefined}
     */
    function addExplicitAttributes(node) {
        var n = node.firstChild;
        // recurse over the dom
        while (n && n !== node) {
            if (n.nodeType === 1) {
                addExplicitAttributes(n);
            }
            n = n.nextSibling || n.parentNode;
        }
        setCssHelperAttributes(node);
        cleanWhitespace(node);
    }

    function getNamespacePrefixes(node, prefixes) {
        var n = node.firstChild, atts, att, i;
        while (n && n !== node) {
            if (n.nodeType === 1) {
                getNamespacePrefixes(n, prefixes);
                atts = n.attributes;
                for (i = atts.length - 1; i >= 0; i -= 1) {
                    att = atts.item(i);
                    // record the prefix that the document uses for namespaces
                    if (att.namespaceURI === "http://www.w3.org/2000/xmlns/") {
                        if (!prefixes[att.nodeValue]) {
                            prefixes[att.nodeValue] = att.localName;
                        }
                    }
                }
            }
            n = n.nextSibling || n.parentNode;
        }
    }

    /**
     * Give each namespace a unique prefix.
     * @param {Object.<?string,?string>} prefixes Map with namespace as key and
     *                                          prefix as value
     * @return {undefined}
     */
    function generateUniquePrefixes(prefixes) {
        var taken = {},
            ns, p, n = 0;
        for (ns in prefixes) {
            if (prefixes.hasOwnProperty(ns) && ns) {
                p = prefixes[ns];
                if (!p || taken.hasOwnProperty(p) || p === "xmlns") {
                    do {
                        p = "ns" + n;
                        n += 1;
                    } while (taken.hasOwnProperty(p));
                    prefixes[ns] = p;
                }
                taken[p] = true;
            }
        }
    }

    // the CSS neededed for the XML edit view depends on the prefixes
    function createCssFromXmlInstance(node) {
        // collect all prefixes and elements
        var prefixes = {},    // namespace prefixes as they occur in the XML
            css = "@namespace customns url(customns);\n",
            name, pre, ns, names, csssel;
        getNamespacePrefixes(node, prefixes);
        generateUniquePrefixes(prefixes);
/*
        for (ns in prefixes) {
            if (ns) {
                css = css + "@namepace " + prefixes[ns] + " url(" + ns + ");\n";
            }
        }
        for (ns in prefixes) {
            if (ns) {
                pre = cssprefix + prefixes[ns] + "|";
                css = css + pre + ":before { content: '<" + prefixes[ns] +
                        ":' attr(customns_name); }\n" +
                        pre + ":after { content: '</" + prefixes[ns] +
                        ":' attr(customns_name) '>'; }\n";
            }
        }
*/
        return css;
    }

    // Adapt the CSS to the current settings.
    function updateCSS() {
        var css = element.ownerDocument.createElement("style"),
            text = createCssFromXmlInstance(element);
        css.type = "text/css";
        text = text + simplecss;
        css.appendChild(element.ownerDocument.createTextNode(text));
        stylesheet = stylesheet.parentNode.replaceChild(css, stylesheet);
    }
    function getXML() {
        return documentElement;
    }
    function setXML(xml) {
        var node = xml.documentElement || xml;
        node = element.ownerDocument.importNode(node, true);
        documentElement = node;

        addExplicitAttributes(node);

        while (element.lastChild) {
            element.removeChild(element.lastChild);
        }
        element.appendChild(node);

        updateCSS();

        walker = new core.PointWalker(node);
    }

    initElement(element);

    this.updateCSS = updateCSS;
    this.setXML = setXML;
    this.getXML = getXML;
};
