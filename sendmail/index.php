<?php

include('kcaptcha/kcaptcha.php');
session_start();
require_once("config.php");


if ($_POST['act']== "y")
{
if(isset($_SESSION['captcha_keystring']) && $_SESSION['captcha_keystring'] ==  $_POST['keystring'])
{

if (isset($_POST['posName']) && $_POST['posName'] == "")
{
$statusError = "$errors_name";
}
elseif (isset($_POST['posEmail']) && $_POST['posEmail'] == "")
{
$statusError = "$errors_mailfrom";
}
elseif(isset($_POST['posEmail']) && !preg_match("/^([a-z,._,0-9])+@([a-z,._,0-9])+(.([a-z])+)+$/", $_POST['posEmail']))
{
$statusError = "$errors_incorrect";

unset($_POST['posEmail']);
}
elseif (isset($_POST['posRegard']) && $_POST['posRegard'] == "")
{
$statusError = "$errors_subject";
}
elseif (isset($_POST['posText']) && $_POST['posText'] == "")
{
$statusError = "$errors_message";
}

elseif (!empty($_POST))
{
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: $content  charset=$charset\r\n";
$headers .= "Date: ".date("Y-m-d (H:i:s)",time())."\r\n";
$headers .= "From: \"".$_POST['posName']."\" <".$_POST['posEmail'].">\r\n";
$headers .= "X-Mailer: My Send E-mail\r\n";

mail("$mailto","$subject","$message","$headers");

unset($name, $posText, $mailto, $subject, $posRegard, $message);

$statusSuccess = "$send";
}

}else{
$statusError = "$captcha_error";
unset($_SESSION['captcha_keystring']);
}
}
?>

<html>
<head>
<title>Oбpaтнaя cвязь</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<link href="styling.css" rel="stylesheet" type="text/css" media='screen,projection' />
</head>
<body>

<h2>Oбpaтнaя cвязь</h2>
<p id="emailSuccess">
<strong style="color:green;"><?php echo "$statusSuccess" ?></strong>
</p>
<p id="emailError"><strong style="color:red;"><?php echo "$statusError" ?></strong></p>

<div id="contactFormArea">
<form action="./" method="post" id="cForm">
<input type="hidden" name="act" value="y" />
<fieldset>
<label for="posName"><b>Ваше имя:</b></label>
<input class="text" type="text" size="25" name="posName" id="posName" />
<label for="posEmail"><b>Ваш E-mail адрес:</b></label>
<input class="text" type="text" size="25" name="posEmail" id="posEmail" />
<label for="posRegard"><b>Тема сообщения:</b></label>
<input class="text" type="text" size="25" name="posRegard" id="posRegard" />
<label for="posText"><b>Сообщение:</b></label>
<textarea cols="50" rows="20" name="posText" id="posText"></textarea>
<label for="posCaptcha"><center><b>Текст на изображении (цифры)</b>:</label><a href='readme.html'><img src="kcaptcha?<?php echo session_name()?>=<?php echo session_id()?>" border=0></a></center><input class="text" type="text" size="25" name="keystring" id="keystring" />
<br><br><label><input class="submit" type="submit" name="selfCC" id="selfCC" value=" Отправить " /></label>
</fieldset>
<?php include 'kcaptcha/kcaptcha_rand.php' ?>
</form>
</div>

</body>
</html>