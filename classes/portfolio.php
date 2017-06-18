<?php
include_once 'commitInfo.php';
include_once 'fileClasses.php';
include_once 'comment.php';
include_once 'utils.php';

class Portfolio{
    public $path2IODict;
    public $rootDir;
    public $files;
    public $directories;
    public $revision2commitDict;
    public $commits;
    public $commitMods;
    public $comments;

    public function __construct($path2IODict, $rootDir, $files, $directories,
                                $rev2commmitDict, $commits, $commitMods, $comments){
        $this->path2IODict = $path2IODict;
        $this->rootDir = $rootDir;
        $this->files = $files;
        $this->directories = $directories;
        $this->revision2commitDict = $rev2commmitDict;
        $this->commits = $commits;
        $this->commitMods = $commitMods;
        $this->comments = $comments;
    }

    /**
     * @param $svnPath
     * @return string
     * given an svnPath (as in ioc->getPath()) gives you it's path on local
     * storage relative; strips off the path of the svn root directory from
     * which we obtained the portfolio; assumes iocExists($svnPath);
     */
    public function getLocalPath($svnPath){
        $ioc = $this->getIOContainer($svnPath);
        $rootPath = $this->rootDir->getPath();
        //plus one for the slash
        return substr($ioc->getPath(), strlen($rootPath) + 1);
    }
    
        /**
     * @param $svnPath
     * @return string
     * given an svnPath (as in ioc->getPath()) gives you it's path on local
     * storage relative; strips off the path of the svn root directory from
     * which we obtained the portfolio; assumes iocExists($svnPath);
     */
    public function getLocalDirectoryPath($svnPath){
        $ioc = $this->getIOContainer($svnPath);
        $rootPath = $this->rootDir->getPath();
        //plus one for the slash
        return substr($ioc->getPath(), strlen($rootPath) + 1);
    }
 
    
    /**
     * @param $path
     * @return bool
     * determines if the path belongs to a file or
     * directory in the formerly given svnListFile_xml
     */
    public function ioContainerExists($path){
        return array_key_exists($path, $this->path2IODict);
    }
    
    public function commitExists($rNumStr){
        return array_key_exists($rNumStr, $this->revision2commitDict);
    }
    
    /**
     * @param $path
     * @return mixed
     * returns the IOContainer whose path matches the give one
     * assumes $this->isContainerExists($path) is true
     */
    public function getIOContainer($path){
        return $this->path2IODict[$path];
    }

    /**
     * @param $rNumStr
     * @return mixed
     * returns the corresponding commit
     * assuming commitExists($rNumStr)
     */
    public function getCommit($rNumStr){
        return $this->revision2commitDict[$rNumStr];
    }

    public function getComments(){
        return $this->comments;
    }
    public function getFiles(){
        return $this->files;
    }

    public function getDirectories(){
        return $this->directories;
    }

    public function getCommits(){
        return $this->commits;
    }

    public function getCommmitModifications(){
        return $this->commitMods();
    }

    public function getAllIOContainers(){
        return array_values($this->path2IODict);
    }

    public function getRootDir(){
        return $this->rootDir;
    }

    public function printCommits(){
        foreach ($this->commits as $commit){
            print $commit->toString();
        }
    }

    public function printFileTree($tabs = "", $shouldPrintCommits = false){
        $this->printFileTreeFromDir($this->rootDir, $tabs, $shouldPrintCommits);
    }

    /**
     * @param $ioContainer
     * @param $tabs
     * @param $shouldPrintCommits
     * recursively print all the contents(files/folders) of
     * given ioContainer.
     */
    public function printFileTreeFromDir($ioContainer, $tabs = "", $shouldPrintCommits = false){
        $tabChar = Utils::tab();
        $newLine = Utils::newLine();
        print $tabs . $ioContainer->getPath() . $newLine;
        if($shouldPrintCommits) {
            foreach ($ioContainer->getCommits() as $commit) {
                print "{$tabs}{$tabChar}{$commit->toString_less()}{$newLine}";
            }
        }
        if( $ioContainer->getKind() == 'dir'){
            foreach ($ioContainer->getContents() as $content){
                $this->printFileTreeFromDir($content, $tabs . $tabChar, $shouldPrintCommits);
            }
            print $newLine;
        }
    }

    //generates an html table for the commits of a given file path
    //assumes ioContainerExists($path);
    public function generateHTMLfromCommits($path){
        $ioc = $this->getIOContainer($path);
        $result =     "<tr>
                            <caption>Commits</caption>
                            <th>Author</td>
                            <th>Date</td>
                            <th>Time</td>
                            <th>Revision</td>
                            <th>Message</td>
                        </tr> ";
        
        foreach($ioc->getCommits() as $commit){
            $result .= "<tr> 
                            <td>{$commit->getAuthor()}</td>
                            <td>{$commit->getDateTime()->getDateStr()}</td>
                            <td>{$commit->getDateTime()->getTimeStr()}</td>
                            <td><a class='commitInfo' href='../commitDetails.php/?revision={$commit->getRevisionNumber()}'>{$commit->getRevisionNumber()}</a></td>
                            <td>{$commit->getMessage()}</td>
                            <td class='viewFileButton'>
                                <button class='navLink viewFileButton'
                                    data-revision='{$commit->getRevisionNumber()}'
                                    data-localdirpath='{$this->getLocalDirectoryPath($path)}'
                                    data-basename='{$ioc->getBaseName()}'
                                    data-localpath='../../portfolioFiles/{$this->getLocalPath($path)}'>View File
                                </button>
                            </td>
                        </tr>";
        }
        return "<table id='commitTable'>{$result}</table>";
    }
    
    
    //generates an html table for the commitsMods of a given revision
    //assumes commitExists($revision);
    public function generateHTMLfromCommitMods($revision){
        $commit = $this->getCommit($revision);
        $result =     "<tr>
                            <caption>Modifications</caption>
                            <th>Kind</td>
                            <th>Action</td>
                            <th>Path</td>
                        </tr> ";
        
        foreach($commit->getModifications() as $mod){
            $path = $mod->getPath();
            $result .= "<tr> 
                            <td>{$mod->getKind()}</td>
                            <td>{$mod->getAction()}</td>";
            //only add a link if they're files that exist in the portfolio
            if($this->ioContainerExists($path) && 
                $this->getIOContainer($path)->getKind() == 'file'){
                $result .=  "<td><a class='commitInfo' href='../fileInfo.php/?action={$path}'>{$path}</a></td>";
            }
            else{
                $result .=  "<td>{$path}</td>";
            }
            $result .= "</tr>";
        }
        return "<table id='commitTable'>{$result}</table>";
    }
    
    /**
     * @param $path
     * @return string
     * returns the html comments corresponding to the given path
     * assumes the given path exists as in ioContainerExists()
     */
    public function generateHTMLCommentsForPath($path){
        $ioc = $this->getIOContainer($path);
        $rootComment = $ioc->getRootComment();
        return $this->generateHTMLStringFromComment($rootComment, $rootComment);
    }


    //make it just a nested list of text areas-all disabled except for one that will pop up when reply button is hit;
    public function generateHTMLStringFromComment($comment, $rootComment, $tabs = "", $result = ""){
        //replace "/n" with "<br>
        $text = stripcslashes($comment->getText());
        $text = htmlspecialchars($text);
        
        $commentId = $comment->getID();

        //we never actually print the root comment
        if ($comment == $rootComment) {
            $result .= "{$tabs} <ol class='tree'> \n";
        } else {
            $result .= "{$tabs}<div>";
            $result .= "{$tabs}<label class='comment' for='{$commentId}'> <pre class='comment'>{$text}</pre> </label> \n";
            $result .= "{$tabs}<button class='startReplyButton' data-filepath='{$comment->getPath()}' data-commentid='{$commentId}'>Reply</button><br>";
            $result .= "{$tabs}</div>";
            //todo: critical note: in "data-something", "something" must be all lower case or you can't retrieve the data(or use dashes)(fucking jquery)

                            
            $result .= "{$tabs} <ol>";
        }
        foreach ($comment->getChildren() as $childComment) {
            $result .= "{$tabs} <li class='collapsible comment'> \n";
            $result = $this->generateHTMLStringFromComment($childComment, $rootComment, $tabs . "\t", $result);
            $result .= "{$tabs} </li> \n";
        }
        $result .= "{$tabs} </ol> \n";
        return $result;
    }

    public function generateHTMLString(){
        return $this->generateHTMLStringFromDir($this->rootDir, $this->rootDir);
    }
    /**
     * @param $ioContainer
     * @param $rootDir
     * @param string $tabs
     * @param string $result
     * @return string
     * returns an html representation of the files and directories
     * starting from specified directory
     */
    public function generateHTMLStringFromDir($ioContainer, $rootDir, $tabs = "", $result = ""){
        $baseName = $ioContainer->getBaseName();
        $path = $ioContainer->getPath();

        if( $ioContainer->getKind() == 'dir'){
            if($ioContainer == $rootDir){
                $tabs . '<ol class="tree">' . "\n";
            }
            else {
                $result .= $tabs . '<label class="folder" for="' . $path. '"><span class="folder">' . $baseName . '</span></label> <input type="checkbox" id="' . $path . '" />' . "\n";
                $result .= $tabs . '<ol>' . "\n";
            }
            foreach ($ioContainer->getContents() as $content){
                if($content->getKind() == "file") {
                    $result .= $tabs . '<li class="file">' . "\n";
                }
                else{
                    $result .= $tabs . '<li class="collapsible folder">' . "\n";
                }
                $result = $this->generateHTMLStringFromDir($content, $rootDir, $tabs."\t", $result);
                $result .= $tabs . '</li>' . "\n";
            }
            $result .=  $tabs . '</ol>' . "\n";
        }
        else{
            $result .= $tabs . '<a href="/Assignment3.2/webView/pages/fileInfo.php/?action=' . $path . '"><span class="file">' . $ioContainer->getBaseName() . '</span></a>' . "\n";
        }
        return $result;
    }
    
    
    public function printAllComments(){
        echo "All Comments:<br>";
        foreach($this->comments as $comment){
            echo "Path: {$comment->getPath()}" . Utils::newLine();
            echo "ParentID: {$comment->getParentId()}" . Utils::newLine();
            echo "Contents: {$comment->getContents()}" . Utils::newLine();
        }
    }


    public function printFilesWithCommits(){
        foreach ($this->path2IODict as $file){
            print $file->toString_less();
        }
    }

}