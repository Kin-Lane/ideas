<?php
$route = '/ideas/:idea_id/';
$app->get($route, function ($idea_id)  use ($app){

	$host = $_SERVER['HTTP_HOST'];
	$idea_id = prepareIdIn($idea_id,$host);

	$ReturnObject = array();

	$Query = "SELECT * FROM idea WHERE idea_id = " . $idea_id;

	$DatabaseResult = mysql_query($Query) or die('Query failed: ' . mysql_error());

	while ($Database = mysql_fetch_assoc($DatabaseResult))
		{

		$idea_id = $Database['idea_id'];
		$name = $Database['name'];
		$description = $Database['description'];

		$TagQuery = "SELECT t.tag_id, t.tag from tags t";
		$TagQuery .= " INNER JOIN idea_tag_pivot btp ON t.tag_id = btp.tag_id";
		$TagQuery .= " WHERE btp.Idea_ID = " . $idea_id;
		$TagQuery .= " ORDER BY t.tag DESC";
		$TagResult = mysql_query($TagQuery) or die('Query failed: ' . mysql_error());

		// manipulation zone
		$host = $_SERVER['HTTP_HOST'];
		$idea_id = prepareIdOut($idea_id,$host);

		$F = array();
		$F['idea_id'] = $idea_id;
		$F['name'] = $name;
		$F['description'] = $description;

		$F['tags'] = array();

		while ($Tag = mysql_fetch_assoc($TagResult))
			{
			$thistag = $Tag['tag'];

			$T = array();
			$T = $thistag;
			array_push($F['tags'], $T);
			//echo $thistag . "<br />";
			if($thistag=='Archive')
				{
				$archive = 1;
				}
			}

		$ReturnObject = $F;
		}

		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));
	});
?>
