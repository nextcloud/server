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
    'apiVersion' => '2011-12-05',
    'endpointPrefix' => 'dynamodb',
    'serviceFullName' => 'Amazon DynamoDB',
    'serviceAbbreviation' => 'DynamoDB',
    'serviceType' => 'json',
    'jsonVersion' => '1.0',
    'targetPrefix' => 'DynamoDB_20111205.',
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
            'summary' => 'Retrieves the attributes for multiple items from multiple tables using their primary keys.',
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
                    'default' => 'DynamoDB_20111205.BatchGetItem',
                ),
                'RequestItems' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'TableName',
                            'key_pattern' => '/[a-zA-Z0-9_.-]+/',
                        ),
                        'properties' => array(
                            'Keys' => array(
                                'required' => true,
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 100,
                                'items' => array(
                                    'name' => 'Key',
                                    'description' => 'The primary key that uniquely identifies each item in a table. A primary key can be a one attribute (hash) primary key or a two attribute (hash-and-range) primary key.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'HashKeyElement' => array(
                                            'required' => true,
                                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'S' => array(
                                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                    'type' => 'string',
                                                ),
                                                'N' => array(
                                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                    'type' => 'string',
                                                ),
                                                'B' => array(
                                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                    'type' => 'string',
                                                    'filters' => array(
                                                        'base64_encode',
                                                    ),
                                                ),
                                                'SS' => array(
                                                    'description' => 'A set of strings.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'StringAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'NS' => array(
                                                    'description' => 'A set of numbers.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'NumberAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'BS' => array(
                                                    'description' => 'A set of binary attributes.',
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
                                        'RangeKeyElement' => array(
                                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'S' => array(
                                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                    'type' => 'string',
                                                ),
                                                'N' => array(
                                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                    'type' => 'string',
                                                ),
                                                'B' => array(
                                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                    'type' => 'string',
                                                    'filters' => array(
                                                        'base64_encode',
                                                    ),
                                                ),
                                                'SS' => array(
                                                    'description' => 'A set of strings.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'StringAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'NS' => array(
                                                    'description' => 'A set of numbers.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'NumberAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'BS' => array(
                                                    'description' => 'A set of binary attributes.',
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
                            'AttributesToGet' => array(
                                'type' => 'array',
                                'minItems' => 1,
                                'items' => array(
                                    'name' => 'AttributeName',
                                    'type' => 'string',
                                ),
                            ),
                            'ConsistentRead' => array(
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Allows to execute a batch of Put and/or Delete Requests for many tables in a single call. A total of 25 requests are allowed.',
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
                    'default' => 'DynamoDB_20111205.BatchWriteItem',
                ),
                'RequestItems' => array(
                    'required' => true,
                    'description' => 'A map of table name to list-of-write-requests. Used as input to the BatchWriteItem API call',
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
                            'description' => 'This structure is a Union of PutRequest and DeleteRequest. It can contain exactly one of PutRequest or DeleteRequest. Never Both. This is enforced in the code.',
                            'type' => 'object',
                            'properties' => array(
                                'PutRequest' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'Item' => array(
                                            'required' => true,
                                            'description' => 'The item to put',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                                                'type' => 'object',
                                                'data' => array(
                                                    'shape_name' => 'AttributeName',
                                                ),
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                        'type' => 'string',
                                                        'filters' => array(
                                                            'base64_encode',
                                                        ),
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'A set of strings.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'A set of numbers.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'A set of binary attributes.',
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
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'required' => true,
                                            'description' => 'The item\'s key to be delete',
                                            'type' => 'object',
                                            'properties' => array(
                                                'HashKeyElement' => array(
                                                    'required' => true,
                                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'S' => array(
                                                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                            'type' => 'string',
                                                        ),
                                                        'N' => array(
                                                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                            'type' => 'string',
                                                        ),
                                                        'B' => array(
                                                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                            'type' => 'string',
                                                            'filters' => array(
                                                                'base64_encode',
                                                            ),
                                                        ),
                                                        'SS' => array(
                                                            'description' => 'A set of strings.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'StringAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'NS' => array(
                                                            'description' => 'A set of numbers.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'NumberAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'BS' => array(
                                                            'description' => 'A set of binary attributes.',
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
                                                'RangeKeyElement' => array(
                                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'S' => array(
                                                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                            'type' => 'string',
                                                        ),
                                                        'N' => array(
                                                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                            'type' => 'string',
                                                        ),
                                                        'B' => array(
                                                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                            'type' => 'string',
                                                            'filters' => array(
                                                                'base64_encode',
                                                            ),
                                                        ),
                                                        'SS' => array(
                                                            'description' => 'A set of strings.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'StringAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'NS' => array(
                                                            'description' => 'A set of numbers.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'NumberAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'BS' => array(
                                                            'description' => 'A set of binary attributes.',
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
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Adds a new table to your account.',
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
                    'default' => 'DynamoDB_20111205.CreateTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table you want to create. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'KeySchema' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'AttributeName' => array(
                                    'required' => true,
                                    'description' => 'The AttributeName of the KeySchemaElement.',
                                    'type' => 'string',
                                    'minLength' => 1,
                                    'maxLength' => 255,
                                ),
                                'AttributeType' => array(
                                    'required' => true,
                                    'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                    'type' => 'string',
                                    'enum' => array(
                                        'S',
                                        'N',
                                        'B',
                                    ),
                                ),
                            ),
                        ),
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'AttributeName' => array(
                                    'required' => true,
                                    'description' => 'The AttributeName of the KeySchemaElement.',
                                    'type' => 'string',
                                    'minLength' => 1,
                                    'maxLength' => 255,
                                ),
                                'AttributeType' => array(
                                    'required' => true,
                                    'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
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
                ),
                'ProvisionedThroughput' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'ReadCapacityUnits' => array(
                            'required' => true,
                            'description' => 'ReadCapacityUnits are in terms of strictly consistent reads, assuming items of 1k. 2k items require twice the ReadCapacityUnits. Eventually-consistent reads only require half the ReadCapacityUnits of stirctly consistent reads.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                        'WriteCapacityUnits' => array(
                            'required' => true,
                            'description' => 'WriteCapacityUnits are in terms of strictly consistent reads, assuming items of 1k. 2k items require twice the WriteCapacityUnits.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'This exception is thrown when the subscriber exceeded the limits on the number of objects or operations.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Deletes a single item in a table by primary key.',
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
                    'default' => 'DynamoDB_20111205.DeleteItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to delete an item. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                'Expected' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Allows you to provide an attribute name, and whether or not Amazon DynamoDB should check to see if the attribute value already exists; or if the attribute value exists and has a particular value before changing it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'description' => 'Specify whether or not a value already exists and has a specific content for the attribute name-value pair.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'A set of strings.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'A set of numbers.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'A set of binary attributes.',
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
                                'description' => 'Specify whether or not a value already exists for the attribute name-value pair.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
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
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when an expected value does not match what was found in the system.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Deletes a table and all of its items.',
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
                    'default' => 'DynamoDB_20111205.DeleteTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table you want to delete. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the subscriber exceeded the limits on the number of objects or operations.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Retrieves information about the table, including the current status of the table, the primary key schema and when the table was created.',
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
                    'default' => 'DynamoDB_20111205.DescribeTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table you want to describe. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Retrieves a set of Attributes for an item that matches the primary key.',
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
                    'default' => 'DynamoDB_20111205.GetItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to get an item. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'ConsistentRead' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Retrieves a paginated list of table names created by the AWS Account of the caller in the AWS Region (e.g. us-east-1).',
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
                    'default' => 'DynamoDB_20111205.ListTables',
                ),
                'ExclusiveStartTableName' => array(
                    'description' => 'The name of the table that starts the list. If you already ran a ListTables operation and received a LastEvaluatedTableName value in the response, use that value here to continue the list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Limit' => array(
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Creates a new item, or replaces an old item with a new item (including all the attributes).',
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
                    'default' => 'DynamoDB_20111205.PutItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to put an item. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Item' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'S' => array(
                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                'type' => 'string',
                                'filters' => array(
                                    'base64_encode',
                                ),
                            ),
                            'SS' => array(
                                'description' => 'A set of strings.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'A set of numbers.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'A set of binary attributes.',
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
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Allows you to provide an attribute name, and whether or not Amazon DynamoDB should check to see if the attribute value already exists; or if the attribute value exists and has a particular value before changing it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'description' => 'Specify whether or not a value already exists and has a specific content for the attribute name-value pair.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'A set of strings.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'A set of numbers.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'A set of binary attributes.',
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
                                'description' => 'Specify whether or not a value already exists for the attribute name-value pair.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
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
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when an expected value does not match what was found in the system.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Gets the values of one or more items and its attributes by primary key (composite primary key, only).',
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
                    'default' => 'DynamoDB_20111205.Query',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to query. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'AttributesToGet' => array(
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'Limit' => array(
                    'description' => 'The maximum number of items to return. If Amazon DynamoDB hits this limit while querying the table, it stops the query and returns the matching values up to the limit, and a LastEvaluatedKey to apply in a subsequent operation to continue the query. Also, if the result set size exceeds 1MB before Amazon DynamoDB hits this limit, it stops the query and returns the matching values, and a LastEvaluatedKey to apply in a subsequent operation to continue the query.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
                'ConsistentRead' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'Count' => array(
                    'description' => 'If set to true, Amazon DynamoDB returns a total number of items that match the query parameters, instead of a list of the matching items and their attributes. Do not set Count to true while providing a list of AttributesToGet, otherwise Amazon DynamoDB returns a validation error.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'HashKeyValue' => array(
                    'required' => true,
                    'description' => 'Attribute value of the hash component of the composite primary key.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'S' => array(
                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                            'type' => 'string',
                        ),
                        'N' => array(
                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                            'type' => 'string',
                        ),
                        'B' => array(
                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                            'type' => 'string',
                            'filters' => array(
                                'base64_encode',
                            ),
                        ),
                        'SS' => array(
                            'description' => 'A set of strings.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'StringAttributeValue',
                                'type' => 'string',
                            ),
                        ),
                        'NS' => array(
                            'description' => 'A set of numbers.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'NumberAttributeValue',
                                'type' => 'string',
                            ),
                        ),
                        'BS' => array(
                            'description' => 'A set of binary attributes.',
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
                'RangeKeyCondition' => array(
                    'description' => 'A container for the attribute values and comparison operators to use for the query.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'AttributeValueList' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AttributeValue',
                                'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'A set of strings.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'A set of numbers.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'A set of binary attributes.',
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
                'ScanIndexForward' => array(
                    'description' => 'Specifies forward or backward traversal of the index. Amazon DynamoDB returns results reflecting the requested order, determined by the range key. The default value is true (forward).',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'ExclusiveStartKey' => array(
                    'description' => 'Primary key of the item from which to continue an earlier query. An earlier query might provide this value as the LastEvaluatedKey if that query operation was interrupted before completing the query; either because of the result set size or the Limit parameter. The LastEvaluatedKey can be passed back in a new query request to continue the operation from that point.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Retrieves one or more items and its attributes by performing a full scan of a table.',
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
                    'default' => 'DynamoDB_20111205.Scan',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to scan. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'AttributesToGet' => array(
                    'type' => 'array',
                    'location' => 'json',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'Limit' => array(
                    'description' => 'The maximum number of items to return. If Amazon DynamoDB hits this limit while scanning the table, it stops the scan and returns the matching values up to the limit, and a LastEvaluatedKey to apply in a subsequent operation to continue the scan. Also, if the scanned data set size exceeds 1 MB before Amazon DynamoDB hits this limit, it stops the scan and returns the matching values up to the limit, and a LastEvaluatedKey to apply in a subsequent operation to continue the scan.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
                'Count' => array(
                    'description' => 'If set to true, Amazon DynamoDB returns a total number of items for the Scan operation, even if the operation has no matching items for the assigned filter. Do not set Count to true while providing a list of AttributesToGet, otherwise Amazon DynamoDB returns a validation error.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'ScanFilter' => array(
                    'description' => 'Evaluates the scan results and returns only the desired values.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'String',
                        ),
                        'properties' => array(
                            'AttributeValueList' => array(
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeValue',
                                    'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'S' => array(
                                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                            'type' => 'string',
                                        ),
                                        'N' => array(
                                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                            'type' => 'string',
                                        ),
                                        'B' => array(
                                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                                            'type' => 'string',
                                            'filters' => array(
                                                'base64_encode',
                                            ),
                                        ),
                                        'SS' => array(
                                            'description' => 'A set of strings.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'StringAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'NS' => array(
                                            'description' => 'A set of numbers.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'NumberAttributeValue',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'BS' => array(
                                            'description' => 'A set of binary attributes.',
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
                    'description' => 'Primary key of the item from which to continue an earlier scan. An earlier scan might provide this value if that scan operation was interrupted before scanning the entire table; either because of the result set size or the Limit parameter. The LastEvaluatedKey can be passed back in a new scan request to continue the operation from that point.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Edits an existing item\'s attributes.',
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
                    'default' => 'DynamoDB_20111205.UpdateItem',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table in which you want to update an item. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 3,
                    'maxLength' => 255,
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'required' => true,
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                    'filters' => array(
                                        'base64_encode',
                                    ),
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                'AttributeUpdates' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Specifies the attribute to update and how to perform the update. Possible values: PUT (default), ADD or DELETE.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'A set of strings.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'A set of numbers.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'A set of binary attributes.',
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
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'Allows you to provide an attribute name, and whether or not Amazon DynamoDB should check to see if the attribute value already exists; or if the attribute value exists and has a particular value before changing it.',
                        'type' => 'object',
                        'data' => array(
                            'shape_name' => 'AttributeName',
                        ),
                        'properties' => array(
                            'Value' => array(
                                'description' => 'Specify whether or not a value already exists and has a specific content for the attribute name-value pair.',
                                'type' => 'object',
                                'properties' => array(
                                    'S' => array(
                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                        'type' => 'string',
                                    ),
                                    'N' => array(
                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                        'type' => 'string',
                                    ),
                                    'B' => array(
                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                        'type' => 'string',
                                        'filters' => array(
                                            'base64_encode',
                                        ),
                                    ),
                                    'SS' => array(
                                        'description' => 'A set of strings.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'StringAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'NS' => array(
                                        'description' => 'A set of numbers.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'NumberAttributeValue',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'BS' => array(
                                        'description' => 'A set of binary attributes.',
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
                                'description' => 'Specify whether or not a value already exists for the attribute name-value pair.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'ReturnValues' => array(
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
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when an expected value does not match what was found in the system.',
                    'class' => 'ConditionalCheckFailedException',
                ),
                array(
                    'reason' => 'This exception is thrown when the level of provisioned throughput defined for the table is exceeded.',
                    'class' => 'ProvisionedThroughputExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
            'summary' => 'Updates the provisioned throughput for the given table.',
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
                    'default' => 'DynamoDB_20111205.UpdateTable',
                ),
                'TableName' => array(
                    'required' => true,
                    'description' => 'The name of the table you want to update. Allowed characters are a-z, A-Z, 0-9, _ (underscore), - (hyphen) and . (period).',
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
                            'description' => 'ReadCapacityUnits are in terms of strictly consistent reads, assuming items of 1k. 2k items require twice the ReadCapacityUnits. Eventually-consistent reads only require half the ReadCapacityUnits of stirctly consistent reads.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                        'WriteCapacityUnits' => array(
                            'required' => true,
                            'description' => 'WriteCapacityUnits are in terms of strictly consistent reads, assuming items of 1k. 2k items require twice the WriteCapacityUnits.',
                            'type' => 'numeric',
                            'minimum' => 1,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'This exception is thrown when the resource which is being attempted to be changed is in use.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'This exception is thrown when the subscriber exceeded the limits on the number of objects or operations.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'This exception is thrown when the service has a problem when trying to process the request.',
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
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'The item attributes from a response in a specific table, along with the read resources consumed on the table during the request.',
                        'type' => 'object',
                        'properties' => array(
                            'Items' => array(
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeMap',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'S' => array(
                                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                'type' => 'string',
                                            ),
                                            'N' => array(
                                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                'type' => 'string',
                                            ),
                                            'B' => array(
                                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                'type' => 'string',
                                            ),
                                            'SS' => array(
                                                'description' => 'A set of strings.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'StringAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'NS' => array(
                                                'description' => 'A set of numbers.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'NumberAttributeValue',
                                                    'type' => 'string',
                                                ),
                                            ),
                                            'BS' => array(
                                                'description' => 'A set of binary attributes.',
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
                            'ConsumedCapacityUnits' => array(
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'UnprocessedKeys' => array(
                    'description' => 'Contains a map of tables and their respective keys that were not processed with the current response, possibly due to reaching a limit on the response size. The UnprocessedKeys value is in the same form as a RequestItems parameter (so the value can be provided directly to a subsequent BatchGetItem operation). For more information, see the above RequestItems parameter.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'object',
                        'properties' => array(
                            'Keys' => array(
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Key',
                                    'description' => 'The primary key that uniquely identifies each item in a table. A primary key can be a one attribute (hash) primary key or a two attribute (hash-and-range) primary key.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'HashKeyElement' => array(
                                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'S' => array(
                                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                    'type' => 'string',
                                                ),
                                                'N' => array(
                                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                    'type' => 'string',
                                                ),
                                                'B' => array(
                                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                    'type' => 'string',
                                                ),
                                                'SS' => array(
                                                    'description' => 'A set of strings.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'StringAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'NS' => array(
                                                    'description' => 'A set of numbers.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'NumberAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'BS' => array(
                                                    'description' => 'A set of binary attributes.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'BinaryAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'RangeKeyElement' => array(
                                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'S' => array(
                                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                    'type' => 'string',
                                                ),
                                                'N' => array(
                                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                    'type' => 'string',
                                                ),
                                                'B' => array(
                                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                    'type' => 'string',
                                                ),
                                                'SS' => array(
                                                    'description' => 'A set of strings.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'StringAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'NS' => array(
                                                    'description' => 'A set of numbers.',
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'NumberAttributeValue',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                                'BS' => array(
                                                    'description' => 'A set of binary attributes.',
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
                            'AttributesToGet' => array(
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AttributeName',
                                    'type' => 'string',
                                ),
                            ),
                            'ConsistentRead' => array(
                                'type' => 'boolean',
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
                'Responses' => array(
                    'description' => 'The response object as a result of BatchWriteItem call. This is essentially a map of table name to ConsumedCapacityUnits.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'object',
                        'properties' => array(
                            'ConsumedCapacityUnits' => array(
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'UnprocessedItems' => array(
                    'description' => 'The Items which we could not successfully process in a BatchWriteItem call is returned as UnprocessedItems',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'array',
                        'items' => array(
                            'name' => 'WriteRequest',
                            'description' => 'This structure is a Union of PutRequest and DeleteRequest. It can contain exactly one of PutRequest or DeleteRequest. Never Both. This is enforced in the code.',
                            'type' => 'object',
                            'properties' => array(
                                'PutRequest' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'Item' => array(
                                            'description' => 'The item to put',
                                            'type' => 'object',
                                            'additionalProperties' => array(
                                                'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'S' => array(
                                                        'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                        'type' => 'string',
                                                    ),
                                                    'N' => array(
                                                        'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                        'type' => 'string',
                                                    ),
                                                    'B' => array(
                                                        'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                        'type' => 'string',
                                                    ),
                                                    'SS' => array(
                                                        'description' => 'A set of strings.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'StringAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'NS' => array(
                                                        'description' => 'A set of numbers.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'NumberAttributeValue',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                    'BS' => array(
                                                        'description' => 'A set of binary attributes.',
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
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The item\'s key to be delete',
                                            'type' => 'object',
                                            'properties' => array(
                                                'HashKeyElement' => array(
                                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'S' => array(
                                                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                            'type' => 'string',
                                                        ),
                                                        'N' => array(
                                                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                            'type' => 'string',
                                                        ),
                                                        'B' => array(
                                                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                            'type' => 'string',
                                                        ),
                                                        'SS' => array(
                                                            'description' => 'A set of strings.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'StringAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'NS' => array(
                                                            'description' => 'A set of numbers.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'NumberAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'BS' => array(
                                                            'description' => 'A set of binary attributes.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'BinaryAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                'RangeKeyElement' => array(
                                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'S' => array(
                                                            'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                                            'type' => 'string',
                                                        ),
                                                        'N' => array(
                                                            'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                                            'type' => 'string',
                                                        ),
                                                        'B' => array(
                                                            'description' => 'Binary attributes are sequences of unsigned bytes.',
                                                            'type' => 'string',
                                                        ),
                                                        'SS' => array(
                                                            'description' => 'A set of strings.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'StringAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'NS' => array(
                                                            'description' => 'A set of numbers.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'NumberAttributeValue',
                                                                'type' => 'string',
                                                            ),
                                                        ),
                                                        'BS' => array(
                                                            'description' => 'A set of binary attributes.',
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
                        'TableName' => array(
                            'description' => 'The name of the table being described.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'type' => 'object',
                            'properties' => array(
                                'HashKeyElement' => array(
                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'RangeKeyElement' => array(
                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'ReadCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'type' => 'numeric',
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
                    'description' => 'If the ReturnValues parameter is provided as ALL_OLD in the request, Amazon DynamoDB returns an array of attribute name-value pairs (essentially, the deleted item). Otherwise, the response contains an empty set.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'A set of strings.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'A set of numbers.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'A set of binary attributes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
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
                        'TableName' => array(
                            'description' => 'The name of the table being described.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'type' => 'object',
                            'properties' => array(
                                'HashKeyElement' => array(
                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'RangeKeyElement' => array(
                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'ReadCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'type' => 'numeric',
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
                        'TableName' => array(
                            'description' => 'The name of the table being described.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'type' => 'object',
                            'properties' => array(
                                'HashKeyElement' => array(
                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'RangeKeyElement' => array(
                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'ReadCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'type' => 'numeric',
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
                    'description' => 'Contains the requested attributes.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'A set of strings.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'A set of numbers.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'A set of binary attributes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'ListTablesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TableNames' => array(
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TableName',
                        'type' => 'string',
                    ),
                ),
                'LastEvaluatedTableName' => array(
                    'description' => 'The name of the last table in the current list. Use this value as the ExclusiveStartTableName in a new request to continue the list until all the table names are returned. If this value is null, all table names have been returned.',
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
                    'description' => 'Attribute values before the put operation, but only if the ReturnValues parameter is specified as ALL_OLD in the request.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'A set of strings.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'A set of numbers.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'A set of binary attributes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'QueryOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Items' => array(
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'AttributeMap',
                        'type' => 'object',
                        'additionalProperties' => array(
                            'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                    'description' => 'Number of items in the response.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'LastEvaluatedKey' => array(
                    'description' => 'Primary key of the item where the query operation stopped, inclusive of the previous result set. Use this value to start a new operation excluding this value in the new request. The LastEvaluatedKey is null when the entire query result set is complete (i.e. the operation processed the "last page").',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'BinaryAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'ScanOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Items' => array(
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'AttributeMap',
                        'type' => 'object',
                        'additionalProperties' => array(
                            'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                    'description' => 'Number of items in the response.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'ScannedCount' => array(
                    'description' => 'Number of items in the complete scan before any filters are applied. A high ScannedCount value with few, or no, Count results indicates an inefficient Scan operation.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'LastEvaluatedKey' => array(
                    'description' => 'Primary key of the item where the scan operation stopped. Provide this value in a subsequent scan operation to continue the operation from that point. The LastEvaluatedKey is null when the entire scan result set is complete (i.e. the operation processed the "last page").',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'HashKeyElement' => array(
                            'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'BinaryAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RangeKeyElement' => array(
                            'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                            'type' => 'object',
                            'properties' => array(
                                'S' => array(
                                    'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                    'type' => 'string',
                                ),
                                'N' => array(
                                    'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                    'type' => 'string',
                                ),
                                'B' => array(
                                    'description' => 'Binary attributes are sequences of unsigned bytes.',
                                    'type' => 'string',
                                ),
                                'SS' => array(
                                    'description' => 'A set of strings.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'StringAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'NS' => array(
                                    'description' => 'A set of numbers.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'NumberAttributeValue',
                                        'type' => 'string',
                                    ),
                                ),
                                'BS' => array(
                                    'description' => 'A set of binary attributes.',
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
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateItemOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of attribute name-value pairs, but only if the ReturnValues parameter is specified as something other than NONE in the request.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'description' => 'AttributeValue can be String, Number, Binary, StringSet, NumberSet, BinarySet.',
                        'type' => 'object',
                        'properties' => array(
                            'S' => array(
                                'description' => 'Strings are Unicode with UTF-8 binary encoding. The maximum size is limited by the size of the primary key (1024 bytes as a range part of a key or 2048 bytes as a single part hash key) or the item size (64k).',
                                'type' => 'string',
                            ),
                            'N' => array(
                                'description' => 'Numbers are positive or negative exact-value decimals and integers. A number can have up to 38 digits precision and can be between 10^-128 to 10^+126.',
                                'type' => 'string',
                            ),
                            'B' => array(
                                'description' => 'Binary attributes are sequences of unsigned bytes.',
                                'type' => 'string',
                            ),
                            'SS' => array(
                                'description' => 'A set of strings.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StringAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'NS' => array(
                                'description' => 'A set of numbers.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NumberAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                            'BS' => array(
                                'description' => 'A set of binary attributes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BinaryAttributeValue',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConsumedCapacityUnits' => array(
                    'type' => 'numeric',
                    'location' => 'json',
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
                        'TableName' => array(
                            'description' => 'The name of the table being described.',
                            'type' => 'string',
                        ),
                        'KeySchema' => array(
                            'type' => 'object',
                            'properties' => array(
                                'HashKeyElement' => array(
                                    'description' => 'A hash key element is treated as the primary key, and can be a string or a number. Single attribute primary keys have one index value. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'RangeKeyElement' => array(
                                    'description' => 'A range key element is treated as a secondary key (used in conjunction with the primary key), and can be a string or a number, and is only used for hash-and-range primary keys. The value can be String, Number, StringSet, NumberSet.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The AttributeName of the KeySchemaElement.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The AttributeType of the KeySchemaElement which can be a String or a Number.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'TableStatus' => array(
                            'type' => 'string',
                        ),
                        'CreationDateTime' => array(
                            'type' => 'string',
                        ),
                        'ProvisionedThroughput' => array(
                            'type' => 'object',
                            'properties' => array(
                                'LastIncreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'LastDecreaseDateTime' => array(
                                    'type' => 'string',
                                ),
                                'ReadCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                                'WriteCapacityUnits' => array(
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'TableSizeBytes' => array(
                            'type' => 'numeric',
                        ),
                        'ItemCount' => array(
                            'type' => 'numeric',
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
