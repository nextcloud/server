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
/*global xmldom, XPathResult, runtime*/
/**
 * Wrapper for XPath functions
 * @constructor
 */
xmldom.XPath = (function () {
    "use strict";
    var createXPathPathIterator,
        parsePredicates;
    /**
     * @param {!number} a
     * @param {!number} b
     * @param {!number} c
     * @return {!boolean}
     */
    function isSmallestPositive(a, b, c) {
        return a !== -1 && (a < b || b === -1) && (a < c || c === -1);
    }
    /**
     * Parse a subset of xpaths.
     * The xpath predicates may contain xpaths. The location may be equated to
     * a value. If a parsing error occurs, null is returned.
     * @param {!string} xpath
     * @param {!number} pos
     * @param {!number} end
     * @param {!Array} steps
     * @return {!number}
     */
    function parseXPathStep(xpath, pos, end, steps) {
        var location = "",
            predicates = [],
            value,
            brapos = xpath.indexOf('[', pos),
            slapos = xpath.indexOf('/', pos),
            eqpos = xpath.indexOf('=', pos),
            depth = 0,
            start = 0;
        // parse the location
        if (isSmallestPositive(slapos, brapos, eqpos)) {
            location = xpath.substring(pos, slapos);
            pos = slapos + 1;
        } else if (isSmallestPositive(brapos, slapos, eqpos)) {
            location = xpath.substring(pos, brapos);
            pos = parsePredicates(xpath, brapos, predicates);
        } else if (isSmallestPositive(eqpos, slapos, brapos)) {
            location = xpath.substring(pos, eqpos);
            pos = eqpos;
        } else {
            location = xpath.substring(pos, end);
            pos = end;
        }
        steps.push({location: location, predicates: predicates});
        return pos;
    }
    function parseXPath(xpath) {
        var steps = [],
            p = 0,
            end = xpath.length,
            value;
        while (p < end) {
            p = parseXPathStep(xpath, p, end, steps);
            if (p < end && xpath[p] === '=') {
                value = xpath.substring(p + 1, end);
                if (value.length > 2 &&
                        (value[0] === '\'' || value[0] === '"')) {
                    value = value.slice(1, value.length - 1);
                } else {
                    try {
                        value = parseInt(value, 10);
                    } catch (e) {
                    }
                }
                p = end;
            }
        }
        return {steps: steps, value: value};
    }
    parsePredicates = function parsePredicates(xpath, start, predicates) {
        var pos = start,
            l = xpath.length,
            selector,
            depth = 0;
        while (pos < l) {
            if (xpath[pos] === ']') {
                depth -= 1;
                if (depth <= 0) {
                    predicates.push(parseXPath(xpath.substring(start, pos)));
                }
            } else if (xpath[pos] === '[') {
                if (depth <= 0) {
                    start = pos + 1;
                }
                depth += 1;
            }
            pos += 1;
        }
        return pos;
    };
    /**
     * Iterator over nodes uses in the xpath implementation
     * @class
     * @interface
     */
    function XPathIterator() {}
    /**
     * @return {Node}
     */
    XPathIterator.prototype.next = function () {};
    /**
     * @return {undefined}
     */
    XPathIterator.prototype.reset = function () {};
    /**
     * @class
     * @constructor
     * @augments XPathIterator
     * @implements {XPathIterator}
     */
    function NodeIterator() {
        var node, done = false;
        this.setNode = function setNode(n) {
            node = n;
        };
        this.reset = function () {
            done = false;
        };
        this.next = function next() {
            var val = (done) ? null : node;
            done = true;
            return val;
        };
    }
    /**
     * @class
     * @constructor
     * @augments XPathIterator
     * @implements {XPathIterator}
     * @param {XPathIterator} it
     * @param {!string} namespace
     * @param {!string} localName
     */
    function AttributeIterator(it, namespace, localName) {
        this.reset = function reset() {
            it.reset();
        };
        this.next = function next() {
            var node = it.next(), attr;
            while (node) {
                node = node.getAttributeNodeNS(namespace, localName);
                if (node) {
                    return node;
                }
                node = it.next();
            }
            return node;
        };
    }
    /**
     * @class
     * @constructor
     * @augments XPathIterator
     * @implements {XPathIterator}
     * @param {XPathIterator} it
     * @param {boolean} recurse
     */
    function AllChildElementIterator(it, recurse) {
        var root = it.next(),
            node = null;
        this.reset = function reset() {
            it.reset();
            root = it.next();
            node = null;
        };
        this.next = function next() {
            while (root) {
                if (node) {
                    if (recurse && node.firstChild) {
                        node = node.firstChild;
                    } else {
                        while (!node.nextSibling && node !== root) {
                            node = node.parentNode;
                        }
                        if (node === root) {
                            root = it.next();
                        } else {
                            node = node.nextSibling;
                        }
                    }
                } else {
                    do {
//                        node = (recurse) ?root :root.firstChild;
                        node = root.firstChild;
                        if (!node) {
                            root = it.next();
                        }
                    } while (root && !node);
                }
                if (node && node.nodeType === 1) {
                    return node;
                }
            }
            return null;
        };
    }
    /**
     * @class
     * @constructor
     * @augments XPathIterator
     * @implements {XPathIterator}
     * @param {XPathIterator} it
     * @param {function(Node):boolean} condition
     */
    function ConditionIterator(it, condition) {
        this.reset = function reset() {
            it.reset();
        };
        this.next = function next() {
            var n = it.next();
            while (n && !condition(n)) {
                n = it.next();
            }
            return n;
        };
    }
    /**
     * @param {XPathIterator} it
     * @param {string} name
     * @param {function(string):string} namespaceResolver
     * @return {!ConditionIterator}
     */
    function createNodenameFilter(it, name, namespaceResolver) {
        var s = name.split(':', 2),
            namespace = namespaceResolver(s[0]),
            localName = s[1];
        return new ConditionIterator(it, function (node) {
            return node.localName === localName &&
                node.namespaceURI === namespace;
        });
    }
    /**
     * @param {XPathIterator} it
     * @param {!Object} p
     * @param {function(string):string} namespaceResolver
     * @return {!ConditionIterator}
     */
    function createPredicateFilteredIterator(it, p, namespaceResolver) {
        var nit = new NodeIterator(),
            pit = createXPathPathIterator(nit, p, namespaceResolver),
            value = p.value;
        if (value === undefined) {
            return new ConditionIterator(it, function (node) {
                nit.setNode(node);
                pit.reset();
                return pit.next();
            });
        }
        return new ConditionIterator(it, function (node) {
            nit.setNode(node);
            pit.reset();
            var n = pit.next();
            // todo: distinuish between number and string
            return n && n.nodeValue === value;
        });
    }
    /**
     * @param {!XPathIterator} it
     * @param {!Object} xpath
     * @param {!Function} namespaceResolver
     * @return {!XPathIterator}
     */
    createXPathPathIterator = function createXPathPathIterator(it, xpath,
                namespaceResolver) {
        var i, j, step, location, namespace, localName, prefix, p;
        for (i = 0; i < xpath.steps.length; i += 1) {
            step = xpath.steps[i];
            location = step.location;
            if (location === "") {
                it = new AllChildElementIterator(it, false);
            } else if (location[0] === '@') {
                p = location.slice(1).split(":", 2);
                it = new AttributeIterator(it, namespaceResolver(p[0]), p[1]);
            } else if (location !== ".") {
                it = new AllChildElementIterator(it, false);
                if (location.indexOf(":") !== -1) {
                    it = createNodenameFilter(it, location, namespaceResolver);
                }
            }
            for (j = 0; j < step.predicates.length; j += 1) {
                p = step.predicates[j];
                it = createPredicateFilteredIterator(it, p, namespaceResolver);
            }
        }
        return it;
    };
    /**
     * @param {!Element} node
     * @param {!string} xpath
     * @param {!Function} namespaceResolver
     * @return {!Array.<Element>}
     */
    function fallback(node, xpath, namespaceResolver) {
        var it = new NodeIterator(),
            i,
            nodelist,
            parsedXPath,
            pos;
        it.setNode(node);
        parsedXPath = parseXPath(xpath);
        it = createXPathPathIterator(it, parsedXPath, namespaceResolver);
        nodelist = [];
        i = it.next();
        while (i) {
            nodelist.push(i);
            i = it.next();
        }
        return nodelist;
    }
    /**
     * @param {!Element} node
     * @param {!string} xpath
     * @param {!Function} namespaceResolver
     * @return {!Array.<Element>}
     */
    function getODFElementsWithXPath(node, xpath, namespaceResolver) {
        var doc = node.ownerDocument,
            nodes,
            elements = [],
            n = null;
        if (!doc || !doc.evaluate || !n) {
            elements = fallback(node, xpath, namespaceResolver);
        } else {
            nodes = doc.evaluate(xpath, node, namespaceResolver,
                XPathResult.UNORDERED_NODE_ITERATOR_TYPE, null);
            n = nodes.iterateNext();
            while (n !== null) {
                if (n.nodeType === 1) {
                    elements.push(n);
                }
                n = nodes.iterateNext();
            }
        }
        return elements;
    }
    /**
     * @constructor
     */
    xmldom.XPath = function XPath() {
        this.getODFElementsWithXPath = getODFElementsWithXPath;
    };
    return xmldom.XPath;
}());
