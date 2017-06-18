<?php
include_once 'utils.php';

Class MyDateTime{
    public $date = "";
    public $time = "";
    public $timeStampStr = "";
    public $origTimeStr = "";

    public function __construct($wholeTimeStr){
        $this->origTimeStr = $wholeTimeStr;
        $timeDateJoined = explode(".", $wholeTimeStr)[0];
        $dateTimeStrs = explode("T", $timeDateJoined);
        $this->timeStampStr = implode(" ", $dateTimeStrs);
        $this->date = $this->format2MMDDYYYY($dateTimeStrs[0]);
        $this->time = $dateTimeStrs[1];
    }


    public function getOrigTimeStr(){
        return $this->origTimeStr;
    }

    public function getDateStr(){
        return $this->date;
    }

    public function getTimeStr(){
        return $this->time;
    }

    /**
     * comparable time string is formatted as: yyyymmddThh:mm
     */
    public function getTimeStampStr(){
        return $this->timeStampStr;
    }

    /**
     * @param $dateStr
     * @return string
     * converts a date string of form "yyyy-mm-dd" to
     * form "mm-dd-yyyy"
     */
    public function format2MMDDYYYY($dateStr){
        $yearMonthDay = explode("-", $dateStr);
        return "{$yearMonthDay[1]}/{$yearMonthDay[2]}/{$yearMonthDay[0]}";
    }

    public function toString(){
        $tab = Utils::tab();
        return "{$this->date}{$tab}{$this->time}";
    }
}

class Commit{
    public $revisionNumber = -1;
    public $author = "";
    public $dateTime = "";
    public $message = "";
    public $modifications = [];

    public function __construct($revisionNum, $auth, $dateTime, $message){
        $this->author = $auth;
        $this->dateTime = new MyDateTime($dateTime);
        $this->revisionNumber = $revisionNum;
        $this->message = $message;
    }

    public function addModification($action, $kind, $path){
        $newMod = new CommitModification($action, $kind, $path);
        array_push($this->modifications, $newMod);
    }

    public function getModifications(){
        return $this->modifications;
    }

    public function getRevisionNumber(){
        return $this->revisionNumber;
    }

    public function getAuthor(){
        return $this->author;
    }

    public function getMessage(){
        return $this->message;
    }

    public function getDateTime(){
        return $this->dateTime;
    }


    /**
     * @param $commit1
     * @param $commit2
     * @return int
     * compare function for sorting in such a way that
     * the latest commits are first.
     */
    public static function timeCompare($commit1, $commit2){
        return -1 * strCmp($commit1->dateTime->getTimeStampStr(), $commit2->dateTime->getTimeStampStr());
    }

    public function toString_less(){
        $newLine = Utils::newLine();
        $tab = Utils::tab();
        $strVer = "{$this->dateTime->toString()}{$tab}{$this->revisionNumber}{$tab}{$this->author}{$tab}{$this->message}";
        return $strVer;
    }

    public function toString(){
        $newLine = Utils::newLine();
        $strVer = $this->toString_less();
        foreach ($this->modifications as $mod){
            $strVer .= "{$newLine}{$mod->toString()}";
        }
        $strVer .= "{$newLine}{$newLine}";
        return $strVer;
    }
}


class CommitModification{
    public $path = "";
    public $action = "";
    public $kind = "";


    public function __construct($action, $kind, $path){
        $this->kind = $kind;
        $this->action = $action;
        $this->path = $path;
    }


    public function getPath(){
        return $this->path;
    }

    public function getAction(){
        return $this->action;
    }
    public function getKind(){
        return $this->kind;
    }

    public function toString(){
        $tab = Utils::tab();
        return "{$this->action}{$tab}{$this->kind}{$tab}{$this->path}";
    }
}
