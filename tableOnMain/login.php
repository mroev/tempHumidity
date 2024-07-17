<?php
$faviconPath = "images/png/logo.png";
$users = [
];

session_start();

$failed = false;
$_SESSION['loggedIn'] = false;

// Überprüfung, ob die Anmeldedaten übermittelt wurden
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['user'] ?? '';
    $password = $_POST['password'] ?? '';

    // Überprüfen, ob der Benutzername und das Passwort mit den gespeicherten Daten übereinstimmen
    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['loggedIn'] = true;
        header("Location: mainTemp.php");
    } else {
        $failed = true;
        $_SESSION['loggedIn'] = false;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" charset="utf-8">
    <title>Login</title>
    <link rel="icon" href="<?php echo $faviconPath; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/styleTemp.css">
    <script src="/js/bootstrap.bundle.min.js" ></script>
</head>
<body>
    <div id="login"> 
        <?php if ($failed): ?>
            <div class="alert alert-danger" id="login-bad">Invalid user or password.</div>
        <?php endif; ?>
            
        <form id="login-form" method="post" action="">
            <h1>PLEASE SIGN IN</h1>
            <input class="form-control" type="text" name="user" placeholder="Username" required>
            <div class="password-container">
                <input class="form-control" type="password" name="password" id="password" placeholder="Password" required>
                <span class="password-toggle" onclick="togglePassword()">
                    <img id="toggleIcon" src="images/png/close-eye.png" alt="Toggle Password">
                </span>
            </div>
            <input id="signin" class="otherButton" type="submit" value="Sign In">
        </form>
    </div>
    <a href="mainTemp.php" class="back-button">back</a>

    <script>
    function togglePassword() {
        var passwordInput = document.getElementById("password");
        var toggleIcon = document.getElementById("toggleIcon");
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.src = "images/png/open-eye.png";
        } else {
            passwordInput.type = "password";
            toggleIcon.src = "images/png/close-eye.png";
        }
    }
    </script>
</body>
</html>
