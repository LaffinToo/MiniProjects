<?php
  $ipath='D:\MyPictures\\';
  $img=isset($_GET['img'])?(basename($_GET['img'])):'';
  $info=pathinfo($img);
  if(empty($img) || !file_exists($ipath.$img) || $info['extension']!='jpg')
  {
    header("HTTP/1.0 404 Not Found");
    die();
  }
  header('Content-Type: image/jpg');
  readfile($ipath.$img)
?>
