<meta http-equiv='refresh' content='6; url=https://dmb8637.github.io'>
<meta charset="UTF-8" />
<?php
if (isset($_POST['name'])) {$name = $_POST['name']; if ($name == '') {unset($name);}}
if (isset($_POST['email'])) {$email = $_POST['email']; if ($email == '') {unset($email);}}
if (isset($_POST['sub'])) {$sub = $_POST['sub']; if ($sub == '') {unset($sub);}}
if (isset($_POST['body'])) {$body = $_POST['body']; if ($body == '') {unset($body);}}
 
if (isset($name) && isset($email) && isset($sub) && isset($body)){
 
$address = "dmb8637@mail.ru";
$mes = "���: $name \nE-mail: $email \n����: $sub \n�����: $body";
$send = mail ($address,$sub,$mes,"Content-type:text/plain; charset = UTF-8\r\nFrom:$email");
if ($send == 'true')
{echo "��������� ���������� �������, ����� 6 ������ �� ������ ���������� �� ������� �������� ����� <a href='https://dmb8637.github.io/'>dmb8637.github.io</a>,��� ������� ���������� ��� ��������";}
else {echo "������, ��������� �� ����������!";}
 
}
else
{
echo "�� ��������� �� ��� ����, ��������� ����� � ��������� ����������� ����!";
}
?>