<?php
$route = '/ideas/';
$app->post($route, function () use ($app,$three_scale_provider_key,$repo){

	$add = 1;

	$ReturnObject = array();

 	$request = $app->request();
 	$_POST = $request->params();

	if(isset($_POST['name'])){ $name = urldecode($_POST['name']); } else { $add = 0; $message = "You need a name for your API idea"; }
	if(isset($_POST['description']) && $add!=0){ $description = urldecode($_POST['description']); } elseif($add!=0) { $add = 0; $message = "You need a description for your API idea"; }
	if(isset($_POST['tags']) && $add!=0){ $tags = urldecode($_POST['tags']); } elseif($add!=0) { $add = 0; $tags = "You need at least one tag for your API idea"; }

	// Incoming Keys
	$appid = $_POST['appid'];
	$appkey = $_POST['appkey'];

	$client = new ThreeScaleClient($three_scale_provider_key);

	// Auth the application
	$response = $client->authorize($appid, $appkey);

	//var_dump($response);
	// Check the response typed
	if ($response->isSuccess()) {

	    $ReturnObject = array();
		$ReturnObject['ideas'] = array();

		$id = md5($name . date("m-d-y"));

		$A = array();
		$A['idea_id'] = $idea_id;
		$A['name'] = $name;
		$A['description'] = $description;
		$A['tags'] = $tags;

		array_push($ReturnObject['ideas'], $A);

		$ReturnObject['updated'] = date('m/d/Y');

		$ObjectText = file_get_contents('https://raw2.github.com/Kin-Lane/idea/gh-pages/data/ideas.json');
		$ObjectResult = json_decode($ObjectText,true);

		array_push($ObjectResult['ideas'], $A);
		$ObjectResult['updated'] = date('m/d/Y');

		$WriteAPIs = format_json(stripslashes(json_encode($ObjectResult)));
		$WriteAPIFile = "/var/www/html/repos/api-ideas/data/ideas.json";
		//echo "<br />Writing to " . $WriteAPIFile . "<br />";
		$fh = fopen($WriteAPIFile, 'w') or die("can't open file");
		fwrite($fh, $WriteAPIs);
		fclose($fh);

		$repo->stage('data/ideas.json');

		try
			{
			$repo->commit('ideas.api.apievangelist.com/ideas', true);
			$repo->push();
			}
		catch (Exception $e)
			{
			echo "ERROR!";
			}

		$Send_Name = "API Ideas";
		$Send_Email = "kinlane@gmail.com";
		$Send_Body =  format_json(json_encode($ReturnObject));

		$Message_Body = $Send_Body;

		$Send_To_1 = "kinlane@gmail.com";
		$Send_Name_1 = "Kin Lane";

		$AdminEmail = "info@apievangelist.com";
		$AdminPass = "@p1Voic3";

		$SiteName = "API Ideas";
		$Message_Subject = "Add API Idea Via API";

		// Email
		$mail=new PHPMailer();
		$mail->IsSMTP();

		$mail->SMTPAuth = true;

		$mail->SMTPSecure = "tls";
		$mail->Host = "smtp.gmail.com";
		$mail->Port = 587;

		///Admin Information
		$mail->Username = $AdminEmail;
		$mail->Password = $AdminPass;

		// Recipients Email
		$name = $SiteName;

		$mail->From = $Send_Email;
		$mail->FromName = $Send_Name;

		$mail->AddAddress($Send_To_1,$Send_Name_1);

		$mail->AddReplyTo($Send_Email,$SiteName);

		$mail->WordWrap = 50;

		$mail->IsHTML(true);

		$mail->Subject = $Message_Subject;

		$mail->Body = $Message_Body;

		if(!$mail->Send())
			{
			//echo "Mailer Error: " . $mail->ErrorInfo;
			}
		else
			{
			//echo "Message has been sent";
			}

		$app->response()->status(200);
		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($ReturnObject));

		}
	else
		{

		$ErrorMessage = $response->getErrorMessage();

		$E = array();
		$E['Message'] = "Sorry, you do not have access!";

		$app->response()->status(403);
		$app->response()->header("Content-Type", "application/json");
		echo format_json(json_encode($E));

		}
	});

?>
