<?php

require("imageBase.php");
#require("includes/exifReader.inc");
#require("vendor/autoload.php");

use Aws\Common\Aws;
use Aws\S3\S3Client;
use Aws\S3\StreamWrapper;
use Guzzle\Http\EntityBody;

class ImageS3 extends ImageBase { 

	function save() {
		// This will allow us to save images to S3 buckets
		// We will need to pull in all the related S3 interfacing to accomplish it.

		$bucket=$this->config['awsBucket'];
	
		// Instantiate the S3 client with your AWS credentials
		$client = S3Client::factory(array(
			'key'    => $this->config['awsCreds']['key'],
			'secret' => $this->config['awsCreds']['secret'],
		));
	
		$result = $client->createBucket(array('Bucket' => $bucket));
	
		// Poll the bucket until it is accessible
		$client->waitUntilBucketExists(array('Bucket' => $bucket));
	
		// Be sure to add a use statement at the beginning of you script:
		//

		// XXX this needs to deal with other extensions, which should be fun as they don't have EXIF...
		$fileExt="jpg";
		// $shaPath=$this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2)."/".$this->fileEXIF['sha'].'.'.$fileExt;

		// This section creates a subdir in the bucket... when we figure out how to do that.
		// if (!is_dir($this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2))) {
		//	mkdir($this->imageBasePath.'/'.substr($this->fileEXIF['sha'],0,2));
		//}

		// // Upload an object by streaming the contents of an EntityBody object
		$client->putObject(array(
			'Bucket' => $bucket,
			'Key'    => trim($this->fileEXIF['sha'].'.'.$fileExt),
			'Body'   => EntityBody::factory(fopen($this->fileName, 'r+'))
		));

		// This is where we'll push EXIF data to ElasticSearch. We'll potentially want to clean up the EXIF first.
		$this->updateES();
	}
}
