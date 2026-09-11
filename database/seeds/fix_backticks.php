<?php
$content = file_get_contents($argv[1]);
// Remove backticks from INSERT INTO to avoid MariaDB parse issue
$content = preg_replace('/INSERT INTO `([^`]+)`/', 'INSERT INTO $1', $content);
// Remove backticks from column lists
$content = preg_replace('/`([a-z_]+)`/', '$1', $content);
// Remove backticks from table names in JOIN/DELETE/UPDATE
$content = preg_replace('/DELETE FROM `([^`]+)`/', 'DELETE FROM $1', $content);
$content = preg_replace('/UPDATE `([^`]+)`/', 'UPDATE $1', $content);
file_put_contents($argv[2], $content);
echo "Fixed\n";
