<?php

class ImageBase {
	$fileData = array(); //Contains filesize, SHA, etc.
	$fileEXIF = array(); //Contains EXIF data

	function __construct ($file) {
		# Load in metadata about a file
		$this->fileEXIF = $this->loadEXIF($file);
		$this->fileData = $this->loadData($file);	
	}

	function save() {
		# Save file to disk (or wherever) using it's SHA.
	}

	function load() {
		# Load data from the file for operations
	}
}	

class ImageLocal extends ImageBase {
	function save() { 
		# This is going to save the file locally in a hash based directory
	}
}

class ImageS3 extends ImageBase { 
	function save() {
		# This will allow us to save images to S3 buckets
		# We will need to pull in all the related S3 interfacing to accomplish it.
	}
}
