<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'jirainstance.php';
require_once 'utilities.php';

$ACTWKP = unserialize(file_get_contents("access/WTP.jix"));
$MPFAA = unserialize(file_get_contents("access/FAA.jix"));
$MPFY2 = unserialize(file_get_contents("access/FY2.jix"));
$OATEON = unserialize(file_get_contents("access/EON.jix"));
$PCG01 = unserialize(file_get_contents("access/PCG01.jix"));

//GenerateCustomerReport($PCG01);
//GenerateCustomerReport($OATEON);
GenerateCustomerReport($MPFY2);
//GenerateCustomerReport($ACTWKP);

function GenerateCustomerReport($reportTarget)
{
    $dataWriter = new DataWriter("reports/customer/" . $reportTarget->getProjectKey() . "/" . date("Ymd") . ".md");
    $dataWriter->outputData("# " . $reportTarget->getProjectName() . "\n");
    $dataWriter->outputData("## Summary\n\n");
    $dataWriter->outputData("Generated " . date("Y-m-d") . "  \n");
    
    $storyReport= '';
    
    foreach ($reportTarget->getReportQueries() as $reportQuery)
    {
        $cchb = defaultJiraCurlOptions();
        curl_setopt($cchb, CURLOPT_USERPWD, $reportTarget->getUserPswd());

        $queryString = $reportTarget->getUrl() . $reportTarget->getApiPath() . $reportQuery["query"];
        
        curl_setopt($cchb, CURLOPT_URL, $queryString);

        $response = curl_exec($cchb);
        $response = json_decode($response);
        
        $dataWriter->outputData("## " . $reportQuery["title"] . "\n");
        
        $issues = $response->issues;
        curl_close($cchb);
        
        
        $storyPoints = 0.0;
        
        $storyReport = $storyReport . "### " . $reportQuery["title"] . ":\n";

        foreach ($issues as $story)
        {
            $storyReport = $storyReport . $story->key;
            
            $pfn = $reportTarget->getPointsFieldName();
            
            if (property_exists($story->fields, $pfn))
            {
                $storyReport = $storyReport . " (" . $story->fields->$pfn . ")  ";
                
                $storyPoints += $story->fields->$pfn;
            }
            else
            {
                $storyReport = $storyReport . " (-)  ";
            }
            
            $storyReport = $storyReport . $story->fields->summary . "  \n";
        }
        
        //$storyReport = $storyReport . "[" . $reportQuery["query"] . "]" .  "\n\n";
        
        $dataWriter->outputData($response->total . " issues " . $storyPoints . " points \n"); 
    }
    
    $dataWriter->outputData("\n## Details\n" . $storyReport);
}
