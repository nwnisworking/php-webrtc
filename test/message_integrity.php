<?php
use STUN\Message\Attr;
use STUN\Message;

require_once "./lib/Autoload.php";
Autoload::load("./lib");
spl_autoload_register([Autoload::class, 'register']);

// $str = "000800742112a4425448484e646c726f44507647001200080001c0da5e12a4430006000c6e776e6973776f726b696e670014001561746c616e7469732d736f6674776172652e6e6574000000001500206132613531616363326164396238346138343165386661613134373164316562000800143a8d2364fff92849777641d7a42a8bccb1059616";

// $msg = new Message(hex2bin($str));
// $test = clone $msg;

// $test->removeAttributes();
// foreach($msg->getAttributes() as $attr){
//   if($attr->getType() === Attr::MESSAGE_INTEGRITY) break;

//   $test->addAttribute($attr);
// }
// $test = (string) $test;
// $test[3] = chr(ord($test[3]) + 24);
// $test = new Message($test);
// var_dump($test);
// var_dump(hash_hmac('sha1', new Message($test), md5('nwnisworking:atlantis-software.net:password', true)));
// $ans = '5027cff38537579decfdcfb7b6c300aa54fb8e1f';

$str = "010800282112a4425448484e646c726f44507647802200096e6f64652d7475726e00000000080014cf10ff8faa7727c7bafd61ae9dbb3f908411d60c";
$msg = new Message(hex2bin($str));
$test = clone $msg;

$test->removeAttributes();
foreach($msg->getAttributes() as $attr){
  if($attr->getType() === Attr::MESSAGE_INTEGRITY) break;

  $test->addAttribute($attr);
}

$test = (string) $test;
$test[3] = chr(ord($test[3]) + 24);
$test = new Message($test);
var_dump(bin2hex($msg->getAttribute(Attr::MESSAGE_INTEGRITY)->getData()));
var_dump(hash_hmac('sha1', new Message($test), md5('nwnisworking:atlantis-software.net:password', true)));
