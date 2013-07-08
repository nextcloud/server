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
    'apiVersion' => '2012-09-25',
    'endpointPrefix' => 'elastictranscoder',
    'serviceFullName' => 'Amazon Elastic Transcoder',
    'serviceType' => 'rest-json',
    'signatureVersion' => 'v4',
    'namespace' => 'ElasticTranscoder',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elastictranscoder.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CancelJob' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-09-25/jobs/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'To cancel a job, send a DELETE request to the /2012-09-25/jobs/[jobId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the job that you want to delete.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The resource you are attempting to change is in use. For example, you are attempting to delete a pipeline that is currently in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'CreateJob' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/jobs',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateJobResponse',
            'responseType' => 'model',
            'summary' => 'To create a job, send a POST request to the /2012-09-25/jobs resource.',
            'parameters' => array(
                'PipelineId' => array(
                    'description' => 'The Id of the pipeline that you want Elastic Transcoder to use for transcoding. The pipeline determines several settings, including the Amazon S3 bucket from which Elastic Transcoder gets the files to transcode and the bucket into which Elastic Transcoder puts the transcoded files.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Input' => array(
                    'description' => 'A section of the request body that provides information about the file that is being transcoded.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Key' => array(
                            'description' => 'The name of the file to transcode. Elsewhere in the body of the JSON block is the the ID of the pipeline to use for processing the job. The InputBucket object in that pipeline tells Elastic Transcoder which Amazon S3 bucket to get the file from.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                        ),
                        'FrameRate' => array(
                            'description' => 'The frame rate of the input file. If you want Elastic Transcoder to automatically detect the frame rate of the input file, specify auto. If you want to specify the frame rate for the input file, enter one of the following values:',
                            'type' => 'string',
                        ),
                        'Resolution' => array(
                            'description' => 'This value must be auto, which causes Elastic Transcoder to automatically detect the resolution of the input file.',
                            'type' => 'string',
                        ),
                        'AspectRatio' => array(
                            'description' => 'The aspect ratio of the input file. If you want Elastic Transcoder to automatically detect the aspect ratio of the input file, specify auto. If you want to specify the aspect ratio for the output file, enter one of the following values:',
                            'type' => 'string',
                        ),
                        'Interlaced' => array(
                            'description' => 'Whether the input file is interlaced. If you want Elastic Transcoder to automatically detect whether the input file is interlaced, specify auto. If you want to specify whether the input file is interlaced, enter one of the following values:',
                            'type' => 'string',
                        ),
                        'Container' => array(
                            'description' => 'The container type for the input file. If you want Elastic Transcoder to automatically detect the container type of the input file, specify auto. If you want to specify the container type for the input file, enter one of the following values:',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Output' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Key' => array(
                            'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID. If a file with the specified name already exists in the output bucket, the job fails.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 255,
                        ),
                        'ThumbnailPattern' => array(
                            'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                            'type' => 'string',
                        ),
                        'Rotate' => array(
                            'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values: auto, 0, 90, 180, 270. The value auto generally works only if the file that you\'re transcoding contains rotation metadata.',
                            'type' => 'string',
                        ),
                        'PresetId' => array(
                            'description' => 'The Id of the preset to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding.',
                            'type' => 'string',
                        ),
                        'SegmentDuration' => array(
                            'description' => 'If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), SegmentDuration is the duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Outputs' => array(
                    'description' => 'A section of the request body that provides information about the transcoded (target) files. We recommend that you use the Outputs syntax instead of the Output syntax.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 30,
                    'items' => array(
                        'name' => 'CreateJobOutput',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID. If a file with the specified name already exists in the output bucket, the job fails.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'ThumbnailPattern' => array(
                                'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                'type' => 'string',
                            ),
                            'Rotate' => array(
                                'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values: auto, 0, 90, 180, 270. The value auto generally works only if the file that you\'re transcoding contains rotation metadata.',
                                'type' => 'string',
                            ),
                            'PresetId' => array(
                                'description' => 'The Id of the preset to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding.',
                                'type' => 'string',
                            ),
                            'SegmentDuration' => array(
                                'description' => 'If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), SegmentDuration is the duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'OutputKeyPrefix' => array(
                    'description' => 'The value, if any, that you want Elastic Transcoder to prepend to the names of all files that this job creates, including output files, thumbnails, and playlists.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Playlists' => array(
                    'description' => 'If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), Playlists contains information about the master playlists that you want Elastic Transcoder to create.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 30,
                    'items' => array(
                        'name' => 'CreateJobPlaylist',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name that you want Elastic Transcoder to assign to the master playlist, for example, nyc-vacation.m3u8. The name cannot include a / character. If you create more than one master playlist (not recommended), the values of all Name objects must be unique. Elastic Transcoder automatically appends .m3u8 to the file name. If you include .m3u8 in Name, it will appear twice in the file name.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Format' => array(
                                'description' => 'This value must currently be HLSv3.',
                                'type' => 'string',
                            ),
                            'OutputKeys' => array(
                                'description' => 'For each output in this job that you want to include in a master playlist, the value of the Outputs:Key object. If you include more than one output in a playlist, the value of SegmentDuration for all of the outputs must be the same.',
                                'type' => 'array',
                                'maxItems' => 30,
                                'items' => array(
                                    'name' => 'Key',
                                    'type' => 'string',
                                    'minLength' => 1,
                                    'maxLength' => 255,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Too many operations for a given AWS account. For example, the number of pipelines exceeds the maximum allowed.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'CreatePipeline' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/pipelines',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreatePipelineResponse',
            'responseType' => 'model',
            'summary' => 'To create a pipeline, send a POST request to the 2012-09-25/pipelines resource.',
            'parameters' => array(
                'Name' => array(
                    'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 40,
                ),
                'InputBucket' => array(
                    'description' => 'The Amazon S3 bucket in which you saved the media files that you want to transcode.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'OutputBucket' => array(
                    'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save the transcoded files. (Use this, or use ContentConfig:Bucket plus ThumbnailConfig:Bucket.)',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Role' => array(
                    'description' => 'The IAM Amazon Resource Name (ARN) for the role that you want Elastic Transcoder to use to create the pipeline.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Notifications' => array(
                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Progressing' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                            'type' => 'string',
                        ),
                        'Completed' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                            'type' => 'string',
                        ),
                        'Warning' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                            'type' => 'string',
                        ),
                        'Error' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ContentConfig' => array(
                    'description' => 'The optional ContentConfig object specifies information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists: which bucket to use, which users you want to have access to the files, the type of access you want users to have, and the storage class that you want to assign to the files.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                        ),
                        'Permissions' => array(
                            'type' => 'array',
                            'maxItems' => 30,
                            'items' => array(
                                'name' => 'Permission',
                                'type' => 'object',
                                'properties' => array(
                                    'GranteeType' => array(
                                        'type' => 'string',
                                    ),
                                    'Grantee' => array(
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                    ),
                                    'Access' => array(
                                        'type' => 'array',
                                        'maxItems' => 30,
                                        'items' => array(
                                            'name' => 'AccessControl',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ThumbnailConfig' => array(
                    'description' => 'The ThumbnailConfig object specifies several values, including the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files, which users you want to have access to the files, the type of access you want users to have, and the storage class that you want to assign to the files.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                        ),
                        'Permissions' => array(
                            'type' => 'array',
                            'maxItems' => 30,
                            'items' => array(
                                'name' => 'Permission',
                                'type' => 'object',
                                'properties' => array(
                                    'GranteeType' => array(
                                        'type' => 'string',
                                    ),
                                    'Grantee' => array(
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                    ),
                                    'Access' => array(
                                        'type' => 'array',
                                        'maxItems' => 30,
                                        'items' => array(
                                            'name' => 'AccessControl',
                                            'type' => 'string',
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
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Too many operations for a given AWS account. For example, the number of pipelines exceeds the maximum allowed.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'CreatePreset' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/presets',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreatePresetResponse',
            'responseType' => 'model',
            'summary' => 'To create a preset, send a POST request to the /2012-09-25/presets resource.',
            'parameters' => array(
                'Name' => array(
                    'description' => 'The name of the preset. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 40,
                ),
                'Description' => array(
                    'description' => 'A description of the preset.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 255,
                ),
                'Container' => array(
                    'description' => 'The container type for the output file. This value must be mp4.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Video' => array(
                    'description' => 'A section of the request body that specifies the video parameters.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Codec' => array(
                            'description' => 'The video codec for the output file. Valid values include H.264 and vp8. You can only specify vp8 when the container type is webm.',
                            'type' => 'string',
                        ),
                        'CodecOptions' => array(
                            'description' => 'Profile',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                                'data' => array(
                                    'shape_name' => 'CodecOption',
                                ),
                            ),
                        ),
                        'KeyframesMaxDist' => array(
                            'description' => 'The maximum number of frames between key frames. Key frames are fully encoded frames; the frames between key frames are encoded based, in part, on the content of the key frames. The value is an integer formatted as a string; valid values are between 1 and 100000, inclusive. A higher value results in higher compression but may also discernibly decrease video quality.',
                            'type' => 'string',
                        ),
                        'FixedGOP' => array(
                            'description' => 'Whether to use a fixed value for FixedGOP. Valid values are true and false:',
                            'type' => 'string',
                        ),
                        'BitRate' => array(
                            'description' => 'The bit rate of the video stream in the output file, in kilobits/second. Valid values depend on the values of Level and Profile. If you specify auto, Elastic Transcoder uses the detected bit rate of the input source. If you specify a value other than auto, we recommend that you specify a value less than or equal to the maximum H.264-compliant value listed for your level and profile:',
                            'type' => 'string',
                        ),
                        'FrameRate' => array(
                            'description' => 'The frames per second for the video stream in the output file. Valid values include:',
                            'type' => 'string',
                        ),
                        'Resolution' => array(
                            'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                            'type' => 'string',
                        ),
                        'AspectRatio' => array(
                            'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                            'type' => 'string',
                        ),
                        'MaxWidth' => array(
                            'description' => 'The maximum width of the output video in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 128 and 4096.',
                            'type' => 'string',
                        ),
                        'MaxHeight' => array(
                            'description' => 'The maximum height of the output video in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 96 and 3072.',
                            'type' => 'string',
                        ),
                        'DisplayAspectRatio' => array(
                            'description' => 'The value that Elastic Transcoder adds to the metadata in the output file.',
                            'type' => 'string',
                        ),
                        'SizingPolicy' => array(
                            'description' => 'Specify one of the following values to control scaling of the output video:',
                            'type' => 'string',
                        ),
                        'PaddingPolicy' => array(
                            'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of the output video to make the total size of the output video match the values that you specified for MaxWidth and MaxHeight.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Audio' => array(
                    'description' => 'A section of the request body that specifies the audio parameters.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Codec' => array(
                            'description' => 'The audio codec for the output file. This value must be AAC.',
                            'type' => 'string',
                        ),
                        'SampleRate' => array(
                            'description' => 'The sample rate of the audio stream in the output file, in Hertz. Valid values include:',
                            'type' => 'string',
                        ),
                        'BitRate' => array(
                            'description' => 'The bit rate of the audio stream in the output file, in kilobits/second. Enter an integer between 64 and 320, inclusive.',
                            'type' => 'string',
                        ),
                        'Channels' => array(
                            'description' => 'The number of audio channels in the output file. Valid values include:',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Thumbnails' => array(
                    'description' => 'A section of the request body that specifies the thumbnail parameters, if any.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Format' => array(
                            'description' => 'The format of thumbnails, if any. Valid values are jpg and png.',
                            'type' => 'string',
                        ),
                        'Interval' => array(
                            'description' => 'The number of seconds between thumbnails. Specify an integer value.',
                            'type' => 'string',
                        ),
                        'Resolution' => array(
                            'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                            'type' => 'string',
                        ),
                        'AspectRatio' => array(
                            'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                            'type' => 'string',
                        ),
                        'MaxWidth' => array(
                            'description' => 'The maximum width of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 4096.',
                            'type' => 'string',
                        ),
                        'MaxHeight' => array(
                            'description' => 'The maximum height of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 3072.',
                            'type' => 'string',
                        ),
                        'SizingPolicy' => array(
                            'description' => 'Specify one of the following values to control scaling of thumbnails:',
                            'type' => 'string',
                        ),
                        'PaddingPolicy' => array(
                            'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of thumbnails to make the total size of the thumbnails match the values that you specified for thumbnail MaxWidth and MaxHeight settings.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Too many operations for a given AWS account. For example, the number of pipelines exceeds the maximum allowed.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'DeletePipeline' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-09-25/pipelines/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'To delete a pipeline, send a DELETE request to the /2012-09-25/pipelines/[pipelineId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline that you want to delete.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The resource you are attempting to change is in use. For example, you are attempting to delete a pipeline that is currently in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'DeletePreset' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-09-25/presets/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'To delete a preset, send a DELETE request to the /2012-09-25/presets/[presetId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the preset for which you want to get detailed information.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ListJobsByPipeline' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/jobsByPipeline/{PipelineId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListJobsByPipelineResponse',
            'responseType' => 'model',
            'summary' => 'To get a list of the jobs currently in a pipeline, send a GET request to the /2012-09-25/jobsByPipeline/[pipelineId] resource.',
            'parameters' => array(
                'PipelineId' => array(
                    'required' => true,
                    'description' => 'The ID of the pipeline for which you want to get job information.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Ascending' => array(
                    'description' => 'To list jobs in chronological order by the date and time that they were submitted, enter true. To list jobs in reverse chronological order, enter false.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'PageToken' => array(
                    'description' => 'When Elastic Transcoder returns more than one page of results, use pageToken in subsequent GET requests to get each successive page of results.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ListJobsByStatus' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/jobsByStatus/{Status}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListJobsByStatusResponse',
            'responseType' => 'model',
            'summary' => 'To get a list of the jobs that have a specified status, send a GET request to the /2012-09-25/jobsByStatus/[status] resource.',
            'parameters' => array(
                'Status' => array(
                    'required' => true,
                    'description' => 'To get information about all of the jobs associated with the current AWS account that have a given status, specify the following status: Submitted, Progressing, Complete, Canceled, or Error.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Ascending' => array(
                    'description' => 'To list jobs in chronological order by the date and time that they were submitted, enter true. To list jobs in reverse chronological order, enter false.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'PageToken' => array(
                    'description' => 'When Elastic Transcoder returns more than one page of results, use pageToken in subsequent GET requests to get each successive page of results.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ListPipelines' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/pipelines',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListPipelinesResponse',
            'responseType' => 'model',
            'summary' => 'To get a list of the pipelines associated with the current AWS account, send a GET request to the /2012-09-25/pipelines resource.',
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
            'parameters' => array(
            ),
        ),
        'ListPresets' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/presets',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListPresetsResponse',
            'responseType' => 'model',
            'summary' => 'To get a list of all presets associated with the current AWS account, send a GET request to the /2012-09-25/presets resource.',
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
            'parameters' => array(
            ),
        ),
        'ReadJob' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/jobs/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ReadJobResponse',
            'responseType' => 'model',
            'summary' => 'To get detailed information about a job, send a GET request to the /2012-09-25/jobs/[jobId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the job for which you want to get detailed information.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ReadPipeline' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/pipelines/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ReadPipelineResponse',
            'responseType' => 'model',
            'summary' => 'To get detailed information about a pipeline, send a GET request to the /2012-09-25/pipelines/[pipelineId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline to read.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ReadPreset' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-09-25/presets/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ReadPresetResponse',
            'responseType' => 'model',
            'summary' => 'To get detailed information about a preset, send a GET request to the /2012-09-25/presets/[presetId] resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the preset for which you want to get detailed information.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'TestRole' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/roleTests',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'TestRoleResponse',
            'responseType' => 'model',
            'summary' => 'To test the IAM role that\'s used by Elastic Transcoder to create the pipeline, send a POST request to the /2012-09-25/roleTests resource.',
            'parameters' => array(
                'Role' => array(
                    'description' => 'The IAM Amazon Resource Name (ARN) for the role that you want Elastic Transcoder to test.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InputBucket' => array(
                    'description' => 'The Amazon S3 bucket that contains media files to be transcoded. The action attempts to read from this bucket.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'OutputBucket' => array(
                    'description' => 'The Amazon S3 bucket that Elastic Transcoder will write transcoded media files to. The action attempts to read from this bucket.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Topics' => array(
                    'description' => 'The ARNs of one or more Amazon Simple Notification Service (Amazon SNS) topics that you want the action to send a test notification to.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 30,
                    'items' => array(
                        'name' => 'SnsTopic',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'UpdatePipeline' => array(
            'httpMethod' => 'PUT',
            'uri' => '/2012-09-25/pipelines/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdatePipelineResponse',
            'responseType' => 'model',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Name' => array(
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 40,
                ),
                'InputBucket' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Role' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Notifications' => array(
                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic or topics to notify in order to report job status.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Progressing' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                            'type' => 'string',
                        ),
                        'Completed' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                            'type' => 'string',
                        ),
                        'Warning' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                            'type' => 'string',
                        ),
                        'Error' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ContentConfig' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                        ),
                        'Permissions' => array(
                            'type' => 'array',
                            'maxItems' => 30,
                            'items' => array(
                                'name' => 'Permission',
                                'type' => 'object',
                                'properties' => array(
                                    'GranteeType' => array(
                                        'type' => 'string',
                                    ),
                                    'Grantee' => array(
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                    ),
                                    'Access' => array(
                                        'type' => 'array',
                                        'maxItems' => 30,
                                        'items' => array(
                                            'name' => 'AccessControl',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ThumbnailConfig' => array(
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                        ),
                        'Permissions' => array(
                            'type' => 'array',
                            'maxItems' => 30,
                            'items' => array(
                                'name' => 'Permission',
                                'type' => 'object',
                                'properties' => array(
                                    'GranteeType' => array(
                                        'type' => 'string',
                                    ),
                                    'Grantee' => array(
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                    ),
                                    'Access' => array(
                                        'type' => 'array',
                                        'maxItems' => 30,
                                        'items' => array(
                                            'name' => 'AccessControl',
                                            'type' => 'string',
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
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'The resource you are attempting to change is in use. For example, you are attempting to delete a pipeline that is currently in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'UpdatePipelineNotifications' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/pipelines/{Id}/notifications',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdatePipelineNotificationsResponse',
            'responseType' => 'model',
            'summary' => 'To update Amazon Simple Notification Service (Amazon SNS) notifications for a pipeline, send a POST request to the /2012-09-25/pipelines/[pipelineId]/notifications resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline for which you want to change notification settings.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Notifications' => array(
                    'description' => 'The topic ARN for the Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Progressing' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                            'type' => 'string',
                        ),
                        'Completed' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                            'type' => 'string',
                        ),
                        'Warning' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                            'type' => 'string',
                        ),
                        'Error' => array(
                            'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The resource you are attempting to change is in use. For example, you are attempting to delete a pipeline that is currently in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'UpdatePipelineStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-09-25/pipelines/{Id}/status',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdatePipelineStatusResponse',
            'responseType' => 'model',
            'summary' => 'To pause or reactivate a pipeline, so the pipeline stops or restarts processing jobs, update the status for the pipeline. Send a POST request to the /2012-09-25/pipelines/[pipelineId]/status resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline to update.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Status' => array(
                    'description' => 'The desired status of the pipeline:',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameter values were not provided in the request.',
                    'class' => 'ValidationException',
                ),
                array(
                    'class' => 'IncompatibleVersionException',
                ),
                array(
                    'reason' => 'The requested resource does not exist or is not available. For example, the pipeline to which you\'re trying to add a job doesn\'t exist or is still being created.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'The resource you are attempting to change is in use. For example, you are attempting to delete a pipeline that is currently in use.',
                    'class' => 'ResourceInUseException',
                ),
                array(
                    'reason' => 'General authentication failure. The request was not signed correctly.',
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'reason' => 'Elastic Transcoder encountered an unexpected exception while trying to fulfill the request.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CreateJobResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Job' => array(
                    'description' => 'A section of the response body that provides information about the job that is created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier that Elastic Transcoder assigned to the job. You use this value to get settings for the job or to delete the job.',
                            'type' => 'string',
                        ),
                        'PipelineId' => array(
                            'description' => 'The Id of the pipeline that you want Elastic Transcoder to use for transcoding. The pipeline determines several settings, including the Amazon S3 bucket from which Elastic Transcoder gets the files to transcode and the bucket into which Elastic Transcoder puts the transcoded files.',
                            'type' => 'string',
                        ),
                        'Input' => array(
                            'description' => 'A section of the request or response body that provides information about the file that is being transcoded.',
                            'type' => 'object',
                            'properties' => array(
                                'Key' => array(
                                    'description' => 'The name of the file to transcode. Elsewhere in the body of the JSON block is the the ID of the pipeline to use for processing the job. The InputBucket object in that pipeline tells Elastic Transcoder which Amazon S3 bucket to get the file from.',
                                    'type' => 'string',
                                ),
                                'FrameRate' => array(
                                    'description' => 'The frame rate of the input file. If you want Elastic Transcoder to automatically detect the frame rate of the input file, specify auto. If you want to specify the frame rate for the input file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'This value must be auto, which causes Elastic Transcoder to automatically detect the resolution of the input file.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'The aspect ratio of the input file. If you want Elastic Transcoder to automatically detect the aspect ratio of the input file, specify auto. If you want to specify the aspect ratio for the output file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Interlaced' => array(
                                    'description' => 'Whether the input file is interlaced. If you want Elastic Transcoder to automatically detect whether the input file is interlaced, specify auto. If you want to specify whether the input file is interlaced, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Container' => array(
                                    'description' => 'The container type for the input file. If you want Elastic Transcoder to automatically detect the container type of the input file, specify auto. If you want to specify the container type for the input file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Output' => array(
                            'description' => 'If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                            'type' => 'object',
                            'properties' => array(
                                'Id' => array(
                                    'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                    'type' => 'string',
                                ),
                                'Key' => array(
                                    'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                    'type' => 'string',
                                ),
                                'ThumbnailPattern' => array(
                                    'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                    'type' => 'string',
                                ),
                                'Rotate' => array(
                                    'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'PresetId' => array(
                                    'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                    'type' => 'string',
                                ),
                                'SegmentDuration' => array(
                                    'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                    'type' => 'string',
                                ),
                                'Status' => array(
                                    'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                    'type' => 'string',
                                ),
                                'StatusDetail' => array(
                                    'description' => 'Information that further explains Status.',
                                    'type' => 'string',
                                ),
                                'Duration' => array(
                                    'description' => 'Duration of the output file, in seconds.',
                                    'type' => 'numeric',
                                ),
                                'Width' => array(
                                    'description' => 'Specifies the width of the output file in pixels.',
                                    'type' => 'numeric',
                                ),
                                'Height' => array(
                                    'description' => 'Height of the output file, in pixels.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'Outputs' => array(
                            'description' => 'Information about the output files. We recommend that you use the Outputs syntax for all jobs, even when you want Elastic Transcoder to transcode a file into only one format. Do not use both the Outputs and Output syntaxes in the same request. You can create a maximum of 30 outputs per job.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'JobOutput',
                                'description' => 'Outputs recommended instead.If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                        'type' => 'string',
                                    ),
                                    'ThumbnailPattern' => array(
                                        'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                        'type' => 'string',
                                    ),
                                    'Rotate' => array(
                                        'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'PresetId' => array(
                                        'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                        'type' => 'string',
                                    ),
                                    'SegmentDuration' => array(
                                        'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'description' => 'Information that further explains Status.',
                                        'type' => 'string',
                                    ),
                                    'Duration' => array(
                                        'description' => 'Duration of the output file, in seconds.',
                                        'type' => 'numeric',
                                    ),
                                    'Width' => array(
                                        'description' => 'Specifies the width of the output file in pixels.',
                                        'type' => 'numeric',
                                    ),
                                    'Height' => array(
                                        'description' => 'Height of the output file, in pixels.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                        'OutputKeyPrefix' => array(
                            'description' => 'The value, if any, that you want Elastic Transcoder to prepend to the names of all files that this job creates, including output files, thumbnails, and playlists. We recommend that you add a / or some other delimiter to the end of the OutputKeyPrefix.',
                            'type' => 'string',
                        ),
                        'Playlists' => array(
                            'description' => 'Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), Playlists contains information about the master playlists that you want Elastic Transcoder to create.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Playlist',
                                'type' => 'object',
                                'properties' => array(
                                    'Name' => array(
                                        'type' => 'string',
                                    ),
                                    'Format' => array(
                                        'type' => 'string',
                                    ),
                                    'OutputKeys' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Key',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of the job: Submitted, Progressing, l, Canceled, or Error.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreatePipelineResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipeline' => array(
                    'description' => 'A section of the response body that provides information about the pipeline that is created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current status of the pipeline:',
                            'type' => 'string',
                        ),
                        'InputBucket' => array(
                            'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                            'type' => 'string',
                        ),
                        'OutputBucket' => array(
                            'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                            'type' => 'string',
                        ),
                        'Role' => array(
                            'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                            'type' => 'string',
                        ),
                        'Notifications' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                            'type' => 'object',
                            'properties' => array(
                                'Progressing' => array(
                                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                    'type' => 'string',
                                ),
                                'Completed' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                    'type' => 'string',
                                ),
                                'Warning' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                    'type' => 'string',
                                ),
                                'Error' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ContentConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ThumbnailConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
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
        'CreatePresetResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Preset' => array(
                    'description' => 'A section of the response body that provides information about the preset that is created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'Identifier for the new preset. You use this value to get settings for the preset or to delete it.',
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the preset.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'A description of the preset.',
                            'type' => 'string',
                        ),
                        'Container' => array(
                            'description' => 'The container type for the output file. This value must be mp4.',
                            'type' => 'string',
                        ),
                        'Audio' => array(
                            'description' => 'A section of the response body that provides information about the audio preset values.',
                            'type' => 'object',
                            'properties' => array(
                                'Codec' => array(
                                    'description' => 'The audio codec for the output file. This value must be AAC.',
                                    'type' => 'string',
                                ),
                                'SampleRate' => array(
                                    'description' => 'The sample rate of the audio stream in the output file, in Hertz. Valid values include:',
                                    'type' => 'string',
                                ),
                                'BitRate' => array(
                                    'description' => 'The bit rate of the audio stream in the output file, in kilobits/second. Enter an integer between 64 and 320, inclusive.',
                                    'type' => 'string',
                                ),
                                'Channels' => array(
                                    'description' => 'The number of audio channels in the output file. Valid values include:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Video' => array(
                            'description' => 'A section of the response body that provides information about the video preset values.',
                            'type' => 'object',
                            'properties' => array(
                                'Codec' => array(
                                    'description' => 'The video codec for the output file. Valid values include H.264 and vp8. You can only specify vp8 when the container type is webm.',
                                    'type' => 'string',
                                ),
                                'CodecOptions' => array(
                                    'description' => 'Profile',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'type' => 'string',
                                    ),
                                ),
                                'KeyframesMaxDist' => array(
                                    'description' => 'The maximum number of frames between key frames. Key frames are fully encoded frames; the frames between key frames are encoded based, in part, on the content of the key frames. The value is an integer formatted as a string; valid values are between 1 and 100000, inclusive. A higher value results in higher compression but may also discernibly decrease video quality.',
                                    'type' => 'string',
                                ),
                                'FixedGOP' => array(
                                    'description' => 'Whether to use a fixed value for FixedGOP. Valid values are true and false:',
                                    'type' => 'string',
                                ),
                                'BitRate' => array(
                                    'description' => 'The bit rate of the video stream in the output file, in kilobits/second. Valid values depend on the values of Level and Profile. If you specify auto, Elastic Transcoder uses the detected bit rate of the input source. If you specify a value other than auto, we recommend that you specify a value less than or equal to the maximum H.264-compliant value listed for your level and profile:',
                                    'type' => 'string',
                                ),
                                'FrameRate' => array(
                                    'description' => 'The frames per second for the video stream in the output file. Valid values include:',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'MaxWidth' => array(
                                    'description' => 'The maximum width of the output video in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 128 and 4096.',
                                    'type' => 'string',
                                ),
                                'MaxHeight' => array(
                                    'description' => 'The maximum height of the output video in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 96 and 3072.',
                                    'type' => 'string',
                                ),
                                'DisplayAspectRatio' => array(
                                    'description' => 'The value that Elastic Transcoder adds to the metadata in the output file.',
                                    'type' => 'string',
                                ),
                                'SizingPolicy' => array(
                                    'description' => 'Specify one of the following values to control scaling of the output video:',
                                    'type' => 'string',
                                ),
                                'PaddingPolicy' => array(
                                    'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of the output video to make the total size of the output video match the values that you specified for MaxWidth and MaxHeight.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Thumbnails' => array(
                            'description' => 'A section of the response body that provides information about the thumbnail preset values, if any.',
                            'type' => 'object',
                            'properties' => array(
                                'Format' => array(
                                    'description' => 'The format of thumbnails, if any. Valid values are jpg and png.',
                                    'type' => 'string',
                                ),
                                'Interval' => array(
                                    'description' => 'The number of seconds between thumbnails. Specify an integer value.',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'MaxWidth' => array(
                                    'description' => 'The maximum width of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 4096.',
                                    'type' => 'string',
                                ),
                                'MaxHeight' => array(
                                    'description' => 'The maximum height of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 3072.',
                                    'type' => 'string',
                                ),
                                'SizingPolicy' => array(
                                    'description' => 'Specify one of the following values to control scaling of thumbnails:',
                                    'type' => 'string',
                                ),
                                'PaddingPolicy' => array(
                                    'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of thumbnails to make the total size of the thumbnails match the values that you specified for thumbnail MaxWidth and MaxHeight settings.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Type' => array(
                            'description' => 'Whether the preset is a default preset provided by Elastic Transcoder (System) or a preset that you have defined (Custom).',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Warning' => array(
                    'description' => 'If the preset settings don\'t comply with the standards for the video codec but Elastic Transcoder created the preset, this message explains the reason the preset settings don\'t meet the standard. Elastic Transcoder created the preset because the settings might produce acceptable output.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListJobsByPipelineResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Jobs' => array(
                    'description' => 'An array of Job objects that are in the specified pipeline.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Job',
                        'description' => 'A section of the response body that provides information about the job that is created.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The identifier that Elastic Transcoder assigned to the job. You use this value to get settings for the job or to delete the job.',
                                'type' => 'string',
                            ),
                            'PipelineId' => array(
                                'description' => 'The Id of the pipeline that you want Elastic Transcoder to use for transcoding. The pipeline determines several settings, including the Amazon S3 bucket from which Elastic Transcoder gets the files to transcode and the bucket into which Elastic Transcoder puts the transcoded files.',
                                'type' => 'string',
                            ),
                            'Input' => array(
                                'description' => 'A section of the request or response body that provides information about the file that is being transcoded.',
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The name of the file to transcode. Elsewhere in the body of the JSON block is the the ID of the pipeline to use for processing the job. The InputBucket object in that pipeline tells Elastic Transcoder which Amazon S3 bucket to get the file from.',
                                        'type' => 'string',
                                    ),
                                    'FrameRate' => array(
                                        'description' => 'The frame rate of the input file. If you want Elastic Transcoder to automatically detect the frame rate of the input file, specify auto. If you want to specify the frame rate for the input file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Resolution' => array(
                                        'description' => 'This value must be auto, which causes Elastic Transcoder to automatically detect the resolution of the input file.',
                                        'type' => 'string',
                                    ),
                                    'AspectRatio' => array(
                                        'description' => 'The aspect ratio of the input file. If you want Elastic Transcoder to automatically detect the aspect ratio of the input file, specify auto. If you want to specify the aspect ratio for the output file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Interlaced' => array(
                                        'description' => 'Whether the input file is interlaced. If you want Elastic Transcoder to automatically detect whether the input file is interlaced, specify auto. If you want to specify whether the input file is interlaced, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Container' => array(
                                        'description' => 'The container type for the input file. If you want Elastic Transcoder to automatically detect the container type of the input file, specify auto. If you want to specify the container type for the input file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Output' => array(
                                'description' => 'If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                        'type' => 'string',
                                    ),
                                    'ThumbnailPattern' => array(
                                        'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                        'type' => 'string',
                                    ),
                                    'Rotate' => array(
                                        'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'PresetId' => array(
                                        'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                        'type' => 'string',
                                    ),
                                    'SegmentDuration' => array(
                                        'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'description' => 'Information that further explains Status.',
                                        'type' => 'string',
                                    ),
                                    'Duration' => array(
                                        'description' => 'Duration of the output file, in seconds.',
                                        'type' => 'numeric',
                                    ),
                                    'Width' => array(
                                        'description' => 'Specifies the width of the output file in pixels.',
                                        'type' => 'numeric',
                                    ),
                                    'Height' => array(
                                        'description' => 'Height of the output file, in pixels.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'Outputs' => array(
                                'description' => 'Information about the output files. We recommend that you use the Outputs syntax for all jobs, even when you want Elastic Transcoder to transcode a file into only one format. Do not use both the Outputs and Output syntaxes in the same request. You can create a maximum of 30 outputs per job.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'JobOutput',
                                    'description' => 'Outputs recommended instead.If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Id' => array(
                                            'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                            'type' => 'string',
                                        ),
                                        'Key' => array(
                                            'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                            'type' => 'string',
                                        ),
                                        'ThumbnailPattern' => array(
                                            'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                            'type' => 'string',
                                        ),
                                        'Rotate' => array(
                                            'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                            'type' => 'string',
                                        ),
                                        'PresetId' => array(
                                            'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                            'type' => 'string',
                                        ),
                                        'SegmentDuration' => array(
                                            'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                            'type' => 'string',
                                        ),
                                        'StatusDetail' => array(
                                            'description' => 'Information that further explains Status.',
                                            'type' => 'string',
                                        ),
                                        'Duration' => array(
                                            'description' => 'Duration of the output file, in seconds.',
                                            'type' => 'numeric',
                                        ),
                                        'Width' => array(
                                            'description' => 'Specifies the width of the output file in pixels.',
                                            'type' => 'numeric',
                                        ),
                                        'Height' => array(
                                            'description' => 'Height of the output file, in pixels.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                            'OutputKeyPrefix' => array(
                                'description' => 'The value, if any, that you want Elastic Transcoder to prepend to the names of all files that this job creates, including output files, thumbnails, and playlists. We recommend that you add a / or some other delimiter to the end of the OutputKeyPrefix.',
                                'type' => 'string',
                            ),
                            'Playlists' => array(
                                'description' => 'Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), Playlists contains information about the master playlists that you want Elastic Transcoder to create.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Playlist',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'type' => 'string',
                                        ),
                                        'Format' => array(
                                            'type' => 'string',
                                        ),
                                        'OutputKeys' => array(
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'Key',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                        ),
                                        'StatusDetail' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'The status of the job: Submitted, Progressing, l, Canceled, or Error.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextPageToken' => array(
                    'description' => 'A value that you use to access the second and subsequent pages of results, if any. When the jobs in the specified pipeline fit on one page or when you\'ve reached the last page of results, the value of NextPageToken is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListJobsByStatusResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Jobs' => array(
                    'description' => 'An array of Job objects that have the specified status.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Job',
                        'description' => 'A section of the response body that provides information about the job that is created.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The identifier that Elastic Transcoder assigned to the job. You use this value to get settings for the job or to delete the job.',
                                'type' => 'string',
                            ),
                            'PipelineId' => array(
                                'description' => 'The Id of the pipeline that you want Elastic Transcoder to use for transcoding. The pipeline determines several settings, including the Amazon S3 bucket from which Elastic Transcoder gets the files to transcode and the bucket into which Elastic Transcoder puts the transcoded files.',
                                'type' => 'string',
                            ),
                            'Input' => array(
                                'description' => 'A section of the request or response body that provides information about the file that is being transcoded.',
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The name of the file to transcode. Elsewhere in the body of the JSON block is the the ID of the pipeline to use for processing the job. The InputBucket object in that pipeline tells Elastic Transcoder which Amazon S3 bucket to get the file from.',
                                        'type' => 'string',
                                    ),
                                    'FrameRate' => array(
                                        'description' => 'The frame rate of the input file. If you want Elastic Transcoder to automatically detect the frame rate of the input file, specify auto. If you want to specify the frame rate for the input file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Resolution' => array(
                                        'description' => 'This value must be auto, which causes Elastic Transcoder to automatically detect the resolution of the input file.',
                                        'type' => 'string',
                                    ),
                                    'AspectRatio' => array(
                                        'description' => 'The aspect ratio of the input file. If you want Elastic Transcoder to automatically detect the aspect ratio of the input file, specify auto. If you want to specify the aspect ratio for the output file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Interlaced' => array(
                                        'description' => 'Whether the input file is interlaced. If you want Elastic Transcoder to automatically detect whether the input file is interlaced, specify auto. If you want to specify whether the input file is interlaced, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'Container' => array(
                                        'description' => 'The container type for the input file. If you want Elastic Transcoder to automatically detect the container type of the input file, specify auto. If you want to specify the container type for the input file, enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Output' => array(
                                'description' => 'If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                        'type' => 'string',
                                    ),
                                    'ThumbnailPattern' => array(
                                        'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                        'type' => 'string',
                                    ),
                                    'Rotate' => array(
                                        'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'PresetId' => array(
                                        'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                        'type' => 'string',
                                    ),
                                    'SegmentDuration' => array(
                                        'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'description' => 'Information that further explains Status.',
                                        'type' => 'string',
                                    ),
                                    'Duration' => array(
                                        'description' => 'Duration of the output file, in seconds.',
                                        'type' => 'numeric',
                                    ),
                                    'Width' => array(
                                        'description' => 'Specifies the width of the output file in pixels.',
                                        'type' => 'numeric',
                                    ),
                                    'Height' => array(
                                        'description' => 'Height of the output file, in pixels.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'Outputs' => array(
                                'description' => 'Information about the output files. We recommend that you use the Outputs syntax for all jobs, even when you want Elastic Transcoder to transcode a file into only one format. Do not use both the Outputs and Output syntaxes in the same request. You can create a maximum of 30 outputs per job.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'JobOutput',
                                    'description' => 'Outputs recommended instead.If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Id' => array(
                                            'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                            'type' => 'string',
                                        ),
                                        'Key' => array(
                                            'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                            'type' => 'string',
                                        ),
                                        'ThumbnailPattern' => array(
                                            'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                            'type' => 'string',
                                        ),
                                        'Rotate' => array(
                                            'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                            'type' => 'string',
                                        ),
                                        'PresetId' => array(
                                            'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                            'type' => 'string',
                                        ),
                                        'SegmentDuration' => array(
                                            'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                            'type' => 'string',
                                        ),
                                        'StatusDetail' => array(
                                            'description' => 'Information that further explains Status.',
                                            'type' => 'string',
                                        ),
                                        'Duration' => array(
                                            'description' => 'Duration of the output file, in seconds.',
                                            'type' => 'numeric',
                                        ),
                                        'Width' => array(
                                            'description' => 'Specifies the width of the output file in pixels.',
                                            'type' => 'numeric',
                                        ),
                                        'Height' => array(
                                            'description' => 'Height of the output file, in pixels.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                            'OutputKeyPrefix' => array(
                                'description' => 'The value, if any, that you want Elastic Transcoder to prepend to the names of all files that this job creates, including output files, thumbnails, and playlists. We recommend that you add a / or some other delimiter to the end of the OutputKeyPrefix.',
                                'type' => 'string',
                            ),
                            'Playlists' => array(
                                'description' => 'Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), Playlists contains information about the master playlists that you want Elastic Transcoder to create.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Playlist',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'type' => 'string',
                                        ),
                                        'Format' => array(
                                            'type' => 'string',
                                        ),
                                        'OutputKeys' => array(
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'Key',
                                                'type' => 'string',
                                            ),
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                        ),
                                        'StatusDetail' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'The status of the job: Submitted, Progressing, l, Canceled, or Error.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextPageToken' => array(
                    'description' => 'A value that you use to access the second and subsequent pages of results, if any. When the jobs in the specified pipeline fit on one page or when you\'ve reached the last page of results, the value of NextPageToken is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListPipelinesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipelines' => array(
                    'description' => 'An array of Pipeline objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Pipeline',
                        'description' => 'The pipeline (queue) that is used to manage jobs.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The current status of the pipeline:',
                                'type' => 'string',
                            ),
                            'InputBucket' => array(
                                'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                                'type' => 'string',
                            ),
                            'OutputBucket' => array(
                                'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                                'type' => 'string',
                            ),
                            'Role' => array(
                                'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                                'type' => 'string',
                            ),
                            'Notifications' => array(
                                'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                                'type' => 'object',
                                'properties' => array(
                                    'Progressing' => array(
                                        'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                        'type' => 'string',
                                    ),
                                    'Completed' => array(
                                        'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                        'type' => 'string',
                                    ),
                                    'Warning' => array(
                                        'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                        'type' => 'string',
                                    ),
                                    'Error' => array(
                                        'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'ContentConfig' => array(
                                'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                                'type' => 'object',
                                'properties' => array(
                                    'Bucket' => array(
                                        'type' => 'string',
                                    ),
                                    'StorageClass' => array(
                                        'type' => 'string',
                                    ),
                                    'Permissions' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Permission',
                                            'type' => 'object',
                                            'properties' => array(
                                                'GranteeType' => array(
                                                    'type' => 'string',
                                                ),
                                                'Grantee' => array(
                                                    'type' => 'string',
                                                ),
                                                'Access' => array(
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'AccessControl',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'ThumbnailConfig' => array(
                                'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                                'type' => 'object',
                                'properties' => array(
                                    'Bucket' => array(
                                        'type' => 'string',
                                    ),
                                    'StorageClass' => array(
                                        'type' => 'string',
                                    ),
                                    'Permissions' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Permission',
                                            'type' => 'object',
                                            'properties' => array(
                                                'GranteeType' => array(
                                                    'type' => 'string',
                                                ),
                                                'Grantee' => array(
                                                    'type' => 'string',
                                                ),
                                                'Access' => array(
                                                    'type' => 'array',
                                                    'items' => array(
                                                        'name' => 'AccessControl',
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
        'ListPresetsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Presets' => array(
                    'description' => 'An array of Preset objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Preset',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'Identifier for the new preset. You use this value to get settings for the preset or to delete it.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The name of the preset.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the preset.',
                                'type' => 'string',
                            ),
                            'Container' => array(
                                'description' => 'The container type for the output file. This value must be mp4.',
                                'type' => 'string',
                            ),
                            'Audio' => array(
                                'description' => 'A section of the response body that provides information about the audio preset values.',
                                'type' => 'object',
                                'properties' => array(
                                    'Codec' => array(
                                        'description' => 'The audio codec for the output file. This value must be AAC.',
                                        'type' => 'string',
                                    ),
                                    'SampleRate' => array(
                                        'description' => 'The sample rate of the audio stream in the output file, in Hertz. Valid values include:',
                                        'type' => 'string',
                                    ),
                                    'BitRate' => array(
                                        'description' => 'The bit rate of the audio stream in the output file, in kilobits/second. Enter an integer between 64 and 320, inclusive.',
                                        'type' => 'string',
                                    ),
                                    'Channels' => array(
                                        'description' => 'The number of audio channels in the output file. Valid values include:',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Video' => array(
                                'description' => 'A section of the response body that provides information about the video preset values.',
                                'type' => 'object',
                                'properties' => array(
                                    'Codec' => array(
                                        'description' => 'The video codec for the output file. Valid values include H.264 and vp8. You can only specify vp8 when the container type is webm.',
                                        'type' => 'string',
                                    ),
                                    'CodecOptions' => array(
                                        'description' => 'Profile',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'KeyframesMaxDist' => array(
                                        'description' => 'The maximum number of frames between key frames. Key frames are fully encoded frames; the frames between key frames are encoded based, in part, on the content of the key frames. The value is an integer formatted as a string; valid values are between 1 and 100000, inclusive. A higher value results in higher compression but may also discernibly decrease video quality.',
                                        'type' => 'string',
                                    ),
                                    'FixedGOP' => array(
                                        'description' => 'Whether to use a fixed value for FixedGOP. Valid values are true and false:',
                                        'type' => 'string',
                                    ),
                                    'BitRate' => array(
                                        'description' => 'The bit rate of the video stream in the output file, in kilobits/second. Valid values depend on the values of Level and Profile. If you specify auto, Elastic Transcoder uses the detected bit rate of the input source. If you specify a value other than auto, we recommend that you specify a value less than or equal to the maximum H.264-compliant value listed for your level and profile:',
                                        'type' => 'string',
                                    ),
                                    'FrameRate' => array(
                                        'description' => 'The frames per second for the video stream in the output file. Valid values include:',
                                        'type' => 'string',
                                    ),
                                    'Resolution' => array(
                                        'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                        'type' => 'string',
                                    ),
                                    'AspectRatio' => array(
                                        'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                        'type' => 'string',
                                    ),
                                    'MaxWidth' => array(
                                        'description' => 'The maximum width of the output video in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 128 and 4096.',
                                        'type' => 'string',
                                    ),
                                    'MaxHeight' => array(
                                        'description' => 'The maximum height of the output video in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 96 and 3072.',
                                        'type' => 'string',
                                    ),
                                    'DisplayAspectRatio' => array(
                                        'description' => 'The value that Elastic Transcoder adds to the metadata in the output file.',
                                        'type' => 'string',
                                    ),
                                    'SizingPolicy' => array(
                                        'description' => 'Specify one of the following values to control scaling of the output video:',
                                        'type' => 'string',
                                    ),
                                    'PaddingPolicy' => array(
                                        'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of the output video to make the total size of the output video match the values that you specified for MaxWidth and MaxHeight.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Thumbnails' => array(
                                'description' => 'A section of the response body that provides information about the thumbnail preset values, if any.',
                                'type' => 'object',
                                'properties' => array(
                                    'Format' => array(
                                        'description' => 'The format of thumbnails, if any. Valid values are jpg and png.',
                                        'type' => 'string',
                                    ),
                                    'Interval' => array(
                                        'description' => 'The number of seconds between thumbnails. Specify an integer value.',
                                        'type' => 'string',
                                    ),
                                    'Resolution' => array(
                                        'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                        'type' => 'string',
                                    ),
                                    'AspectRatio' => array(
                                        'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                        'type' => 'string',
                                    ),
                                    'MaxWidth' => array(
                                        'description' => 'The maximum width of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 4096.',
                                        'type' => 'string',
                                    ),
                                    'MaxHeight' => array(
                                        'description' => 'The maximum height of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 3072.',
                                        'type' => 'string',
                                    ),
                                    'SizingPolicy' => array(
                                        'description' => 'Specify one of the following values to control scaling of thumbnails:',
                                        'type' => 'string',
                                    ),
                                    'PaddingPolicy' => array(
                                        'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of thumbnails to make the total size of the thumbnails match the values that you specified for thumbnail MaxWidth and MaxHeight settings.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Type' => array(
                                'description' => 'Whether the preset is a default preset provided by Elastic Transcoder (System) or a preset that you have defined (Custom).',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ReadJobResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Job' => array(
                    'description' => 'A section of the response body that provides information about the job.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier that Elastic Transcoder assigned to the job. You use this value to get settings for the job or to delete the job.',
                            'type' => 'string',
                        ),
                        'PipelineId' => array(
                            'description' => 'The Id of the pipeline that you want Elastic Transcoder to use for transcoding. The pipeline determines several settings, including the Amazon S3 bucket from which Elastic Transcoder gets the files to transcode and the bucket into which Elastic Transcoder puts the transcoded files.',
                            'type' => 'string',
                        ),
                        'Input' => array(
                            'description' => 'A section of the request or response body that provides information about the file that is being transcoded.',
                            'type' => 'object',
                            'properties' => array(
                                'Key' => array(
                                    'description' => 'The name of the file to transcode. Elsewhere in the body of the JSON block is the the ID of the pipeline to use for processing the job. The InputBucket object in that pipeline tells Elastic Transcoder which Amazon S3 bucket to get the file from.',
                                    'type' => 'string',
                                ),
                                'FrameRate' => array(
                                    'description' => 'The frame rate of the input file. If you want Elastic Transcoder to automatically detect the frame rate of the input file, specify auto. If you want to specify the frame rate for the input file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'This value must be auto, which causes Elastic Transcoder to automatically detect the resolution of the input file.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'The aspect ratio of the input file. If you want Elastic Transcoder to automatically detect the aspect ratio of the input file, specify auto. If you want to specify the aspect ratio for the output file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Interlaced' => array(
                                    'description' => 'Whether the input file is interlaced. If you want Elastic Transcoder to automatically detect whether the input file is interlaced, specify auto. If you want to specify whether the input file is interlaced, enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'Container' => array(
                                    'description' => 'The container type for the input file. If you want Elastic Transcoder to automatically detect the container type of the input file, specify auto. If you want to specify the container type for the input file, enter one of the following values:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Output' => array(
                            'description' => 'If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                            'type' => 'object',
                            'properties' => array(
                                'Id' => array(
                                    'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                    'type' => 'string',
                                ),
                                'Key' => array(
                                    'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                    'type' => 'string',
                                ),
                                'ThumbnailPattern' => array(
                                    'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                    'type' => 'string',
                                ),
                                'Rotate' => array(
                                    'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                    'type' => 'string',
                                ),
                                'PresetId' => array(
                                    'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                    'type' => 'string',
                                ),
                                'SegmentDuration' => array(
                                    'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                    'type' => 'string',
                                ),
                                'Status' => array(
                                    'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                    'type' => 'string',
                                ),
                                'StatusDetail' => array(
                                    'description' => 'Information that further explains Status.',
                                    'type' => 'string',
                                ),
                                'Duration' => array(
                                    'description' => 'Duration of the output file, in seconds.',
                                    'type' => 'numeric',
                                ),
                                'Width' => array(
                                    'description' => 'Specifies the width of the output file in pixels.',
                                    'type' => 'numeric',
                                ),
                                'Height' => array(
                                    'description' => 'Height of the output file, in pixels.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'Outputs' => array(
                            'description' => 'Information about the output files. We recommend that you use the Outputs syntax for all jobs, even when you want Elastic Transcoder to transcode a file into only one format. Do not use both the Outputs and Output syntaxes in the same request. You can create a maximum of 30 outputs per job.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'JobOutput',
                                'description' => 'Outputs recommended instead.If you specified one output for a job, information about that output. If you specified multiple outputs for a job, the Output object lists information about the first output. This duplicates the information that is listed for the first output in the Outputs object.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'A sequential counter, starting with 1, that identifies an output among the outputs from the current job. In the Output syntax, this value is always 1.',
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'description' => 'The name to assign to the transcoded file. Elastic Transcoder saves the file in the Amazon S3 bucket specified by the OutputBucket object in the pipeline that is specified by the pipeline ID.',
                                        'type' => 'string',
                                    ),
                                    'ThumbnailPattern' => array(
                                        'description' => 'Whether you want Elastic Transcoder to create thumbnails for your videos and, if so, how you want Elastic Transcoder to name the files.',
                                        'type' => 'string',
                                    ),
                                    'Rotate' => array(
                                        'description' => 'The number of degrees clockwise by which you want Elastic Transcoder to rotate the output relative to the input. Enter one of the following values:',
                                        'type' => 'string',
                                    ),
                                    'PresetId' => array(
                                        'description' => 'The value of the Id object for the preset that you want to use for this job. The preset determines the audio, video, and thumbnail settings that Elastic Transcoder uses for transcoding. To use a preset that you created, specify the preset ID that Elastic Transcoder returned in the response when you created the preset. You can also use the Elastic Transcoder system presets, which you can get with ListPresets.',
                                        'type' => 'string',
                                    ),
                                    'SegmentDuration' => array(
                                        'description' => '(Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Containeris ts (MPEG-TS), SegmentDuration is the maximum duration of each .ts file in seconds. The range of valid values is 1 to 60 seconds. If the duration of the video is not evenly divisible by SegmentDuration, the duration of the last segment is the remainder of total length/SegmentDuration. Elastic Transcoder creates an output-specific playlist for each output that you specify in OutputKeys. To add an output to the master playlist for this job, include it in OutputKeys.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of one output in a job. If you specified only one output for the job, Outputs:Status is always the same as Job:Status. If you specified more than one output: Job:Status and Outputs:Status for all of the outputs is Submitted until Elastic Transcoder starts to process the first output. When Elastic Transcoder starts to process the first output, Outputs:Status for that output and Job:Status both change to Progressing. For each output, the value of Outputs:Status remains Submitted until Elastic Transcoder starts to process the output. Job:Status remains Progressing until all of the outputs reach a terminal status, either Complete or Error. When all of the outputs reach a terminal status, Job:Status changes to Complete only if Outputs:Status for all of the outputs is Complete. If Outputs:Status for one or more outputs is Error, the terminal status for Job:Status is also Error. The value of Status is one of the following: Submitted, Progressing, Complete, Canceled, or Error.',
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'description' => 'Information that further explains Status.',
                                        'type' => 'string',
                                    ),
                                    'Duration' => array(
                                        'description' => 'Duration of the output file, in seconds.',
                                        'type' => 'numeric',
                                    ),
                                    'Width' => array(
                                        'description' => 'Specifies the width of the output file in pixels.',
                                        'type' => 'numeric',
                                    ),
                                    'Height' => array(
                                        'description' => 'Height of the output file, in pixels.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                        'OutputKeyPrefix' => array(
                            'description' => 'The value, if any, that you want Elastic Transcoder to prepend to the names of all files that this job creates, including output files, thumbnails, and playlists. We recommend that you add a / or some other delimiter to the end of the OutputKeyPrefix.',
                            'type' => 'string',
                        ),
                        'Playlists' => array(
                            'description' => 'Outputs in MPEG-TS format only.If you specify a preset in PresetId for which the value of Container is ts (MPEG-TS), Playlists contains information about the master playlists that you want Elastic Transcoder to create.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Playlist',
                                'type' => 'object',
                                'properties' => array(
                                    'Name' => array(
                                        'type' => 'string',
                                    ),
                                    'Format' => array(
                                        'type' => 'string',
                                    ),
                                    'OutputKeys' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Key',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                    ),
                                    'StatusDetail' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Status' => array(
                            'description' => 'The status of the job: Submitted, Progressing, l, Canceled, or Error.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'ReadPipelineResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipeline' => array(
                    'description' => 'A section of the response body that provides information about the pipeline.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current status of the pipeline:',
                            'type' => 'string',
                        ),
                        'InputBucket' => array(
                            'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                            'type' => 'string',
                        ),
                        'OutputBucket' => array(
                            'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                            'type' => 'string',
                        ),
                        'Role' => array(
                            'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                            'type' => 'string',
                        ),
                        'Notifications' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                            'type' => 'object',
                            'properties' => array(
                                'Progressing' => array(
                                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                    'type' => 'string',
                                ),
                                'Completed' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                    'type' => 'string',
                                ),
                                'Warning' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                    'type' => 'string',
                                ),
                                'Error' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ContentConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ThumbnailConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
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
        'ReadPresetResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Preset' => array(
                    'description' => 'A section of the response body that provides information about the preset.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'Identifier for the new preset. You use this value to get settings for the preset or to delete it.',
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the preset.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'A description of the preset.',
                            'type' => 'string',
                        ),
                        'Container' => array(
                            'description' => 'The container type for the output file. This value must be mp4.',
                            'type' => 'string',
                        ),
                        'Audio' => array(
                            'description' => 'A section of the response body that provides information about the audio preset values.',
                            'type' => 'object',
                            'properties' => array(
                                'Codec' => array(
                                    'description' => 'The audio codec for the output file. This value must be AAC.',
                                    'type' => 'string',
                                ),
                                'SampleRate' => array(
                                    'description' => 'The sample rate of the audio stream in the output file, in Hertz. Valid values include:',
                                    'type' => 'string',
                                ),
                                'BitRate' => array(
                                    'description' => 'The bit rate of the audio stream in the output file, in kilobits/second. Enter an integer between 64 and 320, inclusive.',
                                    'type' => 'string',
                                ),
                                'Channels' => array(
                                    'description' => 'The number of audio channels in the output file. Valid values include:',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Video' => array(
                            'description' => 'A section of the response body that provides information about the video preset values.',
                            'type' => 'object',
                            'properties' => array(
                                'Codec' => array(
                                    'description' => 'The video codec for the output file. Valid values include H.264 and vp8. You can only specify vp8 when the container type is webm.',
                                    'type' => 'string',
                                ),
                                'CodecOptions' => array(
                                    'description' => 'Profile',
                                    'type' => 'object',
                                    'additionalProperties' => array(
                                        'type' => 'string',
                                    ),
                                ),
                                'KeyframesMaxDist' => array(
                                    'description' => 'The maximum number of frames between key frames. Key frames are fully encoded frames; the frames between key frames are encoded based, in part, on the content of the key frames. The value is an integer formatted as a string; valid values are between 1 and 100000, inclusive. A higher value results in higher compression but may also discernibly decrease video quality.',
                                    'type' => 'string',
                                ),
                                'FixedGOP' => array(
                                    'description' => 'Whether to use a fixed value for FixedGOP. Valid values are true and false:',
                                    'type' => 'string',
                                ),
                                'BitRate' => array(
                                    'description' => 'The bit rate of the video stream in the output file, in kilobits/second. Valid values depend on the values of Level and Profile. If you specify auto, Elastic Transcoder uses the detected bit rate of the input source. If you specify a value other than auto, we recommend that you specify a value less than or equal to the maximum H.264-compliant value listed for your level and profile:',
                                    'type' => 'string',
                                ),
                                'FrameRate' => array(
                                    'description' => 'The frames per second for the video stream in the output file. Valid values include:',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'To better control resolution and aspect ratio of output videos, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, PaddingPolicy, and DisplayAspectRatio instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'MaxWidth' => array(
                                    'description' => 'The maximum width of the output video in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 128 and 4096.',
                                    'type' => 'string',
                                ),
                                'MaxHeight' => array(
                                    'description' => 'The maximum height of the output video in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 96 and 3072.',
                                    'type' => 'string',
                                ),
                                'DisplayAspectRatio' => array(
                                    'description' => 'The value that Elastic Transcoder adds to the metadata in the output file.',
                                    'type' => 'string',
                                ),
                                'SizingPolicy' => array(
                                    'description' => 'Specify one of the following values to control scaling of the output video:',
                                    'type' => 'string',
                                ),
                                'PaddingPolicy' => array(
                                    'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of the output video to make the total size of the output video match the values that you specified for MaxWidth and MaxHeight.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Thumbnails' => array(
                            'description' => 'A section of the response body that provides information about the thumbnail preset values, if any.',
                            'type' => 'object',
                            'properties' => array(
                                'Format' => array(
                                    'description' => 'The format of thumbnails, if any. Valid values are jpg and png.',
                                    'type' => 'string',
                                ),
                                'Interval' => array(
                                    'description' => 'The number of seconds between thumbnails. Specify an integer value.',
                                    'type' => 'string',
                                ),
                                'Resolution' => array(
                                    'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'AspectRatio' => array(
                                    'description' => 'To better control resolution and aspect ratio of thumbnails, we recommend that you use the values MaxWidth, MaxHeight, SizingPolicy, and PaddingPolicy instead of Resolution and AspectRatio. The two groups of settings are mutually exclusive. Do not use them together.',
                                    'type' => 'string',
                                ),
                                'MaxWidth' => array(
                                    'description' => 'The maximum width of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1920 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 4096.',
                                    'type' => 'string',
                                ),
                                'MaxHeight' => array(
                                    'description' => 'The maximum height of thumbnails in pixels. If you specify auto, Elastic Transcoder uses 1080 (Full HD) as the default value. If you specify a numeric value, enter an even integer between 32 and 3072.',
                                    'type' => 'string',
                                ),
                                'SizingPolicy' => array(
                                    'description' => 'Specify one of the following values to control scaling of thumbnails:',
                                    'type' => 'string',
                                ),
                                'PaddingPolicy' => array(
                                    'description' => 'When you set PaddingPolicy to Pad, Elastic Transcoder may add black bars to the top and bottom and/or left and right sides of thumbnails to make the total size of the thumbnails match the values that you specified for thumbnail MaxWidth and MaxHeight settings.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Type' => array(
                            'description' => 'Whether the preset is a default preset provided by Elastic Transcoder (System) or a preset that you have defined (Custom).',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'TestRoleResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Success' => array(
                    'description' => 'If the operation is successful, this value is true; otherwise, the value is false.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Messages' => array(
                    'description' => 'If the Success element contains false, this value is an array of one or more error messages that were generated during the test process.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'UpdatePipelineResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipeline' => array(
                    'description' => 'The pipeline (queue) that is used to manage jobs.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current status of the pipeline:',
                            'type' => 'string',
                        ),
                        'InputBucket' => array(
                            'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                            'type' => 'string',
                        ),
                        'OutputBucket' => array(
                            'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                            'type' => 'string',
                        ),
                        'Role' => array(
                            'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                            'type' => 'string',
                        ),
                        'Notifications' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                            'type' => 'object',
                            'properties' => array(
                                'Progressing' => array(
                                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                    'type' => 'string',
                                ),
                                'Completed' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                    'type' => 'string',
                                ),
                                'Warning' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                    'type' => 'string',
                                ),
                                'Error' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ContentConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ThumbnailConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
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
        'UpdatePipelineNotificationsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipeline' => array(
                    'description' => 'A section of the response body that provides information about the pipeline.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current status of the pipeline:',
                            'type' => 'string',
                        ),
                        'InputBucket' => array(
                            'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                            'type' => 'string',
                        ),
                        'OutputBucket' => array(
                            'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                            'type' => 'string',
                        ),
                        'Role' => array(
                            'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                            'type' => 'string',
                        ),
                        'Notifications' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                            'type' => 'object',
                            'properties' => array(
                                'Progressing' => array(
                                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                    'type' => 'string',
                                ),
                                'Completed' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                    'type' => 'string',
                                ),
                                'Warning' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                    'type' => 'string',
                                ),
                                'Error' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ContentConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ThumbnailConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
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
        'UpdatePipelineStatusResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Pipeline' => array(
                    'description' => 'A section of the response body that provides information about the pipeline.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The identifier for the pipeline. You use this value to identify the pipeline in which you want to perform a variety of operations, such as creating a job or a preset.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the pipeline. We recommend that the name be unique within the AWS account, but uniqueness is not enforced.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current status of the pipeline:',
                            'type' => 'string',
                        ),
                        'InputBucket' => array(
                            'description' => 'The Amazon S3 bucket from which Elastic Transcoder gets media files for transcoding.',
                            'type' => 'string',
                        ),
                        'OutputBucket' => array(
                            'description' => 'The Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files, thumbnails, and playlists. Either you specify this value, or you specify both ContentConfig and ThumbnailConfig.',
                            'type' => 'string',
                        ),
                        'Role' => array(
                            'description' => 'The IAM Amazon Resource Name (ARN) for the role that Elastic Transcoder uses to transcode jobs for this pipeline.',
                            'type' => 'string',
                        ),
                        'Notifications' => array(
                            'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify to report job status.',
                            'type' => 'object',
                            'properties' => array(
                                'Progressing' => array(
                                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic that you want to notify when Elastic Transcoder has started to process the job.',
                                    'type' => 'string',
                                ),
                                'Completed' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder has finished processing the job.',
                                    'type' => 'string',
                                ),
                                'Warning' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters a warning condition.',
                                    'type' => 'string',
                                ),
                                'Error' => array(
                                    'description' => 'The Amazon SNS topic that you want to notify when Elastic Transcoder encounters an error condition.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ContentConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save transcoded files and playlists. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ThumbnailConfig' => array(
                            'description' => 'Information about the Amazon S3 bucket in which you want Elastic Transcoder to save thumbnail files. Either you specify both ContentConfig and ThumbnailConfig, or you specify OutputBucket.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'type' => 'string',
                                ),
                                'StorageClass' => array(
                                    'type' => 'string',
                                ),
                                'Permissions' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Permission',
                                        'type' => 'object',
                                        'properties' => array(
                                            'GranteeType' => array(
                                                'type' => 'string',
                                            ),
                                            'Grantee' => array(
                                                'type' => 'string',
                                            ),
                                            'Access' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AccessControl',
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
    'iterators' => array(
        'operations' => array(
            'ListJobsByPipeline' => array(
                'token_param' => 'PageToken',
                'token_key' => 'NextPageToken',
                'result_key' => 'Jobs',
            ),
            'ListJobsByStatus' => array(
                'token_param' => 'PageToken',
                'token_key' => 'NextPageToken',
                'result_key' => 'Jobs',
            ),
            'ListPipelines' => array(
                'result_key' => 'Pipelines',
            ),
            'ListPresets' => array(
                'result_key' => 'Presets',
            ),
        ),
    ),
);
