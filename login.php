<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCR Tracks</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="js/js.cookie.min.js"></script>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="dsLightbox">
    </div>
    <div id="content">
        <!-- <div id="dsCloseFullScreen" onclick="closeFullscreen()">Exit full screen view</div> -->
        <section id="dsLogin">
            <div id="dsMenuButton">
                <a href="all_track_exporter.php">
                    <button id="dsMenu">
                        Reports
                    </button></a>
            </div>
            <div id="dsClientLogo"><img src="images/kcr-logo-cropped.png" alt=""></div>
            <div id="dsUserList"></div>
            <div id="dsUserForm">
                <div id="dsFormDiv">
                    <form class="dsForm" action="">

                        <div class="dsHeadline">
                            <h2>Please enter your name.</h2>
                        </div>
                        <div id="dsPrompt">
                            <!-- <div class="dsSelect">
                                <select name="UserList" id="dsUserList"></select>
                                <button id="dsSwapInput">New User</button>
                            </div> -->

                            <div class="dsInputDiv">
                                <input id="dsGetName" type="text">
                                <!-- <button id="dsSwapInput">Existing User</button> -->
                            </div>
                        </div>
                    </form>
                    <div class="dsButtonDiv">
                        <button class="dsButton" onclick="dsSaveName()">
                            OK
                        </button>
                    </div>
                </div>
                <div id="dsLogoDiv"><img src="images/tracks-logo.png" alt=""></div>
                <div id="dsCurrentUsers" class="">Show current users</div>
        </section>

        <section id="dsSession">
            <div id="dsClientLogo"><img src="images/kcr-logo-cropped.png" alt=""></div>
            <div id="dsSessionListDiv">
                <div class="dsHeadline">
                    <h2 id="userName"></h2>
                </div>
                <div id="dsSessionList">
                    <h2>Choose an existing session or create a new one </h2>
                    <div id="dsSessionItems" class="clearfix">
                    </div>
                    <div id="dsTrackList"></div>
                    <div class=" dsButtonBar dsRight">
                        <button title="Return to the home screen." id="dsCancel" class="dsButton"
                            onclick="gotoLoginView()">
                            Home
                        </button>
                        <button title="Upload new files and create a new session." id="dsNewSession" class="dsButton"
                            onclick="gotoPlayerView()">
                            Create a new Session
                        </button>
                    </div>
                </div>
                <div id="dsLogoDiv"><img src="images/tracks-logo.png" alt=""></div>
        </section>

        <section id="dsPlayer">
            <div id="fileButtonDiv">
                <input id="fileupload" type="file" name="fileupload" multiple />
            </div>
            <div id="dsClientLogo"><img src="images/kcr-logo-cropped.png" alt=""></div>
            <div id="dsAudioPlayer">
                <div id="fileList"></div>
                <div id="dsPlayerDContainer">
                    <div id="audioPlayer"></div>
                    <div id="dsMenuButton">
                        <a href="login.php">
                            <button id="dsMenu">
                                Home
                            </button>
                            <button id="dsAddToList">
                                Add Tracks
                            </button>
                        </a>
                    </div>
                </div>
            </div>
            <div id="dsLogoDiv"><img src="images/tracks-logo.png" alt=""></div>
        </section>

        <section>
            <div id="dsIframeDiv">
                <iframe src="http://localhost:90/kcr-tracks/login.php" frameborder="0">

                </iframe>
            </div>
        </section>
    </div>



    <script>
    let data;
    let num;
    let user;
    let username, usernameDateTime = "";
    let existingUsers = [];
    let sessionArray = [];
    let sessionsList = [];
    let sessionItem;
    let userSessionsList = [];
    let fileHTML;
    let path;
    let sessionPath;
    let escName;

    //  GET EXISTING SYSTEM USER AND SESSION DATA
    var base_url = window.location.origin + "/kcr-tracks/";
    getSessions();

    /***********************        
      LOGIN FUNCTIONS        
     *************************/

    function dsSaveName() {
        username = document.getElementById("dsGetName").value;

        //  CLEAN UP USERNAME SO IT DOES NOT BEAK THE SYSTEM 
        username = username.replace(/-/g, "");
        username = username.replace(/ /g, "");

        //  CHECK AND FORMAT USERNAME
        if (username.length > 3) {
            usernameDateTime = username + "-" + dsDateStamp();

            if ($.inArray(username, existingUsers) != -1) {
                getSessionsList();

                gotoSessionView();

            } else {
                gotoPlayerView();
            }
        } else {
            alert("Please enter your name - must be longer than three characters");
            return
        }
    }


    /*****************************
     * NEW SESSION FUNCTIONS
     ****************************/

    //  AFTER FILES HAVE BEEN SELECTED AND OK'd, UPLOAD THEM AUTOMATICALLY USING PHP SCRIPT
    document.getElementById("fileupload").onchange = function(e) {
        $("#fileList").html(
            "<div class='dsUploadMessage'><h2>HI THERE.</h2>  <br />We're preparing your .MP3 files right now. We won't be long!</div>"
        );

        //  IF CHECK IF NEW SESSION OR ADD FILES TO EXISTING SESSION

        // $("#dsPlayer").hasClass("dsAddFileFlag") ? alert('Flag') : alert('No Flag');
        $("#dsPlayer").hasClass("dsAddFileFlag") ? dsAddToSession() : uploadFile();

    }

    //  ONCE FILES ARE UPLOADED, CREATE INTERFACE ELEMENTS WITH NAMES AND URLS IN DATA- ATTRIBUTES
    async function uploadFile() {
        //  SET DEFAULT PATH
        path = base_url + "/music/" + usernameDateTime + "/";

        //  GET THE FILE DATA ON THE UPLOADED MUSIC TRACKS
        let fileNames = fileupload.files;
        let fileList = [];
        let formData = new FormData();
        let numFiles = fileupload.files.length;

        //  CLEAR VARIABLE
        fileHTML = "";


        // INTERATE THROUGH THE TRACK LIST AND CREATE THE ELEMENTS
        for (let i = 0; i < numFiles; i++) {


            formData.append("file", fileupload.files[i]);
            await fetch(base_url + '/upload.php', {
                method: "POST",
                body: formData
            });

            //  ENCODE THE ' CHARACTER
            escName = fileNames[i].name.replace("'", "%27");

            fileHTML = "<div id='" + (i + 1) + "' class='dsAudioFileName' data-url='" + path + escName +
                "' data-name='" + escName + "'  onclick='dsPlayAudio(this)' >" +
                fileNames[i].name + "</div>"

            fileList = fileList + fileHTML + "<br />";
        }

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        document.getElementById("fileList").innerHTML = fileList;

        //  HIDE FILE UPLOAD DIV
        $("#fileButtonDiv").css('visibility', 'hidden');

        //  ADD ELEMENT TO THE PLAYER DIV        
        document.getElementById("audioPlayer").innerHTML =
            "<div class='dsNotify'>Choose a track to play.</div>";

        // CLEAN UP
        document.getElementById("fileupload").value = null

    }

    async function dsAddToSession() {
        //  GET THE FILE DATA ON THE UPLOADED MUSIC TRACKS
        let fileNames = fileupload.files;
        let fileList = [];
        let formData = new FormData();
        let numFiles = fileupload.files.length;

        //  CLEAR VARIABLE
        fileHTML = "";


        // INTERATE THROUGH THE TRACK LIST AND CREATE THE ELEMENTS
        for (let i = 0; i < numFiles; i++) {
            let escName;

            formData.append("file", fileupload.files[i]);
            await fetch(base_url + '/upload.php', {
                method: "POST",
                body: formData
            });
        }

        // dsDisplayTracks(session)
    }

    function dsGetFileList_AfterAdd() {

        //  GET THE NAME

        //  RETURN ALL DATA FOR THAT NAME

        //  GET THE SELECTED FOLDER DATA

        //  CALL TO DISPLAY THE DATA

    }

    // UPON TRACK LIST CLICK, PLAY FILE
    let currentTrackID = "";

    function dsPlayAudio(e) {

        // EXTRACT DATA ATTRIBUTES
        let audioID = e.getAttribute("id");
        let audioURL = e.getAttribute("data-url");
        let trackName = e.getAttribute("data-name");

        //  REMOVE ENCODING ON TRACK NAME
        //trackName = trackName.replace(/%27/g, "'");

        if (currentTrackID === audioID) {
            // alert('same track');
            document.getElementById("audioPlayer").innerHTML =
                "<div class='dsNotify'>Choose another track to play.</div>";
            dsClearActive();
            currentTrackID = "";
            return
        }

        // SET THE CLICKED ELEMENT TO ACTIVE
        dsClearActive();
        e.classList.add("dsActive");
        currentTrackID = audioID;

        //  BUILD THE HTML5 AUDIO TAGS
        let audioHtml = "<audio id='dsAudio' controls autoplay> <source src='" + audioURL +
            "' type='audio/mpeg'></audio><div class='dsTrackTitle'><span class='dsTitle'>Title: </span>" +
            trackName.replace("%27", "'") +
            "</div>";

        // SET THE TRACK TO PLAY IN THE AUDIO PLAYER DIV
        document.getElementById("audioPlayer").innerHTML = audioHtml;

        // CLEAR PLAYER WHEN FILE IS DONE
        const audio = document.querySelector('audio');
        audio.addEventListener('ended', (event) => {
            document.getElementById("audioPlayer").innerHTML =
                "<div class='dsNotify'>Choose another track to play.</div>";
            dsClearActive();
            currentTrackID = "";
        });
    }

    //  CLEAR ALL ACTIVE TRACK CLASSES FROM TRACK ELEMENTS
    function dsClearActive() {
        let audioButton = document.getElementsByClassName("dsActive");
        while (audioButton.length) audioButton[0].classList.remove("dsActive");
    }

    /******************************
     *    CHANGE VIEW FUNCTIONS
     *****************************/

    function gotoLoginView() {
        //  SWAP TO LOGIN VIEW  
        const dsSessionList = document.getElementById("dsSession");
        dsSessionList.style.display = 'none';
        const dsPlayer = document.getElementById("dsPlayer");
        dsPlayer.style.display = 'none';

        $("#dsGetName").val("");

        const dsForm = document.getElementById("dsLogin");
        dsForm.style.display = 'block';
    }

    function gotoSessionView() {
        //  SWAP TO SESSION LIST VIEW                 
        document.getElementById('userName').innerHTML = "USER: " + username;

        const dsForm = document.getElementById("dsLogin");
        dsForm.style.display = 'none';

        const dsSessionList = document.getElementById("dsSession");
        dsSessionList.style.display = 'block';
    }

    function gotoPlayerView() {
        //  SWAP TO FILE MANAGMENT VIEW
        const dsForm = document.getElementById("dsLogin");
        dsForm.style.display = 'none';
        const dsSession = document.getElementById("dsSession");
        dsSession.style.display = 'none';

        const dsPlayer = document.getElementById("dsPlayer");
        dsPlayer.style.display = 'block';

        //  SET THE COOKIE FOR THE PHP SCRIPT
        Cookies.set('username', usernameDateTime, {
            expires: 14
        })
    }

    function getSessions() {
        jQuery.ajax({
            url: 'json.php',
            type: 'POST',
            data: {
                option: 'users'
            },
            success: function(data) {
                // console.log(data);
                num = data.length;
                jQuery("#dsData").html(data);

                var obj = jQuery.parseJSON(data);
                jQuery.each(obj, function(key, value) {
                    user = (value.name);
                    //  GET USERNAME
                    username = user.substr(0, user.indexOf('-'));
                    existingUsers.push(username);

                    // console.log("existingUsers:  ");
                    // console.log(existingUsers);

                    //  GET SESSION DATE TIME
                    sessionItem = user.substr(user.length - 11);
                    sessionsList.push(getDateTime(sessionItem));

                    sessionArray.push({
                        Username: username,
                        Session: getDateTime(sessionItem)
                    });
                });

                //  GET SINGLE INSTANCE OF USERNAMES
                userList = jQuery.uniqueSort(existingUsers);
                // console.log("userList:  ");
                // console.log(userList);
                let dsUsersDisplay;

                //  CHECK IF THERE ARE CURRENT USERS AND DISPLAY THEM ELSE DISPLAY MESSAGE
                if ((userList).length > 0) {
                    $.each(userList, function(key, val) {
                        let dsUser = "<div class='dsUserName' id='" + val + "' >" + val +
                            "</div>";
                        dsUsersDisplay = (dsUsersDisplay) ? dsUsersDisplay + dsUser : dsUser;
                    })
                } else {
                    dsUsersDisplay = "No Users Yet";
                }

                //  ADD TO USERS DROP DOWN
                $("#dsUserList").html(
                    "<h3 class='dsH3' >Select a current user or click New User.</h3> <div class='dsUserListDiv'>" +
                    dsUsersDisplay +
                    "<div class='dsButtonDiv'><button id='dsCancel'>Cancel</button><button id='dsNewUser'>New User</button></div"
                );
            }
        })
    }


    function getSessionsList() {
        return $.ajax({
            url: 'json.php',
            type: 'POST',
            data: {
                u_name: username
            },
            success: function(data) {
                let allSessions;
                let obj = jQuery.parseJSON(data);
                $.each(obj, function(key, value) {
                    let tracks = '"' + value.music.toString() + '"';
                    let directoryName = value.name;
                    let sessionDetails = directoryName.split('-');

                    // REFORMAT NAME AND DATE TIME FOR DISPLAY
                    let time = sessionDetails.pop();
                    let date = sessionDetails.pop();
                    let user = sessionDetails.pop();
                    var result, timeFormatted;

                    //  ADD COLONS TO TIME FORMAT (WITH HACK FOR 4 AND 6 NUMBER DATE-TIMES)
                    if (time.length === 6) {
                        result = [
                            time.slice(0, 2), ':', time.slice(2),
                        ].join('')
                        timeFormatted = [
                            result.slice(0, 5), ':', result.slice(5),
                        ].join('')
                    } else {
                        timeFormatted = [
                            time.slice(0, 2), ':', time.slice(2),
                        ].join('')
                    }

                    //  APPLY NORMAL FORMATTING TO DATE
                    var year = "/20" + date.substr(0, 2);
                    var month = "/" + date.substr(2, 2);
                    var day = date.substr(4, 2);
                    var dateFormatted = day + month + year;

                    //  CREATE HTML TRACK DISPLAY ITEM
                    let mySession =
                        '<div id="' + directoryName +
                        '" class="dsSessionName" title="Click item to load this session." data-tracks=' +
                        tracks + '>' + user + '  | <span class="dsSmall dsItalic"> Date: ' +
                        dateFormatted + ' - ' +
                        timeFormatted +
                        '</span></div>' +
                        '<button title="Click item to preview tracks in this session." data-tracks=' +
                        tracks +
                        ' class="dsShowTracks">Show Tracks</button>' +
                        '<button title="Add new tracks to this session." data-folder=' +
                        value.name +
                        ' class="dsAddTracks">Add Tracks</button>';

                    allSessions = (allSessions) ? mySession + allSessions : mySession;
                })

                //  ADD TRACK DETAILS TO THE INTERFACE
                $("#dsSessionItems").html(allSessions);
            }
        });
    }


    $(document).on('click', '.dsUserName', function() {
        username = $(this).attr('id');
        // $("#dsGetName").val($(this).attr('id'));
        $("#dsLightbox").fadeOut(200);
        $("#dsUserList").fadeOut(200);
        $("#dsCurrentUsers").html("Show current users")
        $("#dsCurrentUsers").removeClass("dsActive");

        //  THESE FUNCTIONS USE THE username VARIABLE AS THEIR PARAMETER
        getSessionsList();
        gotoSessionView();
    })
    $(document).on('click', '#dsAddToList', function() {
        $("#dsIframeDiv").fadeIn(400);
    })

    $(document).on('click', '#dsCurrentUsers', function() {
        if ($("#dsCurrentUsers").hasClass('dsActive')) {
            $("#dsLightbox").fadeOut(200);
            $("#dsUserList").fadeOut(200);
            $("#dsCurrentUsers").html("Show current users")
            $("#dsCurrentUsers").removeClass("dsActive");

        } else {
            $("#dsLightbox").fadeIn(200);
            $("#dsUserList").fadeIn(200);
            $("#dsCurrentUsers").html("Hide current users")
            $("#dsCurrentUsers").addClass("dsActive");
        }
    })

    $(document).on('click', '#dsNewUser', function() {
        $("#dsGetName").val($(this).attr('id'));
        $("#dsLightbox").fadeOut(200);
        $("#dsUserList").fadeOut(200);
        $("#dsCurrentUsers").html("Show current users")
        $("#dsCurrentUsers").removeClass("dsActive");
        $("#dsGetName").val("");
        $("#dsGetName").focus();

        return false;
    })

    $(document).on('click', '#dsCancel', function() {
        $("#dsLightbox").fadeOut(200);
        $("#dsUserList").fadeOut(200);
        $("#dsCurrentUsers").html("Show current users")
        $("#dsCurrentUsers").removeClass("dsActive");
        $("#dsGetName").val("");
        $("#dsGetName").focus();

        return false;
    })

    $(document).on('click', '.dsSessionName', function() {

        //  CLEAR VARIABLE
        fileHTML,
        fileList = "";
        usernameDateTime = $(this).attr('id');;
        var dsTracks = $(this).attr("data-tracks");
        dsTracks = dsTracks.split(',')

        //  SET DEFAULT PATH
        path = base_url + "/music/" + usernameDateTime + "/";

        $.each(dsTracks, function(key, value) {
            escName = value.replace("'", "%27");
            fileHTML = "<div id='" + value + "' class='dsAudioFileName' data-url='" + path +
                escName + "' data-name='" + escName +
                "'  onclick='dsPlayAudio(this)' >" +
                value + "</div><br />"

            fileList = (fileList) ? fileList + fileHTML : fileHTML;
        })

        gotoPlayerView();
        // $("#fileButtonDiv").hide();
        $("#fileButtonDiv").css('visibility', 'hidden');

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        $("#fileList").html("");
        $("#fileList").append(fileList);
        // document.getElementById("fileList").innerHTML = fileList;

        //  ADD ELEMENT TO THE PLAYER DIV        
        document.getElementById("audioPlayer").innerHTML =
        "<div class='dsNotify'>Choose a track to play.</div>";
    });

    function dsDisplayTracks() {

        //  CLEAR VARIABLE
        fileHTML,
        fileList = "";
        usernameDateTime = sessionPath;

        //  GET ARRAY OF FILES 
        var dsTracks = $(this).attr("data-tracks");


        dsTracks = dsTracks.split(',')

        //  SET DEFAULT PATH
        path = base_url + "/music/" + usernameDateTime + "/";

        $.each(dsTracks, function(key, value) {
            fileHTML = "<div id='" + value + "' class='dsAudioFileName' data-url='" + path +
                value + "' data-name='" + value + "'  onclick='dsPlayAudio(this)' >" +
                value + "</div><br />"

            fileList = (fileList) ? fileList + fileHTML : fileHTML;
        })

        gotoPlayerView();
        // $("#fileButtonDiv").hide();
        $("#fileButtonDiv").css('visibility', 'hidden');

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        $("#fileList").html("");
        $("#fileList").append(fileList);
        // document.getElementById("fileList").innerHTML = fileList;

        //  ADD ELEMENT TO THE PLAYER DIV        
        document.getElementById("audioPlayer").innerHTML =
        "<div class='dsNotify'>Choose a track to play.</div>";
    }

    $(document).on('click', '.dsShowTracks', function() {
        // var dsTracks = $(this).attr("data-tracks").split(',').join('\n');
        var dsTracks = $(this).attr("data-tracks").replace(/ *, */g, '<br>');
        // console.log(dsTracks);
        $("#dsLightbox").fadeIn(200);
        $("#dsTrackList").html("<div class='dsTrackListDiv'>" + dsTracks.replace("%27", "'") +
                "</div><div class='dsCloseBar'><button class='dsCloseButton'>Close List</div></div>")
            .fadeIn(
                300);
        // alert(dsTracks);
    });

    $(document).on('click', '.dsAddTracks', function() {
        //  GET FOLDER ID
        let dsFolderID = $(this).attr("data-folder");

        //  SAVE THE SESSION PATH TO BUILD PLAY LIST ITEMS
        sessionPath = dsFolderID;

        //  SET COOKIE
        Cookies.set('username', dsFolderID, {
            expires: 14
        })
        // alert(dsFolderID);

        //  SWAP TO FILE MANAGMENT VIEW
        const dsForm = document.getElementById("dsLogin");
        dsForm.style.display = 'none';
        const dsSession = document.getElementById("dsSession");
        dsSession.style.display = 'none';

        const dsPlayer = document.getElementById("dsPlayer");
        dsPlayer.style.display = 'block';
        dsPlayer.classList.add("dsAddFileFlag");
        //document.getElementById("fileButtonDiv").style.visibility = 'visible';
        document.getElementById("fileButtonDiv").style.removeProperty("visibility");
        document.getElementById("audioPlayer").style.display = 'none';

    });


    //  ONCE FILES ARE UPLOADED, CREATE INTERFACE ELEMENTS WITH NAMES AND URLS IN DATA- ATTRIBUTES
    async function dsAddFile() {
        //  SET DEFAULT PATH
        // path = base_url + "/music/" + usernameDateTime + "/";

        //  GET THE FILE DATA ON THE UPLOADED MUSIC TRACKS
        let fileNames = fileupload.files;
        let fileList = [];
        let formData = new FormData();
        let numFiles = fileupload.files.length;

        //  CLEAR VARIABLE
        fileHTML = "";


        // INTERATE THROUGH THE TRACK LIST AND CREATE THE ELEMENTS
        for (let i = 0; i < numFiles; i++) {
            let escName;

            formData.append("file", fileupload.files[i]);
            await fetch(base_url + '/upload.php', {
                method: "POST",
                body: formData
            });

            //  ENCODE THE ' CHARACTER
            escName = fileNames[i].name.replace("'", "%27");

            fileHTML = "<div id='" + (i + 1) + "' class='dsAudioFileName' data-url='" + path + escName +
                "' data-name='" + escName + "'  onclick='dsPlayAudio(this)' >" +
                fileNames[i].name + "</div>"

            fileList = fileList + fileHTML + "<br />";
        }

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        document.getElementById("fileList").innerHTML = fileList;

        //  HIDE FILE UPLOAD DIV
        $("#fileButtonDiv").css('visibility', 'hidden');

        //  ADD ELEMENT TO THE PLAYER DIV        
        document.getElementById("audioPlayer").innerHTML =
            "<div class='dsNotify'>Choose a track to play.</div>";

        // CLEAN UP
        document.getElementById("fileupload").value = null

    }


    $(document).on('click', '.dsCloseButton', function() {
        $("#dsLightbox").fadeOut(200);
        $("#dsTrackList").fadeOut(150);
        $("#dsUserList").fadeOut(200);
    });


    /************************************
     * LOAD EXISTING SESSION
     ***********************************/

    function getSessionTracks() {
        $.ajax({
            url: 'json.php',
            type: 'POST',
            data: {
                t_name: 'Peter-221003-0933'
            },
            success: function(data) {
                console.log(data);
            }
        });
    }

    function getDateTime(sessionDT) {
        let date = sessionDT.substr(0, sessionDT.indexOf('-'));
        let year = "20" + sessionDT.substr(0, 2);
        let month = sessionDT.substr(2, 2);
        let day = sessionDT.substr(4, 2);
        let hour = sessionDT.substr(7, 2);
        let minute = sessionDT.substr(9, 2);

        return day + "/" + month + "/" + year + " " + hour + ":" + minute;
    }

    //  CREATE A DATE AND TIME STAMP TO USE IN THE UPLOADED MUSIC DIRECTORY NAME
    function dsDateStamp() {
        let currentDate = new Date();
        let date = currentDate.getDate();
        let month = currentDate.getMonth();
        let year = currentDate.getFullYear().toString().substr(-2); // Two digits only
        let hours = currentDate.getHours();
        let minutes = currentDate.getMinutes();
        let seconds = currentDate.getSeconds();
        let stamp = year + pad(month + 1) + pad(date) + "-" + pad(hours) + pad(minutes) + pad(seconds);

        return stamp;
    }

    //  PAD DATE/TIME ELEMENTS TO TWO DIGITS
    function pad(n) {
        return n < 10 ? '0' + n : n;
    }
    </script>

</body>

</html>