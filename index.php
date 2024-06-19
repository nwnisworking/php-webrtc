<?php
use STUN\Message;
use STUN\Message\Attr;
use STUN\Message\Method;
use STUN\Message\Type;
use STUN\Attribute;

function generateEphemeralPort() {
  $minEphemeralPort = 49152;
  $maxEphemeralPort = 65535;

  return rand($minEphemeralPort, $maxEphemeralPort);
} 

define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', __DIR__);
define('LIB_PATH', BASE_PATH.DS.'lib');

require_once LIB_PATH.DS.'Autoload.php';

Autoload::load(LIB_PATH);
spl_autoload_register([Autoload::class, 'register']);

$master = new Sock(new Address('127.0.0.1', 9000), 'udp');
$master
->setReuseAddr(true)
->setBlock()
->bind();

$sockets = [];

$sockets[] = [
  'socket'=>$master,
  'user_address'=>null,
  'expires'=>null
];

$i = 0;
function &s_log(string $str){
  static $logs = [];

  if(isset($logs[0]) && $logs[array_key_last($logs)] === $str)
    return $logs;

  $logs[] = $str;

  echo $str.PHP_EOL;
  return $logs;
}
while(1){
  $i++;
  $i%= count($sockets);

  [
    'socket'=>$socket,
    'user_address'=>$user_address,
    'expires'=>$expires
  ] = $sockets[$i];

  s_log('Total sockets: '.count($sockets));

  if(!is_null($expires) && time() > $expires){
    array_splice($sockets, $i, 1);
    $i--;
    continue;
  }

  if(!$socket || empty($data = $socket->read($addr)))
    continue;

  $msg = new Message($data);
  $res = clone $msg;
  $res->setClass(Type::RESPONSE);

  switch($msg->getMethod()){
    case Method::BINDING : 
      if($res->getAttribute(Attr::XOR_MAPPED_ADDRESS)){
        $res->removeAttributes();
      
        $res->addAttribute(
          // Attribute::Software(),
        );
      }

      
      $res->addAttribute(Attribute::XORMappedAddress($addr, $res));

      break;
    case Method::ALLOCATE : 
      $res->removeAttributes();
      $relay = new Sock(new Address($addr->ip), 'udp');
      $relay->setReuseAddr(true)->setBlock()->bind();

      $res->addAttribute(
        Attribute::XORRelayedAddress($relay->getAddress(), $res),
        Attribute::Lifetime(600),
        Attribute::XORMappedAddress($addr, $res),
        Attribute::Software('php-webrtc')
      );

      $sockets[] = [
        'socket'=>$relay,
        'user_address'=>$addr,
        'expires'=>time() + 600
      ];
      break;
    case Method::REFRESH : 
      $res->removeAttributes();

      s_log('refresh');

      foreach($sockets as &$meta){
        if($meta['user_address'] == $addr)
          $meta['expires'] = 0;
      }
      break;
    case Method::CREATE_PERMISSION : 
      $res->removeAttributes();
      $res->addAttribute(Attribute::Software('php-webrtc'));
      break;
    case Method::SEND : 
      $peer = $res->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($res);
      $res = $res->getAttribute(Attr::DATA)->getData($res);

      foreach($sockets as $sock){
        if($sock['user_address'] == $addr){
          $socket = $sock['socket'];
          $addr = $peer['address'];
          break;
        }
      }

      break;
  }

  $socket->send($res, $addr);
}