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
runtime.loadClass("core.Cursor");
/**
 * This class modifies the selection in different ways.
 * @constructor
 * @param {Selection} selection
 * @param {!core.PointWalker} pointWalker
 * @return {!gui.SelectionMover}
 */
gui.SelectionMover = function SelectionMover(selection, pointWalker) {
    "use strict";
    var doc = pointWalker.node().ownerDocument,
        cursor = new core.Cursor(selection, doc);
    /**
     * Return the last range in the selection. Create one if the selection is
     * empty.
     */
    function getActiveRange(node) {
        var range;
        if (selection.rangeCount === 0) {
            selection.addRange(node.ownerDocument.createRange());
        }
        return selection.getRangeAt(selection.rangeCount - 1);
    }
    function setStart(node, offset) {
        // selection interface is cumbersome and in Chrome it is buggy
        // as a workaround all ranges are removed. The last one is updated and
        // all ranges are placed back
        var ranges = [], i, range;
        for (i = 0; i < selection.rangeCount; i += 1) {
            ranges[i] = selection.getRangeAt(i);
        }
        selection.removeAllRanges();
        if (ranges.length === 0) {
            ranges[0] = node.ownerDocument.createRange();
        }
        ranges[ranges.length - 1].setStart(pointWalker.node(),
                pointWalker.position());
        for (i = 0; i < ranges.length; i += 1) {
            selection.addRange(ranges[i]);
        }
    }
    function doMove(extend, move) {
        if (selection.rangeCount === 0) {
            return;
        }
        var range = selection.getRangeAt(0),
            /**@type{Element}*/ element;
        if (!range.startContainer || range.startContainer.nodeType !== 1) {
            return;
        }
        element = /**@type{!Element}*/(range.startContainer);
        pointWalker.setPoint(element, range.startOffset);
        move();
        setStart(pointWalker.node(), pointWalker.position());
    }
    function doMoveForward(extend, move) {
        if (selection.rangeCount === 0) {
            return;
        }
        move();
        var range = selection.getRangeAt(0),
            /**@type{Element}*/ element;
        if (!range.startContainer || range.startContainer.nodeType !== 1) {
            return;
        }
        element = /**@type{!Element}*/(range.startContainer);
        pointWalker.setPoint(element, range.startOffset);
    }
/*
    function fallbackMoveLineUp() {
        // put an element at the current position and call
        // pointWalker.stepForward until the y position increases and x position
        // is comparable to the previous one
        cursor.updateToSelection();
        // retrieve cursor x and y position, then move selection/cursor left
        // until, y offset is less and x offset about equal
        var rect = cursor.getNode().getBoundingClientRect(),
            x = rect.left,
            y = rect.top,
            arrived = false,
            allowedSteps = 200;
        while (!arrived && allowedSteps) {
            allowedSteps -= 1;
            cursor.remove();
            pointWalker.setPoint(selection.focusNode, selection.focusOffset);
            pointWalker.stepForward();
        moveCursor(walker.node(), walker.position());
            moveCursorLeft();
            rect = cursor.getNode().getBoundingClientRect();
            arrived = rect.top !== y && rect.left < x;
        }
    }
*/
    function moveCursor(node, offset, selectMode) {
        if (selectMode) {
            selection.extend(node, offset);
        } else {
            selection.collapse(node, offset);
        }
        cursor.updateToSelection();
    }
    function moveCursorLeft() {
        var /**@type{Element}*/ element;
        if (!selection.focusNode || selection.focusNode.nodeType !== 1) {
            return;
        }
        element = /**@type{!Element}*/(selection.focusNode);
        pointWalker.setPoint(element, selection.focusOffset);
        pointWalker.stepBackward();
        moveCursor(pointWalker.node(), pointWalker.position(), false);
    }
    function moveCursorRight() {
        cursor.remove();
        var /**@type{Element}*/ element;
        if (!selection.focusNode || selection.focusNode.nodeType !== 1) {
            return;
        }
        element = /**@type{!Element}*/(selection.focusNode);
        pointWalker.setPoint(element, selection.focusOffset);
        pointWalker.stepForward();
        moveCursor(pointWalker.node(), pointWalker.position(), false);
    }
    function moveCursorUp() {
        // retrieve cursor x and y position, then move selection/cursor left
        // until, y offset is less and x offset about equal
        var rect = cursor.getNode().getBoundingClientRect(),
            x = rect.left,
            y = rect.top,
            arrived = false,
            left = 200;
        while (!arrived && left) {
            left -= 1;
            moveCursorLeft();
            rect = cursor.getNode().getBoundingClientRect();
            arrived = rect.top !== y && rect.left < x;
        }
    }
    function moveCursorDown() {
        // retrieve cursor x and y position, then move selection/cursor right
        // until, x offset is less
        cursor.updateToSelection();
        var rect = cursor.getNode().getBoundingClientRect(),
            x = rect.left,
            y = rect.top,
            arrived = false,
            left = 200;
        while (!arrived) {
            left -= 1;
            moveCursorRight();
            rect = cursor.getNode().getBoundingClientRect();
            arrived = rect.top !== y && rect.left > x;
        }
//alert(left + " " + y + " " + x + " " + rect.top + " " + rect.left);
    }
    /**
     * Move selection forward one point.
     * @param {boolean} extend true if range is to be expanded from the current
     *                         point
     * @return {undefined}
     **/
    this.movePointForward = function (extend) {
        doMove(extend, pointWalker.stepForward);
    };
    this.movePointBackward = function (extend) {
        doMove(extend, pointWalker.stepBackward);
    };
    this.moveLineForward = function (extend) {
        if (selection.modify) {
            // TODO add a way to 
            selection.modify(extend ? "extend" : "move", "forward", "line");
        } else {
            doMove(extend, moveCursorDown);
        }
    };
    this.moveLineBackward = function (extend) {
        if (selection.modify) {
            selection.modify(extend ? "extend" : "move", "backward", "line");
        } else {
            doMove(extend, function () {
            });
        }
    };
    return this;
};
