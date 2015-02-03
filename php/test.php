<?php
include_once("image.php");

$Img = new ImageLocal($argv[1]);
$Img->setImagePath($argv[1]);
echo $Img->dumpEXIF();
$Img->save();
?>
