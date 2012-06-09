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
 * @class
 * A cursor is a dom node that visually represents a cursor in a DOM tree.
 * It should stay synchronized with the selection in the document. When
 * there is only one collapsed selection range, a cursor should be shown at
 * that point.
 *
 * Putting the cursor in the DOM tree modifies the DOM, so care should be taken
 * to keep the selection consistent. If e.g. a selection is drawn over the
 * cursor, and the cursor is updated to the selection, the cursor is removed
 * from the DOM because the selection is not collapsed. This means that the
 * offsets of the selection may have to be changed.
 *
 * When the selection is collapsed, the cursor is placed after the point of the
 * selection and the selection will stay valid. However, if the cursor was
 * placed in the DOM tree and was counted in the offset, the offset in the
 * selection should be decreased by one.
 *
 * Even when the selection allows for a cursor, it might be desireable to hide
 * the cursor by not letting it be part of the DOM.
 *
 * @constructor
 * @param {Selection} selection The selection to which the cursor corresponds
 * @param {Document} document The document in which the cursor is placed
 */
core.Cursor = function Cursor(selection, document) {
    "use strict";
    var cursorns,
        cursorNode;
    cursorns = 'urn:webodf:names:cursor';
    cursorNode = document.createElementNS(cursorns, 'cursor');

    function putCursorIntoTextNode(container, offset) {
        var len, ref, textnode, parent;
        parent = container.parentNode;
        if (offset === 0) {
            parent.insertBefore(cursorNode, container);
        } else if (offset === container.length) {
            parent.appendChild(cursorNode);
        } else {
            len = container.length;
            ref = container.nextSibling;
            textnode = document.createTextNode(
                container.substringData(offset, len)
            );
            container.deleteData(offset, len);
            if (ref) {
                parent.insertBefore(textnode, ref);
            } else {
                parent.appendChild(textnode);
            }
            parent.insertBefore(cursorNode, textnode);
        }
    }
    function putCursorIntoContainer(container, offset) {
        var node;
        node = container.firstChild;
        while (node && offset) {
            node = node.nextSibling;
            offset -= 1;
        }
        container.insertBefore(cursorNode, node);
    }
    function getPotentialParentOrNode(parent, node) {
        var n = node;
        while (n && n !== parent) {
            n = n.parentNode;
        }
        return n || node;
    }
    function removeCursorFromSelectionRange(range, cursorpos) {
        var cursorParent, start, end;
        cursorParent = cursorNode.parentNode;
        start = getPotentialParentOrNode(cursorNode, range.startContainer);
        end = getPotentialParentOrNode(cursorNode, range.endContainer);
        if (start === cursorNode) {
            range.setStart(cursorParent, cursorpos);
        } else if (start === cursorParent &&
                range.startOffset > cursorpos) {
            range.setStart(cursorParent, range.startOffset - 1);
        }
        if (range.endContainer === cursorNode) {
            range.setEnd(cursorParent, cursorpos);
        } else if (range.endContainer === cursorParent &&
                range.endOffset > cursorpos) {
            range.setEnd(cursorParent, range.endOffset - 1);
        }
    }
    function adaptRangeToMergedText(range, prev, textnodetomerge, cursorpos) {
        var diff = prev.length - textnodetomerge.length;
        if (range.startContainer === textnodetomerge) {
            range.setStart(prev, diff + range.startOffset);
        } else if (range.startContainer === prev.parentNode &&
                range.startOffset === cursorpos) {
            range.setStart(prev, diff);
        }
        if (range.endContainer === textnodetomerge) {
            range.setEnd(prev, diff + range.endOffset);
        } else if (range.endContainer === prev.parentNode &&
                range.endOffset === cursorpos) {
            range.setEnd(prev, diff);
        }
    }
    function removeCursor() {
        // if the cursor is part of a selection, the selection must be adapted
        var i, cursorpos, node, textnodetoremove, range;
        // if the cursor has no parent, it is already not part of the document
        // tree
        if (!cursorNode.parentNode) {
            return;
        }
        // find the position of the cursor in its parent
        cursorpos = 0;
        node = cursorNode.parentNode.firstChild;
        while (node && node !== cursorNode) {
            cursorpos += 1;
            node = node.nextSibling;
        }
        // Check if removing the node will result in a merge of texts.
        // This will happen if the cursor is between two text nodes.
        // The text of the text node after the cursor is put in the text node
        // before the cursor. The latter node is removed after the selection
        // has been adapted.
        if (cursorNode.previousSibling &&
                cursorNode.previousSibling.nodeType === 3 && // TEXT_NODE
                cursorNode.nextSibling &&
                cursorNode.nextSibling.nodeType === 3) { // TEXT_NODE
            textnodetoremove = cursorNode.nextSibling;
            cursorNode.previousSibling.appendData(textnodetoremove.nodeValue);
        }
        // remove the node from the selections
        for (i = 0; i < selection.rangeCount; i += 1) {
            removeCursorFromSelectionRange(selection.getRangeAt(i), cursorpos);
        }
        // merge the texts that surround the cursor
        if (textnodetoremove) {
            for (i = 0; i < selection.rangeCount; i += 1) {
                adaptRangeToMergedText(selection.getRangeAt(i),
                       cursorNode.previousSibling, textnodetoremove, cursorpos);
            }
            textnodetoremove.parentNode.removeChild(textnodetoremove);
        }
        cursorNode.parentNode.removeChild(cursorNode);
    }
    // put the cursor at a particular position
    function putCursor(container, offset) {
        if (container.nodeType === 3) { // TEXT_NODE
            putCursorIntoTextNode(container, offset);
        } else if (container.nodeType !== 9) { // DOCUMENT_NODE
            putCursorIntoContainer(container, offset);
        }
    }
    /**
     * Obtain the node representing the cursor.
     * @return {Element}
     */
    this.getNode = function () {
        return cursorNode;
    };
    /**
     * Synchronize the cursor with the current selection.
     * If there is a single collapsed selection range, the cursor will be placed
     * there. If not, the cursor will be removed from the document tree.
     * @return {undefined}
     */
    this.updateToSelection = function () {
        var range;
        removeCursor();
        if (selection.focusNode) {
            putCursor(selection.focusNode, selection.focusOffset);
        }
    };
    /**
     * Remove the cursor from the document tree.
     * @return {undefined}
     */
    this.remove = function () {
        removeCursor();
    };
};
