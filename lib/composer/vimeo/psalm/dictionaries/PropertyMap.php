<?php
namespace Psalm\Internal;

/**
 * Stolen from https://github.com/etsy/phan/blob/master/src/Phan/Language/Internal/PropertyMap.php
 *
 * The MIT License (MIT)
 * Copyright (c) 2015 Rasmus Lerdorf
 * Copyright (c) 2015 Andrew Morrison
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

return [
    'arrayobject' => [
        'name' => 'string',
    ],
    'limititerator' => [
        'name' => 'string',
    ],
    'solrdocumentfield' => [
        'name' => 'string',
        'boost' => 'float',
        'values' => 'array',
    ],
    'domprocessinginstruction' => [
        'target' => 'string',
        'data' => 'string',
    ],
    'recursivearrayiterator' => [
        'name' => 'string',
    ],
    'eventbuffer' => [
        'length' => 'int',
        'contiguous-space' => 'int',
    ],
    'mongocursor' => [
        'slaveokay' => 'boolean',
        'timeout' => 'integer',
    ],
    'domxpath' => [
        'document' => 'DOMDocument',
    ],
    'domentity' => [
        'publicId' => 'string',
        'systemId' => 'string',
        'notationName' => 'string',
        'actualEncoding' => 'string',
        'encoding' => 'string',
        'version' => 'string',
    ],
    'splminheap' => [
        'name' => 'string',
    ],
    'mongodb-driver-exception-writeexception' => [
        'writeresult' => 'MongoDBDriverWriteResult',
    ],
    'ziparchive' => [
        'status' => 'int',
        'statusSys' => 'int',
        'numFiles' => 'int',
        'filename' => 'string',
        'comment' => 'string',
    ],
    'solrexception' => [
        'sourceline' => 'integer',
        'sourcefile' => 'string',
        'zif-name' => 'string',
    ],
    'arrayiterator' => [
        'name' => 'string',
    ],
    'mongoid' => [
        'id' => 'string',
    ],
    'dateinterval' => [
        'y' => 'integer',
        'm' => 'integer',
        'd' => 'integer',
        'h' => 'integer',
        'i' => 'integer',
        's' => 'integer',
        'f' => 'float', // only present from 7.1 onwards
        'invert' => 'integer',
        'days' => 'false|int',
    ],
    'tokyotyrantexception' => [
        'code' => 'int',
    ],
    'tidy' => [
        'errorbuffer' => 'string',
    ],
    'filteriterator' => [
        'name' => 'string',
    ],
    'parentiterator' => [
        'name' => 'string',
    ],
    'recursiveregexiterator' => [
        'name' => 'string',
    ],
    'error' => [
        'message' => 'string',
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
    ],
    'domexception' => [
        'code' => 'int',
    ],
    'domentityreference' => [
        'name' => 'string',
    ],
    'spldoublylinkedlist' => [
        'name' => 'string',
    ],
    'domdocumentfragment' => [
        'name' => 'string',
    ],
    'collator' => [
        'name' => 'string',
    ],
    'streamwrapper' => [
        'context' => 'resource',
    ],
    'pdostatement' => [
        'querystring' => 'string',
    ],
    'domnotation' => [
        'publicId' => 'string',
        'systemId' => 'string',
    ],
    'snmpexception' => [
        'code' => 'string',
    ],
    'directoryiterator' => [
        'name' => 'string',
    ],
    'splqueue' => [
        'name' => 'string',
    ],
    'locale' => [
        'name' => 'string',
    ],
    'directory' => [
        'path' => 'string',
        'handle' => 'resource',
    ],
    'splheap' => [
        'name' => 'string',
    ],
    'domnodelist' => [
        'length' => 'int',
    ],
    'mongodb' => [
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'splpriorityqueue' => [
        'name' => 'string',
    ],
    'mongoclient' => [
        'connected' => 'boolean',
        'status' => 'string',
    ],
    'domdocument' => [
        'actualEncoding' => 'string',
        'config' => 'null',
        'doctype' => 'DOMDocumentType',
        'documentElement' => 'DOMElement',
        'documentURI' => 'string',
        'encoding' => 'string',
        'formatOutput' => 'bool',
        'implementation' => 'DOMImplementation',
        'preserveWhiteSpace' => 'bool',
        'recover' => 'bool',
        'resolveExternals' => 'bool',
        'standalone' => 'bool',
        'strictErrorChecking' => 'bool',
        'substituteEntities' => 'bool',
        'validateOnParse' => 'bool',
        'version' => 'string',
        'xmlEncoding' => 'string',
        'xmlStandalone' => 'bool',
        'xmlVersion' => 'string',
        'ownerDocument' => 'null',
        'parentNode' => 'null',
    ],
    'libxmlerror' => [
        'level' => 'int',
        'code' => 'int',
        'column' => 'int',
        'message' => 'string',
        'file' => 'string',
        'line' => 'int',
    ],
    'domimplementation' => [
        'name' => 'string',
    ],
    'normalizer' => [
        'name' => 'string',
    ],
    'norewinditerator' => [
        'name' => 'string',
    ],
    'event' => [
        'pending' => 'bool',
    ],
    'domdocumenttype' => [
        'publicId' => 'string',
        'systemId' => 'string',
        'name' => 'string',
        'entities' => 'DOMNamedNodeMap',
        'notations' => 'DOMNamedNodeMap',
        'internalSubset' => 'string',
    ],
    'errorexception' => [
        'severity' => 'int',
    ],
    'recursivedirectoryiterator' => [
        'name' => 'string',
    ],
    'domcharacterdata' => [
        'data' => 'string',
        'length' => 'int',
    ],
    'mongocollection' => [
        'db' => 'MongoDB',
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'mongoint64' => [
        'value' => 'string',
    ],
    'mysqli' => [
        'affected_rows' => 'int',
        'client_info' => 'string',
        'client_version' => 'int',
        'connect_errno' => 'int',
        'connect_error' => 'string',
        'errno' => 'int',
        'error' => 'string',
        'error_list' => 'array',
        'field_count' => 'int',
        'host_info' => 'string',
        'info' => 'string',
        'insert_id' => 'int',
        'protocol_version' => 'string',
        'server_info' => 'string',
        'server_version' => 'int',
        'sqlstate' => 'string',
        'thread_id' => 'int',
        'warning_count' => 'int',
    ],
    'mysqli_driver' => [
        'client_info' => 'string',
        'client_version' => 'string',
        'driver_version' => 'string',
        'embedded' => 'string',
        'reconnect' => 'bool',
        'report_mode' => 'int'
    ],
    'mysqli_result' => [
        'current_field'  => 'int',
        'field_count' => 'int',
        'lengths' => 'array',
        'num_rows' => 'int',
        'type' => 'mixed',
    ],
    'mysqli_sql_exception' => [
        'sqlstate' => 'string'
    ],
    'mysqli_stmt' => [
        'affected_rows' => 'int',
        'errno' => 'int',
        'error' => 'string',
        'error_list' => 'array',
        'field_count' => 'int',
        'id' => 'mixed',
        'insert_id' => 'int',
        'num_rows' => 'int',
        'param_count' => 'int',
        'sqlstate' => 'string',
    ],
    'mysqli_warning' => [
        'errno' => 'int',
        'message' => 'string',
        'sqlstate' => 'string',
    ],
    'eventlistener' => [
        'fd' => 'int',
    ],
    'splmaxheap' => [
        'name' => 'string',
    ],
    'regexiterator' => [
        'name' => 'string',
    ],
    'domelement' => [
        'schemaTypeInfo' => 'bool',
        'tagName' => 'string',
        'attributes' => 'DOMNamedNodeMap<DOMAttr>',
    ],
    'tidynode' => [
        'value' => 'string',
        'name' => 'string',
        'type' => 'int',
        'line' => 'int',
        'column' => 'int',
        'proprietary' => 'bool',
        'id' => 'int',
        'attribute' => 'array',
        'child' => '?array',
    ],
    'recursivecachingiterator' => [
        'name' => 'string',
    ],
    'solrresponse' => [
        'http-status' => 'integer',
        'parser-mode' => 'integer',
        'success' => 'bool',
        'http-status-message' => 'string',
        'http-request-url' => 'string',
        'http-raw-request-headers' => 'string',
        'http-raw-request' => 'string',
        'http-raw-response-headers' => 'string',
        'http-raw-response' => 'string',
        'http-digested-response' => 'string',
    ],
    'domnamednodemap' => [
        'length' => 'int',
    ],
    'splstack' => [
        'name' => 'string',
    ],
    'numberformatter' => [
        'name' => 'string',
    ],
    'eventsslcontext' => [
        'local-cert' => 'string',
        'local-pk' => 'string',
    ],
    'pdoexception' => [
        'errorinfo' => 'array',
        'code' => 'string',
    ],
    'domnode' => [
        'nodeName' => 'string',
        'nodeValue' => 'string',
        'nodeType' => 'int',
        'parentNode' => 'DOMNode|null',
        'childNodes' => 'DOMNodeList',
        'firstChild' => 'DOMNode|null',
        'lastChild' => 'DOMNode|null',
        'previousSibling' => 'DOMNode|null',
        'nextSibling' => 'DOMNode|null',
        'attributes' => 'null',
        'ownerDocument' => 'DOMDocument|null',
        'namespaceURI' => 'string|null',
        'prefix' => 'string',
        'localName' => 'string',
        'baseURI' => 'string|null',
        'textContent' => 'string',
    ],
    'domattr' => [
        'name' => 'string',
        'ownerElement' => 'DOMElement',
        'schemaTypeInfo' => 'bool',
        'specified' => 'bool',
        'value' => 'string',
    ],
    'simplexmliterator' => [
        'name' => 'string',
    ],
    'snmp' => [
        'max-oids' => 'int',
        'valueretrieval' => 'int',
        'quick-print' => 'bool',
        'enum-print' => 'bool',
        'oid-output-format' => 'int',
        'oid-increasing-check' => 'bool',
        'exceptions-enabled' => 'int',
        'info' => 'array',
    ],
    'mongoint32' => [
        'value' => 'string',
    ],
    'xmlreader' => [
        'attributeCount' => 'int',
        'baseURI' => 'string',
        'depth' => 'int',
        'hasAttributes' => 'bool',
        'hasValue' => 'bool',
        'isDefault' => 'bool',
        'isEmptyElement' => 'bool',
        'localName' => 'string',
        'name' => 'string',
        'namespaceURI' => 'string',
        'nodeType' => 'int',
        'prefix' => 'string',
        'value' => 'string',
        'xmlLang' => 'string',
    ],
    'eventbufferevent' => [
        'fd' => 'integer',
        'priority' => 'integer',
        'input' => 'EventBuffer',
        'output' => 'EventBuffer',
    ],
    'domtext' => [
        'wholeText' => 'string',
    ],
    'exception' => [
        'message' => 'string',
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
    ],
    'reflectionclass' => [
        'name' => 'string',
    ],
    'reflectionmethod' => [
        'class' => 'string',
        'name' => 'string',
    ],
    'reflectionparameter' => [
        'name' => 'string',
    ],
    'phpparser\\node\\expr\\funccall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\new_' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\array_' => [
        'items' => 'array<int, PhpParser\Node\Expr\ArrayItem|null>',
    ],
    'phpparser\\node\\expr\\list_' => [
        'items' => 'array<int, PhpParser\Node\Expr\ArrayItem|null>',
    ],
    'phpparser\\node\\expr\\methodcall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\staticcall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\name' => [
        'parts' => 'non-empty-array<non-empty-string>',
    ],
    'phpparser\\node\\stmt\\namespace_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\if_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\elseif_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\else_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\for_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\foreach_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\trycatch' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\catch_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\finally_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\case_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\while_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\do_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\class_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\trait_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\interface_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\matcharm' => [
        'conds' => 'null|non-empty-list<PhpParser\Node\Expr>',
    ],
    'rdkafka\\message' => [
        'err' => 'int',
        'topic_name' => 'string',
        'partition' => 'int',
        'payload' => 'string',
        'key' => 'string|null',
        'offset' => 'int',
        'timestamp' => 'int',
        'headers' => 'array<string, string>|null',
    ],
];
