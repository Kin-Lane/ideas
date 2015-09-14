<?php

include 'm-ideas-get.php';

$route = '/';
$app->get($route, function ()  use ($app){

	$E = array();
	$E['Message'] = "Welcome!";	

	$app->response()->status(200);
	$app->response()->header("Content-Type", "application/json");
	echo format_json(json_encode($E));

	});

?>
