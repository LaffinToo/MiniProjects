<?php
  // Create Database
  $sdb=sqlite_open('wih.sdb');
  sqlite_exec($sdb,'CREATE TABLE wih ( id INTEGER PRIMARY KEY, filename VARCHAR( 32 ), wins INTEGER, loss INTEGER, rating FLOAT )');
  sqlite_exec($db.'CREATE TABLE casts ( id TEXT UNIQUE NOT NULL, dt DATETIME, p1 INTEGER, p2 INTEGER )');
  $files=glob('D:\MyPictures\*.jpg');
  foreach($files as &$file)
  {
    $file=basename($file);
    sqlite_exec($sdb,"INSERT into wih (filename) VALUES('{$file}')");
  }
?>
