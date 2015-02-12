<?php
/* To use this script with an apache vhost, you'll need the following:
  <VirtualHost *:80>
	ServerName imgs.thejml.info
	DocumentRoot /var/www/photos/imgs/

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
	$requestedFile	=	$_SERVER['SCRIPT_NAME'];
	if (preg_match('/^\/([a-fA-F0-9]+)\/([a-fA-F0-9]+)([0-9]{4})([0-9]{4}).([a-zA-Z]+)/',
		$requestedFile,$matches) ) {
//		echo "File Requested Data:";
//		print_r($matches);
		$dir 	= $matches[1];
		$name	= $matches[2];
		$width	= intval($matches[3]);
		$height = intval($matches[4]);
		$ext	= $matches[5];
		echo "Going to rescale ".$name.".".$ext." to be ".$width."px wide and ".$height."px high\n";
	} else {
		header("HTTP/1.0 400 Bad Request"); 
		echo "Sorry, I don't understand your request for ".$requestedFile;
	}
//	echo "Don't have that image, But you asked for:\n\tWidth:".$width."\n\t".$Height."\n";

?>
