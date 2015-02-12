<?php

$output=array();
$pageID=null;
$m = new MongoClient();
$db = $m->amesdb;
$collection = $db->tags;
$tagorganize=array();




if (array_key_exists('pageID', $_GET)) {
	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["pageID"], $matches)) {
		$pageID=$matches[0];
	}
	if ($pageID) {
		$iterator=$collection->find( array( 'pageid' => $pageID ) );
		$output=iterator_to_array($iterator);
	}
} elseif (array_key_exists('tagtypes', $_GET)) {

	if (preg_match('/^[A-Za-z0-9\',\.\s]+$/', $_GET["term"], $matches)) {
		$suggtext=$matches[0];
	}

	if (array_key_exists('term', $_GET)) {
		$output=$collection->distinct("type", array("type" => new MongoRegex("/^".$suggtext.".*/i")));
	} else {
		$output=$collection->distinct("type");
	}

} elseif (array_key_exists('term', $_GET)) {

	// though term can also be submitted above to get tag suggestions,
	// the fact that tagtypes is set there won't let it get to this 
	// elseif

	$suggtext=null;
	$tagtype=null;

	if (preg_match('/^[A-Za-z0-9\',\.\s]+$/', $_GET["term"], $matches)) {
		$suggtext=$matches[0];
	}

	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["tagtype"], $matches)) {
		$tagtype=$matches[0];
	}

	$suggquery=array();
	if (!empty($suggtext) && !empty($tagtype)) {
	    $suggquery = array(
	   		array('$match' => array('tag' => new MongoRegex("/^".$suggtext.".*/i"), 'type' => $tagtype)),
	   		array('$group' => array('_id' => array('type' => '$type', 'tag' => '$tag' ))),
	   		array('$limit' => 5)
	   	); 
	} elseif (!empty($suggtext) && empty($tagtype)) {
		$suggquery = array(
	   		array('$match' => array('tag' => new MongoRegex("/^".$suggtext.".*/i"))),
	   		array('$group' => array('_id' => array('type' => '$type', 'tag' => '$tag' ))),
	   		array('$limit' => 5)
	   	);
	} elseif (empty($suggtext) && !empty($tagtype)) {
	    $suggquery = array(
	   		array('$match' => array('type' => $tagtype)),
	   		array('$group' => array('_id' => array('type' => '$type', 'tag' => '$tag' ))),
	   		array('$limit' => 5)
	   	); 
	} else {
		$suggquery = array(
	   		array('$group' => array('_id' => array('type' => '$type', 'tag' => '$tag' ))),
	   		array('$limit' => 5)
	   	); 
	}

    $results= $collection->aggregate( $suggquery );
    foreach ($results as $result){
    	foreach ($result as $thisresult) {
    		if (!array_key_exists($thisresult[_id][type], $tagorganize)) {
    			$tagorganize[$thisresult[_id][type]]=array();
    		}
    		array_push($tagorganize[$thisresult[_id][type]], $thisresult[_id][tag]);
    	}
    }

    foreach ($tagorganize as $type=>$tags) {
    	foreach ($tags as $tag) {
	    	array_push($output, array("category" => $type, "label"=>$tag));
    	}
    }

}


header('Content-Type: application/json');
echo json_encode($output);

/*
collection = db.tags;
result = collection.aggregate( 
            [
                {"$group": { "_id": { type: "$type", tag: "$tag" } } }
            ]
        );
printjson(result);
*/

    