<?php
$route = '/api/:api_id/';	
$app->delete($route, function ($api_id) use ($app){
	
	$host = $_SERVER['HTTP_HOST'];
	$api_id = prepareIdIn($api_id,$host);

	$Add = 1;
	$ReturnObject = array();
	
	$host = $_SERVER['HTTP_HOST'];		
	$api_id = decrypt($api_id,$host);		
	
 	$request = $app->request(); 
 	$params = $request->params();	

	$query = "DELETE FROM blog WHERE slug = '" . $api_id . "'";
	//echo $query . "<br />";
	mysql_query($query) or die('Query failed: ' . mysql_error());	

	});	
	
?>