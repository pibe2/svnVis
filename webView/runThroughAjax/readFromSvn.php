<?php
$svnPath = $_GET['svnPath'];
$basename = $_GET['basename'];
$revision = (int)($_GET['revision']);
$dirPathNoRev = $_GET['localDirPath'];

$completeLocalDirPath = "../../webView/portfolioFiles_dynamic/{$dirPathNoRev}/{$revision}";
$completeLocalPath = "{$completeLocalDirPath}/{$basename}";

$responsePath = "../../portfolioFiles_dynamic/{$dirPathNoRev}/{$revision}/{$basename}";

//check if file revision already exists, if so return,
//else create parent dirs to house it, then export it from svn
if(file_exists($completeLocalPath) == false){
    mkdir($completeLocalDirPath, 0777, true);
    $command = "svn export -r {$revision} {$svnPath} {$completeLocalPath}";
    exec($command) or die("SVN export failed\n");
    echo $responsePath;
}
else{
    echo $responsePath;
}

//echo urlencode(svn_cat($svnPath, $revision));
//doesn't work for some magical reason
//keeps hitting an error GET: error 500, and i don't know what it is