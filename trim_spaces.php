<?php
echo "hello please edit this script and then run it";die();
ob_start();


$count = 0;

$files = scandir('files/');
foreach($files as $file) {
  //do your work here
//  if($count == 5) {echo 'end of file';die; }
  $count++;
  if($file == '.' || $file == '..') {
  continue;
  }
  else {
  //	echo '<hr>'.$file;
	$file_content = trim(file_get_contents('files/'.$file));
	file_put_contents('files/'.$file, $file_content);
  }
}
echo $count.' items done successfully';die;
