<?php
include_once 'fileClasses.php';
include_once 'commitInfo.php';
include_once 'utils.php';
include_once 'portfolio.php';
/**
 * Created by PhpStorm.
 * User: daxterix
 * Date: 3/7/16
 * Time: 11:57 PM
 */
class SvnXmlParser{
    public $path2IODict = [];
    public $rootDir = NULL;
    public $files = [];
    public $directories = [];
    public $revision2commitDict = [];
    public $commits = [];
    public $commitMods = [];
    public $comments = [];



    public function __construct(){
    }

    /**
     * @param $svnListFile_xml
     * @param $svnLogFile_xml
     * @return MDirectory|null
     * Given two xml files as obtained from 'svn list' 'svn log --verbose',
     * returns a root directory object, which contains information on the
     * files and directories in the xml file. First creates the files and
     * directories then goes through each of the created elements and updates
     * their parentDir as well as their contents[] in the case of directories
     * Lastly, it reads the svnLogFile_xml and adds all relevant commits
     *
     */
    public function generatePortfolio($svnListFile_xml, $svnLogFile_xml){
        $this->createFilesNDirs($svnListFile_xml);
        $this->nestDirs();

        $this->createCommits($svnLogFile_xml);
        $this->addCommits2Files();

        $portfolio = new Portfolio($this->path2IODict, $this->rootDir, $this->files,
            $this->directories, $this->revision2commitDict, $this->commits, $this->commitMods, $this->comments);

        return $portfolio;
    }


    /**
     * @param $svnListFile_xml
     * parses given svnListFile_xml file and creates
     * all files and directories specified in the file
     */
    public function createFilesNDirs($svnListFile_xml){
        if (!file_exists($svnListFile_xml)){
            echo $svnListFile_xml . " does not exist".Utils::newLine().Utils::newLine();
        }
        $xmlContents = simplexml_load_file($svnListFile_xml);

        $rootPath = (string)($xmlContents->list['path']);
        $this->rootDir = new MDirectory($rootPath);
        array_push($this->directories, $this->rootDir);
        $this->rootDir->setParentDir(NULL);
        $this->path2IODict[$rootPath] = $this->rootDir;

        foreach ($xmlContents->list->entry as $file){
            $kind = (string)$file['kind'];
            $path = $rootPath . "/" . (string)$file->name;
            $size = (int)((string)$file->size);
            if ($kind == "dir"){
                $newDir = new MDirectory($path);
                array_push($this->directories, $newDir);
                $this->path2IODict[$path] = $newDir;
            }
            else{
                $newFile = new MFile($path, $size);
                array_push($this->files, $newFile);
                $this->path2IODict[$path] = $newFile;
            }
        }
    }

    /**
     * all the files have been created, but they are not
     * connected with other IO objects, so we nest directories
     * and subdirectories, etc.
     */
    public function nestDirs(){
        foreach (array_values($this->path2IODict) as $ioContainer ){
            if($ioContainer != $this->rootDir) {
                $parentPath = $ioContainer->getParentPath();
                $parentDir = $this->path2IODict[$parentPath];
                $ioContainer->setParentDir($parentDir);
                $parentDir->addContent($ioContainer);
            }
        }
    }


    /**
     * @param $fileName
     * @return array
     * generates info of all commits in $filename, in
     * sorted order, latest first.
     */
    public function createCommits($fileName){
        if (!file_exists($fileName)){
            echo $fileName . " does not exist <br><br>";
        }

        $xmlContents = simplexml_load_file($fileName);
        foreach ($xmlContents->logentry as $commit){
            $auth = (string)$commit->author;
            $rNum = (string)(int)$commit['revision'];
            $dateTimeStr = (string)$commit->date;
            $message = (string)$commit->msg;
            $commitInfo = new Commit($rNum, $auth, $dateTimeStr, $message);
            array_push($this->commits, $commitInfo);
            $this->revision2commitDict[$rNum] = $commitInfo;

            foreach ($commit->paths->path as $modification){
                $action = (string)$modification['action'];
                $kind = (string)$modification['kind'];
                $path = $this->getRightPathFromCommitModPath((string)$modification);
                $commitInfo->addModification($action, $kind, $path);
            }
        }
        //so weird: sorting function is referenced as a string
        usort($this->commits, "Commit::timeCompare");
    }

    /**
     * @param $pathWithBaseDir
     * takes path from form "/pibe2/Assignment2.0/..." to form:
     * "Assignment2.0/..."
     * @return string
     */
    public function getRightPathFromCommitModPath($pathWithBaseDir){
        $pathChunks = explode("/", $pathWithBaseDir);
        $pathWithoutBaseDir = implode("/", array_splice($pathChunks, 2));
        return "{$this->rootDir->getPath()}/{$pathWithoutBaseDir}";
    }


    /**
     * for each commit, we to all files it modifies, providing
     * that the file was one of those read from the svnList file
     */
    public function addCommits2Files(){
        $path2isAdded = [];
        foreach ($this->commits as $commit){
            foreach ($commit->getModifications() as $mod){
                $path = $mod->getPath();
                if(array_key_exists($path, $this->path2IODict)){
                    $file = $this->path2IODict[$path];
                    if(array_key_exists($path, $path2isAdded) == false) {
                        $file->addCommit($commit);
                        array_push($this->commitMods, $mod);

                        if($mod->action == "A"){
                            $path2isAdded[$path] = true;
                        }
                    }
                }
            }
        }
    }
}
