<?php
use STUN\Message;
use STUN\Message\Attr;
require_once "./lib/Autoload.php";
Autoload::load("./lib");
spl_autoload_register([Autoload::class, 'register']);

// $str = "0101002c2112a4424a4b4b4b5a6f793175574665002000080001fffc5e12a44300080014ce136bb265ee6de8bb64dbf97e204ff92220f4cb80280004f82bdd98";
// $str = hex2bin($str);

// var_dump(bin2hex(pack('N', crc32(substr($str, 0, strlen($str) - 8))) ^ 'STUN'));