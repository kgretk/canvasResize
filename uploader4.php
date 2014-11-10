<?php
/* mostly from https://github.com/gokercebeci/canvasResize
 * modifications:
 *
 *
 *
 */

// For performance check
$time_start = microtime(true);

// Result object
$r = new stdClass();
// no-cache (important for mobile safari)
header('cache-control: no-cache');
// Result content type
header('content-type: application/json');

// session id, also path for session folder
$s = trim(rawurldecode(@$_GET['s']));
if (strlen($s)>0) {
    session_id($s);
    session_start();
}
else {
    //$r->error = "No session id...";
	session_start();
	$s = session_id();
}

include('upload-config.php');

// File size control
if ($_FILES['photo']['size'] > ($maxsize * 1048576)) {

    $r->error = "Error: Max file size: $maxsize Kb";
}

// If the file is an image
if (preg_match('/image/i', $_FILES['photo']['type'])) {
    $path1 = $upload_folder.'/'.substr($s,0,2).'/';
    $path  = $upload_folder.'/'.substr($s,0,2).'/'.$s.'/';
    //TODO check if $uplaod_folder writable.. exit with 403 if not
    make_folders($path1, $path, $r);
	
} else {

    $r->error = "Error: Only image files";
}

// Supporting image file types
$types = Array('image/png', 'image/gif', 'image/jpeg');
// File type control
if (in_array($_FILES['photo']['type'], $types)) {
    // Create an unique file name    
	$f = uniqid();
    $filename = $f . '.jpg';
    // Uploaded file source
    $source = file_get_contents($_FILES["photo"]["tmp_name"]);
    // Image resize and save
	imageresize($source, $path . $f . '-800' . '.jpg', 800, 600, false, 80);
	
	
} else {
    // If the file is not an image
    $r->error = "Error: this is not an image file";
    return false;
}

// File path
//$path = str_replace('uploader.php', '', $_SERVER['SCRIPT_NAME']);

// Result data
$r->filename = $filename;
$r->path = $path;
$r->img = '<img src="' . $path . $filename . '" alt="image" />';
$r->session = $s;

// Return to JSON
echo json_encode($r);

// For performance
$time_stop = microtime(true);
$time_dur = $time_stop - $time_start; //seconds

//Log
if ($log_perf) {
	$log = '=== ' . @date('Y-m-d, H:i:s') . ', ' .
	$path . $f . '.jpg, filesize,' . $_FILES["photo"]["size"] .
	', time,'.sprintf("%.3f", $time_dur) .
	', mem,'.memory_get_peak_usage(true) . "\n";
	//$log .= 'FILES:' . print_r($_FILES, 1) . "\n\n";

	$fp = fopen($upload_folder.'/'.$log_filename, 'a');
	fwrite($fp, $log);
	fclose($fp);
}

// Image resize function with php + gd2 lib
function imageresize($source, $destination, $width = 0, $height = 0, $crop = false, $quality = 80) {
    $quality = $quality ? $quality : 80;
    $image = imagecreatefromstring($source);
    if ($image) {
        // Get dimensions
        $w = imagesx($image);
        $h = imagesy($image);
        //die(json_encode(array('width' => $w, 'height' => $h)));
        if (($width && $w > $width) || ($height && $h > $height)) {
            $ratio = $w / $h;
            if (($ratio >= 1 || $height == 0) && $width && !$crop) {
                $new_height = $width / $ratio;
                $new_width = $width;
            } elseif ($crop && $ratio <= ($width / $height)) {
                $new_height = $width / $ratio;
                $new_width = $width;
            } else {
                $new_width = $height * $ratio;
                $new_height = $height;
            }
        } else {
            $new_width = $w;
            $new_height = $h;
        }
        $x_mid = $new_width * .5;  //horizontal middle
        $y_mid = $new_height * .5; //vertical middle
        // Resample
    //    error_log('height: ' . $new_height . ' - width: ' . $new_width);
        $new = imagecreatetruecolor(floor($new_width), floor($new_height));
        $x = 0;
        if ($new_width > $new_height) {
            //$new_height = $new_height *8;
        } else {
            //$x = -$new_width * 7;
            //$new_width = $new_width *8;
        }
        imagecopyresampled($new, $image, 0, 0, $x, 0, $new_width, $new_height, $w, $h);
        // Crop
        if ($crop) {
            $crop = imagecreatetruecolor($width ? $width : $new_width, $height ? $height : $new_height);
            imagecopyresampled($crop, $new, 0, 0, ($x_mid - ($width * .5)), 0, $width, $height, $width, $height);
            //($y_mid - ($height * .5))
        }
        // Output
        // Enable interlancing [for progressive JPEG]
        imageinterlace($crop ? $crop : $new, true);

        $dext = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
        if ($dext == '') {
            $dext = $ext;
            $destination .= '.' . $ext;
        }
        switch ($dext) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($crop ? $crop : $new, $destination, $quality);
                break;
            case 'png':
                $pngQuality = ($quality - 100) / 11.111111;
                $pngQuality = round(abs($pngQuality));
                imagepng($crop ? $crop : $new, $destination, $pngQuality);
                break;
            case 'gif':
                imagegif($crop ? $crop : $new, $destination);
                break;
        }
        @imagedestroy($image);
        @imagedestroy($new);
        @imagedestroy($crop);
    }
}

function make_folders($path1, $path, $r) {
	if (!is_dir($path))
	{
	  if (!is_dir($path1))
	  {
		if (!@mkdir($path1))
			$r->error = "error creating folder";
	  }
	  if (!@mkdir($path))
		$r->error = "error creating folder ";

	  //create folder for thumbnails
	  //if (!@mkdir($path.'th'))
		//$r->error = "error creating folder";

	}
}


?>