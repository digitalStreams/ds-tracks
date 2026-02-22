<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="css/exporter-styles.css">

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdn.datatables.net/1.12.0/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script type="text/javascript" language="javascript"
    src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>


<title>Export list of all tracks</title>

<body>
    <div id="content">

        <div id="dsMenuButton">
            <a href="login.php">
                <button id="dsMenu">
                    Home
                </button>
            </a>
        </div>
        <H1 style="text-align: center">Export list of all tracks </H1>

        <?php
?>
        <table id='example' class='display nowrap' style='width:600px'>
            <thead>
                <tr>
                    <th class="dsTrackName">Track Name</th>
                    <th class="dsPathName">Path</th>
                    <th class="dsDateTimeName">Date & Time</th>
                </tr>
            </thead>
            <tbody>


                <!-- SCAN DIRECTORIES AND GET FILE NAMES AND PATHS   -->
                <?php
                    // Configuration
                    define('MUSIC_BASE_DIR', __DIR__ . '/music/');

                    // Validate path function
                    function isValidMusicPath($path) {
                        $realPath = realpath($path);
                        $baseDir = realpath(MUSIC_BASE_DIR);

                        if ($realPath === false || $baseDir === false) {
                            return false;
                        }

                        return strpos($realPath, $baseDir) === 0;
                    }

                    $dirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');

                    foreach ($dirs as $key => $value) {
                        // Validate path
                        if (!isValidMusicPath($value)) {
                            continue;
                        }

                        $dirName = basename($value);
                        $files = scandir($value);
                        array_splice($files, 0, 2); // Remove . and ..

                        // Build table for display
                        foreach($files as $file_sub) {
                            $safeFileName = htmlspecialchars($file_sub, ENT_QUOTES, 'UTF-8');
                            $safePath = htmlspecialchars($dirName . '/' . $file_sub, ENT_QUOTES, 'UTF-8');

                            // Parse date/time from directory name
                            $nameParts = explode('-', $dirName);
                            $dateTime = '';
                            if (count($nameParts) >= 3) {
                                $dateTime = htmlspecialchars($nameParts[1] . "-" . $nameParts[2], ENT_QUOTES, 'UTF-8');
                            }

                            echo "<tr><td>" . $safeFileName . "</td><td>" . $safePath . "</td><td>" . $dateTime . "</td></tr>";
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</body>

<script>
$(document).ready(function() {
    $('.loading').hide()
    $('#example').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'print'
        ]
    });
    $("#content").delay(700).fadeIn(200);
});
</script>