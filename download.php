<?php

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $split = explode('/', $dir);
        $last = $split[count($split)-1];
        if (substr($last, 0, 2) == '__') continue;
        $files = array_merge(
            [],
            ...[$files, rglob($dir . "/" . basename($pattern), $flags)]
        );
    }
    foreach ($files as $i => $f) {
        $base = basename($f);
        if (substr($base, 0, 2) == '__' || substr($base, -4, 4) == '.mp4' || $base == 'download.zip'  || $base == 'settings.php'  || $base == 'composer.lock') {
            unset($files[$i]);
        }
    }
    return $files;
}

$zip = new ZipArchive;
$zipfile = '/tmp/zilverenpeloton'.date('Ymdhi').'.zip';
if ($zip->open($zipfile, ZipArchive::CREATE|ZipArchive::OVERWRITE) === TRUE) {
    $files = rglob('*', GLOB_MARK); 
    // $files = [];
    // echo '<pre>';
    foreach ($files as $f) {
        if (substr($f, -1, 1) != '/'
            && substr($f, -4, 4) != '.mp4'
            && $f != './settings.php'
            && $f != './composer.lock'
        ) {
          if (substr($f, 0, 2) == './') {
       //     echo 'adding in dir '.$f;
            $g = substr($f, 1);
       //     echo ' >> ' . $g . "\n";
          }
          else {
       //     echo 'adding ' . $f . "\n";
            $g = $f;
          }
          $rst = $zip->addFile($f, $g, 0, 0, ZipArchive::FL_OVERWRITE);
       //   echo $rst ? "   OK\n" : "   problem\n";
        }
       // else {
       //   echo 'skip ' . $f . "\n";
       // }
    }
    $settings = file_get_contents('./settings.php');
    $settings = preg_replace("/= '([^\.']*)/", "= 'secret", $settings);
    // $settings = preg_replace("/='([^']*)'/", "= 'secret'", $settings);
    $zip->addFromString('./settings.php', $settings, ZipArchive::FL_OVERWRITE);
    $zip->close();

    //print_r($files);
    //echo '</pre>';
    //exit;

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipfile).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipfile));
    readfile($zipfile);

} else {
    echo 'failed';
}
