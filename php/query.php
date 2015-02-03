<?php
	
require 'vendor/autoload.php';
$config = json_decode(file_get_contents("config.php"),TRUE);
$params = array('hosts'=>array($config['elasticSearchHostname'].":".$config['elasticSearchPort']));
$client = new Elasticsearch\Client($params);
$t=microtime(true);
	$searchParams = array();
    	$searchParams['index'] = 'photos';
    	$searchParams['type']  = 'name';
//    	$searchParams['body']['query']['filtered']['query']['match']['sha'] = $argv[1];
    	$searchParams['body']['query']['filtered']['query']['match']['isoEquiv'] = $argv[1];
//	$searchParams['body']['query']['match']['sha']=$argv[1];
//    	if (isset($argv[2])) {
//		//$searchParams['body']['query']['match']['Region'] = $argv[2];
//		$searchParams['body']['query']['filtered']['filter']['term']['Region'] = $argv[2];
//	}
echo json_encode($searchParams);
    	$queryResponse = $client->search($searchParams);
$tprime=microtime(true);
echo "Returned in: ".($tprime-$t)."s";
print_r($queryResponse['hits']['hits']);
//echo print_r($queryResponse['hits']['hits'][0]['_source']);



?>

