<?php

declare(strict_types=1);

namespace OpenStack\ObjectStore\v1;

use OpenStack\Common\Api\AbstractParams;
use Psr\Http\Message\StreamInterface;

class Params extends AbstractParams
{
    public function endMarker(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => <<<EOT
Based on a string value, only containers with names that are less in value than the specified marker will be returned.
"Less in value" refers to the sorting algorithm, which is based on the SQLite memcmp() collating function.'
EOT
        ];
    }

    public function prefix(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => <<<EOT
Based on a string value, only containers with names that begin with this value will be returned. This is useful when
you only want to return a set of containers that match a particular pattern.
EOT
        ];
    }

    public function delimiter(): array
    {
        return [
            'location'    => self::QUERY,
            'description' => <<<EOT
Delimiter value, which returns the object names that are nested in the container.
EOT
        ];
    }

    public function newest(): array
    {
        return [
            'location'    => self::HEADER,
            'type'        => self::BOOL_TYPE,
            'sentAs'      => 'X-Newest',
            'description' => <<<EOT
If set to True, Object Storage queries all replicas to return the most recent one. If you omit this header, Object
Storage responds faster after it finds one valid replica. Because setting this header to True is more expensive for the
back end, use it only when it is absolutely needed.
EOT
        ];
    }

    public function tempUrlKey($type)
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => sprintf('X-%s-Meta-Temp-URL-Key', ucfirst($type)),
            'description' => 'The secret key value for temporary URLs.',
        ];
    }

    public function tempUrlKey2($type)
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => sprintf('X-%s-Meta-Temp-URL-Key-2', ucfirst($type)),
            'description' => <<<EOT
A second secret key value for temporary URLs. The second key enables you to rotate keys by having an old and new key
active at the same time.
EOT
        ];
    }

    public function containerName(): array
    {
        return [
            'location'    => self::URL,
            'required'    => true,
            'description' => <<<EOT
The unique name for the container. The container name must be from 1 to 256 characters long and can start with any
character and contain any pattern. Character set must be UTF-8. The container name cannot contain a slash (/) character
because this character delimits the container and object name. For example, /account/container/object.
EOT
        ];
    }

    public function path(): array
    {
        return [
            'location'    => 'query',
            'description' => <<<EOT
For a string value, returns the object names that are nested in the pseudo path. Equivalent to setting delimiter to /
and prefix to the path with a / at the end.
EOT
        ];
    }

    public function readAccess($type)
    {
        return [
            'location'    => 'header',
            'sentAs'      => sprintf('X-%s-Read', ucfirst($type)),
            'description' => <<<EOT
Sets a container access control list (ACL) that grants read access. Container ACLs are available on any Object Storage
cluster, and are enabled by container rather than by cluster. Specify the ACL value as follows:

".r:*" to allow access for all referrers.

".r:example.com,swift.example.com" to allow access to a comma-separated list of referrers.

".rlistings" to allow container listing.

"AUTH_username" allows access to a specified user who authenticates through a legacy or non-OpenStack-Identity-based
authentication system.

"LDAP_" allows access to all users who authenticate through an LDAP-based legacy or non-OpenStack-Identity-based
authentication system.
EOT
        ];
    }

    public function syncTo(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Container-Sync-To',
            'description' => <<<EOT
Sets the destination for container synchronization. Used with the secret key indicated in the X-Container-Sync-Key
header. If you want to stop a container from synchronizing, send a blank value for the X-Container-Sync-Key header.
EOT
        ];
    }

    public function syncKey(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Container-Sync-Key',
            'description' => <<<EOT
Sets the secret key for container synchronization. If you remove the secret key, synchronization is halted.
EOT
        ];
    }

    public function writeAccess($type)
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => sprintf('X-%s-Write', ucfirst($type)),
            'description' => 'Like `readAccess` parameter, but for write access.',
        ];
    }

    public function metadata($type, $remove = false)
    {
        if (true == $remove) {
            $type = 'Remove-'.ucfirst($type);
        }

        return [
            'location'   => self::HEADER,
            'type'       => self::OBJECT_TYPE,
            'prefix'     => sprintf('X-%s-Meta-', ucfirst($type)),
            'properties' => [
                'type' => self::STRING_TYPE,
            ],
            'description' => <<<EOT
Human-readable key/value pairs that help describe and determine what type of resource it is. You can specify whichever
key you like, but the values need to be in scalar form (since they will be translated to strings).
EOT
        ];
    }

    public function versionsLocation(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Versions-Location',
            'description' => <<<EOT
Enables versioning on this container. The value is the name of another container. You must UTF-8-encode and then
URL-encode the name before you include it in the header. To disable versioning, set the header to an empty string.
EOT
        ];
    }

    public function bytesQuota(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Container-Meta-Quota-Bytes',
            'description' => <<<EOT
Sets maximum size of the container, in bytes. Typically these values are set by an administrator. Returns a 413
response (request entity too large) when an object PUT operation exceeds this quota value.
EOT
        ];
    }

    public function countQuota(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Container-Meta-Quota-Count',
            'description' => <<<EOT
Sets maximum object count of the container. Typically these values are set by an administrator. Returns a 413
response (request entity too large) when an object PUT operation exceeds this quota value.
EOT
        ];
    }

    public function webDirType(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Container-Meta-Web-Directory-Type',
            'description' => <<<EOT
Sets the content-type of directory marker objects. If the header is not set, default is application/directory.
Directory marker objects are 0-byte objects that represent directories to create a simulated hierarchical structure.
For example, if you set "X-Container-Meta-Web-Directory- Type: text/directory", Object Storage treats 0-byte objects
with a content-type of text/directory as directories rather than objects.
EOT
        ];
    }

    public function detectContentType(): array
    {
        return [
            'location'    => self::HEADER,
            'type'        => self::BOOL_TYPE,
            'sentAs'      => 'X-Detect-Content-Type',
            'description' => <<<EOT
If set to true, Object Storage guesses the content type based on the file extension and ignores the value sent in the
Content-Type header, if present.
EOT
        ];
    }

    public function removeVersionsLocation(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Remove-Versions-Location',
            'description' => 'Set to any value to disable versioning.',
        ];
    }

    public function objectName(): array
    {
        return [
            'location'    => self::URL,
            'required'    => true,
            'description' => 'The unique name for the object',
        ];
    }

    public function range(): array
    {
        return [
            'location'    => self::HEADER,
            'description' => <<<EOT
You can use the Range header to get portions of data by using one or more range specifications. To specify many ranges,
separate the range specifications with a comma. The types of range specifications are:

- Byte range specification. Use FIRST_BYTE_OFFSET to specify the start of the data range, and LAST_BYTE_OFFSET to
specify the end. You can omit the LAST_BYTE_OFFSET and if you do, the value defaults to the offset of the last byte of
data.

- Suffix byte range specification . Use LENGTH bytes to specify the length of the data range.

The following forms of the header specify the following ranges of data:

Range: bytes=-5. The last five bytes.

Range: bytes=10-15. The five bytes of data after a 10-byte offset.

Range: bytes=10-15,-5. A multi-part response that contains the last five bytes and the five bytes of data after a
10-byte offset. The Content-Type of the response is then multipart/byteranges.

Range: bytes=4-6. Bytes 4 to 6 inclusive.

Range: bytes=2-2. Byte 2, the third byte of the data.

Range: bytes=6-. Byte 6 and after.

Range: bytes=1-3,2-5. A multi-part response that contains bytes 1 to 3 inclusive, and bytes 2 to 5 inclusive. The
Content-Type of the response is then multipart/byteranges.
EOT
        ];
    }

    public function ifMatch(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'If-Match',
            'description' => <<<EOT
In a nutshell, this provides for conditional requests. The value provided should be a MD5 checksum, and it will be
checked by the server receiving the request. If any existing entity held on the server has the same MD5 checksum (or
ETag), or if "*" is provided as the value, the request will be performed.

Conversely, if none of the entity tags match, or if "*" is given and no current entity exists, the server MUST NOT
perform the requested method, and MUST return a 412 (Precondition Failed) response.

See http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14 for more information.
EOT
        ];
    }

    public function ifNoneMatch(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'If-None-Match',
            'description' => <<<EOT
In a nutshell, this provides for conditional requests. The value provided should be a MD5 checksum, and it will be
checked by the server receiving the request. If any existing entity held on the server has the same MD5 checksum (or
ETag), or if "*" is provided as the value, the request MUST NOT perform the request, and MUST return a 412
(Precondition Failed) response.

Conversely, if none of the entity tags match, or if "*" is given and no current entity exists, the server may
perform the requested method.

See http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14 for more information.
EOT
        ];
    }

    public function ifModifiedSince(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'If-Modified-Since',
            'description' => <<<EOT
The value should be a valid HTTP-date. This value makes the request conditional. If the requested resource HAS NOT
been modified or changed since the specified date, it will not be returned. Instead a 304 (Not Modified) response will
be returned without any message body. If the resource HAS been modified since the specified date, it will be returned
as usual.
EOT
        ];
    }

    public function ifUnmodifiedSince(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'If-Unmodified-Since',
            'description' => <<<EOT
The value should be a valid HTTP-date. This value makes the request conditional. If the requested resource HAS
been modified or changed since the specified date, it will not be returned. Instead a 412 (Precondition Failed)
response will be returned without any message body. If the resource HAS NOT been modified since the specified date, it
will be returned as usual.
EOT
        ];
    }

    public function deleteAfter(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Delete-After',
            'description' => <<<EOT
Specifies the number of seconds after which the object is removed. Internally, the Object Storage system stores this
value in the X-Delete-At metadata item.
EOT
        ];
    }

    public function deleteAt(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Delete-At',
            'description' => 'The certain date, in UNIX Epoch timestamp format, when the object will be removed.',
        ];
    }

    public function contentEncoding(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'Content-Encoding',
            'description' => <<<EOT
The Content-Encoding entity-header field is used as a modifier to the media-type. When present, its value indicates
what additional content codings have been applied to the entity-body, and thus what decoding mechanisms must be applied
in order to obtain the media-type referenced by the Content-Type header field
EOT
        ];
    }

    public function contentDisposition(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'Content-Disposition',
            'description' => <<<EOT
The Content-Disposition response-header field has been proposed as a means for the origin server to suggest a default
filename if the user requests that the content is saved to a file.
EOT
        ];
    }

    public function copyFrom(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Copy-From',
            'description' => <<<EOT
If set, this is the name of an object used to create the new object by copying the X-Copy-From object. The value is in
form {container}/{object}. You must UTF-8-encode and then URL-encode the names of the container and object before you
include them in the header. Using PUT with X-Copy-From has the same effect as using the COPY operation to copy an object.
EOT
        ];
    }

    public function etag(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'ETag',
            'description' => <<<EOT
The MD5 checksum value of the request body. For example, the MD5 checksum value of the object content. You are strongly
recommended to compute the MD5 checksum value of object content and include it in the request. This enables the Object
Storage API to check the integrity of the upload. The value is not quoted.
EOT
        ];
    }

    public function contentType(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'Content-Type',
            'description' => 'The Content-Type entity-header field indicates the media type of the entity-body',
        ];
    }

    public function destination(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'Destination',
            'description' => <<<EOT
The container and object name of the destination object in the form of /container/object. You must UTF-8-encode and
then URL-encode the names of the destination container and object before you include them in this header.
EOT
        ];
    }

    public function freshMetadata(): array
    {
        return [
            'location'    => self::HEADER,
            'type'        => self::BOOL_TYPE,
            'description' => <<<EOT
Enables object creation that omits existing user metadata. If set to True, the COPY request creates an object without
existing user metadata. Default value is False.
EOT
        ];
    }

    public function content(): array
    {
        return [
            'location'    => self::RAW,
            'type'        => self::STRING_TYPE,
            'description' => 'The content of the object in string form',
        ];
    }

    public function stream(): array
    {
        return [
            'location'    => self::RAW,
            'type'        => StreamInterface::class,
            'description' => 'The content of the object in string form',
        ];
    }

    public function format(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Defines the format of the collection. Will always default to `json`.',
        ];
    }

    public function objectManifest(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Object-Manifest',
            'type'        => self::STRING_TYPE,
            'description' => <<<EOT
The value of this header is {container}/{prefix}, where {container} is the name of the container where the segment
objects are stored, and {prefix} is a string that all segment objects have in common
EOT
        ];
    }
}
