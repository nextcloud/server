<?php
$hostMetaHeader = array(
	'Access-Control-Allow-Origin' => '*',
	'Content-Type' => 'application/xml+xrd'
);
$ownCloudDir = dirname($appsDir);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
try {
    $webRoot = substr(realpath($ownCloudDir), strlen(realpath($docRoot)));
} catch(Exception $e) {
    // some servers fail on realpath(), let's try it the unsecure way:
    $webRoot = substr($ownCloudDir, strlen($docRoot));
}
$serverName = $_SERVER['SERVER_NAME'];
$lrddTmpl = 'http';
if(isset($_SERVER['HTTPS'])) {
    $lrddTmpl .= 's';
}
$lrddTmpl .= '://' . $serverName . $webRoot . '/public.php?service=webfinger&q={uri}';
$hostMetaPath = $docRoot . '/.well-known/host-meta';
$hostMetaDir = $docRoot . '/.well-known';
$hostMetaContents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<XRD xmlns=\"http://docs.oasis-open.org/ns/xri/xrd-1.0\" xmlns:hm=\"http://host-meta.net/xrd/1.0\">
    <hm:Host xmlns=\"http://host-meta.net/xrd/1.0\">" . $serverName . "</hm:Host>
    <Link rel=\"lrdd\" template=\"" . $lrddTmpl . "\">
        <Title>Resource Descriptor</Title>
    </Link>
</XRD>";
foreach($hostMetaHeader as $header => $value) {
	header($header . ": " . $value);
}
echo $hostMetaContents;
