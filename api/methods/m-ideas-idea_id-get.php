<?php
$route = '/ideas/:incoming_id';
$app->get($route, function ($incoming_id)  use ($app){

	$ObjectText = file_get_contents('https://raw2.github.com/Kin-Lane/idea/gh-pages/data/ideas.json');
	$ObjectResult = json_decode($ObjectText,true);
	$ReturnObject = array();

	foreach($ObjectResult['ideas'] as $Object){

		$IncludeRecord = 0;

		$idea_id = $Object['idea_id'];
		$name = $Object['name'];
		$description = $Object['description'];
		$tags = $Object['tags'];

		if($incoming_id==$idea_id)
			{
			$IncludeRecord=1;
			}

		if($IncludeRecord==1)
			{
			$F = array();
			$F['idea_id'] = $idea_id;
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
