<?php
if($_SESSION['userrole'] >= 2){ // PAGE AUTHENTICATION
	$getPageToken = $methods->getValueDB(['table'=>'tokens','alias'=>$methods->Params['pageName'],'schema'=>Info::DB_SYSTEMS]);
	if($getPageToken['id']){ // IF USER NOT ADMIN AND NOT AUTHORIZE
		if(!in_array($getPageToken['id'], $_SESSION["tokens"])) throw new \Exception($methods->errorPage('403'), 1);
	}
}
// $getPageDetail = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>$params['module_type'],'meta_option'=>$params['view']],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['meta_option','meta_value']];
// $pageDetail = $methods->selectDB($getPageDetail);
//var_dump($pageDetail);
//echo "page/{$thisType}-{$view}.php";
if($methods->authError) throw new \Exception($methods->errorPage('405'), 1);
include_once "page/{$thisType}-{$view}.php";
?>