<?php
include_once "comment.php";
/**
 * Created by PhpStorm.
 * User: daxterix
 * Date: 3/9/16
 * Time: 8:12 PM
 */


class IOContainer{
    public $parentDir = NULL;
    public $parentPath = "";
    public $path = "";
    public $kind = "";
    public $baseName = "";
    public $commits = [];
    public $rootComment = NULL;

    public function __construct($path){
        $this->path = $path;
        $this->parentPath = pathinfo($path, PATHINFO_DIRNAME);
        $this->baseName = pathinfo($path, PATHINFO_BASENAME);
        $this->rootComment = new Comment($id = -1, $parentId = -2, $path, $text = "");
    }

    public function getRootComment(){
        return $this->rootComment;
    }

    public function addCommit($newCommit){
        array_push($this->commits, $newCommit);
    }

    public function getCommits(){
        return $this->commits;
    }

    public function getPath(){
        return $this->path;
    }

    public function getBaseName(){
        return $this->baseName;
    }

    public function getKind(){
        return $this->kind;
    }

    public function getParentPath(){
        return $this->parentPath;
    }

    public function setParentPath($newParentPath){
        $this->parentPath = $newParentPath;

    }

    public function setParentDir($newParentDir){
        $this->parentDir = $newParentDir;
    }

    public function toString_less(){
        $tab = Utils::tab();
        $newLine = Utils::newLine();
        $res = "{$this->getPath()}{$tab}{$this->getKind()}";
        foreach ($this->commits as $commit){
            $res .= "{$newLine}{$commit->toString_less()}";
        }
        $res .= "{$newLine}";
        return $res;
    }

    public function toString_minimal(){
        $tab = Utils::tab();
        $res = "{$this->getPath()}{$tab}{$this->getKind()}{$tab}";
        return $res;
    }

    public function kindCompare($ioContainer1, $ioContainer2){
        return strCmp($ioContainer1->getKind(), $ioContainer2->getKind());
    }

    public function nameCompare($ioContainer1, $ioContainer2){
        return strCmp($ioContainer1->getBaseName(), $ioContainer2->getBaseName());
    }
}

class MDirectory extends IOContainer{
    public $contents = [];

    public function __construct($path){
        parent::__construct($path);
        $this->kind = "dir";
    }

    public function addContent($anotherIOContainer){
        array_push($this->contents, $anotherIOContainer);
    }
    public function getContents(){
        return $this->contents;
    }
}

class MFile extends IOContainer{
    public $extension = "";
    public $size = -1;

    public function __construct($path, $size){
        parent::__construct($path);
        $this->kind = "file";
        $this->size = $size;
        $this->extension = pathinfo($path, PATHINFO_EXTENSION);
    }

    public function getSize(){
        return $this->size;
    }

    public function toString_less(){
        $tab = Utils::tab();
        $newLine = Utils::newLine();
        $res = "{$this->getPath()}{$tab}{$this->getKind()}{$tab}{$this->getSize()} bytes";
        foreach ($this->commits as $commit){
            $res .= "{$newLine}{$commit->toString_less()}";
        }
        $res .= "{$newLine}";
        return $res;
    }

    public function toString_minimal(){
        $tab = Utils::tab();
        $res = "{$this->getPath()}{$tab}{$this->getKind()}{$tab}{$this->getSize()} bytes";
        return $res;
    }



}
