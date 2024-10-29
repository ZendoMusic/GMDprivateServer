<?php
//error_reporting(0);
include "../incl/lib/connection.php";
require_once "../incl/lib/exploitPatch.php";
require_once "../incl/lib/zemu.php";
require_once "../incl/lib/Captcha.php";
$zm = new zemu();
if(!empty($_POST['songid'])){

	if(!Captcha::validateCaptcha())
		exit("Invalid captcha response");

	$result = $zm->songReupload($_POST['songid']);
    switch ($result) {
        case "-6":
            echo "config/zemu.php file is missing or corrupted or not configurated. Please check the file and try again.";
            break;
        case "-5":
        case "-2":
            echo "The download link isn't a valid URL";
            break;
        case "-4":
            echo "This URL doesn't point to a valid audio file.";
            break;
        case "-3":
            echo "This song already exists in our database.";
            break;
        case "-1":
            echo "Song failed to upload. Please try again.";
            break;
        default:
            echo "Song reuploaded: <b>${result}</b><hr>";
    }

}else{
	echo 'Reupload song from <b>ZendoMusic</b><br>
		<form action="zemuSongReupload.php" method="post">
		Song ID: <input type="text" name="songid"><br>';
	Captcha::displayCaptcha();
	echo '<input type="submit" value="Reupload Song"></form>';
}
?>