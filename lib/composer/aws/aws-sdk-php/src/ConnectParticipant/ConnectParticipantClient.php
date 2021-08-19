<?php
namespace Aws\ConnectParticipant;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Connect Participant Service** service.
 * @method \Aws\Result completeAttachmentUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise completeAttachmentUploadAsync(array $args = [])
 * @method \Aws\Result createParticipantConnection(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createParticipantConnectionAsync(array $args = [])
 * @method \Aws\Result disconnectParticipant(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disconnectParticipantAsync(array $args = [])
 * @method \Aws\Result getAttachment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAttachmentAsync(array $args = [])
 * @method \Aws\Result getTranscript(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTranscriptAsync(array $args = [])
 * @method \Aws\Result sendEvent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise sendEventAsync(array $args = [])
 * @method \Aws\Result sendMessage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise sendMessageAsync(array $args = [])
 * @method \Aws\Result startAttachmentUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startAttachmentUploadAsync(array $args = [])
 */
class ConnectParticipantClient extends AwsClient {}
