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
    $dataWriter = new DataWriter("reports/transfer/" . date("YmdHi") . ".md");

	$dataWriter->outputData(date("Ymd H:i") . "  \n");
    
    $OATcentral = unserialize(file_get_contents("access/TAO.jix"));
    $BTPARCCADS = unserialize(file_get_contents("access/ADS.jix"));
    $ACTWKP = unserialize(file_get_contents("access/WTP.jix"));
    $NCCER = unserialize(file_get_contents("access/NCCER.jix"));
    $MPFAA = unserialize(file_get_contents("access/FAA.jix"));
    $MPFY2 = unserialize(file_get_contents("access/FY2.jix"));
    $PCG01 = unserialize(file_get_contents("access/PCG01.jix"));

    transferData($ACTWKP, $OATcentral, 'WK', $dataWriter);
    transferData($NCCER, $OATcentral, 'NCCER', $dataWriter);
    transferData($MPFY2, $OATcentral, 'FY2', $dataWriter);
    //transferData($PCG01, $OATcentral, 'PCG');
    //transferData($BTPARCCADS, $OATcentral, 'ADS', $dataWriter);
}

function transferData(JiraInstance $from, JiraInstance $to, $targetRef, $dataWriter, $interactive = false)
{
    $dataWriter->outputData("\n");
    $dataWriter->outputData("from: " . $from->getUrl() . "  \n");
    $dataWriter->outputData("to  : " . $to->getUrl() . "  \n");
    $dataWriter->outputData("\n");
    
    //grab data from the fetch system
    $fch = defaultJiraCurlOptions();

    curl_setopt($fch, CURLOPT_USERPWD, $from->getUserPswd());
    curl_setopt($fch, CURLOPT_URL, $from->getUrl() . $from->getApiPath() . $from->getQuery());

    // grab URL and pass it to the browser
    $data = curl_exec($fch);
    $error = curl_error($fch);

    if($error)
    {
        $dataWriter->outputData("Curl error: " . $error . "  \n");
    }
    else
    {
        $dataWriter->outputData("Operation completed without any errors  \n");
    }

    // close cURL resource, and free up system resources
    curl_close($fch);
    
    //we should have everything now
    //run through it and prepare for import
    $jsondata = json_decode($data);
    $issues = $jsondata->issues;

    foreach ($issues as $issue)
    {        
        $identifier = str_replace($from->getProjectKey(), $targetRef, $issue->key);

        $cchb = defaultJiraCurlOptions();
        curl_setopt($cchb, CURLOPT_USERPWD, $to->getUserPswd());

        $dupesearch = 'search?jql=summary%20~%20' . $identifier;
        curl_setopt($cchb, CURLOPT_URL, $to->getUrl() . $to->getApiPath() . $dupesearch);

        $duplicates = curl_exec($cchb);
        curl_close($cchb);
        
        //$attachments = getAttachmentFromJiraIssue($from, $issue->id);
        
        //file_put_contents("ItemDump" . "\\" . $issue->key . "-issue.zip", $data);
        //file_put_contents("ItemDump" . "\\" . $issue->key . "-attach.zip", $attachments);

        if (json_decode($duplicates)->total == 0)
        {

			$issueReport = issueReportLineCustom($issue, $from, NULL, $to, "Import  \n");
			$dataWriter->outputData($issueReport . "\n");
 
			if ($interactive)
			{
            	$handle = fopen ("php://stdin","r");
            	$line = fgets($handle);
			}
            
            if(!$interactive || trim($line) == 'y')
			{
				$new_issue = createDuplicateIssue($issue, $from, $identifier, $to);

				uploadIssue($new_issue, $to);
            }
        }
        else
        {
            $duplicates = json_decode($duplicates);
            $duplicates = $duplicates->issues;
            
			if (count($duplicates) > 1)
			{
				$dataWriter->outputData(" warning, " . count($duplicates) . " duplicates:  \n");
			}

            foreach ($duplicates as $duplicate) 
            {   
				$issueReport = issueReportCompareStatus($issue, $from, $duplicate, $to);
				$dataWriter->outputData($issueReport . "  \n");
            }
        }
    }
}

function getAttachmentFromJiraIssue(JiraInstance $from, $issueID)
{
    //grab data from the fetch system
    $fch = defaultJiraCurlOptions();

    curl_setopt($fch, CURLOPT_USERPWD, $from->getUserPswd());
    curl_setopt($fch, CURLOPT_URL, $from->getUrl() . "/secure/attachmentzip/" . $issueID . ".zip");

    // grab URL and pass it to the browser
    $data = curl_exec($fch);
    $error = curl_error($fch);
    
    if($error)
    {
        //echo "Curl error: " . $error . "\n";
        //echo $from->getUrl() . "secure/attachmentzip/" . $issueID . ".zip";
        $data = null;
    }
    else
    {
        //echo "Got attachment\n";
    }

    // close cURL resource, and free up system resources
    curl_close($fch);
    
    return $data;
}

function uploadIssue($new_issue, $to)
{
    $cch = defaultJiraCurlOptions();
    curl_setopt($cch, CURLOPT_USERPWD, $to->getUserPswd());
    curl_setopt($cch, CURLOPT_URL, $to->getUrl() . $to->getApiPath() . $to->getIssuePath());

    $encoded = json_encode($new_issue);

    //var_dump($encoded);

    curl_setopt($cch, CURLOPT_POSTFIELDS, $encoded);

    //var_dump($cch);

    // grab URL and pass it to the browser
    $data = curl_exec($cch);
    $error = curl_error($cch);
    
    // close cURL resource, and free up system resources
	curl_close($cch);
}

function createDuplicateIssue($frissue, $from, $id, $to)
{
	$new_issue = array(
    	'fields' => array(
        	'project' => array('key' => 'TAO'),
            'summary' => $id . ' ' . $frissue->fields->summary,
            'description' => $from->getUrl() . $from->getBrowsePath() . $frissue->key . "\n\n" . $frissue->fields->description,
            'issuetype' => array('name' => $frissue->fields->issuetype->name)
        )
	);

	return $new_issue;
}
