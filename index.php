<?php

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('Requires PHP version 8.0 or higher!');
}

if (! extension_loaded('pdo_odbc')) {
    die('Please activate pdo_odbc extension in your php.ini!');
}

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit'])) {

    $login = isset($_POST['account']) ? validateInput($_POST['account']) : '';
    $password = isset($_POST['password']) ? validateInput($_POST['password']) : '';
    $payStat = 1;
    $answer = '0x0';
    $quiz = '';

    if (empty($login) || empty($password)) {
        echo '<script>alert("Please fill out all form fields!");</script>';
        die;
    }

    if (! checkStr($login) || !checkStr($password)) {
        echo '<script>alert("Your input fields must contain only latin letters or digits!");</script>';
        exit;
    }

    if (! checkStrLength($login) || !checkStrLength($password)) {
        echo '<script>alert("Your input fields must be between 4 and 16 characters long!");</script>';
        exit;
    }

    $conn = getDatabaseConnection();

    $stmt = $conn->prepare('SELECT COUNT(*) FROM dbo.user_auth WHERE account = :account');
    $stmt->bindParam(':account', $login);
    $stmt->execute();

    $result = $stmt->fetchColumn();

    if ($result > 0) {
        echo '<script>alert("The account already exists!");</script>';
        die;
    }

    try {
        $conn->beginTransaction();

        $stmtUserAccount = $conn->prepare("INSERT INTO dbo.user_account (account, pay_stat) VALUES (:account, :pay_stat)");
        $stmtUserAccount->bindParam(':account', $login);
        $stmtUserAccount->bindParam(':pay_stat', $payStat);
        $stmtUserAccount->execute();

        $stmtUserAuth = $conn->prepare("INSERT INTO dbo.user_auth (account, password, quiz1, quiz2, answer1, answer2) VALUES (:account, :password, :quiz1, :quiz2, :answer1, :answer2)");
        $stmtUserAuth->bindParam(':account', $login);

        $encryptedPassword = encrypt($password);

        $stmtUserAuth->bindParam(':password', $encryptedPassword, PDO::PARAM_LOB);
        $stmtUserAuth->bindParam(':quiz1', $quiz);
        $stmtUserAuth->bindParam(':quiz2', $quiz);
        $stmtUserAuth->bindParam(':answer1', $answer);
        $stmtUserAuth->bindParam(':answer2', $answer);
        $stmtUserAuth->execute();

        $conn->commit();
        echo '<script>alert("Your account has been created successfully!");</script>';
    } catch (PDOException $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>L2PTS REGISTRATION EXAMPLE VIA PDO_ODBC</title>
</head>
<body>
    <h1>Create a PTS account</h1>
    <form method="POST">
        <label for="account">Login:</label>
        <input type="text" name="account" id="account">
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password">
        <br>
        <input type="submit" name="submit" value="Create an account">
    </form>
</body>
</html>
