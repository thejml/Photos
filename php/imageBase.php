<?php

require("includes/exifReader.inc");
require("vendor/autoload.php");

class ImageBase {
	protected $fileEXIF 	= array(); //Contains EXIF data
	protected $fileName 	= "";
	protected $config 	= array();
	protected $tags		= array();	

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
		$this->fileEXIF['lastUpdate']=ceil(microtime(true)*1000);
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

	function addTags($tags) { 
		foreach ($tags as $tag=>$value) {
			$this->tags[$value]=1;
		}
	}

	function delTags($tags) {
		foreach ($tags as $tag=>$value) { 
			unset($this->tags[$value]);
		}
	}

	function renderTags() {
		$output=array();
		foreach($this->tags as $tag=>$value) { 
			$output[]=$tag;
		}
		return $output;
	}

	/**
	 * Update ElasticSearch with the EXIF and sha info from the file.
  	 */
	function updateES() {
		$params 	 = array();
    		$params['body']  = $this->fileEXIF;   
		$params['body']['tags'] = $this->renderTags();
		$params['index'] = 'photos';
    		$params['type']  = 'name';
    		$params['id']    = $this->fileEXIF['sha'];
    		$ret 		 = $this->esClient->index($params);
	}
}	

