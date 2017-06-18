<?php

class Comment{
    public $path;
    public $id;
    public $text;
    public $parentId;
    public $parent;
    public $children;

    public function __construct($id, $parentid, $path, $text){
        $this->id = $id;
        $this->parentId = $parentid;
        $this->path = $path;
        $this->children = [];
        $this->text = $text;
    }

    public function getPath(){
        return $this->path;
    }

    public function setPath($path){
        $this->path = $path;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getParentId(){
        return $this->parentId;
    }

    public function setParentId($parentId){
        $this->parentId = $parentId;
    }

    public function getParent(){
        return $this->parent;
    }

    public function setParent($newParent){
        $this->parent = $newParent;
    }

    public function getText(){
        return $this->text;
    }

    public function getChildren(){
        return $this->children;
    }

    public function addChild($childComment){
        array_push($this->children, $childComment);
    }

}