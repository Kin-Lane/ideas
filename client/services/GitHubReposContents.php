<?php

require_once(__DIR__ . '/../GitHubClient.php');
require_once(__DIR__ . '/../GitHubService.php');
require_once(__DIR__ . '/../objects/GitHubReadmeContent.php');
	

class GitHubReposContents extends GitHubService
{

	/**
	 * Get the README
	 * 
	 * @param $ref string (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @return GitHubReadmeContent
	 */
	public function getTheReadme($owner, $repo, $ref = null)
	{
		$data = array();
		if(!is_null($ref))
			$data['ref'] = $ref;
		
		return $this->client->request("/repos/$owner/$repo/readme", 'GET', $data, 200, 'GitHubReadmeContent');
	}
	
	/**
	 * Get a file
	 * 
	 * @param $ref string (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @param $path string  (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @return GitHubReadmeContent
	 */
	public function getContents($owner, $repo, $ref = null, $path = null)
	{
		$data = array();
		if(!is_null($ref))
			$data['ref'] = $ref;	
		
		return $this->client->request("/repos/$owner/$repo/contents/" . $path, 'GET', $data, 200, 'GitHubReadmeContent');
	}

	/**
	 * Get a file
	 * 
	 * @param $ref string (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @param $path string  (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @return GitHubReadmeContent
	 */
	public function updateFile($owner, $repo, $path = null, $message = null, $content = null, $sha = null, $branch = null)
	{
		$data = array();
		if(!is_null($message))
			$data['message'] = $message;	
		if(!is_null($content))
			$data['content'] = $content;
		if(!is_null($sha))
			$data['sha'] = $sha;
		if(!is_null($branch))
			$data['branch'] = $branch;							
		
		return $this->client->request("/repos/$owner/$repo/contents/" . $path, 'PUT', $data, 200, 'GitHubReadmeContent');
	}	
	
	/**
	 * Get a file
	 * 
	 * @param $ref string (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @param $path string  (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @return GitHubReadmeContent
	 */
	public function createFile($owner, $repo, $path = null, $message = null, $content = null, $branch = null)
	{
		$data = array();
		if(!is_null($message))
			$data['message'] = $message;	
		if(!is_null($content))
			$data['content'] = $content;
		if(!is_null($branch))
			$data['branch'] = $branch;							
		
		return $this->client->request("/repos/$owner/$repo/contents/" . $path, 'PUT', $data, 201, 'GitHubReadmeContent');
	}	
	
	/**
	 * Get a file
	 * 
	 * @param $ref string (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @param $path string  (Optional) - The String name of the Commit/Branch/Tag.  Defaults to `master`.
	 * @return GitHubReadmeContent
	 */
	public function deleteFile($owner, $repo, $path = null, $message = null, $sha = null, $branch = null)
	{
		$data = array();
		if(!is_null($message))
			$data['message'] = $message;	
		if(!is_null($sha))
			$data['sha'] = $sha;	
		if(!is_null($branch))
			$data['branch'] = $branch;			
		
		//$data['committer'] = array();	
		//$committer = array();
		//$committer['name'] = "Kin Lane";
		//$committer['email'] = "kinlane@gmail.com";	
		//$data['committer'] = $committer;														
		
		return $this->client->request("/repos/$owner/$repo/contents/" . $path, 'DELETE', $data, 200, '');
	}		
	
}

