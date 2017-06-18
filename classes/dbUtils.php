<?php

//TODO: prepare statements, and escape userinput
class DBUtils{
    //not that super string have to be added prior to their substrings
    public static $FILTERED_WORDS = ['fucking', 'fucked', 'fuck', 'bitch'];
    
    public static function closeDataBaseConnection($conn){
        $conn->close();
    }

    public static function createDataBaseConnection(){
        $host = 'localhost';
        $username = 'root';
        $passwd = 'password';

        $conn = new mysqli($host, $username, $passwd);
        if($conn->connect_error){
            mysqli_error($conn);
            die('could not connect');
        }
        return $conn;
    }
    
    public static function filterComment($comment){
        $filteredComment = substr($comment, 0);
        foreach(DBUtils::$FILTERED_WORDS as $filteredWord){
            $filteredComment = str_ireplace($filteredWord, "*{$filteredWord}*", $filteredComment);
        }
        return $filteredComment;
    }

    public static function addCommentToDataBase($parentId, $filePath, $commentText){
        $conn = DBUtils::createDataBaseConnection();
        $commentText = DBUtils::filterComment($commentText);
        $commentText = mysqli_real_escape_string($conn, $commentText);
        
        $insQuery = "INSERT INTO `cs242Assig3`.`comments` (`parentID`, `filePath`, `content`) VALUES (?, ?, ?)";
        $preparedQuery = $conn->prepare($insQuery);
        $preparedQuery->bind_param('dss', $parentId, $filePath, $commentText);
        $preparedQuery->execute() or die(mysqli_error($conn));
        $preparedQuery->close();
        DBUtils::closeDataBaseConnection($conn);
    }
    
    
    /*public static function addCommentToDataBase($parentId, $filePath, $commentText){
        $conn = DBUtils::createDataBaseConnection();
        $commentText = DBUtils::filterComment($commentText);
        $commentText = mysqli_real_escape_string($conn, $commentText);
        $insQuery = "INSERT INTO `cs242Assig3`.`comments` (`parentID`, `filePath`, `content`) VALUES ({$parentId}, '{$filePath}', '{$commentText}')";
        mysqli_query($conn, $insQuery) or die(mysqli_error($conn));
        DBUtils::closeDataBaseConnection($conn);
    }*/
}
