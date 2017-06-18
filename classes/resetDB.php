<?php
require_once(__DIR__."/svnXmlParser.php");
require_once(__DIR__."/utils.php");


/**
 * @param string $svnlist_xml
 * @param string $svnlog_xml
 * clears the database information then reloads repopulates
 * database with information provided in the given files.
 */
function populateDataBase($svnlist_xml = "/home/daxterix/PhpstormProjects/Assignment3.1/inputFiles/svn_list.xml" ,
                          $svnlog_xml = "/home/daxterix/PhpstormProjects/Assignment3.1/inputFiles/svn_log.xml"){
    $conn = createDataBaseConnection();
    $parsedInfo = new SvnXmlParser();
    $portfolio = $parsedInfo->generatePortfolio($svnlist_xml, $svnlog_xml);

    clearDataBaseAtConnection($conn);
    populateCommitsNMods($portfolio, $conn);
    populateIOObjects($portfolio, $conn);

    closeDataBaseConnection($conn);
    echo "database reset" . Utils::newLine();
}



/** clears the database
 */
function clearDataBase(){
    $conn = createDataBaseConnection();
    clearDataBaseAtConnection($conn);
    closeDataBaseConnection($conn);
    echo "database cleared" . Utils::newLine();
}

function createDataBaseConnection(){
    $host = 'localhost';
    $username = 'root';
    $passwd = 'password';

    $conn = new mysqli($host, $username, $passwd /*$dbname, $port*/);
    if($conn->connect_error){
        mysqli_error($conn);
        die();
    }
    return $conn;
}

function closeDataBaseConnection($conn){
    $conn->close();
}


function clearDataBaseAtConnection($dataBaseConnection){
     $tables = ['comments', 'commits', 'commitModifications', 'ioObjects'];
    foreach($tables as $tableName) {
        $delQuery = "DELETE FROM `cs242Assig3`.`{$tableName}` WHERE 1 = 1";

        $stmt = $dataBaseConnection->prepare($delQuery);
        if ($stmt->execute() == false) {
            echo "Error deleting {$tableName} records: " . $dataBaseConnection->error . Utils::newLine();
            die();
        }
        else{
            echo "{$tableName} records deleted successfully" . Utils::newLine();
        }
    }
}


//add commits to database along with their associated modifications
function populateCommitsNMods($portfolio, $dataBaseConnection){
    foreach ($portfolio->getCommits() as $commit) {
        $insQuery = "INSERT INTO `cs242Assig3`.`commits` (`revision`, `author`, `date_time`, `message`) " .
            "VALUES ({$commit->getRevisionNumber()}, '{$commit->getAuthor()}', '{$commit->getDateTime()->getOrigTimeStr()}', '" . addslashes($commit->getMessage()). "')";

        $stmt = $dataBaseConnection->prepare($insQuery);

        if ($stmt->execute() == false) {
            echo "error inserting commitModifications" . Utils::newLine();
            die();
        }
        foreach ($commit->getModifications() as $mod) {
            $insQuery = "INSERT INTO `cs242Assig3`.`commitModifications` (`revision`, `action`, `kind`, `path`) " .
                "VALUES ({$commit->getRevisionNumber()}, '{$mod->getAction()}', '{$mod->getKind()}', '{$mod->getPath()}')";

            $stmt = $dataBaseConnection->prepare($insQuery);
            if ($stmt->execute() == false) {
                echo "error inserting commitModifications" . Utils::newLine();
                die();
            }
        }
    }
    echo "sucessfully added commit, commidMod records" . Utils::newLine();
}

function populateIOObjects($portfolio, $dataBaseConnection){
    foreach ($portfolio->getAllIOContainers() as $ioc) {
        $size = -1;
        if($ioc->getKind() == "file"){
            $size = $ioc->getSize();
        }

        $insQuery = "INSERT INTO `cs242Assig3`.`ioObjects` (`path`, `parentPath`, `kind`, `size`) " .
                    "VALUES ('{$ioc->getPath()}', '{$ioc->getParentPath()}', '{$ioc->getKind()}', {$size})";

        $stmt = $dataBaseConnection->prepare($insQuery);
        if ($stmt->execute() == false) {
            echo "error inserting commitModifications" . Utils::newLine();
            die();
        }

    }
    echo "sucessfully added IOObjects, commitMod records" . Utils::newLine();
}
//clearDataBase();
populateDataBase();
?>
