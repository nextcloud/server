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
 *
 * implementation according to
 * http://www.thaiopensource.com/relaxng/derivative.html
 */
runtime.loadClass("xmldom.RelaxNGParser");
/**
 * @constructor
 */
xmldom.RelaxNG = function RelaxNG() {
    "use strict";
    var xmlnsns = "http://www.w3.org/2000/xmlns/",
        createChoice,
        createInterleave,
        createGroup,
        createAfter,
        createOneOrMore,
        createValue,
        createAttribute,
        createNameClass,
        createData,
        makePattern,
        notAllowed = {
            type: "notAllowed",
            nullable: false,
            hash: "notAllowed",
            textDeriv: function () { return notAllowed; },
            startTagOpenDeriv: function () { return notAllowed; },
            attDeriv: function () { return notAllowed; },
            startTagCloseDeriv: function () { return notAllowed; },
            endTagDeriv: function () { return notAllowed; }
        },
        empty = {
            type: "empty",
            nullable: true,
            hash: "empty",
            textDeriv: function () { return notAllowed; },
            startTagOpenDeriv: function () { return notAllowed; },
            attDeriv: function (context, attribute) { return notAllowed; },
            startTagCloseDeriv: function () { return empty; },
            endTagDeriv: function () { return notAllowed; }
        },
        text = {
            type: "text",
            nullable: true,
            hash: "text",
            textDeriv: function () { return text; },
            startTagOpenDeriv: function () { return notAllowed; },
            attDeriv: function () { return notAllowed; },
            startTagCloseDeriv: function () { return text; },
            endTagDeriv: function () { return notAllowed; }
        },
        applyAfter,
        childDeriv,
        rootPattern;

    function memoize0arg(func) {
        return (function () {
            var cache;
            return function () {
                if (cache === undefined) {
                    cache = func();
                }
                return cache;
            };
        }());
    }
    function memoize1arg(type, func) {
        return (function () {
            var cache = {}, cachecount = 0;
            return function (a) {
                var ahash = a.hash || a.toString(),
                    v;
                v = cache[ahash];
                if (v !== undefined) {
                    return v;
                }
                cache[ahash] = v = func(a);
                v.hash = type + cachecount.toString();
                cachecount += 1;
                return v;
            };
        }());
    }
    function memoizeNode(func) {
        return (function () {
            var cache = {};
            return function (node) {
                var v, m;
                m = cache[node.localName];
                if (m === undefined) {
                    cache[node.localName] = m = {};
                } else {
                    v = m[node.namespaceURI];
                    if (v !== undefined) {
                        return v;
                    }
                }
                m[node.namespaceURI] = v = func(node);
                return v;
            };
        }());
    }
    function memoize2arg(type, fastfunc, func) {
        return (function () {
            var cache = {}, cachecount = 0;
            return function (a, b) {
                var v = fastfunc && fastfunc(a, b),
                    ahash, bhash, m;
                if (v !== undefined) { return v; }
                ahash = a.hash || a.toString();
                bhash = b.hash || b.toString();
                m = cache[ahash];
                if (m === undefined) {
                    cache[ahash] = m = {};
                } else {
                    v = m[bhash];
                    if (v !== undefined) {
                        return v;
                    }
                }
                m[bhash] = v = func(a, b);
                v.hash = type + cachecount.toString();
                cachecount += 1;
                return v;
            };
        }());
    }
    // this memoize function can be used for functions where the order of two
    // arguments is not important
    function unorderedMemoize2arg(type, fastfunc, func) {
        return (function () {
            var cache = {}, cachecount = 0;
            return function (a, b) {
                var v = fastfunc && fastfunc(a, b),
                    ahash, bhash, m;
                if (v !== undefined) { return v; }
                ahash = a.hash || a.toString();
                bhash = b.hash || b.toString();
                if (ahash < bhash) {
                    m = ahash; ahash = bhash; bhash = m;
                    m = a; a = b; b = m;
                }
                m = cache[ahash];
                if (m === undefined) {
                    cache[ahash] = m = {};
                } else {
                    v = m[bhash];
                    if (v !== undefined) {
                        return v;
                    }
                }
                m[bhash] = v = func(a, b);
                v.hash = type + cachecount.toString();
                cachecount += 1;
                return v;
            };
        }());
    }
    function getUniqueLeaves(leaves, pattern) {
        if (pattern.p1.type === "choice") {
            getUniqueLeaves(leaves, pattern.p1);
        } else {
            leaves[pattern.p1.hash] = pattern.p1;
        }
        if (pattern.p2.type === "choice") {
            getUniqueLeaves(leaves, pattern.p2);
        } else {
            leaves[pattern.p2.hash] = pattern.p2;
        }
    }
    createChoice = memoize2arg("choice", function (p1, p2) {
        if (p1 === notAllowed) { return p2; }
        if (p2 === notAllowed) { return p1; }
        if (p1 === p2) { return p1; }
    }, function (p1, p2) {
        function makeChoice(p1, p2) {
            return {
                type: "choice",
                p1: p1,
                p2: p2,
                nullable: p1.nullable || p2.nullable,
                textDeriv: function (context, text) {
                    return createChoice(p1.textDeriv(context, text),
                        p2.textDeriv(context, text));
                },
                startTagOpenDeriv: memoizeNode(function (node) {
                    return createChoice(p1.startTagOpenDeriv(node),
                        p2.startTagOpenDeriv(node));
                }),
                attDeriv: function (context, attribute) {
                    return createChoice(p1.attDeriv(context, attribute),
                        p2.attDeriv(context, attribute));
                },
                startTagCloseDeriv: memoize0arg(function () {
                    return createChoice(p1.startTagCloseDeriv(),
                        p2.startTagCloseDeriv());
                }),
                endTagDeriv: memoize0arg(function () {
                    return createChoice(p1.endTagDeriv(), p2.endTagDeriv());
                })
            };
        }
        var leaves = {}, i;
        getUniqueLeaves(leaves, {p1: p1, p2: p2});
        p1 = undefined;
        p2 = undefined;
        for (i in leaves) {
            if (leaves.hasOwnProperty(i)) {
                if (p1 === undefined) {
                    p1 = leaves[i];
                } else if (p2 === undefined) {
                    p2 = leaves[i];
                } else {
                    p2 = createChoice(p2, leaves[i]);
                }
            }
        }
        return makeChoice(p1, p2);
    });
    createInterleave = unorderedMemoize2arg("interleave", function (p1, p2) {
        if (p1 === notAllowed || p2 === notAllowed) { return notAllowed; }
        if (p1 === empty) { return p2; }
        if (p2 === empty) { return p1; }
    }, function (p1, p2) {
        return {
            type: "interleave",
            p1: p1,
            p2: p2,
            nullable: p1.nullable && p2.nullable,
            textDeriv: function (context, text) {
                return createChoice(
                    createInterleave(p1.textDeriv(context, text), p2),
                    createInterleave(p1, p2.textDeriv(context, text))
                );
            },
            startTagOpenDeriv: memoizeNode(function (node) {
                return createChoice(
                    applyAfter(function (p) { return createInterleave(p, p2); },
                               p1.startTagOpenDeriv(node)),
                    applyAfter(function (p) { return createInterleave(p1, p); },
                               p2.startTagOpenDeriv(node)));
            }),
            attDeriv: function (context, attribute) {
                return createChoice(
                    createInterleave(p1.attDeriv(context, attribute), p2),
                    createInterleave(p1, p2.attDeriv(context, attribute)));
            },
            startTagCloseDeriv: memoize0arg(function () {
                return createInterleave(p1.startTagCloseDeriv(),
                    p2.startTagCloseDeriv());
            })
        };
    });
    createGroup = memoize2arg("group", function (p1, p2) {
        if (p1 === notAllowed || p2 === notAllowed) { return notAllowed; }
        if (p1 === empty) { return p2; }
        if (p2 === empty) { return p1; }
    }, function (p1, p2) {
        return {
            type: "group",
            p1: p1,
            p2: p2,
            nullable: p1.nullable && p2.nullable,
            textDeriv: function (context, text) {
                var p = createGroup(p1.textDeriv(context, text), p2);
                if (p1.nullable) {
                    return createChoice(p, p2.textDeriv(context, text));
                }
                return p;
            },
            startTagOpenDeriv: function (node) {
                var x = applyAfter(function (p) { return createGroup(p, p2); },
                        p1.startTagOpenDeriv(node));
                if (p1.nullable) {
                    return createChoice(x, p2.startTagOpenDeriv(node));
                }
                return x;
            },
            attDeriv: function (context, attribute) {
                return createChoice(
                    createGroup(p1.attDeriv(context, attribute), p2),
                    createGroup(p1, p2.attDeriv(context, attribute)));
            },
            startTagCloseDeriv: memoize0arg(function () {
                return createGroup(p1.startTagCloseDeriv(),
                    p2.startTagCloseDeriv());
            })
        };
    });
    createAfter = memoize2arg("after", function (p1, p2) {
        if (p1 === notAllowed || p2 === notAllowed) { return notAllowed; }
    }, function (p1, p2) {
        return {
            type: "after",
            p1: p1,
            p2: p2,
            nullable: false,
            textDeriv: function (context, text) {
                return createAfter(p1.textDeriv(context, text), p2);
            },
            startTagOpenDeriv: memoizeNode(function (node) {
                return applyAfter(function (p) { return createAfter(p, p2); },
                    p1.startTagOpenDeriv(node));
            }),
            attDeriv: function (context, attribute) {
                return createAfter(p1.attDeriv(context, attribute), p2);
            },
            startTagCloseDeriv: memoize0arg(function () {
                return createAfter(p1.startTagCloseDeriv(), p2);
            }),
            endTagDeriv: memoize0arg(function () {
                return (p1.nullable) ? p2 : notAllowed;
            })
        };
    });
    createOneOrMore = memoize1arg("oneormore", function (p) {
        if (p === notAllowed) { return notAllowed; }
        return {
            type: "oneOrMore",
            p: p,
            nullable: p.nullable,
            textDeriv: function (context, text) {
                return createGroup(p.textDeriv(context, text),
                            createChoice(this, empty));
            },
            startTagOpenDeriv: function (node) {
                var oneOrMore = this;
                return applyAfter(function (pf) {
                    return createGroup(pf, createChoice(oneOrMore, empty));
                }, p.startTagOpenDeriv(node));
            },
            attDeriv: function (context, attribute) {
                var oneOrMore = this;
                return createGroup(p.attDeriv(context, attribute),
                    createChoice(oneOrMore, empty));
            },
            startTagCloseDeriv: memoize0arg(function () {
                return createOneOrMore(p.startTagCloseDeriv());
            })
        };
    });
    function createElement(nc, p) {
        return {
            type: "element",
            nc: nc,
            nullable: false,
            textDeriv: function () { return notAllowed; },
            startTagOpenDeriv: function (node) {
                if (nc.contains(node)) {
                    return createAfter(p, empty);
                }
                return notAllowed;
            },
            attDeriv: function (context, attribute) { return notAllowed; },
            startTagCloseDeriv: function () { return this; }
        };
    }
    function valueMatch(context, pattern, text) {
        return (pattern.nullable && /^\s+$/.test(text)) ||
                pattern.textDeriv(context, text).nullable;
    }
    createAttribute = memoize2arg("attribute", undefined, function (nc, p) {
        return {
            type: "attribute",
            nullable: false,
            nc: nc,
            p: p,
            attDeriv: function (context, attribute) {
                if (nc.contains(attribute) && valueMatch(context, p,
                        attribute.nodeValue)) {
                    return empty;
                }
                return notAllowed;
            },
            startTagCloseDeriv: function () { return notAllowed; }
        };
    });
    function createList() {
        return {
            type: "list",
            nullable: false,
            hash: "list",
            textDeriv: function (context, text) {
                return empty;
            }
        };
    }
    createValue = memoize1arg("value", function (value) {
        return {
            type: "value",
            nullable: false,
            value: value,
            textDeriv: function (context, text) {
                return (text === value) ? empty : notAllowed;
            },
            attDeriv: function () { return notAllowed; },
            startTagCloseDeriv: function () { return this; }
        };
    });
    createData = memoize1arg("data", function (type) {
        return {
            type: "data",
            nullable: false,
            dataType: type,
            textDeriv: function () { return empty; },
            attDeriv: function () { return notAllowed; },
            startTagCloseDeriv: function () { return this; }
        };
    });
    function createDataExcept() {
        return {
            type: "dataExcept",
            nullable: false,
            hash: "dataExcept"
        };
    }
    applyAfter = function applyAfter(f, p) {
        var result;
        if (p.type === "after") {
            result = createAfter(p.p1, f(p.p2));
        } else if (p.type === "choice") {
            result = createChoice(applyAfter(f, p.p1), applyAfter(f, p.p2));
        } else {
            result = p;
        }
        return result;
    };
    function attsDeriv(context, pattern, attributes, position) {
        if (pattern === notAllowed) {
            return notAllowed;
        }
        if (position >= attributes.length) {
            return pattern;
        }
        if (position === 0) {
            // TODO: loop over attributes to update namespace mapping
            position = 0;
        }
        var a = attributes.item(position);
        while (a.namespaceURI === xmlnsns) { // always ok
            position += 1;
            if (position >= attributes.length) {
                return pattern;
            }
            a = attributes.item(position);
        }
        a = attsDeriv(context, pattern.attDeriv(context,
                attributes.item(position)), attributes, position + 1);
        return a;
    }
    function childrenDeriv(context, pattern, walker) {
        var element = walker.currentNode,
            childNode = walker.firstChild(),
            numberOfTextNodes = 0,
            childNodes = [], i, p;
        // simple incomplete implementation: only use non-empty text nodes
        while (childNode) {
            if (childNode.nodeType === 1) {
                childNodes.push(childNode);
            } else if (childNode.nodeType === 3 &&
                    !/^\s*$/.test(childNode.nodeValue)) {
                childNodes.push(childNode.nodeValue);
                numberOfTextNodes += 1;
            }
            childNode = walker.nextSibling();
        }
        // if there is no nodes at all, add an empty text node
        if (childNodes.length === 0) {
            childNodes = [""];
        }
        p = pattern;
        for (i = 0; p !== notAllowed && i < childNodes.length; i += 1) {
            childNode = childNodes[i];
            if (typeof childNode === "string") {
                if (/^\s*$/.test(childNode)) {
                    p = createChoice(p, p.textDeriv(context, childNode));
                } else {
                    p = p.textDeriv(context, childNode);
                }
            } else {
                walker.currentNode = childNode;
                p = childDeriv(context, p, walker);
            }
        }
        walker.currentNode = element;
        return p;
    }
    childDeriv = function childDeriv(context, pattern, walker) {
        var childNode = walker.currentNode, p;
        p = pattern.startTagOpenDeriv(childNode);
        p = attsDeriv(context, p, childNode.attributes, 0);
        p = p.startTagCloseDeriv();
        p = childrenDeriv(context, p, walker);
        p = p.endTagDeriv();
        return p;
    };
    function addNames(name, ns, pattern) {
        if (pattern.e[0].a) {
            name.push(pattern.e[0].text);
            ns.push(pattern.e[0].a.ns);
        } else {
            addNames(name, ns, pattern.e[0]);
        }
        if (pattern.e[1].a) {
            name.push(pattern.e[1].text);
            ns.push(pattern.e[1].a.ns);
        } else {
            addNames(name, ns, pattern.e[1]);
        }
    }
    createNameClass = function createNameClass(pattern) {
        var name, ns, hash, i, result;
        if (pattern.name === "name") {
            name = pattern.text;
            ns = pattern.a.ns;
            result = {
                name: name,
                ns: ns,
                hash: "{" + ns + "}" + name,
                contains: function (node) {
                    return node.namespaceURI === ns && node.localName === name;
                }
            };
        } else if (pattern.name === "choice") {
            name = [];
            ns = [];
            addNames(name, ns, pattern);
            hash = "";
            for (i = 0; i < name.length; i += 1) {
                 hash += "{" + ns[i] + "}" + name[i] + ",";
            }
            result = {
                hash: hash,
                contains: function (node) {
                    var i;
                    for (i = 0; i < name.length; i += 1) {
                        if (name[i] === node.localName &&
                                ns[i] === node.namespaceURI) {
                            return true;
                        }
                    }
                    return false;
                }
            };
        } else {
            result = {
                hash: "anyName",
                contains: function () { return true; }
            };
        }
        return result;
    };
    function resolveElement(pattern, elements) {
        var element, p, i, hash;
        // create an empty object in the store to enable circular
        // dependencies
        hash = "element" + pattern.id.toString();
        p = elements[pattern.id] = { hash: hash };
        element = createElement(createNameClass(pattern.e[0]),
            makePattern(pattern.e[1], elements));
        // copy the properties of the new object into the predefined one
        for (i in element) {
            if (element.hasOwnProperty(i)) {
                p[i] = element[i];
            }
        }
        return p;
    }
    makePattern = function makePattern(pattern, elements) {
        var p, i;
        if (pattern.name === "elementref") {
            p = pattern.id || 0;
            pattern = elements[p];
            if (pattern.name !== undefined) {
                return resolveElement(pattern, elements);
            }
            return pattern;
        }
        switch (pattern.name) {
            case 'empty':
                return empty;
            case 'notAllowed':
                return notAllowed;
            case 'text':
                return text;
            case 'choice':
                return createChoice(makePattern(pattern.e[0], elements),
                    makePattern(pattern.e[1], elements));
            case 'interleave':
                p = makePattern(pattern.e[0], elements);
                for (i = 1; i < pattern.e.length; i += 1) {
                    p = createInterleave(p, makePattern(pattern.e[i],
                            elements));
                }
                return p;
            case 'group':
                return createGroup(makePattern(pattern.e[0], elements),
                    makePattern(pattern.e[1], elements));
            case 'oneOrMore':
                return createOneOrMore(makePattern(pattern.e[0], elements));
            case 'attribute':
                return createAttribute(createNameClass(pattern.e[0]),
                    makePattern(pattern.e[1], elements));
            case 'value':
                return createValue(pattern.text);
            case 'data':
                p = pattern.a && pattern.a.type;
                if (p === undefined) {
                    p = "";
                }
                return createData(p);
            case 'list':
                return createList();
        }
        throw "No support for " + pattern.name;
    };
    this.makePattern = function (pattern, elements) {
        var copy = {}, i;
        for (i in elements) {
            if (elements.hasOwnProperty(i)) {
                copy[i] = elements[i];
            }
        }
        i = makePattern(pattern, copy);
        return i;
    };
    /**
     * Validate the elements pointed to by the TreeWalker
     * @param {!TreeWalker} walker
     * @param {!function(Array.<string>):undefined} callback
     * @return {undefined}
     */
    this.validate = function validate(walker, callback) {
        var errors;
        walker.currentNode = walker.root;
        errors = childDeriv(null, rootPattern, walker);
        if (!errors.nullable) {
            runtime.log("Error in Relax NG validation: " + errors);
            callback(["Error in Relax NG validation: " + errors]);
        } else {
            callback(null);
        }
    };
    this.init = function init(rootPattern1) {
        rootPattern = rootPattern1;
    };
};
