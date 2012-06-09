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
/*global core: true, Node: true*/
/**
 * A simple walker that allows finegrained stepping through the DOM.
 * It does not support node filtering.
 * TODO: write a position walker that uses a treewalker
 * @constructor
 * @param {!Node} node
 */
core.PointWalker = function PointWalker(node) {
    "use strict";
    var currentNode = node,
        before = null, // node before the point
        after = node && node.firstChild, // node after the point
        root = node,
        pos = 0;

    /**
     * @param {!Node} node
     * @return {!number}
     */
    function getPosition(node) {
        var /**@type{!number}*/ p = -1,
            /**@type{Node}*/ n = node;
        while (n) {
            n = n.previousSibling;
            p += 1;
        }
        return p;
    }
    /**
     * Move the walker to the point given by @p node and @p position.
     * @param {!Element} node must be the root of this walker or part of the
     *                   tree of this walker.
     * @param {!number} position must be a valid position in @node.
     **/
    this.setPoint = function (node, position) {
        currentNode = node;
        pos = position;
        if (currentNode.nodeType === 3) { // Node.TEXT_NODE
            after = null;
            before = null;
        } else {
            after = currentNode.firstChild;
            while (position) {
                position -= 1;
                after = after.nextSibling;
            }
            if (after) {
                before = after.previousSibling;
            } else {
                before = currentNode.lastChild;
            }
        }
    };
    /**
     * @return {!boolean}
     */
    this.stepForward = function () {
        var len;
        // if this is a text node, move to the next position in the text
        if (currentNode.nodeType === 3) { // TEXT_NODE
            if (typeof currentNode.nodeValue.length === "number") {
                len = currentNode.nodeValue.length;
            } else {
                len = currentNode.nodeValue.length();
            }
            if (pos < len) {
                pos += 1;
                return true;
            }
        }
        if (after) {
            if (after.nodeType === 1) { // ELEMENT_NODE
                currentNode = after;
                before = null;
                after = currentNode.firstChild;
                pos = 0;
            } else if (after.nodeType === 3) { // TEXT_NODE
                currentNode = after;
                before = null;
                after = null;
                pos = 0;
            } else {
                before = after;
                after = after.nextSibling;
                pos += 1;
            }
            return true;
        }
        if (currentNode !== root) {
            before = currentNode;
            after = before.nextSibling;
            currentNode = currentNode.parentNode;
            pos = getPosition(before) + 1;
            return true;
        }
        return false;
    };
    /**
     * @return {!boolean}
     */
    this.stepBackward = function () {
        // if this is a text node, move to the next position in the text
        if (currentNode.nodeType === 3) { // TEXT_NODE
            if (pos > 0) {
                pos -= 1;
                return true;
            }
        }
        if (before) {
            if (before.nodeType === 1) { // ELEMENT_NODE
                currentNode = before;
                before = currentNode.lastChild;
                after = null;
                pos = getPosition(before) + 1;
            } else if (before.nodeType === 3) { // TEXT_NODE
                currentNode = before;
                before = null;
                after = null;
                if (typeof currentNode.nodeValue.length === "number") {
                    pos = currentNode.nodeValue.length;
                } else {
                    pos = currentNode.nodeValue.length();
                }
            } else {
                after = before;
                before = before.previousSibling;
                pos -= 1;
            }
            return true;
        }
        if (currentNode !== root) {
            after = currentNode;
            before = after.previousSibling;
            currentNode = currentNode.parentNode;
            pos = getPosition(after);
            return true;
        }
        return false;
    };
    /**
     * @return {?Node}
     */
    this.node = function () {
        return currentNode;
    };
    /**
     * @return {!number}
     */
    this.position = function () {
        return pos;
    };
    /**
     * @return {?Node}
     */
    this.precedingSibling = function () {
        return before;
    };
    /**
     * @return {?Node}
     */
    this.followingSibling = function () {
        return after;
    };
};
