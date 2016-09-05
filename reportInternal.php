<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'jirainstance.php';
require_once 'utilities.php';

require_once "pChart/class/pDraw.class.php";
require_once "pChart/class/pImage.class.php";
require_once "pChart/class/pData.class.php";

$sprints = array(
    /*array(1, 2, "Sprint 0"),
    array(2, 3, "Sprint 1"),
    array(3, 4, "Sprint 0 mk 2"),
    array(4, 5, "Sprint 2"),
    array(5, 6, "Sprint 3"),
    array(6, 7, "Sprint 4"),
    array(7, 8, "Sprint 5"),
    array(8, 9, "Sprint 6"),
    array(9, 10, "Sprint 7"),*/
    array(10, 12, "Sprint 8"),
    array(12, 13, "Sprint 9"),
    array(13, 14, "Sprint 10"),
    array(14, 15, "Sprint 11"),
    array(15, 16, "Sprint 12"),
    array(16, 17, "Sprint 13"),
    array(17, 18, "Sprint 14"),
    array(18, 19, "Sprint 15"),
//    array(19, 20, "Sprint 16"),
    array(20, 21, "Sprint 17"),
    array(21, 22, "Sprint 18"),
    array(22, 23, "Sprint 19"),
    array(23, 24, "Sprint 20"),
    array(24, 25, "Sprint 21"),
    array(25, 26, "Sprint 22"),
    array(26, 27, "Sprint 23"),
    array(27, 28, "Sprint 24"),
    array(28, 29, "Sprint 25"),
    array(29, 30, "Sprint 26"),
    array(30, 31, "Sprint 27"),
    array(31, 32, "Sprint 28"),
    array(32, 33, "Sprint 29"),
    array(33, 34, "Sprint 30"),
    array(34, 35, "Sprint 31"),
    array(35, 36, "Sprint 32"),
    array(36, 37, "Sprint 33")
);

GenerateCompleteSprintReport($sprints);


class SprintReportData
{
    protected $sprintName;
    protected $sprintNumber;
    
    protected $allIssues;
    protected $allStories;
    protected $allTasks;
    protected $allBugs;
    
    public function __construct($_sprintName, $_sprintNumber) 
    {
        $this->sprintName = $_sprintName;
        $this->sprintNumber = $_sprintNumber;
    }
    
    function getReport()
    {
        $report = "### " . $this->sprintName . " (" . $this->sprintNumber . ")" . "\n";
        $report = $report . count($this->allIssues) . " issues addressed (" . count($this->allStories) . " stories, " . count($this->allBugs) . " bugs)  \n";
        $report = $report . "*story points: " . ($this->getAllPoints()) . " (" . $this->getStoryPoints() . "+" . $this->getTaskPoints() . ")*  \n";
        
        return $report;
    }
    
    function getAllPoints()
    {
        return $this->getStoryPoints() + $this->getTaskPoints();
    }
    
    function getStoryPoints()
    { 
        $storyPoints = 0.0;

        foreach ($this->allStories as $story)
        {
            $storyPoints += $story->fields->customfield_10103;
        }
        
        return $storyPoints;
    }
    
    function getTaskPoints()
    {    
        $taskPoints = 0.0;
        foreach ($this->allTasks as $task)
        {
            $taskPoints += $task->fields->customfield_10103;
        }
        
        return $taskPoints;
    }
    
    function getAllIssues()
    {
        return $this->allIssues;
    }
    function setAllIssues($_allIssues)
    {
        $this->allIssues = $_allIssues;
    }
    
    function getAllStories()
    {
        return $this->allStories;
    }
    function setAllStories($_allStories)
    {
        $this->allStories = $_allStories;
    }
    
    function getAllTasks()
    {
        return $this->allTasks;
    }
    function setAllTasks($_allTasks)
    {
        $this->allTasks = $_allTasks;
    }
    
    function getAllBugs()
    {
        return $this->allBugs;
    }
    function setAllBugs($_allBugs)
    {
        $this->allBugs = $_allBugs;
    }
    
    function getSprintName()
    {
        return $this->sprintName;
    }
        
    function getSprintNumber()
    {
        return $this->sprintNumber;
    }
}

function GenerateCompleteSprintReport($sprints)
{
    $sprintReports = array();
    $dataWriter = new DataWriter("reports/internal/" . date("Ymd") . ".md");

    foreach ($sprints as $sprint)
    {
        //beginreporting($sprint[0], $sprint[1], $sprint[2], 'text%20~%20%22WK-*%22%20AND%20');
        $sprintReports[] = GenerateSprintReport($sprint[0], $sprint[1], $sprint[2]);
    }

    $dataWriter->outputData("#Sprints Report\n");
    $dataWriter->outputData("##Overview\n");

    $velocity = 0.0;
    
    //graph data
    $velocityData = array();
    $velocityAvg = array();
    $velocityMA = array();
    $sprintNames = array();
        
    for ($i = 0; $i < count($sprintReports); $i++)
    {
        $velocityData[$i] = $sprintReports[$i]->getAllPoints();
        $velocity += $velocityData[$i];
        
        $avgCount = 0;
        $velocityMA[$i] = 0;
                
        for ($j = max([$i - 5, 0]); $j <= $i; $j++)
        {
            //echo $i . " " . $j . " " . count($sprintReports) . " " . $sprintReports[$j]->getAllPoints() . "\n";
            $velocityMA[$i] += $sprintReports[$j]->getAllPoints();
            //echo $velocityMA[$i] . "\n";
            $avgCount++;
        }
        
        $velocityMA[$i] = $velocityMA[$i] / $avgCount;
                
        /*if (($i - 1 >= 0) && ($i + 1 < count($sprintReports)))
        {
            $velocityMA[$i] = (($sprintReports[$i-1]->getAllPoints() + $sprintReports[$i]->getAllPoints() + $sprintReports[$i+1]->getAllPoints()) / 3);
        }
        else
        {
            $velocityMA[$i] = VOID;
        }*/
    }
        
    $velocity = $velocity / count($sprintReports);
    $dataWriter->outputData("velocity " . number_format($velocity, 2) . "\n");
    for ($i = 0; $i < count($sprintReports); $i++)
    {
        $velocityAvg[$i] = $velocity;
        $sprintNames[$i] = $sprintReports[$i]->getSprintName();
        echo $sprintNames[$i];
    }
      
    $myData = new pData();
    $myData->addPoints($velocityData, "velocity");
    $myData->addPoints($velocityMA, "velocityMA");
    $myData->addPoints($velocityAvg, "velocityA");
    $myData->addPoints($sprintNames, "SprintNames");
    
    //$myData->setSerieDrawable("SprintNames", false);
    $myData->setSerieDescription("SprintNames", "Sprint");
    $myData->setAbscissa("SprintNames");
    
    $myImage = new pImage(800, 600, $myData);
    $myImage->setFontProperties(array("FontName" => "pChart/fonts/GeosansLight.ttf", "FontSize" => 15));
    $myImage->setGraphArea(40,40, 760, 480);
    $myImage->drawScale(array("Mode"=>SCALE_MODE_START0,"LabelRotation"=>65));
        
    
    $myData->setSerieDrawable("velocityA", false);
    $myData->setSerieDrawable("velocityMA", false);
    $myImage->drawBarChart();
    
    $myData->setSerieDrawable("velocityA");
    $myData->setSerieDrawable("velocityMA");
    $myData->setSerieDrawable("velocity", false);
    $myImage->drawLineChart();
    
    $imageName = date("Ymd") . "velocity.png";
    
    $myImage->render("reports/internal/" . $imageName);
    
    $dataWriter->outputData("![velocity chart](" . $imageName . ") \n");
    
    $dataWriter->outputData("##Breakdown\n");
    foreach (array_reverse($sprintReports) as $sprintReport)
    {
        $dataWriter->outputData($sprintReport->getReport());
    }
}

function GenerateSprintReport($sprintNumber, $nextSprintNumber, $sprintName, $searchPrefix = '')
{
    $SprintReportData = new SprintReportData($sprintName, $sprintNumber);
        
    $OATcentral = unserialize(file_get_contents("access/TAO.jix"));

    $OATcentral->setQuery("search?jql=" . $searchPrefix . "status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    //$OATcentral->setQuery("search?jql=text%20~%20%22WK-*%22%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    $data = executeQuery($OATcentral);    
    $jsondata = json_decode($data);
    $SprintReportData->setAllIssues($jsondata->issues);
    
    $OATcentral->setQuery("search?jql=" . $searchPrefix . "type%20%3D%20Story%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    //$OATcentral->setQuery("search?jql=text%20~%20%22WK-*%22%20AND%20type%20%3D%20Story%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    $data = executeQuery($OATcentral);
    $jsondata = json_decode($data);
    $SprintReportData->setAllStories($jsondata->issues);
    
    $OATcentral->setQuery("search?jql=" . $searchPrefix . "type%20%3D%20Task%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    //$OATcentral->setQuery("search?jql=text%20~%20%22WK-*%22%20AND%20type%20%3D%20Task%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    $data = executeQuery($OATcentral);
    $jsondata = json_decode($data);
    $SprintReportData->setAllTasks($jsondata->issues);
    
    $OATcentral->setQuery("search?jql=" . $searchPrefix . "type%20%3D%20Bug%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    //$OATcentral->setQuery("search?jql=text%20~%20%22WK-*%22%20AND%20type%20%3D%20Bug%20AND%20status%20%3D%20Done%20AND%20Sprint%20%3D%20" . $sprintNumber . "%20AND%20Sprint%20!%3D%20" . $nextSprintNumber . "&maxResults=500");
    $data = executeQuery($OATcentral);
    $jsondata = json_decode($data);
    $SprintReportData->setAllBugs($jsondata->issues);
    
    echo ".";
    
    return $SprintReportData;
    ////outputData("### " . $sprintName . " (" . $sprintNumber . ")" . "\n");
    //outputData(count($allIssues) . " issues addressed (" . count($allStories) . " stories, " . count($allBugs) . " bugs)  \n");
    //outputData("*story points: " . ($storyPoints + $taskPoints) . " (" . $storyPoints . "+" . $taskPoints . ")*  \n");
}
