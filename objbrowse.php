<?php

$docID=null;
if (array_key_exists('docID', $_GET)) {
	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["docID"], $matches)) {
		$docID=$matches[0];
	}
}

$output=array();
if (!$docID) {

	$m = new MongoClient();
	$db = $m->amesdb;
	$collection = $db->scandata;

	$cursor = $collection->find();

	foreach ($cursor as $document) {
		array_push($output, $document);
	}

} else {

	$m = new MongoClient();
	$db = $m->amesdb;
	$collection = $db->scanpages;	
	$cursor=$collection->find( array( 'objid' => ($docID) ) );
	
	foreach ($cursor as $document) {
		array_push($output, $document);
	}
}

header('Content-Type: application/json');
echo json_encode($output);