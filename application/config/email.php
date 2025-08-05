<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
*/

$config['protocol']  = 'smtp';
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_user'] = 'jiwanbayalkoti@gmail.com'; 
$config['smtp_auth'] = TRUE;
$config['smtp_crypto'] = 'tls'; 
$config['mailtype']  = 'text';
$config['charset']   = 'utf-8';
$config['newline']   = "\r\n";
$config['smtp_timeout'] = 30;
$config['smtp_debug'] = 2;

/*
|--------------------------------------------------------------------------
| Additional Email Settings
|--------------------------------------------------------------------------
|
| You can add more email configuration options here as needed.
|
*/

$config['wordwrap'] = TRUE;
$config['wrapchars'] = 76;
$config['priority'] = 3; 
$config['bcc_batch_mode'] = FALSE;
$config['bcc_batch_size'] = 200; 