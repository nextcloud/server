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
/*global odf: true, runtime: true*/
/**
 * @constructor
 */
odf.Formatting = function Formatting() {
    "use strict";
    var /**@type{odf.OdfContainer}*/ odfContainer,
        /**@type{odf.StyleInfo}*/ styleInfo = new odf.StyleInfo();

    /**
     * Class that iterates over all elements that are part of the range.
     * @constructor
     * @param {!Range} range
     * @return {undefined}
     */
    function RangeElementIterator(range) {
        /**
         * @param {Node} parent
         * @param {!number} n
         * @return {Node}
         */
        function getNthChild(parent, n) {
            var c = parent && parent.firstChild;
            while (c && n) {
                c = c.nextSibling;
                n -= 1;
            }
            return c;
        }
        var start = getNthChild(range.startContainer, range.startOffset),
            end = getNthChild(range.endContainer, range.endOffset),
            current = start;
        /**
         * @return {Element|null}
         */
        this.next = function () {
            var c = current;
            if (c === null) {
                return c;
            }
            return null;
        };
    }

    /**
     * @param {!Element} element
     * @return {Element}
     */
    function getParentStyle(element) {
        var n = element.firstChild, e;
        if (n.nodeType === 1) { // Element
            e = /**@type{Element}*/(n);
            return e;
        }
        return null;
    }
    /**
     * @param {!Range} range
     * @return {!Array.<!Element>}
     */
    function getParagraphStyles(range) {
        var iter = new RangeElementIterator(range), e, styles = [];
        e = iter.next();
        while (e) {
            if (styleInfo.canElementHaveStyle("paragraph", e)) {
                styles.push(e);
            }
        }
        return styles;
    }

    /**
     * @param {!odf.OdfContainer} odfcontainer
     * @return {undefined}
     */
    this.setOdfContainer = function (odfcontainer) {
        odfContainer = odfcontainer;
    };
    /**
     * Return true if all parts of the selection are bold.
     * @param {!Array.<!Range>} selection
     * @return {!boolean}
     */
    this.isCompletelyBold = function (selection) {
        return false;
    };
    /**
     * Get the alignment or undefined if no uniform alignment is found
     * @param {!Array.<!Range>} selection
     * @return {!string|undefined}
     */
    this.getAlignment = function (selection) {
        var styles = this.getParagraphStyles(selection), i, l = styles.length;
        return undefined;
    };
    /**
     * Get the list of paragraph styles that covered by the current selection.
     * @param {!Array.<!Range>} selection
     * @return {!Array.<Element>}
     */
    this.getParagraphStyles = function (selection) {
        var i, j, s, styles = [];
        for (i = 0; i < selection.length; i += 0) {
            s = getParagraphStyles(selection[i]);
            for (j = 0; j < s.length; j += 1) {
                if (styles.indexOf(s[j]) === -1) {
                    styles.push(s[j]);
                }
            }
        }
        return styles;
    };
    /**
     * Get the list of text styles that are covered by the current selection.
     * @param {!Array.<!Range>} selection
     * @return {!Array.<Element>}
     */
    this.getTextStyles = function (selection) {
        return [];
    };
};
