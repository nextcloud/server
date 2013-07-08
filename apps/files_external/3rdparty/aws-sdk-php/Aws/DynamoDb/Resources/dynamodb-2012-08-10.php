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
    'apiVersion' => '2012-08-10',
    'endpointPrefix' => 'dynamodb',
    'serviceFullName' => 'Amazon DynamoDB',
    'serviceAbbreviation' => 'DynamoDB',
    'serviceType' => 'json',
    'jsonVersion' => '1.0',
    'targetPrefix' => 'DynamoDB_20120810.',
    'signatureVersion' => 'v4',
    'namespace' => 'DynamoDb',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'dynamodb.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'dynamodb.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'BatchGetItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'BatchGetItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The BatchGetItem operation returns the attributes of one or more items from one or more tables. You identify requested items by primary key.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.BatchGetItem',
                ),
                'RequestItems' => array(
                    'required' => true,
                    'description' => 'A map of one or more table names and, for each table, the corresponding primary keys for the items to retrieve. Each table name can be invoked only once.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents a set of primary keys and, for each key, the attributes to retrieve from the table.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'TableName',
                            'key_pattern' => '/[a-zA-Z0-9_.-]+/',
                        ),
                        'properties' => array(
                            'Keys' => array(
                                'required' => true,
                                'description' => 'Represents the primary key attribute values that define the items and the attributes associated with the items.',
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 100,
                                'items' => array(
                                    'name' => 'Key',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                        'type' => 'object',
                                        'data' => array(
                                            'shape_name' => 'AttributeName',
                                        ),
                                        'properties' => array(
                                            'S' => array(
                                                'description' => 'Represents a String data type',
                                                'type' => 'string',
                                            ),
                                            'N' => array(
                                                'description' => 'Represents a Number data type',
                                                'type' => 'string',
                                            ),
                                            'B' => array(
                                                'description' => 'Represents a Binary data type',
                                                'type' => 'string',
                                                'filters' => array(
                                                    'base64_encode',
                                                ),
                                            ),
                                            'SS' => array(
                                                'description' => 'Represents a String set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'StringAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'NS' => array(
                                                'description' => 'Represents a Number set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NumberAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'BS' => array(
                                                'description' => 'Represents a Binary set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'BinaryAttributeValue',
                                                    'type' => 'string',
                                                    'filters' => array(
                                                        'base64_encode',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'AttributesToGet' => array(
                                'description' => 'Represents one or more attributes to retrieve from the table or index. If no attribute names are specified then all attributes will be returned. If any of the specified attributes are not found, they will not appear in the result.',
                                'type' => 'array',
                                'minItems' => 1,
                                'items' => array(
                                    'name' => 'AttributeName',
                                    'type' => 'string',
                                ),
                            ),
                            'ConsistentRead' => array(
                                'description' => 'Represents the consistency of a read operation. If set to true, then a strongly consistent read is used; otherwise, an eventually consistent read is used.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'BatchWriteItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'BatchWriteItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The BatchWriteItem operation puts or deletes multiple items in one or more tables. A single call to BatchWriteItem can write up to 1 MB of data, which can comprise as many as 25 put or delete requests. Individual items to be written can be as large as 64 KB.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.BatchWriteItem',
                ),
                'RequestItems' => array(
                    'required' => true,
                    'description' => 'A map of one or more table names and, for each table, a list of operations to be performed (DeleteRequest or PutRequest). Each element in the map consists of the following:',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'array',
                        'minItems' => 1,
                        'maxItems' => 25,
                        'data' => array(
                            'shape_name' => 'TableName',
                            'key_pattern' => '/[a-zA-Z0-9_.-]+/',
                        ),
                        'items' => array(
                            'name' => 'WriteRequest',
                            'description' => 'Represents an operation to perform - either DeleteItem or PutItem. You can only specify one of these operations, not both, in a single WriteRequest. If you do need to perform both of these operations, you will need to specify two separate WriteRequest objects.',
                            'type' => 'object',
                            'properties' => array(
                                'PutRequest' => array(
                                    'description' => 'Represents a request to perform a DeleteItem operation.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Item' => array(
                                            'required' => true,
                                            'description' => 'A map of attribute name to attribute values, representing the primary key of an item to be processed by PutItem. All of the table\'s primary key attributes must be specified, and their data types must match those of the table\'s key schema. If any attributes are present in the item which are part of an index key schema for the table, their types must match the index key schema.',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                                'type' => 'object',
                                                'data' => array(
                                                    'shape_name' => 'AttributeName',
                                                ),
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Represents a String data type',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Represents a Number data type',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Represents a Binary data type',
                                                        'type' => 'string',
                                                        'filters' => array(
                                                            'base64_encode',
                                                        ),
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'Represents a String set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'Represents a Number set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'Represents a Binary set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'BinaryAttributeValue',
                                                            'type' => 'string',
                                                            'filters' => array(
                                                                'base64_encode',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                                'DeleteRequest' => array(
                                    'description' => 'Represents a request to perform a PutItem operation.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'required' => true,
                                            'description' => 'A map of attribute name to attribute values, representing the primary key of the item to delete. All of the table\'s primary key attributes must be specified, and their data types must match those of the table\'s key schema.',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                                'type' => 'object',
                                                'data' => array(
                                                    'shape_name' => 'AttributeName',
                                                ),
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Represents a String data type',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Represents a Number data type',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Represents a Binary data type',
                                                        'type' => 'string',
                                                        'filters' => array(
                                                            'base64_encode',
                                                        ),
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'Represents a String set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'Represents a Number set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'Represents a Binary set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'BinaryAttributeValue',
                                                            'type' => 'string',
                                                            'filters' => array(
                                                                'base64_encode',
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
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
                'ReturnItemCollectionMetrics' => array(
                    'description' => 'If set to SIZE, statistics about item collections, if any, that were modified during the operation are returned in the response. If set to NONE (the default), no statistics are returned..',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'SIZE',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An item collection is too large. This exception is only returned for tables that have one or more local secondary indexes.',
                    'class' => 'ItemCollectionSizeLimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'CreateTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateTableOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The CreateTable operation adds a new table to your account. In an AWS account, table names must be unique within each region. That is, you can have two tables with same name if you create the tables in different regions.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.CreateTable',
                ),
                'AttributeDefinitions' => array(
                    'required' => true,
                    'description' => 'An array of attributes that describe the key schema for the table and indexes.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'AttributeDefinition',
                        'description' => 'Specifies an attribute for describing the key schema for the table and indexes.',
                        'type' => 'object',
                        'properties' => array(
                            'AttributeName' => array(
                                'required' => true,
                                'description' => 'A name for the attribute.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'AttributeType' => array(
                                'required' => true,
                                'description' => 'The data type for the attribute.',
                                'type' => 'string',
                                'enum' => array(
                                    'S',
                                    'N',
                                    'B',
                                ),
                            ),
                        ),
                    ),
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table to create.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'KeySchema' => array(
                    'required' => true,
                    'description' => 'Specifies the attributes that make up the primary key for the table. The attributes in KeySchema must also be defined in the AttributeDefinitions array. For more information, see Data Model in the Amazon DynamoDB Developer Guide.',
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'maxItems' => 2,
                    'items' => array(
                        'name' => 'KeySchemaElement',
                        'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                        'type' => 'object',
                        'properties' => array(
                            'AttributeName' => array(
                                'required' => true,
                                'description' => 'Represents the name of a key attribute.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'KeyType' => array(
                                'required' => true,
                                'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                'type' => 'string',
                                'enum' => array(
                                    'HASH',
                                    'RANGE',
                                ),
                            ),
                        ),
                    ),
                ),
                'LocalSecondaryIndexes' => array(
                    'description' => 'One or more secondary indexes (the maximum is five) to be created on the table. Each index is scoped to a given hash key value. There is a 10 gigabyte size limit per hash key; otherwise, the size of a local secondary index is unconstrained.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'LocalSecondaryIndex',
                        'description' => 'Represents a local secondary index.',
                        'type' => 'object',
                        'properties' => array(
                            'IndexName' => array(
                                'required' => true,
                                'description' => 'Represents the name of the secondary index. The name must be unique among all other indexes on this table.',
                                'type' => 'string',
                                'minLength' => 3,
                                'maxLength' => 255,
                            ),
                            'KeySchema' => array(
                                'required' => true,
                                'description' => 'Represents the complete index key schema, which consists of one or more pairs of attribute names and key types (HASH or RANGE).',
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 2,
                                'items' => array(
                                    'name' => 'KeySchemaElement',
                                    'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'required' => true,
                                            'description' => 'Represents the name of a key attribute.',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 255,
                                        ),
                                        'KeyType' => array(
                                            'required' => true,
                                            'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                            'type' => 'string',
                                            'enum' => array(
                                                'HASH',
                                                'RANGE',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Projection' => array(
                                'required' => true,
                                'type' => 'object',
                                'properties' => array(
                                    'ProjectionType' => array(
                                        'description' => 'Represents the set of attributes that are projected into the index:',
                                        'type' => 'string',
                                        'enum' => array(
                                            'ALL',
                                            'KEYS_ONLY',
                                            'INCLUDE',
                                        ),
                                    ),
                                    'NonKeyAttributes' => array(
                                        'description' => 'Represents the non-key attribute names which will be projected into the index.',
                                        'type' => 'array',
                                        'minItems' => 1,
                                        'maxItems' => 20,
                                        'items' => array(
                                            'name' => 'NonKeyAttributeName',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 255,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ProvisionedThroughput' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ReadCapacityUnits' => array(
                            'required' => true,
                            'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. For more information, see Specifying Read and Write Requirements in the Amazon DynamoDB Developer Guide.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                        'WriteCapacityUnits' => array(
                            'required' => true,
                            'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException. For more information, see Specifying Read and Write Requirements in the Amazon DynamoDB Developer Guide.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation conflicts with the resource\'s availability. For example, you attempted to recreate an existing table, or tried to delete a table currently in the CREATING state.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'The number of concurrent table requests (cumulative number of tables in the CREATING, DELETING or UPDATING state) exceeds the maximum allowed of 10.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a single item in a table by primary key. You can perform a conditional delete operation that deletes the item if it exists, or if it has an expected attribute value.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.DeleteItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table from which to delete the item.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'description' => 'A map of attribute names to AttributeValue objects, representing the primary key of the item to delete.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Expected' => array(
                    'description' => 'A map of attribute/condition pairs. This is the conditional block for the DeleteItemoperation. All the conditions must be met for the operation to succeed.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'An attribute value used with conditional DeleteItem, PutItem or UpdateItem operations. Amazon DynamoDB will check to see if the attribute value already exists; or if the attribute exists and has a particular value before updating it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Exists' => array(
                                'description' => 'Causes Amazon DynamoDB to evaluate the value before attempting a conditional operation:',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
                    'description' => 'Use ReturnValues if you want to get the item attributes as they appeared before they were deleted. For DeleteItem, the valid values are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'NONE',
                        'ALL_OLD',
                        'UPDATED_OLD',
                        'ALL_NEW',
                        'UPDATED_NEW',
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
                'ReturnItemCollectionMetrics' => array(
                    'description' => 'If set to SIZE, statistics about item collections, if any, that were modified during the operation are returned in the response. If set to NONE (the default), no statistics are returned..',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'SIZE',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A condition specified in the operation could not be evaluated.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An item collection is too large. This exception is only returned for tables that have one or more local secondary indexes.',
                    'class' => 'ItemCollectionSizeLimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteTableOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The DeleteTable operation deletes a table and all of its items. After a DeleteTable request, the specified table is in the DELETING state until Amazon DynamoDB completes the deletion. If the table is in the ACTIVE state, you can delete it. If a table is in CREATING or UPDATING states, then Amazon DynamoDB returns a ResourceInUseException. If the specified table does not exist, Amazon DynamoDB returns a ResourceNotFoundException. If table is already in the DELETING state, no error is returned.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.DeleteTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table to delete.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation conflicts with the resource\'s availability. For example, you attempted to recreate an existing table, or tried to delete a table currently in the CREATING state.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The number of concurrent table requests (cumulative number of tables in the CREATING, DELETING or UPDATING state) exceeds the maximum allowed of 10.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTableOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about the table, including the current status of the table, when it was created, the primary key schema, and any indexes on the table.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.DescribeTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table to describe.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'GetItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'GetItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The GetItem operation returns a set of attributes for the item with the given primary key. If there is no matching item, GetItem does not return any data.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.GetItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table containing the requested item.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'description' => 'A map of attribute names to AttributeValue objects, representing the primary key of the item to retrieve.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'AttributesToGet' => array(
                    'description' => 'The names of one or more attributes to retrieve. If no attribute names are specified, then all attributes will be returned. If any of the requested attributes are not found, they will not appear in the result.',
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'ConsistentRead' => array(
                    'description' => 'If set to true, then the operation uses strongly consistent reads; otherwise, eventually consistent reads are used.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ListTables' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListTablesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns an array of all the tables associated with the current account and endpoint.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.ListTables',
                ),
                'ExclusiveStartTableName' => array(
                    'description' => 'The name of the table that starts the list. If you already ran a ListTables operation and received a LastEvaluatedTableName value in the response, use that value here to continue the list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Limit' => array(
                    'description' => 'A maximum number of table names to return.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'PutItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'PutItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new item, or replaces an old item with a new item. If an item already exists in the specified table with the same primary key, the new item completely replaces the existing item. You can perform a conditional put (insert a new item if one with the specified primary key doesn\'t exist), or replace an existing item if it has certain attribute values.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.PutItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table to contain the item.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Item' => array(
                    'required' => true,
                    'description' => 'A map of attribute name/value pairs, one for each attribute. Only the primary key attributes are required; you can optionally provide other attribute name-value pairs for the item.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Expected' => array(
                    'description' => 'A map of attribute/condition pairs. This is the conditional block for the PutItem operation. All the conditions must be met for the operation to succeed.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'An attribute value used with conditional DeleteItem, PutItem or UpdateItem operations. Amazon DynamoDB will check to see if the attribute value already exists; or if the attribute exists and has a particular value before updating it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Exists' => array(
                                'description' => 'Causes Amazon DynamoDB to evaluate the value before attempting a conditional operation:',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
                    'description' => 'Use ReturnValues if you want to get the item attributes as they appeared before they were updated with the PutItem request. For PutItem, the valid values are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'NONE',
                        'ALL_OLD',
                        'UPDATED_OLD',
                        'ALL_NEW',
                        'UPDATED_NEW',
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
                'ReturnItemCollectionMetrics' => array(
                    'description' => 'If set to SIZE, statistics about item collections, if any, that were modified during the operation are returned in the response. If set to NONE (the default), no statistics are returned..',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'SIZE',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A condition specified in the operation could not be evaluated.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An item collection is too large. This exception is only returned for tables that have one or more local secondary indexes.',
                    'class' => 'ItemCollectionSizeLimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'Query' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'QueryOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'A Query operation directly accesses items from a table using the table primary key, or from an index using the index key. You must provide a specific hash key value. You can narrow the scope of the query by using comparison operators on the range key value, or on the index key. You can use the ScanIndexForward parameter to get results in forward or reverse order, by range key or by index key.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.Query',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table containing the requested items.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'IndexName' => array(
                    'description' => 'The name of an index on the table to query.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Select' => array(
                    'description' => 'The attributes to be returned in the result. You can retrieve all item attributes, specific item attributes, the count of matching items, or in the case of an index, some or all of the attributes projected into the index.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ALL_ATTRIBUTES',
                        'ALL_PROJECTED_ATTRIBUTES',
                        'SPECIFIC_ATTRIBUTES',
                        'COUNT',
                    ),
                ),
                'AttributesToGet' => array(
                    'description' => 'The names of one or more attributes to retrieve. If no attribute names are specified, then all attributes will be returned. If any of the requested attributes are not found, they will not appear in the result.',
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'Limit' => array(
                    'description' => 'The maximum number of items to evaluate (not necessarily the number of matching items). If Amazon DynamoDB processes the number of items up to the limit while processing the results, it stops the operation and returns the matching values up to that point, and a LastEvaluatedKey to apply in a subsequent operation, so that you can pick up where you left off. Also, if the processed data set size exceeds 1 MB before Amazon DynamoDB reaches this limit, it stops the operation and returns the matching values up to the limit, and a LastEvaluatedKey to apply in a subsequent operation to continue the operation. For more information see Query and Scan in the Amazon DynamoDB Developer Guide.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
                'ConsistentRead' => array(
                    'description' => 'If set to true, then the operation uses strongly consistent reads; otherwise, eventually consistent reads are used.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'KeyConditions' => array(
                    'description' => 'The selection criteria for the query.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents a selection criteria for a Query or Scan operation.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'AttributeValueList' => array(
                                'description' => 'Represents one or more values to evaluate against the supplied attribute. This list contains exactly one value, except for a BETWEEN or IN comparison, in which case the list contains two values.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeValue',
                                    'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'S' => array(
                                            'description' => 'Represents a String data type',
                                            'type' => 'string',
                                        ),
                                        'N' => array(
                                            'description' => 'Represents a Number data type',
                                            'type' => 'string',
                                        ),
                                        'B' => array(
                                            'description' => 'Represents a Binary data type',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                        'SS' => array(
                                            'description' => 'Represents a String set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'StringAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'NS' => array(
                                            'description' => 'Represents a Number set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'NumberAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'BS' => array(
                                            'description' => 'Represents a Binary set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'BinaryAttributeValue',
                                                'type' => 'string',
                                                'filters' => array(
                                                    'base64_encode',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'ComparisonOperator' => array(
                                'required' => true,
                                'description' => 'Represents a comparator for evaluating attributes. For example, equals, greater than, less than, etc.',
                                'type' => 'string',
                                'enum' => array(
                                    'EQ',
                                    'NE',
                                    'IN',
                                    'LE',
                                    'LT',
                                    'GE',
                                    'GT',
                                    'BETWEEN',
                                    'NOT_NULL',
                                    'NULL',
                                    'CONTAINS',
                                    'NOT_CONTAINS',
                                    'BEGINS_WITH',
                                ),
                            ),
                        ),
                    ),
                ),
                'ScanIndexForward' => array(
                    'description' => 'Specifies ascending (true) or descending (false) traversal of the index. Amazon DynamoDB returns results reflecting the requested order determined by the range key. If the data type is Number, the results are returned in numeric order. For String, the results are returned in order of ASCII character code values. For Binary, Amazon DynamoDB treats each byte of the binary data as unsigned when it compares binary values.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'ExclusiveStartKey' => array(
                    'description' => 'The primary key of the item from which to continue an earlier operation. An earlier operation might provide this value as the LastEvaluatedKey if that operation was interrupted before completion; either because of the result set size or because of the setting for Limit. The LastEvaluatedKey can be passed back in a new request to continue the operation from that point.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'Scan' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ScanOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'The Scan operation returns one or more items and item attributes by accessing every item in the table. To have Amazon DynamoDB return fewer items, you can provide a ScanFilter.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.Scan',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table containing the requested items.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'AttributesToGet' => array(
                    'description' => 'The names of one or more attributes to retrieve. If no attribute names are specified, then all attributes will be returned. If any of the requested attributes are not found, they will not appear in the result.',
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'Limit' => array(
                    'description' => 'The maximum number of items to evaluate (not necessarily the number of matching items). If Amazon DynamoDB processes the number of items up to the limit while processing the results, it stops the operation and returns the matching values up to that point, and a LastEvaluatedKey to apply in a subsequent operation, so that you can pick up where you left off. Also, if the processed data set size exceeds 1 MB before Amazon DynamoDB reaches this limit, it stops the operation and returns the matching values up to the limit, and a LastEvaluatedKey to apply in a subsequent operation to continue the operation. For more information see Query and Scan in the Amazon DynamoDB Developer Guide.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
                'Select' => array(
                    'description' => 'The attributes to be returned in the result. You can retrieve all item attributes, specific item attributes, the count of matching items, or in the case of an index, some or all of the attributes projected into the index.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ALL_ATTRIBUTES',
                        'ALL_PROJECTED_ATTRIBUTES',
                        'SPECIFIC_ATTRIBUTES',
                        'COUNT',
                    ),
                ),
                'ScanFilter' => array(
                    'description' => 'Evaluates the scan results and returns only the desired values. Multiple conditions are treated as "AND" operations: all conditions must be met to be included in the results.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents a selection criteria for a Query or Scan operation.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'AttributeValueList' => array(
                                'description' => 'Represents one or more values to evaluate against the supplied attribute. This list contains exactly one value, except for a BETWEEN or IN comparison, in which case the list contains two values.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeValue',
                                    'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'S' => array(
                                            'description' => 'Represents a String data type',
                                            'type' => 'string',
                                        ),
                                        'N' => array(
                                            'description' => 'Represents a Number data type',
                                            'type' => 'string',
                                        ),
                                        'B' => array(
                                            'description' => 'Represents a Binary data type',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                        'SS' => array(
                                            'description' => 'Represents a String set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'StringAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'NS' => array(
                                            'description' => 'Represents a Number set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'NumberAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'BS' => array(
                                            'description' => 'Represents a Binary set data type',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'BinaryAttributeValue',
                                                'type' => 'string',
                                                'filters' => array(
                                                    'base64_encode',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'ComparisonOperator' => array(
                                'required' => true,
                                'description' => 'Represents a comparator for evaluating attributes. For example, equals, greater than, less than, etc.',
                                'type' => 'string',
                                'enum' => array(
                                    'EQ',
                                    'NE',
                                    'IN',
                                    'LE',
                                    'LT',
                                    'GE',
                                    'GT',
                                    'BETWEEN',
                                    'NOT_NULL',
                                    'NULL',
                                    'CONTAINS',
                                    'NOT_CONTAINS',
                                    'BEGINS_WITH',
                                ),
                            ),
                        ),
                    ),
                ),
                'ExclusiveStartKey' => array(
                    'description' => 'The primary key of the item from which to continue an earlier operation. An earlier operation might provide this value as the LastEvaluatedKey if that operation was interrupted before completion; either because of the result set size or because of the setting for Limit. The LastEvaluatedKey can be passed back in a new request to continue the operation from that point.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
                'TotalSegments' => array(
                    'description' => 'For parallel Scan requests, TotalSegmentsrepresents the total number of segments for a table that is being scanned. Segments are a way to logically divide a table into equally sized portions, for the duration of the Scan request. The value of TotalSegments corresponds to the number of application "workers" (such as threads or processes) that will perform the parallel Scan. For example, if you want to scan a table using four application threads, you would specify a TotalSegments value of 4.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                    'maximum' => 4096,
                ),
                'Segment' => array(
                    'description' => 'For parallel Scan requests, Segment identifies an individual segment to be scanned by an application "worker" (such as a thread or a process). Each worker issues a Scan request with a distinct value for the segment it will scan.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 4095,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateItem' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateItemOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Edits an existing item\'s attributes, or inserts a new item if it does not already exist. You can put, delete, or add attribute values. You can also perform a conditional update (insert a new attribute name-value pair if it doesn\'t exist, or replace an existing name-value pair if it has certain expected attribute values).',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.UpdateItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table containing the item to update.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'description' => 'The primary key that defines the item. Each element consists of an attribute name and a value for that attribute.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'AttributeUpdates' => array(
                    'description' => 'The names of attributes to be modified, the action to perform on each, and the new value for each. If you are updating an attribute that is an index key attribute for any indexes on that table, the attribute type must match the index key type defined in the AttributesDefinition of the table description. You can use UpdateItem to update any non-key attributes.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'For the UpdateItem operation, represents the attributes to be modified,the action to perform on each, and the new value for each.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Action' => array(
                                'description' => 'Specifies how to perform the update. Valid values are PUT, DELETE, and ADD. The behavior depends on whether the specified primary key already exists in the table.',
                                'type' => 'string',
                                'enum' => array(
                                    'ADD',
                                    'PUT',
                                    'DELETE',
                                ),
                            ),
                        ),
                    ),
                ),
                'Expected' => array(
                    'description' => 'A map of attribute/condition pairs. This is the conditional block for the UpdateItem operation. All the conditions must be met for the operation to succeed.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'An attribute value used with conditional DeleteItem, PutItem or UpdateItem operations. Amazon DynamoDB will check to see if the attribute value already exists; or if the attribute exists and has a particular value before updating it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Exists' => array(
                                'description' => 'Causes Amazon DynamoDB to evaluate the value before attempting a conditional operation:',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
                    'description' => 'Use ReturnValues if you want to get the item attributes as they appeared either before or after they were updated. For UpdateItem, the valid values are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'NONE',
                        'ALL_OLD',
                        'UPDATED_OLD',
                        'ALL_NEW',
                        'UPDATED_NEW',
                    ),
                ),
                'ReturnConsumedCapacity' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TOTAL',
                        'NONE',
                    ),
                ),
                'ReturnItemCollectionMetrics' => array(
                    'description' => 'If set to SIZE, statistics about item collections, if any, that were modified during the operation are returned in the response. If set to NONE (the default), no statistics are returned..',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'SIZE',
                        'NONE',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A condition specified in the operation could not be evaluated.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'The request rate is too high, or the request is too large, for the available throughput to accommodate. The AWS SDKs automatically retry requests that receive this exception; therefore, your request will eventually succeed, unless the request is too large or your retry queue is too large to finish. Reduce the frequency of requests by using the strategies listed in Error Retries and Exponential Backoff in the Amazon DynamoDB Developer Guide.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'An item collection is too large. This exception is only returned for tables that have one or more local secondary indexes.',
                    'class' => 'ItemCollectionSizeLimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateTableOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates the provisioned throughput for the given table. Setting the throughput for a table helps you manage performance and is part of the provisioned throughput feature of Amazon DynamoDB.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DynamoDB_20120810.UpdateTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table to be updated.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'ProvisionedThroughput' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ReadCapacityUnits' => array(
                            'required' => true,
                            'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. For more information, see Specifying Read and Write Requirements in the Amazon DynamoDB Developer Guide.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                        'WriteCapacityUnits' => array(
                            'required' => true,
                            'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException. For more information, see Specifying Read and Write Requirements in the Amazon DynamoDB Developer Guide.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation conflicts with the resource\'s availability. For example, you attempted to recreate an existing table, or tried to delete a table currently in the CREATING state.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'The operation tried to access a nonexistent table or index. The resource may not be specified correctly, or its status may not be ACTIVE.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The number of concurrent table requests (cumulative number of tables in the CREATING, DELETING or UPDATING state) exceeds the maximum allowed of 10.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'An error occurred on the server side.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
    ),
    'models' => array(
        'BatchGetItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Responses' => array(
                    'description' => 'A map of table name to a list of items. Each object in Responsesconsists of a table name, along with a map of attribute data consisting of the data type and attribute value.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'array',
                        'items' => array(
                            'name' => 'AttributeMap',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'UnprocessedKeys' => array(
                    'description' => 'A map of tables and their respective keys that were not processed with the current response. The UnprocessedKeys value is in the same form as RequestItems, so the value can be provided directly to a subsequent BatchGetItem operation. For more information, see RequestItems in the Request Parameters section.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents a set of primary keys and, for each key, the attributes to retrieve from the table.',
                        'type' => 'object',
                        'properties' => array(
                            'Keys' => array(
                                'description' => 'Represents the primary key attribute values that define the items and the attributes associated with the items.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Key',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'S' => array(
                                                'description' => 'Represents a String data type',
                                                'type' => 'string',
                                            ),
                                            'N' => array(
                                                'description' => 'Represents a Number data type',
                                                'type' => 'string',
                                            ),
                                            'B' => array(
                                                'description' => 'Represents a Binary data type',
                                                'type' => 'string',
                                            ),
                                            'SS' => array(
                                                'description' => 'Represents a String set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'StringAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'NS' => array(
                                                'description' => 'Represents a Number set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NumberAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'BS' => array(
                                                'description' => 'Represents a Binary set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'BinaryAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'AttributesToGet' => array(
                                'description' => 'Represents one or more attributes to retrieve from the table or index. If no attribute names are specified then all attributes will be returned. If any of the specified attributes are not found, they will not appear in the result.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeName',
                                    'type' => 'string',
                                ),
                            ),
                            'ConsistentRead' => array(
                                'description' => 'Represents the consistency of a read operation. If set to true, then a strongly consistent read is used; otherwise, an eventually consistent read is used.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'description' => 'The write capacity units consumed by the operation.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ConsumedCapacity',
                        'description' => 'The table name that consumed provisioned throughput, and the number of capacity units consumed by it. ConsumedCapacity is only returned if it was asked for in the request. For more information, see Provisioned Throughput in the Amazon DynamoDB Developer Guide.',
                        'type' => 'object',
                        'properties' => array(
                            'TableName' => array(
                                'description' => 'The table that consumed the provisioned throughput.',
                                'type' => 'string',
                            ),
                            'CapacityUnits' => array(
                                'description' => 'The total number of capacity units consumed.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'BatchWriteItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'UnprocessedItems' => array(
                    'description' => 'A map of tables and requests against those tables that were not processed. The UnprocessedKeys value is in the same form as RequestItems, so you can provide this value directly to a subsequent BatchGetItem operation. For more information, see RequestItems in the Request Parameters section.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'array',
                        'items' => array(
                            'name' => 'WriteRequest',
                            'description' => 'Represents an operation to perform - either DeleteItem or PutItem. You can only specify one of these operations, not both, in a single WriteRequest. If you do need to perform both of these operations, you will need to specify two separate WriteRequest objects.',
                            'type' => 'object',
                            'properties' => array(
                                'PutRequest' => array(
                                    'description' => 'Represents a request to perform a DeleteItem operation.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Item' => array(
                                            'description' => 'A map of attribute name to attribute values, representing the primary key of an item to be processed by PutItem. All of the table\'s primary key attributes must be specified, and their data types must match those of the table\'s key schema. If any attributes are present in the item which are part of an index key schema for the table, their types must match the index key schema.',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Represents a String data type',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Represents a Number data type',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Represents a Binary data type',
                                                        'type' => 'string',
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'Represents a String set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'Represents a Number set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'Represents a Binary set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'BinaryAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                                'DeleteRequest' => array(
                                    'description' => 'Represents a request to perform a PutItem operation.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'A map of attribute name to attribute values, representing the primary key of the item to delete. All of the table\'s primary key attributes must be specified, and their data types must match those of the table\'s key schema.',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Represents a String data type',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Represents a Number data type',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Represents a Binary data type',
                                                        'type' => 'string',
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'Represents a String set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'Represents a Number set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'Represents a Binary set data type',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'BinaryAttributeValue',
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
                    ),
                ),
                'ItemCollectionMetrics' => array(
                    'description' => 'A list of tables that were processed by BatchWriteItem and, for each table, information about any item collections that were affected by individual DeleteItem or PutItem operations.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'array',
                        'items' => array(
                            'name' => 'ItemCollectionMetrics',
                            'description' => 'Information about item collections, if any, that were affected by the operation. ItemCollectionMetrics is only returned if it was asked for in the request. If the table does not have any secondary indexes, this information is not returned in the response.',
                            'type' => 'object',
                            'properties' => array(
                                'ItemCollectionKey' => array(
                                    'description' => 'The hash key value of the item collection. This is the same as the hash key of the item.',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'S' => array(
                                                'description' => 'Represents a String data type',
                                                'type' => 'string',
                                            ),
                                            'N' => array(
                                                'description' => 'Represents a Number data type',
                                                'type' => 'string',
                                            ),
                                            'B' => array(
                                                'description' => 'Represents a Binary data type',
                                                'type' => 'string',
                                            ),
                                            'SS' => array(
                                                'description' => 'Represents a String set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'StringAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'NS' => array(
                                                'description' => 'Represents a Number set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NumberAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'BS' => array(
                                                'description' => 'Represents a Binary set data type',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'BinaryAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                                'SizeEstimateRangeGB' => array(
                                    'description' => 'An estimate of item collection size, measured in gigabytes. This is a two-element array containing a lower bound and an upper bound for the estimate. The estimate includes the size of all the items in the table, plus the size of all attributes projected into all of the secondary indexes on that table. Use this estimate to measure whether a secondary index is approaching its size limit.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'ItemCollectionSizeEstimateBound',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'description' => 'The capacity units consumed by the operation.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ConsumedCapacity',
                        'description' => 'The table name that consumed provisioned throughput, and the number of capacity units consumed by it. ConsumedCapacity is only returned if it was asked for in the request. For more information, see Provisioned Throughput in the Amazon DynamoDB Developer Guide.',
                        'type' => 'object',
                        'properties' => array(
                            'TableName' => array(
                                'description' => 'The table that consumed the provisioned throughput.',
                                'type' => 'string',
                            ),
                            'CapacityUnits' => array(
                                'description' => 'The total number of capacity units consumed.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateTableOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TableDescription' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'AttributeDefinitions' => array(
                            'description' => 'An array of AttributeDefinition objects. Each of these objects describes one attribute in the table and index key schema.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AttributeDefinition',
                                'description' => 'Specifies an attribute for describing the key schema for the table and indexes.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'A name for the attribute.',
                                        'type' => 'string',
                                    ),
                                    'AttributeType' => array(
                                        'description' => 'The data type for the attribute.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableName' => array(
                            'description' => 'The name of the table.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'description' => 'The primary key structure for the table. Each KeySchemaElement consists of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'KeySchemaElement',
                                'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'Represents the name of a key attribute.',
                                        'type' => 'string',
                                    ),
                                    'KeyType' => array(
                                        'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'description' => 'Represents the current state of the table:',
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'description' => 'Represents the date and time when the table was created, in UNIX epoch time format.',
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'description' => 'Represents the provisioned throughput settings for the table, consisting of read and write capacity units, along with data about increases and decreases.',
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput increase for this table.',
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput decrease for this table.',
                                    'type' => 'string',
                                ),
                                'NumberOfDecreasesToday' => array(
                                    'description' => 'The number of provisioned throughput decreases for this table during this UTC calendar day. For current maximums on provisioned throughput decreases, see Limits in the Amazon DynamoDB Developer Guide.',
                                    'type' => 'numeric',
                                ),
                                'ReadCapacityUnits' => array(
                                    'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. Eventually consistent reads require less effort than strongly consistent reads, so a setting of 50 ReadCapacityUnits per second provides 100 eventually consistent ReadCapacityUnits per second.',
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'description' => 'Represents the total size of the specified table, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'description' => 'Represents the number of items in the specified table. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'LocalSecondaryIndexes' => array(
                            'description' => 'Represents one or more secondary indexes on the table. Each index is scoped to a given hash key value. Tables with one or more local secondary indexes are subject to an item collection size limit, where the amount of data within a given item collection cannot exceed 10 GB. Each element is composed of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LocalSecondaryIndexDescription',
                                'description' => 'Represents the properties of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'IndexName' => array(
                                        'description' => 'Represents the name of the secondary index.',
                                        'type' => 'string',
                                    ),
                                    'KeySchema' => array(
                                        'description' => 'Represents the complete index key schema, which consists of one or more pairs of attribute names and key types (HASH or RANGE).',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'KeySchemaElement',
                                            'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'AttributeName' => array(
                                                    'description' => 'Represents the name of a key attribute.',
                                                    'type' => 'string',
                                                ),
                                                'KeyType' => array(
                                                    'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Projection' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'ProjectionType' => array(
                                                'description' => 'Represents the set of attributes that are projected into the index:',
                                                'type' => 'string',
                                            ),
                                            'NonKeyAttributes' => array(
                                                'description' => 'Represents the non-key attribute names which will be projected into the index.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NonKeyAttributeName',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IndexSizeBytes' => array(
                                        'description' => 'Represents the total size of the index, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                    'ItemCount' => array(
                                        'description' => 'Represents the number of items in the index. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of attribute names to AttributeValue objects, representing the item as it appeared before the DeleteItem operation. This map appears in the response only if ReturnValues was specified as ALL_OLD in the request.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'ItemCollectionMetrics' => array(
                    'description' => 'Information about item collections, if any, that were affected by the operation. ItemCollectionMetrics is only returned if it was asked for in the request. If the table does not have any secondary indexes, this information is not returned in the response.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ItemCollectionKey' => array(
                            'description' => 'The hash key value of the item collection. This is the same as the hash key of the item.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'SizeEstimateRangeGB' => array(
                            'description' => 'An estimate of item collection size, measured in gigabytes. This is a two-element array containing a lower bound and an upper bound for the estimate. The estimate includes the size of all the items in the table, plus the size of all attributes projected into all of the secondary indexes on that table. Use this estimate to measure whether a secondary index is approaching its size limit.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ItemCollectionSizeEstimateBound',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteTableOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TableDescription' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'AttributeDefinitions' => array(
                            'description' => 'An array of AttributeDefinition objects. Each of these objects describes one attribute in the table and index key schema.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AttributeDefinition',
                                'description' => 'Specifies an attribute for describing the key schema for the table and indexes.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'A name for the attribute.',
                                        'type' => 'string',
                                    ),
                                    'AttributeType' => array(
                                        'description' => 'The data type for the attribute.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableName' => array(
                            'description' => 'The name of the table.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'description' => 'The primary key structure for the table. Each KeySchemaElement consists of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'KeySchemaElement',
                                'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'Represents the name of a key attribute.',
                                        'type' => 'string',
                                    ),
                                    'KeyType' => array(
                                        'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'description' => 'Represents the current state of the table:',
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'description' => 'Represents the date and time when the table was created, in UNIX epoch time format.',
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'description' => 'Represents the provisioned throughput settings for the table, consisting of read and write capacity units, along with data about increases and decreases.',
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput increase for this table.',
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput decrease for this table.',
                                    'type' => 'string',
                                ),
                                'NumberOfDecreasesToday' => array(
                                    'description' => 'The number of provisioned throughput decreases for this table during this UTC calendar day. For current maximums on provisioned throughput decreases, see Limits in the Amazon DynamoDB Developer Guide.',
                                    'type' => 'numeric',
                                ),
                                'ReadCapacityUnits' => array(
                                    'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. Eventually consistent reads require less effort than strongly consistent reads, so a setting of 50 ReadCapacityUnits per second provides 100 eventually consistent ReadCapacityUnits per second.',
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'description' => 'Represents the total size of the specified table, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'description' => 'Represents the number of items in the specified table. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'LocalSecondaryIndexes' => array(
                            'description' => 'Represents one or more secondary indexes on the table. Each index is scoped to a given hash key value. Tables with one or more local secondary indexes are subject to an item collection size limit, where the amount of data within a given item collection cannot exceed 10 GB. Each element is composed of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LocalSecondaryIndexDescription',
                                'description' => 'Represents the properties of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'IndexName' => array(
                                        'description' => 'Represents the name of the secondary index.',
                                        'type' => 'string',
                                    ),
                                    'KeySchema' => array(
                                        'description' => 'Represents the complete index key schema, which consists of one or more pairs of attribute names and key types (HASH or RANGE).',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'KeySchemaElement',
                                            'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'AttributeName' => array(
                                                    'description' => 'Represents the name of a key attribute.',
                                                    'type' => 'string',
                                                ),
                                                'KeyType' => array(
                                                    'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Projection' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'ProjectionType' => array(
                                                'description' => 'Represents the set of attributes that are projected into the index:',
                                                'type' => 'string',
                                            ),
                                            'NonKeyAttributes' => array(
                                                'description' => 'Represents the non-key attribute names which will be projected into the index.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NonKeyAttributeName',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IndexSizeBytes' => array(
                                        'description' => 'Represents the total size of the index, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                    'ItemCount' => array(
                                        'description' => 'Represents the number of items in the index. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTableOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Table' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'AttributeDefinitions' => array(
                            'description' => 'An array of AttributeDefinition objects. Each of these objects describes one attribute in the table and index key schema.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AttributeDefinition',
                                'description' => 'Specifies an attribute for describing the key schema for the table and indexes.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'A name for the attribute.',
                                        'type' => 'string',
                                    ),
                                    'AttributeType' => array(
                                        'description' => 'The data type for the attribute.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableName' => array(
                            'description' => 'The name of the table.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'description' => 'The primary key structure for the table. Each KeySchemaElement consists of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'KeySchemaElement',
                                'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'Represents the name of a key attribute.',
                                        'type' => 'string',
                                    ),
                                    'KeyType' => array(
                                        'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'description' => 'Represents the current state of the table:',
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'description' => 'Represents the date and time when the table was created, in UNIX epoch time format.',
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'description' => 'Represents the provisioned throughput settings for the table, consisting of read and write capacity units, along with data about increases and decreases.',
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput increase for this table.',
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput decrease for this table.',
                                    'type' => 'string',
                                ),
                                'NumberOfDecreasesToday' => array(
                                    'description' => 'The number of provisioned throughput decreases for this table during this UTC calendar day. For current maximums on provisioned throughput decreases, see Limits in the Amazon DynamoDB Developer Guide.',
                                    'type' => 'numeric',
                                ),
                                'ReadCapacityUnits' => array(
                                    'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. Eventually consistent reads require less effort than strongly consistent reads, so a setting of 50 ReadCapacityUnits per second provides 100 eventually consistent ReadCapacityUnits per second.',
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'description' => 'Represents the total size of the specified table, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'description' => 'Represents the number of items in the specified table. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'LocalSecondaryIndexes' => array(
                            'description' => 'Represents one or more secondary indexes on the table. Each index is scoped to a given hash key value. Tables with one or more local secondary indexes are subject to an item collection size limit, where the amount of data within a given item collection cannot exceed 10 GB. Each element is composed of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LocalSecondaryIndexDescription',
                                'description' => 'Represents the properties of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'IndexName' => array(
                                        'description' => 'Represents the name of the secondary index.',
                                        'type' => 'string',
                                    ),
                                    'KeySchema' => array(
                                        'description' => 'Represents the complete index key schema, which consists of one or more pairs of attribute names and key types (HASH or RANGE).',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'KeySchemaElement',
                                            'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'AttributeName' => array(
                                                    'description' => 'Represents the name of a key attribute.',
                                                    'type' => 'string',
                                                ),
                                                'KeyType' => array(
                                                    'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Projection' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'ProjectionType' => array(
                                                'description' => 'Represents the set of attributes that are projected into the index:',
                                                'type' => 'string',
                                            ),
                                            'NonKeyAttributes' => array(
                                                'description' => 'Represents the non-key attribute names which will be projected into the index.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NonKeyAttributeName',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IndexSizeBytes' => array(
                                        'description' => 'Represents the total size of the index, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                    'ItemCount' => array(
                                        'description' => 'Represents the number of items in the index. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Item' => array(
                    'description' => 'A map of attribute names to AttributeValue objects, as specified by AttributesToGet.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'ListTablesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TableNames' => array(
                    'description' => 'The names of the tables associated with the current account at the current endpoint.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TableName',
                        'type' => 'string',
                    ),
                ),
                'LastEvaluatedTableName' => array(
                    'description' => 'The name of the last table in the current list, only if some tables for the account and endpoint have not been returned. This value does not exist in a response if all table names are already returned. Use this value as the ExclusiveStartTableName in a new request to continue the list until all the table names are returned.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'PutItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'The attribute values as they appeared before the PutItem operation, but only if ReturnValues is specified as ALL_OLD in the request. Each element consists of an attribute name and an attribute value.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'ItemCollectionMetrics' => array(
                    'description' => 'Information about item collections, if any, that were affected by the operation. ItemCollectionMetrics is only returned if it was asked for in the request. If the table does not have any secondary indexes, this information is not returned in the response.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ItemCollectionKey' => array(
                            'description' => 'The hash key value of the item collection. This is the same as the hash key of the item.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'SizeEstimateRangeGB' => array(
                            'description' => 'An estimate of item collection size, measured in gigabytes. This is a two-element array containing a lower bound and an upper bound for the estimate. The estimate includes the size of all the items in the table, plus the size of all attributes projected into all of the secondary indexes on that table. Use this estimate to measure whether a secondary index is approaching its size limit.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ItemCollectionSizeEstimateBound',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'QueryOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Items' => array(
                    'description' => 'An array of item attributes that match the query criteria. Each element in this array consists of an attribute name and the value for that attribute.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'AttributeMap',
                        'type' => 'object',
                        'additionalProperties' => array(
                            'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Represents a String data type',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Represents a Number data type',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Represents a Binary data type',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'Represents a String set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'Represents a Number set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'Represents a Binary set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'BinaryAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Count' => array(
                    'description' => 'The number of items in the response.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'LastEvaluatedKey' => array(
                    'description' => 'The primary key of the item where the operation stopped, inclusive of the previous result set. Use this value to start a new operation, excluding this value in the new request.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'ScanOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Items' => array(
                    'description' => 'An array of item attributes that match the scan criteria. Each element in this array consists of an attribute name and the value for that attribute.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'AttributeMap',
                        'type' => 'object',
                        'additionalProperties' => array(
                            'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Represents a String data type',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Represents a Number data type',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Represents a Binary data type',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'Represents a String set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'Represents a Number set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'Represents a Binary set data type',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'BinaryAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Count' => array(
                    'description' => 'The number of items in the response.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'ScannedCount' => array(
                    'description' => 'The number of items in the complete scan, before any filters are applied. A high ScannedCount value with few, or no, Count results indicates an inefficient Scan operation. For more information, see Count and ScannedCount in the Amazon DynamoDB Developer Guide.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'LastEvaluatedKey' => array(
                    'description' => 'The primary key of the item where the operation stopped, inclusive of the previous result set. Use this value to start a new operation, excluding this value in the new request.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'UpdateItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of attribute values as they appeard before the UpdateItem operation, but only if ReturnValues was specified as something other than NONE in the request. Each element represents one attribute.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Represents a String data type',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Represents a Number data type',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Represents a Binary data type',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'Represents a String set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'Represents a Number set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'Represents a Binary set data type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacity' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'TableName' => array(
                            'description' => 'The table that consumed the provisioned throughput.',
                            'type' => 'string',
                        ),
                        'CapacityUnits' => array(
                            'description' => 'The total number of capacity units consumed.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'ItemCollectionMetrics' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ItemCollectionKey' => array(
                            'description' => 'The hash key value of the item collection. This is the same as the hash key of the item.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'description' => 'Represents the data for an attribute. You can set one, and only one, of the elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Represents a String data type',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Represents a Number data type',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Represents a Binary data type',
                                        'type' => 'string',
                                    ),
                                    'SS' => array(
                                        'description' => 'Represents a String set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'Represents a Number set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'Represents a Binary set data type',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'BinaryAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'SizeEstimateRangeGB' => array(
                            'description' => 'An estimate of item collection size, measured in gigabytes. This is a two-element array containing a lower bound and an upper bound for the estimate. The estimate includes the size of all the items in the table, plus the size of all attributes projected into all of the secondary indexes on that table. Use this estimate to measure whether a secondary index is approaching its size limit.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ItemCollectionSizeEstimateBound',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UpdateTableOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TableDescription' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'AttributeDefinitions' => array(
                            'description' => 'An array of AttributeDefinition objects. Each of these objects describes one attribute in the table and index key schema.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AttributeDefinition',
                                'description' => 'Specifies an attribute for describing the key schema for the table and indexes.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'A name for the attribute.',
                                        'type' => 'string',
                                    ),
                                    'AttributeType' => array(
                                        'description' => 'The data type for the attribute.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableName' => array(
                            'description' => 'The name of the table.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'description' => 'The primary key structure for the table. Each KeySchemaElement consists of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'KeySchemaElement',
                                'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'AttributeName' => array(
                                        'description' => 'Represents the name of a key attribute.',
                                        'type' => 'string',
                                    ),
                                    'KeyType' => array(
                                        'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'description' => 'Represents the current state of the table:',
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'description' => 'Represents the date and time when the table was created, in UNIX epoch time format.',
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'description' => 'Represents the provisioned throughput settings for the table, consisting of read and write capacity units, along with data about increases and decreases.',
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput increase for this table.',
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'description' => 'The date and time of the last provisioned throughput decrease for this table.',
                                    'type' => 'string',
                                ),
                                'NumberOfDecreasesToday' => array(
                                    'description' => 'The number of provisioned throughput decreases for this table during this UTC calendar day. For current maximums on provisioned throughput decreases, see Limits in the Amazon DynamoDB Developer Guide.',
                                    'type' => 'numeric',
                                ),
                                'ReadCapacityUnits' => array(
                                    'description' => 'The maximum number of strongly consistent reads consumed per second before Amazon DynamoDB returns a ThrottlingException. Eventually consistent reads require less effort than strongly consistent reads, so a setting of 50 ReadCapacityUnits per second provides 100 eventually consistent ReadCapacityUnits per second.',
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'description' => 'The maximum number of writes consumed per second before Amazon DynamoDB returns a ThrottlingException.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'description' => 'Represents the total size of the specified table, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'description' => 'Represents the number of items in the specified table. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                            'type' => 'numeric',
                        ),
                        'LocalSecondaryIndexes' => array(
                            'description' => 'Represents one or more secondary indexes on the table. Each index is scoped to a given hash key value. Tables with one or more local secondary indexes are subject to an item collection size limit, where the amount of data within a given item collection cannot exceed 10 GB. Each element is composed of:',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LocalSecondaryIndexDescription',
                                'description' => 'Represents the properties of a secondary index.',
                                'type' => 'object',
                                'properties' => array(
                                    'IndexName' => array(
                                        'description' => 'Represents the name of the secondary index.',
                                        'type' => 'string',
                                    ),
                                    'KeySchema' => array(
                                        'description' => 'Represents the complete index key schema, which consists of one or more pairs of attribute names and key types (HASH or RANGE).',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'KeySchemaElement',
                                            'description' => 'Represents a key schema. Specifies the attributes that make up the primary key of a table, or the key attributes of a secondary index.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'AttributeName' => array(
                                                    'description' => 'Represents the name of a key attribute.',
                                                    'type' => 'string',
                                                ),
                                                'KeyType' => array(
                                                    'description' => 'Represents the attribute data, consisting of the data type and the attribute value itself.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Projection' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'ProjectionType' => array(
                                                'description' => 'Represents the set of attributes that are projected into the index:',
                                                'type' => 'string',
                                            ),
                                            'NonKeyAttributes' => array(
                                                'description' => 'Represents the non-key attribute names which will be projected into the index.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NonKeyAttributeName',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IndexSizeBytes' => array(
                                        'description' => 'Represents the total size of the index, in bytes. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                    'ItemCount' => array(
                                        'description' => 'Represents the number of items in the index. Amazon DynamoDB updates this value approximately every six hours. Recent changes might not be reflected in this value.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 20,
            'max_attempts' => 25,
        ),
        '__TableState' => array(
            'operation' => 'DescribeTable',
        ),
        'TableExists' => array(
            'extends' => '__TableState',
            'description' => 'Wait until a table exists and can be accessed',
            'success.type' => 'output',
            'success.path' => 'Table/TableStatus',
            'success.value' => 'ACTIVE',
            'ignore_errors' => array(
                'ResourceNotFoundException',
            ),
        ),
        'TableNotExists' => array(
            'extends' => '__TableState',
            'description' => 'Wait until a table is deleted',
            'success.type' => 'error',
            'success.value' => 'ResourceNotFoundException',
        ),
    ),
);
