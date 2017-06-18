<?php
include_once 'fileClasses.php';
include_once 'commitInfo.php';
include_once 'utils.php';
include_once 'dbUtils.php';
include_once 'portfolio.php';


//TODO: use prepared statements
class DBLoader{
    public $path2IODict;
    public $rootDir;
    public $files;
    public $directories;
    public $commits;
    public $commitMods;
    public $revision2commitDict;
    public $comments;
    public $id2commentDict;


    public function __construct(){
    }

    /**creates db connection using the info ther, First creates the files and
     * directories then goes through each of the created elements and updates
     * their parentDir as well as their contents[] in the case of directories.
     * Then it reads the commit information and adds all relevant commits.
     * Lastly it reads all the comments, and encapsulating them as descendants
     * of their corresponding file/dir's default rootDirectory.
     */
    public function generatePortfolio(){
        $this->resetAttrs();

        $conn = DBUtils::createDataBaseConnection();
        $this->createFilesNDirs($conn);
        $this->nestDirs();
        $this->createCommits($conn);
        $this->createCommitMods($conn);
        $this->addCommits2Files();
        $this->loadComments($conn);
        $this->nestCommentsNUnifyWFiles();
        DBUtils::closeDataBaseConnection($conn);

        $portfolio = new Portfolio($this->path2IODict, $this->rootDir, $this->files,
            $this->directories, $this->revision2commitDict, $this->commits, $this->commitMods, $this->comments);

        return $portfolio;
    }

    public function resetAttrs(){
        $this->path2IODict = [];
        $this->rootDir = NULL;
        $this->files = [];
        $this->directories = [];
        $this->commits = [];
        $this->commitMods = [];
        $this->revision2commitDict = [];
        $this->comments = [];
        $this->id2commentDict = [];
    }


    /**
     * @param $dbConn
     * queries given sql database connection and creates
     * all files and directory object specified in the file
     * assumes at least the root directory is in the database
     */
    public function createFilesNDirs($conn){
        $selectQuery = "SELECT * FROM `cs242Assig3` . `ioObjects`";
        $preparedQuery = $conn->prepare($selectQuery);
        $preparedQuery->execute();
        $path = '';
        $parentPath = '';
        $kind = '';
        $size = '';
        $preparedQuery->bind_result($path, $parentPath, $kind, $size);
        
        while ($preparedQuery->fetch()) {
            if ($kind == 'dir') {
                $newDir = new MDirectory($path);
                array_push($this->directories, $newDir);
                $this->path2IODict[$path] = $newDir;
            } else {
                $newFile = new MFile($path, $size);
                array_push($this->files, $newFile);
                $this->path2IODict[$path] = $newFile;
            }
        }
        $preparedQuery->close();
    }


    /**
     * all the files have been created, but they are not
     * connected with other IO objects, so we nest directories
     * and subdirectories, etc.
     */
    public function nestDirs(){
        foreach (array_values($this->path2IODict) as $ioContainer ){
            $parentPath = $ioContainer->getParentPath();
            if(array_key_exists($parentPath, $this->path2IODict)) {
                $parentDir = $this->path2IODict[$parentPath];
                $ioContainer->setParentDir($parentDir);
                $parentDir->addContent($ioContainer);
            }
            else{
                $this->rootDir = $ioContainer;
                $ioContainer->setParentDir(NULL);
            }
        }
    }


    /**
     * @return array
     * generates info of all commits in database, in
     * sorted order, latest first.
    */
    public function createCommits($conn){
        $selectQuery = "SELECT * FROM `cs242Assig3` . `commits`";
        $preparedQuery = $conn->prepare($selectQuery);
        $preparedQuery->execute();
        $rNum = '';
        $auth = '';
        $dateTimeStr = '';
        $message = '';
        $preparedQuery->bind_result($rNum, $auth, $message, $dateTimeStr);

        while ($preparedQuery->fetch()) {
            $commitInfo = new Commit($rNum, $auth, $dateTimeStr, $message);
            array_push($this->commits, $commitInfo);
            $this->revision2commitDict[$rNum] = $commitInfo;
        }
        $preparedQuery->close();
        usort($this->commits, "Commit::timeCompare");
    }

    /**
     * @param $conn
     * loads all commitModifications form the commitModifications
     * table, and adds it to the modifications array, as well as
     * to it's 'parent' commit
     */
    public function createCommitMods($conn){
        $selectQuery = "SELECT * FROM `cs242Assig3` . `commitModifications`";
        $preparedQuery = $conn->prepare($selectQuery);
        $preparedQuery->execute();
        $rNum = '';
        $action= '';
        $kind = '';
        $path = '';
        $preparedQuery->bind_result($rNum, $action, $kind, $path);

        while ($preparedQuery->fetch()) {
            if (key_exists($rNum, $this->revision2commitDict)) {
                $parentCommit = $this->revision2commitDict[$rNum];
                $parentCommit->addModification($action, $kind, $path);
            }
        }
        $preparedQuery->close();
    }


    /**
     * for each commit, we add it to all files it modifies, providing
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

    /**
     * @param $conn
     * loads comments from the db; no connections are made between
     * parent and child comments, nor are any connections made
     * between a comment and it's 'parent' file/dir
     */
    public function loadComments($conn){
        $selectQuery = "SELECT * FROM `cs242Assig3` . `comments` ORDER BY `ID` DESC";
        $preparedQuery = $conn->prepare($selectQuery);
        $preparedQuery->execute();
        $parentId = '';
        $id = '';
        $path = '';
        $text = '';
        $preparedQuery->bind_result($id, $parentId, $path, $text);
        
        while ($preparedQuery->fetch()) {
            $comment = new Comment($id, $parentId, $path, $text);
            $this->id2commentDict[$id] = $comment;
            array_push($this->comments, $comment);
        }
        $preparedQuery->close();
    }

    /**
     * we make explicit the relationship between child and parent
     * comments adding all comments of as a direct or indirect
     * subComment of the file/directory's psuedo rootDirectory
     */
    public function nestCommentsNUnifyWFiles(){
        foreach ($this->comments as $comment){
            $file = $this->path2IODict[$comment->getPath()];
            $parentId = $comment->getParentId();
            $parentComment = NULL;

            //it has no parent, so we add it as a direct child of
            //its file's root-comment
            if($parentId == -1){
                $parentComment = $file->getRootComment();
            }
            else{
                $parentComment = $this->id2commentDict[$parentId];
            }
            $comment->setParent($parentComment);
            $parentComment->addChild($comment);
        }
    }

}
