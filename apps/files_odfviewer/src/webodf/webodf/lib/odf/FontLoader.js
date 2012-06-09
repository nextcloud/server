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
/*jslint sub: true*/
/*global runtime, odf, core, document, xmldom*/
runtime.loadClass("core.Base64");
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");
/**
 * This class loads embedded fonts into the CSS 
 * @constructor
 **/
odf.FontLoader = (function () {
    "use strict";
    var style2CSS = new odf.Style2CSS(),
        xpath = new xmldom.XPath(),
        base64 = new core.Base64();
    /**
     * @param {!Element} fontFaceDecls
     * @return {!Object.<string,Object>}
     */
    function getEmbeddedFontDeclarations(fontFaceDecls) {
        var decls = {},
            fonts,
            i, font, name, uris, href;
        if (!fontFaceDecls) {
            return decls;
        }
        fonts = xpath.getODFElementsWithXPath(fontFaceDecls,
                    "style:font-face[svg:font-face-src]",
                    style2CSS.namespaceResolver);
        for (i = 0; i < fonts.length; i += 1) {
            font = fonts[i];
            name = font.getAttributeNS(style2CSS.namespaces["style"], "name");
            uris = xpath.getODFElementsWithXPath(font,
                "svg:font-face-src/svg:font-face-uri",
                style2CSS.namespaceResolver);
            if (uris.length > 0) {
                href = uris[0].getAttributeNS(style2CSS.namespaces["xlink"],
                        "href");
                decls[name] = {href: href};
            }
        }
        return decls;
    }
    function addFontToCSS(name, font, fontdata, stylesheet) {
        // hack: get the first stylesheet
        stylesheet = document.styleSheets[0];
        var rule = "@font-face { font-family: \"" + name + "\"; src: " +
            "url(data:application/x-font-ttf;charset=binary;base64," +
            base64.convertUTF8ArrayToBase64(fontdata) +
            ") format(\"truetype\"); }";
        try {
            stylesheet.insertRule(rule, stylesheet.cssRules.length);
        } catch (e) {
            runtime.log("Problem inserting rule in CSS: " + rule);
        }
    }
    function loadFontIntoCSS(embeddedFontDeclarations, zip, pos, stylesheet,
            callback) {
        var name, i = 0, n;
        for (n in embeddedFontDeclarations) {
            if (embeddedFontDeclarations.hasOwnProperty(n)) {
                if (i === pos) {
                    name = n;
                }
                i += 1;
            }
        }
        if (!name) {
            return callback();
        }
        zip.load(embeddedFontDeclarations[name].href, function (err, fontdata) {
            if (err) {
                runtime.log(err);
            } else {
                addFontToCSS(name, embeddedFontDeclarations[name], fontdata,
                    stylesheet);
            }
            return loadFontIntoCSS(embeddedFontDeclarations, zip, pos + 1,
                    stylesheet, callback);
        });
    }
    function loadFontsIntoCSS(embeddedFontDeclarations, zip, stylesheet) {
        loadFontIntoCSS(embeddedFontDeclarations, zip, 0, stylesheet,
            function () {});
    }
    /**
     * @constructor
     */
    odf.FontLoader = function FontLoader() {
        var self = this;
        /**
         * @param {!Element} fontFaceDecls
         * @param {!core.Zip} zip
         * @param {!StyleSheet} stylesheet
         * @return {undefined}
         */
        this.loadFonts = function (fontFaceDecls, zip, stylesheet) {
            var embeddedFontDeclarations = getEmbeddedFontDeclarations(
                    fontFaceDecls);
            loadFontsIntoCSS(embeddedFontDeclarations, zip, stylesheet);
        };
    };
    return odf.FontLoader;
}());
