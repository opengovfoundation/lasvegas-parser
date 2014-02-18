<?php
$link = mysql_connect('localhost', 'root', 'password');
if (!$link) {
   echo mysql_error();
   ?>
   <META HTTP-EQUIV="REFRESH" CONTENT="3">
   <?
   die();
}
$db_selected = mysql_select_db('edizleco_lasvegas', $link);
if (!$db_selected) {
   die ('Can\'t use crawl: ' . mysql_error());
}
?> 