<?php

$output=array();
$pageID=null;
$m = new MongoClient();
$db = $m->amesdb;

$scandataMongoCollection = $db->scandata;
$scanpagesMongoCollection = $db->scanpages;
$tagsMongoCollection = $db->tags;

$scandataIterator= $scandataMongoCollection->find();

foreach ($scandataIterator as $scandataresult){
        $docID=strval($scandataresult['_id']);
        echo $docID."\n";

        $scanpagesIterator= $scanpagesMongoCollection->find(array( 'objid' => ($docID) ));
        foreach ($scanpagesIterator as $pageresult){
                $pageID=strval($pageresult['_id']);
                echo "\t".$pageID."\n";

                $tagsIterator= $tagsMongoCollection->find(array( 'pageid' => ($pageID) ));
                foreach ($tagsIterator as $tagsresult){
                        $tagID=strval($tagsresult['_id']);
                        echo "\t\t".$tagID." ".$tagsresult['tag']."\n";
                }
        }
}