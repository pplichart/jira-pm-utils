<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the te
 * mplate in the editor.
 */

require_once 'jirainstance.php';
require_once 'utilities.php';

beginHealthCheck();

function beginHealthCheck()
{
    $dataWriter = new DataWriter("reports/health/" . date("Ymd") . ".md");
	
	$dataWriter->outputData(date("Ymd H:i") . "  \n");
    
    $OATcentral = unserialize(file_get_contents("access/TAO.jix"));
    $targetInstances = array(
        unserialize(file_get_contents("access/ADS.jix")),
        unserialize(file_get_contents("access/WTP.jix")),
        unserialize(file_get_contents("access/FAA.jix")));
    
    $dataWriter->outputData("# search for external issues in the backlog\n");
    foreach ($targetInstances as $targetInstance)
    {
        findInBacklog($OATcentral, $targetInstance, $dataWriter);
        $dataWriter->outputData("- - - - -  \n");
    }
    
    $dataWriter->outputData("# search for unpointed  \n");
    findUnpointed($OATcentral, $dataWriter);
    
    $dataWriter->outputData("# search for pointed  \n");
    findPointedBugs($OATcentral, $dataWriter);
    
    $dataWriter->outputData("# search for non standard reporter  \n");
    findNonStandardReporter($OATcentral, $dataWriter);
    
    $dataWriter->outputData("# orphan search  \n");
    foreach ($targetInstances as $targetInstance)
    {
        findOrphans($OATcentral, $targetInstance, TRUE, $dataWriter);
        $dataWriter->outputData("- - - - -  \n");
    }
}

function findOrphans($sourceInstance, $targetInstance, $searchOnTarget, $dataWriter)
{
    $sourceInstance->setQuery("search?jql=text%20~%20%22" . $targetInstance->getProjectKey() . "-*%22%20AND%20status%20not%20in%20(Done,%20Rejected)%20AND%20type%20not%20in%20(Epic)");
    $data = executeQuery($sourceInstance);
    $jsondata = json_decode($data);
    $sourceIssues = $jsondata->issues;

    $data = executeQuery($targetInstance);
    $jsondata = json_decode($data);
    $targetIssues = $jsondata->issues;

    foreach ($sourceIssues as $sourceIssue)
    {
        $numMatches = preg_match("/-([0-9]+)/",$sourceIssue->fields->summary,$matches);
        if ($numMatches != 1)
        {
            outputData($sourceIssue->fields->summary . "has " . $numMatches . " matches");
        }
        {
            $targetIssueKey = $targetInstance->getProjectKey() . $matches[0];
            $orphan = TRUE;

            foreach ($targetIssues as $targetIssue)
            {
                if ($targetIssue->key == $targetIssueKey)
                {
                    $orphan = FALSE;
                }
            }

            if ($orphan)
            {   
                $parentStatus = "";
                if ($searchOnTarget)
                {
                    $targetInstance->setQuery("search?jql=key%20%3D%20" . $targetIssueKey);
                    $data = executeQuery($targetInstance);
                    $jsondata = json_decode($data);

                    if ($jsondata != NULL)
                    {
                        $matchingIssues = $jsondata->issues;
                        foreach ($matchingIssues as $matchingIssue)
                        {
                            $parentStatus = " (" . $matchingIssue->fields->status->name . ") ";

							$rlo = new ReportLineOptions(true, true, false, false, NULL);
							$reportLine = issueReportDetails($matchingIssue, $targetInstance, $sourceIssue, $sourceInstance, $rlo);
							$dataWriter->outputData($reportLine . "  \n");  
                        }
                    }
                }
				else
				{
					$rlo = new ReportLineOptions(true, true, false, false, NULL);
					$reportLine = issueReportDetails($sourceIssue, $sourceInstance, NULL, NULL, $rlo);
					$dataWriter->outputData($reportLine . "  \n");  
				}
                
				//$dataWriter->outputData("Potential orphan: " . $sourceIssue->key . ":" . $targetIssueKey . $parentStatus . " \t" . $sourceIssue->fields->summary . "  \n");
            }
        }
    }
}

function findPointedBugs($targetInstance, $dataWriter)
{
    $targetInstance->setQuery("search?jql=type%20%3D%20Bug%20AND%20%22Story%20Points%22%20is%20not%20EMPTY");
    
    $data = executeQuery($targetInstance);
    
    //we should have everything now
    //run through it and prepare for import
    $jsondata = json_decode($data);
    $issues = $jsondata->issues;

    foreach ($issues as $issue)
    { 
		$rlo = new ReportLineOptions(true, true, false, false, NULL);
		$reportLine = issueReportDetails($issue, $targetInstance, NULL, NULL, $rlo);
		$dataWriter->outputData($reportLine . "  \n");  
    }
}

function findUnpointed($targetInstance, $dataWriter)
{
    $targetInstance->setQuery("search?jql=type%20in%20(Story%2CTask%2C%22Tech%20Story%22)%20and%20Sprint%20is%20not%20EMPTY%20and%20Sprint%20not%20in%20futureSprints()%20AND%20%22Story%20Points%22%20is%20EMPTY%20ORDER%20BY%20assignee%20ASC");
    
    $data = executeQuery($targetInstance);
    
    //we should have everything now
    //run through it and prepare for import
    $jsondata = json_decode($data);
    $issues = $jsondata->issues;

    foreach ($issues as $issue)
    { 
		$rlo = new ReportLineOptions(true, true, false, false, NULL);
		$reportLine = issueReportDetails($issue, $targetInstance, NULL, NULL, $rlo);
		$dataWriter->outputData($reportLine . "  \n");
    }
}

function findNonStandardReporter($targetInstance, $dataWriter)
{
    $targetInstance->setQuery("search?jql=type%20not%20in%20(Sub-task%2C%20%22IMS%20Task%22)%20AND%20reporter%20not%20in%20(oatadmin%2C%20patrickplichart%2C%20lionellecaque%2C%20artemzhuk%2C%20vitali.shchur%2C%20hans.terhorst%2C%20tatianaavdeichik)");
    
    $data = executeQuery($targetInstance);
    
    //we should have everything now
    //run through it and prepare for import
    $jsondata = json_decode($data);
    $issues = $jsondata->issues;

    $pfn = $targetInstance->getPointsFieldName();
    
    foreach ($issues as $issue)
    {
		$rlo = new ReportLineOptions(true, true, true, true, $pfn);
		$reportLine = issueReportDetails($issue, $targetInstance, NULL, NULL, $rlo);
		$reportLine = $reportLine . " " . $issue->fields->reporter->name;
		$dataWriter->outputData($reportLine . "  \n");

        //$dataWriter->outputData($issue->key . " \t" . $points . " (" . $issue->fields->issuetype->name . ") \"" . $issue->fields->summary . "\"\t" . $issue->fields->reporter->name . "  \n");
    }
}

function findInBacklog($sourceInstance, $targetInstance, $dataWriter)
{
    $sourceInstance->setQuery("search?jql=text%20~%20%22" . $targetInstance->getProjectKey() . "-*%22%20AND%20Sprint%20is%20EMPTY%20AND%20type%20not%20in%20(Epic)");
    $data = executeQuery($sourceInstance);
    $jsondata = json_decode($data);
    $sourceIssues = $jsondata->issues;
    
    if ($sourceIssues != NULL)
    {
        foreach ($sourceIssues as $issue)
        {
			$rlo = new ReportLineOptions(true, true, false, false, NULL);
			$reportLine = issueReportDetails($issue, $sourceInstance, NULL, NULL, $rlo);
			$dataWriter->outputData($reportLine . "  \n");

            //$dataWriter->outputData("external in backlog: " . $issue->key . " \t" . "\"" . $issue->fields->summary . "\"  \n");
        }
    }
}
