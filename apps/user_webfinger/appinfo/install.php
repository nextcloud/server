<?php
$hostMetaHeader = array(
	'Access-Control-Allow-Origin' => '*',
	'Content-Type' => 'application/xml+xrd'
);
$appInfoDir = __DIR__;
$thisAppDir = dirname($appInfoDir);
$appsDir = dirname($thisAppDir);
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
$lrddTmpl .= '://' . $serverName . $webRoot . '/apps/user_webfinger/webfinger.php?q={uri}';
$hostMetaPath = $docRoot . '/.well-known/host-meta';
$hostMetaDir = $docRoot . '/.well-known';
$hostMetaContents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<XRD xmlns=\"http://docs.oasis-open.org/ns/xri/xrd-1.0\" xmlns:hm=\"http://host-meta.net/xrd/1.0\">
    <hm:Host xmlns=\"http://host-meta.net/xrd/1.0\">" . $serverName . "</hm:Host>
    <Link rel=\"lrdd\" template=\"" . $lrddTmpl . "\">
        <Title>Resource Descriptor</Title>
    </Link>
</XRD>";
@mkdir($hostMetaDir);
$hostMeta = fopen($hostMetaPath, 'w');
if(!$hostMeta) {
    die("Could not open " . $hostMetaPath . " for writing, please check permissions!");
}
if(!fwrite($hostMeta, $hostMetaContents, strlen($hostMetaContents))) {
    die("Could not write to " . $hostMetaPath . ", please check permissions!");
}
fclose($hostMeta);

// write custom headers into .htaccess:
$htaccess = fopen($hostMetaDir . '/.htaccess', 'w');
//TODO: check compatibility!
fwrite($htaccess, "<filesMatch \"^host-meta$\">
<ifModule mod_headers.c>\n");
foreach($hostMetaHeader as $header => $value) {
	fwrite($htaccess, "Header set " . $header . " \"" . $value . "\"\n");
}
fwrite($htaccess, "</ifModule>\n</filesMatch>");
fclose($htaccess);
