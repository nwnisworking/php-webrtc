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
  'expires'=>null, 
  'client_addr'=>null, 
  'server_addr'=>$master->getAddress()
];

// function listening(){
//   global $sockets;

//   return array_map(fn($e)=>"$e[server_addr] > $e[client_addr]", $sockets);
// }

// while(1){
//   $i++;
//   $i%= count($sockets);

//   [
//     'socket'=>$socket,
//     'expires'=>$expires,
//     'client_addr'=>$c_addr,
//     'server_addr'=>$s_addr
//   ] = $sockets[$i];

//   # Lifetime expires through refresh request or actual expiry
//   if(!is_null($expires) && time() > $expires){
//     array_splice($sockets, $i, 1);
//     $i--;
//     continue;
//   }

//   # Nothing to read, continue on
//   if(!$socket || empty($data = $socket->read($addr))){
//     continue;
//   }

//   echo "$s_addr > $addr\n";

//   $msg = new Message($data);
//   $res = clone $msg;
//   $res->setClass(Type::RESPONSE);

//   echo $msg->getMethod()->name."\n\n";

//   switch($msg->getMethod()){
//     case Method::BINDING : 
//       $username = $res->getAttributes(Attr::USERNAME);
//       $fingerprint = $res->getAttributes(Attr::FINGERPRINT)?->getData($res)['data'];
//       $msg_int = $res->getAttributes(Attr::MESSAGE_INTEGRITY)?->getData($res);
//       $res->removeAttributes();

//       $res->addAttribute(Attribute::XORMappedAddress($addr, $res));

//       if($msg_int){
//         $md5 = md5("$username::password");
//         $prev_len = $msg_int['length'];
        
//       }      
//     break;
//     case Method::ALLOCATE : 
//       $res->removeAttributes();
//       $relay = new Sock(new Address($addr->ip), 'udp');
//       $relay
//       ->setReuseAddr(true)
//       ->setBlock()
//       ->bind();

//       $res->addAttribute(
//         Attribute::XORRelayedAddress($relay->getAddress(), $res),
//         Attribute::Lifetime(600),
//         Attribute::XORMappedAddress($addr, $res),
//         Attribute::Software('php-webrtc')
//       );

//       $sockets[] = [
//         'socket'=>$relay,
//         'expires'=>time() + 600,
//         'client_addr'=>$addr,
//         'server_addr'=>$relay->getAddress()
//       ];
//     break;
//     case Method::REFRESH : 
//       $res->removeAttributes();

//       foreach($sockets as &$meta)
//         if($meta['client_addr'] == $addr)
//           $meta['expires'] = 0;

//       continue 2;
//     break;
//     case Method::CREATE_PERMISSION : 
//       $res
//       ->removeAttributes()
//       ->addAttribute(Attribute::Software('php-webrtc'));
//     break;
//     case Method::SEND : 
//       $peer_addr = $res->getAttributes(Attr::XOR_PEER_ADDRESS)->getData($res)['data'];
//       $res_msg = $res->getAttributes(Attr::DATA)->getData($res)['data'];

//       $a = array_filter($sockets, fn($e)=>$e['client_addr'] == $addr);
//       $a = current($a);

//       $a['socket']->send($res_msg, $peer_addr['addr']);

//       continue 2;
//     break;
//   } 

//   $master->send($res, $addr);
//   sleep(1);
// }

$i = 0;
while(1){
  $i++;
  $i%= count($sockets);

  [
    'socket'=>$socket,
    'expires'=>$expires,
    'client_addr'=>$c_addr,
    'server_addr'=>$s_addr
  ] = $sockets[$i];

  # Created sockets and the lifetime has expired
  if(!is_null($expires) && time() > $expires){
    array_splice($sockets, $i, 1);
    $i--;
    continue;
  }

  # No message in socket 
  if(empty($data = $socket->read($addr)))
    continue;

  $msg = new Message($data);
  $res = clone $msg;
  $res->setClass(Type::RESPONSE);

  switch($msg->getMethod()){
    case Method::BINDING : 
      if(!$res->getAttribute(Attr::XOR_MAPPED_ADDRESS)){
        $res->addAttribute(Attribute::XORMappedAddress($addr, $res));
      }
      else{
        $xor_mapped = $res->getAttribute(Attr::XOR_MAPPED_ADDRESS);
        $username = $res->getAttribute(Attr::USERNAME);
        $res->removeAttributes();
        $res->addAttribute($xor_mapped);
        $res->addAttribute(
          Attribute::MessageIntegrity($res->integrity('password', $username->getData($res), '')
        ));
        $res->addAttribute(Attribute::Fingerprint($res->fingerprint()));
        $addr = $xor_mapped->getData($res)['address'];
      }
      break;
    case Method::ALLOCATE : 
      $res->removeAttributes();
      $relay = new Sock(new Address($addr->ip), 'udp');
      $relay
      ->setReuseAddr(true)
      ->setBlock()
      ->bind();

      $res->addAttribute(
        Attribute::XORRelayedAddress($socket->getAddress(), $res),
        Attribute::Lifetime(600),
        Attribute::XORMappedAddress($addr, $res),
        Attribute::Software('php-webrtc')
      );

      $sockets[] = [
        'socket'=>$relay,
        'expires'=>time() + 600,
        'client_addr'=>$addr,
        'server_addr'=>$relay->getAddress()
      ];
      break;
      case Method::REFRESH : 
        $res->removeAttributes();

        foreach($sockets as &$meta)
          if($meta['client_addr'] == $addr)
            $meta['expires'] = 0;

        # Skip switch + while loop
        continue 2;
      case Method::CREATE_PERMISSION : 
        $res->removeAttributes();
        $res->addAttribute(Attribute::Software('php-webrtc'));
        break;
      case Method::SEND : 
        $peer = $res->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($res);
        $msg = $res->getAttribute(Attr::DATA)->getData($res);

        $s = array_filter($sockets, fn($e)=>$e['client_addr'] == $addr);
        $s = current($s);

        $s['socket']->send($msg, $peer['address']);

        continue 2;
  }

  $master->send($res, $addr);
  sleep(1);
}