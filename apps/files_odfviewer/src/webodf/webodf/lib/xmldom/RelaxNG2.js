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
 */
runtime.loadClass("xmldom.RelaxNGParser");
/**
 * @constructor
 */
xmldom.RelaxNG2 = function RelaxNG2() {
    "use strict";
    var start,
        validateNonEmptyPattern,
        nsmap,
        depth = 0,
        p = "                                                                ";

    /**
     * @constructor
     * @param {!string} error
     * @param {Node=} context
     */
    function RelaxNGParseError(error, context) {
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
//        runtime.log("[" + p.slice(0, depth) + this.message() + "]");
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateOneOrMore(elementdef, walker, element) {
        // The list of definitions in the elements list should be completely
        // traversed at least once. If a second or later round fails, the walker
        // should go back to the start of the last successful traversal
        var node, i = 0, err;
        do {
            node = walker.currentNode;
            err = validateNonEmptyPattern(elementdef.e[0], walker, element);
            i += 1;
        } while (!err && node !== walker.currentNode);
        if (i > 1) { // at least one round was without error
            // set position back to position of before last failed round
            walker.currentNode = node;
            return null;
        }
        return err;
    }
    /**
     * @param {!Node} node
     * @return {!string}
     */
    function qName(node) {
        return nsmap[node.namespaceURI] + ":" + node.localName;
    }
    /**
     * @param {!Node} node
     * @return {!boolean}
     */
    function isWhitespace(node) {
        return node && node.nodeType === 3 && /^\s+$/.test(node.nodeValue);
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @param {string=} data
     * @return {Array.<RelaxNGParseError>}
     */
    function validatePattern(elementdef, walker, element, data) {
        if (elementdef.name === "empty") {
            return null;
        }
        return validateNonEmptyPattern(elementdef, walker, element, data);
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateAttribute(elementdef, walker, element) {
        if (elementdef.e.length !== 2) {
            throw "Attribute with wrong # of elements: " + elementdef.e.length;
        }
        var att, a, l = elementdef.localnames.length, i;
        for (i = 0; i < l; i += 1) {
            a = element.getAttributeNS(elementdef.namespaces[i],
                    elementdef.localnames[i]);
            // if an element is not present, getAttributeNS will return an empty
            // string but an empty string is possible attribute value, so an
            // extra check is needed
            if (a === "" && !element.hasAttributeNS(elementdef.namespaces[i],
                    elementdef.localnames[i])) {
                a = undefined;
            }
            if (att !== undefined && a !== undefined) {
                return [new RelaxNGParseError("Attribute defined too often.",
                        element)];
            }
            att = a;
        }
        if (att === undefined) {
            return [new RelaxNGParseError("Attribute not found: " +
                    elementdef.names, element)];
        }
        return validatePattern(elementdef.e[1], walker, element, att);
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateTop(elementdef, walker, element) {
        // notAllowed not implemented atm
        return validatePattern(elementdef, walker, element);
    }
    /**
     * Validate an element.
     * Function forwards the walker until an element is met.
     * If element if of the right type, it is entered and the validation
     * continues inside the element. After validation, regardless of whether an
     * error occurred, the walker is at the same depth in the dom tree.
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateElement(elementdef, walker, element) {
        if (elementdef.e.length !== 2) {
            throw "Element with wrong # of elements: " + elementdef.e.length;
        }
        depth += 1;
        // forward until an element is seen, then check the name
        var /**@type{Node}*/ node = walker.currentNode,
            /**@type{number}*/ type = node ? node.nodeType : 0,
            error = null;
        // find the next element, skip text nodes with only whitespace
        while (type > 1) {
            if (type !== 8 &&
                    (type !== 3 ||
                     !/^\s+$/.test(walker.currentNode.nodeValue))) {// TEXT_NODE
                depth -= 1;
                return [new RelaxNGParseError("Not allowed node of type " +
                        type + ".")];
            }
            node = walker.nextSibling();
            type = node ? node.nodeType : 0;
        }
        if (!node) {
            depth -= 1;
            return [new RelaxNGParseError("Missing element " +
                    elementdef.names)];
        }
        if (elementdef.names && elementdef.names.indexOf(qName(node)) === -1) {
            depth -= 1;
            return [new RelaxNGParseError("Found " + node.nodeName +
                    " instead of " + elementdef.names + ".", node)];
        }
        // the right element was found, now parse the contents
        if (walker.firstChild()) {
            // currentNode now points to the first child node of this element
            error = validateTop(elementdef.e[1], walker, node);
            // there should be no content left
            while (walker.nextSibling()) {
                type = walker.currentNode.nodeType;
                if (!isWhitespace(walker.currentNode) && type !== 8) {
                    depth -= 1;
                    return [new RelaxNGParseError("Spurious content.",
                            walker.currentNode)];
                }
            }
            if (walker.parentNode() !== node) {
                depth -= 1;
                return [new RelaxNGParseError("Implementation error.")];
            }
        } else {
            error = validateTop(elementdef.e[1], walker, node);
        }
        depth -= 1;
        // move to the next node
        node = walker.nextSibling();
        return error;
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @param {string=} data
     * @return {Array.<RelaxNGParseError>}
     */
    function validateChoice(elementdef, walker, element, data) {
        // loop through child definitions and return if a match is found
        if (elementdef.e.length !== 2) {
            throw "Choice with wrong # of options: " + elementdef.e.length;
        }
        var node = walker.currentNode, err;
        // if the first option is empty, just check the second one for debugging
        // but the total choice is alwasy ok
        if (elementdef.e[0].name === "empty") {
            err = validateNonEmptyPattern(elementdef.e[1], walker, element,
                    data);
            if (err) {
                walker.currentNode = node;
            }
            return null;
        }
        err = validatePattern(elementdef.e[0], walker, element, data);
        if (err) {
            walker.currentNode = node;
            err = validateNonEmptyPattern(elementdef.e[1], walker, element,
                    data);
        }
        return err;
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateInterleave(elementdef, walker, element) {
        var l = elementdef.e.length, n = [l], err, i, todo = l,
            donethisround, node, subnode, e;
        // the interleave is done when all items are 'true' and no 
        while (todo > 0) {
            donethisround = 0;
            node = walker.currentNode;
            for (i = 0; i < l; i += 1) {
                subnode = walker.currentNode;
                if (n[i] !== true && n[i] !== subnode) {
                    e = elementdef.e[i];
                    err = validateNonEmptyPattern(e, walker, element);
                    if (err) {
                        walker.currentNode = subnode;
                        if (n[i] === undefined) {
                            n[i] = false;
                        }
                    } else if (subnode === walker.currentNode ||
                            // this is a bit dodgy, there should be a rule to
                            // see if multiple elements are allowed
                            e.name === "oneOrMore" ||
                            (e.name === "choice" &&
                            (e.e[0].name === "oneOrMore" ||
                             e.e[1].name === "oneOrMore"))) {
                        donethisround += 1;
                        n[i] = subnode; // no error and try this one again later
                    } else {
                        donethisround += 1;
                        n[i] = true; // no error and progress
                    }
                }
            }
            if (node === walker.currentNode && donethisround === todo) {
                return null;
            }
            if (donethisround === 0) {
                for (i = 0; i < l; i += 1) {
                    if (n[i] === false) {
                        return [new RelaxNGParseError(
                                "Interleave does not match.", element)];
                    }
                }
                return null;
            }
            todo = 0;
            for (i = 0; i < l; i += 1) {
                if (n[i] !== true) {
                    todo += 1;
                }
            }
        }
        return null;
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateGroup(elementdef, walker, element) {
        if (elementdef.e.length !== 2) {
            throw "Group with wrong # of members: " + elementdef.e.length;
        }
        //runtime.log(elementdef.e[0].name + " " + elementdef.e[1].name);
        return validateNonEmptyPattern(elementdef.e[0], walker, element) ||
                validateNonEmptyPattern(elementdef.e[1], walker, element);
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @return {Array.<RelaxNGParseError>}
     */
    function validateText(elementdef, walker, element) {
        var /**@type{Node}*/ node = walker.currentNode,
            /**@type{number}*/ type = node ? node.nodeType : 0,
            error = null;
        // find the next element, skip text nodes with only whitespace
        while (node !== element && type !== 3) {
            if (type === 1) {
                return [new RelaxNGParseError(
                        "Element not allowed here.", node)];
            }
            node = walker.nextSibling();
            type = node ? node.nodeType : 0;
        }
        walker.nextSibling();
        return null;
    }
    /**
     * @param elementdef
     * @param walker
     * @param {Element} element
     * @param {string=} data
     * @return {Array.<RelaxNGParseError>}
     */
    validateNonEmptyPattern = function validateNonEmptyPattern(elementdef,
                walker, element, data) {
        var name = elementdef.name, err = null;
        if (name === "text") {
            err = validateText(elementdef, walker, element);
        } else if (name === "data") {
            err = null; // data not implemented
        } else if (name === "value") {
            if (data !== elementdef.text) {
                err = [new RelaxNGParseError("Wrong value, should be '" +
                        elementdef.text + "', not '" + data + "'", element)];
            }
        } else if (name === "list") {
            err = null; // list not implemented
        } else if (name === "attribute") {
            err = validateAttribute(elementdef, walker, element);
        } else if (name === "element") {
            err = validateElement(elementdef, walker, element);
        } else if (name === "oneOrMore") {
            err = validateOneOrMore(elementdef, walker, element);
        } else if (name === "choice") {
            err = validateChoice(elementdef, walker, element, data);
        } else if (name === "group") {
            err = validateGroup(elementdef, walker, element);
        } else if (name === "interleave") {
            err = validateInterleave(elementdef, walker, element);
        } else {
            throw name + " not allowed in nonEmptyPattern.";
        }
        return err;
    };
    /**
     * Validate the elements pointed to by the TreeWalker
     * @param {!TreeWalker} walker
     * @param {!function(Array.<RelaxNGParseError>):undefined} callback
     * @return {undefined}
     */
    this.validate = function validate(walker, callback) {
        walker.currentNode = walker.root;
        var errors = validatePattern(start.e[0], walker, walker.root);
        callback(errors);
    };
    this.init = function init(start1, nsmap1) {
        start = start1;
        nsmap = nsmap1;
    };
};
