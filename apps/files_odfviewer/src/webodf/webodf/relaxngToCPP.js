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
runtime.loadClass("xmldom.RelaxNGParser");

var nsmap = {
        "http://purl.org/dc/elements/1.1/": "purl",
        "http://www.w3.org/1998/Math/MathML": "mathml",
        "http://www.w3.org/1999/xhtml": "xhtml",
        "http://www.w3.org/1999/xlink": "xlink",
        "http://www.w3.org/2002/xforms": "xforms",
        "http://www.w3.org/2003/g/data-view#": "dv",
        "http://www.w3.org/XML/1998/namespace": "xmlns",
        "urn:oasis:names:tc:opendocument:xmlns:animation:1.0": "animation",
        "urn:oasis:names:tc:opendocument:xmlns:chart:1.0": "chart",
        "urn:oasis:names:tc:opendocument:xmlns:config:1.0": "config",
        "urn:oasis:names:tc:opendocument:xmlns:database:1.0": "database",
        "urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0": "datastyle",
        "urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0": "dr3d",
        "urn:oasis:names:tc:opendocument:xmlns:drawing:1.0": "drawing",
        "urn:oasis:names:tc:opendocument:xmlns:form:1.0": "form",
        "urn:oasis:names:tc:opendocument:xmlns:meta:1.0": "meta",
        "urn:oasis:names:tc:opendocument:xmlns:office:1.0": "office",
        "urn:oasis:names:tc:opendocument:xmlns:presentation:1.0": "presentation",
        "urn:oasis:names:tc:opendocument:xmlns:script:1.0": "script",
        "urn:oasis:names:tc:opendocument:xmlns:smil-compatible:1.0": "smilc",
        "urn:oasis:names:tc:opendocument:xmlns:style:1.0": "style",
        "urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0": "svgc",
        "urn:oasis:names:tc:opendocument:xmlns:table:1.0": "table",
        "urn:oasis:names:tc:opendocument:xmlns:text:1.0": "text",
        "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0": "xslfoc"
    },
    typemap = {
        "string": "const QString&",
        "NCName": "const QString&",
        "date": "const QString&",
        "time": "const QString&",
        "dateTime": "const QString&",
        "duration": "const QString&",
        "anyURI": "const QString&",
        "ID": "const QString&",
        "IDREF": "const QString&",
        "IDREFS": "const QString&",
        "QName": "const QString&",
        "token": "const QString&",
        "language": "const QString&",
        "positiveInteger": "quint32",
        "nonNegativeInteger": "quint32",
        "integer": "qint32",
        "decimal": "double"
    },
    args = arguments,
    relaxngurl = args[1],
    parser = new xmldom.RelaxNGParser(relaxngurl);

function out(string) {
    "use strict";
    runtime.log(string);
}
function toCamelCase(s) {
    "use strict";
    var str = "", i, up = true;
    for (i = 0; i < s.length; i += 1) {
        if (up) {
            str += s.substr(i, 1).toUpperCase();
        } else {
            str += s.substr(i, 1);
        }
        up = false;
        while (/\W/.test(s.substr(i + 1, 1))) {
            up = true;
            i += 1;
        }
    }
    return str;
}
function getName(e) {
    "use strict";
    return toCamelCase(nsmap[e.a.ns]) + toCamelCase(e.text);
}
function getNames(e, names) {
    "use strict";
    if (e.name === "name") {
        names.push(e);
    } else if (e.name === "choice") {
        getNames(e.e[0], names);
        getNames(e.e[1], names);
    }
}
function parseAttributes(e, att) {
    "use strict";
    var i, name;
    if (e.name === "choice" || e.name === "interleave"
            || e.name === "group") {
        for (i = 0; i < e.e.length; i += 1) {
            parseAttributes(e.e[i], att);
        }
    } else if (e.name === "value") {
        att.values.push(e.text);
    } else if (e.name === "data") {
        att.types.push(e.a.type);
    } else if (e.name === "list") {
        name = null; // todo 
    } else if (e.name === "empty") {
        att.empty = true;
    } else {
        runtime.log("OOPS " + e.name);
        throw null;
    }
}
function writeAttributeSetter(name, type, a) {
    "use strict";
    var i, s = "";
    out("    /**");
    if (a.optional) {
        out("     * Set optional attribute " + a.nsname + ".");
    } else {
        out("     * Set required attribute " + a.nsname + ".");
    }
    if (a.values.length > 0) {
        s = "Choose one of these values: '" + a.values[0] + "'";
        for (i = 1; i < a.values.length; i += 1) {
            s += ", '" + a.values[i] + "'";
        }
        out("     * " + s + ".");
    }
    out("     */");
    out("    inline void write" + name + "(" + type + " value) {");
    out("        xml->addAttribute(\"" + a.nsname + "\", value);");
    out("    }");
}
function writeAttribute(name, a) {
    "use strict";
    if (!a.optional) {
        return;
    }
    var i, type, done = {}, needfallback = true;
    for (i = 0; i < a.types.length; i += 1) {
        needfallback = false;
        type = typemap[a.types[i]] || a.types[i];
        if (!done.hasOwnProperty(type)) {
            done[type] = 1;
            writeAttributeSetter(name, type, a);
        }
    }
    if (a.values.indexOf("true") !== -1 && a.values.indexOf("false") !== -1 &&
            done.hasOwnProperty("bool")) {
        needfallback = false;
        writeAttributeSetter(name, "bool", a);
    }
    if (needfallback) {
        writeAttributeSetter(name, "const QString&", a);
    }
}
function writeOptionalAttributes(atts) {
    "use strict";
    var name;
    for (name in atts) {
        if (atts.hasOwnProperty(name)) {
            writeAttribute(name, atts[name]);
        }
    }
}
function writeFixedRequiredAttributes(atts) {
    "use strict";
    var name, a;
    for (name in atts) {
        if (atts.hasOwnProperty(name)) {
            a = atts[name];
            if (!a.optional && a.types.length === 0 && a.values.length === 1) {
                 out("        xml->addAttribute(\"" + a.nsname + "\", \"" +
                     a.values[0] + "\");");
            }
        }
    }
}
function getRequiredAttributeArguments(atts) {
    "use strict";
    var name, a, s = "", type;
    for (name in atts) {
        if (atts.hasOwnProperty(name)) {
            a = atts[name];
            if (!a.optional && (a.types.length > 0 || a.values.length !== 1)) {
                type = typemap[a.types[0]] || a.types[0] || "const QString&";
                if (s) {
                    s += ", ";
                }
                s += type + " " + name.toLowerCase();
            }
        }
    }
    return s;
}
function getRequiredAttributeCall(atts) {
    "use strict";
    var name, a, s = "";
    for (name in atts) {
        if (atts.hasOwnProperty(name)) {
            a = atts[name];
            if (!a.optional && (a.types.length > 0 || a.values.length !== 1)) {
                if (s) {
                    s += ", ";
                }
                s += name.toLowerCase();
            }
        }
    }
    return s;
}
function writeRequiredAttributesSetters(atts) {
    "use strict";
    var name, a;
    for (name in atts) {
        if (atts.hasOwnProperty(name)) {
            a = atts[name];
            if (!a.optional && (a.types.length > 0 || a.values.length !== 1)) {
                out("        xml->addAttribute(\"" + a.nsname + "\", " +
                    name.toLowerCase() + ");");
            }
        }
    }
}
function writeMembers(e, atts, optional) {
    "use strict";
    var ne, nsname, i, name, names;
    if (e.name === "element") {
        name = null;
    } else if (e.name === "attribute") {
        names = [];
        getNames(e.e[0], names);
        for (i = 0; i < names.length; i += 1) {
            ne = names[i];
            name = getName(ne);
            if (!atts.hasOwnProperty(name)) {
                nsname = nsmap[ne.a.ns] + ":" + ne.text;
                atts[name] = {
                    nsname: nsname,
                    values: [],
                    types: [],
                    optional: optional,
                    empty: false
                };
            }
            parseAttributes(e.e[1], atts[name]);
        }
    } else if (e.name === "choice") {
        for (i = 0; i < e.e.length; i += 1) {
            writeMembers(e.e[i], atts, true);
        }
    } else if (e.name === "interleave" || e.name === "group") {
        for (i = 0; i < e.e.length; i += 1) {
            writeMembers(e.e[i], atts, optional);
        }
    } else if (e.name === "oneOrMore") {
        writeMembers(e.e[0], atts, optional);
    } else if (e.name === "value") {
        name = null; // todo 
    } else if (e.name === "data") {
        name = null; // todo 
    } else if (e.name === "text") {
        out("    void addTextNode(const QString& str) { xml->addTextNode(str); }");
    } else if (e.name === "empty") {
        name = null; // todo 
    } else {
        runtime.log("OOPS " + e.name);
        throw null;
    }
}
function defineClass(e, parents, children) {
    "use strict";
    var c, p, i,
        ne = e.e[0],
        nsname = nsmap[ne.a.ns] + ":" + ne.text,
        name = ne.cppname, atts = {};
    out("/**");
    out(" * Serialize a <" + nsname + "> element.");
    out(" */");
    out("class " + name + "Writer {");
    for (c in children) {
        if (children.hasOwnProperty(c) && c !== name) {
            out("friend class " + c + "Writer;");
        }
    }
    out("public:");
    writeMembers(e.e[1], atts, false);
    writeOptionalAttributes(atts);
    e.requiredAttributes = getRequiredAttributeArguments(atts);
    e.requiredAttributeCall = getRequiredAttributeCall(atts);
    out("private:");
    out("    inline void start(" + e.requiredAttributes + ") {");
    out("        xml->startElement(\"" + nsname + "\");");
    if (e.requiredAttributes) {
        e.requiredAttributes = ", " + e.requiredAttributes;
    }
    writeFixedRequiredAttributes(atts);
    writeRequiredAttributesSetters(atts);
    out("    }");
    out("public:");
    out("    KoXmlWriter* const xml;");
    for (p in parents) {
        if (parents.hasOwnProperty(p)) {
            out("    inline explicit " + name + "Writer(const " + p +
                    "Writer& p" + e.requiredAttributes + ");");
        }
    }
    out("    inline explicit " + name + "Writer(KoXmlWriter* xml_" +
            e.requiredAttributes +
            ") :xml(xml_) { start(" + e.requiredAttributeCall + "); }");
    out("    void end() { xml->endElement(); }");
    out("    void operator=(const " + name + "Writer&) { }");
    out("};");
}
function defineConstructors(e, parents) {
    "use strict";
    var p,
        ne = e.e[0],
        nsname = nsmap[ne.a.ns] + ":" + ne.text,
        name = ne.cppname;
    for (p in parents) {
        if (parents.hasOwnProperty(p)) {
            out(name + "Writer::" + name + "Writer(const " + p +
                "Writer& p" + e.requiredAttributes +
                ") :xml(p.xml) { start(" + e.requiredAttributeCall + "); }");
        }
    }
}
function getChildren(e, children) {
    "use strict";
    var name, i, names;
    if (e.name === "element") {
        names = [];
        getNames(e.e[0], names);
        for (i = 0; i < names.length; i += 1) {
            children[names[i].cppname] = 1;
        }
    } else if (e.name === "choice" || e.name === "interleave"
            || e.name === "group") {
        for (i = 0; i < e.e.length; i += 1) {
            getChildren(e.e[i], children);
        }
    } else if (e.name === "oneOrMore") {
        getChildren(e.e[0], children);
    } else if (e.name === "attribute" || e.name === "value" ||
            e.name === "data" || e.name === "text" || e.name === "empty") {
        name = null; // ignore
    } else {
        runtime.log("OOPS " + e.name);
        throw null;
    }
}
function childrenToParents(childrenmap) {
    "use strict";
    var p, children, c, parents = {};
    for (p in childrenmap) {
        if (childrenmap.hasOwnProperty(p)) {
            children = childrenmap[p];
            for (c in children) {
                if (children.hasOwnProperty(c)) {
                    if (!parents.hasOwnProperty(c)) {
                        parents[c] = {};
                    }
                    parents[c][p] = 1;
                }
            }
        }
    }
    return parents;
}
function toCPP(elements) {
    "use strict";
    out("#include <KoXmlWriter.h>");

    // first get a mapping for all the parents
    var children = {}, parents = {}, i, j, ce, ec, name, names, c,
        elementMap = {}, sortedElementNames = [];
    for (i = 0; i < elements.length; i += 1) {
        ce = elements[i];
        if (ce.name !== "element") {
            runtime.log("Error in parsed data.");
            return;
        }
        names = [];
        getNames(ce.e[0], names);
        for (j = 0; j < names.length; j += 1) {
            name = getName(names[j]);
            while (elementMap.hasOwnProperty(name)) {
                name = name + "_";
            }
            names[j].cppname = name;
            ec = {e: [names[j], ce.e[1]]};
            elementMap[name] = ec;
            sortedElementNames.push(name);
        }
    }
    sortedElementNames.sort();

    for (i = 0; i < sortedElementNames.length; i += 1) {
        name = sortedElementNames[i];
        c = {};
        getChildren(elementMap[name].e[1], c);
        children[name] = c;
    }
    parents = childrenToParents(children);

    for (i = 0; i < sortedElementNames.length; i += 1) {
        name = sortedElementNames[i];
        out("class " + name + "Writer;");
    }
    for (i = 0; i < sortedElementNames.length; i += 1) {
        name = sortedElementNames[i];
        defineClass(elementMap[name], parents[name], children[name]);
    }
    for (i = 0; i < sortedElementNames.length; i += 1) {
        name = sortedElementNames[i];
        defineConstructors(elementMap[name], parents[name]);
    }
}

// load and parse the Relax NG
runtime.loadXML(relaxngurl, function (err, dom) {
    "use strict";
    var parser = new xmldom.RelaxNGParser();
    if (err) {
        runtime.log(err);
    } else {
        err = parser.parseRelaxNGDOM(dom);
        if (err) {
            runtime.log(err);
        } else {
            toCPP(parser.elements);
        }
    }
});
