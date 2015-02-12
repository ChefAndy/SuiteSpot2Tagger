<?php

if (array_key_exists('unconfirmedurns', $_GET)) {
	$urns=array();
	$urnsToConfirm=array();
	$testingurns=preg_split('/http:/', $_GET['unconfirmedurns']);
	foreach ($testingurns as $testurn) {
		if (preg_match('/^(\/\/nrs\.harvard\.edu\/urn-3:[A-Za-z\:\.0-9]+)(.*)$/', $testurn, $matches)) {
			array_push($urns, 'http:'.$matches[1]);
		}
	}

	foreach ($urns as $procurn) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,  $procurn);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl , CURLOPT_NOBODY, true);
		$data = curl_exec($curl);
		curl_close($curl);
		$url=preg_replace('/\s*/s', '', preg_replace('/\n.*/s', '', preg_replace('/.*Location: /s', '', $data)));

		if (preg_match('@http://([pi]ds)\.lib\.harvard.edu/[pi]ds/view/([0-9]+)$@', $url, $components)) {
			$linktype=$components[1];
			$linkid=$components[2];

		}
		elseif (preg_match('@http://([pi]ds)\.lib\.harvard.edu/[pi]ds/view/([0-9]+)\?n=([0-9])@', $url, $components)) {
			$linktype=$components[1];
			$linkid=$components[2];
			$seqno=$components[3];
		}

	    if ($linktype == 'pds'){

	            $curl = curl_init();
	            curl_setopt($curl, CURLOPT_URL, "http://pds.lib.harvard.edu/pds/get/$linkid");
	            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	            $data = curl_exec($curl);
	            curl_close($curl);
	            $retindex=simplexml_load_string($data);

            array_push($urnsToConfirm, array(
	        	'urn'=>$procurn,
	        	'title'=>strval($retindex->fulltitle),
	        	'label'=>strval($retindex->displaylabel),
	        	'imgct'=>strval($retindex->lastpage),
	        	'thumbnail'=>strval($retindex->thumb),
	        	'drsID'=>$linkid
        	));        
	    }
	}

	header('Content-Type: application/json');
	echo json_encode($urnsToConfirm);

} elseif (array_key_exists('urns', $_POST)) {

	$confirmed=array();
	if ($datainput=json_decode($_POST['urns'])) {
		foreach ($datainput as $array) {

			$linkid=$array->drsID;


		    $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, "http://pds.lib.harvard.edu/pds/toc/$linkid");
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    $data = curl_exec($curl);
		    curl_close($curl);
		    $data=preg_replace('/<font size="[0-9]+" face="[A-Za-z0-9 ]+">/', '', $data);
		    $data=preg_replace('/<\/a>/', '', $data);
		    $rettoc=simplexml_load_string($data);

			$m = new MongoClient();
			$db = $m->amesdb;
			$collectionp = $db->scanpages;
			$collectiont = $db->tags;
			$collection = $db->scandata;
			#$content['_id'] = new MongoId();
			$collection->insert($array);
			$objid=strval($array->_id);


			$simauthors=array("Thomas Collichio", "Padama Lakshmi", "Gail Simmons", "Richard Blaiz", "Anthony Bourdain", "Emeril Lagasse");
			$simlabels=array("1.2 Buckle My Shoe", "3.4 Shut the Door", "5.6 Pick Up Sticks", "7.8 Shut The Gate", "9.10 Do It Again");
			$simother=array("Super Awesome", "Too Long", "Needs More Jokes", "Wicked Wordy", "Are we there yet?", "Good amazon reviews", "Original Document Smells Weird", "Found in Basement in an Old Box of Vacation Slides", "Hexed by Shaman", "Analyzed while consuming period appropriate tea and jellied eels.");


            $insertcount=0;
		    foreach ($rettoc->page as $cseq)
		    {
           		$insertcount++;

				$simauthors[mt_rand(0, count($simauthors)-1)];

				$pageid=new MongoId();
				$thispage['_id'] = strval($pageid);
	            $thispage['objid']=$objid;
	            $thispage['label']=strval($cseq->attributes()->label);
	            $thispage['pagelabel']=strval($cseq->attributes()->pagelabel);
	            $thispage['sequence']=strval($cseq->attributes()->sequence);
	            $thispage['pagenum']=strval($cseq->attributes()->pagenum);
	            $thispage['thumb']=strval($cseq->attributes()->thumb);
	            $thispage['link']=strval($cseq->attributes()->link);
	            $thispage['image']=preg_replace('/\?.*/', '', $cseq->attributes()->image);
				$collectionp->insert($thispage);


	            $thistag=array();
	            $thistag['authors']=array();
	            $thistag['labels']=array();

	            $collectiont->insert(array("pageid" => strval($thispage['_id']), "objid" => $objid, "type" => "author", "tag" => $simauthors[mt_rand(0, count($simauthors)-1)]));
	            $collectiont->insert(array("pageid" => strval($thispage['_id']), "objid" => $objid, "type" => "label", "tag" => $simlabels[mt_rand(0, count($simlabels)-1)]));
	            foreach (range(1, 50) as $number) {
	            	if (mt_rand(0, 50) == mt_rand(0, 50)) {
	            		$tag=$simother[mt_rand(0, count($simother)-1)];
						$pageid=new MongoId();
			            $collectiont->insert(array("pageid" => strval($thispage['_id']), "objid" => $objid, "type" => "other", "tag" => $tag));
	            	}
	            }
		    }
			$confirmed[strval($array->label)]=$insertcount;
		}
	}
header('Content-Type: application/json');
echo json_encode($confirmed);
}
