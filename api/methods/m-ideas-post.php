<?php
$route = '/ideas/';
$app->post($route, function () use ($app){

	$Add = 1;
	$ReturnObject = array();

 	$request = $app->request();
 	$params = $request->params();

	if(isset($params['name'])){ $name = mysql_real_escape_string($params['name']); } else { $name = 'No Name'; }
	if(isset($params['description'])){ $description = mysql_real_escape_string($params['description']); } else { $description = ''; }

  	$Query = "SELECT * FROM idea WHERE name = '" . $name . "'";
	//echo $Query . "<br />";
	$Database = mysql_query($Query) or die('Query failed: ' . mysql_error());

	if($Database && mysql_num_rows($Database))
		{
		$ThisIdea = mysql_fetch_assoc($Database);
		$idea_id = $ThisIdea['idea_id'];
		}
	else
		{
		$Query = "INSERT INTO idea(name,description)";
		$Query .= " VALUES(";
		$Query .= "'" . mysql_real_escape_string($name) . "',";
		$Query .= "'" . mysql_real_escape_string($description) . "'";
		$Query .= ")";
		//echo $Query . "<br />";
		mysql_query($Query) or die('Query failed: ' . mysql_error());
		$idea_id = mysql_insert_id();
		}

	$host = $_SERVER['HTTP_HOST'];
  $idea_id = prepareIdOut($idea_id,$host);

	$ReturnObject['idea_id'] = $idea_id;

	$app->response()->header("Content-Type", "application/json");
	echo format_json(json_encode($ReturnObject));

	});
?>
