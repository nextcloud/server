define('ace/mode/scala', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/javascript', 'ace/tokenizer', 'ace/mode/scala_highlight_rules'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var JavaScriptMode = require("./javascript").Mode;
var Tokenizer = require("../tokenizer").Tokenizer;
var ScalaHighlightRules = require("./scala_highlight_rules").ScalaHighlightRules;

var Mode = function() {
    JavaScriptMode.call(this);
    
    this.$tokenizer = new Tokenizer(new ScalaHighlightRules().getRules());
};
oop.inherits(Mode, JavaScriptMode);

(function() {

    this.createWorker = function(session) {
        return null;
    };

}).call(Mode.prototype);

exports.Mode = Mode;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/javascript', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/text', 'ace/tokenizer', 'ace/mode/javascript_highlight_rules', 'ace/mode/matching_brace_outdent', 'ace/range', 'ace/worker/worker_client', 'ace/mode/behaviour/cstyle', 'ace/mode/folding/cstyle'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var TextMode = require("./text").Mode;
var Tokenizer = require("../tokenizer").Tokenizer;
var JavaScriptHighlightRules = require("./javascript_highlight_rules").JavaScriptHighlightRules;
var MatchingBraceOutdent = require("./matching_brace_outdent").MatchingBraceOutdent;
var Range = require("../range").Range;
var WorkerClient = require("../worker/worker_client").WorkerClient;
var CstyleBehaviour = require("./behaviour/cstyle").CstyleBehaviour;
var CStyleFoldMode = require("./folding/cstyle").FoldMode;

var Mode = function() {
    this.$tokenizer = new Tokenizer(new JavaScriptHighlightRules().getRules());
    this.$outdent = new MatchingBraceOutdent();
    this.$behaviour = new CstyleBehaviour();
    this.foldingRules = new CStyleFoldMode();
};
oop.inherits(Mode, TextMode);

(function() {


    this.toggleCommentLines = function(state, doc, startRow, endRow) {
        var outdent = true;
        var re = /^(\s*)\/\//;

        for (var i=startRow; i<= endRow; i++) {
            if (!re.test(doc.getLine(i))) {
                outdent = false;
                break;
            }
        }

        if (outdent) {
            var deleteRange = new Range(0, 0, 0, 0);
            for (var i=startRow; i<= endRow; i++)
            {
                var line = doc.getLine(i);
                var m = line.match(re);
                deleteRange.start.row = i;
                deleteRange.end.row = i;
                deleteRange.end.column = m[0].length;
                doc.replace(deleteRange, m[1]);
            }
        }
        else {
            doc.indentRows(startRow, endRow, "//");
        }
    };

    this.getNextLineIndent = function(state, line, tab) {
        var indent = this.$getIndent(line);

        var tokenizedLine = this.$tokenizer.getLineTokens(line, state);
        var tokens = tokenizedLine.tokens;
        var endState = tokenizedLine.state;

        if (tokens.length && tokens[tokens.length-1].type == "comment") {
            return indent;
        }
        
        if (state == "start" || state == "regex_allowed") {
            var match = line.match(/^.*(?:\bcase\b.*\:|[\{\(\[])\s*$/);
            if (match) {
                indent += tab;
            }
        } else if (state == "doc-start") {
            if (endState == "start" || state == "regex_allowed") {
                return "";
            }
            var match = line.match(/^\s*(\/?)\*/);
            if (match) {
                if (match[1]) {
                    indent += " ";
                }
                indent += "* ";
            }
        }

        return indent;
    };

    this.checkOutdent = function(state, line, input) {
        return this.$outdent.checkOutdent(line, input);
    };

    this.autoOutdent = function(state, doc, row) {
        this.$outdent.autoOutdent(doc, row);
    };
    
    this.createWorker = function(session) {
        var worker = new WorkerClient(["ace"], "worker-javascript.js", "ace/mode/javascript_worker", "JavaScriptWorker");
        worker.attachToDocument(session.getDocument());
            
        worker.on("jslint", function(results) {
            var errors = [];
            for (var i=0; i<results.data.length; i++) {
                var error = results.data[i];
                if (error)
                    errors.push({
                        row: error.line-1,
                        column: error.character-1,
                        text: error.reason,
                        type: "warning",
                        lint: error
                    });
            }
            session.setAnnotations(errors);
        });
        
        worker.on("narcissus", function(e) {
            session.setAnnotations([e.data]);
        });
        
        worker.on("terminate", function() {
            session.clearAnnotations();
        });
        
        return worker;
    };

}).call(Mode.prototype);

exports.Mode = Mode;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *      Mihai Sucan <mihai DOT sucan AT gmail DOT com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/javascript_highlight_rules', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/lang', 'ace/unicode', 'ace/mode/doc_comment_highlight_rules', 'ace/mode/text_highlight_rules'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var lang = require("../lib/lang");
var unicode = require("../unicode");
var DocCommentHighlightRules = require("./doc_comment_highlight_rules").DocCommentHighlightRules;
var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;

var JavaScriptHighlightRules = function() {

    // see: https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects
    var globals = lang.arrayToMap(
      // Constructors
        ("Array|Boolean|Date|Function|Iterator|Number|Object|RegExp|String|Proxy|" +
      // E4X
         "Namespace|QName|XML|XMLList|" +
         "ArrayBuffer|Float32Array|Float64Array|Int16Array|Int32Array|Int8Array|" +
         "Uint16Array|Uint32Array|Uint8Array|Uint8ClampedArray|" +
      // Errors
        "Error|EvalError|InternalError|RangeError|ReferenceError|StopIteration|" +
        "SyntaxError|TypeError|URIError|" +
      //  Non-constructor functions
        "decodeURI|decodeURIComponent|encodeURI|encodeURIComponent|eval|isFinite|" +
        "isNaN|parseFloat|parseInt|" +
      // Other
        "JSON|Math|" +
      // Pseudo
        "this|arguments|prototype|window|document"
      ).split("|")
    );

    var keywords = lang.arrayToMap(
        ("break|case|catch|continue|default|delete|do|else|finally|for|function|" +
        "if|in|instanceof|new|return|switch|throw|try|typeof|let|var|while|with|" +
        "const|yield|import|get|set").split("|")
    );

    // keywords which can be followed by regular expressions
    var kwBeforeRe = "case|do|else|finally|in|instanceof|return|throw|try|typeof|yield";

    var deprecated = lang.arrayToMap(
        ("__parent__|__count__|escape|unescape|with|__proto__").split("|")
    );

    var definitions = lang.arrayToMap(("const|let|var|function").split("|"));

    var buildinConstants = lang.arrayToMap(
        ("null|Infinity|NaN|undefined").split("|")
    );

    var futureReserved = lang.arrayToMap(
        ("class|enum|extends|super|export|implements|private|" +
        "public|interface|package|protected|static").split("|")
    );

    // TODO: Unicode escape sequences
    var identifierRe = "[" + unicode.packages.L + "\\$_][" 
        + unicode.packages.L
        + unicode.packages.Mn + unicode.packages.Mc
        + unicode.packages.Nd
        + unicode.packages.Pc + "\\$_]*\\b";
        
    // regexp must not have capturing parentheses. Use (?:) instead.
    // regexps are ordered -> the first match is used

    this.$rules = {
        "start" : [
            {
                token : "comment",
                regex : "\\/\\/.*$"
            },
            new DocCommentHighlightRules().getStartRule("doc-start"),
            {
                token : "comment", // multi line comment
                merge : true,
                regex : "\\/\\*",
                next : "comment"
            }, {
                token : "string", // single line
                regex : '["](?:(?:\\\\.)|(?:[^"\\\\]))*?["]'
            }, {
                token : "string", // multi line string start
                merge : true,
                regex : '["].*\\\\$',
                next : "qqstring"
            }, {
                token : "string", // single line
                regex : "['](?:(?:\\\\.)|(?:[^'\\\\]))*?[']"
            }, {
                token : "string", // multi line string start
                merge : true,
                regex : "['].*\\\\$",
                next : "qstring"
            }, {
                token : "constant.numeric", // hex
                regex : "0[xX][0-9a-fA-F]+\\b"
            }, {
                token : "constant.numeric", // float
                regex : "[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b"
            }, {
                token : ["keyword.definition", "text", "entity.name.function"],
                regex : "(function)(\\s+)(" + identifierRe + ")"
            }, {
                token : "constant.language.boolean",
                regex : "(?:true|false)\\b"
            }, {
                token : "keyword",
                regex : "(?:" + kwBeforeRe + ")\\b",
                next : "regex_allowed"
            }, {
                token : function(value) {
                    if (globals.hasOwnProperty(value))
                        return "variable.language";
                    else if (deprecated.hasOwnProperty(value))
                        return "invalid.deprecated";
                    else if (definitions.hasOwnProperty(value))
                        return "keyword.definition";
                    else if (keywords.hasOwnProperty(value))
                        return "keyword";
                    else if (buildinConstants.hasOwnProperty(value))
                        return "constant.language";
                    else if (futureReserved.hasOwnProperty(value))
                        return "invalid.illegal";
                    else if (value == "debugger")
                        return "invalid.deprecated";
                    else
                        return "identifier";
                },
                regex : identifierRe
            }, {
                token : "keyword.operator",
                regex : "!|\\$|%|&|\\*|\\-\\-|\\-|\\+\\+|\\+|~|===|==|=|!=|!==|<=|>=|<<=|>>=|>>>=|<>|<|>|!|&&|\\|\\||\\?\\:|\\*=|%=|\\+=|\\-=|&=|\\^=|\\b(?:in|instanceof|new|delete|typeof|void)",
                next  : "regex_allowed"
            }, {
                token : "punctuation.operator",
                regex : "\\?|\\:|\\,|\\;|\\.",
                next  : "regex_allowed"
            }, {
                token : "paren.lparen",
                regex : "[[({]",
                next  : "regex_allowed"
            }, {
                token : "paren.rparen",
                regex : "[\\])}]"
            }, {
                token : "keyword.operator",
                regex : "\\/=?",
                next  : "regex_allowed"
            }, {
                token: "comment",
                regex: "^#!.*$" 
            }, {
                token : "text",
                regex : "\\s+"
            }
        ],
        // regular expressions are only allowed after certain tokens. This
        // makes sure we don't mix up regexps with the divison operator
        "regex_allowed": [
            {
                token : "comment", // multi line comment
                merge : true,
                regex : "\\/\\*",
                next : "comment_regex_allowed"
            }, {
                token : "comment",
                regex : "\\/\\/.*$"
            }, {
                token: "string.regexp",
                regex: "\\/",
                next: "regex",
                merge: true
            }, {
                token : "text",
                regex : "\\s+"
            }, {
                // immediately return to the start mode without matching
                // anything
                token: "empty", 
                regex: "",
                next: "start"
            }
        ],
        "regex": [
            {
                token: "regexp.keyword.operator",
                regex: "\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)",
                next: "regex"
            }, {
				// flag
                token: "string.regexp", 
                regex: "/\\w*",
                next: "start",
                merge: true 
            }, {
                token: "string.regexp",
                regex: "[^\\\\/\\[]+",
                next: "regex",
                merge: true
            }, {
                token: "string.regexp.charachterclass",
                regex: "\\[",
                next: "regex_character_class",
                merge: true
            }, {
                token: "empty", 
                regex: "",
                next: "start" 
            }
        ],
        "regex_character_class": [
            {
                token: "regexp.keyword.operator",
                regex: "\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)",
                next: "regex_character_class"
            }, {
                token: "string.regexp.charachterclass",
                regex: "]",
                next: "regex",
                merge: true
            }, {
                token: "string.regexp.charachterclass",
                regex: "[^\\\\\\]]+",
                next: "regex_character_class",
                merge: true
            }, {
                token: "empty", 
                regex: "",
                next: "start" 
            }
        ],
        "comment_regex_allowed" : [
            {
                token : "comment", // closing comment
                regex : ".*?\\*\\/",
                merge : true,
                next : "regex_allowed"
            }, {
                token : "comment", // comment spanning whole line
                merge : true,
                regex : ".+"
            }
        ],
        "comment" : [
            {
                token : "comment", // closing comment
                regex : ".*?\\*\\/",
                merge : true,
                next : "start"
            }, {
                token : "comment", // comment spanning whole line
                merge : true,
                regex : ".+"
            }
        ],
        "qqstring" : [
            {
                token : "string",
                regex : '(?:(?:\\\\.)|(?:[^"\\\\]))*?"',
                next : "start"
            }, {
                token : "string",
                merge : true,
                regex : '.+'
            }
        ],
        "qstring" : [
            {
                token : "string",
                regex : "(?:(?:\\\\.)|(?:[^'\\\\]))*?'",
                next : "start"
            }, {
                token : "string",
                merge : true,
                regex : '.+'
            }
        ]
    };
    
    this.embedRules(DocCommentHighlightRules, "doc-",
        [ new DocCommentHighlightRules().getEndRule("start") ]);
};

oop.inherits(JavaScriptHighlightRules, TextHighlightRules);

exports.JavaScriptHighlightRules = JavaScriptHighlightRules;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/doc_comment_highlight_rules', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/text_highlight_rules'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;

var DocCommentHighlightRules = function() {

    this.$rules = {
        "start" : [ {
            token : "comment.doc.tag",
            regex : "@[\\w\\d_]+" // TODO: fix email addresses
        }, {
            token : "comment.doc",
            merge : true,
            regex : "\\s+"
        }, {
            token : "comment.doc",
            merge : true,
            regex : "TODO"
        }, {
            token : "comment.doc",
            merge : true,
            regex : "[^@\\*]+"
        }, {
            token : "comment.doc",
            merge : true,
            regex : "."
        }]
    };
};

oop.inherits(DocCommentHighlightRules, TextHighlightRules);

(function() {

    this.getStartRule = function(start) {
        return {
            token : "comment.doc", // doc comment
            merge : true,
            regex : "\\/\\*(?=\\*)",
            next  : start
        };
    };
    
    this.getEndRule = function (start) {
        return {
            token : "comment.doc", // closing comment
            merge : true,
            regex : "\\*\\/",
            next  : start
        };
    };

}).call(DocCommentHighlightRules.prototype);

exports.DocCommentHighlightRules = DocCommentHighlightRules;

});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/matching_brace_outdent', ['require', 'exports', 'module' , 'ace/range'], function(require, exports, module) {
"use strict";

var Range = require("../range").Range;

var MatchingBraceOutdent = function() {};

(function() {

    this.checkOutdent = function(line, input) {
        if (! /^\s+$/.test(line))
            return false;

        return /^\s*\}/.test(input);
    };

    this.autoOutdent = function(doc, row) {
        var line = doc.getLine(row);
        var match = line.match(/^(\s*\})/);

        if (!match) return 0;

        var column = match[1].length;
        var openBracePos = doc.findMatchingBracket({row: row, column: column});

        if (!openBracePos || openBracePos.row == row) return 0;

        var indent = this.$getIndent(doc.getLine(openBracePos.row));
        doc.replace(new Range(row, 0, row, column-1), indent);
    };

    this.$getIndent = function(line) {
        var match = line.match(/^(\s+)/);
        if (match) {
            return match[1];
        }

        return "";
    };

}).call(MatchingBraceOutdent.prototype);

exports.MatchingBraceOutdent = MatchingBraceOutdent;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/worker/worker_client', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/event_emitter'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var EventEmitter = require("../lib/event_emitter").EventEmitter;

var WorkerClient = function(topLevelNamespaces, packagedJs, mod, classname) {

    this.changeListener = this.changeListener.bind(this);

    if (module.packaged) {
        var base = this.$guessBasePath();
        this.$worker = new Worker(base + packagedJs);
    }
    else {
        var workerUrl = this.$normalizePath(require.nameToUrl("ace/worker/worker", null, "_"));
        this.$worker = new Worker(workerUrl);

        var tlns = {};
        for (var i=0; i<topLevelNamespaces.length; i++) {
            var ns = topLevelNamespaces[i];
            var path = this.$normalizePath(require.nameToUrl(ns, null, "_").replace(/.js$/, ""));

            tlns[ns] = path;
        }
    }

    this.$worker.postMessage({
        init : true,
        tlns: tlns,
        module: mod,
        classname: classname
    });

    this.callbackId = 1;
    this.callbacks = {};

    var _self = this;
    this.$worker.onerror = function(e) {
        window.console && console.log && console.log(e);
        throw e;
    };
    this.$worker.onmessage = function(e) {
        var msg = e.data;
        switch(msg.type) {
            case "log":
                window.console && console.log && console.log(msg.data);
                break;

            case "event":
                _self._emit(msg.name, {data: msg.data});
                break;

            case "call":
                var callback = _self.callbacks[msg.id];
                if (callback) {
                    callback(msg.data);
                    delete _self.callbacks[msg.id];
                }
                break;
        }
    };
};

(function(){

    oop.implement(this, EventEmitter);

    this.$normalizePath = function(path) {
        path = path.replace(/^[a-z]+:\/\/[^\/]+\//, ""); // Remove domain name and rebuild it
        path = location.protocol + "//" + location.host
            // paths starting with a slash are relative to the root (host)
            + (path.charAt(0) == "/" ? "" : location.pathname.replace(/\/[^\/]*$/, ""))
            + "/" + path.replace(/^[\/]+/, "");
        return path;
    };

    this.$guessBasePath = function() {
        if (require.aceBaseUrl)
            return require.aceBaseUrl;

        var scripts = document.getElementsByTagName("script");
        for (var i=0; i<scripts.length; i++) {
            var script = scripts[i];

            var base = script.getAttribute("data-ace-base");
            if (base)
                return base.replace(/\/*$/, "/");

            var src = script.src || script.getAttribute("src");
            if (!src) {
                continue;
            }
            var m = src.match(/^(?:(.*\/)ace\.js|(.*\/)ace(-uncompressed)?(-noconflict)?\.js)(?:\?|$)/);
            if (m)
                return m[1] || m[2];
        }
        return "";
    };

    this.terminate = function() {
        this._emit("terminate", {});
        this.$worker.terminate();
        this.$worker = null;
        this.$doc.removeEventListener("change", this.changeListener);
        this.$doc = null;
    };

    this.send = function(cmd, args) {
        this.$worker.postMessage({command: cmd, args: args});
    };

    this.call = function(cmd, args, callback) {
        if (callback) {
            var id = this.callbackId++;
            this.callbacks[id] = callback;
            args.push(id);
        }
        this.send(cmd, args);
    };

    this.emit = function(event, data) {
        try {
            // firefox refuses to clone objects which have function properties
            // TODO: cleanup event
            this.$worker.postMessage({event: event, data: {data: data.data}});
        }
        catch(ex) {}
    };

    this.attachToDocument = function(doc) {
        if(this.$doc)
            this.terminate();

        this.$doc = doc;
        this.call("setValue", [doc.getValue()]);
        doc.on("change", this.changeListener);
    };

    this.changeListener = function(e) {
        e.range = {
            start: e.data.range.start,
            end: e.data.range.end
        };
        this.emit("change", e);
    };

}).call(WorkerClient.prototype);

exports.WorkerClient = WorkerClient;

});
/* vim:ts=4:sts=4:sw=4:
 * ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Chris Spencer <chris.ag.spencer AT googlemail DOT com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/behaviour/cstyle', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/behaviour'], function(require, exports, module) {
"use strict";

var oop = require("../../lib/oop");
var Behaviour = require('../behaviour').Behaviour;

var CstyleBehaviour = function () {

    this.add("braces", "insertion", function (state, action, editor, session, text) {
        if (text == '{') {
            var selection = editor.getSelectionRange();
            var selected = session.doc.getTextRange(selection);
            if (selected !== "") {
                return {
                    text: '{' + selected + '}',
                    selection: false
                }
            } else {
                return {
                    text: '{}',
                    selection: [1, 1]
                }
            }
        } else if (text == '}') {
            var cursor = editor.getCursorPosition();
            var line = session.doc.getLine(cursor.row);
            var rightChar = line.substring(cursor.column, cursor.column + 1);
            if (rightChar == '}') {
                var matching = session.$findOpeningBracket('}', {column: cursor.column + 1, row: cursor.row});
                if (matching !== null) {
                    return {
                        text: '',
                        selection: [1, 1]
                    }
                }
            }
        } else if (text == "\n") {
            var cursor = editor.getCursorPosition();
            var line = session.doc.getLine(cursor.row);
            var rightChar = line.substring(cursor.column, cursor.column + 1);
            if (rightChar == '}') {
                var openBracePos = session.findMatchingBracket({row: cursor.row, column: cursor.column + 1});
                if (!openBracePos)
                     return null;

                var indent = this.getNextLineIndent(state, line.substring(0, line.length - 1), session.getTabString());
                var next_indent = this.$getIndent(session.doc.getLine(openBracePos.row));

                return {
                    text: '\n' + indent + '\n' + next_indent,
                    selection: [1, indent.length, 1, indent.length]
                }
            }
        }
    });

    this.add("braces", "deletion", function (state, action, editor, session, range) {
        var selected = session.doc.getTextRange(range);
        if (!range.isMultiLine() && selected == '{') {
            var line = session.doc.getLine(range.start.row);
            var rightChar = line.substring(range.end.column, range.end.column + 1);
            if (rightChar == '}') {
                range.end.column++;
                return range;
            }
        }
    });

    this.add("parens", "insertion", function (state, action, editor, session, text) {
        if (text == '(') {
            var selection = editor.getSelectionRange();
            var selected = session.doc.getTextRange(selection);
            if (selected !== "") {
                return {
                    text: '(' + selected + ')',
                    selection: false
                }
            } else {
                return {
                    text: '()',
                    selection: [1, 1]
                }
            }
        } else if (text == ')') {
            var cursor = editor.getCursorPosition();
            var line = session.doc.getLine(cursor.row);
            var rightChar = line.substring(cursor.column, cursor.column + 1);
            if (rightChar == ')') {
                var matching = session.$findOpeningBracket(')', {column: cursor.column + 1, row: cursor.row});
                if (matching !== null) {
                    return {
                        text: '',
                        selection: [1, 1]
                    }
                }
            }
        }
    });

    this.add("parens", "deletion", function (state, action, editor, session, range) {
        var selected = session.doc.getTextRange(range);
        if (!range.isMultiLine() && selected == '(') {
            var line = session.doc.getLine(range.start.row);
            var rightChar = line.substring(range.start.column + 1, range.start.column + 2);
            if (rightChar == ')') {
                range.end.column++;
                return range;
            }
        }
    });

    this.add("string_dquotes", "insertion", function (state, action, editor, session, text) {
        if (text == '"') {
            var selection = editor.getSelectionRange();
            var selected = session.doc.getTextRange(selection);
            if (selected !== "") {
                return {
                    text: '"' + selected + '"',
                    selection: false
                }
            } else {
                var cursor = editor.getCursorPosition();
                var line = session.doc.getLine(cursor.row);
                var leftChar = line.substring(cursor.column-1, cursor.column);

                // We're escaped.
                if (leftChar == '\\') {
                    return null;
                }

                // Find what token we're inside.
                var tokens = session.getTokens(selection.start.row, selection.start.row)[0].tokens;
                var col = 0, token;
                var quotepos = -1; // Track whether we're inside an open quote.

                for (var x = 0; x < tokens.length; x++) {
                    token = tokens[x];
                    if (token.type == "string") {
                      quotepos = -1;
                    } else if (quotepos < 0) {
                      quotepos = token.value.indexOf('"');
                    }
                    if ((token.value.length + col) > selection.start.column) {
                        break;
                    }
                    col += tokens[x].value.length;
                }

                // Try and be smart about when we auto insert.
                if (!token || (quotepos < 0 && token.type !== "comment" && (token.type !== "string" || ((selection.start.column !== token.value.length+col-1) && token.value.lastIndexOf('"') === token.value.length-1)))) {
                    return {
                        text: '""',
                        selection: [1,1]
                    }
                } else if (token && token.type === "string") {
                    // Ignore input and move right one if we're typing over the closing quote.
                    var rightChar = line.substring(cursor.column, cursor.column + 1);
                    if (rightChar == '"') {
                        return {
                            text: '',
                            selection: [1, 1]
                        }
                    }
                }
            }
        }
    });

    this.add("string_dquotes", "deletion", function (state, action, editor, session, range) {
        var selected = session.doc.getTextRange(range);
        if (!range.isMultiLine() && selected == '"') {
            var line = session.doc.getLine(range.start.row);
            var rightChar = line.substring(range.start.column + 1, range.start.column + 2);
            if (rightChar == '"') {
                range.end.column++;
                return range;
            }
        }
    });

}
oop.inherits(CstyleBehaviour, Behaviour);

exports.CstyleBehaviour = CstyleBehaviour;
});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/folding/cstyle', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/range', 'ace/mode/folding/fold_mode'], function(require, exports, module) {
"use strict";

var oop = require("../../lib/oop");
var Range = require("../../range").Range;
var BaseFoldMode = require("./fold_mode").FoldMode;

var FoldMode = exports.FoldMode = function() {};
oop.inherits(FoldMode, BaseFoldMode);

(function() {

    this.foldingStartMarker = /(\{|\[)[^\}\]]*$|^\s*(\/\*)/;
    this.foldingStopMarker = /^[^\[\{]*(\}|\])|^[\s\*]*(\*\/)/;
    
    this.getFoldWidgetRange = function(session, foldStyle, row) {
        var line = session.getLine(row);
        var match = line.match(this.foldingStartMarker);
        if (match) {
            var i = match.index;

            if (match[1])
                return this.openingBracketBlock(session, match[1], row, i);

            var range = session.getCommentFoldRange(row, i + match[0].length);
            range.end.column -= 2;
            return range;
        }

        if (foldStyle !== "markbeginend")
            return;
            
        var match = line.match(this.foldingStopMarker);
        if (match) {
            var i = match.index + match[0].length;

            if (match[2]) {
                var range = session.getCommentFoldRange(row, i);
                range.end.column -= 2;
                return range;
            }

            var end = {row: row, column: i};
            var start = session.$findOpeningBracket(match[1], end);
            
            if (!start)
                return;

            start.column++;
            end.column--;

            return  Range.fromPoints(start, end);
        }
    };
    
}).call(FoldMode.prototype);

});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/folding/fold_mode', ['require', 'exports', 'module' , 'ace/range'], function(require, exports, module) {
"use strict";

var Range = require("../../range").Range;

var FoldMode = exports.FoldMode = function() {};

(function() {

    this.foldingStartMarker = null;
    this.foldingStopMarker = null;

    // must return "" if there's no fold, to enable caching
    this.getFoldWidget = function(session, foldStyle, row) {
        var line = session.getLine(row);
        if (this.foldingStartMarker.test(line))
            return "start";
        if (foldStyle == "markbeginend"
                && this.foldingStopMarker
                && this.foldingStopMarker.test(line))
            return "end";
        return "";
    };
    
    this.getFoldWidgetRange = function(session, foldStyle, row) {
        return null;
    };

    this.indentationBlock = function(session, row, column) {
        var re = /^\s*/;
        var startRow = row;
        var endRow = row;
        var line = session.getLine(row);
        var startColumn = column || line.length;
        var startLevel = line.match(re)[0].length;
        var maxRow = session.getLength()
        
        while (++row < maxRow) {
            line = session.getLine(row);
            var level = line.match(re)[0].length;

            if (level == line.length)
                continue;

            if (level <= startLevel)
                break;

            endRow = row;
        }

        if (endRow > startRow) {
            var endColumn = session.getLine(endRow).length;
            return new Range(startRow, startColumn, endRow, endColumn);
        }
    };

    this.openingBracketBlock = function(session, bracket, row, column) {
        var start = {row: row, column: column + 1};
        var end = session.$findClosingBracket(bracket, start);
        if (!end)
            return;

        var fw = session.foldWidgets[end.row];
        if (fw == null)
            fw = this.getFoldWidget(session, end.row);

        if (fw == "start") {
            end.row --;
            end.column = session.getLine(end.row).length;
        }
        return Range.fromPoints(start, end);
    };

}).call(FoldMode.prototype);

});
define('ace/mode/scala_highlight_rules', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/lang', 'ace/mode/doc_comment_highlight_rules', 'ace/mode/text_highlight_rules'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var lang = require("../lib/lang");
var DocCommentHighlightRules = require("./doc_comment_highlight_rules").DocCommentHighlightRules;
var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;

var ScalaHighlightRules = function() {

    // taken from http://download.oracle.com/javase/tutorial/java/nutsandbolts/_keywords.html
    var keywords = lang.arrayToMap(
        (
            "case|default|do|else|for|if|match|while|throw|return|try|catch|finally|yield|" +
            "abstract|class|def|extends|final|forSome|implicit|implicits|import|lazy|new|object|" +
            "override|package|private|protected|sealed|super|this|trait|type|val|var|with"
        ).split("|")
    );

    var buildinConstants = lang.arrayToMap(
        ("true|false").split("|")
    );
    
    var langClasses = lang.arrayToMap(
        ("AbstractMethodError|AssertionError|ClassCircularityError|"+
        "ClassFormatError|Deprecated|EnumConstantNotPresentException|"+
        "ExceptionInInitializerError|IllegalAccessError|"+
        "IllegalThreadStateException|InstantiationError|InternalError|"+

        "NegativeArraySizeException|NoSuchFieldError|Override|Process|"+
        "ProcessBuilder|SecurityManager|StringIndexOutOfBoundsException|"+
        "SuppressWarnings|TypeNotPresentException|UnknownError|"+
        "UnsatisfiedLinkError|UnsupportedClassVersionError|VerifyError|"+
        "InstantiationException|IndexOutOfBoundsException|"+
        "ArrayIndexOutOfBoundsException|CloneNotSupportedException|"+
        "NoSuchFieldException|IllegalArgumentException|NumberFormatException|"+
        "SecurityException|Void|InheritableThreadLocal|IllegalStateException|"+
        "InterruptedException|NoSuchMethodException|IllegalAccessException|"+
        "UnsupportedOperationException|Enum|StrictMath|Package|Compiler|"+
        "Readable|Runtime|StringBuilder|Math|IncompatibleClassChangeError|"+
        "NoSuchMethodError|ThreadLocal|RuntimePermission|ArithmeticException|"+
        "NullPointerException|Long|Integer|Short|Byte|Double|Number|Float|"+
        "Character|Boolean|StackTraceElement|Appendable|StringBuffer|"+
        "Iterable|ThreadGroup|Runnable|Thread|IllegalMonitorStateException|"+
        "StackOverflowError|OutOfMemoryError|VirtualMachineError|"+
        "ArrayStoreException|ClassCastException|LinkageError|"+
        "NoClassDefFoundError|ClassNotFoundException|RuntimeException|"+
        "Exception|ThreadDeath|Error|Throwable|System|ClassLoader|"+
        "Cloneable|Class|CharSequence|Comparable|String|Object|" +
        "Unit|Any|AnyVal|AnyRef|Null|ScalaObject|Singleton|Seq|Iterable|List|" +
        "Option|Array|Char|Byte|Short|Int|Long|Nothing"
        
        ).split("|")
    );
    
    var importClasses = lang.arrayToMap(
        ("").split("|")
    );
    // regexp must not have capturing parentheses. Use (?:) instead.
    // regexps are ordered -> the first match is used

    this.$rules = {
        "start" : [
            {
                token : "comment",
                regex : "\\/\\/.*$"
            },
            new DocCommentHighlightRules().getStartRule("doc-start"),
            {
                token : "comment", // multi line comment
                merge : true,
                regex : "\\/\\*",
                next : "comment"
            }, {
                token : "string.regexp",
                regex : "[/](?:(?:\\[(?:\\\\]|[^\\]])+\\])|(?:\\\\/|[^\\]/]))*[/]\\w*\\s*(?=[).,;]|$)"
            }, {
                token : "string", // single line
                regex : '["](?:(?:\\\\.)|(?:[^"\\\\]))*?["]'
            }, {
                token : "string", // single line
                regex : "['](?:(?:\\\\.)|(?:[^'\\\\]))*?[']"
            }, {
                token : "constant.numeric", // hex
                regex : "0[xX][0-9a-fA-F]+\\b"
            }, {
                token : "constant.numeric", // float
                regex : "[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b"
            }, {
                token : "constant.language.boolean",
                regex : "(?:true|false)\\b"
            }, {
                token : function(value) {
                    if (value == "this")
                        return "variable.language";
                    else if (keywords.hasOwnProperty(value))
                        return "keyword";
                    else if (langClasses.hasOwnProperty(value))
                        return "support.function";
                    else if (importClasses.hasOwnProperty(value))
                        return "support.function";
                    else if (buildinConstants.hasOwnProperty(value))
                        return "constant.language";
                    else
                        return "identifier";
                },
                // TODO: Unicode escape sequences
                // TODO: Unicode identifiers
                regex : "[a-zA-Z_$][a-zA-Z0-9_$]*\\b"
            }, {
                token : "keyword.operator",
                regex : "!|\\$|%|&|\\*|\\-\\-|\\-|\\+\\+|\\+|~|===|==|=|!=|!==|<=|>=|<<=|>>=|>>>=|<>|<|>|!|&&|\\|\\||\\?\\:|\\*=|%=|\\+=|\\-=|&=|\\^=|\\b(?:in|instanceof|new|delete|typeof|void)"
            }, {
                token : "paren.lparen",
                regex : "[[({]"
            }, {
                token : "paren.rparen",
                regex : "[\\])}]"
            }, {
                token : "text",
                regex : "\\s+"
            }
        ],
        "comment" : [
            {
                token : "comment", // closing comment
                regex : ".*?\\*\\/",
                next : "start"
            }, {
                token : "comment", // comment spanning whole line
                merge : true,
                regex : ".+"
            }
        ]
    };
    
    this.embedRules(DocCommentHighlightRules, "doc-",
        [ new DocCommentHighlightRules().getEndRule("start") ]);
};

oop.inherits(ScalaHighlightRules, TextHighlightRules);

exports.ScalaHighlightRules = ScalaHighlightRules;
});
;
            (function() {
                window.require(["ace/ace"], function(a) {
                    if (!window.ace)
                        window.ace = {};
                    for (var key in a) if (a.hasOwnProperty(key))
                        ace[key] = a[key];
                });
            })();
        