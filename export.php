<?php

$output=array();
$pageID=null;
$m = new MongoClient();
$db = $m->amesdb;

$scandataMongoCollection = $db->scandata;
$scanpagesMongoCollection = $db->scanpages;
$tagsMongoCollection = $db->tags;

$scandataIterator= $scandataMongoCollection->find();
$mysql_ObjID=0;
$mysql_PagID=0;
$mysql_TagID=0;

echo "CREATE TABLE objects (id INT, urn VARCHAR(50), title VARCHAR(256), entrylabel VARCHAR(256), imgct INT, thumbnail VARCHAR(50), drsID INT, mongoID VARCHAR(24));";
echo "CREATE TABLE tags (id INT, object_id INT, ttype VARCHAR(256), tag VARCHAR(256), mongoObjid VARCHAR(64), mognoPageID VARCHAR(64), mongoTagID VARCHAR(64));";
echo "CREATE TABLE pages (id INT, object_id INT, sectionLabel VARCHAR(256), sectionPagestart  VARCHAR(256), sectionPageend  VARCHAR(256), sectionSeqstart INT, sectionSeqend INT, sectionLlabelrange VARCHAR(256), sectionSeqrange VARCHAR(256), sectionLink VARCHAR(256), entrylabel VARCHAR(256), pagelabel VARCHAR(256), sequence INT, pagenum VARCHAR(256), thumb VARCHAR(256), image VARCHAR(256), link VARCHAR(256), mongoObjid VARCHAR(64), mognoPageID VARCHAR(64));";


foreach ($scandataIterator as $scandataresult){
		$mysql_ObjID++;
        $docID=strval($scandataresult['_id']);
        echo "INSERT INTO objects (id, urn, title, entrylabel, imgct, thumbnail, drsID, mongoID) VALUES("
        	.$mysql_ObjID.", '"
        	.mysql_escape_string( strval($scandataresult['urn']))."', '"
        	.mysql_escape_string(strval($scandataresult['title']))."', '"
        	.mysql_escape_string(strval($scandataresult['entrylabel']))."', '"
        	.mysql_escape_string(strval($scandataresult['imgct']))."', '"
        	.mysql_escape_string(strval($scandataresult['thumbnail']))."', '"
        	.mysql_escape_string(strval($scandataresult['drsID']))."', '"
        	.$docID."');";

        $scanpagesIterator= $scanpagesMongoCollection->find(array( 'objid' => ($docID) ));
        foreach ($scanpagesIterator as $pageresult){
				$mysql_PagID++;
                $pageID=strval($pageresult['_id']);

                $sectionLabel=null;
                $sectionPagestart=null;
                $sectionPageend=null;
                $sectionSeqstart=null;
                $sectionSeqend=null;
                $sectionLlabelrange=null;
                $sectionSeqrange=null;
                $sectionLink=null;

                if (array_key_exists('sectionLabel', $pageresult)) { $sectionLabel=mysql_escape_string( strval($pageresult['sectionLabel'])); }
                if (array_key_exists('sectionPagestart', $pageresult)) { $sectionPagestart=mysql_escape_string( strval($pageresult['sectionPagestart'])); }
                if (array_key_exists('sectionPageend', $pageresult)) { $sectionPageend=mysql_escape_string( strval($pageresult['sectionPageend'])); }
                if (array_key_exists('sectionSeqstart', $pageresult)) { $sectionSeqstart=mysql_escape_string( strval($pageresult['sectionSeqstart'])); }
                if (array_key_exists('sectionSeqend', $pageresult)) { $sectionSeqend=mysql_escape_string( strval($pageresult['sectionSeqend'])); }
                if (array_key_exists('sectionLlabelrange', $pageresult)) { $sectionLlabelrange=mysql_escape_string( strval($pageresult['sectionLlabelrange'])); }
                if (array_key_exists('sectionSeqrange', $pageresult)) { $sectionSeqrange=mysql_escape_string( strval($pageresult['sectionSeqrange'])); }
                if (array_key_exists('sectionLink', $pageresult)) { $sectionLink=mysql_escape_string( strval($pageresult['sectionLink'])); }

        	    echo "\tINSERT INTO pages (id, object_id, sectionLabel, sectionPagestart, sectionPageend, sectionSeqstart, sectionSeqend, sectionLlabelrange, sectionSeqrange, sectionLink, entrylabel, pagelabel, sequence, pagenum, thumb, image, link, mongoObjid, mognoPageID) VALUES("
		        	.$mysql_PagID.", "
		        	.$mysql_ObjID.", '"
		        	.$sectionLabel."', '"
		        	.$sectionPagestart."', '"
		        	.$sectionPageend."', '"
		        	.$sectionSeqstart."', '"
		        	.$sectionSeqend."', '"
		        	.$sectionLlabelrange."', '"
		        	.$sectionSeqrange."', '"
		        	.$sectionLink."', '"
		        	.mysql_escape_string(strval($pageresult['entrylabel']))."', '"
		        	.mysql_escape_string(strval($pageresult['pagelabel']))."', '"
		        	.mysql_escape_string(strval($pageresult['sequence']))."', '"
		        	.mysql_escape_string(strval($pageresult['pagenum']))."', '"
		        	.mysql_escape_string(strval($pageresult['thumb']))."', '"
		        	.mysql_escape_string(strval($pageresult['image']))."', '"
		        	.mysql_escape_string(strval($pageresult['link']))."', '"
		        	.mysql_escape_string(strval($pageresult['objid']))."', '"
		        	.$pageID."', '"
		        .$docID."');";

                $tagsIterator= $tagsMongoCollection->find(array( 'pageid' => ($pageID) ));
                foreach ($tagsIterator as $tagsresult){

						$mysql_TagID++;
                        $tagID=strval($tagsresult['_id']);
	        	    echo "\tINSERT INTO tags (id, page_id, object_id, ttype, tag, mongoObjid, mognoPageID, mongoTagID) VALUES("
			        	.$mysql_TagID.", "
			        	.$mysql_PagID.", "
			        	.$mysql_ObjID.", '"
			        	.mysql_escape_string(strval($tagsresult['type']))."', '"
			        	.mysql_escape_string(strval($tagsresult['tag']))."', '"
			        	.$tagID."', '"
			        	.$pageID."', '"
			        .$docID."');";
                }
        }
}
