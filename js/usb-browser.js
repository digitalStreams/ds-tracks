/**
 * DS-Tracks - USB File Browser & Touch Player
 *
 * Handles USB detection, file browsing, import, and playback
 * for the touch-optimised Raspberry Pi interface.
 */

(function() {
    'use strict';

    // ── Configuration ────────────────────────────────────────

    // Auto-detect base URL from the current page location
    var pathDir = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    var BASE_URL = window.location.origin + pathDir;
    var USB_POLL_INTERVAL = 2000;   // Check USB status every 2 seconds
    var pollTimer = null;
    var usbMounted = false;

    // ── State ────────────────────────────────────────────────

    var currentPath = '/';
    var selectedFiles = [];         // Array of file paths selected for import
    var existingUsers = [];
    var currentUsername = '';
    var currentSession = '';
    var tracks = [];                // Tracks in current player session
    var currentTrackIndex = -1;
    var audioElement = null;
    var autoPlay = false;
    var legacyMode = false;        // When true, skip user ID screen and return to legacy session view
    var legacySessionFolder = null; // When set, import into this existing session folder
    var initialCheckDone = false;   // Suppress auto-browse on first poll if USB already present
    var passwordResetTriggered = false;  // Prevent re-triggering password reset

    // ── Initialisation ───────────────────────────────────────

    function init() {
        loadExistingUsers();
        setupAudio();
        showScreen('idleScreen');  // This will start polling automatically
    }

    // ── USB Polling ──────────────────────────────────────────

    function startUsbPolling() {
        pollTimer = setInterval(checkUsbStatus, USB_POLL_INTERVAL);
        checkUsbStatus(); // Check immediately
    }

    function checkUsbStatus() {
        fetch(BASE_URL + '/usb-status.php', { cache: 'no-store' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.mounted && !usbMounted) {
                    usbMounted = true;
                    if (!initialCheckDone) {
                        // USB was already present on page load - don't auto-browse
                        initialCheckDone = true;
                    } else {
                        // USB just inserted - show browser
                        onUsbInserted(data);
                    }
                } else if (!data.mounted && !initialCheckDone) {
                    initialCheckDone = true;
                } else if (!data.mounted && usbMounted) {
                    // USB just removed
                    usbMounted = false;
                    passwordResetTriggered = false;
                    onUsbRemoved();
                }

                // Check for password reset file on USB
                if (data.password_reset_available && !passwordResetTriggered) {
                    passwordResetTriggered = true;
                    triggerPasswordReset();
                }
            })
            .catch(function() {
                // Server not available or error - treat as no USB
                if (usbMounted) {
                    usbMounted = false;
                    onUsbRemoved();
                }
            });
    }

    function onUsbInserted(data) {
        var label = data.label || 'USB Drive';
        var count = data.audio_count || 0;

        // If we're on the idle screen, auto-navigate to browser
        var idleScreen = document.getElementById('idleScreen');
        if (idleScreen && idleScreen.style.display !== 'none') {
            currentPath = '/';
            selectedFiles = [];
            showScreen('usbBrowser');
            browsePath('/');
        }
    }

    function onUsbRemoved() {
        // If we're in the browser or user-id screen, go back to idle
        var usbBrowser = document.getElementById('usbBrowser');
        var userIdScreen = document.getElementById('userIdScreen');

        if ((usbBrowser && usbBrowser.style.display !== 'none') ||
            (userIdScreen && userIdScreen.style.display !== 'none')) {
            selectedFiles = [];
            showScreen('idleScreen');
        }
    }

    // ── Screen Management ────────────────────────────────────

    function showScreen(screenId) {
        var screens = ['idleScreen', 'usbBrowser', 'userIdScreen', 'touchPlayer',
                       'dsLogin', 'dsSession', 'dsPlayer'];

        var legacyScreens = ['dsLogin', 'dsSession', 'dsPlayer'];

        for (var i = 0; i < screens.length; i++) {
            var el = document.getElementById(screens[i]);
            if (el) {
                if (screens[i] === screenId) {
                    el.style.display = (legacyScreens.indexOf(screens[i]) !== -1) ? 'block' : 'flex';
                } else {
                    el.style.display = 'none';
                }
            }
        }

        // Only poll for USB when idle screen or USB browser is visible
        if (screenId === 'idleScreen' || screenId === 'usbBrowser') {
            if (!pollTimer) startUsbPolling();
        } else {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        }
    }

    // ── File Browser ─────────────────────────────────────────

    function browsePath(path) {
        currentPath = path;

        var list = document.getElementById('browserList');
        if (list) {
            list.innerHTML = '<div class="browser-loading">Loading...</div>';
        }

        var formData = new FormData();
        formData.append('path', path);

        fetch(BASE_URL + '/usb-browse.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderBrowser(data);
        })
        .catch(function(err) {
            if (list) {
                list.innerHTML = '<div class="no-files-message">Could not read USB drive.</div>';
            }
        });
    }

    function renderBrowser(data) {
        var list = document.getElementById('browserList');
        var headerTitle = document.getElementById('browserHeaderTitle');

        if (!list) return;

        // Update header
        if (headerTitle) {
            if (data.current_path === '/') {
                headerTitle.innerHTML = 'USB Drive';
            } else {
                var folderName = data.current_path.split('/').pop();
                headerTitle.innerHTML = '<span class="back-arrow" onclick="window.dsUsb.goBack()">&#8592;</span> ' +
                    escapeHtml(folderName);
            }
        }

        // Build list HTML
        var html = '';
        var hasContent = false;

        // Folders
        for (var i = 0; i < data.folders.length; i++) {
            var folder = data.folders[i];
            hasContent = true;
            html += '<div class="browser-row folder" onclick="window.dsUsb.browsePath(\'' +
                escapeAttr(folder.path) + '\')">' +
                '<span class="row-icon">&#128193;</span>' +
                '<span class="row-name">' + escapeHtml(folder.name) + '</span>' +
                '<span class="row-info">' + folder.audio_count + ' tracks</span>' +
                '<span class="row-arrow">&#8250;</span>' +
                '</div>';
        }

        // Files
        for (var j = 0; j < data.files.length; j++) {
            var file = data.files[j];
            hasContent = true;
            var isSelected = selectedFiles.indexOf(file.path) !== -1;
            html += '<div class="browser-row file' + (isSelected ? ' selected' : '') +
                '" onclick="window.dsUsb.toggleFile(\'' + escapeAttr(file.path) + '\', this)">' +
                '<span class="row-icon">&#9835;</span>' +
                '<span class="row-name">' + escapeHtml(file.name) + '</span>' +
                '<span class="row-info">' + file.size_human + '</span>' +
                '<span class="row-checkbox"></span>' +
                '</div>';
        }

        if (!hasContent) {
            html = '<div class="no-files-message">' +
                'No audio files found in this folder.' +
                '<div class="formats">Supported: MP3, WAV, OGG, FLAC, M4A</div>' +
                '</div>';
        }

        list.innerHTML = html;

        updateSelectedCount();
        updateSelectAllButton(data.files);
    }

    function toggleFile(path, rowElement) {
        var idx = selectedFiles.indexOf(path);
        if (idx === -1) {
            selectedFiles.push(path);
            if (rowElement) rowElement.classList.add('selected');
        } else {
            selectedFiles.splice(idx, 1);
            if (rowElement) rowElement.classList.remove('selected');
        }
        updateSelectedCount();
    }

    function selectAll() {
        var rows = document.querySelectorAll('#browserList .browser-row.file');
        var allSelected = true;

        // Check if all are already selected
        for (var i = 0; i < rows.length; i++) {
            if (!rows[i].classList.contains('selected')) {
                allSelected = true;
                break;
            }
            if (i === rows.length - 1) allSelected = true;
        }

        // Get file paths from onclick attributes
        var btn = document.getElementById('btnSelectAll');

        if (btn && btn.classList.contains('all-selected')) {
            // Deselect all in current view
            for (var j = 0; j < rows.length; j++) {
                var path = getPathFromRow(rows[j]);
                if (path) {
                    var idx = selectedFiles.indexOf(path);
                    if (idx !== -1) selectedFiles.splice(idx, 1);
                }
                rows[j].classList.remove('selected');
            }
            btn.classList.remove('all-selected');
            btn.textContent = 'Select All';
        } else {
            // Select all in current view
            for (var k = 0; k < rows.length; k++) {
                var filePath = getPathFromRow(rows[k]);
                if (filePath && selectedFiles.indexOf(filePath) === -1) {
                    selectedFiles.push(filePath);
                }
                rows[k].classList.add('selected');
            }
            if (btn) {
                btn.classList.add('all-selected');
                btn.textContent = 'Deselect All';
            }
        }

        updateSelectedCount();
    }

    function getPathFromRow(row) {
        var onclick = row.getAttribute('onclick');
        if (!onclick) return null;
        var match = onclick.match(/toggleFile\('([^']+)'/);
        return match ? match[1] : null;
    }

    function updateSelectedCount() {
        var countEl = document.getElementById('selectedCount');
        if (countEl) {
            countEl.textContent = selectedFiles.length + ' selected';
        }

        var importBtn = document.getElementById('btnImport');
        if (importBtn) {
            importBtn.disabled = selectedFiles.length === 0;
        }
    }

    function updateSelectAllButton(files) {
        var btn = document.getElementById('btnSelectAll');
        if (!btn) return;

        if (!files || files.length === 0) {
            btn.style.display = 'none';
            return;
        }

        btn.style.display = '';

        // Check if all files in view are selected
        var allSelected = true;
        for (var i = 0; i < files.length; i++) {
            if (selectedFiles.indexOf(files[i].path) === -1) {
                allSelected = false;
                break;
            }
        }

        if (allSelected && files.length > 0) {
            btn.classList.add('all-selected');
            btn.textContent = 'Deselect All';
        } else {
            btn.classList.remove('all-selected');
            btn.textContent = 'Select All';
        }
    }

    function goBack() {
        if (currentPath === '/') {
            // At root - go back to idle screen
            selectedFiles = [];
            showScreen('idleScreen');
        } else {
            // Navigate to parent folder
            var parts = currentPath.split('/').filter(function(p) { return p; });
            parts.pop();
            var parentPath = '/' + parts.join('/');
            browsePath(parentPath);
        }
    }

    // ── User Identification ──────────────────────────────────

    function showUserIdScreen() {
        if (selectedFiles.length === 0) return;

        // In legacy mode, skip user ID � we already have the username
        if (legacyMode && currentUsername) {
            importFiles(currentUsername);
            return;
        }

        showScreen('userIdScreen');
        renderExistingUsers();

        var nameInput = document.getElementById('nameInput');
        if (nameInput) {
            nameInput.value = '';
            nameInput.focus();
        }
    }

    function loadExistingUsers() {
        var formData = new FormData();
        formData.append('option', 'users');

        fetch(BASE_URL + '/json.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.text(); })
        .then(function(text) {
            try {
                var data = JSON.parse(text);
                var names = {};
                for (var i = 0; i < data.length; i++) {
                    var name = data[i].name;
                    var username = name.substr(0, name.indexOf('-'));
                    if (username) names[username] = true;
                }
                existingUsers = Object.keys(names);
            } catch(e) {
                existingUsers = [];
            }
        })
        .catch(function() {
            existingUsers = [];
        });
    }

    function renderExistingUsers() {
        var container = document.getElementById('existingUsersList');
        if (!container) return;

        if (existingUsers.length === 0) {
            container.style.display = 'none';
            var label = document.querySelector('.existing-users-label');
            if (label) label.style.display = 'none';
            var divider = document.querySelector('.user-divider');
            if (divider) divider.style.display = 'none';
            return;
        }

        var html = '';
        for (var i = 0; i < existingUsers.length; i++) {
            html += '<div class="existing-user-item" onclick="window.dsUsb.selectExistingUser(\'' +
                escapeAttr(existingUsers[i]) + '\')">' +
                escapeHtml(existingUsers[i]) + '</div>';
        }
        container.innerHTML = html;
    }

    function confirmUsername() {
        var nameInput = document.getElementById('nameInput');
        if (!nameInput) return;

        var name = nameInput.value.replace(/[^a-zA-Z0-9_-]/g, '').trim();

        if (name.length < 3) {
            nameInput.style.borderColor = '#ff4444';
            nameInput.setAttribute('placeholder', 'Name must be 3+ characters');
            return;
        }

        currentUsername = name;
        importFiles(name);
    }

    function selectExistingUser(name) {
        currentUsername = name;
        importFiles(name);
    }

    // ── File Import ──────────────────────────────────────────

    function importFiles(username) {
        showScreen('usbBrowser');

        // Show import progress
        var browser = document.getElementById('usbBrowser');
        if (browser) {
            browser.innerHTML =
                '<div class="import-progress" style="display:flex">' +
                '<div class="progress-message">Importing ' + selectedFiles.length + ' tracks...</div>' +
                '<div class="progress-detail">Please wait</div>' +
                '</div>';
        }

        fetch(BASE_URL + '/usb-import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                files: selectedFiles,
                session: legacySessionFolder || undefined
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                currentSession = data.session;
                tracks = data.tracks;
                selectedFiles = [];

                // Set cookie for compatibility
                document.cookie = 'username=' + data.session + ';path=/;max-age=' + (14*24*60*60);

                // Reload existing users for next time
                loadExistingUsers();

                // Restore browser HTML for later use
                restoreBrowserHTML();

                // Show player or return to legacy session view
                if (legacyMode) {
                    var sessionToShow = data.session;
                    legacyMode = false;
                    legacySessionFolder = null;
                    showScreen('dsPlayer');
                    if (typeof loadSessionIntoPlayer === 'function') {
                        loadSessionIntoPlayer(sessionToShow);
                    } else {
                        if (typeof getSessionsList === 'function') getSessionsList();
                        showScreen('dsSession');
                    }
                } else {
                    renderPlayer();
                    showScreen('touchPlayer');
                }
            } else {
                restoreBrowserHTML();
                alert('Import failed: ' + (data.error || 'Unknown error'));
                showScreen('usbBrowser');
                browsePath('/');
            }
        })
        .catch(function(err) {
            restoreBrowserHTML();
            alert('Import failed. Please try again.');
            showScreen('usbBrowser');
            browsePath('/');
        });
    }

    function restoreBrowserHTML() {
        var browser = document.getElementById('usbBrowser');
        if (!browser || browser.querySelector('.browser-header')) return;

        browser.innerHTML =
            '<div class="browser-header">' +
            '  <div class="header-title" id="browserHeaderTitle">USB Drive</div>' +
            '  <button class="btn-select-all" id="btnSelectAll" onclick="window.dsUsb.selectAll()">Select All</button>' +
            '</div>' +
            '<div class="browser-list" id="browserList"></div>' +
            '<div class="browser-footer">' +
            '  <span class="selected-count" id="selectedCount">0 selected</span>' +
            '  <div class="footer-buttons">' +
            '    <button class="btn-browser btn-back" onclick="window.dsUsb.goBack()">&#8592; Back</button>' +
            '    <button class="btn-browser btn-import" id="btnImport" disabled onclick="window.dsUsb.showUserIdScreen()">Use These Tracks &#8594;</button>' +
            '  </div>' +
            '</div>';
    }

    // ── Audio Player ─────────────────────────────────────────

    function setupAudio() {
        audioElement = new Audio();

        audioElement.addEventListener('timeupdate', function() {
            updateProgress();
        });

        audioElement.addEventListener('ended', function() {
            onTrackEnded();
        });

        audioElement.addEventListener('loadedmetadata', function() {
            updateDuration();
        });
    }

    function renderPlayer() {
        var trackListEl = document.getElementById('playerTrackList');
        var playerUser = document.getElementById('playerUser');

        if (playerUser) {
            playerUser.textContent = currentUsername;
        }

        if (trackListEl) {
            var html = '';
            for (var i = 0; i < tracks.length; i++) {
                html += '<div class="player-track" id="track-' + i +
                    '" onclick="window.dsUsb.playTrack(' + i + ')">' +
                    '<span class="track-indicator">' + (i + 1) + '</span>' +
                    '<span class="track-name">' + escapeHtml(tracks[i].name) + '</span>' +
                    '</div>';
            }
            trackListEl.innerHTML = html;
        }

        // Reset player state
        currentTrackIndex = -1;
        updatePlayerControls();
    }

    function playTrack(index) {
        if (index < 0 || index >= tracks.length) return;

        // If clicking the same track that's playing, toggle pause
        if (index === currentTrackIndex && audioElement && !audioElement.paused) {
            togglePause();
            return;
        }

        currentTrackIndex = index;
        var track = tracks[index];

        audioElement.src = BASE_URL + '/music/' + currentSession + '/' + encodeURIComponent(track.name);
        audioElement.play().catch(function() {
            // Autoplay may be blocked - user needs to tap play
        });

        // Update track list highlighting
        var allTracks = document.querySelectorAll('.player-track');
        for (var i = 0; i < allTracks.length; i++) {
            allTracks[i].classList.remove('playing');
            allTracks[i].querySelector('.track-indicator').textContent = (i + 1);
        }

        var activeTrack = document.getElementById('track-' + index);
        if (activeTrack) {
            activeTrack.classList.add('playing');
            activeTrack.querySelector('.track-indicator').innerHTML = '&#9654;';
            // Scroll into view
            activeTrack.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        updatePlayerControls();
    }

    function togglePause() {
        if (!audioElement) return;

        if (audioElement.paused) {
            audioElement.play();
        } else {
            audioElement.pause();
        }

        updatePlayButton();
    }

    function nextTrack() {
        if (currentTrackIndex < tracks.length - 1) {
            playTrack(currentTrackIndex + 1);
        }
    }

    function onTrackEnded() {
        if (autoPlay && currentTrackIndex < tracks.length - 1) {
            playTrack(currentTrackIndex + 1);
        } else {
            // Clear playing state
            var allTracks = document.querySelectorAll('.player-track');
            for (var i = 0; i < allTracks.length; i++) {
                allTracks[i].classList.remove('playing');
                allTracks[i].querySelector('.track-indicator').textContent = (i + 1);
            }
            currentTrackIndex = -1;
            updatePlayerControls();
        }
    }

    function updatePlayerControls() {
        var titleEl = document.getElementById('playerTrackTitle');
        var controlsArea = document.querySelector('.player-controls');

        if (currentTrackIndex === -1) {
            // No track playing
            if (titleEl) titleEl.textContent = '';
            var waitingEl = document.querySelector('.player-waiting');
            if (waitingEl) waitingEl.style.display = '';

            var progressContainer = document.querySelector('.player-progress-container');
            if (progressContainer) progressContainer.style.visibility = 'hidden';

            var timeEl = document.querySelector('.player-time');
            if (timeEl) timeEl.style.visibility = 'hidden';

            var buttonsEl = document.querySelector('.player-buttons');
            if (buttonsEl) buttonsEl.style.visibility = 'hidden';

            var nowPlaying = document.querySelector('.player-now-playing');
            if (nowPlaying) nowPlaying.style.visibility = 'hidden';
        } else {
            // Track playing
            if (titleEl) titleEl.textContent = tracks[currentTrackIndex].name;

            var waitingEl2 = document.querySelector('.player-waiting');
            if (waitingEl2) waitingEl2.style.display = 'none';

            var progressContainer2 = document.querySelector('.player-progress-container');
            if (progressContainer2) progressContainer2.style.visibility = '';

            var timeEl2 = document.querySelector('.player-time');
            if (timeEl2) timeEl2.style.visibility = '';

            var buttonsEl2 = document.querySelector('.player-buttons');
            if (buttonsEl2) buttonsEl2.style.visibility = '';

            var nowPlaying2 = document.querySelector('.player-now-playing');
            if (nowPlaying2) nowPlaying2.style.visibility = '';
        }

        updatePlayButton();
    }

    function updatePlayButton() {
        var btn = document.getElementById('btnPlay');
        if (!btn) return;

        if (audioElement && !audioElement.paused) {
            btn.innerHTML = '&#9646;&#9646; PAUSE';
        } else {
            btn.innerHTML = '&#9654; PLAY';
        }
    }

    function updateProgress() {
        if (!audioElement || !audioElement.duration) return;

        var percent = (audioElement.currentTime / audioElement.duration) * 100;
        var fill = document.querySelector('.player-progress-fill');
        if (fill) {
            fill.style.width = percent + '%';
        }

        var currentEl = document.getElementById('timeCurrent');
        if (currentEl) {
            currentEl.textContent = formatTime(audioElement.currentTime);
        }
    }

    function updateDuration() {
        var durationEl = document.getElementById('timeDuration');
        if (durationEl && audioElement) {
            durationEl.textContent = formatTime(audioElement.duration);
        }
    }

    function seekTo(event) {
        if (!audioElement || !audioElement.duration) return;

        var bar = document.querySelector('.player-progress-bar');
        if (!bar) return;

        var rect = bar.getBoundingClientRect();
        var x = (event.clientX || event.touches[0].clientX) - rect.left;
        var percent = Math.max(0, Math.min(1, x / rect.width));

        audioElement.currentTime = percent * audioElement.duration;
    }

    function toggleAutoPlay() {
        autoPlay = !autoPlay;

        var toggle = document.getElementById('autoPlayToggle');
        if (toggle) {
            if (autoPlay) {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
        }
    }

    // ── Navigation ───────────────────────────────────────────

    function goHome() {
        if (audioElement) {
            audioElement.pause();
            audioElement.src = '';
        }
        currentTrackIndex = -1;
        selectedFiles = [];
        showScreen('idleScreen');  // showScreen auto-restarts polling for idle screen
    }

    function goToSessions() {
        // Switch to the original session management flow
        if (audioElement) {
            audioElement.pause();
            audioElement.src = '';
        }
        currentTrackIndex = -1;

        showScreen('dsLogin');
    }

    function addMoreTracks() {
        // Go back to USB browser to add more files
        if (usbMounted) {
            showScreen('usbBrowser');
            restoreBrowserHTML();
            browsePath('/');
        }
    }

    function goToLegacyLogin() {
        // Switch to the original login flow (keyboard/mouse)
        showScreen('dsLogin');  // showScreen auto-stops polling for non-USB screens
    }

    function browseAndImport(username, sessionFolder) {
        if (!usbMounted) {
            alert('Please insert a USB drive to add tracks.');
            return;
        }
        legacyMode = true;
        legacySessionFolder = sessionFolder || null;
        currentUsername = username;
        selectedFiles = [];
        showScreen('usbBrowser');
        restoreBrowserHTML();
        browsePath('/');
    }

    // ── Helpers ───────────────────────────────────────────────

    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    // ── Public API (exposed to onclick handlers) ─────────────

    window.dsUsb = {
        init: init,
        browsePath: browsePath,
        toggleFile: toggleFile,
        selectAll: selectAll,
        goBack: goBack,
        showUserIdScreen: showUserIdScreen,
        confirmUsername: confirmUsername,
        selectExistingUser: selectExistingUser,
        playTrack: playTrack,
        togglePause: togglePause,
        nextTrack: nextTrack,
        seekTo: seekTo,
        toggleAutoPlay: toggleAutoPlay,
        goHome: goHome,
        goToSessions: goToSessions,
        addMoreTracks: addMoreTracks,
        goToLegacyLogin: goToLegacyLogin,
        browseAndImport: browseAndImport,
        isUsbMounted: function() { return usbMounted; }
    };

        // Password Reset via USB

    function triggerPasswordReset() {
        fetch(BASE_URL + '/password-reset.php', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.message + '. You can now log into the admin panel.');
                } else {
                    alert('Password reset failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() {
                alert('Password reset failed: could not reach server.');
            });
    }

    // ── Auto-Initialise ──────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
