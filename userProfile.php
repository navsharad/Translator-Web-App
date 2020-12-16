<?php
    require_once 'login.php';
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die(mysql_error());
    session_start();

    //if someone not logged in tries to access this page, gets redirected
    if (!$_SESSION['loggedIn']) header("Location: userLogin.php");
    $email = $_SESSION['email'];

        echo <<<_END
        <html><head><title>Profile</title></head><body>
        <h1>Translator App</h1>
        <hr>
        <h2>Welcome</h2>
        <h4> Submit a text file with english word and translated word seperated by a space. Add new line for each entry in the file.</h4>
        <form method='post' action='userProfile.php' enctype='multipart/form-data'>
            Text File: <input type='file' name='filecontent'><br>
            <input type='submit' value='add' name='submit'>
            <br><br>
            <h2>Click here to log out.</h2>
            <input type='submit' value='Log Out' name='logout'>
        </form>
        <h2>Enter English text to translate.</h2>
        <h5>If word does not exist in dictionary, inputted word will be printed out in English.</h5>
        <form method='post' action='userProfile.php' enctype='multipart/form-data'>
            <input type='text' name='english'>
            <input type='submit' value='translate' name='translate'>
            <br><br>
        </form>
        _END;
    

    //adds translation file to database
    if (isset($_POST['submit']) && $_FILES && $_FILES['filecontent']['type'] == 'text/plain') {
        $fileContent = file_get_contents($_FILES['filecontent']['tmp_name']);
        handleFile($conn,$email, $fileContent); //reads file and stores translations in database
    }   
        
    //searches db for matches and prints out translated word if found, otherwise prints out user inputted word
    if (isset($_POST['translate'])) {
        $englishPhrase = sanitize($conn, $_POST['english']);
        $words = explode(" ", $englishPhrase);
        foreach($words as $word) {
            $query = "SELECT * FROM translation WHERE english='$word' AND email='$email';";
            $result = $conn->query($query);
            if (!$result) die(mysql_error());
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

    //logs user out
    if (isset($_POST['logout'])) {
        $_SESSION = array();
        setcookie(session_name(),'', time() - 2592000, '/');
        session_destroy();
        header("Location: userLogin.php");
    }

    $conn->close();

    //sanitizes file content and inserts it into db
    function handleFile($conn, $email, $fileContent) {
        $fileContent = stripslashes($fileContent);
        $fileContent = strip_tags($fileContent);
        $fileContent = htmlentities($fileContent);
        $fileContent = rtrim($fileContent);

        $result = $conn->query("TRUNCATE TABLE translation");// deletes previous dictionary since its not needed and to avoid table from getting too crowded
        if (!$result) echo 'Insert failed';
        $words = explode("\n", $fileContent);
        foreach($words as $line) { //each line has the english word followed by translated word, explode them and insert into db
            $line = explode(" ", $line);
            $englishWord = $line[0];
            $transWord = $line[1];
            $query = "INSERT INTO translation (email, english, translated) VALUES ('$email','$englishWord', '$transWord');";
            $result = $conn->query($query);
            if (!$result) echo 'Insert failed';
        }
        echo 'File sucessfully added!';
    }
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

