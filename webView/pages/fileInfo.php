<html lang="en">

<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    //NOTE THAT _POST does not work on my local machine***
?>

<head>
    <meta charset="UTF-8">
    <title>Bloopa</title>
    <link rel="stylesheet" type="text/css" href="/Assignment3.2/webView/styles/copiedStyles.css" media="screen">

    <script src="https://code.jquery.com/jquery-3.0.0-alpha1.js"></script>
    
    <script>
        $(document).ready(function(){
            var fileIframe = $('#fileIframe');
            fileIframe.hide();
            
            assignSubmitCommentBtnsOnClick();
            assignStartReplyBtnsOnClick();
            assignViewFileBtnsOnClick();
        });
        
        //assigns onclick event handlers to buttons that reveal reply textarea
        function assignStartReplyBtnsOnClick(){
            var startReplyBtns = document.getElementsByClassName('startReplyButton');
            for (var i = 0; i < startReplyBtns.length; i += 1) {
                startReplyBtns[i].onclick = function(e) {
                    var filePath = $(this).data('filepath');
                    var parentCommentId = $(this).data('commentid');
                    var btnId = 'SCB' + parentCommentId;
                    var correspTextAreaId = 'TA4' + btnId;
                    var replyElements = "<div class='commentReplyDiv'>" + 
                                            "<textarea class='commentReply' id='" + correspTextAreaId + "' placeholder='Write Your Reply' required>" + 
                                            "</textarea><br>" +
                                            "<button class='submitReplyButton submitCommentButton'" +
                                                    "data-filepath='" + filePath +
                                                    "' id='" +  btnId +
                                                    "' data-corresptextareaid='" + correspTextAreaId +
                                                    "' data-parentcommentid='" + parentCommentId +
                                            "'>Submit Reply</buton>" +
                                        "</div><br>";
                    //todo: critical note: in "data-something", "something" must be all
                    // lower case or you can't retrieve the data with .data() (or use dashes)(fucking jquery)
                    $(this).hide();
                    $(this).parent().append(replyElements);
                    $(this).remove();
                    assignSubmitCommentBtnsOnClick();
                };
            };
        };
        
       
        //assigns onclick handlers for comment submission buttons
        function assignSubmitCommentBtnsOnClick(){
            var btns = document.getElementsByTagName('button');
            for (var i = 0; i < btns.length; i += 1) {
                if($(btns[i]).hasClass('submitCommentButton')){
                    btns[i].onclick = function (e) {
                        var correspTextAreaId = $(this).data('corresptextareaid');
                        var correspTextArea = document.getElementById(correspTextAreaId);
                        submitCommentBtnsOnClick(this, correspTextArea);
                    }; 
                }
            };
        };
        
        
        //onclick event handler for comment submission; ajax call that
        //adds new comment to database and reloads comment section
        function submitCommentBtnsOnClick(submitCommentBtn, correspTextArea) {
            var commentText = encodeURIComponent(correspTextArea.value);
            var parentCommentId = $(submitCommentBtn).data('parentcommentid');
            var filePath = $(submitCommentBtn).data('filepath');
            if (commentText == "") {
                return;
            }
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    var dest = document.getElementById("commentBubblesDiv");
                    var serverResponse = xmlhttp.responseText;
                    correspTextArea.value = '';
                    dest.innerHTML = serverResponse;
                    assignSubmitCommentBtnsOnClick();
                    assignStartReplyBtnsOnClick();
                }
            };
            var requestStr = "../../runThroughAjax/addNewComment.php?" + 
                                "newComment=" + commentText +
                                "&parentCommentId=" + parentCommentId +
                                "&filePath=" + filePath;
            xmlhttp.open("GET", requestStr, true);
            xmlhttp.send();
        }
        
        
        function assignViewFileBtnsOnClick(){
         var btns = document.getElementsByTagName('button');
            for (var i = 0; i < btns.length; i += 1) {
                if($(btns[i]).hasClass('viewFileButton')){
                    btns[i].onclick = function (e) {
                        viewFileBtnOnClick_better(this);
                    }; 
                }
            };
        }
 
        //loads loads a file revision into an iframe 
        function viewFileBtnOnClick_better(button){
            var revision = $(button).data('revision');
            var localDirPath = $(button).data('localdirpath');
            var basename = $(button).data('basename');
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    var serverResponse = xmlhttp.responseText;
                    var fileIframe = $('#fileIframe');
                    fileIframe.attr('src', serverResponse)
                    fileIframe.show();
                    var contentDiv = $('#content');
                    contentDiv.css('margin-left', '20px');
                    contentDiv.css('margin-right', '10px');
                    contentDiv.css('float', 'left');
                }
            };
            var requestStr = "../../runThroughAjax/readFromSvn.php?" + 
                                "revision=" + revision +
                                "&basename=" + basename +
                                "&localDirPath=" + localDirPath +
                                "&svnPath=<?php echo $_GET['action'];?>";
            xmlhttp.open("GET", requestStr, true);
            xmlhttp.send();
        }
 
        

    </script>
    
</head>

<body>
    <?php
    require_once("../../classes/utils.php");
    require_once("../../classes/dbUtils.php");
    require("../../webView/autogeneratedElements/loadPortfolioFromDB.php");
    $tab = Utils::tab();
    
    $rootCommentId = -1;
    $submitCommentBtnId = "SCB{$rootCommentId}";
    $correspTextAreaId = "TA4{$submitCommentBtnId}";
    $headRevision = '10120';
    
    $requestedFilePath = $_GET['action'];
    if(isset($_GET['action']) && $portfolio->ioContainerExists($_GET['action'])
                    && $portfolio->getIOContainer($_GET['action'])->getKind() == 'file') {
        
        $requestedFile = $portfolio->getIOContainer($requestedFilePath);
        //todo insert commits and other file info
        echo     "<div class='navLink'>
                        <a class='navLink' href='/Assignment3.2/webView/pages/index.php'>Home</a>
                        <button class='navLink viewFileButton'
                                data-revision='{$headRevision}'
                                data-localdirpath='{$portfolio->getLocalDirectoryPath($requestedFilePath)}'
                                data-basename='{$requestedFile->getBaseName()}'
                                data-localpath='../../portfolioFiles/{$portfolio->getLocalPath($requestedFilePath)}'>View File
                        </button>
                        {$tab}{$tab} File: {$requestedFile->getBaseName()} 
                 </div>
             
                 <div id='content'> 
                     <div class='fileInfo'>
                        <table class='fileInfo'>
                            <caption>Statistics</caption>
                            <tr>
                                <th>Full Path</th> <td>{$requestedFile->getPath()}</td>
                            </tr>
                            <tr>
                                <th>Size</th> <td>{$requestedFile->getSize()} bytes</td>
                            </tr>
                        </table>
                        
                        {$portfolio->generateHTMLfromCommits($requestedFilePath)}       
                     </div>
                      
                      
                     <div id='commentSectionDiv'>
                         <h2>Comments</h2><br>
                         <div id='addNewCommentDiv'>
                                <textarea class='comment'
                                          id='{$correspTextAreaId}'
                                          name='newComment'
                                          placeholder='Leave a Comment'
                                          required></textarea><br>
                                <button class='submitCommentButton' id='{$submitCommentBtnId}'
                                        data-corresptextareaid='{$correspTextAreaId}'
                                        data-filepath='{$requestedFilePath}'
                                        data-parentcommentid='-1'>Submit Comment</button><br>
                         </div>

                          
                         <div id='commentBubblesDiv'>
                             {$portfolio->generateHTMLCommentsForPath($requestedFilePath)}
                         </div>
                     </div>
                      
                 </div>
                <div id=iframeDiv><iframe id='fileIframe' src=''></iframe></div>";
    }
    else{
        echo "    <div class='navLink'>
                        <a class='navLink' href='/Assignment3.2/webView/pages/index.php'>Home</a>
                        {$tab}{$tab} Error: file not in portfolio
                  </div>";
        echo "NO!" . Utils::newLine();
    }

    ?>
    

</body>

</html>

