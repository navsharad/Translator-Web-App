<?php
    require_once 'login.php';
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die(mysql_error());
    session_start();

    //if someone not logged in tries to access this page, gets redirected
    if (!$_SESSION['loggedIn']) header("Location: userLogin.php");
    $email = $_SESSION['email'];
    
    //form for user to add content and log out
    echo <<<_END
        <html><head><title>Profile</title></head><body>
        <h2>Welcome. Enter name and attach text file to upload.</h2>
        <form method='post' action='userProfile.php' enctype='multipart/form-data'>
            Name: <input type='text' name='filename'><br>
            Text File: <input type='file' name='filecontent'><br>
            <input type='submit' value='add' name='submit'>
            <br><br>
            <h2>Click here to log out.</h2>
            <input type='submit' value='Log Out' name='logout'>
        </form>
        _END;

    //adds user content to database
    if (isset($_POST['submit']) && !empty($_POST['filename']) && $_FILES && $_FILES['filecontent']['type'] == 'text/plain') {
        $fileName = sanitize($conn, $_POST['filename']);
        $fileContent = file_get_contents($_FILES['filecontent']['tmp_name']);
        $fileContent = sanitize($conn, $fileContent);
        $query = "INSERT INTO usercontent (email, name, content) VALUES ('$email','$fileName', '$fileContent');";
        $result = $conn->query($query);
        if (!$result) echo 'Insert failed';
    }

    //logs user out
    if (isset($_POST['logout'])) {
        $_SESSION = array();
        setcookie(session_name(),'', time() - 2592000, '/');
        session_destroy();
        header("Location: userLogin.php");
    }

    //prints users content
    $query = "SELECT * FROM usercontent WHERE email='$email';";
    $result = $conn->query($query);
    if (!$result) die(mysql_error());
    $rows = $result->num_rows;
    echo '<h2>Your Content</h2>';
    for ($i = 0; $i < $rows; ++$i) {
        $result->data_seek($i);
        $row = $result->fetch_array(MYSQLI_NUM);
        echo <<<_END
        <pre>
        Name: $row[1]
        Content: $row[2]
        </pre>
        <hr>
        _END;
    } 
    $result->close();
    $conn->close();

    //sanitizes user inputted variables
    function sanitize($conn, $var) {
        $var = $conn->real_escape_string($var);
        $var = stripslashes($var);
        $var = strip_tags($var);
        $var = htmlentities($var);
        return $var;
    }

    function mysql_error() {
        echo 'Oops something went wrong on wrong on our end, please try refreshing your page. If you are still having issues please contact support@gmail.com.
        We apologize for the inconvenience.';
    }

