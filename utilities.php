<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'jirainstance.php';

function serializeInstanceToFile($instance, $filename)
{
    $encoded = serialize($instance);
    file_put_contents("access/" . $filename . ".jix", $encoded);
}

function issueReportLine($frissue, $from, $toissue, $to)
{
	$lineItem = "";
	
	if (isset($frissue) && isset($from))
	{
		$lineItem = $lineItem . "[" . $frissue->key . "]" . "(" . $from->getUrl() . "browse/" . $frissue->key  . ")";
	}	

	if (isset($frissue) && isset($from) && isset($toissue) && isset($to))
	{
		$lineItem = $lineItem . " ~ ";
	}

	if (isset($toissue) && isset($to))
	{
		$lineItem = $lineItem . "[" . $toissue->key . "]" . "(" . $to->getUrl() . "browse/" . $toissue->key  . ")";
	}

	return $lineItem;
}

function issueReportLineCustom($frissue, $from, $toissue, $to, $customMessage)
{
	$lineItem = issueReportLine($frissue, $from, $toissue, $to) . " : " . $customMessage;
	
	return $lineItem;
}

function addIssueLineOptions($issue, ReportLineOptions $options)
{
	$lineItem = ' ';
	if ($options->getPoints())
	{
		$pfn = $options->getPointsFieldName();		
        $points = '';
        
        if (property_exists($issue->fields, $pfn))
        {
            $points = "(" . $issue->fields->$pfn . ")";
        }
        else
        {
            $points = "(-)";
        }
		$lineItem = $lineItem . $points;
	}	

	if ($options->getIssueType())
	{
		$lineItem = $lineItem . "[" . $issue->fields->issuetype->name . "]";
	}
	
	if ($options->getStatus())
	{
		$lineItem = $lineItem . "[" . $issue->fields->status->name . "]";
	}

	if ($options->getSummary())
	{
   		$lineItem = $lineItem . " \"" . $issue->fields->summary . "\"";
	}

	return $lineItem;
}

function issueReportDetails($frissue, $from, $toissue, $to, ReportLineOptions $options)
{
	$lineItem = issueReportLine($frissue, $from, $toissue, $to);

	if (isset($frissue))
	{
		$lineItem = $lineItem .	addIssueLineOptions($frissue, $options);
	}
	elseif (isset($toissue))
	{
		$lineItem = $lineItem . addIssueLineOptions($toissue, $options);
	}

	return $lineItem;
}

function issueReportCompareStatus($frissue, $from, $toissue, $to)
{
	$lineItem = issueReportLine($frissue, $from, $toissue, $to);
	$rlo = new ReportLineOptions(false, true, false, false, null);

	$sourceStatus = $frissue->fields->status->name;
    $targetStatus = $toissue->fields->status->name;

	if (statussesMatch($sourceStatus, $targetStatus))
	{
		//$lineItem = $lineItem . " [" . $sourceStatus . "]";
		$rlo->setStatus(true);
	}
    else
    {
    	$lineItem = $lineItem . " *status conflict* " . $from->getProjectKey() . ":" . $sourceStatus . " != " . $to->getProjectKey() . ":" . $targetStatus;
    }

	if (pointsMatch($frissue, $from, $toissue, $to))
	{
		$rlo->setPoints(true, $from->getPointsFieldName());
	}
	else
	{
    	$lineItem = $lineItem . " *points conflict* ";
	}
	
    $lineItem = $lineItem . addIssueLineOptions($frissue, $rlo);
	
	return $lineItem;
}

function pointsMatch($issue1, $i1host, $issue2, $i2host)
{
	$p1 = 0;
	$p2 = 0;

	$i1pfn = $i1host->getPointsFieldName();
	$i2pfn = $i2host->getPointsFieldName();

	if (property_exists($issue1->fields, $i1pfn))
	{
		$p1 = 0 + $issue1->fields->$i1pfn;
	}

	if (property_exists($issue2->fields, $i2pfn))
	{
		$p2 = 0 + $issue2->fields->$i2pfn;
	}

	return $p1 == $p2;
}

function statussesMatch($status1, $status2)
{
    $statusDictionary = array(
        array("Preparation", "Ready for Dev", "To Do", "TO DO"),
        array("In Progress", "Dev In Progress"),
        array("Dev Complete", "Resolved"),
        array("Ready for Test", "Ready For Test", "Ready to Test"),
        array("Test Complete"),
        array("Done", "Closed", "Handoff", "Testing")    
    );
    
    foreach ($statusDictionary as $status)
    {
        if (in_array($status1, $status) && in_array($status2, $status))
        {
            return true;
        }
    }
    return false;
}

class DataWriter
{
    protected $outputTarget;
    
    public function __construct($outputTarget) 
    {
        $this->outputTarget = $outputTarget;
    }
    
    function outputData($stringToOutput)
    {
        echo $stringToOutput;

        $file = $this->outputTarget;
        $handle = fopen($file, 'a');

        fwrite($handle, $stringToOutput);
    }
}


class ReportLineOptions
{
    protected $status;
    protected $summary;
    protected $issueType;
    protected $points;
    protected $pointsFieldName;

    public function __construct($status, $summary, $issueType, $points, $pointsFieldName)
	{
		$this->status = $status;
		$this->summary = $summary;
    	$this->issueType = $issueType;

    	$this->points = $points && isset($pointsFieldName);
    	$this->pointsFieldName = $pointsFieldName;
	} 

	public function getStatus()
	{
		return $this->status;
	}
	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function getSummary()
	{
		return $this->summary;
	}
	public function setSummary($summary)
	{
		$this->summary = $summary;
	}

	public function getIssueType()
	{
		return $this->issueType;
	}
	public function setIssueType($issueType)
	{
		$this->issueType = $issueType;
	}

	public function getPoints()
	{
		return $this->points;
	}
	public function getPointsFieldName()
	{
		return $this->pointsFieldName;
	}
	public function setPoints($points, $pointsFieldName)
	{
		$this->points = $points;
		$this->pointsFieldName = $pointsFieldName;
	}
}
