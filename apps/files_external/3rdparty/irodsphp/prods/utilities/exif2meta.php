<?php

// reset the connection parameters below
$host = 'rt.sdsc.edu';
$port = 1247;
$user = 'rods';
$pass = 'RODS';


/**
 * This simple script reads JPEG/TIFF files stored in iRODS, extract
 * its EXIF information, and set it as userdefined metadata.
 * Note: EXIF php module is required for this program to work properly
 *       http://us2.php.net/manual/en/ref.exif.php
 *
 * Example: php -f exif2meta.php /tempZone/home/rods/test2/RIMG0087.jpg
 */

//----------don't modify below this line!! -----------

require_once("../src/Prods.inc.php");

$target_file = $argv[1];

try {
    $account = new RODSAccount($host, $port, $user, $pass);
    $irodsfile = new ProdsFile($account, $target_file, true);

    $metas = $irodsfile->getMeta();
    $metaalreadyset = false;
    foreach ($metas as $meta) {
        if ($meta->name == 'EXIF.ExifVersion') {
            $metaalreadyset = true;
            break;
        }
    }

    if ($metaalreadyset === true) {
        $time = '[' . date('c') . ']';
        echo "$time 0: metadata already set for '$target_file'\n";
        exit(0);
    }

    // download file from irods to tmp
    $localfile = '/tmp/' . basename($target_file);
    if (file_exists($localfile))
        unlink($localfile);
    $irodsfile->open("r");
    $str = '';
    while ((($buffer = $irodsfile->read(1024 * 1024)) != NULL) &&
        (connection_status() == 0)) {
        $str = $str . $buffer;
    }
    $irodsfile->close();
    file_put_contents($localfile, $str);

    extactExif($localfile, $irodsfile);

    if (file_exists($localfile))
        unlink($localfile);

    $time = '[' . date('c') . ']';
    echo "$time 0: '$target_file' processed!\n";
    exit(0);

} catch (Exception $e) {

    if (file_exists($localfile))
        unlink($localfile);

    $time = '[' . date('c') . ']';
    echo "$time " . $e->getCode() . ": " . "$e";
    exit(-1);
}


function extactExif($localfile, $remoteRODSfile)
{
    $exif = exif_read_data($localfile, 'EXIF');
    if ($exif === false) return;

    foreach ($exif as $name => $val) {

        // replace ascii char that can't be displayed, which causes problem in irods
        if ((!is_array($val)) && (is_string($val)) &&
            ((ord($val[0]) < 32) || (ord($val[0]) > 126)) &&
            ($name != 'UserComment')
        ) {
            $val = '__undefined__';
        }

        if ($name == 'THUMBNAIL') {
            foreach ($val as $tname => $tval)
                $remoteRODSfile->addMeta(new RODSMeta(
                    'EXIF.THUMBNAIL.' . $tname, $tval, ''));
        } else
            if ($name == 'COMPUTED') {
                foreach ($val as $cname => $cval) {
                    if ($cname == 'html') {
                        //skip html tag, because there is a irods server bug that corrupting string with
                        //double quotes: 'COMPUTED.html: width="3264" height="2448"'
                    } else
                        $remoteRODSfile->addMeta(new RODSMeta(
                            'EXIF.COMPUTED.' . $cname, $cval, ''));
                }
            } else
                if ($name == 'MakerNote') {
                    //skip makernote
                } else
                    if ($name == 'ComponentsConfiguration') {
                        //skip ComponentsConfiguration, because there is a irods server bug that corrupting string with

                    } else
                        if ($name == 'UserComment') {
                            if (($start = strpos($val, 'GCM_TAG')) !== false) {
                                $str = substr($val, $start + strlen('GCM_TAG'));
                                $gcm_tokens = explode(chr(0), $str);
                                $gcm_counter = 0;
                                foreach ($gcm_tokens as $gcm_tag) {
                                    if ((strlen($gcm_tag) > 0) && (preg_match('/^[' . chr(32) . '-' . chr(126) . ']+$/', $gcm_tag))) {
                                        $remoteRODSfile->addMeta(new RODSMeta(
                                            'EXIF.UserComment' . $gcm_counter++, $gcm_tag, ''));
                                    }
                                }
                            } else {
                                if (strlen($val) < 1)
                                    $str = ' ';
                                //replace no displable char
                                $str = preg_replace('/[^' . chr(32) . '-' . chr(126) . ']+/', ' ', $val);
                                $remoteRODSfile->addMeta(new RODSMeta(
                                    'EXIF.UserComment', $str, ''));
                            }
                        } else
                            if (is_array($val)) {
                                foreach ($val as $cname => $cval) {
                                    $remoteRODSfile->addMeta(new RODSMeta(
                                        "EXIF.$name." . $cname, $cval, ''));
                                }
                            } else
                                $remoteRODSfile->addMeta(new RODSMeta(
                                    'EXIF.' . $name, $val, ''));
    }
}

?>
