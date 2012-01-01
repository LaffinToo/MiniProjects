<?php
  // header("Content-Type: text/plain");
  $sdb=sqlite_open('wih.sdb');
  if(isset($_GET['cast']) && isset($_GET['id']))
  {
     $cast=array_search($_GET['cast'],array(0,1));
     $id=preg_match('@^[a-f0-9]{14}\.[0-9]{8}$@',$_GET['id'])?$_GET['id']:FALSE;
     if($cast!==FALSE && $id!==FALSE)
     {
       $res=sqlite_query($sdb,"SELECT * FROM casts WHERE id='{$id}'");
       $bout=sqlite_fetch_array($res,SQLITE_NUM);
       $opps = array($bout[2],$bout[3]);
       foreach($opps as $idx=>$opp)
       {
         $field=($cast==$idx)?"wins":"Loss";
         sqlite_exec($sdb,"UPDATE wih SET {$field}={$field}+1 WHERE id=$opp");
       }
     }
  }
  sqlite_exec($sdb,"DELETE FROM casts WHERE dt < datetime('now','-10 minutes')");
  $res=sqlite_query($sdb,"SELECT id,filename FROM wih ORDER BY RANDOM() LIMIT 2");
  $opps=sqlite_fetch_all($res,SQLITE_NUM);
  do {
    $cid=uniqid(null,true);
    $ok=sqlite_exec($sdb,'INSERT INTO casts (id,dt,p1,p2) VALUES (\''. $cid ."',datetime('now'),{$opps[0][0]},{$opps[1][0]})");
  } while(!$ok);
  echo '<table><tr>';
  $cast=0;
  foreach($opps as $row)
  {
    echo <<<EOF
<td>
  <img src="image.php?img={$row[1]}" width="200" height="150">
  <form method="get">
    <input type="hidden" name="cast" value="{$cast}">
    <input type="hidden" name="id" value="{$cid}">
    <input type="submit" value="Submit">
  </form>
</td>
EOF;
    $cast++;
  }
  echo '</tr></table>'
?>
