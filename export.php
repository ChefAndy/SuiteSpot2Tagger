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

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"AmesOutput.sql\""); 

echo "CREATE TABLE objects (id INT,\n urn VARCHAR(50),\n title VARCHAR(256),\n entrylabel VARCHAR(256),\n imgct INT,\n thumbnail VARCHAR(50),\n drsID INT,\n mongoID VARCHAR(24));\n\n";
echo "CREATE TABLE tags (id INT,\n object_id INT,\n ttype VARCHAR(256),\n tag VARCHAR(256),\n mongoObjid VARCHAR(64),\n mognoPageID VARCHAR(64),\n mongoTagID VARCHAR(64));\n\n";
echo "CREATE TABLE pages (id INT,\n object_id INT,\n sectionLabel VARCHAR(256),\n sectionPagestart  VARCHAR(256),\n sectionPageend  VARCHAR(256),\n sectionSeqstart INT,\n sectionSeqend INT,\n sectionLlabelrange VARCHAR(256),\n sectionSeqrange VARCHAR(256),\n sectionLink VARCHAR(256),\n entrylabel VARCHAR(256),\n pagelabel VARCHAR(256),\n sequence INT,\n pagenum VARCHAR(256),\n thumb VARCHAR(256),\n image VARCHAR(256),\n link VARCHAR(256),\n mongoObjid VARCHAR(64),\n mognoPageID VARCHAR(64));\n\n";


foreach ($scandataIterator as $scandataresult){
		$mysql_ObjID++;
        $docID=strval($scandataresult['_id']);
        echo "INSERT INTO objects (id, urn, title, entrylabel, imgct, thumbnail, drsID, mongoID) VALUES("
                .$mysql_ObjID.",\n '"
                .mysql_escape_string( strval($scandataresult['urn']))."',\n '"
                .mysql_escape_string(strval($scandataresult['title']))."',\n '"
                .mysql_escape_string(strval($scandataresult['entrylabel']))."',\n '"
                .mysql_escape_string(strval($scandataresult['imgct']))."',\n '"
                .mysql_escape_string(strval($scandataresult['thumbnail']))."',\n '"
                .mysql_escape_string(strval($scandataresult['drsID']))."',\n '"
        	.$docID."');\n";

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
                                .$mysql_PagID.",\n "
                                .$mysql_ObjID.",\n '"
                                .$sectionLabel."',\n '"
                                .$sectionPagestart."',\n '"
                                .$sectionPageend."',\n '"
                                .$sectionSeqstart."',\n '"
                                .$sectionSeqend."',\n '"
                                .$sectionLlabelrange."',\n '"
                                .$sectionSeqrange."',\n '"
                                .$sectionLink."',\n '"
                                .mysql_escape_string(strval($pageresult['entrylabel']))."',\n '"
                                .mysql_escape_string(strval($pageresult['pagelabel']))."',\n '"
                                .mysql_escape_string(strval($pageresult['sequence']))."',\n '"
                                .mysql_escape_string(strval($pageresult['pagenum']))."',\n '"
                                .mysql_escape_string(strval($pageresult['thumb']))."',\n '"
                                .mysql_escape_string(strval($pageresult['image']))."',\n '"
                                .mysql_escape_string(strval($pageresult['link']))."',\n '"
                                .mysql_escape_string(strval($pageresult['objid']))."',\n '"
                                .$pageID."',\n '"
		        .$docID."');\n\n";

                $tagsIterator= $tagsMongoCollection->find(array( 'pageid' => ($pageID) ));
                foreach ($tagsIterator as $tagsresult){

						$mysql_TagID++;
                        $tagID=strval($tagsresult['_id']);
                            echo "\tINSERT INTO tags (id,\n page_id,\n object_id,\n ttype,\n tag,\n mongoObjid,\n mognoPageID,\n mongoTagID) VALUES("
                                        .$mysql_TagID.",\n "
                                        .$mysql_PagID.",\n "
                                        .$mysql_ObjID.",\n '"
                                        .mysql_escape_string(strval($tagsresult['type']))."',\n '"
                                        .mysql_escape_string(strval($tagsresult['tag']))."',\n '"
                                        .$tagID."',\n '"
                                        .$pageID."',\n '"
                                .$docID."');\n\n";
                }
        }
}
