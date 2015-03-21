<?php
/* To use this script with an apache vhost, you'll need the following:
  <VirtualHost *:80>
	ServerName imgs.thejml.info
	DocumentRoot /var/www/photos/imgs/resized

	<Directory /var/www/photos/>
		AllowOverride None
		Order allow,deny
		allow from all
	</Directory>

	RewriteEngine On
	RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-f
	RewriteRule ^.*$ /var/www/photos/php/resize.php [NC,L]

	LogLevel warn
	ErrorLog ${APACHE_LOG_DIR}/imgs_error.log
	CustomLog ${APACHE_LOG_DIR}/imgs_access.log combined
  </VirtualHost>

  This will allow apache to serve the file directly if it has it, or resize the file and let php serve it (after saving it). Thus you will only have to create a thumbnail once per storage backend.
*/

/* Filename: $name
 * Extention: $ext
 * Width: $width
 * Height: $height
 * Source Directory: $sourceDir
 * Requested File (with resize options): $out
 */
function generateImage($name, $ext, $sourceDir, $targetDir, $out, $width, $height, $rotation=0) {
//	echo "Going to rescale ".$name.".".$ext." to be ".$width."px wide and ".$height."px high, and save it to ".$targetDir.$out." using ".$sourceDir.$name.".".$ext." as the source.\n";
	$quality=85;
	$targetFile=$targetDir.$out;
	$targetDir=substr($targetDir.$out,0,strrpos($targetDir.$out,"/"));
	switch (strtolower($ext)) {
		case 'jpg':
		case 'jpeg':
			$image=imagecreatefromjpeg($sourceDir.$name.'.'.$ext);
	}

	if ($rotation!=0) { $image=imagerotate($image,$rotation,0); }

	$actualHeight	= imagesy($image);
	$actualWidth	= imagesx($image);

        if($actualHeight>$height||$actualWidth>$width) {
        	if ($width  > $actualWidth)  { $width	= $actualWidth;  }
        	if ($height > $actualHeight) { $height	= $actualHeight; }

		//get the percentage for the difference in size
		$percentage1 = $width  / $actualWidth;
		$percentage2 = $height / $actualHeight;

		if ($percentage1 < $percentage2) {
			$percentage=$percentage1;	
		} else {
			$percentage=$percentage2;
		}

		//apply the percentage and round the result
		$newWidth	= round(($actualWidth  * $percentage));
		$newHeight	= round(($actualHeight * $percentage));

		//double check and make sure we are not bigger than our max
		if (($newWidth > $width)   && ($width!=0)) {    $newWidth=$width;    }
		if (($newHeight > $height) && ($height!=0)) {   $newHeight=$height;  }

		//make a blank image of the correct size
		$resizedimage=imagecreatetruecolor($newWidth,$newHeight);
		//copy the old image to the smaller one, and resize it.

		if (!imagecopyresampled($resizedimage,$image,0,0,0,0,$newWidth,$newHeight,$actualWidth,$actualHeight)) {
			$echo("error resizing image.");
		} else {
			$image=$resizedimage;
		}

		if(stristr($ext,"jp")!==false){
			if (!is_dir($targetDir)) { mkdir($targetDir,0777,true); }
			if(!imagejpeg($image,$targetFile,$quality)==false) {
				header('Content-type: image/jpeg');
				echo file_get_contents($targetFile);
			} else {
				header("HTTP/1.0 400 Bad Request"); 
			}
		}
	} // Should we do something if they request a size larger than the original?
}


	$requestedFile	=	$_SERVER['SCRIPT_NAME'];
	if (preg_match('/^\/([a-fA-F0-9]+)\/([a-fA-F0-9]+)([0-9]{4})([0-9]{4})([0-9]{3}).([a-zA-Z]+)/', $requestedFile,$matches) ) {
		$dir 	= $matches[1];
		$name	= $matches[2];
		$width	= intval($matches[3]);
		$height = intval($matches[4]);
		$rotate	= intval($matches[5]);
		$ext	= $matches[6];

		$root	= $_SERVER['DOCUMENT_ROOT'];
		$originalPathEX = explode('/',$root);
		unset($originalPathEX[count($originalPathEX)-2]);
		$originalPath = implode('/',$originalPathEX);
		generateImage($name,$ext,$originalPath.$dir.'/',$root,trim($requestedFile,'/'),$width,$height,$rotate);
	} else {
		header("HTTP/1.0 400 Bad Request"); 
		echo "Sorry, I don't understand your request for ".$requestedFile;
	}
?>
