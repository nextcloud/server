<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

return array (
    'apiVersion' => '2011-02-01',
    'endpointPrefix' => 'cloudsearch',
    'serviceFullName' => 'Amazon CloudSearch',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'CloudSearch',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudsearch.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudsearch.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudsearch.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudsearch.eu-west-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudsearch.ap-southeast-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CreateDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateDomainResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDomain',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'DefineIndexField' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DefineIndexFieldResponse',
            'responseType' => 'model',
            'summary' => 'Configures an IndexField for the search domain. Used to create new fields and modify existing ones. If the field exists, the new configuration replaces the old one. You can configure a maximum of 200 index fields.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DefineIndexField',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'IndexField' => array(
                    'required' => true,
                    'description' => 'Defines a field in the index, including its name, type, and the source of its data. The IndexFieldType indicates which of the options will be present. It is invalid to specify options for a type other than the IndexFieldType.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'IndexFieldName' => array(
                            'required' => true,
                            'description' => 'The name of a field in the search index. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                        'IndexFieldType' => array(
                            'required' => true,
                            'description' => 'The type of field. Based on this type, exactly one of the UIntOptions, LiteralOptions or TextOptions must be present.',
                            'type' => 'string',
                            'enum' => array(
                                'uint',
                                'literal',
                                'text',
                            ),
                        ),
                        'UIntOptions' => array(
                            'description' => 'Options for an unsigned integer field. Present if IndexFieldType specifies the field is of type unsigned integer.',
                            'type' => 'object',
                            'properties' => array(
                                'DefaultValue' => array(
                                    'description' => 'The default value for an unsigned integer field. Optional.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'LiteralOptions' => array(
                            'description' => 'Options for literal field. Present if IndexFieldType specifies the field is of type literal.',
                            'type' => 'object',
                            'properties' => array(
                                'DefaultValue' => array(
                                    'description' => 'The default value for a literal field. Optional.',
                                    'type' => 'string',
                                    'maxLength' => 1024,
                                ),
                                'SearchEnabled' => array(
                                    'description' => 'Specifies whether search is enabled for this field. Default: False.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'FacetEnabled' => array(
                                    'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'ResultEnabled' => array(
                                    'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                            ),
                        ),
                        'TextOptions' => array(
                            'description' => 'Options for text field. Present if IndexFieldType specifies the field is of type text.',
                            'type' => 'object',
                            'properties' => array(
                                'DefaultValue' => array(
                                    'description' => 'The default value for a text field. Optional.',
                                    'type' => 'string',
                                    'maxLength' => 1024,
                                ),
                                'FacetEnabled' => array(
                                    'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'ResultEnabled' => array(
                                    'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'TextProcessor' => array(
                                    'description' => 'The text processor to apply to this field. Optional. Possible values:',
                                    'type' => 'string',
                                    'minLength' => 1,
                                    'maxLength' => 64,
                                ),
                            ),
                        ),
                        'SourceAttributes' => array(
                            'description' => 'An optional list of source attributes that provide data for this index field. If not specified, the data is pulled from a source attribute with the same name as this IndexField. When one or more source attributes are specified, an optional data transformation can be applied to the source data when populating the index field. You can configure a maximum of 20 sources for an IndexField.',
                            'type' => 'array',
                            'sentAs' => 'SourceAttributes.member',
                            'items' => array(
                                'name' => 'SourceAttribute',
                                'description' => 'Identifies the source data for an index field. An optional data transformation can be applied to the source data when populating the index field. By default, the value of the source attribute is copied to the index field.',
                                'type' => 'object',
                                'properties' => array(
                                    'SourceDataFunction' => array(
                                        'required' => true,
                                        'description' => 'Identifies the transformation to apply when copying data from a source attribute.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'Copy',
                                            'TrimTitle',
                                            'Map',
                                        ),
                                    ),
                                    'SourceDataCopy' => array(
                                        'description' => 'Copies data from a source document attribute to an IndexField.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'SourceName' => array(
                                                'required' => true,
                                                'description' => 'The name of the document source field to add to this IndexField.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                            ),
                                            'DefaultValue' => array(
                                                'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                'type' => 'string',
                                                'maxLength' => 1024,
                                            ),
                                        ),
                                    ),
                                    'SourceDataTrimTitle' => array(
                                        'description' => 'Trims common title words from a source document attribute when populating an IndexField. This can be used to create an IndexField you can use for sorting.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'SourceName' => array(
                                                'required' => true,
                                                'description' => 'The name of the document source field to add to this IndexField.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                            ),
                                            'DefaultValue' => array(
                                                'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                'type' => 'string',
                                                'maxLength' => 1024,
                                            ),
                                            'Separator' => array(
                                                'description' => 'The separator that follows the text to trim.',
                                                'type' => 'string',
                                            ),
                                            'Language' => array(
                                                'description' => 'An IETF RFC 4646 language code. Only the primary language is considered. English (en) is currently the only supported language.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'SourceDataMap' => array(
                                        'description' => 'Maps source document attribute values to new values when populating the IndexField.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'SourceName' => array(
                                                'required' => true,
                                                'description' => 'The name of the document source field to add to this IndexField.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                            ),
                                            'DefaultValue' => array(
                                                'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                'type' => 'string',
                                                'maxLength' => 1024,
                                            ),
                                            'Cases' => array(
                                                'description' => 'A map that translates source field values to custom values.',
                                                'type' => 'object',
                                                'additionalProperties' => array(
                                                    'description' => 'The value of a field or source document attribute.',
                                                    'type' => 'string',
                                                    'maxLength' => 1024,
                                                    'data' => array(
                                                        'shape_name' => 'FieldValue',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DefineRankExpression' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DefineRankExpressionResponse',
            'responseType' => 'model',
            'summary' => 'Configures a RankExpression for the search domain. Used to create new rank expressions and modify existing ones. If the expression exists, the new configuration replaces the old one. You can configure a maximum of 50 rank expressions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DefineRankExpression',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'RankExpression' => array(
                    'required' => true,
                    'description' => 'A named expression that can be evaluated at search time and used for ranking or thresholding in a search query.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'RankName' => array(
                            'required' => true,
                            'description' => 'The name of a rank expression. Rank expression names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                        'RankExpression' => array(
                            'required' => true,
                            'description' => 'The expression to evaluate for ranking or thresholding while processing a search request. The RankExpression syntax is based on JavaScript expressions and supports:',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 10240,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DeleteDomainResponse',
            'responseType' => 'model',
            'summary' => 'Permanently deletes a search domain and all of its data.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDomain',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
            ),
        ),
        'DeleteIndexField' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DeleteIndexFieldResponse',
            'responseType' => 'model',
            'summary' => 'Removes an IndexField from the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteIndexField',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'IndexFieldName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of an index field. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteRankExpression' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DeleteRankExpressionResponse',
            'responseType' => 'model',
            'summary' => 'Removes a RankExpression from the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteRankExpression',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'RankName' => array(
                    'required' => true,
                    'description' => 'The name of the RankExpression to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeDefaultSearchField' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeDefaultSearchFieldResponse',
            'responseType' => 'model',
            'summary' => 'Gets the default search field configured for the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDefaultSearchField',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeDomains' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeDomainsResponse',
            'responseType' => 'model',
            'summary' => 'Gets information about the search domains owned by this account. Can be limited to specific domains. Shows all domains by default.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDomains',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainNames' => array(
                    'description' => 'Limits the DescribeDomains response to the specified search domains.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DomainNames.member',
                    'items' => array(
                        'name' => 'DomainName',
                        'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                        'type' => 'string',
                        'minLength' => 3,
                        'maxLength' => 28,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
            ),
        ),
        'DescribeIndexFields' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeIndexFieldsResponse',
            'responseType' => 'model',
            'summary' => 'Gets information about the index fields configured for the search domain. Can be limited to specific fields by name. Shows all fields by default.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeIndexFields',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'FieldNames' => array(
                    'description' => 'Limits the DescribeIndexFields response to the specified fields.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'FieldNames.member',
                    'items' => array(
                        'name' => 'FieldName',
                        'description' => 'A string that represents the name of an index field. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 64,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeRankExpressions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeRankExpressionsResponse',
            'responseType' => 'model',
            'summary' => 'Gets the rank expressions configured for the search domain. Can be limited to specific rank expressions by name. Shows all rank expressions by default.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeRankExpressions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'RankNames' => array(
                    'description' => 'Limits the DescribeRankExpressions response to the specified fields.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'RankNames.member',
                    'items' => array(
                        'name' => 'FieldName',
                        'description' => 'A string that represents the name of an index field. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 64,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeServiceAccessPolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeServiceAccessPoliciesResponse',
            'responseType' => 'model',
            'summary' => 'Gets information about the resource-based policies that control access to the domain\'s document and search services.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeServiceAccessPolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeStemmingOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStemmingOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Gets the stemming dictionary configured for the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStemmingOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeStopwordOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStopwordOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Gets the stopwords configured for the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStopwordOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeSynonymOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSynonymOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Gets the synonym dictionary configured for the search domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSynonymOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'IndexDocuments' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'IndexDocumentsResponse',
            'responseType' => 'model',
            'summary' => 'Tells the search domain to start indexing its documents using the latest text processing options and IndexFields. This operation must be invoked to make options whose OptionStatus has OptionState of RequiresIndexDocuments visible in search results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'IndexDocuments',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateDefaultSearchField' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateDefaultSearchFieldResponse',
            'responseType' => 'model',
            'summary' => 'Configures the default search field for the search domain. The default search field is used when a search request does not specify which fields to search. By default, it is configured to include the contents of all of the domain\'s text fields.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateDefaultSearchField',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'DefaultSearchField' => array(
                    'required' => true,
                    'description' => 'The IndexField to use for search requests issued with the q parameter. The default is an empty string, which automatically searches all text fields.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateServiceAccessPolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateServiceAccessPoliciesResponse',
            'responseType' => 'model',
            'summary' => 'Configures the policies that control access to the domain\'s document and search services. The maximum size of an access policy document is 100 KB.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateServiceAccessPolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'AccessPolicies' => array(
                    'required' => true,
                    'description' => 'An IAM access policy as described in The Access Policy Language in Using AWS Identity and Access Management. The maximum size of an access policy document is 100 KB.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
            ),
        ),
        'UpdateStemmingOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateStemmingOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Configures a stemming dictionary for the search domain. The stemming dictionary is used during indexing and when processing search requests. The maximum size of the stemming dictionary is 500 KB.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateStemmingOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'Stems' => array(
                    'required' => true,
                    'description' => 'Maps terms to their stems, serialized as a JSON document. The document has a single object with one property "stems" whose value is an object mapping terms to their stems. The maximum size of a stemming document is 500 KB. Example: { "stems": {"people": "person", "walking": "walk"} }',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateStopwordOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateStopwordOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Configures stopwords for the search domain. Stopwords are used during indexing and when processing search requests. The maximum size of the stopwords dictionary is 10 KB.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateStopwordOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'Stopwords' => array(
                    'required' => true,
                    'description' => 'Lists stopwords serialized as a JSON document. The document has a single object with one property "stopwords" whose value is an array of strings. The maximum size of a stopwords document is 10 KB. Example: { "stopwords": ["a", "an", "the", "of"] }',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateSynonymOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateSynonymOptionsResponse',
            'responseType' => 'model',
            'summary' => 'Configures a synonym dictionary for the search domain. The synonym dictionary is used during indexing to configure mappings for terms that occur in text fields. The maximum size of the synonym dictionary is 100 KB.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateSynonymOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-02-01',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 28,
                ),
                'Synonyms' => array(
                    'required' => true,
                    'description' => 'Maps terms to their synonyms, serialized as a JSON document. The document has a single object with one property "synonyms" whose value is an object mapping terms to their synonyms. Each synonym is a simple string or an array of strings. The maximum size of a stopwords document is 100 KB. Example: { "synonyms": {"cat": ["feline", "kitten"], "puppy": "dog"} }',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred while processing the request.',
                    'class' => 'BaseException',
                ),
                array(
                    'reason' => 'An internal error occurred while processing the request. If this problem persists, report an issue from the Service Health Dashboard.',
                    'class' => 'InternalException',
                ),
                array(
                    'reason' => 'The request was rejected because it specified an invalid type definition.',
                    'class' => 'InvalidTypeException',
                ),
                array(
                    'reason' => 'The request was rejected because a resource limit has already been met.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to reference a resource that does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
    ),
    'models' => array(
        'CreateDomainResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DomainStatus' => array(
                    'description' => 'The current status of the search domain.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DomainId' => array(
                            'description' => 'An internally generated unique identifier for a domain.',
                            'type' => 'string',
                        ),
                        'DomainName' => array(
                            'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                            'type' => 'string',
                        ),
                        'Created' => array(
                            'description' => 'True if the search domain is created. It can take several minutes to initialize a domain when CreateDomain is called. Newly created search domains are returned from DescribeDomains with a false value for Created until domain creation is complete.',
                            'type' => 'boolean',
                        ),
                        'Deleted' => array(
                            'description' => 'True if the search domain has been deleted. The system must clean up resources dedicated to the search domain when DeleteDomain is called. Newly deleted search domains are returned from DescribeDomains with a true value for IsDeleted for several minutes until resource cleanup is complete.',
                            'type' => 'boolean',
                        ),
                        'NumSearchableDocs' => array(
                            'description' => 'The number of documents that have been submitted to the domain and indexed.',
                            'type' => 'numeric',
                        ),
                        'DocService' => array(
                            'description' => 'The service endpoint for updating documents in a search domain.',
                            'type' => 'object',
                            'properties' => array(
                                'Arn' => array(
                                    'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                    'type' => 'string',
                                ),
                                'Endpoint' => array(
                                    'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'SearchService' => array(
                            'description' => 'The service endpoint for requesting search results from a search domain.',
                            'type' => 'object',
                            'properties' => array(
                                'Arn' => array(
                                    'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                    'type' => 'string',
                                ),
                                'Endpoint' => array(
                                    'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RequiresIndexDocuments' => array(
                            'description' => 'True if IndexDocuments needs to be called to activate the current domain configuration.',
                            'type' => 'boolean',
                        ),
                        'Processing' => array(
                            'description' => 'True if processing is being done to activate the current domain configuration.',
                            'type' => 'boolean',
                        ),
                        'SearchInstanceType' => array(
                            'description' => 'The instance type (such as search.m1.small) that is being used to process search requests.',
                            'type' => 'string',
                        ),
                        'SearchPartitionCount' => array(
                            'description' => 'The number of partitions across which the search index is spread.',
                            'type' => 'numeric',
                        ),
                        'SearchInstanceCount' => array(
                            'description' => 'The number of search instances that are available to process search requests.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'DefineIndexFieldResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IndexField' => array(
                    'description' => 'The value of an IndexField and its current status.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Defines a field in the index, including its name, type, and the source of its data. The IndexFieldType indicates which of the options will be present. It is invalid to specify options for a type other than the IndexFieldType.',
                            'type' => 'object',
                            'properties' => array(
                                'IndexFieldName' => array(
                                    'description' => 'The name of a field in the search index. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                    'type' => 'string',
                                ),
                                'IndexFieldType' => array(
                                    'description' => 'The type of field. Based on this type, exactly one of the UIntOptions, LiteralOptions or TextOptions must be present.',
                                    'type' => 'string',
                                ),
                                'UIntOptions' => array(
                                    'description' => 'Options for an unsigned integer field. Present if IndexFieldType specifies the field is of type unsigned integer.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for an unsigned integer field. Optional.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                                'LiteralOptions' => array(
                                    'description' => 'Options for literal field. Present if IndexFieldType specifies the field is of type literal.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for a literal field. Optional.',
                                            'type' => 'string',
                                        ),
                                        'SearchEnabled' => array(
                                            'description' => 'Specifies whether search is enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'FacetEnabled' => array(
                                            'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'ResultEnabled' => array(
                                            'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                                'TextOptions' => array(
                                    'description' => 'Options for text field. Present if IndexFieldType specifies the field is of type text.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for a text field. Optional.',
                                            'type' => 'string',
                                        ),
                                        'FacetEnabled' => array(
                                            'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'ResultEnabled' => array(
                                            'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'TextProcessor' => array(
                                            'description' => 'The text processor to apply to this field. Optional. Possible values:',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'SourceAttributes' => array(
                                    'description' => 'An optional list of source attributes that provide data for this index field. If not specified, the data is pulled from a source attribute with the same name as this IndexField. When one or more source attributes are specified, an optional data transformation can be applied to the source data when populating the index field. You can configure a maximum of 20 sources for an IndexField.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'SourceAttribute',
                                        'description' => 'Identifies the source data for an index field. An optional data transformation can be applied to the source data when populating the index field. By default, the value of the source attribute is copied to the index field.',
                                        'type' => 'object',
                                        'sentAs' => 'member',
                                        'properties' => array(
                                            'SourceDataFunction' => array(
                                                'description' => 'Identifies the transformation to apply when copying data from a source attribute.',
                                                'type' => 'string',
                                            ),
                                            'SourceDataCopy' => array(
                                                'description' => 'Copies data from a source document attribute to an IndexField.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'SourceDataTrimTitle' => array(
                                                'description' => 'Trims common title words from a source document attribute when populating an IndexField. This can be used to create an IndexField you can use for sorting.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                    'Separator' => array(
                                                        'description' => 'The separator that follows the text to trim.',
                                                        'type' => 'string',
                                                    ),
                                                    'Language' => array(
                                                        'description' => 'An IETF RFC 4646 language code. Only the primary language is considered. English (en) is currently the only supported language.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'SourceDataMap' => array(
                                                'description' => 'Maps source document attribute values to new values when populating the IndexField.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                    'Cases' => array(
                                                        'description' => 'A map that translates source field values to custom values.',
                                                        'type' => 'array',
                                                        'data' => array(
                                                            'xmlMap' => array(
                                                            ),
                                                        ),
                                                        'filters' => array(
                                                            array(
                                                                'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                                                                'args' => array(
                                                                    '@value',
                                                                    'entry',
                                                                    'key',
                                                                    'value',
                                                                ),
                                                            ),
                                                        ),
                                                        'items' => array(
                                                            'name' => 'entry',
                                                            'type' => 'object',
                                                            'sentAs' => 'entry',
                                                            'additionalProperties' => true,
                                                            'properties' => array(
                                                                'key' => array(
                                                                    'type' => 'string',
                                                                ),
                                                                'value' => array(
                                                                    'description' => 'The value of a field or source document attribute.',
                                                                    'type' => 'string',
                                                                ),
                                                            ),
                                                        ),
                                                        'additionalProperties' => false,
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DefineRankExpressionResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RankExpression' => array(
                    'description' => 'The value of a RankExpression and its current status.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'The expression that is evaluated for ranking or thresholding while processing a search request.',
                            'type' => 'object',
                            'properties' => array(
                                'RankName' => array(
                                    'description' => 'The name of a rank expression. Rank expression names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                    'type' => 'string',
                                ),
                                'RankExpression' => array(
                                    'description' => 'The expression to evaluate for ranking or thresholding while processing a search request. The RankExpression syntax is based on JavaScript expressions and supports:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteDomainResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DomainStatus' => array(
                    'description' => 'The current status of the search domain.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DomainId' => array(
                            'description' => 'An internally generated unique identifier for a domain.',
                            'type' => 'string',
                        ),
                        'DomainName' => array(
                            'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                            'type' => 'string',
                        ),
                        'Created' => array(
                            'description' => 'True if the search domain is created. It can take several minutes to initialize a domain when CreateDomain is called. Newly created search domains are returned from DescribeDomains with a false value for Created until domain creation is complete.',
                            'type' => 'boolean',
                        ),
                        'Deleted' => array(
                            'description' => 'True if the search domain has been deleted. The system must clean up resources dedicated to the search domain when DeleteDomain is called. Newly deleted search domains are returned from DescribeDomains with a true value for IsDeleted for several minutes until resource cleanup is complete.',
                            'type' => 'boolean',
                        ),
                        'NumSearchableDocs' => array(
                            'description' => 'The number of documents that have been submitted to the domain and indexed.',
                            'type' => 'numeric',
                        ),
                        'DocService' => array(
                            'description' => 'The service endpoint for updating documents in a search domain.',
                            'type' => 'object',
                            'properties' => array(
                                'Arn' => array(
                                    'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                    'type' => 'string',
                                ),
                                'Endpoint' => array(
                                    'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'SearchService' => array(
                            'description' => 'The service endpoint for requesting search results from a search domain.',
                            'type' => 'object',
                            'properties' => array(
                                'Arn' => array(
                                    'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                    'type' => 'string',
                                ),
                                'Endpoint' => array(
                                    'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RequiresIndexDocuments' => array(
                            'description' => 'True if IndexDocuments needs to be called to activate the current domain configuration.',
                            'type' => 'boolean',
                        ),
                        'Processing' => array(
                            'description' => 'True if processing is being done to activate the current domain configuration.',
                            'type' => 'boolean',
                        ),
                        'SearchInstanceType' => array(
                            'description' => 'The instance type (such as search.m1.small) that is being used to process search requests.',
                            'type' => 'string',
                        ),
                        'SearchPartitionCount' => array(
                            'description' => 'The number of partitions across which the search index is spread.',
                            'type' => 'numeric',
                        ),
                        'SearchInstanceCount' => array(
                            'description' => 'The number of search instances that are available to process search requests.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'DeleteIndexFieldResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IndexField' => array(
                    'description' => 'The value of an IndexField and its current status.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Defines a field in the index, including its name, type, and the source of its data. The IndexFieldType indicates which of the options will be present. It is invalid to specify options for a type other than the IndexFieldType.',
                            'type' => 'object',
                            'properties' => array(
                                'IndexFieldName' => array(
                                    'description' => 'The name of a field in the search index. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                    'type' => 'string',
                                ),
                                'IndexFieldType' => array(
                                    'description' => 'The type of field. Based on this type, exactly one of the UIntOptions, LiteralOptions or TextOptions must be present.',
                                    'type' => 'string',
                                ),
                                'UIntOptions' => array(
                                    'description' => 'Options for an unsigned integer field. Present if IndexFieldType specifies the field is of type unsigned integer.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for an unsigned integer field. Optional.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                                'LiteralOptions' => array(
                                    'description' => 'Options for literal field. Present if IndexFieldType specifies the field is of type literal.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for a literal field. Optional.',
                                            'type' => 'string',
                                        ),
                                        'SearchEnabled' => array(
                                            'description' => 'Specifies whether search is enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'FacetEnabled' => array(
                                            'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'ResultEnabled' => array(
                                            'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                                'TextOptions' => array(
                                    'description' => 'Options for text field. Present if IndexFieldType specifies the field is of type text.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'DefaultValue' => array(
                                            'description' => 'The default value for a text field. Optional.',
                                            'type' => 'string',
                                        ),
                                        'FacetEnabled' => array(
                                            'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'ResultEnabled' => array(
                                            'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                            'type' => 'boolean',
                                        ),
                                        'TextProcessor' => array(
                                            'description' => 'The text processor to apply to this field. Optional. Possible values:',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'SourceAttributes' => array(
                                    'description' => 'An optional list of source attributes that provide data for this index field. If not specified, the data is pulled from a source attribute with the same name as this IndexField. When one or more source attributes are specified, an optional data transformation can be applied to the source data when populating the index field. You can configure a maximum of 20 sources for an IndexField.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'SourceAttribute',
                                        'description' => 'Identifies the source data for an index field. An optional data transformation can be applied to the source data when populating the index field. By default, the value of the source attribute is copied to the index field.',
                                        'type' => 'object',
                                        'sentAs' => 'member',
                                        'properties' => array(
                                            'SourceDataFunction' => array(
                                                'description' => 'Identifies the transformation to apply when copying data from a source attribute.',
                                                'type' => 'string',
                                            ),
                                            'SourceDataCopy' => array(
                                                'description' => 'Copies data from a source document attribute to an IndexField.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'SourceDataTrimTitle' => array(
                                                'description' => 'Trims common title words from a source document attribute when populating an IndexField. This can be used to create an IndexField you can use for sorting.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                    'Separator' => array(
                                                        'description' => 'The separator that follows the text to trim.',
                                                        'type' => 'string',
                                                    ),
                                                    'Language' => array(
                                                        'description' => 'An IETF RFC 4646 language code. Only the primary language is considered. English (en) is currently the only supported language.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'SourceDataMap' => array(
                                                'description' => 'Maps source document attribute values to new values when populating the IndexField.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'SourceName' => array(
                                                        'description' => 'The name of the document source field to add to this IndexField.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                        'type' => 'string',
                                                    ),
                                                    'Cases' => array(
                                                        'description' => 'A map that translates source field values to custom values.',
                                                        'type' => 'array',
                                                        'data' => array(
                                                            'xmlMap' => array(
                                                            ),
                                                        ),
                                                        'filters' => array(
                                                            array(
                                                                'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                                                                'args' => array(
                                                                    '@value',
                                                                    'entry',
                                                                    'key',
                                                                    'value',
                                                                ),
                                                            ),
                                                        ),
                                                        'items' => array(
                                                            'name' => 'entry',
                                                            'type' => 'object',
                                                            'sentAs' => 'entry',
                                                            'additionalProperties' => true,
                                                            'properties' => array(
                                                                'key' => array(
                                                                    'type' => 'string',
                                                                ),
                                                                'value' => array(
                                                                    'description' => 'The value of a field or source document attribute.',
                                                                    'type' => 'string',
                                                                ),
                                                            ),
                                                        ),
                                                        'additionalProperties' => false,
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteRankExpressionResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RankExpression' => array(
                    'description' => 'The value of a RankExpression and its current status.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'The expression that is evaluated for ranking or thresholding while processing a search request.',
                            'type' => 'object',
                            'properties' => array(
                                'RankName' => array(
                                    'description' => 'The name of a rank expression. Rank expression names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                    'type' => 'string',
                                ),
                                'RankExpression' => array(
                                    'description' => 'The expression to evaluate for ranking or thresholding while processing a search request. The RankExpression syntax is based on JavaScript expressions and supports:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeDefaultSearchFieldResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DefaultSearchField' => array(
                    'description' => 'The name of the IndexField to use for search requests issued with the q parameter. The default is the empty string, which automatically searches all text fields.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'The name of the IndexField to use as the default search field. The default is an empty string, which automatically searches all text fields.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeDomainsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DomainStatusList' => array(
                    'description' => 'The current status of all of your search domains.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DomainStatus',
                        'description' => 'The current status of the search domain.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'DomainId' => array(
                                'description' => 'An internally generated unique identifier for a domain.',
                                'type' => 'string',
                            ),
                            'DomainName' => array(
                                'description' => 'A string that represents the name of a domain. Domain names must be unique across the domains owned by an account within an AWS region. Domain names must start with a letter or number and can contain the following characters: a-z (lowercase), 0-9, and - (hyphen). Uppercase letters and underscores are not allowed.',
                                'type' => 'string',
                            ),
                            'Created' => array(
                                'description' => 'True if the search domain is created. It can take several minutes to initialize a domain when CreateDomain is called. Newly created search domains are returned from DescribeDomains with a false value for Created until domain creation is complete.',
                                'type' => 'boolean',
                            ),
                            'Deleted' => array(
                                'description' => 'True if the search domain has been deleted. The system must clean up resources dedicated to the search domain when DeleteDomain is called. Newly deleted search domains are returned from DescribeDomains with a true value for IsDeleted for several minutes until resource cleanup is complete.',
                                'type' => 'boolean',
                            ),
                            'NumSearchableDocs' => array(
                                'description' => 'The number of documents that have been submitted to the domain and indexed.',
                                'type' => 'numeric',
                            ),
                            'DocService' => array(
                                'description' => 'The service endpoint for updating documents in a search domain.',
                                'type' => 'object',
                                'properties' => array(
                                    'Arn' => array(
                                        'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                        'type' => 'string',
                                    ),
                                    'Endpoint' => array(
                                        'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'SearchService' => array(
                                'description' => 'The service endpoint for requesting search results from a search domain.',
                                'type' => 'object',
                                'properties' => array(
                                    'Arn' => array(
                                        'description' => 'An Amazon Resource Name (ARN). See Identifiers for IAM Entities in Using AWS Identity and Access Management for more information.',
                                        'type' => 'string',
                                    ),
                                    'Endpoint' => array(
                                        'description' => 'The URL (including /version/pathPrefix) to which service requests can be submitted.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'RequiresIndexDocuments' => array(
                                'description' => 'True if IndexDocuments needs to be called to activate the current domain configuration.',
                                'type' => 'boolean',
                            ),
                            'Processing' => array(
                                'description' => 'True if processing is being done to activate the current domain configuration.',
                                'type' => 'boolean',
                            ),
                            'SearchInstanceType' => array(
                                'description' => 'The instance type (such as search.m1.small) that is being used to process search requests.',
                                'type' => 'string',
                            ),
                            'SearchPartitionCount' => array(
                                'description' => 'The number of partitions across which the search index is spread.',
                                'type' => 'numeric',
                            ),
                            'SearchInstanceCount' => array(
                                'description' => 'The number of search instances that are available to process search requests.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeIndexFieldsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IndexFields' => array(
                    'description' => 'The index fields configured for the domain.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'IndexFieldStatus',
                        'description' => 'The value of an IndexField and its current status.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Options' => array(
                                'description' => 'Defines a field in the index, including its name, type, and the source of its data. The IndexFieldType indicates which of the options will be present. It is invalid to specify options for a type other than the IndexFieldType.',
                                'type' => 'object',
                                'properties' => array(
                                    'IndexFieldName' => array(
                                        'description' => 'The name of a field in the search index. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                        'type' => 'string',
                                    ),
                                    'IndexFieldType' => array(
                                        'description' => 'The type of field. Based on this type, exactly one of the UIntOptions, LiteralOptions or TextOptions must be present.',
                                        'type' => 'string',
                                    ),
                                    'UIntOptions' => array(
                                        'description' => 'Options for an unsigned integer field. Present if IndexFieldType specifies the field is of type unsigned integer.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'DefaultValue' => array(
                                                'description' => 'The default value for an unsigned integer field. Optional.',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'LiteralOptions' => array(
                                        'description' => 'Options for literal field. Present if IndexFieldType specifies the field is of type literal.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'DefaultValue' => array(
                                                'description' => 'The default value for a literal field. Optional.',
                                                'type' => 'string',
                                            ),
                                            'SearchEnabled' => array(
                                                'description' => 'Specifies whether search is enabled for this field. Default: False.',
                                                'type' => 'boolean',
                                            ),
                                            'FacetEnabled' => array(
                                                'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                                'type' => 'boolean',
                                            ),
                                            'ResultEnabled' => array(
                                                'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                                'type' => 'boolean',
                                            ),
                                        ),
                                    ),
                                    'TextOptions' => array(
                                        'description' => 'Options for text field. Present if IndexFieldType specifies the field is of type text.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'DefaultValue' => array(
                                                'description' => 'The default value for a text field. Optional.',
                                                'type' => 'string',
                                            ),
                                            'FacetEnabled' => array(
                                                'description' => 'Specifies whether facets are enabled for this field. Default: False.',
                                                'type' => 'boolean',
                                            ),
                                            'ResultEnabled' => array(
                                                'description' => 'Specifies whether values of this field can be returned in search results and used for ranking. Default: False.',
                                                'type' => 'boolean',
                                            ),
                                            'TextProcessor' => array(
                                                'description' => 'The text processor to apply to this field. Optional. Possible values:',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'SourceAttributes' => array(
                                        'description' => 'An optional list of source attributes that provide data for this index field. If not specified, the data is pulled from a source attribute with the same name as this IndexField. When one or more source attributes are specified, an optional data transformation can be applied to the source data when populating the index field. You can configure a maximum of 20 sources for an IndexField.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'SourceAttribute',
                                            'description' => 'Identifies the source data for an index field. An optional data transformation can be applied to the source data when populating the index field. By default, the value of the source attribute is copied to the index field.',
                                            'type' => 'object',
                                            'sentAs' => 'member',
                                            'properties' => array(
                                                'SourceDataFunction' => array(
                                                    'description' => 'Identifies the transformation to apply when copying data from a source attribute.',
                                                    'type' => 'string',
                                                ),
                                                'SourceDataCopy' => array(
                                                    'description' => 'Copies data from a source document attribute to an IndexField.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'SourceName' => array(
                                                            'description' => 'The name of the document source field to add to this IndexField.',
                                                            'type' => 'string',
                                                        ),
                                                        'DefaultValue' => array(
                                                            'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                                'SourceDataTrimTitle' => array(
                                                    'description' => 'Trims common title words from a source document attribute when populating an IndexField. This can be used to create an IndexField you can use for sorting.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'SourceName' => array(
                                                            'description' => 'The name of the document source field to add to this IndexField.',
                                                            'type' => 'string',
                                                        ),
                                                        'DefaultValue' => array(
                                                            'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                            'type' => 'string',
                                                        ),
                                                        'Separator' => array(
                                                            'description' => 'The separator that follows the text to trim.',
                                                            'type' => 'string',
                                                        ),
                                                        'Language' => array(
                                                            'description' => 'An IETF RFC 4646 language code. Only the primary language is considered. English (en) is currently the only supported language.',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                                'SourceDataMap' => array(
                                                    'description' => 'Maps source document attribute values to new values when populating the IndexField.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'SourceName' => array(
                                                            'description' => 'The name of the document source field to add to this IndexField.',
                                                            'type' => 'string',
                                                        ),
                                                        'DefaultValue' => array(
                                                            'description' => 'The default value to use if the source attribute is not specified in a document. Optional.',
                                                            'type' => 'string',
                                                        ),
                                                        'Cases' => array(
                                                            'description' => 'A map that translates source field values to custom values.',
                                                            'type' => 'array',
                                                            'data' => array(
                                                                'xmlMap' => array(
                                                                ),
                                                            ),
                                                            'filters' => array(
                                                                array(
                                                                    'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                                                                    'args' => array(
                                                                        '@value',
                                                                        'entry',
                                                                        'key',
                                                                        'value',
                                                                    ),
                                                                ),
                                                            ),
                                                            'items' => array(
                                                                'name' => 'entry',
                                                                'type' => 'object',
                                                                'sentAs' => 'entry',
                                                                'additionalProperties' => true,
                                                                'properties' => array(
                                                                    'key' => array(
                                                                        'type' => 'string',
                                                                    ),
                                                                    'value' => array(
                                                                        'description' => 'The value of a field or source document attribute.',
                                                                        'type' => 'string',
                                                                    ),
                                                                ),
                                                            ),
                                                            'additionalProperties' => false,
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                                'type' => 'object',
                                'properties' => array(
                                    'CreationDate' => array(
                                        'description' => 'A timestamp for when this option was created.',
                                        'type' => 'string',
                                    ),
                                    'UpdateDate' => array(
                                        'description' => 'A timestamp for when this option was last updated.',
                                        'type' => 'string',
                                    ),
                                    'UpdateVersion' => array(
                                        'description' => 'A unique integer that indicates when this option was last updated.',
                                        'type' => 'numeric',
                                    ),
                                    'State' => array(
                                        'description' => 'The state of processing a change to an option. Possible values:',
                                        'type' => 'string',
                                    ),
                                    'PendingDeletion' => array(
                                        'description' => 'Indicates that the option will be deleted once processing is complete.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeRankExpressionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RankExpressions' => array(
                    'description' => 'The rank expressions configured for the domain.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'RankExpressionStatus',
                        'description' => 'The value of a RankExpression and its current status.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Options' => array(
                                'description' => 'The expression that is evaluated for ranking or thresholding while processing a search request.',
                                'type' => 'object',
                                'properties' => array(
                                    'RankName' => array(
                                        'description' => 'The name of a rank expression. Rank expression names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                                        'type' => 'string',
                                    ),
                                    'RankExpression' => array(
                                        'description' => 'The expression to evaluate for ranking or thresholding while processing a search request. The RankExpression syntax is based on JavaScript expressions and supports:',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                                'type' => 'object',
                                'properties' => array(
                                    'CreationDate' => array(
                                        'description' => 'A timestamp for when this option was created.',
                                        'type' => 'string',
                                    ),
                                    'UpdateDate' => array(
                                        'description' => 'A timestamp for when this option was last updated.',
                                        'type' => 'string',
                                    ),
                                    'UpdateVersion' => array(
                                        'description' => 'A unique integer that indicates when this option was last updated.',
                                        'type' => 'numeric',
                                    ),
                                    'State' => array(
                                        'description' => 'The state of processing a change to an option. Possible values:',
                                        'type' => 'string',
                                    ),
                                    'PendingDeletion' => array(
                                        'description' => 'Indicates that the option will be deleted once processing is complete.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeServiceAccessPoliciesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccessPolicies' => array(
                    'description' => 'A PolicyDocument that specifies access policies for the search domain\'s services, and the current status of those policies.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'An IAM access policy as described in The Access Policy Language in Using AWS Identity and Access Management. The maximum size of an access policy document is 100 KB.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeStemmingOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stems' => array(
                    'description' => 'The stemming options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Maps terms to their stems, serialized as a JSON document. The document has a single object with one property "stems" whose value is an object mapping terms to their stems. The maximum size of a stemming document is 500 KB. Example: { "stems": {"people": "person", "walking": "walk"} }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeStopwordOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stopwords' => array(
                    'description' => 'The stopword options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Lists stopwords serialized as a JSON document. The document has a single object with one property "stopwords" whose value is an array of strings. The maximum size of a stopwords document is 10 KB. Example: { "stopwords": ["a", "an", "the", "of"] }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSynonymOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Synonyms' => array(
                    'description' => 'The synonym options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Maps terms to their synonyms, serialized as a JSON document. The document has a single object with one property "synonyms" whose value is an object mapping terms to their synonyms. Each synonym is a simple string or an array of strings. The maximum size of a stopwords document is 100 KB. Example: { "synonyms": {"cat": ["feline", "kitten"], "puppy": "dog"} }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'IndexDocumentsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'FieldNames' => array(
                    'description' => 'The names of the fields that are currently being processed due to an IndexDocuments action.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'FieldName',
                        'description' => 'A string that represents the name of an index field. Field names must begin with a letter and can contain the following characters: a-z (lowercase), 0-9, and _ (underscore). Uppercase letters and hyphens are not allowed. The names "body", "docid", and "text_relevance" are reserved and cannot be specified as field or rank expression names.',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'UpdateDefaultSearchFieldResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DefaultSearchField' => array(
                    'description' => 'The value of the DefaultSearchField configured for this search domain and its current status.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'The name of the IndexField to use as the default search field. The default is an empty string, which automatically searches all text fields.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UpdateServiceAccessPoliciesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccessPolicies' => array(
                    'description' => 'A PolicyDocument that specifies access policies for the search domain\'s services, and the current status of those policies.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'An IAM access policy as described in The Access Policy Language in Using AWS Identity and Access Management. The maximum size of an access policy document is 100 KB.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UpdateStemmingOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stems' => array(
                    'description' => 'The stemming options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Maps terms to their stems, serialized as a JSON document. The document has a single object with one property "stems" whose value is an object mapping terms to their stems. The maximum size of a stemming document is 500 KB. Example: { "stems": {"people": "person", "walking": "walk"} }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UpdateStopwordOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stopwords' => array(
                    'description' => 'The stopword options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Lists stopwords serialized as a JSON document. The document has a single object with one property "stopwords" whose value is an array of strings. The maximum size of a stopwords document is 10 KB. Example: { "stopwords": ["a", "an", "the", "of"] }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UpdateSynonymOptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Synonyms' => array(
                    'description' => 'The synonym options configured for this search domain and the current status of those options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Options' => array(
                            'description' => 'Maps terms to their synonyms, serialized as a JSON document. The document has a single object with one property "synonyms" whose value is an object mapping terms to their synonyms. Each synonym is a simple string or an array of strings. The maximum size of a stopwords document is 100 KB. Example: { "synonyms": {"cat": ["feline", "kitten"], "puppy": "dog"} }',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of an option, including when it was last updated and whether it is actively in use for searches.',
                            'type' => 'object',
                            'properties' => array(
                                'CreationDate' => array(
                                    'description' => 'A timestamp for when this option was created.',
                                    'type' => 'string',
                                ),
                                'UpdateDate' => array(
                                    'description' => 'A timestamp for when this option was last updated.',
                                    'type' => 'string',
                                ),
                                'UpdateVersion' => array(
                                    'description' => 'A unique integer that indicates when this option was last updated.',
                                    'type' => 'numeric',
                                ),
                                'State' => array(
                                    'description' => 'The state of processing a change to an option. Possible values:',
                                    'type' => 'string',
                                ),
                                'PendingDeletion' => array(
                                    'description' => 'Indicates that the option will be deleted once processing is complete.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeDomains' => array(
                'result_key' => 'DomainStatusList',
            ),
            'DescribeIndexFields' => array(
                'result_key' => 'IndexFields',
            ),
            'DescribeRankExpressions' => array(
                'result_key' => 'RankExpressions',
            ),
        ),
    ),
);
