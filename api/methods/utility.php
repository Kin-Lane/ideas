<?php
		
$route = '/utility/api/server/rebuild/';	
$app->get($route, function ()  use ($app,$githuborg,$githubrepo,$gclient){
	
	$ref = "gh-pages";
	$APIsJSONURL = "https://raw.github.com/" . $githuborg . "/" . $githubrepo . "/gh-pages/apis.json";
	
	$Resource_Store_File = "apis.json";
	
	//echo $Resource_Store_File . "<br />";
	
	$CheckFile = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $Resource_Store_File);
	$APIsJSONContent = base64_decode($CheckFile->getcontent());	

	$APIsJSON = json_decode($APIsJSONContent,true);

	foreach($APIsJSON['apis'] as $APIsJSON)
		{
			
		$properties = $APIsJSON['properties'];
			
		foreach($properties as $property)
			{
				
			$property_type = $property['type'];
			
			if(strtolower($property_type)=="swagger")
				{
					
				$swagger_url = $property['url'];		
				echo $property_type . " - " . $swagger_url . "<br />";
				
				$cleanbase = "https://github.com/" . $githuborg . "/" . $githubrepo . "/blame/" . $ref . "/";
				$swagger_path = str_replace($cleanbase,"",$swagger_url); 
				
				$swagger_path =  "swagger.json";
				
				echo "path: " . $swagger_path . "<br />";
				
				$PullSwagger = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $swagger_path);
				$SwaggerJSON = base64_decode($PullSwagger->getcontent());						
				
				$Swagger = json_decode($SwaggerJSON,true);	
				
				$Swagger_Title = $Swagger['info']['title'];
				$Swagger_Description = $Swagger['info']['description'];
				$Swagger_TOS = $Swagger['info']['termsOfService'];
				$Swagger_Version = $Swagger['info']['version'];
				
				$Swagger_Host = $Swagger['host'];
				$Swagger_BasePath = $Swagger['basePath'];
				
				$Swagger_Scheme = $Swagger['schemes'][0];
				$Swagger_Produces = $Swagger['produces'][0];
				
				echo $Swagger_Title . "<br />";

				$Method = "";
				$Method .= "<?php" . chr(13);			
					
				$Swagger_Definitions = $Swagger['definitions'];	
					
				$Swagger_Paths = $Swagger['paths'];				
				foreach($Swagger_Paths as $key => $value)
					{
						
					$Path_Route = $key;
					echo $Path_Route . "<br />";
					
					// Each Path Variable
					$id = 0;
					$Path_Variable_Count = 1;
					$Path_Variables = "";
					$Begin_Tag = "{";
					$End_Tag = "}";
					$path_variables_array = return_between($Path_Route, $Begin_Tag, $End_Tag, EXCL);

					$Path_Route = str_replace("{",":",$Path_Route);
					$Path_Route = str_replace("}","",$Path_Route);

					if(is_array($path_variables_array))
						{
						foreach($path_variables_array as $var)
							{
							echo "VAR: " . $var . "<br />";
							if($Path_Variable_Count==1)
								{
								$Path_Variables .= chr(36) . $var;
								$Path_Variable_Count++;
								$id = $var;
								}
							else
								{
								$Path_Variables .= "," . chr(36) . $var;	
								}
							}										
						}
					else
						{
						if(strlen($path_variables_array)>2)
							{
							$Path_Variables =  chr(36) . $path_variables_array;
							$id = chr(36) . $path_variables_array;
							}
						}
						
					// Each Path
					foreach($value as $key2 => $value2)
						{
							
						$Definition = "";
						$Path = "";
						$Path_Verb = $key2;
								
						$Path_Summary = $value2['summary'];
						$Path_Desc = $value2['description'];
						$Path_OperationID = $value2['operationId'];
						$Path_Parameters = $value2['parameters'];		
						
						echo $Path_Verb . "<br />";
						echo $Path_Summary . "<br />";							
						
						$Path .= chr(36) . "route = '" . $Path_Route . "';" . chr(13);
						$Path .= chr(36) . "app->" . strtolower($Path_Verb) . "(" . chr(36) . "route, function (" . $Path_Variables . ")  use (" . chr(36) . "app){" . chr(13) . chr(13);																	
								
						$Path .= chr(9) . chr(36) . "request = " . chr(36) . "app->request();" . chr(13);
						$Path .= chr(9) . chr(36) . "_GET = " . chr(36) . "request->params();" . chr(13) . chr(13);								

						$Path_Responses = $value2['responses'];		
						foreach($Path_Responses as $key3 => $value3)
							{
								
							$Response_Code = $key3;												
							$Response_Desc = $value3['description'];							
							$Response_Definition = $value3['schema']['items'][chr(36)."ref"];
							$Response_Definition = str_replace("#/definitions/", "", $Response_Definition);
							
							if($Response_Code=="200")
								{
								$Definition = $Response_Definition;
								}													
							}

						foreach($Path_Parameters as $parameter)
							{
							$Parameter_Name = $parameter['name'];
							$Parameter_In = $parameter['in'];	
							$Parameter_Desc = $parameter['description'];	
							$Parameter_Required = $parameter['required'];	
							$Parameter_Type = $parameter['type'];									
							echo $Parameter_Name . "(" . $Parameter_In . ")<br />";	
							if($Parameter_In=='query')
								{																
								$Path .= chr(9) . "if(isset(" . chr(36) . "_GET['" . $Parameter_Name . "'])){ " . chr(36) . $Parameter_Name . " = " . chr(36) . "_GET['" . $Parameter_Name . "']; } else { " . chr(36) . $Parameter_Name . " = '';}" . chr(13);
								}							
							}						
						
						// Each Verb
						if($Path_Verb=="get")
							{
							
							$Path .= chr(13) . chr(9) . chr(36) . "ReturnObject = array();" . chr(13) . chr(13);
														
							if($id!='')
								{
								$Path .= chr(9) . chr(36) . "Query = " . chr(34) . "SELECT * FROM " . strtolower($Definition) . " WHERE slug = '" . chr(34) . " . " . chr(36) . "slug . " . chr(34) . "'" . chr(34) . ";" . chr(13);
								}
							else
								{
								$Path .= chr(9) . "if(" . chr(36) . "query=='')" . chr(13);
								$Path .= chr(9) . chr(9) . "{" . chr(13);
								$Path .= chr(9) . chr(9) . chr(36) . "Query = " . chr(34) . "SELECT * FROM " . strtolower($Definition) . " WHERE name LIKE '%" . chr(34) . " . " . chr(36) . "query . " . chr(34) . "%'" . chr(34) . ";" . chr(13);
								$Path .= chr(9) . chr(9) . "}" . chr(13);
								$Path .= chr(9) . "else" . chr(13);
								$Path .= chr(9) . chr(9) . "{" . chr(13);
								$Path .= chr(9) . chr(9) . chr(36) . "Query = " . chr(34) . "SELECT * FROM " . strtolower($Definition) . chr(34) . ";" . chr(13);		
								$Path .= chr(9) . chr(9) . "}" . chr(13);
								
								$Path .= chr(13) . chr(9) . chr(36) . "Query .= " . chr(34) . " ORDER BY name ASC" . chr(34) . ";" . chr(13) . chr(13);	
								}	
																			
							$Path .= chr(9) . chr(36) . "DatabaseResult = mysql_query(" . chr(36) . "Query) or die('Query failed: ' . mysql_error());" . chr(13) . chr(13);		
							  
							$Path .= chr(9) . "while (" . chr(36) . "Database = mysql_fetch_assoc(" . chr(36) . "DatabaseResult))" . chr(13);
							$Path .= chr(9) . chr(9) . "{" . chr(13);			
						
							foreach($Swagger_Definitions as $key => $value)
								{											
								echo $key . "<br />";	
								if($key == $Definition)
									{
									$Definition_Properties = $value['properties'];	
									
									// Incoming
									foreach($Definition_Properties as $key4 => $value4)
										{
										$Definition_Property_Name = $key4;
										echo $Definition_Property_Name . "<br />";
										
										if(isset($value4['description'])){ $Definition_Property_Desc = $value4['description']; } else { $Definition_Property_Desc = ""; }
										if(isset($value4['type'])){ $Definition_Property_Type = $value4['type']; } else { $Definition_Property_Type = ""; }
										if(isset($value4['format'])){ $Definition_Property_Format = $value4['format']; } else { $Definition_Property_Format = ""; }
										
										$Path .= chr(9) . chr(9) . chr(36) . $Definition_Property_Name . " = " . chr(36) . "Database['" . $Definition_Property_Name . "'];" . chr(13);		
										}
										
									// Outgoing
									$Path .= chr(13) . chr(9) . chr(9) . chr(36) . "F = array();" . chr(13);
									foreach($Definition_Properties as $key4 => $value4)
										{
										$Definition_Property_Name = $key4;
										echo $Definition_Property_Name . "<br />";
										
										if(isset($value4['description'])){ $Definition_Property_Desc = $value4['description']; } else { $Definition_Property_Desc = ""; }
										if(isset($value4['type'])){ $Definition_Property_Type = $value4['type']; } else { $Definition_Property_Type = ""; }
										if(isset($value4['format'])){ $Definition_Property_Format = $value4['format']; } else { $Definition_Property_Format = ""; }
										
										$Path .= chr(9) . chr(9) . chr(36) . "F['" . $Definition_Property_Name . "'] = " . chr(36) . $Definition_Property_Name . ";" . chr(13);		
										}										
									$Path .= chr(13) . chr(9) . chr(9) . "array_push(" . chr(36) . "ReturnObject, " . chr(36) . "F);" . chr(13) . chr(13);
									}																
								}												

							$Path .= chr(9) . chr(9) . "}" . chr(13) . chr(13);		
						
							$Path .= chr(9) . chr(36) . "api->response()->header(" . chr(34) . "Content-Type" . chr(34) . ", " . chr(34) . "application/json" . chr(34) . ");" . chr(13);
							$Path .= chr(9) . "echo stripslashes(format_json(json_encode(" . chr(36) . "ReturnObject)));" . chr(13);	
							
							} 
						elseif($Path_Verb=="post")
							{

							$Path .= chr(13) . chr(9) . chr(36) . "slug = PrepareFileName(" . chr(36) . "name);" . chr(13). chr(13);						
						  	$Path .= chr(13) . chr(9) . chr(36) . "Query = " . chr(34) . "SELECT * FROM " . strtolower($Definition) . " WHERE name = '" . chr(34) . " . " . chr(36) . "name . " . chr(34) . "'" . chr(34) . ";" . chr(13). chr(13);
							$Path .= chr(9) . chr(36) . "Database = mysql_query(" . chr(36) . "Query) or die('Query failed: ' . mysql_error());" . chr(13). chr(13);							
							$Path .= chr(9) . "if(" . chr(36) . "Database && mysql_num_rows(" . chr(36) . "Database))" . chr(13);
							$Path .= chr(9) . chr(9) . "{" . chr(13);	
							$Path .= chr(9) . chr(9) . chr(36) . "Link = mysql_fetch_assoc(" . chr(36) . "Database);" . chr(13) . chr(13);											
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject = array();" . chr(13);												
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['message'] = " . chr(34) . ucfirst($Definition) . " Already Exists!" . chr(34) . ";" . chr(13);			
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['slug'] = " . chr(36) . "slug;" . chr(13) . chr(13);										
							$Path .= chr(9) . chr(9) . "}" . chr(13);
							$Path .= chr(9) . "else" . chr(13); 
							$Path .= chr(9) . chr(9) . "{" . chr(13);				
									
							$Path .= chr(9) . chr(9) . chr(36) . "query = " . chr(34) . "INSERT INTO " . strtolower($Definition) . "(" . chr(34) . ";" . chr(13) . chr(13);

							foreach($Path_Parameters as $parameter)
								{
								$Parameter_Name = $parameter['name'];
								$Parameter_In = $parameter['in'];	
								$Parameter_Desc = $parameter['description'];	
								$Parameter_Required = $parameter['required'];	
								$Parameter_Type = $parameter['type'];									
								//echo $Parameter_Name . "<br />";																	
								$Path .= chr(9) . chr(9) . "if(isset(" . chr(36) . $Parameter_Name . ")){ " . chr(36) . "query .= " . chr(36) . $Parameter_Name . " . " . chr(34) . "," . chr(34) . "; }" . chr(13);						
								}								
																
							$Path .= chr(13) . chr(9) . chr(9) . chr(36) . "query .= " . chr(34) . ") VALUES(" . chr(34) . ";" . chr(13) . chr(13);										
								
							foreach($Path_Parameters as $parameter)
								{
								$Parameter_Name = $parameter['name'];
								$Parameter_In = $parameter['in'];	
								$Parameter_Desc = $parameter['description'];	
								$Parameter_Required = $parameter['required'];	
								$Parameter_Type = $parameter['type'];																
								$Path .= chr(9) . chr(9) . "if(isset(" . chr(36) . $Parameter_Name . ")){ " . chr(36) . "query .= " . chr(34) . "'" . chr(34) . " . mysql_real_escape_string(" . chr(36) . $Parameter_Name . ") . " . chr(34) . "'," . chr(34) . "; }" . chr(13);					
								}								

							$Path .= chr(13) . chr(9) . chr(9) . chr(36) . "query .= " . chr(34) . ")" . chr(34) . ";" . chr(13) . chr(13);

							$Path .= chr(9) . chr(9) . "mysql_query(" . chr(36) . "query) or die('Query failed: ' . mysql_error());" . chr(13) . chr(13);
								
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject = array();" . chr(13);												
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['message'] = " . chr(34) . ucfirst($Definition) . " Added!" . chr(34) . ";" . chr(13);	
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['slug'] = " . chr(36) . "slug;" . chr(13);				
											
							$Path .= chr(9) . chr(9) . "}" . chr(13) . chr(13);		
						
							$Path .= chr(9) . chr(36) . "api->response()->header(" . chr(34) . "Content-Type" . chr(34) . ", " . chr(34) . "application/json" . chr(34) . ");" . chr(13);
							$Path .= chr(9) . "echo stripslashes(format_json(json_encode(" . chr(36) . "ReturnObject)));" . chr(13);											
							
							}
						elseif($Path_Verb=="put")
							{
						
						  	$Path .= chr(13) . chr(9) . chr(36) . "Query = " . chr(34) . "SELECT * FROM " . strtolower($Definition) . " WHERE slug = '" . chr(34) . " . " . chr(36) . "slug . " . chr(34) . "'" . chr(34) . ";" . chr(13). chr(13);
							$Path .= chr(9) . chr(36) . "Database = mysql_query(" . chr(36) . "Query) or die('Query failed: ' . mysql_error());" . chr(13). chr(13);							
							$Path .= chr(9) . "if(" . chr(36) . "Database && mysql_num_rows(" . chr(36) . "Database))" . chr(13);
							$Path .= chr(9) . chr(9) . "{" . chr(13);				
									
							$Path .= chr(9) . chr(9) . chr(36) . "query = " . chr(34) . "UPDATE " . strtolower($Definition) . " SET" . chr(34) . ";" . chr(13) . chr(13);

							foreach($Path_Parameters as $parameter)
								{
								$Parameter_Name = $parameter['name'];
								$Parameter_In = $parameter['in'];	
								$Parameter_Desc = $parameter['description'];	
								$Parameter_Required = $parameter['required'];	
								$Parameter_Type = $parameter['type'];																

								$Path .= chr(9) . chr(9) . "if(isset(" . chr(36) . $Parameter_Name . "))" . chr(13);
								$Path .= chr(9) . chr(9) .chr(9) . "{" . chr(13);
								$Path .= chr(9) . chr(9) .chr(9) . chr(36) . "query .= " . chr(34) . $Parameter_Name . "='" . chr(34) . " . mysql_real_escape_string(" . chr(36) . $Parameter_Name . ") . " . chr(34) . "'" . chr(34) . ";" . chr(13); 
								$Path .= chr(9) . chr(9) .chr(9) . "}" . chr(13); 								
																			
								}					
								
							$Path .= chr(13) . chr(9) . chr(9) . chr(36) . "query .= " . chr(34) . " WHERE slug = '" . chr(34) . " . " . chr(36) . "slug . " . chr(34) . "'" . chr(34) . ";" . chr(13);											

							$Path .= chr(9) . chr(9) . "mysql_query(" . chr(36) . "query) or die('Query failed: ' . mysql_error());" . chr(13) . chr(13);
								
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject = array();" . chr(13);												
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['message'] = " . chr(34) . ucfirst($Definition) . " Updated!" . chr(34) . ";" . chr(13);	
							$Path .= chr(9) . chr(9) . chr(36) . "ReturnObject['slug'] = " . chr(36) . "slug;" . chr(13);				
											
							$Path .= chr(9) . chr(9) . "}" . chr(13) . chr(13);		
						
							$Path .= chr(9) . chr(36) . "api->response()->header(" . chr(34) . "Content-Type" . chr(34) . ", " . chr(34) . "application/json" . chr(34) . ");" . chr(13);
							$Path .= chr(9) . "echo stripslashes(format_json(json_encode(" . chr(36) . "ReturnObject)));" . chr(13);											
							
							}							
						elseif($Path_Verb=="delete")
							{
							
						  	$Path .= chr(9) . chr(36) . "Query = " . chr(34) . "DELETE FROM " . strtolower($Definition) . " WHERE slug = '" . chr(34) . " . " . chr(36) . "slug . " . chr(34) . "'" . chr(34) . ";" . chr(13). chr(13);
							$Path .= chr(9) . "mysql_query(" . chr(36) . "Query) or die('Query failed: ' . mysql_error());" . chr(13). chr(13);												

							$Path .= chr(9) . chr(36) . "ReturnObject = array();" . chr(13);												
							$Path .= chr(9) . chr(36) . "ReturnObject['message'] = " . chr(34) . ucfirst($Definition) . " Deleted!" . chr(34) . ";" . chr(13);	
							$Path .= chr(9) . chr(36) . "ReturnObject['slug'] = " . chr(36) . "slug;" . chr(13);					
						
							$Path .= chr(13) . chr(9) . chr(36) . "api->response()->header(" . chr(34) . "Content-Type" . chr(34) . ", " . chr(34) . "application/json" . chr(34) . ");" . chr(13);
							$Path .= chr(9) . "echo stripslashes(format_json(json_encode(" . chr(36) . "ReturnObject)));" . chr(13);							
							}																											
																				
						$Path .= chr(13) . chr(9) . "});" . chr(13);				
						$Path .= chr(13) . chr(13);
						
						$Method .= $Path;
						}															
					}
					
				echo "<hr />";
				
				echo "--definitions--<br />";
				foreach($Swagger_Definitions as $key => $value)
					{											
					echo $key . "<br />";	
					$Definition_Properties = $value['properties'];	
					foreach($Definition_Properties as $key4 => $value4)
						{
						$Definition_Property_Name = $key4;
						echo $Definition_Property_Name . "<br />";
						
						if(isset($value4['description'])){ $Definition_Property_Desc = $value4['description']; } else { $Definition_Property_Desc = ""; }
						if(isset($value4['type'])){ $Definition_Property_Type = $value4['type']; } else { $Definition_Property_Type = ""; }
						if(isset($value4['format'])){ $Definition_Property_Format = $value4['format']; } else { $Definition_Property_Format = ""; }
						
						echo $Definition_Property_Type . "<br />";
						echo $Definition_Property_Desc . "<br />";		
						}						
					//var_dump($value);
					echo "<hr />";											
					}				
					
				}
						
			$Method .= "?>" . chr(13);			
										
			$AccountFolder = "/var/www/html/kin_lane/blog/api/methods/";	
			$MethodName = "blog.php";
			$MethodFile = $AccountFolder . $MethodName;
			echo "Writing: " . $MethodFile . "<br />";
			$fp = fopen($MethodFile, "w+");				
			fwrite($fp, $Method);
			fclose($fp);	

	
			}	
		}	


	});	
		
$route = '/utility/api/client-js/rebuild/';	
$app->get($route, function ()  use ($app,$githuborg,$githubrepo,$gclient){
	
	$ALLPARAMETERS = "";
	
	$ref = "gh-pages";
	$APIsJSONURL = "https://raw.github.com/" . $githuborg . "/" . $githubrepo . "/gh-pages/apis.json";
	
	$Resource_Store_File = "apis.json";
	
	//echo $Resource_Store_File . "<br />";
	
	$CheckFile = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $Resource_Store_File);
	$APIsJSONContent = base64_decode($CheckFile->getcontent());	

	$APIsJSON = json_decode($APIsJSONContent,true);

	foreach($APIsJSON['apis'] as $APIsJSON)
		{
			
		$properties = $APIsJSON['properties'];	

		foreach($properties as $property)
			{
				
			$property_type = $property['type'];
			
			if(strtolower($property_type)=="swagger")
				{
					
				$swagger_url = $property['url'];		
				//echo $property_type . " - " . $swagger_url . "<br />";
				
				$cleanbase = "https://github.com/" . $githuborg . "/" . $githubrepo . "/blame/" . $ref . "/";
				$swagger_path = str_replace($cleanbase,"",$swagger_url); 
				
				$swagger_path =  "swagger.json";
				
				//echo "path: " . $swagger_path . "<br />";
				
				$PullSwagger = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $swagger_path);
				$SwaggerJSON = base64_decode($PullSwagger->getcontent());						
				
				$Swagger = json_decode($SwaggerJSON,true);	
				
				$Swagger_Title = $Swagger['info']['title'];
				$Swagger_Description = $Swagger['info']['description'];
				$Swagger_TOS = $Swagger['info']['termsOfService'];
				$Swagger_Version = $Swagger['info']['version'];
				
				$Swagger_Host = $Swagger['host'];
				$Swagger_BasePath = $Swagger['basePath'];
				
				$Swagger_Scheme = $Swagger['schemes'][0];
				$Swagger_Produces = $Swagger['produces'][0];
				
				//echo $Swagger_Title . "<br />";

				$Swagger_Definitions = $Swagger['definitions'];	
					
				$Swagger_Paths = $Swagger['paths'];				
				foreach($Swagger_Paths as $key => $value)
					{
						
					$Path_Route = $key;
					//echo $Path_Route . "<br />";
					
					// Each Path Variable
					$id = 0;
					$Path_Variable_Count = 1;
					$Path_Variables = "";
					$Begin_Tag = "{";
					$End_Tag = "}";
					$path_variables_array = return_between($Path_Route, $Begin_Tag, $End_Tag, EXCL);

					$Path_Route = str_replace("{",":",$Path_Route);
					$Path_Route = str_replace("}","",$Path_Route);				
						
					// Each Path
					foreach($value as $key2 => $value2)
						{
							
						$Definition = "";
						$Path = "";
						$Path_Verb = $key2;
								
						$Path_Summary = $value2['summary'];
						$Path_Desc = $value2['description'];
						$Path_OperationID = $value2['operationId'];
						$Path_Parameters = $value2['parameters'];						
						$Path_Responses = $value2['responses'];		
						
						//echo $Path_Verb . "<br />";
						//echo $Path_Summary . "<br />";																								
						
						// Each Verb
						if($Path_Verb=="get")
							{
							
							$GET_Host = $Swagger_Host;
							$GET_Base = $Swagger_BasePath;
							$GET_Resource = $Path_Route;
							$GET_Parameters = $Path_Parameters;
							$GET_Responses = $Path_Responses;
							

							} 
						elseif($Path_Verb=="post")
							{

							$POST_Host = $Swagger_Host;
							$POST_Base = $Swagger_BasePath;
							$POST_Resource = $Path_Route;
							$POST_Resource = str_replace("/","",$POST_Resource);
							$POST_Parameters = $Path_Parameters;
							$POST_Responses = $Path_Responses;

							}
						elseif($Path_Verb=="put")
							{


							$PUT_Host = $Swagger_Host;
							$PUT_Base = $Swagger_BasePath;
							$PUT_Resource = $Path_Route;
							$PUT_Parameters = $Path_Parameters;			
							$PUT_Responses = $Path_Responses;

							}							
						elseif($Path_Verb=="delete")
							{
							
							$DELETE_Host = $Swagger_Host;
							$DELETE_Base = $Swagger_BasePath;
							$DELETE_Resource = $Path_Route;
							$DELETE_Parameters = $Path_Parameters;						  	
							$DELETE_Responses = $Path_Responses;
														
							}																											
																				

						}	
						
					 // end each Verb	
																				
					}
					
				// end each path										
						
			
				// End of Swagger
				echo "<br />";
				echo "GET_Host: " . $GET_Host . "<br />";
				echo "GET_Base: " . $GET_Base . "<br />";
				
				$GET_Resource_Array = explode(":",$GET_Resource);
				if(count($GET_Resource_Array)>1)
					{
					$GET_Resource = $GET_Resource_Array[0];
					$GET_Resource_Id = $GET_Resource_Array[1];	
					}
				else 
					{
					$GET_Resource_Id = "";					
					}
					
				$GET_Resource_Clean = str_replace("/","",$GET_Resource);	
				echo "GET_Resource_Count = " . count($GET_Resource_Array) . "<br />";				
				echo "GET_Resource: " . $GET_Resource . "<br />";
				
				echo "<br />GET_Params: <br />";
				var_dump($GET_Parameters);
								
				$GET_Definition = $GET_Responses['200']['schema']['items'][chr(36).'ref'];	
				$GET_Definition = str_replace("#/definitions/","",$GET_Definition);
				echo "<br />GET_Response: " . $GET_Definition . "<br />";
								
				echo "<br /><br />";
				echo "POST_Host: " . $POST_Host . "<br />";
				echo "POST_Base: " . $POST_Base . "<br />";
				
				$POST_Resource_Array = explode(":",$POST_Resource);
				if(count($POST_Resource_Array)>1)
					{
					$POST_Resource = $POST_Resource_Array[0];
					$POST_Resource_Id = $POST_Resource_Array[1];	
					}
				else 
					{
					$POST_Resource_Id = "";					
					}
				$POST_Resource_Clean = str_replace("/","",$POST_Resource);					
				echo "POST_Resource_Count = " . count($POST_Resource_Array) . "<br />";				
				echo "POST_Resource: " . $POST_Resource . "<br />";
				
				echo "<br />POST_Params: <br />";
				var_dump($POST_Parameters);

				$POST_Definition = $POST_Responses['200']['schema']['items'][chr(36).'ref'];
				$POST_Definition = str_replace("#/definitions/","",$POST_Definition);	
				echo "<br />POST_Response: " . $POST_Definition . "<br />";
			
				echo "<br /><br />";
				echo "PUT_Host: " . $PUT_Host . "<br />";
				echo "PUT_Base: " . $PUT_Base . "<br />";

				$PUT_Resource_Array = explode(":",$PUT_Resource);
				if(count($PUT_Resource_Array)>1)
					{
					$PUT_Resource = $PUT_Resource_Array[0];
					$PUT_Resource_Id = $PUT_Resource_Array[1];	
					}
				else 
					{
					$PUT_Resource_Id = "";					
					}		
				$PUT_Resource_Clean = str_replace("/","",$PUT_Resource);				
				echo "PUT_Resource_Count = " . count($PUT_Resource_Array) . "<br />";				
				echo "PUT_Resource: " . $PUT_Resource . "<br />";
				
				echo "<br />PUT_Params: <br />";
				var_dump($PUT_Parameters);	
				
				$PUT_Definition = $PUT_Responses['200']['schema']['items'][chr(36).'ref'];
				$PUT_Definition = str_replace("#/definitions/","",$PUT_Definition);	
				echo "<br />PUT_Response: " . $PUT_Definition . "<br />";
									
				echo "<br /><br />";
				echo "DELETE_Host: " . $DELETE_Host . "<br />";
				echo "DELETE_Base: " . $DELETE_Base . "<br />";
				
				$DELETE_Resource_Array = explode(":",$DELETE_Resource);
				if(count($DELETE_Resource_Array)>1)
					{
					$DELETE_Resource = $DELETE_Resource_Array[0];
					$DELETE_Resource_Id = $DELETE_Resource_Array[1];	
					}
				else 
					{
					$DELETE_Resource_Id = "";					
					}
				$DELETE_Resource_Clean = str_replace("/","",$DELETE_Resource);						
				echo "DELETE_Resource_Count = " . count($DELETE_Resource_Array) . "<br />";				
				echo "DELETE_Resource: " . $DELETE_Resource . "<br />";
				
				echo "DELETE_Params: <br />";
				var_dump($DELETE_Parameters);	

				$DELETE_Definition = $DELETE_Responses['200']['schema']['items'][chr(36).'ref'];
				$DELETE_Definition = str_replace("#/definitions/","",$DELETE_Definition);	
				echo "<br />DELETE_Response: " . $DELETE_Definition . "<br />";							
				
				echo "<br />Definitions: <br />";
				var_dump($Swagger_Definitions);
				
				//
				// Start Building JS File 
				//
				
				$JSFile = "";
				
				$JSFile .= '$WorkingResponse = "";' . chr(13);
				$JSFile .= '$resourcecount = 0;' . chr(13);
				$JSFile .= '$textEditors = "";' . chr(13) . chr(13);
				
				// Show Function
				$JSFile .= 'function ResourceShowme($row)' . chr(13);
				$JSFile .= chr(9) . '{' . chr(13);
				$JSFile .= chr(9) . chr(36) . 'thisrow = $row.id;' . chr(13);			
				$JSFile .= chr(9) . chr(36) . 'thisslug = $thisrow.replace("-icon","");' . chr(13);
					
				$JSFile .= chr(9) . chr(36) . 'thisrow = document.getElementById($thisslug).style.display;' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . 'if($thisrow==' . chr(39) . 'none' . chr(39) . ')' . chr(13);
				$JSFile .= chr(9) . chr(9) . '{' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'document.getElementById($thisslug).style.display = ' . chr(39) . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . chr(9) . '}' . chr(13);
				$JSFile .= chr(9) . 'else' . chr(13);
				$JSFile .= chr(9) . chr(9) . '{' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'document.getElementById($thisslug).style.display = ' . chr(39) . 'none' . chr(39) . ';' . chr(13);	
				$JSFile .= chr(9) . chr(9) . '}' . chr(13);		
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);	
					
				$JSFile .= 'function addResource(tinyInstance)' . chr(13);	
				$JSFile .= chr(9) . '{' . chr(13) . chr(13);	
		
				// LOOP THROUGH POST Parameters	- GET Field		
				foreach($POST_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}
					$JSFile .= chr(9) . chr(36) . 'resource_' . $param_name_clean . ' = document.getElementById("add-resource-' . $param_name_clean . '").value;' . chr(13);
					};
					
				$JSFile .= chr(13) . chr(9) . chr(36) . 'postData = {};' . chr(13) . chr(13);
					  
					// HOW DO WE HANDLE??  - API-CONFIG.JSON - STILL OPEN!! Better??
				$JSFile .= chr(9) . chr(36) . 'postData[' . chr(39) . 'appid' . chr(39) . '] = ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appid' . chr(39) . '];' . chr(13);
				$JSFile .= chr(9) . chr(36) . 'postData[' . chr(39) . 'appkey' . chr(39) . '] = ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appkey' . chr(39) . '];' . chr(13) . chr(13);
				
					// LOOP THROUGH POST Parameters
				foreach($POST_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}					
					$JSFile .= chr(9) . chr(36) . 'postData[' . chr(39) . $param_name . chr(39) . '] = ' . chr(36) . 'resource_' . $param_name . ';' . chr(13);
					}
						
					// POST_Host
				$JSFile .= chr(13) . chr(9) . chr(36) . 'hosturl = ' . chr(39) . 'http://' . $POST_Host . chr(39) . ';' . chr(13);
				
					// POST_Base
				$JSFile .= chr(9) . chr(36) . 'baseurl = ' . chr(39) . $POST_Base . chr(39) . ';' . chr(13);
					
					// POST_Resource
				$JSFile .= chr(9) . chr(36) . 'resource = ' . chr(39) . $POST_Resource . chr(39) . ';' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . chr(36) . 'apiurl = $hosturl + $baseurl + $resource;' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . chr(9) . chr(36) . '.ajax({' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'url: $apiurl,' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'type: ' . chr(39) . 'POST' . chr(39) . ',' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'data: $postData,' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'success: function(data) {' . chr(13) . chr(13);
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . 'WorkingResponse = data;' . chr(13);
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . 'ResourceCount = 0;' . chr(13) . chr(13);
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . '.each(data, function(resourceKey, resourceValue) {' . chr(13) . chr(13);
									
					// LOOP THROUGH DEFINITION PARAMETERS
				$All_Parameters = "";
				$First = 1;
				foreach($Swagger_Definitions[$POST_Resource]['properties'] as $key => $value)
					{
					$param_name	= $key;						
					if($First==1)
						{
						$All_Parameters .= chr(36) . 'resource_' . $param_name;
						$First++;
						}
					else	
						{
						$All_Parameters .= ',' . chr(36) . 'resource_' . $param_name;
						}														
					$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resource_' . $param_name . ' = resourceValue[' . chr(39) . $param_name . chr(39) . '];' . chr(13);
					}
				$All_Parameters .= ',' . chr(36) . 'resourcecount';	
					
					// LOOP THROUGH DEFINITION PARAMETERS			
				$JSFile .= chr(13) . chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'html = getResourceListing(' . $All_Parameters . ');' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#jsonResourceEditorTable' . chr(39) . ').append($html);' . chr(13) . chr(13);
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resourcecount++;' . chr(13) . chr(13);
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . '});' . chr(13) . chr(13);		
							
				$JSFile .= chr(9) . chr(9) . chr(9) . '}' . chr(13);
				$JSFile .= chr(9) . chr(9) . '});' . chr(13) . chr(13);			
					
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);		
					
				$JSFile .= 'function getAddResource()' . chr(13);
				$JSFile .= chr(9) . '{' . chr(13) . chr(13);		
						
				$JSFile .= chr(9) . 'html = ' . chr(39) . '<tr id="add-resource-post" style="display: none;"><td align="center" style="font-size: 12px; background-color:#CCC; padding:5px;">' . chr(39) . ';' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<span style="font-size: 18px;"><strong>Add ' . $POST_Resource . '</span></strong>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<table border="0" width="90%" cellpadding="3" cellspacing="2" id="resource-post-table">' . chr(39) . ';' . chr(13) . chr(13);
				    
					// LOOP THROUGH EACH POST_Parameters 
				foreach($POST_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}							
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<tr>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="right" width="5%"><strong>' . $param_name . ':</strong></td>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="left"><input type="text" id="add-resource-' . $param_name_clean . '" value="" style="width:95%;" /></td>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</tr>' . chr(39) . ';' . chr(13) . chr(13);
					}
					
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<tr>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="center" style="background-color:#FFF;" colspan="2"><input type="button" name="addAPIButton" value="Add" onclick="addResource();" /></td>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</tr>' . chr(39) . ';' . chr(13) . chr(13);        
				     
				$JSFile .= chr(9) . ' html = html + ' . chr(39) . '</table>' . chr(39) . ';' . chr(13) . chr(13);
				    
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<br /></td></tr>' . chr(39) . ';' . chr(13) . chr(13);  
				    	
				$JSFile .= chr(9) . 'return html;' . chr(13); 			
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);
					
				$JSFile .= 'function ConfirmDelete($resourcecount)' . chr(13); 
				$JSFile .= chr(9) . '{' . chr(13); 
				$JSFile .= chr(9) . 'if(confirm("Are you Sure?"))' . chr(13); 
				$JSFile .= chr(9) . chr(9) . '{' . chr(13); 
				$JSFile .= chr(9) . chr(9) . 'deleteResource($resourcecount);' . chr(13); 
				$JSFile .= chr(9) . chr(9) . '}' . chr(13); 
				$JSFile .= chr(9) . 'else{' . chr(13) . chr(13);
						
				$JSFile .= chr(9) . chr(9) . '}' . chr(13); 	
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);		
					
				$JSFile .= 'function deleteResource(' . chr(36) . 'resourcecount)' . chr(13);
				$JSFile .= chr(9) . '{' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . chr(36) . 'resource_slug = document.getElementById("edit-resource-slug-" + ' . chr(36) . 'resourcecount).value;' . chr(13) . chr(13);		
					
					// DELETE_Host
				$JSFile .= chr(13) . chr(9) . chr(36) . 'hosturl = ' . chr(39) . 'http://' . $DELETE_Host . chr(39) . ';' . chr(13);
				
					// DELETE_Base
				$JSFile .= chr(9) . chr(36) . 'baseurl = ' . chr(39) . $DELETE_Base . chr(39) . ';' . chr(13);
					
					// DELETE_Resource
				$JSFile .= chr(9) . chr(36) . 'resource = ' . chr(39) . $DELETE_Resource . chr(39) . ';' . chr(13) . chr(13);
				
					// HOW DO WE HANDLE??  - API-CONFIG.JSON
				$JSFile .= chr(9) . chr(36) . 'query = ' . chr(39) . '?appid=' . chr(39) . ' + ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appid' . chr(39) . ']' . ';' . chr(13);
				$JSFile .= chr(9) . chr(36) . 'query = ' . chr(36) . 'query + ' . chr(39) . '&appkey=' . chr(39) . ' + ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appkey' . chr(39) . ']' . ';' . chr(13) . chr(13);					
					
					// HOW ARE WE GOING TO DO IDs ??	
				$JSFile .= chr(9) . chr(36) . 'apiurl = ' . chr(36) . 'hosturl + ' . chr(36) . 'baseurl + ' . chr(36) . 'resource + ' . chr(36) . 'resource_slug + ' . chr(39) . '/' . chr(39) . ' + ' . chr(36) . 'query;' . chr(13) . chr(13);	
				
				$JSFile .= chr(9) . chr(36) . '.ajax({' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'url: $apiurl,' . chr(13);   
				$JSFile .= chr(9) . chr(9) . 'type: ' . chr(39) . 'DELETE' . chr(39) . ',' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'success: function(data) {' . chr(13) . chr(13);	
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount).remove();' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#add-resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount).remove();' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#edit-resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount).remove();' . chr(13) . chr(13);	
					        										
				$JSFile .= chr(9) . chr(9) . chr(9) . '}' . chr(13);
				$JSFile .= chr(9) . chr(9) . '});' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . 'function editResource($resourcecount)' . chr(13);
				$JSFile .= chr(9) . chr(9) . '{' . chr(13) . chr(13);	
					
					// LOOP THROUGH PUT Parameters
				foreach($PUT_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}					
					$JSFile .= chr(9) . chr(9) . chr(36) . 'resource_' . $param_name . ' = document.getElementById("edit-resource-' . $param_name . '-" + ' . chr(36) . 'resourcecount).value;' . chr(13);		
					}
					
				$JSFile .= chr(13) . chr(9) . chr(9) . chr(36) . 'postData = {};' . chr(13) . chr(13);	
					
					// HOW DO WE HANDLE??  - API-CONFIG.JSON - STILL OPEN!! Better??
				$JSFile .= chr(9) . chr(9) . chr(36) . 'postData[' . chr(39) . 'appid' . chr(39) . '] = ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appid' . chr(39) . '];' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(36) . 'postData[' . chr(39) . 'appkey' . chr(39) . '] = ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appkey' . chr(39) . '];' . chr(13) . chr(13);
				
					// LOOP THROUGH PUT Parameters
				foreach($PUT_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}					
					$JSFile .= chr(9) . chr(9) . chr(36) . 'postData[' . chr(39) . $param_name . chr(39) . '] = $resource_' . $param_name_clean . ';' . chr(13);	
					}
						
					// PUT_Host
				$JSFile .= chr(13) . chr(9) . chr(9) . chr(36) . 'hosturl = ' . chr(39) . 'http://' . $PUT_Host . chr(39) . ';' . chr(13);
				
					// PUT_Base
				$JSFile .= chr(9) . chr(9) . chr(36) . 'baseurl = ' . chr(39) . $PUT_Base . chr(39) . ';' . chr(13);
					
					// PUT_Resource
				$JSFile .= chr(9) . chr(9) . chr(36) . 'resource = ' . chr(39) . $PUT_Resource . chr(39) . ';' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . chr(9) . chr(36) . 'apiurl = ' . chr(36) . 'hosturl + ' . chr(36) . 'baseurl + ' . chr(36) . 'resource + ' . chr(36) . 'resource_slug;' . chr(13) . chr(13);	
				
				$JSFile .= chr(9) . chr(9) . chr(36) . '.ajax({' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . 'url: $apiurl,' . chr(13); 
				$JSFile .= chr(9) . chr(9) . chr(9) . 'type: ' . chr(39) . 'PUT' . chr(39) . ',' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . 'data: $postData,' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . 'success: function(data) {' . chr(13) . chr(13);	
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'WorkingResponse = data;' . chr(13) . chr(13);	
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resourcecount = 0;' . chr(13) . chr(13);	
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . '.each(data, function(resourceKey, resourceValue) {' . chr(13) . chr(13);	
								
					// LOOP THROUGH DEFINITION PARAMETERS	
				$All_Parameters = "";
				$First = 1;
				foreach($Swagger_Definitions[$PUT_Resource_Clean]['properties'] as $key => $value)
					{
					$param_name	= $key;						
					if($First==1)
						{
						$All_Parameters .= chr(36) . 'resource_' . $param_name;
						$First++;
						}
					else	
						{
						$All_Parameters .= ',' . chr(36) . 'resource_' . $param_name;
						}										
					$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resource_' . $param_name . ' = resourceValue[' . chr(39) . $param_name . chr(39) . '];' . chr(13);	
					}
					
				$JSFile .= chr(13) . chr(9) . chr(9) . chr(9) . chr(9) . chr(9) . '});' . chr(13) . chr(13);	
											
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . '}' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . '});' . chr(13) . chr(13);		
					
				$JSFile .= chr(9) . chr(9) . '}' . chr(13) . chr(13);		
				
					// LOOP THROUGH DEFINITION - ALL PARAMETERS		
				$JSFile .= 'function getEditResource(' . $All_Parameters . ',$resourcecount)' . chr(13);
				$JSFile .= chr(9) . chr(9) . '{' . chr(13) . chr(13);				
						
				$JSFile .= chr(9) . 'html = ' . chr(39) . '<tr id="edit-resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '" style="display: none;"><td align="center" style="font-size: 12px; background-color:#CCC; padding:5px;">' . chr(39) . ';' . chr(13) . chr(13);		
				
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<span style="font-size: 18px;"><strong>Edit Resource</span></strong>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<table border="0" width="90%" cellpadding="3" cellspacing="2" id="resource-post-table">' . chr(39) . ';' . chr(13) . chr(13);	
				    
					// LOOP THROUGH PUT Parameters
				$All_Parameters = "";
				$First = 1;					
				foreach($PUT_Parameters as $param)
					{					
					$param_name = $param['name'];
					$param_name_clean = strtolower($param_name);
					$param_in = $param['in'];
					$param_description = $param['description'];
					$param_required = $param['required'];
					
					$param_type = "";
					if(isset($param['type']))
						{					
						$param_type = $param['type'];
						}
					$param_format = "";	
					if(isset($param['format']))
						{			
						$param_format = $param['format'];
						}
					if($First==1)
						{
						$All_Parameters .= chr(36) . $param_name;
						$First++;
						}
					else	
						{
						$All_Parameters .= ',' . chr(36) . $param_name;
						}											
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<tr>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="right" width="5%"><strong>' . $param_name . ':</strong></td>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="left"><input type="text" id="edit-resource-' . $param_name_clean . '-' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '" value="' . chr(39) . ' + ' . chr(36) . 'resource_' . $param_name . ' + ' . chr(39) . '" style="width:95%;" /></td>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</tr>' . chr(39) . ';' . chr(13) . chr(13);			     
					}
  
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<tr>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td align="center" colspan="2"><input type="button" name="editAPIButton" value="Save" onclick="editResource(' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . ')" /></td>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</tr>' . chr(39) . ';' . chr(13) . chr(13);	         
				     
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</table>' . chr(39) . ';' . chr(13) . chr(13);	
				    
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<br /></td></tr>' . chr(39) . ';' . chr(13) . chr(13);	 
				    	
				$JSFile .= chr(9) . 'return html;' . chr(13) . chr(13);	 			
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);			
					
				// LOOP THROUGH PUT Parameters	
				$JSFile .= 'function getResourceListing(' . $All_Parameters . ',$resourcecount)' . chr(13);
				$JSFile .= chr(9) . '{' . chr(13) . chr(13);	
						
				$JSFile .= chr(9) . 'html = ' . chr(39) . '<tr id="resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '">' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<td style="padding-top: 5px; padding-bottom: 5px;">' . chr(39) . ';' . chr(13) . chr(13);		
				
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<a href="#" onclick="ConfirmDelete(' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '); return false;" id="delete-resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '-icon" title="Delete Resource Post"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-delete-circle.png" width="35" align="right"  /></a>' . chr(39) . ';' . chr(13);		
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<a href="#" onclick="ResourceShowme(this); return false;" id="edit-resource-post-' . chr(39) . ' + ' . chr(36) . 'resourcecount + ' . chr(39) . '-icon" title="Edit Resource Post"><img src="https://s3.amazonaws.com/kinlane-productions/bw-icons/bw-edit-circle.png" width="35" align="right"  /></a>' . chr(39) . ';' . chr(13) . chr(13);		
					
				// LOOP THROUGH DEFINITION PARAMETERS (label)
				$All_Parameters = "";
				$First = 1;				
				foreach($Swagger_Definitions[$PUT_Resource_Clean]['properties'] as $key => $value)
					{
					$param_name	= $key;				
					if($First==1)
						{
						$All_Parameters .= chr(36) . $param_name;
						$First++;
						}
					else	
						{
						$All_Parameters .= ',' . chr(36) . $param_name;
						}				
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<span style="font-size:20px;">' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '<strong>' . chr(39) . ' + ' . chr(36) . 'resource_' . $param_name . ' + ' . chr(39) . '</strong>' . chr(39) . ';' . chr(13);
					$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</span>' . chr(39) . ';' . chr(13) . chr(13);	
					}
						
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</td>' . chr(39) . ';' . chr(13);
				$JSFile .= chr(9) . 'html = html + ' . chr(39) . '</tr>' . chr(39) . ';' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . 'return html; ' . chr(13) . chr(13);	
								
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);		
				
				$JSFile .= 'function loadResourceEditor()' . chr(13);
				$JSFile .= chr(9) . '{' . chr(13) . chr(13);	
				
				$JSFile .= chr(9) . chr(36) . 'response = "";' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . chr(36) . 'html = getAddResource();' . chr(13);
				$JSFile .= chr(9) . chr(36) . '(' . chr(39) . '#jsonResourceEditorTable' . chr(39) . ').append($html); ' . chr(13);
				$JSFile .= chr(9) . chr(36) . 'textEditors = "add-resource-post";' . chr(13) . chr(13);	
					
					// GET_Host
				$JSFile .= chr(13) . chr(9) . chr(36) . 'hosturl = ' . chr(39) . 'http://' . $GET_Host . chr(39) . ';' . chr(13);
				
					// GET_Base
				$JSFile .= chr(9) . chr(36) . 'baseurl = ' . chr(39) . $GET_Base . chr(39) . ';' . chr(13);
					
					// GET_Resource
				$JSFile .= chr(9) . chr(36) . 'resource = ' . chr(39) . $GET_Resource . chr(39) . ';' . chr(13) . chr(13);
				
					// HOW DO WE HANDLE??  - API-CONFIG.JSON
				$JSFile .= chr(9) . chr(36) . 'query = ' . chr(39) . '?appid=' . chr(39) . ' + ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appid' . chr(39) . '];' . chr(13);
				$JSFile .= chr(9) . chr(36) . 'query = ' . chr(36) . 'query + ' . chr(39) . '&appkey=' . chr(39) . ' + ' . chr(36) . 'APIConfig[' . chr(39) . '3Scale' . chr(39) . '][' . chr(39) . 'appkey' . chr(39) . '];' . chr(13) . chr(13);					
					
				$JSFile .= chr(9) . chr(36) . 'apiurl = ' . chr(36) . 'hosturl + ' . chr(36) . 'baseurl + ' . chr(36) . 'resource + ' . chr(36) . 'query;' . chr(13) . chr(13);	
					
				$JSFile .= chr(9) . chr(36) . '.ajax({' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'url: $apiurl,' . chr(13);   
				$JSFile .= chr(9) . chr(9) . 'type: ' . chr(39) . 'GET' . chr(39) . ',' . chr(13);
				$JSFile .= chr(9) . chr(9) . 'success: function(data) {' . chr(13) . chr(13);	
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . 'WorkingResponse = data;' . chr(13) . chr(13);	
							
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(36) . '.each(data, function(resourceKey, resourceValue) {' . chr(13) . chr(13);	
								
				// LOOP THROUGH DEFINITION PARAMETERS (label)
				$All_Parameters = "";
				$First = 1;				
				foreach($Swagger_Definitions[$GET_Resource_Clean]['properties'] as $key => $value)
					{
					$param_name	= $key;				
					if($First==1)
						{
						$All_Parameters .= chr(36) . 'resource_' . $param_name;
						$First++;
						}
					else	
						{
						$All_Parameters .= ',' . chr(36) . 'resource_' . $param_name;
						}				
					$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resource_' . $param_name . ' = resourceValue[' . chr(39) . $param_name . chr(39) . '];' . chr(13);	
					}				
								
				$JSFile .= chr(13) . chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'html = getResourceListing(' . $All_Parameters . ',$resourcecount);' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#jsonResourceEditorTable' . chr(39) . ').append($html); ' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'html = getEditResource($resource_name,$resource_description,$resource_url,$resource_tags,$resource_slug,$resourcecount)' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . '(' . chr(39) . '#jsonResourceEditorTable' . chr(39) . ').append($html);' . chr(13) . chr(13);
				
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'textEditors = $textEditors + ",edit-resource-post-" + $resourcecount;' . chr(13) . chr(13);	
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . chr(36) . 'resourcecount++;' . chr(13) . chr(13);	
								
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . '});' . chr(13) . chr(13);	
				
				$JSFile .= chr(9) . chr(9) . chr(9) . 'tinyMCE.init({' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'mode : "textareas",' . chr(13);		
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme : "advanced",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'plugins : "spellchecker,pagebreak,layer,table,advhr,advimage,autosave,advlist,advlink,inlinepopups,insertdatetime,preview,media,contextmenu,paste,nonbreaking",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_buttons1 : "save,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,code,|,hr,|,spellchecker",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_buttons2 : "",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_buttons3 : "",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_toolbar_location : "top",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_toolbar_align : "left",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'theme_advanced_statusbar_location : "bottom",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'extended_valid_elements : "iframe[src|width|height|name|align]",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'width : "550px",' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . chr(9) . 'height : "300px"' . chr(13);
				$JSFile .= chr(9) . chr(9) . chr(9) . '});' . chr(13) . chr(13);		
									
				$JSFile .= chr(9) . chr(9) . chr(9) . '}' . chr(13);
				$JSFile .= chr(9) . chr(9) . '});' . chr(13) . chr(13);			
				
				$JSFile .= chr(9) . '}' . chr(13) . chr(13);					
				
				// End Building JS File
				
				?><br /><textarea name="showme" cols="150" rows="50"><?php echo $JSFile; ?></textarea><?php
				
				}
										
			// end each API Property (IN APIS.json - Now Swagger Yet)			
			
			}				
			
		// end of each API in APIs.json
		
		// [ PUT THE JS FILE SCRIPT HERE]					
			
		}	


	});	
	
$route = '/utility/api/client-php/rebuild/';	
$app->get($route, function ()  use ($app,$githuborg,$githubrepo,$gclient){
	
	$ref = "gh-pages";
	$APIsJSONURL = "https://raw.github.com/" . $githuborg . "/" . $githubrepo . "/gh-pages/apis.json";
	
	$Resource_Store_File = "apis.json";
	
	echo $Resource_Store_File . "<br />";
	
	$CheckFile = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $Resource_Store_File);
	$APIsJSONContent = base64_decode($CheckFile->getcontent());	

	$APIsJSON = json_decode($APIsJSONContent,true);

	foreach($APIsJSON['apis'] as $APIsJSON)
		{
		$properties = $APIsJSON['properties'];	
		foreach($properties as $property)
			{
			$property_type = $property['type'];
			if(strtolower($property_type)=="swagger")
				{
				$swagger_url = $property['url'];		
				echo $property_type . " - " . $swagger_url . "<br />";
				
				$cleanbase = "https://github.com/" . $githuborg . "/" . $githubrepo . "/blame/" . $ref . "/";
				$swagger_path = str_replace($cleanbase,"",$swagger_url); 

				$PullSwagger = $gclient->repos->contents->getContents($githuborg, $githubrepo, $ref, $swagger_path);
				$SwaggerJSON = base64_decode($PullSwagger->getcontent());						
				
				$Swagger = json_decode($SwaggerJSON,true);	
				
				$Swagger_Title = $Swagger['info']['title'];
				$Swagger_Description = $Swagger['info']['description'];
				$Swagger_TOS = $Swagger['info']['termsOfService'];
				$Swagger_Version = $Swagger['info']['version'];
				
				$Swagger_Host = $Swagger['host'];
				$Swagger_BasePath = $Swagger['basePath'];
				
				$Swagger_Scheme = $Swagger['schemes'][0];
				$Swagger_Produces = $Swagger['produces'][0];
				
				echo $Swagger_Title . "<br />";

				$Method = "";
				$Method .= "<?php" . chr(13);			
					
				$Swagger_Definitions = $Swagger['definitions'];	
					
				$Swagger_Paths = $Swagger['paths'];				
				foreach($Swagger_Paths as $key => $value)
					{
						
					$Path_Route = $key;
					echo $Path_Route . "<br />";
					
					// Each Path Variable
					$id = 0;
					$Path_Variable_Count = 1;
					$Path_Variables = "";
					$Begin_Tag = "{";
					$End_Tag = "}";
					$path_variables_array = return_between($Path_Route, $Begin_Tag, $End_Tag, EXCL);

					$Path_Route = str_replace("{",":",$Path_Route);
					$Path_Route = str_replace("}","",$Path_Route);				
						
					// Each Path
					foreach($value as $key2 => $value2)
						{
							
						$Definition = "";
						$Path = "";
						$Path_Verb = $key2;
								
						$Path_Summary = $value2['summary'];
						$Path_Desc = $value2['description'];
						$Path_OperationID = $value2['operationId'];
						$Path_Parameters = $value2['parameters'];		
						
						echo $Path_Verb . "<br />";
						echo $Path_Summary . "<br />";																								
						
						// Each Verb
						if($Path_Verb=="get")
							{
							

							} 
						elseif($Path_Verb=="post")
							{



							}
						elseif($Path_Verb=="put")
							{
						


							}							
						elseif($Path_Verb=="delete")
							{
							
						  	
														
							}																											
																				
						
						
						
						$Method .= $Path;
						}															
					}
					
				echo "<hr />";
				
				echo "--definitions--<br />";
				foreach($Swagger_Definitions as $key => $value)
					{											
					echo $key . "<br />";	
					$Definition_Properties = $value['properties'];	
					foreach($Definition_Properties as $key4 => $value4)
						{
						$Definition_Property_Name = $key4;
						echo $Definition_Property_Name . "<br />";
						
						if(isset($value4['description'])){ $Definition_Property_Desc = $value4['description']; } else { $Definition_Property_Desc = ""; }
						if(isset($value4['type'])){ $Definition_Property_Type = $value4['type']; } else { $Definition_Property_Type = ""; }
						if(isset($value4['format'])){ $Definition_Property_Format = $value4['format']; } else { $Definition_Property_Format = ""; }
						
						echo $Definition_Property_Type . "<br />";
						echo $Definition_Property_Desc . "<br />";		
						}						
					//var_dump($value);
					echo "<hr />";											
					}				
					
				}
											
	
			}	
		}	


	});	
	
?>