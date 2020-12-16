<?php
    require_once 'login.php';
    session_start();
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die(mysql_error());

    if ($_SESSION && $_SESSION['loggedIn']) header("Location: userProfile.php");

    //form for log in and sign up
    echo <<<_END
    <html><head><title>Login/Signup</title></head><body>
    <h1>Translator App</h1>
    <hr>
    <pre>
    <h2>Log In to upload and use custom dictionary</h2>
    <form method='post' action='userLogin.php' enctype='multipart/form-data'>
        Username: <input type='text' name='name'><br>
        Password: <input type='password' name='password'>
        <input type='submit' value='Login' name='login'>
    </form>
    <h2>Or Sign Up Below</h2>
    <form method='post' action='userLogin.php' enctype='multipart/form-data'>
        <pre>
        Email:    <input type='text' name='createEmail'><br>
        Username: <input type='text' name='createName'><br>
        Password: <input type='password' name='createPassword'>
        <input type='submit' value='Create User' name='createUser'>
        </pre>
    </form>
    <h2>Or use default dictionary.</h2>
    <h5>If word does not exist in dictionary, inputted word will be printed out in English.</h5>
    <form method='post' action='userLogin.php' enctype='multipart/form-data'>
        <input type='text' name='english'>
        <input type='submit' value='translate' name='translate'>
        <br><br>
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

    //searches db for matches and prints out translated word if found, otherwise prints out user inputted word in English
    if (isset($_POST['translate'])) {
        $englishPhrase = sanitize($conn, $_POST['english']);
        $words = explode(" ", $englishPhrase);
        foreach($words as $word) {
            $query = "SELECT * FROM translation WHERE english='$word'; AND email= 'default'";
            $result = $conn->query($query);
            if (!$result) die();
            $rows = $result->num_rows;
            if ($rows == 0) // if word doesnt exist in database, print out the inputted word
                echo $word." ";
            else { // prints out translation
                $result->data_seek(0);
                $row = $result->fetch_array(MYSQLI_NUM);
                echo $row[2]." ";
            }

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