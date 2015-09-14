<?php
$route = '/ideas/';
$app->get($route, function ()  use ($app,$three_scale_provider_key,$repo){

	$ReturnObject = array();

 	$request = $app->request();
 	$_GET = $request->params();

	if(isset($_REQUEST['query'])){ $query = $_REQUEST['query']; } else { $query = '';}

	$ObjectText = file_get_contents('https://raw2.github.com/Kin-Lane/idea/gh-pages/data/ideas.json');
	$ObjectResult = json_decode($ObjectText,true);
	$ReturnObject = array();

	foreach($ObjectResult['ideas'] as $Object){

		$IncludeRecord = 1;

		$idea_id = $Object['idea_id'];
		$name = $Object['name'];
		$description = $Object['description'];
		$tags = $Object['tags'];

		if($query!=''){
			$IncludeRecord = 0;
			if(strpos(strtolower($name),strtolower($query)) != 0 || strpos(strtolower($description),strtolower($query)) != 0 || strpos(strtolower($tags),strtolower($query)) != 0)
				{
				$IncludeRecord = 1;
				}
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
