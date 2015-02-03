<?php

require("includes/exifReader.inc");
require("vendor/autoload.php");

class ImageBase {
	protected $fileEXIF = array(); //Contains EXIF data
	protected $fileName = "";
	protected $config = array();
	
	protected $elasticsearchParams=array();
	protected $esClient = null;

	function __construct ($file) {
		$this->fileName = $file;
		// Pull in the configuration info
		$this->config = json_decode(file_get_contents("config.php"),TRUE);
		// Connect to ElasticSearch
 		$this->elasticsearchParams = array('hosts'=>array($this->config['elasticSearchHostname'].":".$this->config['elasticSearchPort']));
		$this->esClient = new Elasticsearch\Client($this->elasticsearchParams);
		// Load in metadata about a file
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
		// The built in Thumbnail is too small for our use, so let's get rid of it. In fact, let's clear up some other things...
		$cleanUp=array('Thumbnail','ThumbnailSize','flashpixVersion','subSectionTimeOriginal','subSectionTimeDigtized','FileName');
		foreach ($cleanUp as $clean) { 
			unset($this->fileEXIF[$clean]);
		}
		$this->fileEXIF['sha']=$sha1;
	} 

	function dumpEXIF($json=TRUE) {
		return ($json?json_encode($this->fileEXIF):$this->fileEXIF);
	}

	function save() {
		// Save file to disk (or wherever) using it's SHA.
	}

	function load() {
		// Load data from the file for operations
	}

	/**
	 * Update ElasticSearch with the EXIF and sha info from the file.
  	 */
	function updateES() {
		$params = array();
    		$params['body']  = $this->fileEXIF;   
		$params['index'] = 'photos';
    		$params['type']  = 'name';
    		$params['id']    = $this->fileEXIF['sha'];
    		$ret = $this->esClient->index($params);
	}
}	

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

class ImageS3 extends ImageBase { 
	protected $imageBucket = "";
	protected $awsCreds = "";

	function setImageBucket($bucket) { $this->imageBucket=$bucket; }
	function setAWSCredentials($creds) { $this->awsCreds=$creds; }

	function save() {
		// This will allow us to save images to S3 buckets
		// We will need to pull in all the related S3 interfacing to accomplish it.

		// This is where we'll push EXIF data to ElasticSearch. We'll potentially want to clean up the EXIF first.
		$this->updateES();
	}
}
