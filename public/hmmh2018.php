<?php
/*
H.O.W.D.Y. Media Mega Helper
www.howdymedia.com
315-469-8414

v4.1 - 2019-04-04

This tools ws created to assist with common functions while working on web development projects.

If you found this and are not currently a client of ours, please delete this tool as it could be misused.

*/

session_start();
error_reporting(E_ALL);
date_default_timezone_set('America/New_York');
$basedir = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF']).'/';
$host = $_SERVER['SERVER_NAME'];

class Db {

 /**
  * Database Class
  *
  * @var string $host - Database server
  * @var string $username - Database user
  * @var string $password - Database user's password
  * @var string $database - Database name
  * @var string $query - Last query executed
  *
  * @copyright 2018 H.O.W.D.Y. Media
  * @version  1.4
  */

 private $host = '';
 private $username = '';
 private $password = '';
 private $database = '';
 private $query = '';

 private $db = false;
 private $connected = false;
 private $query_resource = false;
 private $method = 'mysqli';
 private $mode = 'object';
 private $fatal_errors = true;

 private $instance_id = null;
 static $instance = 0;

 public function __construct() {
  $this->instance_id = self::$instance;
  self::$instance++;
 }

 public function getInstanceID() {return self::$instance;}
 public function getMethod() {return $this->method;}
 public function getMode() {return $this->mode;}
 public function setMethod($method) {$this->method = ($method == 'pdo' ? 'pdo' : 'mysqli');}
 public function setMode($mode) {$this->mode = ($mode == 'object' ? 'object' : 'array');}

 public function connect($host,$username,$password,$db = '') {

  $this->host = $host;
  $this->username = $username;
  $this->password = $password;
  if (isset($db)) $this->database = $db;

  if ($this->method == 'mysqli') $this->db = mysqli_connect($this->host,$this->username,$this->password,$this->database);
  if ($this->db) return true;
  return false;
 }

 public function query($query) {
  if ($this->db === false) return false;
  $this->query = $query;
  if ($this->method == 'mysqli') $this->query_resource = mysqli_query($this->db,$query);

  if ($this->query_resource) return $this->query_resource;

  $this->error();
  return false;
 }

 public function fetch($resource = false) {
  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {
   if ($this->method == 'mysqli') {
    if ($this->mode == 'object') return mysqli_fetch_object($resource);
    else return mysqli_fetch_assoc($resource);
   }
  }
  return false;
 }

 public function fetch_numeric($resource = false) {
  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {
   if ($this->method == 'mysqli') return mysqli_fetch_array($resource,MYSQLI_NUM);

  }
  return false;
 }

 public function fetch_all($resource = false) {
  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {
   $rows = array();

   if ($this->method == 'mysqli') {
    if ($this->mode == 'object') while ($r = mysqli_fetch_object($resource)) $rows[] = $r;
    else while ($r = mysqli_fetch_assoc($resource)) $rows[] = $r;
   }

   $this->free();

   return $rows;
  }
  return false;
 }

 public function result($query,$intval = false) {
  $q = $this->query($query);
  if ($q) {
   $r = $this->fetch($q);
   $this->free($q);
   if (is_array($r) || is_object($r)) {
    $r = current($r);
    if ($intval) $r = intval($r);
   }

   return $r;
  }
  return false;
 }

 public function get_row($resource = false) {

  if (is_string($resource)) $resource = $this->query($resource);

  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {
   if ($this->method == 'mysqli') {
    if ($this->mode == 'object') $r = mysqli_fetch_object($resource);
    else $r = mysqli_fetch_assoc($resource);
   }

   $this->free($resource);
   return $r;
  }

  return false;
 }

 public function count($resource = false) {
  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {

   if ($this->method == 'mysqli') {
    $c = mysqli_num_rows($resource);
    if ($c !== false) mysqli_data_seek($resource,0);
   }

   return intval($c);
  }
  return false;
 }

 private function prepFields($assoc_array) {
  $out['fields'] = '`'.implode('`,`',array_keys($assoc_array)).'`';
  $out['tokens'] = implode(',',array_fill(0,count($out['fields']),"'%s'"));
  $out['values'] = array_values($assoc_array);
  return $out;
 }

 public function insert($table,$assoc_array) {
  $fields = $this->prepFields($assoc_array);
  $q = $this->query($this->prepare("INSERT INTO `$table` ({$fields['fields']}) VALUES ({$fields['tokens']})",$fields['values']));
  return $this->insert_id();
 }

 /**
  * Execute a MySQL UPDATE query
  *
  * @param string $table
  * @param array $assoc_array
  * @param string|array $where Strings passed must already be prepared to prevent MySQL injection
  * @return boolean|int Returns insert ID or false
  */
 public function update($table,$assoc_array,$where) {
  $fields = $this->prepFields($assoc_array);
  if (is_array($where)) $where = $this->prepFields($where);
  $q = $this->query($this->prepare("UPDATE `$table` ({$fields['fields']}) VALUES ({$fields['tokens']})",$fields['values']));
  return $this->insert_id();
 }

 public function insert_id() {
  if ($this->method == 'mysqli') return mysqli_insert_id($this->db);

  return false;
 }

 public function free($resource = false) {
  $resource = (is_object($resource) ? $resource : $this->query_resource);

  if (is_object($resource)) {
   if ($this->method == 'mysqli') mysqli_free_result($resource);

  }

 }

 public function affected() {
  if ($this->method == 'mysqli') return mysqli_affected_rows($this->db);

  return false;
 }

 function prepare($query) {
  $query = str_replace('%s',';s;',$query);
  $query = str_replace('%d',';d;',$query);
  $query = str_replace('%',';q;',$query);
  $query = str_replace(';s;','%s',$query);
  $query = str_replace(';d;','%d',$query);
  $args = func_get_arg(1);
  if (!is_array($args)) {
   $args = func_get_args();
   array_shift($args);
  }
  $args = call_user_func(array($this,'escape'),$args);
  array_unshift($args,$query);
  $query = call_user_func_array('sprintf',$args);
  $query = str_replace(';q;','%',$query);
  return $query;
 }

 public function escape($field_array) {
  if (!is_array($field_array)) {
   $is_string = true;
   $field_array = array($field_array);
  }
  else $is_string = false;

  if ($this->method == 'mysqli')
   foreach ($field_array as $id => $field) {
    if (get_magic_quotes_gpc()) $field = stripslashes($field);
    $field_array[$id] = mysqli_real_escape_string($this->db,$field);
   }

  if ($is_string) return current($field_array);

  return $field_array;
 }

 public function fieldExists($table,$field) {
  $table = $this->escape($table);
  $field = $this->escape($field);
  $q = $this->query("SHOW FIELDS FROM $table WHERE field = '$field';");
  if ($this->count($q) > 0) return true;
  return false;
 }

 public function tableExists($table) {
  $q = $this->query("SHOW TABLES LIKE '".$this->escape($table)."';");
  if ($this->count($q) > 0) return true;
  return false;
 }

 private function error($resource = false) {
  if ($this->db) {
   if ($this->method == 'mysqli') {
    $errno = mysqli_errno($this->db);
    $error = mysqli_error($this->db);
    echo "<p><b>$errno - $error<br><br>{$this->query}</b></p>";
   }
  }
 }
}

function cleanOutput($in,$nl2br = false) { // HM_cleanOutput v1.3
 $in = htmlentities(stripslashes($in),ENT_COMPAT,'UTF-8');
 if ($nl2br) $in = nl2br($in);
 return $in;
}
function cleanOutputTextarea($in) { // HM_cleanOutputTextarea v1.0
 $in = str_replace('&','&amp;',$in);
 return str_replace('<','&lt;',$in);
}
function dirtyTextarea($in) { // HM_dirtyTextarea v1.0
 $in =  str_replace('&lt;','<',$in);
 return str_replace('&amp;','&',$in);
}
function secure() {
 if (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') {
  header ('Location: https://'.$_SERVER['REQUEST_URI']);
  exit();
 }
}
function pdir($path) {
 $bit = strrev($path);
 $bits = explode('/',$bit,2);
 return strrev($bits[1]);
}
function emailfile($file,$email) {
 global $host;
 $message = 'File from: '.$_SERVER['HTTP_HOST'].'<br>';
 $filename = basename($file);
 $handle = fopen($file, 'rb');
 $f_contents = fread($handle, filesize($file));
 $f_contents = chunk_split(base64_encode($f_contents));
 $f_type = filetype($file);
 fclose($handle);

 $mime_boundary = md5(time());
 $headers = "From: system@$host\r\n".
 "Reply-To: system@$host\r\n".
 "Message-ID: <".$now." system@$host>\r\n".
 'X-Mailer: Mega-Help Mailer'."\r\n".
 "MIME-Version: 1.0\r\n".
 "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n".

 $msg = "This message is in MIME format. Since your mail reader does not understand
this format, some or all of this message may not be legible.\r\n\r\n";
 $msg .= "--".$mime_boundary."\r\n";
 $msg .= "Content-Type: text/html; charset=iso-8859-1\r\n";
 $msg .= "Content-Transfer-Encoding: 8bit\r\n";
 $msg .= $message."\r\n\r\n";
 $msg .= "--".$mime_boundary."\r\n";
 $msg .= "Content-Type: application/txt; name=\"$filename\"\r\n";
 $msg .= "Content-Transfer-Encoding: base64\r\n";
 $msg .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
 $msg .= $f_contents."\r\n\r\n";
 $msg .= "--".$mime_boundary."--\r\n\r\n";
 mail($email,'[MH] '.$host,$msg,$headers);
}
function upFile($upfile,$path) {
 global $basedir;
 echo 'File: '.cleanOutput($_FILES[$upfile]['name']).' ';
 $result = false;
 if ($_FILES[$upfile]['error'] > 0) {
  echo '<span class="err">';
  switch ($_FILES[$upfile]['error']) {
   case 1:
    echo 'Failed File Upload: File is too big.<br>';
    break;
   case 2:
    echo 'Failed File Upload: File is too big.<br>';
    break;
   case 3:
    echo 'Failed File Upload: File only partially uploaded.<br>';
    break;
   case 4:
    echo 'Failed File Upload: No file was uploaded.<br>';
    break;
   default:
    echo 'Failed File Upload: Unknown Error.<br>';
  }
  echo '</span>';
 } elseif ($_FILES[$upfile]['size'] != 0) {
  $newfile .= basename($_FILES[$upfile]['name']);
  if (move_uploaded_file($_FILES[$upfile]['tmp_name'], rtrim($path,'/').'/'.$newfile)) {
   echo '<span class="good">Uploaded Successfully</span>.<br>';
   $result = true;
  } else {
   echo '<span class="err">Failed Upload</span>.<br>';
  }
 }
 return array($result,$newfile);
}
function mkspc($text,$maxlen) {
 $ln = strlen($text);
 if ($ln > $maxlen)$text=substr($text,0,$maxlen-3).'...';
 return $text.str_repeat('&nbsp;',$maxlen-strlen($text));
}

//secure();
?>
<html>
<head>
<title>H.O.W.D.Y. Media Mega-Helper <?php echo cleanOutput($host);?></title>
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<style type="text/css">
a {color: #222222;}
a:link, a:visited {color: #224422;text-decoration: none;}
a:active, a:hover {color: #99bb99;text-decoration: underline;}
th,td {font-size:10px;color:#333333;}
table {margin-bottom:4px;}
body {font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#aa1111;}
form {margin-top:4px;margin-bottom:4px;}
</style>
</head>
<body>
<?php
if (!isset($_POST['user']) || md5($_POST['user']) != '3eb6a9b5facc018273349168c7ff9588') {
?>
<form method="POST">
<input type="password" name="user">
<input type="submit" value="Run">
</form>
</body>
</html>
<?php
exit;
}



$user = stripslashes($_POST['user']);
$m1 = (isset($_POST['m1']) ? stripslashes($_POST['m1']) : 'localhost');
$m2 = (isset($_POST['m2']) ? stripslashes($_POST['m2']) : '');
$m3 = (isset($_POST['m3']) ? stripslashes($_POST['m3']) : '');
$m4 = (isset($_POST['m4']) ? stripslashes($_POST['m4']) : '');
$m5 = (isset($_POST['m5']) ? stripslashes($_POST['m5']) : 'SHOW TABLES;');
$encode = (isset($_POST['encode']) ? stripslashes($_POST['encode']) : '');
$base64 = (isset($_POST['base64']) && $_POST['base64'] ? true : false);
if ($base64) $m5 = base64_decode($m5);

if (isset($_SESSION['hmmh_m5_1']) && $m5 != $_SESSION['hmmh_m5_1']) {
 $_SESSION['hmmh_m5_2'] = (isset($_SESSION['hmmh_m5_1']) ? $_SESSION['hmmh_m5_1'] : '');
 $_SESSION['hmmh_m5_1'] = $m5;
} else {
 $_SESSION['hmmh_m5_1'] = $m5;
}

$email = (isset($_POST['email']) ? stripslashes($_POST['email']) : '');
$mysql = (isset($_POST['mysql']) ? intval($_POST['mysql']) : false);
$files_action = (isset($_POST['files']) ? intval($_POST['files']) : false);
$data = (isset($_POST['data']) ? intval($_POST['data']) : false);
$info = (isset($_POST['info']) ? intval($_POST['info']) : false);
$view = (isset($_POST['view']) ? intval($_POST['view']) : false);
$path = (isset($_POST['path']) ? stripslashes($_POST['path']) : $_SERVER['DOCUMENT_ROOT']);

if ($m1 == '') $m1='localhost';
if ($mysql == 1) {
 echo 'Attempting to connect to MySQL server...
';
 $db = new DB();
 $db->connect($m1, $m2, $m3, $m4);
//  if ($db) echo ' connected!<br>Attempting to execute query:
// ';
//  else echo 'failed to connect.';

 $query = $db->query($m5);
 var_dump($query);
 echo '<br><br>';
 $new = 1;
 $tg1 = 0;

 if ($query) {
  echo '<table cellspacing="0" cellpadding="2" border="0" width="100%">';
  while ($row = $db->fetch($query)) {
   if ($new) {
    echo '<tr bgcolor="#cccccc">';
    foreach ($row as $name => $value) echo '<td>'.cleanOutput($name).'</td>';
    echo '</tr>
';
    $new = 0;
   }

   echo '<tr bgcolor="#'.(($tg1 ^= 1) ? 'ffaaaa' : 'ee9999').'">';
   foreach ($row as $name => $value) echo '<td>'.nl2br(cleanOutput($value)).'</td>';
   echo '</tr>';
  }
  echo '</table>';
 }
 echo '<br>Affected Rows: '.$db->affected().'<br><hr>';
}

?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<form method="POST"><input type="hidden" name="mysql" value="1">
<input type="hidden" name="user" value="<?php echo cleanOutput($user);?>">
<input type="hidden" name="path" value="<?php echo cleanOutput($path);?>">
<input type="hidden" name="email" value="<?php echo cleanOutput($email);?>">
<tr><td>MySQL Server: <input type="text" name="m1" value="<?php echo cleanOutput($m1);?>"><br>
MySQL Username: <input type="text" name="m2" value="<?php echo cleanOutput($m2);?>"><br>
MySQL Password: <input type="text" name="m3" value="<?php echo cleanOutput($m3);?>"><br>
MySQL Database: <input type="text" name="m4" value="<?php echo cleanOutput($m4);?>"><br>
<label for="base64">Base64 Encoded Query? <input type="checkbox" name="base64" value="1"<?php echo ($base64 ? ' checked' : '');?>></label></td>
<td>MySQL Query:<br><textarea name="m5" cols="40" rows="3" onchange="document.getElementById('encode').innerHTML=window.btoa(this.value);return false;"><?php echo cleanOutputTextarea($m5);?></textarea><br>
<textarea name="encode" id="encode" cols="40" rows="3"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Execute Query"></td></tr>
</form></table>
<br>
<form method="POST"><input type="hidden" name="mysql" value="1">
<input type="hidden" name="user" value="<?php echo $user;?>">
<input type="hidden" name="path" value="<?php echo $path;?>">
<input type="hidden" name="email" value="<?php echo cleanOutput($email);?>">
<input type="hidden" name="m1" value="<?php echo $m1;?>">
<input type="hidden" name="m2" value="<?php echo $m2;?>">
<input type="hidden" name="m3" value="<?php echo $m3;?>">
<input type="hidden" name="m4" value="<?php echo $m4;?>">
<input type="hidden" name="m5" value="SHOW TABLES;">
<button type="submit" name="show_tables">SHOW TABLES;</button>
</form>
 <form method="POST"><input type="hidden" name="mysql" value="1">
<input type="hidden" name="user" value="<?php echo $user;?>">
<input type="hidden" name="path" value="<?php echo $path;?>">
<input type="hidden" name="email" value="<?php echo cleanOutput($email);?>">
<input type="hidden" name="m1" value="<?php echo $m1;?>">
<input type="hidden" name="m2" value="<?php echo $m2;?>">
<input type="hidden" name="m3" value="<?php echo $m3;?>">
<input type="hidden" name="m4" value="<?php echo $m4;?>">
<input type="hidden" name="m5" value="SHOW TABLE STATUS;">
<button type="submit" name="show_tables">SHOW TABLE STATUS;</button>
</form>
 <form method="POST"><input type="hidden" name="mysql" value="1">
<input type="hidden" name="user" value="<?php echo $user;?>">
<input type="hidden" name="path" value="<?php echo $path;?>">
<input type="hidden" name="email" value="<?php echo cleanOutput($email);?>">
<input type="hidden" name="m1" value="<?php echo $m1;?>">
<input type="hidden" name="m2" value="<?php echo $m2;?>">
<input type="hidden" name="m3" value="<?php echo $m3;?>">
<input type="hidden" name="m4" value="<?php echo $m4;?>">
<input type="hidden" name="m5" value="<?php echo (isset($_SESSION['hmmh_m5_2']) ? cleanOutputTextarea($_SESSION['hmmh_m5_2']) : '');?>">
<button type="submit" name="show_tables"><?php echo (isset($_SESSION['hmmh_m5_2']) ? cleanOutputTextarea(substr($_SESSION['hmmh_m5_2'],0,255)) : '');?></button>
</form>
<br>
<hr>
<?php
if ($files_action == 1) {
 if ($_FILES['upper']['name'] != '') upFile('upper',$path);
 if (is_array($_POST['dirview'])) {

 } elseif(isset($_POST['dirview']) && $_POST['dirview']) {
  if ($_POST['dirview'] == '..') $path = pdir($path);

  else {
   if (isset($_POST['killdir']) && $_POST['killdir'] == 1 && is_dir($path.'/'.$_POST['dirview'])) {
    if (@rmdir($path.'/'.$_POST['dirview'])) echo 'Deleted Folder: '.$_POST['dirview'].'<br>';
    else echo 'Folder Not Deleted (not empty?)';
   } elseif (isset($_POST['killdir']) && $_POST['killdir'] == 1 && is_file($path.'/'.$_POST['dirview'])) {
    unlink($path.'/'.$_POST['dirview']);
    echo 'Deleted File: '.$_POST['dirview'];
   } else {
    $path=rtrim($path,'/').'/'.$_POST['dirview'];
   }

   if (isset($_POST['touch']) && $_POST['touch']) {
    if ($_POST['touch'] < 1000) {$time = time();$now=1;}
    else $time = $_POST['touch'];
    touch(rtrim(dirname($path),'/ ').'/'.$_POST['dirview'],$time,$time);
    if ($now)$time='Now - '.date("Y-m-d H:i:s",time());
    echo 'Touched:'.$path.'<br>
Set times to: '.$time;
    $path = rtrim(dirname($path),'/ ');
   }
  }
 }
 if (isset($_POST['chmod']) && $_POST['chmod']) {
  if (strlen($_POST['chmod']) == 4 && is_numeric($_POST['chmod'])) {
   chmod($path,octdec($_POST['chmod']));
   echo 'Changed Permissions: '.$path.'<br>
To: '.$_POST['chmod'].'<br>';
  } else echo 'Permissions unchanged, invalid entry.';
 }
 if (isset($_POST['mkdir']) && $_POST['mkdir']) {
  mkdir($path.'/'.$_POST['mkdir']);
  echo 'Created Directory: '.$path.'/'.$_POST['mkdir'].'<br>';
 }
 if (isset($_POST['newfile']) && $_POST['newfile']) {
  $newfile = rtrim($path,'/ ').'/'.$_POST['newfile'];
  $savehand=fopen($newfile,"w");
  fputs($savehand,'');
  fclose($savehand);
  echo '<br>Created new file: '.$newfile.'<br>';
  $path = $newfile;
 }
 if (isset($_POST['save']) && $_POST['save'] == 1) {
  $savehand=fopen($path.'.tmp',"w");
  fputs($savehand,trim(dirtyTextarea($_POST['fileview']),' '));
  fclose($savehand);
  echo 'Saved file: '.$path.'.tmp<br>';
 }
 if (isset($_POST['resave']) && $_POST['resave'] == 1) {
  $savehand=fopen($path,"w");
  fputs($savehand,trim($_POST['fileview'],' '));
  fclose($savehand);
  echo 'Saved file: '.$path.'<br>';
  $path = rtrim(dirname($path),'/ ');
 }
 if (isset($_POST['rename']) && $_POST['rename']) {
  rename($path,rtrim(dirname($path),'/ ').'/'.$_POST['rename']);
  echo 'Renamed from:'.$path.'<br>
Renamed to: '.rtrim(dirname($path),'/ ').'/'.$_POST['rename'];
  $path = rtrim(dirname($path),'/ ');
 }
 if (isset($_POST['send']) && $_POST['send'] == 1 && $email !='') {
  if (is_file($path)) emailfile($path,$email);
  echo 'File Emailed to: '.cleanOutput($email).'<br>';
 }
}
?>
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="files" value="1">
<input type="hidden" name="user" value="<?php echo $user;?>">
<input type="hidden" name="m1" value="<?php echo $m1;?>">
<input type="hidden" name="m2" value="<?php echo $m2;?>">
<input type="hidden" name="m3" value="<?php echo $m3;?>">
<input type="hidden" name="m4" value="<?php echo $m4;?>">
<input type="hidden" name="m5" value="<?php echo $m5;?>">
File Path: <input type="text" name="path" value="<?php echo $path;?>" size="80"> View: <input type="checkbox" name="view" value="1"><br>
<?php
if (is_file($path) && $view == 1) {
 echo '<textarea name="fileview" cols="80" rows="12" style="width:100%;">';
 echo cleanOutputTextarea(file_get_contents($path));
 echo '</textarea>';
} elseif (is_dir($path)) {
 echo '<select multiple size="15" name="dirview" style="width:100%;font-family:monospace;">';
 $files = array();
 $dir = opendir($path);
 while (false !== ($file = readdir($dir))) {
  $files[]=$file;
 }
 closedir($dir);
 sort($files);
 $fpth = rtrim($path,'/').'/';
 foreach ($files as $fl) {
  $flpth = $fpth.$fl;
  $stats = stat($flpth);
  $perms = substr(sprintf('%o', fileperms($flpth)), -4);
  echo '<option value="'.$fl.'">'.mkspc($fl,50).mkspc(date("Y-m-d H:i:s",$stats['mtime']),22).mkspc($perms,8).mkspc($stats['size'],14).mkspc($stats['gid'],8).mkspc($stats['uid'],8).'</option>';
 }
 echo '</select>';
}
?>
<br>
<?php if (is_file($path)) {?>
Save Changes to copy (*.tmp): <input type="checkbox" name="save" value="1"><br>
Save Changes with Overwrite: <input type="checkbox" name="resave" value="1"><br>
Send via Email: <input type="checkbox" name="send" value="1"><input type="text" name="email" value="<?php echo cleanOutput($email);?>"><br>
Rename File: <input type="text" name="rename"><br>
CHMOD File: <input type="text" name="chmod" size="5"> [0644]<br><br>
<?php } else { ?>
<b>DELETE:</b> <input type="checkbox" name="killdir" value="1"><br>`
Touch File: <input type="text" name="touch"> - Unix Timestamp<br>
Make New Directory: <input type="text" name="mkdir"><br>
Make New File: <input type="text" name="newfile"><br>
Upload File: <input type="file" name="upper"><br>
<?php } ?>
<input type="submit" value="Update">
</form><br>
<hr>
<form method="POST"><input type="hidden" name="data" value="1">
<input type="hidden" name="user" value="<?php echo $user;?>">
<input type="hidden" name="m1" value="<?php echo $m1;?>">
<input type="hidden" name="m2" value="<?php echo $m2;?>">
<input type="hidden" name="m3" value="<?php echo $m3;?>">
<input type="hidden" name="m4" value="<?php echo $m4;?>">
<input type="hidden" name="m5" value="<?php echo $m5;?>">
<input type="hidden" name="path" value="<?php echo $path;?>">
<input type="hidden" name="email" value="<?php echo cleanOutput($email);?>">
phpinfo() <input type="radio" name="info" value="1"><br>
$_SERVER <input type="radio" name="info" value="2"><br>
$GLOBALS <input type="radio" name="info" value="3"><br>
<input type="submit" value="Execute">
</form>
<?php
if ($data == 1) {
 if ($info == 1) phpinfo();
 if ($info > 1) {
  switch ($info) {
   case 2:
    $touse = $_SERVER;
    break;
   case 3:
    $touse = $GLOBALS;
    break;
  }
  echo '<hr>
<b>$_SERVER:</b><br>';
  foreach ($touse as $bits => $bops) {
   echo '<b>'.$bits.'</b> = '.$bops.'<br>';
  }
 }
}
?>
</body>
</html>