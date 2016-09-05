<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'jirainstance.php';
require_once 'utilities.php';

updateJira();

function updateJira()
{
    $OATcentral = unserialize(file_get_contents("access\\TAO.jix"));
//    $BTPARCCADS = unserialize(file_get_contents("access\\ADS.jix"));
//    $BTPARCCACR = unserialize(file_get_contents("access\\PARADS.jix"));
    $ACTWKP = unserialize(file_get_contents("access\\WTP.jix"));
    $MPFAA = unserialize(file_get_contents("access\\FAA.jix"));

    //transferData($BTPARCCACR, $OATcentral, 'BT');
    //transferData($BTPARCCADS, $OATcentral, 'ADS');
    $targetDirectory = "archive\\" . date("Ymd");
    mkdir($targetDirectory, 0777, true);
    archiveData($OATcentral, $targetDirectory);
    archiveData($ACTWKP, $targetDirectory);
    archiveData($MPFAA, $targetDirectory);
}

function archiveData(JiraInstance $toArchive, $targetDirectory)
{
    $targetDirectory = $targetDirectory . "\\" . $toArchive->getProjectKey();
    mkdir($targetDirectory , 0777, true);
    
    $startSprint = 1;
    $nextSprint = 2; 
    //there's no good way to find all the sprint numbers so we'll assume for now that 3 empty sprints in a row means we've found them all
    while ($nextSprint - $startSprint < 3) 
    {        
        $toArchive->setQuery("search?jql=status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $startSprint . "%20AND%20Sprint%20!%3D%20" . $nextSprint . "&maxResults=500");

        //grab data from the fetch system
        $fch = defaultJiraCurlOptions();

        curl_setopt($fch, CURLOPT_USERPWD, $toArchive->getUserPswd());
        curl_setopt($fch, CURLOPT_URL, $toArchive->getUrl() . $toArchive->getApiPath() . $toArchive->getQuery());

        // grab URL and pass it to the browser
        $data = curl_exec($fch);
        $error = curl_error($fch);

        if($error)
        {
            echo "Curl error: " . $error . "\n";
            echo $toArchive->getUrl() . $toArchive->getApiPath() . $toArchive->getQuery(). "\n";
            $nextSprint++;
        }
        else
        {
            echo "Operation completed without any errors\n";
            
            //we should have everything now
            //run through it and prepare for import
            $jsondata = json_decode($data);
            //$emptySprints = $jsondata->total > 0 ? 0 : $emptySprints + 1;
                        
            echo $jsondata->total . " issues to save\n";
            
            file_put_contents($targetDirectory . "\\" . "Sprint" . sprintf("%03d", $startSprint) . "-" . sprintf("%03d", $nextSprint) . ".jip", $data);
            
            $startSprint = $nextSprint++;
        }

        // close cURL resource, and free up system resources
        curl_close($fch);
    }
}

function archiveSprint()
{
    
}