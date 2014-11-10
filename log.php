<?php
include('upload-config.php');

$i= trim(rawurldecode($_GET['i']));
session_start();

  $log = @date('Y-m-d, H:i:s') . ', '. $i . ', '. session_id() . "\n";

  $fp = fopen($upload_folder.'/'.$log_js, 'a');
  fwrite($fp, $log);
  fclose($fp);
  