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

	function fractionToDecimal($str) { 
		$dex=explode("/",$str); 
		return ($dex[0]/$dex[1]);
	}

	/**
	* Create an object, then process the file. We don't want the thumbnail (it's tiny), save the rest.
	*/
	function loadEXIF() {
		$sha1 = sha1_file($this->fileName);
//		$er = new phpExifReader($this->fileName);
//		$er->processFile();
//		$this->fileEXIF=$er->getImageInfo();
		$EXIF=exif_read_data($this->fileName);
		$desiredEXIFAttributes=array("FileName","FileDateTime","FileSize","MimeType","Make","Model","Orientation","XResolution","YResolution","ResolutionUnit","Software","DateTime","ExposureTime","FNumber","ExposureProgram","ISOSpeedRatings","DateTimeOriginal","DateTimeDigitized","ShutterSpeedValue","ApertureValue","BrightnessValue","MeteringMode","Flash","FocalLength","ColorSpace","ExifImageWidth","ExifImageLength","ExposureMode","WhiteBalance","GPSLatitudeRef","GPSLatitude","GPSLongitudeRef","GPSLongitude","GPSAltitudeRef","GPSAltitude","GPSTimeStamp","GPSImgDirectionRef","GPSImgDirection");
		$divisibleEXIFAttributes=array("XResolution","YResolution","ExposureTime","FNumber","ShutterSpeedValue","ApertureValue","BrightnessValue","FocalLength","GPSAltitude","GPSImgDirection");
		$geoDataEXIFAttributes=array("GPSLatitude"=>"lat","GPSLongitude"=>"lon"); //,"GPSTimeStamp"=>"GPSTime");

		// We only care about what's listed in the $desiredEXIFAttributes array. Just copy those over.
		foreach ($desiredEXIFAttributes as $desired) { 
			if (isset($EXIF[$desired])) { 
				$this->fileEXIF[$desired]=$EXIF[$desired]; 
			}
		}

		// There are a number of things returned as functions, let's make them decimals for ease of use
		foreach ($divisibleEXIFAttributes as $div) { 
			if (isset($this->fileEXIF[$div])) { 
				$this->fileEXIF[$div]=$this->fractionToDecimal($this->fileEXIF[$div]);
			}
		}

		$this->fileEXIF['Model']	= str_replace(" ","_",$this->fileEXIF['Model']);
		$this->fileEXIF['Make']		= str_replace(" ","_",$this->fileEXIF['Make']);
		$this->fileEXIF['DateTime']	= date("Y-m-d\TH:i:s.000\Z",strtotime($this->fileEXIF['DateTime']));
 
		// For/if GeoData, let's convert the 3 part return array to a decimal number
		foreach ($geoDataEXIFAttributes as $gda => $name) { 
			if (isset($this->fileEXIF[$gda])) { 
				$this->fileEXIF[$gda][0]=$this->fractionToDecimal($this->fileEXIF[$gda][0]);
				$this->fileEXIF[$gda][1]=$this->fractionToDecimal($this->fileEXIF[$gda][1]);
				$this->fileEXIF[$gda][2]=$this->fractionToDecimal($this->fileEXIF[$gda][2]);
				$this->fileEXIF['location'][$name]=$this->fileEXIF[$gda][0]+($this->fileEXIF[$gda][1]/60)+($this->fileEXIF[$gda][2]/3600);
			}
		}

		if (isset($this->fileEXIF["location"])) {
			// North is Plus, South is Minus.
			if (isset($this->fileEXIF["GPSLatitudeRef"]) && ($this->fileEXIF["GPSLatitudeRef"]=='N')) { 
				$this->fileEXIF["location"]['lat']=abs($this->fileEXIF["location"]['lat']);
			} else { $this->fileEXIF["location"]['lat']=-1*abs($this->fileEXIF["location"]['lat']); }

		// East is Plus, West is Minus
			if (isset($this->fileEXIF["GPSLongitudeRef"]) && ($this->fileEXIF["GPSLongitudeRef"]=='E')) { 
				$this->fileEXIF["location"]['lon']=abs($this->fileEXIF["location"]['lon']);
			} else { $this->fileEXIF["location"]['lon']=-1*abs($this->fileEXIF["location"]['lon']); }
			$this->fileEXIF["location"]=array(round($this->fileEXIF["location"]["lat"],4),round($this->fileEXIF["location"]["lon"],2));
			//Clean up unused indexes
			unset($this->fileEXIF["GPSLatitudeRef"]);
			unset($this->fileEXIF["GPSLatitude"]);
			unset($this->fileEXIF["GPSLongitudeRef"]);
			unset($this->fileEXIF["GPSLongitude"]);
			unset($this->fileEXIF["GPSTimeStamp"]);
		}
		// The built in Thumbnail is too small for our use, so let's get rid of it. In fact, let's clear up some other things...
		//$cleanUp=array('Thumbnail','ThumbnailSize','flashpixVersion','subSectionTimeOriginal','subSectionTimeDigtized','FileName');
		//foreach ($cleanUp as $clean) { 
		//	unset($this->fileEXIF[$clean]);
		//}
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


