<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class JiraInstance
{
    protected $projectName;
    protected $url;
    
    protected $apiPath = 'rest/api/2/';
    protected $browsePath = 'browse/';
    protected $issuePath = 'issue/';
    
    protected $searchQuery;
    
    protected $projectKey;
    protected $userpswd;
    
    protected $pointsFieldName;
    protected $reportQueries;
    
    public function __construct($projectName, $url, $apiPath, $browsePath, $issuePath, $searchQuery, $projectKey, $userpswd, $pointsFieldName, $reportQueries) 
    {
        $this->projectName = $projectName;
        $this->url = $url;
        
        $this->apiPath = ($apiPath == NULL) ? $this->apiPath : $apiPath;
        $this->browsePath = ($browsePath == NULL) ? $this->browsePath : $browsePath;
        $this->issuePath = ($issuePath == NULL) ? $this->issuePath : $issuePath;
        
        $this->searchQuery = $searchQuery;
        
        $this->projectKey = $projectKey;
        $this->userpswd = $userpswd;
        
        $this->userpswd = $userpswd;
        
        $this->pointsFieldName = $pointsFieldName;
        $this->reportQueries = $reportQueries;
    }
    
    public function getProjectName()
    {
        return $this->projectName;
    }
    public function setProjectName($ProjectName)
    {
        $this->projectName = $ProjectName;
    }
    
    public function getUrl()
    {
        return $this->url;
    }
    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    public function getProjectKey()
    {
        return $this->projectKey;
    }
    public function setProjectKey($projectKey)
    {
        $this->projectKey = $projectKey;
    }
    
    public function getUserPswd()
    {
        return $this->userpswd;
    }
    public function setUserPswd($userpswd)
    {
        $this->userpswd = $userpswd;
    }
    
    public function getQuery()
    {
        return $this->searchQuery;
    }
    public function setQuery($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    }
    
    public function getBrowsePath()
    {
        return $this->browsePath;
    }
    public function setBrowsePath($browsePath)
    {
        $this->browsePath = $browsePath;
    }
    
    public function getIssuePath()
    {
        return $this->issuePath;
    }
    public function setIssuePath($issuePath)
    {
        $this->issuePath = $issuePath;
    }
    
    public function getApiPath()
    {
        return $this->apiPath;
    }
    public function setApiPath($apiPath)
    {
        $this->apiPath = $apiPath;
    }
    
    public function getPointsFieldName()
    {
        return $this->pointsFieldName;
    }
    public function setPointsFieldName($pointsFieldName)
    {
        $this->pointsFieldName = $pointsFieldName;
    }
    
    public function getReportQueries()
    {
        return $this->reportQueries;
    }
    public function setReportQueries($reportQueries)
    {
        $this->reportQueries = $reportQueries;
    }
}

function defaultJiraCurlOptions()
{
    // create a new cURL resource
    $ch = curl_init();

    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json'
    );


    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    //certification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/-.atlassian.net.crt");

    //curl_setopt($ch, CURLOPT_VERBOSE, true);

    return $ch;
}

function executeQuery($targetInstance)
{
    //grab data from the fetch system
    $fch = defaultJiraCurlOptions();

    curl_setopt($fch, CURLOPT_USERPWD, $targetInstance->getUserPswd());
    curl_setopt($fch, CURLOPT_URL, $targetInstance->getUrl() . $targetInstance->getApiPath() . $targetInstance->getQuery());

    //echo("using: " . $targetInstance->getUrl() . $targetInstance->getApiPath() . $targetInstance->getQuery() . "\n");
    
    // grab URL and pass it to the browser
    $data = curl_exec($fch);
    $error = curl_error($fch);
    
    // close cURL resource, and free up system resources
    curl_close($fch);

    if($error)
    {
        echo "Curl error: " . $error . "\n";
        return NULL;
    }
    else
    {
        //echo "Operation completed without any errors\n";
    }

    return $data;
}
