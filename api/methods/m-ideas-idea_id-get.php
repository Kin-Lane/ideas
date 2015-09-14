<?php
$route = '/ideas/:id';
$app->get($route, function ($IncomingID)  use ($app){

	$ObjectText = file_get_contents('https://raw2.github.com/Kin-Lane/ideas/gh-pages/data/ideas.json');
	$ObjectResult = json_decode($ObjectText,true);
	$ReturnObject = array();

	foreach($ObjectResult['ideas'] as $Object){

		$IncludeRecord = 0;

		$id = $Object['id'];
		$name = $Object['name'];
		$description = $Object['description'];
		$tags = $Object['tags'];

		if($IncomingID==$id)
			{
			$IncludeRecord=1;
			}

		if($IncludeRecord==1)
			{
			$F = array();
			$F['id'] = $id;
			$F['name'] = $name;
			$F['description'] = $description;
			$F['tags'] = $tags;
			array_push($ReturnObject, $F);
			}
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));
	});
?>
