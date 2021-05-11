<?php
namespace TymFrontiers;
use \TymFrontiers\HTTP\Header;
require_once ".appinit.php";
require_once APP_BASE_INC;

$gen = new Generic;
$params = $gen->requestParam(
  [
    "fname" =>["fname","text",3,256],
    "fid" =>["fid","int"],
    "dl" =>["dl","boolean"],
    "getsize" =>["getsize","text",3,0]
  ],
  "get",[]);
if( empty($params['fname']) && empty($params['fid']) ){
  Header::badRequest();
}
$query = "SELECT * FROM :db:.`:tbl:` ";
if (!empty($params['fname'])) $query .= " WHERE _name='{$database->escapeValue($params['fname'])}' ";
if (!empty($params['fid'])) $query .= " WHERE id={$params['fid']} ";
$query .= " LIMIT 1";
$file = File::findBySql($query);
if( !$file ) Header::notFound();
$file = $file[0];
if (!empty($params['getsize']) && $file->groupName() == 'image') {
  list($width, $height) = \explode('x',\strtolower($params['getsize']));
  $width = (int)$width;
  $width = $width >= 16 ? $width : false;
  $height = (int)$height;
  $height = $height >= 16 ? $height : false;
  if ($width) {
    $rez = new \Gumlet\ImageResize($file->fullPath());
    if ($height) {
      $rez->resize($width,$height);
    } else {
      $rez->resizeToWidth($width);
    }
    $rez->output();
    exit;
  }
}
// record download
$ext = \pathinfo($file->fullPath(),PATHINFO_EXTENSION);
// var_dump($file);
if( !\file_exists($file->fullPath()) ) Header::notFound(false);
\header("Content-Type: {$file->type()}");
\header("Content-Length: " . \filesize($file->fullPath()));
if( !empty($_GET['dl']) &&  (bool)$_GET['dl'] ){
  \header('Content-Disposition: attachment;filename="'.$file->nice_name.".".$ext.'"');
}
\readfile($file->fullPath());
exit;
