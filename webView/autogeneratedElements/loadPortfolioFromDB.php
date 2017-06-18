<?php
require_once("../../classes/svnXmlParser.php");
require_once("../../classes/dbLoader.php");
/*$xmlParser = new SvnXmlParser();
$portfolio = $xmlParser->generatePortfolio("/home/daxterix/PhpstormProjects/Assignment3.1/inputFiles/svn_list.xml",
                                 "/home/daxterix/PhpstormProjects/Assignment3.1/inputFiles/svn_log.xml");

*/
$dbLoader = new DBLoader();
$portfolio = $dbLoader->generatePortfolio();
