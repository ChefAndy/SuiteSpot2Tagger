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

#} elseif (array_key_exists('urns', $_POST))  {

//a few bits of testing  code
} elseif (array_key_exists('urns', $_POST) || 1==1) {

#echo "<pre>";
#$data='[{"urn":"http:\/\/nrs.harvard.edu\/urn-3:HLS.Libr:5351631","title":"Sequence 1 : Narrationes and Abridgement of Cases, ca. 1450. Manuscript. HLS MS 41. Parts II-IV, Abridgement of Cases. Harvard Law School Library.","label":"Narrationes and Abridgement of Cases, ca. 1450. Manuscript. HLS MS 41. Parts II-IV, Abridgement of Cases. Harvard Law School Library.","imgct":"851","thumbnail":"http:\/\/ids.lib.harvard.edu\/ids\/view\/37093852?width=150&height=150&usethumb=y","drsID":"37093680"}]';
#$data='[{"urn":"http:\/\/nrs.harvard.edu\/urn-3:HLS.Libr:8598333","title":"Sequence 1 : Registrum Brevium, 1384. Manuscript. MSS HLS MS 155. Harvard Law School Library.","label":"Registrum Brevium, 1384. Manuscript. MSS HLS MS 155. Harvard Law School Library.","imgct":"541","thumbnail":"http:\/\/ids.lib.harvard.edu\/ids\/view\/43276912?width=150&height=150&usethumb=y","drsID":"43276911"}]';
#	if ($datainput=json_decode($data)) {
	
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


			#$simauthors=array("Thomas Collichio", "Padama Lakshmi", "Gail Simmons", "Richard Blaiz", "Anthony Bourdain", "Emeril Lagasse");
			#$simlabels=array("1.2 Buckle My Shoe", "3.4 Shut the Door", "5.6 Pick Up Sticks", "7.8 Shut The Gate", "9.10 Do It Again");
			#$simother=array("Super Awesome", "Too Long", "Needs More Jokes", "Wicked Wordy", "Are we there yet?", "Good amazon reviews", "Original Document Smells Weird", "Found in Basement in an Old Box of Vacation Slides", "Hexed by Shaman", "Analyzed while consuming period appropriate tea and jellied eels.");


            $insertcount=0;
            $pages=array();

            if (count($rettoc->section) > 0) {
		        foreach ($rettoc->section as $thissection) {
		        	foreach ($rettoc->section->page as $page) {
		        		$pushpage=array();
		        		//Section Specific values
		        		#print_r($page);
		        		if ($thissection->attributes()) {
		   		        		$pushpage['sectionLabel']=strval(strval($thissection->attributes()['label']));
				        		$pushpage['sectionPagestart']=strval(strval($thissection->attributes()['pagestart']));
				        		$pushpage['sectionPageend']=strval(strval($thissection->attributes()['pageend']));
				        		$pushpage['sectionSeqstart']=intval(strval($thissection->attributes()['seqstart']));
				        		$pushpage['sectionSeqend']=intval(strval($thissection->attributes()['seqend']));
				        		$pushpage['sectionLlabelrange']=strval(strval($thissection->attributes()['labelrange']));
				        		$pushpage['sectionSeqrange']=strval(strval($thissection->attributes()['seqrange']));
				        		$pushpage['sectionLink']=strval(strval($thissection->attributes()['link']));			
		        		}

		        		//Page Specific values
			            $pushpage['label']=strval($page->attributes()['label']);
			            $pushpage['pagelabel']=strval($page->attributes()['pagelabel']);
			            $pushpage['sequence']=intval($page->attributes()['sequence']);
			            $pushpage['pagenum']=strval($page->attributes()['pagenum']);
			            $pushpage['thumb']=strval($page->attributes()['thumb']);
			            $pushpage['image']=strval(preg_replace('/\?.*/', '', $page->attributes()['image']));
			            $pushpage['link']=strval($page->attributes()['link']);
			            array_push($pages, $pushpage);
		        	}
		        }
            }

            if (count($rettoc->page) > 0) {
		        foreach ($rettoc->page as $page) {
	        		$pushpage=array();
	        		//Section Specific values
		            $pushpage['label']=strval($page->attributes()['label']);
		            $pushpage['pagelabel']=strval($page->attributes()['pagelabel']);
		            $pushpage['sequence']=intval($page->attributes()['sequence']);
		            $pushpage['pagenum']=strval($page->attributes()['pagenum']);
		            $pushpage['thumb']=strval($page->attributes()['thumb']);
		            $pushpage['image']=strval(preg_replace('/\?.*/', '', $page->attributes()['image']));
		            $pushpage['link']=strval($page->attributes()['link']);
		            array_push($pages, $pushpage);
		        }
            }

            #rettoc->page

		    foreach ($pages as $cseq)
		    {
           		$insertcount++;

				#$simauthors[mt_rand(0, count($simauthors)-1)];
				$pageid=new MongoId();
				$cseq['_id'] = strval($pageid);
	            $cseq['objid']=$objid;
				$collectionp->insert($cseq);

	            #$thistag=array();
	            #$thistag['authors']=array();
	            #$thistag['labels']=array();
				if (!empty($cseq['pagenum'])) {
		            $collectiont->insert(array("pageid" => strval($cseq['_id']), "objid" => $objid, "type" => "foliation", "tag" => $cseq['pagenum'] ));
				}
	            #$collectiont->insert(array("pageid" => strval($cseq['_id']), "objid" => $objid, "type" => "author", "tag" => $simauthors[mt_rand(0, count($simauthors)-1)]));
	            #$collectiont->insert(array("pageid" => strval($cseq['_id']), "objid" => $objid, "type" => "label", "tag" => $simlabels[mt_rand(0, count($simlabels)-1)]));
	            #foreach (range(1, 50) as $number) {
	            #	if (mt_rand(0, 50) == mt_rand(0, 50)) {
	            #		$tag=$simother[mt_rand(0, count($simother)-1)];
				#		$pageid=new MongoId();
			    #        $collectiont->insert(array("pageid" => strval($cseq['_id']), "objid" => $objid, "type" => "other", "tag" => $tag));
	            #	}
	            #}
		    }
			$confirmed[strval($array->label)]=$insertcount;
		}
	}
header('Content-Type: application/json');
echo json_encode($confirmed);
}
