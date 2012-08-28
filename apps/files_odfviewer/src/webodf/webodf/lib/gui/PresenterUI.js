/**
 * Copyright (C) 2011 KO GmbH - Tobias Hintze
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

/*global runtime, gui, odf, core, xmldom, document, window*/
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");

gui.PresenterUI = (function () {
    "use strict";
    var s2css = new odf.Style2CSS(),
        xpath = new xmldom.XPath(),
        nsResolver = s2css.namespaceResolver;

    return function PresenterUI(odf_element) {
        var self = this;

        self.setInitialSlideMode = function () {
            self.startSlideMode('single');
        };

        self.keyDownHandler = function (ev) {
            if (ev.target.isContentEditable) { return; }
            if (ev.target.nodeName === 'input') { return; }
            switch (ev.keyCode) {
            case 84: // t - hide/show toolbar
                self.toggleToolbar();
                break;
            case 37: // left
            case 8: // left
                self.prevSlide();
                break;
            case 39:  // right
            case 32:  // space
                self.nextSlide();
                break;
            case 36: // pos1
                self.firstSlide();
                break;
            case 35: // end
                self.lastSlide();
                break;
            }
        };

        self.root = function () { return self.odf_canvas.odfContainer().rootElement; };

        self.firstSlide = function () { self.slideChange(function (old, pc) { return 0; }); };
        self.lastSlide = function () { self.slideChange(function (old, pc) { return pc - 1; }); };

        self.nextSlide = function () {
            self.slideChange(function (old, pc) { return old + 1 < pc ? old + 1 : -1; });
        };
        self.prevSlide = function () {
            self.slideChange(function (old, pc) { return old < 1 ? -1 : old - 1; });
        };
        // indexChanger gets (idx,pagecount) as parameter and returns the new index
        self.slideChange = function (indexChanger) {
            var pages = self.getPages(self.odf_canvas.odfContainer().rootElement),
                last = -1,
                i = 0,
                newidx,
                pagelist;
            pages.forEach(function (tuple) {
                var name = tuple[0],
                    node = tuple[1];
                if (node.hasAttribute('slide_current')) {
                    last = i;
                    node.removeAttribute('slide_current');
                }
                i += 1;
            });
            newidx = indexChanger(last, pages.length);
            if (newidx === -1) { newidx = last; }
            pages[newidx][1].setAttribute('slide_current', '1');
            pagelist = document.getElementById('pagelist');
            pagelist.selectedIndex = newidx;
            // FIXME this needs to become a sane callback/listener mechanism
            // (and the mode probably a class/instance..)
            if (self.slide_mode === 'cont') {
                window.scrollBy(0, pages[newidx][1].getBoundingClientRect().top - 30);
            }
        };

        self.selectSlide = function (idx) {
            self.slideChange(function (old, pc) {
                if (idx >= pc) { return -1; }
                if (idx < 0) { return -1; }
                return idx;
            });
        };

        // make one specific slide visible in cont-mode
        self.scrollIntoContView = function (idx) {
            var pages = self.getPages(self.odf_canvas.odfContainer().rootElement);
            if (pages.length === 0) { return; }
            /*
            if (false) {
                // works in chrome
                pages[idx][1].scrollIntoView();
            } else {
            */
                // works in ff
            window.scrollBy(0, pages[idx][1].getBoundingClientRect().top - 30);
            /*}*/
        };

        // return a list of tuples (pagename, pagenode)
        self.getPages = function (root) {
            var pagenodes = root.getElementsByTagNameNS(nsResolver('draw'), 'page'),
                pages  = [],
                i;
            for (i = 0; i < pagenodes.length; i += 1) {
                pages.push([
                    pagenodes[i].getAttribute('draw:name'),
                    pagenodes[i]
                ]);
            }
            return pages;
        };

        // fill a html-select with options, one option per page in odf (odp)
        self.fillPageList = function (odfdom_root, html_select) {
            var pages = self.getPages(odfdom_root),
                i,
                html_option,
                res,
                page_denom;

            // empty the pagelist
            while (html_select.firstChild) {
                html_select.removeChild(html_select.firstChild);
            }

            // populate it
            for (i = 0; i < pages.length; i += 1) {
                html_option = document.createElement('option');
                res = xpath.getODFElementsWithXPath(pages[i][1],
                    './draw:frame[@presentation:class="title"]//draw:text-box/text:p',
                    xmldom.XPath);
                page_denom = (res.length > 0) ? res[0].textContent : pages[i][0];
                html_option.textContent = (i + 1) + ": " + page_denom;
                html_select.appendChild(html_option);
            }
        };

        self.startSlideMode = function (mode) {
            var pagelist = document.getElementById('pagelist'),
                css = self.odf_canvas.slidevisibilitycss().sheet;
            self.slide_mode = mode;
            while (css.cssRules.length > 0) { css.deleteRule(0); }
            // start on slide 0
            self.selectSlide(0);
            if (self.slide_mode === 'single') {
                css.insertRule("draw|page { position:fixed; left:0px;top:30px; z-index:1; }", 0);
                css.insertRule("draw|page[slide_current]  { z-index:2;}", 1);
                css.insertRule("draw|page  { -webkit-transform: scale(1);}", 2);
                self.fitToWindow();
                window.addEventListener('resize', self.fitToWindow, false);

            } else if (self.slide_mode === 'cont') {
                window.removeEventListener('resize', self.fitToWindow, false);
            }

            self.fillPageList(self.odf_canvas.odfContainer().rootElement, pagelist);
        };

        // toggle (show/hide) toolbar
        self.toggleToolbar = function () {
            var css, found, i;
            css = self.odf_canvas.slidevisibilitycss().sheet;
            found = -1;
            for (i = 0; i < css.cssRules.length; i += 1) {
                if (css.cssRules[i].cssText.substring(0, 8) === ".toolbar") {
                    found = i;
                    break;
                }
            }
            if (found > -1) {
                css.deleteRule(found);
            } else {
                css.insertRule(".toolbar { position:fixed; left:0px;top:-200px; z-index:0; }", 0);
            }
        };

        // adapt css-transform to window-size
        self.fitToWindow = function () {
            function ruleByFactor(f) {
                return "draw|page { \n" +
                    "-moz-transform: scale(" + f + "); \n" +
                    "-moz-transform-origin: 0% 0%; " +
                    "-webkit-transform-origin: 0% 0%; -webkit-transform: scale(" + f + "); " +
                    "-o-transform-origin: 0% 0%; -o-transform: scale(" + f + "); " +
                    "-ms-transform-origin: 0% 0%; -ms-transform: scale(" + f + "); " +
                    "}";
            }
            var pages = self.getPages(self.root()),
                factorVert = ((window.innerHeight - 40) / pages[0][1].clientHeight),
                factorHoriz = ((window.innerWidth - 10) / pages[0][1].clientWidth),
                factor = factorVert < factorHoriz ? factorVert : factorHoriz,
                css = self.odf_canvas.slidevisibilitycss().sheet;
            css.deleteRule(2);
            css.insertRule(ruleByFactor(factor), 2);
        };

        self.load = function (url) {
            self.odf_canvas.load(url);
        };

        self.odf_element = odf_element;
        self.odf_canvas = new odf.OdfCanvas(self.odf_element);
        self.odf_canvas.addListener("statereadychange", self.setInitialSlideMode);
        self.slide_mode = 'undefined';
        document.addEventListener('keydown', self.keyDownHandler, false);
    };
}());

