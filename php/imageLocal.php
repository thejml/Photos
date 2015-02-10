<?php
require("imageBase.php");
#require("includes/exifReader.inc");
#require("vendor/autoload.php");

class ImageLocal extends ImageBase {
	protected $imageBasePath = "";

	function setImagePath($path) { $this->imageBasePath=trim($path,'/ '); }
	function save() { 
		$this->setImagePath($this->config['imageBasePath']);
		// This is going to save the file locally in a hash based directory
		// XXX this needs to deal with other extensions, which should be fun as they don't have EXIF...
		$fileExt="jpg";
		$shaPath=$this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2)."/".$this->fileEXIF['sha'].'.'.$fileExt;
		echo $shaPath;
		if (!is_dir($this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2))) {
			mkdir($this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2));
		}
		rename($this->fileName,$shaPath);
		// This is where we'll push EXIF data to ElasticSearch. We'll potentially want to clean up the EXIF first.
		$this->updateES();
	}
}

