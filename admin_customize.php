<?php
/**
 * KCR Tracks - Customization Admin Interface
 * Simple interface to customize branding without editing code
 */

define('KCR_TRACKS', true);
require_once 'config.php';

// Simple password protection - CHANGE THIS PASSWORD!
$ADMIN_PASSWORD = 'changeme123';

session_start();

// Handle login
if (isset($_POST['login'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin_customize.php');
    exit;
}

// Handle music storage mode change
if (isset($_POST['save_storage']) && isset($_SESSION['admin_logged_in'])) {
    $newMode = ($_POST['music_storage'] === 'sdcard') ? 'sdcard' : 'usb';
    $output = [];
    $returnCode = 0;
    exec('sudo /usr/local/bin/apply-storage-mode.sh ' . escapeshellarg($newMode) . ' 2>&1', $output, $returnCode);

    $result = json_decode(implode('', $output), true);
    if ($result && $result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'] ?? 'Failed to change storage mode';
    }
}

// Handle customization save
if (isset($_POST['save_branding']) && isset($_SESSION['admin_logged_in'])) {
    $config = "<?php\n";
    $config .= "/**\n * KCR Tracks - Branding & Customization Configuration\n";
    $config .= " * Auto-generated on " . date('Y-m-d H:i:s') . "\n */\n\n";
    $config .= "if (!defined('KCR_TRACKS')) {\n    define('KCR_TRACKS', true);\n}\n\n";
    $config .= "class Branding {\n\n";

    // Station info
    $stationName = $_POST['station_name'] ?? '';
    $stationShort = $_POST['station_short'] ?? '';
    $stationWebsite = $_POST['station_website'] ?? '';

    $config .= "    // Station Information\n";
    $config .= "    public static \$stationName = " . var_export($stationName, true) . ";\n";
    $config .= "    public static \$stationShortName = " . var_export($stationShort, true) . ";\n";
    $config .= "    public static \$stationWebsite = " . var_export($stationWebsite, true) . ";\n\n";

    // Logos
    $config .= "    // Logo Configuration\n";
    $config .= "    public static \$logoPath = " . var_export($_POST['logo_path'] ?? 'images/kcr-logo-cropped.png', true) . ";\n";
    $config .= "    public static \$tracksLogoPath = " . var_export($_POST['tracks_logo_path'] ?? 'images/tracks-logo.png', true) . ";\n";
    $config .= "    public static \$faviconPath = " . var_export($_POST['favicon_path'] ?? 'images/favicon.ico', true) . ";\n\n";

    // Colors
    $config .= "    // Color Scheme (CSS Custom Properties)\n";
    $config .= "    public static \$colors = [\n";
    $config .= "        'primary-color' => " . var_export($_POST['primary_color'] ?? '#1a7a7a', true) . ",\n";
    $config .= "        'primary-dark' => " . var_export($_POST['primary_dark'] ?? '#145a5a', true) . ",\n";
    $config .= "        'primary-light' => " . var_export($_POST['primary_light'] ?? '#4da6a6', true) . ",\n";
    $config .= "        'accent-color' => " . var_export($_POST['accent_color'] ?? '#d32f2f', true) . ",\n";
    $config .= "        'accent-light' => " . var_export($_POST['accent_light'] ?? '#ff6659', true) . ",\n";
    $config .= "        'background-main' => " . var_export($_POST['background_main'] ?? '#1a7a7a', true) . ",\n";
    $config .= "        'background-secondary' => '#f5f5f5',\n";
    $config .= "        'background-card' => '#ffffff',\n";
    $config .= "        'text-primary' => '#ffffff',\n";
    $config .= "        'text-secondary' => '#333333',\n";
    $config .= "        'text-muted' => '#666666',\n";
    $config .= "        'button-primary' => " . var_export($_POST['primary_color'] ?? '#1a7a7a', true) . ",\n";
    $config .= "        'button-primary-hover' => " . var_export($_POST['primary_dark'] ?? '#145a5a', true) . ",\n";
    $config .= "        'button-secondary' => " . var_export($_POST['primary_light'] ?? '#4da6a6', true) . ",\n";
    $config .= "        'button-danger' => '#d32f2f',\n";
    $config .= "        'border-color' => '#ddd',\n";
    $config .= "        'shadow-color' => 'rgba(0, 0, 0, 0.1)',\n";
    $config .= "        'active-track' => " . var_export($_POST['accent_light'] ?? '#ff6659', true) . ",\n";
    $config .= "    ];\n\n";

    // Add the rest of the class methods (copy from original)
    $config .= file_get_contents('branding_template.txt');

    if (file_put_contents('branding.php', $config)) {
        $success = "Branding saved successfully!";
    } else {
        $error = "Failed to save branding configuration";
    }
}

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login - KCR Tracks</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background: linear-gradient(135deg, #1a7a7a, #4da6a6);
                margin: 0;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                width: 300px;
            }
            h2 { margin-top: 0; color: #1a7a7a; }
            input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-sizing: border-box;
            }
            button {
                width: 100%;
                padding: 12px;
                background: #1a7a7a;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            }
            button:hover { background: #145a5a; }
            .error { color: #d32f2f; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin Login</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Enter admin password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load current branding
require_once 'branding.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customize Branding - KCR Tracks</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a7a7a;
            margin-top: 0;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section h2 {
            margin-top: 0;
            color: #145a5a;
            font-size: 18px;
        }
        label {
            display: block;
            margin: 15px 0 5px 0;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="url"],
        input[type="color"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="color"] {
            height: 50px;
            cursor: pointer;
        }
        .color-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: #1a7a7a;
            color: white;
        }
        .btn-primary:hover {
            background: #145a5a;
        }
        .btn-secondary {
            background: #ddd;
            color: #333;
        }
        .btn-secondary:hover {
            background: #ccc;
        }
        .success {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #d32f2f;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .storage-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 15px 0;
        }
        .storage-option {
            display: block;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: normal;
        }
        .storage-option:hover {
            border-color: #1a7a7a;
        }
        .storage-option input[type="radio"] {
            margin-right: 8px;
        }
        .storage-option .hint {
            display: block;
            margin-top: 5px;
            margin-left: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Customize Branding</h1>
        <p><a href="?logout">Logout</a> | <a href="login.php">Back to App</a></p>

        <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="post">
            <div class="section">
                <h2>Station Information</h2>
                <label>Station Name:</label>
                <input type="text" name="station_name" value="<?php echo htmlspecialchars(Branding::$stationName); ?>" required>
                <p class="hint">Full name of your radio station</p>

                <label>Short Name:</label>
                <input type="text" name="station_short" value="<?php echo htmlspecialchars(Branding::$stationShortName); ?>" required>
                <p class="hint">Abbreviation or short name (e.g., "KCR")</p>

                <label>Website URL:</label>
                <input type="url" name="station_website" value="<?php echo htmlspecialchars(Branding::$stationWebsite); ?>">
                <p class="hint">Your station's website (optional)</p>
            </div>

            <div class="section">
                <h2>Logo Paths</h2>
                <label>Main Logo:</label>
                <input type="text" name="logo_path" value="<?php echo htmlspecialchars(Branding::$logoPath); ?>">
                <p class="hint">Path to your station logo (e.g., images/my-logo.png)</p>

                <label>Tracks Logo:</label>
                <input type="text" name="tracks_logo_path" value="<?php echo htmlspecialchars(Branding::$tracksLogoPath); ?>">
                <p class="hint">Path to tracks logo (optional)</p>

                <label>Favicon:</label>
                <input type="text" name="favicon_path" value="<?php echo htmlspecialchars(Branding::$faviconPath); ?>">
                <p class="hint">Path to favicon icon</p>
            </div>

            <div class="section">
                <h2>Color Scheme</h2>
                <p class="hint">Click on colors to change them</p>

                <div class="color-group">
                    <div>
                        <label>Primary Color:</label>
                        <input type="color" name="primary_color" value="<?php echo Branding::$colors['primary-color']; ?>">
                    </div>
                    <div>
                        <label>Primary Dark:</label>
                        <input type="color" name="primary_dark" value="<?php echo Branding::$colors['primary-dark']; ?>">
                    </div>
                    <div>
                        <label>Primary Light:</label>
                        <input type="color" name="primary_light" value="<?php echo Branding::$colors['primary-light']; ?>">
                    </div>
                    <div>
                        <label>Accent Color:</label>
                        <input type="color" name="accent_color" value="<?php echo Branding::$colors['accent-color']; ?>">
                    </div>
                    <div>
                        <label>Accent Light:</label>
                        <input type="color" name="accent_light" value="<?php echo Branding::$colors['accent-light']; ?>">
                    </div>
                    <div>
                        <label>Background:</label>
                        <input type="color" name="background_main" value="<?php echo Branding::$colors['background-main']; ?>">
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" name="save_branding" class="btn-primary">Save Changes</button>
                <button type="button" onclick="location.href='login.php'" class="btn-secondary">Cancel</button>
            </div>
        </form>

        <form method="post" style="margin-top: 30px;">
            <div class="section">
                <h2>Music Storage</h2>
                <p>Choose where music files are stored.</p>

                <?php
                    // Detect current mode
                    $musicPath = __DIR__ . '/music';
                    $currentMode = is_link($musicPath) ? 'usb' : 'sdcard';
                    $usbMounted = is_dir('/mnt/kcr-music') && @file_exists('/mnt/kcr-music/music');
                ?>

                <div class="storage-options">
                    <label class="storage-option">
                        <input type="radio" name="music_storage" value="sdcard" <?php echo $currentMode === 'sdcard' ? 'checked' : ''; ?>>
                        <strong>SD Card</strong> — Music stored on this SD card
                        <span class="hint">Simpler setup. Limited by SD card size.</span>
                    </label>
                    <label class="storage-option">
                        <input type="radio" name="music_storage" value="usb" <?php echo $currentMode === 'usb' ? 'checked' : ''; ?>>
                        <strong>USB SSD</strong> — Music stored on separate USB drive
                        <span class="hint">Best for large libraries. Requires a USB drive labelled KCR-MUSIC.
                        <?php if (!$usbMounted && $currentMode !== 'usb') echo '<br><em>No KCR-MUSIC drive detected.</em>'; ?></span>
                    </label>
                </div>

                <p class="hint" style="margin-top: 15px;">Currently using: <strong><?php echo $currentMode === 'usb' ? 'USB SSD' : 'SD Card'; ?></strong></p>

                <div class="button-group">
                    <button type="submit" name="save_storage" class="btn-primary">Apply Storage Setting</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
<?php
?>
