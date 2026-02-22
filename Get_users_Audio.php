<style type="text/css">
    body {
    display: block;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: linear-gradient(to bottom right, #b968c5, skyblue);
}
.button4,.button3 {border-radius: 12px;}
.button {
  background-color: #04AA6D; /* Green */
  border: none;
  color: white;
  padding: 20px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}
.button4{
    width: 78%;
}
.button3{
      background-color: #00fa9a;
    width: 18%;
    color: black;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<label for="Music Users">Choose a User:</label>

<select name="Music" id="Users">
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

    $scan = scandir(MUSIC_BASE_DIR);
    foreach($scan as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $fullPath = MUSIC_BASE_DIR . $file;
        if (!is_dir($fullPath) || !isValidMusicPath($fullPath)) {
            continue;
        }

        $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        echo "<option value=\"" . $safeFile . "\">" . $safeFile . "</option>";
    }
?>
</select>

<div id="myaud">
</div>
<script type="text/javascript">
$("#Users").change(function() {
    //get the selected value
    var selectedValue = this.value;
    //make the ajax call
    $.ajax({
        url: 'music.php',
        type: 'POST',
        data: {option : selectedValue},
        success: function(data) {
           jQuery('#example_filter').hide();
            $('#example').html(data);
            console.log(data);
        }
    });
});
</script>
<?php
?>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.0/css/jquery.dataTables.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

  <script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.12.0/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
  <style>
      button#order {
    background-color: #83f083;
    padding: 6px 30px;
    border-radius: 15px;
    cursor: pointer;
}
  </style>
  <style>
/* Absolute Center Spinner */
.loading {
  position: fixed;
  z-index: 999;
  height: 2em;
  width: 2em;
  overflow: show;
  margin: auto;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
}

/* Transparent Overlay */
.loading:before {
  content: '';
  display: block;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
    background: radial-gradient(rgba(20, 20, 20,.8), rgba(0, 0, 0, .8));

  background: -webkit-radial-gradient(rgba(20, 20, 20,.8), rgba(0, 0, 0,.8));
}

/* :not(:required) hides these rules from IE9 and below */
.loading:not(:required) {
  /* hide "loading..." text */
  font: 0/0 a;
  color: transparent;
  text-shadow: none;
  background-color: transparent;
  border: 0;
}

.loading:not(:required):after {
  content: '';
  display: block;
  font-size: 10px;
  width: 1em;
  height: 1em;
  margin-top: -0.5em;
  -webkit-animation: spinner 150ms infinite linear;
  -moz-animation: spinner 150ms infinite linear;
  -ms-animation: spinner 150ms infinite linear;
  -o-animation: spinner 150ms infinite linear;
  animation: spinner 150ms infinite linear;
  border-radius: 0.5em;
  -webkit-box-shadow: rgba(255,255,255, 0.75) 1.5em 0 0 0, rgba(255,255,255, 0.75) 1.1em 1.1em 0 0, rgba(255,255,255, 0.75) 0 1.5em 0 0, rgba(255,255,255, 0.75) -1.1em 1.1em 0 0, rgba(255,255,255, 0.75) -1.5em 0 0 0, rgba(255,255,255, 0.75) -1.1em -1.1em 0 0, rgba(255,255,255, 0.75) 0 -1.5em 0 0, rgba(255,255,255, 0.75) 1.1em -1.1em 0 0;
box-shadow: rgba(255,255,255, 0.75) 1.5em 0 0 0, rgba(255,255,255, 0.75) 1.1em 1.1em 0 0, rgba(255,255,255, 0.75) 0 1.5em 0 0, rgba(255,255,255, 0.75) -1.1em 1.1em 0 0, rgba(255,255,255, 0.75) -1.5em 0 0 0, rgba(255,255,255, 0.75) -1.1em -1.1em 0 0, rgba(255,255,255, 0.75) 0 -1.5em 0 0, rgba(255,255,255, 0.75) 1.1em -1.1em 0 0;
}

/* Animation */

@-webkit-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-moz-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-o-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
table#example {
    background: mediumspringgreen;
    border: groove;
    border-radius: 10px;
}
</style>
<title>list of all user Music</title>
<H1>List of all user Music  <a href="Get_users.php" ><button class="button button3">List of all user names</button></a></a>
<a href="fileUpload.php" ><button class="button button3">Uploads</button></a></H1>
<body translate="no" >
  <div class="loading">Loading&#8230;</div>
</body>

<table id='example' class='display nowrap' style='width:100%'>
<thead><tr><th>Users Name</th><th>Users Music</th></tr></thead><tbody>
<?php

?>
</tbody></table>
 <!--Import jQuery before export.js-->
    <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>


    <!--Data Table-->
    <script type="text/javascript"  src=" https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript"  src=" https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>

    <!--Export table buttons-->
    <script type="text/javascript"  src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js" ></script>
    <script type="text/javascript"  src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.1/js/buttons.print.min.js"></script>

<!--Export table button CSS-->

<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css">
<script>
    $(document).ready(function() {
        $('.loading').hide()
        jQuery('#example_filter').hide();
    $('#example').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            
        ]
    } );
} );

</script>

</script>
<!-- <button id="order">Click Here To Load the Latest Order</button> -->


