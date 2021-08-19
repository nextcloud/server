<?php
namespace Aws\Api\Serializer;

use Aws\Api\StructureShape;
use Aws\Api\Service;

/**
 * @internal
 */
class RestXmlSerializer extends RestSerializer
{
    /** @var XmlBody */
    private $xmlBody;

    /**
     * @param Service $api      Service API description
     * @param string  $endpoint Endpoint to connect to
     * @param XmlBody $xmlBody  Optional XML formatter to use
     */
    public function __construct(
        Service $api,
        $endpoint,
        XmlBody $xmlBody = null
    ) {
        parent::__construct($api, $endpoint);
        $this->xmlBody = $xmlBody ?: new XmlBody($api);
    }

    protected function payload(StructureShape $member, array $value, array &$opts)
    {
        $opts['headers']['Content-Type'] = 'application/xml';
        $opts['body'] = $this->getXmlBody($member, $value);
    }

    /**
     * @param StructureShape $member
     * @param array $value
     * @return string
     */
    private function getXmlBody(StructureShape $member, array $value)
    {
        $xmlBody = (string)$this->xmlBody->build($member, $value);
        $xmlBody = str_replace("'", "&apos;", $xmlBody);
        $xmlBody = str_replace('\r', "&#13;", $xmlBody);
        $xmlBody = str_replace('\n', "&#10;", $xmlBody);
        return $xmlBody;
    }
}
