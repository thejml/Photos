<?php

require("includes/exifReader.inc");

class ImageBase {
	protected $fileEXIF = array(); //Contains EXIF data
	protected $fileName = "";
	
	function __construct ($file) {
		$this->fileName = $file;
		# Load in metadata about a file
		$this->loadEXIF();
	}

	/**
	* Create an object, then process the file. We don't want the thumbnail (it's tiny), save the rest.
	*/
	function loadEXIF() {
		$sha1 = sha1_file($this->fileName);
		$er = new phpExifReader($this->fileName);
		$er->processFile();
		$this->fileEXIF=$er->getImageInfo();
		unset($this->fileEXIF['Thumbnail']);
		$this->fileEXIF['sha']=$sha1;
	} 

	function dumpEXIF($json=TRUE) {
		return ($json?json_encode($this->fileEXIF):$this->fileEXIF);
	}

	function save() {
		# Save file to disk (or wherever) using it's SHA.
	}

	function load() {
		# Load data from the file for operations
	}
}	

class ImageLocal extends ImageBase {
	protected $imageBasePath = "";

	function setImagePath($path) { $this->imageBasePath=trim($path,'/ '); }
	function save() { 
		# This is going to save the file locally in a hash based directory
		# XXX this needs to deal with other extensions
		$fileExt="jpg";
		$shaPath=$this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2)."/".$this->fileEXIF['sha'].'.'.$fileExt;
		mkdir($this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2));
		rename($this->fileName,$shaPath);
		# this is where we'll push EXIF data to ElasticSearch. We'll potentially want to clean up the EXIF first.
	}
}

class ImageS3 extends ImageBase { 
	protected $imageBucket = "";
	protected $awsCreds = "";

	function setImageBucket($bucket) { $this->imageBucket=$bucket; }
	function setAWSCredentials($creds) { $this->awsCreds=$creds; }

	function save() {
		# This will allow us to save images to S3 buckets
		# We will need to pull in all the related S3 interfacing to accomplish it.
	}
}
