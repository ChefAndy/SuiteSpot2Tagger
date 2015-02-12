<?php

$output=array();
$pageID=null;
$m = new MongoClient();
$db = $m->amesdb;



$collection = $db->tags;
$tags=array();
    $results= $collection->find();
    foreach ($results as $result){
    	print_r($result);
    }

    