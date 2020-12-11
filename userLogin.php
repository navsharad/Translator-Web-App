<?php
    require_once 'login.php';
    session_start();
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die(mysql_error());

    if ($_SESSION && $_SESSION['loggedIn']) header("Location: userProfile.php");

    //form for log in and sign up
    echo <<<_END
    <html><head><title>Login/Signup</title></head><body>
    <h1>Log In</h1>
    <form method='post' action='userLogin.php' enctype='multipart/form-data'>
        <pre>
        Username: <input type='text' name='name'><br>
        Password: <input type='password' name='password'>
        <input type='submit' value='Login' name='login'>
        <pre>
    </form>
    <h1>Or Sign Up Below</h1>
    <form method='post' action='userLogin.php' enctype='multipart/form-data'>
        <pre>
        Email:    <input type='text' name='createEmail'><br>
        Username: <input type='text' name='createName'><br>
        Password: <input type='password' name='createPassword'>
        <input type='submit' value='Create User' name='createUser'>
        <pre>
    </form>
    _END;

    //validates username and password
    if (isset($_POST['login']) && isset($_POST['name']) && isset($_POST['password'])) {
        $username = sanitize($conn, $_POST['name']);
        $password = sanitize($conn, $_POST['password']);

        $query = "SELECT * FROM user WHERE username='$username'";
        $result = $conn->query($query);
        if(!$result) die(mysql_error());
        $row = $result->num_rows;
        if ($row == 0) {
            echo 'Invalid username or password.';
        } else {
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_NUM);
            $hashedPassword = $row[1];
            $email = $row[2]; 
            if(password_verify($password, $hashedPassword)) {
                $_SESSION['loggedIn'] = true;
                $_SESSION['email'] = $email;
                header("Location: userProfile.php");
            } else {
                echo 'Invalid username or password.';
            }
        } 
    }

    //create user
    if (!empty($_POST['createUser']) && !empty($_POST['createName']) && !empty($_POST['createPassword']) && !empty($_POST['createEmail'])) {
        $username = sanitize($conn, $_POST['createName']);
        $password = sanitize($conn, $_POST['createPassword']);
        $email = sanitize($conn, $_POST['createEmail']);
        $password = password_hash($password, PASSWORD_DEFAULT);

        if (duplicate_checker($conn, $email)) {
            echo 'Cannot create account, this email is associated with another account.';
        } else if (duplicate_checker($conn, $username)) {
            echo 'Cannot create account, this username is already taken.';
        } else {
            $query = "INSERT INTO user (username, password, email) VALUES ('$username', '$password', '$email')";
            $result = $conn->query($query);
            if (!$result) 
            echo 'Insert Failed.';
        else
            echo 'User created.';
        }
        
    }
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

    //returns true if duplicate username or email in database
    function duplicate_checker($conn, $var) {
        $query = "SELECT * FROM user WHERE username='$var' OR email='$var';";
        $result = $conn->query($query);
        if(!$result) die(mysql_error());
        $row = $result->num_rows;
        if ($row != 0)
            return true;
        else
            return false;

    }