<?php
$faviconPath = "images/png/logo.png";
$users = [
    "beispielUser" => "beispielPassword"
];

session_start();

$failed = false;
$_SESSION['loggedIn'] = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['user'] ?? '';
    $password = $_POST['password'] ?? '';

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
            <input class="form-control" type="password" name="password" placeholder="Password" required>
            <input id="signin" class="otherButton" type="submit" value="Sign In">
        </form>
    </div>
    <a href="mainTemp.php" class="back-button">back</a>
</body>
</html>
