<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DS-Tracks</title>
    <link rel="icon" type="image/png" href="images/ds-icon.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="js/jquery-ui-custom.min.js"></script>
    <script src="js/js.cookie.min.js"></script>
    <script src="js/touch-dnd.js"></script>

    <link rel="stylesheet" href="css/style.css?v=7">
    <link rel="stylesheet" href="css/touch.css">
</head>

<body>
    <div id="dsLightbox">
    </div>
    <div id="content" class="touch-screen">

        <!-- ══════════════════════════════════════════════════
             TOUCH INTERFACE - USB Browser & Player
             ══════════════════════════════════════════════════ -->

        <!-- Idle Screen -->
        <div id="idleScreen" style="display:flex">
            <img class="idle-logo" src="images/station-logo.png" alt="Station Logo">
            <div class="idle-message">Insert your USB drive to begin</div>
            <button class="idle-button primary" onclick="dsUsb.goToLegacyLogin()">Return to a previous session</button>
            <div class="idle-footer">
                <a href="all_track_exporter.php">
                    <button class="idle-button" style="width:auto;padding:8px 20px;font-size:14px;min-height:36px">Reports</button>
                </a>
            </div>
        </div>

        <!-- USB File Browser -->
        <div id="usbBrowser" style="display:none">
            <div class="browser-header">
                <div class="header-title" id="browserHeaderTitle">USB Drive</div>
                <button class="btn-select-all" id="btnSelectAll" onclick="dsUsb.selectAll()">Select All</button>
            </div>
            <div class="browser-list" id="browserList"></div>
            <div class="browser-footer">
                <span class="selected-count" id="selectedCount">0 selected</span>
                <div class="footer-buttons">
                    <button class="btn-browser btn-back" onclick="dsUsb.goBack()">&#8592; Back</button>
                    <button class="btn-browser btn-import" id="btnImport" disabled onclick="dsUsb.showUserIdScreen()">Use These Tracks &#8594;</button>
                </div>
            </div>
        </div>

        <!-- User Identification -->
        <div id="userIdScreen" style="display:none">
            <img class="user-id-logo" src="images/station-logo.png" alt="">
            <div class="user-id-panel">
                <h3>Enter your name</h3>
                <input class="name-input" type="text" id="nameInput" placeholder="Your name"
                    onkeydown="if(event.key==='Enter')dsUsb.confirmUsername()">
                <button class="btn-name-ok" onclick="dsUsb.confirmUsername()">OK</button>
                <div class="user-divider">&#8212; or select your name &#8212;</div>
                <div class="existing-users-label">Previous users:</div>
                <div class="existing-user-list" id="existingUsersList"></div>
            </div>
        </div>

        <!-- Touch Player -->
        <div id="touchPlayer" style="display:none">
            <div class="player-header">
                <span class="player-user" id="playerUser"></span>
                <button class="dsHomeIcon" onclick="dsUsb.goHome()" title="Home">&#8962;</button>
                <button class="btn-add-more" onclick="dsUsb.addMoreTracks()">+ Add More</button>
            </div>
            <div class="player-body">
                <div class="player-track-list" id="playerTrackList"></div>
                <div class="player-controls">
                    <div class="player-waiting">Tap a track to play</div>
                    <div class="player-now-playing" style="visibility:hidden">Now Playing:</div>
                    <div class="player-track-title" id="playerTrackTitle"></div>
                    <div class="player-progress-container" style="visibility:hidden"
                         onclick="dsUsb.seekTo(event)" ontouchstart="dsUsb.seekTo(event)">
                        <div class="player-progress-bar">
                            <div class="player-progress-fill"></div>
                        </div>
                    </div>
                    <div class="player-time" style="visibility:hidden">
                        <span id="timeCurrent">0:00</span>
                        <span id="timeDuration">0:00</span>
                    </div>
                    <div class="player-buttons" style="visibility:hidden">
                        <button class="btn-playback btn-play" id="btnPlay" onclick="dsUsb.togglePause()">&#9654; PLAY</button>
                        <button class="btn-playback" onclick="dsUsb.nextTrack()">&#9654;&#9654; NEXT</button>
                    </div>
                </div>
            </div>
            <div class="player-footer">
                <button class="btn-player-nav" onclick="dsUsb.goToSessions()">&#8592; Sessions</button>
                <div class="autoplay-toggle">
                    Auto-play
                    <div class="toggle-switch" id="autoPlayToggle" onclick="dsUsb.toggleAutoPlay()">
                        <div class="toggle-knob"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             ORIGINAL INTERFACE - Login, Sessions, Player
             (hidden by default, accessible via "Return to session")
             ══════════════════════════════════════════════════ -->

        <!-- <div id="dsCloseFullScreen" onclick="closeFullscreen()">Exit full screen view</div> -->
        <section id="dsLogin" style="display:none">
            <div id="dsMenuButton">
                <a href="all_track_exporter.php">
                    <button id="dsMenu">
                        Reports
                    </button></a>
            </div>
            <div id="dsClientLogo"><img src="images/station-logo.png" alt=""></div>
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
            <div id="dsClientLogo"><img src="images/station-logo.png" alt=""></div>
            <button class="dsHomeIcon" onclick="gotoLoginView()" title="Home">&#8962;</button>
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
                        <button title="Upload new files and create a new session." id="dsNewSession" class="dsButton"
                            onclick="showNewSessionPrompt()">
                            Create a new Session
                        </button>
                    </div>
                    <div id="dsNewSessionPrompt" style="display:none">
                        <div class="dsPromptOverlay"></div>
                        <div class="dsPromptPanel">
                            <h3>New Session</h3>
                            <p>Give this session a name (optional):</p>
                            <input type="text" id="dsSessionLabelInput" placeholder="e.g. Classical music" maxlength="100"
                                onkeydown="if(event.key==='Enter')confirmNewSession()">
                            <div class="dsPromptButtons">
                                <button class="dsButton" onclick="cancelNewSession()">Cancel</button>
                                <button class="dsButton dsButtonAccent" onclick="confirmNewSession()">Continue</button>
                            </div>
                        </div>
                    </div>
                    <div id="dsEditLabelPrompt" style="display:none">
                        <div class="dsPromptOverlay" onclick="cancelEditLabel()"></div>
                        <div class="dsPromptPanel">
                            <h3>Edit Session Name</h3>
                            <input type="text" id="dsEditLabelInput" placeholder="e.g. Classical music" maxlength="100"
                                onkeydown="if(event.key==='Enter')saveEditLabel()">
                            <div class="dsPromptButtons">
                                <button class="dsButton" onclick="cancelEditLabel()">Cancel</button>
                                <button class="dsButton dsButtonAccent" onclick="saveEditLabel()">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="dsLogoDiv"><img src="images/tracks-logo.png" alt=""></div>
        </section>

        <section id="dsPlayer">
            <div id="fileButtonDiv">
                <input id="fileupload" type="file" name="fileupload" multiple />
            </div>
            <div id="dsClientLogo"><img src="images/station-logo.png" alt=""></div>
            <button class="dsHomeIcon" onclick="gotoLoginView()" title="Home">&#8962;</button>
            <div id="dsAudioPlayer">
                <div id="fileList"></div>
                <div id="dsPlayerDContainer">
                    <div id="audioPlayer"></div>
                    <div id="dsPlayerButtons">
                        <button class="dsPlayerBtn" onclick="gotoMySessionsView()">
                            My Sessions
                        </button>
                        <button class="dsPlayerBtn dsPlayerBtnAccent" onclick="dsShowFileUpload()">
                            Add Tracks
                        </button>
                    </div>
                    <div class="dsBottomButtons">
                        <div class="dsEnableAuto">
                            <label class="switch">
                                <input id="dsAutoPlaySwitch" type="checkbox">
                                <span class="slider round"></span>
                            </label>
                            <div class="dsAutoPlayText">Enable auto play.</div>
                        </div>
                        <button id="dsBtn_PlayAll" onclick="dsPlayAll()" class="dsButton">Play All</button>
                        <button id="dsBtn_StopAll" onclick="dsStopPlayAll()" class="dsButton">Stop playing all tracks</button>
                    </div>
                </div>
            </div>
            <div id="dsLogoDiv"><img src="images/tracks-logo.png" alt=""></div>
        </section>

        <section>
            <div id="dsIframeDiv">
                <iframe src="login.php" frameborder="0">

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
    var base_url = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    base_url = window.location.origin + base_url;
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

            Cookies.set('username', usernameDateTime, { expires: 14 });

            if ($.inArray(username, existingUsers) != -1) {
                getSessionsList();

                gotoSessionView();

            } else {
                gotoSessionView();
            }
        } else {
            alert("Please enter your name - must be longer than three characters");
            return
        }
    }


    /*****************************
     * NEW SESSION FUNCTIONS
     ****************************/

    //  AFTER FILES HAVE BEEN SELECTED AND OK'd, UPLOAD THEM AUTOMATICALLY
    document.getElementById("fileupload").onchange = function(e) {
        if (!fileupload.files.length) return;

        //  NAVIGATE TO PLAYER VIEW (from wherever we are)
        document.getElementById("dsLogin").style.display = 'none';
        document.getElementById("dsSession").style.display = 'none';
        document.getElementById("dsPlayer").style.display = 'block';

        //  SHOW UPLOAD PROGRESS — use full width for spinner
        let numFiles = fileupload.files.length;
        $("#dsPlayerButtons").hide();
        $("#dsPlayerDContainer").hide();
        $("#fileList").css({ 'float': 'none', 'width': '100%', 'max-width': '100%', 'border': 'none', 'background': 'transparent' });
        $("#fileList").html(
            "<div class='dsUploadProgress'>" +
            "<div class='dsSpinner'></div>" +
            "<div class='dsUploadStatus'>Uploading " + numFiles + " track" + (numFiles > 1 ? "s" : "") + "...</div>" +
            "<div class='dsUploadDetail'>Track <span id='dsUploadCurrent'>1</span> of " + numFiles + "</div>" +
            "</div>"
        );
        document.getElementById("audioPlayer").innerHTML = "";

        //  UPLOAD: add to existing session or new session
    }

    //  UPLOAD FILES AND BUILD TRACK LIST
    async function uploadFile() {
        //  SET DEFAULT PATH
        path = base_url + "music/" + usernameDateTime + "/";

        let fileNames = fileupload.files;
        let fileList = "";
        let numFiles = fileupload.files.length;

        for (let i = 0; i < numFiles; i++) {
            //  UPDATE PROGRESS
            let counter = document.getElementById('dsUploadCurrent');
            if (counter) counter.textContent = (i + 1);

            let formData = new FormData();
            formData.append("file", fileupload.files[i]);
            await fetch(base_url + 'upload.php', {
                method: "POST",
                body: formData
            });

            escName = fileNames[i].name.replace("'", "%27");
            fileList += "<div id='" + (i + 1) + "' class='dsAudioFileName' data-url='" + path + escName +
                "' data-name='" + escName + "'  onclick='dsPlayAudio(this)' >" +
                fileNames[i].name + "</div>";
        }

        //  CLEAN UP AND RESTORE LAYOUT
        $("#fileList").css({ 'float': '', 'width': '', 'max-width': '', 'border': '', 'background': '' });
        $("#dsPlayerDContainer").show();
        $("#dsPlayerButtons").show();
        document.getElementById("fileupload").value = null;

        //  REFRESH SESSION LIST AND NAVIGATE BACK TO IT
        await getSessionsList();
        gotoSessionView();
    }

    async function dsAddToSession() {
        let fileNames = fileupload.files;
        let numFiles = fileupload.files.length;

        // UPLOAD EACH FILE WITH PROGRESS
        for (let i = 0; i < numFiles; i++) {
            let counter = document.getElementById('dsUploadCurrent');
            if (counter) counter.textContent = (i + 1);

            let formData = new FormData();
            formData.append("file", fileupload.files[i]);
            await fetch(base_url + 'upload.php', {
                method: "POST",
                body: formData
            });
        }

        // AFTER UPLOAD, REBUILD TRACK LIST FROM SERVER DATA
        // Fetch the updated session tracks from json.php
        let sessionName = Cookies.get('username');
        if (sessionName) {
            let response = await fetch(base_url + 'json.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'u_name=' + encodeURIComponent(sessionName.split('-')[0])
            });
            let data = await response.text();
            try {
                let sessions = JSON.parse(data);
                // Find the matching session
                for (let s = 0; s < sessions.length; s++) {
                    if (sessions[s].name === sessionName) {
                        let tracks = sessions[s].music;
                        let fileList = "";
                        path = base_url + "music/" + sessionName + "/";
                        for (let t = 0; t < tracks.length; t++) {
                            let escName = tracks[t].replace("'", "%27");
                            fileList += "<div id='" + (t + 1) + "' class='dsAudioFileName' data-url='" + path + escName +
                                "' data-name='" + escName + "' onclick='dsPlayAudio(this)' >" +
                                tracks[t] + "</div>";
                        }
                        document.getElementById("fileList").innerHTML = fileList;
                        break;
                    }
                }
            } catch(e) {
                // Fallback: just show a message
                document.getElementById("fileList").innerHTML =
                    "<div class='dsUploadMessage'><h2>Tracks uploaded.</h2> Please reload to see the full list.</div>";
            }
        }

        //  RESTORE LAYOUT, SHOW PLAYER CONTROLS & CLEAN UP
        $("#fileList").css({ 'float': '', 'width': '', 'max-width': '', 'border': '', 'background': '' });
        $("#dsPlayerDContainer").show();
        $("#dsPlayerButtons").show();
        document.getElementById("audioPlayer").style.display = '';
        document.getElementById("audioPlayer").innerHTML =
            "<div class='dsNotify'>Choose a track to play.</div>";
        document.getElementById("fileupload").value = null;
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

    function gotoMySessionsView() {
        //  GO BACK TO THE CURRENT USER'S SESSION LIST
        if (username) {
            getSessionsList();
            gotoSessionView();
        } else {
            gotoLoginView();
        }
    }

    function dsShowFileUpload() {
        //  SHOW USB BROWSER TO SELECT TRACKS
        dsUsb.browseAndImport(username, usernameDateTime);
    }

    // DELETE FUNCTIONS
    function deleteTrack(trackName) {
        if (!confirm('Delete "' + trackName + '" from this session?')) return;
        $.post('json.php', {
            delete_action: 'track',
            session: usernameDateTime,
            track: trackName
        }, function(data) {
            if (data.status === 'ok') {
                // Remove track from UI
                $('#fileList').find('#' + CSS.escape(trackName)).remove();
                if (data.remaining === 0) {
                    // Session is empty, go back to session list
                    getSessionsList();
                    gotoSessionView();
                    showScreen('dsSession');
                }
            } else {
                alert('Could not delete track: ' + (data.message || 'Unknown error'));
            }
        }, 'json');
    }

    function deleteSession(sessionFolder) {
        if (!confirm('Delete this session and all its tracks?')) return;
        $.post('json.php', {
            delete_action: 'session',
            session: sessionFolder
        }, function(data) {
            if (data.status === 'ok') {
                getSessionsList();
            } else {
                alert('Could not delete session: ' + (data.message || 'Unknown error'));
            }
        }, 'json');
    }

    function deleteUser(targetUsername) {
        if (!confirm('Delete user "' + targetUsername + '" and ALL their sessions? This cannot be undone.')) return;
        $.post('json.php', {
            delete_action: 'user',
            username: targetUsername
        }, function(data) {
            if (data.status === 'ok') {
                getSessions();
                // If we just deleted ourselves, go back to login
                if (targetUsername === username) {
                    Cookies.remove('username');
                    document.getElementById("dsSession").style.display = 'none';
                    document.getElementById("dsPlayer").style.display = 'none';
                    document.getElementById("dsLogin").style.display = 'block';
                }
            } else {
                alert('Could not delete user: ' + (data.message || 'Unknown error'));
            }
        }, 'json');
    }

    function gotoSessionView() {
        //  SWAP TO SESSION LIST VIEW                 
        document.getElementById('userName').innerHTML = "USER: " + username;

        const dsForm = document.getElementById("dsLogin");
        dsForm.style.display = 'none';

        const dsSessionList = document.getElementById("dsSession");
        dsSessionList.style.display = 'block';
    }

    function showPlayerSection() {
        //  NAVIGATE TO PLAYER VIEW (no file dialog)
        document.getElementById("dsLogin").style.display = 'none';
        document.getElementById("dsSession").style.display = 'none';
        document.getElementById("dsPlayer").style.display = 'block';
    }

    function loadSessionIntoPlayer(sessionFolder) {
        $.ajax({
            url: 'json.php',
            type: 'POST',
            data: { u_name: username },
            success: function(data) {
                var obj = jQuery.parseJSON(data);
                var found = false;
                $.each(obj, function(key, value) {
                    if (value.name === sessionFolder) {
                        found = true;
                        usernameDateTime = sessionFolder;
                        path = base_url + 'music/' + sessionFolder + '/';
                        Cookies.set('username', sessionFolder, { expires: 14 });

                        var fileList = '';
                        $.each(value.music, function(i, track) {
                            var escName = track.replace("'", '%27');
                            fileList += "<div id='" + track + "' class='dsAudioFileName' data-url='" + path + escName + "' data-name='" + escName + "' onclick='dsPlayAudio(this)'>" + track + "<span class='dsDeleteTrack' data-track='" + escName + "' title='Delete track' onclick='event.stopPropagation(); deleteTrack(\"" + escName + "\")'>&" + "#128465;</span></div>";
                        });

                        showPlayerSection();
                        $('#fileList').html(fileList);
                        document.getElementById('audioPlayer').innerHTML = "<div class='dsNotify'>Choose a track to play.</div>";
                        return false;
                    }
                });
                if (!found) {
                    gotoSessionView();
                }
            }
        });
    }

    function showNewSessionPrompt() {
        //  SHOW THE SESSION NAME PROMPT
        document.getElementById("dsSessionLabelInput").value = "";
        document.getElementById("dsNewSessionPrompt").style.display = "block";
        document.getElementById("dsSessionLabelInput").focus();
    }

    function cancelNewSession() {
        document.getElementById("dsNewSessionPrompt").style.display = "none";
    }

    function confirmNewSession() {
        //  GENERATE A FRESH SESSION NAME WITH NEW TIMESTAMP
        usernameDateTime = username + "-" + dsDateStamp();
        Cookies.set('username', usernameDateTime, { expires: 14 });

        //  SAVE LABEL IF PROVIDED
        let label = document.getElementById("dsSessionLabelInput").value.trim();
        if (label) {
            fetch(base_url + 'json.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'save_label=' + encodeURIComponent(label) + '&session_id=' + encodeURIComponent(usernameDateTime)
            });
        }

        //  HIDE PROMPT AND SHOW USB BROWSER
        document.getElementById("dsNewSessionPrompt").style.display = "none";
        dsUsb.browseAndImport(username, usernameDateTime);
    }

    function gotoPlayerView() {
        //  FOR BRAND NEW USERS (from dsSaveName) — set cookie + open file dialog
        Cookies.set('username', usernameDateTime, { expires: 14 });
    }

    function getSessions() {
        existingUsers = [];
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
                            "<span class='dsDeleteUser' data-user='" + val + "' title='Delete user and all sessions' onclick='event.stopPropagation(); deleteUser(\"" + val + "\")'>&#128465;</span>" +
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

                    //  SESSION LABEL (user-defined name)
                    let labelText = value.label || '';
                    //  CREATE HTML TRACK DISPLAY ITEM
                    let shortDate = day + '/' + date.substr(2, 2) + '/' + date.substr(0, 2);
                    let shortTime = time.slice(0, 2) + ':' + time.slice(2, 4);
                    let dateStr = shortDate + '-' + shortTime;
                    let sessionTitle = labelText
                        ? '<span class="dsSessionLabelPrimary">' + labelText + '</span>' +
                          '<span class="dsSessionDate">' + dateStr + '</span>'
                        : '<span class="dsSessionDate dsSessionDateOnly">' + dateStr + '</span>';

                    let mySession =
                        '<div id="' + directoryName +
                        '" class="dsSessionName" title="Click item to load this session." data-tracks=' +
                        tracks + '>' + sessionTitle +
                        '<span class="dsEditLabel" data-session="' + directoryName +
                        '" title="Edit session name">&#9998;</span>' +
                        '</div>' +
                        '<button title="Delete this session and all tracks." data-folder="' +
                        value.name +
                        '" class="dsDeleteSession">&#128465;</button>' +
                        '<button title="Add new tracks to this session." data-folder=' +
                        value.name +
                        ' class="dsAddTracks">Add Tracks</button>' +
                        '<button title="Click item to preview tracks in this session." data-tracks=' +
                        tracks +
                        ' class="dsShowTracks">Show Tracks</button>';

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

    $(document).on('click', '#dsCurrentUsers', function() {
        if ($("#dsCurrentUsers").hasClass('dsActive')) {
            $("#dsLightbox").fadeOut(200);
            $("#dsUserList").fadeOut(200);
            $("#dsCurrentUsers").html("Show current users")
            $("#dsCurrentUsers").removeClass("dsActive");

        } else {
            getSessions();
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
        path = base_url + "music/" + usernameDateTime + "/";

        $.each(dsTracks, function(key, value) {
            escName = value.replace("'", "%27");
            fileHTML = "<div id='" + value + "' class='dsAudioFileName' data-url='" + path +
                escName + "' data-name='" + escName +
                "'  onclick='dsPlayAudio(this)' >" +
                value +
                "<span class='dsDeleteTrack' data-track='" + escName + "' title='Delete track' onclick='event.stopPropagation(); deleteTrack(\"" + escName + "\")'>&#128465;</span>" +
                "</div>"

            fileList = (fileList) ? fileList + fileHTML : fileHTML;
        })

        //  SET COOKIE AND NAVIGATE TO PLAYER (no file dialog)
        Cookies.set('username', usernameDateTime, { expires: 14 });
        showPlayerSection();

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        $("#fileList").html("");
        $("#fileList").append(fileList);

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
        path = base_url + "music/" + usernameDateTime + "/";

        $.each(dsTracks, function(key, value) {
            fileHTML = "<div id='" + value + "' class='dsAudioFileName' data-url='" + path +
                value + "' data-name='" + value + "'  onclick='dsPlayAudio(this)' >" +
                value + "</div>"

            fileList = (fileList) ? fileList + fileHTML : fileHTML;
        })

        //  SET COOKIE AND NAVIGATE TO PLAYER (no file dialog)
        Cookies.set('username', usernameDateTime, { expires: 14 });
        showPlayerSection();

        //  ADD THE ELEMENTS TO THE FILELIST DIV
        $("#fileList").html("");
        $("#fileList").append(fileList);

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

        //  SHOW USB BROWSER TO SELECT TRACKS FOR THIS SESSION
        dsUsb.browseAndImport(username, dsFolderID);
    });

    $(document).on('click', '.dsDeleteSession', function(e) {
        e.stopPropagation();
        let folder = $(this).attr("data-folder");
        deleteSession(folder);
    });

    //  EDIT SESSION LABEL — modal prompt
    let editLabelSessionId = '';

    $(document).on('click', '.dsEditLabel', function(e) {
        e.stopPropagation();
        editLabelSessionId = $(this).attr("data-session");
        let currentText = $("#" + editLabelSessionId).find(".dsSessionLabel").text() || '';
        $("#dsEditLabelInput").val(currentText);
        $("#dsEditLabelPrompt").show();
        $("#dsEditLabelInput").focus().select();
    });

    function cancelEditLabel() {
        $("#dsEditLabelPrompt").hide();
        editLabelSessionId = '';
    }

    function saveEditLabel() {
        let newLabel = $("#dsEditLabelInput").val().trim();
        let sessionRow = $("#" + editLabelSessionId);
        let labelSpan = sessionRow.find(".dsSessionLabel");

        //  UPDATE THE DISPLAY
        if (labelSpan.length) {
            labelSpan.text(newLabel);
        } else if (newLabel) {
            sessionRow.find(".dsEditLabel").before(' <span class="dsSessionLabel">' + $('<span>').text(newLabel).html() + '</span>');
        }

        //  SAVE TO SERVER
        fetch(base_url + 'json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'save_label=' + encodeURIComponent(newLabel || ' ') + '&session_id=' + encodeURIComponent(editLabelSessionId)
        });

        $("#dsEditLabelPrompt").hide();
        editLabelSessionId = '';
    }


    //  ONCE FILES ARE UPLOADED, CREATE INTERFACE ELEMENTS WITH NAMES AND URLS IN DATA- ATTRIBUTES
    async function dsAddFile() {
        //  SET DEFAULT PATH
        // path = base_url + "music/" + usernameDateTime + "/";

        //  GET THE FILE DATA ON THE UPLOADED MUSIC TRACKS
        let fileNames = fileupload.files;
        let fileList = [];
        let numFiles = fileupload.files.length;

        //  CLEAR VARIABLE
        fileHTML = "";


        // INTERATE THROUGH THE TRACK LIST AND CREATE THE ELEMENTS
        for (let i = 0; i < numFiles; i++) {
            let escName;

            let formData = new FormData();
            formData.append("file", fileupload.files[i]);
            await fetch(base_url + 'upload.php', {
                method: "POST",
                body: formData
            });

            //  ENCODE THE ' CHARACTER
            escName = fileNames[i].name.replace("'", "%27");

            fileHTML = "<div id='" + (i + 1) + "' class='dsAudioFileName' data-url='" + path + escName +
                "' data-name='" + escName + "'  onclick='dsPlayAudio(this)' >" +
                fileNames[i].name + "</div>"

            fileList = fileList + fileHTML;
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
                // console.log(data);
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

    /******************************
     *    AUTO-PLAY FUNCTIONS
     *****************************/

    let allTracks;
    let numTracks;
    let dsCurrentTrack;
    let dsTrackIndex;
    let autoPlayStatus;

    //  TOGGLE: when checked, enable sortable + show Play All button
    $(document).on('change', '#dsAutoPlaySwitch', function() {
        if (this.checked) {
            dsEnableSortable();
        } else {
            dsStopPlayAll();
        }
    });

    //  ENABLE SORTABLE — allows drag-and-drop reordering of tracks
    function dsEnableSortable() {
        $(".dsAutoPlayText").hide();
        $(".dsAutoPlayText").html("Auto play enabled. You can re-order the tracks & play them all now.").fadeIn(800);
        $("button#dsBtn_PlayAll").fadeIn(800);
        $(".dsFileDiv").addClass("dsNowSorting");
        $("#fileList").addClass("dsNowSorting");
        //  ENABLE SORTABLE
        $('#fileList').sortable({
            items: ".dsAudioFileName"
        });
        $('#fileList').sortable("enable");
    }

    //  PLAY ALL TRACKS — start from track 1, play through entire list
    function dsPlayAll() {
        allTracks = $('.dsAudioFileName');
        numTracks = $('.dsAudioFileName').length;
        //  PLAY FIRST TRACK
        dsCurrentTrack = $(allTracks[0]);
        dsTrackIndex = 0;
        autoPlayStatus = "autoPlay";
        dsPlayList(dsCurrentTrack);
        $("#dsBtn_PlayAll").fadeOut(200);
        $("#dsBtn_StopAll").fadeIn(800);
    }

    //  STOP ALL — disable sortable and reset auto-play state
    function dsStopPlayAll() {
        $(".dsAutoPlayText").html("Enable auto play.");
        $("button#dsBtn_PlayAll").fadeOut(200);
        try { $('#fileList').sortable("disable"); } catch(e) {}
        $(".dsFileDiv").removeClass("dsNowSorting");
        $("#fileList").removeClass("dsNowSorting");
        $("#dsBtn_StopAll").hide();
        $("#dsAutoPlaySwitch").prop('checked', false);
        if (autoPlayStatus === "autoPlay") {
            $("#audioPlayer").html("<div class='dsNotify'>Choose another track to play.</div>");
            dsClearActive();
        }
        autoPlayStatus = "";
    }

    //  SEQUENTIAL PLAY — plays tracks one after another via onended event
    function dsPlayList(dsCurrentTrack) {
        //  CHECK IF INITIAL TRACK OR CALLED FROM 'END OF TRACK' EVENT
        if (dsCurrentTrack == null) {
            allTracks = $('.dsAudioFileName');
            numTracks = $('.dsAudioFileName').length;

            //  CHECK IF WE'VE PLAYED ALL TRACKS
            if (dsTrackIndex >= numTracks) {
                dsStopPlayAll();
                return;
            }
            dsCurrentTrack = $(allTracks[dsTrackIndex]);
        }

        // EXTRACT DATA ATTRIBUTES
        let audioID = dsCurrentTrack.attr("id");
        let audioURL = dsCurrentTrack.attr('data-url');
        let trackName = dsCurrentTrack.attr("data-name");

        dsTrackIndex++;

        // SET THE CLICKED ELEMENT TO ACTIVE
        dsClearActive();
        $(dsCurrentTrack).addClass("dsActive");
        currentTrackID = audioID;

        //  BUILD THE HTML5 AUDIO TAGS — onended chains to next track
        let audioHtml =
            "<audio id='dsAudio' controls controlsList='nodownload noplaybackrate' onended='dsPlayList()' autoplay> <source src='" +
            audioURL +
            "' type='audio/mpeg'></audio><div class='dsTrackTitle'><span class='dsTitle'>Title: </span>" +
            trackName.replace("%27", "'").replace("%2C", ",") +
            "</div>";

        // SET THE TRACK TO PLAY IN THE AUDIO PLAYER DIV
        document.getElementById("audioPlayer").innerHTML = audioHtml;
    }
    </script>

    <!-- USB Browser & Touch Player -->
    <script src="js/usb-browser.js?v=9"></script>

    <!-- On-Screen Keyboard -->
    <script src="js/on-screen-keyboard.js?v=4"></script>

</body>

</html>