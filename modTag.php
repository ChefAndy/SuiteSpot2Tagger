<?php

$output=array();
$pageID=null;
$m = new MongoClient();
$db = $m->amesdb;
$collection = $db->tags;
$result=array();
$result['exception']=array();



if (array_key_exists('remove', $_GET)) {

	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["remove"], $matches)) {
		$remove=$matches[0];
		$thisid=new MongoId($remove);
		if ($collection->remove( array('_id'=> $thisid) )) {
			$result['success']=strval($thisid);
		} else {
			array_push($result['exception'], "remove");
		}

	} else {
		array_push($result['exception'], "tagID");
	}

} if (array_key_exists('edit', $_GET)) {
	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["edit"], $matches)) {

		$edit=$matches[0];
		$thisid=new MongoId($edit);

		$type=htmlspecialchars($_GET["type"]);
		$tag=htmlspecialchars($_GET["tag"]);

		if ($type && $tag && is_object($thisid)) {
			if ($collection->update( array('_id'=> $thisid) , array('$set' => array("tag" => $tag, "type" => $type)))) {
				$result['success']=strval($thisid);
			} else {
				array_push($result['exception'], "remove");
			}
		} else {
			header('Content-Type: application/json');
			echo json_encode($result);
			exit;
		}

	} else {
		array_push($result['exception'], "tagID");
	}
} elseif (array_key_exists('pageID', $_GET)) {

	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["pageID"], $matches)) {
		$pageID=$matches[0];
	} else {
		array_push($result['exception'], "pageID");
	}

	if (preg_match('/^[A-Za-z0-9]+$/', $_GET["objID"], $matches)) {
		$objID=$matches[0];
	} else {
		array_push($result['exception'], "objID");
	}

	$tag=htmlspecialchars($_GET["tag"]);

		$type=htmlspecialchars($_GET["type"]);

	if ($pageID && $tag && $objID && $pageID) {
		$thisid=new MongoId();
		if ($collection->insert(array('_id' => $thisid,"pageid" => $pageID, "objid" => $objID, "type" => $type, "tag" => $tag))) {
			$result['success']=strval($thisid);
		} else {
			array_push($result['exception'], "insert");
		}
	} else {
		header('Content-Type: application/json');
		echo json_encode($result);
		exit;
	}
} 


if (count($result['exception']) == 0) {
	unset($result['exception']);
}


header('Content-Type: application/json');
echo json_encode($result);

/*
collection = db.tags;
result = collection.aggregate( 
            [
                {"$group": { "_id": { type: "$type", tag: "$tag" } } }
            ]
        );
printjson(result);
*/

    