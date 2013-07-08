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
    'apiVersion' => '2009-04-15',
    'endpointPrefix' => 'sdb',
    'serviceFullName' => 'Amazon SimpleDB',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'SimpleDb',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.ap-southeast-1.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sdb.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'BatchDeleteAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Performs multiple DeleteAttributes operations in a single call, which reduces round trips and latencies. This enables Amazon SimpleDB to optimize requests, which generally yields better throughput.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'BatchDeleteAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the attributes are being deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Items' => array(
                    'required' => true,
                    'description' => 'A list of items on which to perform the operation.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Item',
                    'items' => array(
                        'name' => 'Item',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'type' => 'string',
                                'sentAs' => 'ItemName',
                            ),
                            'Attributes' => array(
                                'type' => 'array',
                                'sentAs' => 'Attribute',
                                'items' => array(
                                    'name' => 'Attribute',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'required' => true,
                                            'description' => 'The name of the attribute.',
                                            'type' => 'string',
                                        ),
                                        'AlternateNameEncoding' => array(
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'required' => true,
                                            'description' => 'The value of the attribute.',
                                            'type' => 'string',
                                        ),
                                        'AlternateValueEncoding' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'BatchPutAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The BatchPutAttributes operation creates or replaces attributes within one or more items. By using this operation, the client can perform multiple PutAttribute operation with a single call. This helps yield savings in round trips and latencies, enabling Amazon SimpleDB to optimize requests and generally produce better throughput.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'BatchPutAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the attributes are being stored.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Items' => array(
                    'required' => true,
                    'description' => 'A list of items on which to perform the operation.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Item',
                    'items' => array(
                        'name' => 'Item',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the replaceable item.',
                                'type' => 'string',
                                'sentAs' => 'ItemName',
                            ),
                            'Attributes' => array(
                                'required' => true,
                                'description' => 'The list of attributes for a replaceable item.',
                                'type' => 'array',
                                'sentAs' => 'Attribute',
                                'items' => array(
                                    'name' => 'Attribute',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'required' => true,
                                            'description' => 'The name of the replaceable attribute.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'required' => true,
                                            'description' => 'The value of the replaceable attribute.',
                                            'type' => 'string',
                                        ),
                                        'Replace' => array(
                                            'description' => 'A flag specifying whether or not to replace the attribute/value pair or to add a new attribute/value pair. The default setting is false.',
                                            'type' => 'boolean',
                                            'format' => 'boolean-string',
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
                    'reason' => 'The item name was specified more than once.',
                    'class' => 'DuplicateItemNameException',
                ),
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
                array(
                    'reason' => 'Too many attributes in this item.',
                    'class' => 'NumberItemAttributesExceededException',
                ),
                array(
                    'reason' => 'Too many attributes in this domain.',
                    'class' => 'NumberDomainAttributesExceededException',
                ),
                array(
                    'reason' => 'Too many bytes in this domain.',
                    'class' => 'NumberDomainBytesExceededException',
                ),
                array(
                    'reason' => 'Too many items exist in a single call.',
                    'class' => 'NumberSubmittedItemsExceededException',
                ),
                array(
                    'reason' => 'Too many attributes exist in a single call.',
                    'class' => 'NumberSubmittedAttributesExceededException',
                ),
            ),
        ),
        'CreateDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The CreateDomain operation creates a new domain. The domain name should be unique among the domains associated with the Access Key ID provided in the request. The CreateDomain operation may take 10 or more seconds to complete.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDomain',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain to create. The name can range between 3 and 255 characters and can contain the following characters: a-z, A-Z, 0-9, \'_\', \'-\', and \'.\'.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'Too many domains exist per this account.',
                    'class' => 'NumberDomainsExceededException',
                ),
            ),
        ),
        'DeleteAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes one or more attributes associated with an item. If all attributes of the item are deleted, the item is deleted.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which to perform the operation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ItemName' => array(
                    'required' => true,
                    'description' => 'The name of the item. Similar to rows on a spreadsheet, items represent individual objects that contain one or more value-attribute pairs.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attributes' => array(
                    'description' => 'A list of Attributes. Similar to columns on a spreadsheet, attributes represent categories of data that can be assigned to items.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Attribute',
                    'items' => array(
                        'name' => 'Attribute',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the attribute.',
                                'type' => 'string',
                            ),
                            'AlternateNameEncoding' => array(
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'The value of the attribute.',
                                'type' => 'string',
                            ),
                            'AlternateValueEncoding' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Expected' => array(
                    'description' => 'The update condition which, if specified, determines whether the specified attributes will be deleted or not. The update condition must be satisfied in order for this request to be processed and the attributes to be deleted.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Name' => array(
                            'description' => 'The name of the attribute involved in the condition.',
                            'type' => 'string',
                        ),
                        'Value' => array(
                            'description' => 'The value of an attribute. This value can only be specified when the Exists parameter is equal to true.',
                            'type' => 'string',
                        ),
                        'Exists' => array(
                            'description' => 'A value specifying whether or not the specified attribute must exist with the specified value in order for the update condition to be satisfied. Specify true if the attribute must exist for the update condition to be satisfied. Specify false if the attribute should not exist in order for the update condition to be satisfied.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
                array(
                    'reason' => 'The specified attribute does not exist.',
                    'class' => 'AttributeDoesNotExistException',
                ),
            ),
        ),
        'DeleteDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeleteDomain operation deletes a domain. Any items (and their attributes) in the domain are deleted as well. The DeleteDomain operation might take 10 or more seconds to complete.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDomain',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
            ),
        ),
        'DomainMetadata' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DomainMetadataResult',
            'responseType' => 'model',
            'summary' => 'Returns information about the domain, including when the domain was created, the number of items and attributes in the domain, and the size of the attribute names and values.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DomainMetadata',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain for which to display the metadata of.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
            ),
        ),
        'GetAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetAttributesResult',
            'responseType' => 'model',
            'summary' => 'Returns all of the attributes associated with the specified item. Optionally, the attributes returned can be limited to one or more attributes by specifying an attribute name parameter.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which to perform the operation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ItemName' => array(
                    'required' => true,
                    'description' => 'The name of the item.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeNames' => array(
                    'description' => 'The names of the attributes.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AttributeName',
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'ConsistentRead' => array(
                    'description' => 'Determines whether or not strong consistency should be enforced when data is read from SimpleDB. If true, any data previously written to SimpleDB will be returned. Otherwise, results will be consistent eventually, and the client may not see data that was written immediately before your read.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
            ),
        ),
        'ListDomains' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListDomainsResult',
            'responseType' => 'model',
            'summary' => 'The ListDomains operation lists all domains associated with the Access Key ID. It returns domain names up to the limit set by MaxNumberOfDomains. A NextToken is returned if there are more than MaxNumberOfDomains domains. Calling ListDomains successive times with the NextToken provided by the operation returns up to MaxNumberOfDomains more domain names with each successive operation call.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListDomains',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'MaxNumberOfDomains' => array(
                    'description' => 'The maximum number of domain names you want returned. The range is 1 to 100. The default setting is 100.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'A string informing Amazon SimpleDB where to start the next list of domain names.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The specified NextToken is not valid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'PutAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The PutAttributes operation creates or replaces attributes in an item. The client may specify new attributes using a combination of the Attribute.X.Name and Attribute.X.Value parameters. The client specifies the first attribute by the parameters Attribute.0.Name and Attribute.0.Value, the second attribute by the parameters Attribute.1.Name and Attribute.1.Value, and so on.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'DomainName' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which to perform the operation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ItemName' => array(
                    'required' => true,
                    'description' => 'The name of the item.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attributes' => array(
                    'required' => true,
                    'description' => 'The list of attributes.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Attribute',
                    'items' => array(
                        'name' => 'Attribute',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the replaceable attribute.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'The value of the replaceable attribute.',
                                'type' => 'string',
                            ),
                            'Replace' => array(
                                'description' => 'A flag specifying whether or not to replace the attribute/value pair or to add a new attribute/value pair. The default setting is false.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'Expected' => array(
                    'description' => 'The update condition which, if specified, determines whether the specified attributes will be updated or not. The update condition must be satisfied in order for this request to be processed and the attributes to be updated.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Name' => array(
                            'description' => 'The name of the attribute involved in the condition.',
                            'type' => 'string',
                        ),
                        'Value' => array(
                            'description' => 'The value of an attribute. This value can only be specified when the Exists parameter is equal to true.',
                            'type' => 'string',
                        ),
                        'Exists' => array(
                            'description' => 'A value specifying whether or not the specified attribute must exist with the specified value in order for the update condition to be satisfied. Specify true if the attribute must exist for the update condition to be satisfied. Specify false if the attribute should not exist in order for the update condition to be satisfied.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
                array(
                    'reason' => 'Too many attributes in this domain.',
                    'class' => 'NumberDomainAttributesExceededException',
                ),
                array(
                    'reason' => 'Too many bytes in this domain.',
                    'class' => 'NumberDomainBytesExceededException',
                ),
                array(
                    'reason' => 'Too many attributes in this item.',
                    'class' => 'NumberItemAttributesExceededException',
                ),
                array(
                    'reason' => 'The specified attribute does not exist.',
                    'class' => 'AttributeDoesNotExistException',
                ),
            ),
        ),
        'Select' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SelectResult',
            'responseType' => 'model',
            'summary' => 'The Select operation returns a set of attributes for ItemNames that match the select expression. Select is similar to the standard SQL SELECT statement.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'Select',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-04-15',
                ),
                'SelectExpression' => array(
                    'required' => true,
                    'description' => 'The expression used to query the domain.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'A string informing Amazon SimpleDB where to start the next list of ItemNames.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ConsistentRead' => array(
                    'description' => 'Determines whether or not strong consistency should be enforced when data is read from SimpleDB. If true, any data previously written to SimpleDB will be returned. Otherwise, results will be consistent eventually, and the client may not see data that was written immediately before your read.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value for a parameter is invalid.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'The specified NextToken is not valid.',
                    'class' => 'InvalidNextTokenException',
                ),
                array(
                    'reason' => 'Too many predicates exist in the query expression.',
                    'class' => 'InvalidNumberPredicatesException',
                ),
                array(
                    'reason' => 'Too many predicates exist in the query expression.',
                    'class' => 'InvalidNumberValueTestsException',
                ),
                array(
                    'reason' => 'The specified query expression syntax is not valid.',
                    'class' => 'InvalidQueryExpressionException',
                ),
                array(
                    'reason' => 'The request must contain the specified missing parameter.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'The specified domain does not exist.',
                    'class' => 'NoSuchDomainException',
                ),
                array(
                    'reason' => 'A timeout occurred when attempting to query the specified domain with specified query expression.',
                    'class' => 'RequestTimeoutException',
                ),
                array(
                    'reason' => 'Too many attributes requested.',
                    'class' => 'TooManyRequestedAttributesException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'DomainMetadataResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ItemCount' => array(
                    'description' => 'The number of all items in the domain.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'ItemNamesSizeBytes' => array(
                    'description' => 'The total size of all item names in the domain, in bytes.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'AttributeNameCount' => array(
                    'description' => 'The number of unique attribute names in the domain.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'AttributeNamesSizeBytes' => array(
                    'description' => 'The total size of all unique attribute names in the domain, in bytes.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'AttributeValueCount' => array(
                    'description' => 'The number of all attribute name/value pairs in the domain.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'AttributeValuesSizeBytes' => array(
                    'description' => 'The total size of all attribute values in the domain, in bytes.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Timestamp' => array(
                    'description' => 'The data and time when metadata was calculated, in Epoch (UNIX) seconds.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetAttributesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'The list of attributes returned by the operation.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Attribute',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'Attribute',
                        'type' => 'object',
                        'sentAs' => 'Attribute',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name of the attribute.',
                                'type' => 'string',
                            ),
                            'AlternateNameEncoding' => array(
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The value of the attribute.',
                                'type' => 'string',
                            ),
                            'AlternateValueEncoding' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListDomainsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DomainNames' => array(
                    'description' => 'A list of domain names that match the expression.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'DomainName',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'DomainName',
                        'type' => 'string',
                        'sentAs' => 'DomainName',
                    ),
                ),
                'NextToken' => array(
                    'description' => 'An opaque token indicating that there are more domains than the specified MaxNumberOfDomains still available.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'SelectResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Items' => array(
                    'description' => 'A list of items that match the select expression.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Item',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'Item',
                        'type' => 'object',
                        'sentAs' => 'Item',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name of the item.',
                                'type' => 'string',
                            ),
                            'AlternateNameEncoding' => array(
                                'type' => 'string',
                            ),
                            'Attributes' => array(
                                'description' => 'A list of attributes.',
                                'type' => 'array',
                                'sentAs' => 'Attribute',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'Attribute',
                                    'type' => 'object',
                                    'sentAs' => 'Attribute',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the attribute.',
                                            'type' => 'string',
                                        ),
                                        'AlternateNameEncoding' => array(
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value of the attribute.',
                                            'type' => 'string',
                                        ),
                                        'AlternateValueEncoding' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'An opaque token indicating that more items than MaxNumberOfItems were matched, the response size exceeded 1 megabyte, or the execution time exceeded 5 seconds.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
);
