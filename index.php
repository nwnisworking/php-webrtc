<?php
use STUN\Message;
use STUN\Message\Attr;
use STUN\Message\Method;
use STUN\Message\Type;
use STUN\Server;
use STUN\Attribute;

define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', __DIR__);
define('LIB_PATH', BASE_PATH.DS.'lib');

require_once LIB_PATH.DS.'Autoload.php';

Autoload::load(LIB_PATH);
spl_autoload_register([Autoload::class, 'register']);

$i = -1;

function logger(string $text){
  global $log;

  if($log === $text)
    return;

  $log = $text;

  echo $text.PHP_EOL;
}

$server = new Server(new Address('127.0.0.1', 9000));

while(1){
  $i++;
  $i%= count($server->sockets);

  $socket = $server->sockets[$i];

  if(
    empty($data = $socket->read($addr)) || 
    ($msg = new Message($data)) && $msg->getClass() === Type::RESPONSE
  )
    continue;

  switch($msg->getMethod()){
    case Method::BINDING : 
      $reply = clone $msg;
      $reply
      ->setClass(Type::RESPONSE)
      ->removeAttributes()
      ->addAttribute(Attribute::XORMappedAddress($addr, $reply));

      if($msg->getAttribute(Attr::USERNAME)){
        $reply->addAttribute(Attribute::MessageIntegrity(Message::integrity('password', $reply)));
        $reply->addAttribute(Attribute::Fingerprint(Message::fingerprint($reply)));

        $p_addr = $server->find($addr);
        $msg = new Message;
        $msg
        ->setClass(Type::INDICATION)
        ->setMethod(Method::DATA);

        # send to socket  
        $msg->addAttribute(
          Attribute::XORPeerAddress($socket->pair->relayAddress(), $msg),
          Attribute::Data($reply)
        );

        $socket->send($reply, $addr);
        $server->sockets[0]->send($msg, $socket->user_address);
        logger($socket->user_address);
      }
      else
        $socket->send($reply, $addr);
      break;
    case Method::ALLOCATE : 
      $reply = clone $msg;
      $relay = $server->allocate($addr);

      $reply
      ->setClass(Type::RESPONSE)
      ->removeAttributes()
      ->addAttribute(
        Attribute::XORRelayedAddress($relay->sock->getAddress(), $reply),
        Attribute::Lifetime($relay->expires),
        Attribute::Software('php-webrtc'),
        Attribute::XORMappedAddress($addr, $reply)
      );

      logger('[Allocate] '.$relay->sock->getAddress().'[R] allocated to '.$relay->user_address.'[M]');

      $socket->send($reply, $addr);
      break;
    case Method::CREATE_PERMISSION : 
      $reply = clone $msg;
      $reply
      ->setClass(Type::RESPONSE)
      ->removeAttributes()
      ->addAttribute(Attribute::Software('php-webrtc'));

      $remote = $server->find($msg->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($msg));
      $local = $server->find($addr);

      $remote = $remote->current();
      $local = $local->current();
      $remote->pair = $local;
      $local->pair = $remote;

      logger('[Permission] '.$local->sock->getAddress() .'[R] Paired with '.$remote->sock->getAddress().'[R]');

      $socket->send($reply, $addr);
      break;

    case Method::SEND : 
      $remote = $server->find($msg->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($msg));
      $data = $msg->getAttribute(Attr::DATA)->getData($msg);

      $remote = $remote->current();

      $remote->pair->send($data, $remote->relayAddress());

      logger('[Send] '.$addr.'[M] sent to '.$remote->user_address.'[M]');
      break;
    case Method::DATA : 
      var_dump($msg);
      break;
  }

}

//   switch($msg->getMethod()){
//     case Method::BINDING : 
//       $reply = clone $msg;

//       if($msg->getAttribute(Attr::USERNAME)){
//         $reply->addAttribute(Attribute::MessageIntegrity(Message::integrity('password', $reply)));
//         $reply->addAttribute(Attribute::Fingerprint(Message::fingerprint($reply)));
//         $sock = $sockets[find($addr, true)];

//         $msg = new Message;
//         $msg
//         ->setClass(Type::INDICATION)
//         ->setMethod(Method::DATA);

//         logger("[Bind] Data sent from $user_address to $sock[user_address]");
//       }

//       $socket->send($reply, $addr);

//       break;
//     #region alloc
//     case Method::ALLOCATE : 
//       $reply = clone $msg;
//       $relay = new Sock(new Address($addr->ip), 'udp');
      
//       $relay
//       ->setReuseAddr(true)
//       ->setBlock()
//       ->bind();

//       $reply
//       ->setClass(Type::RESPONSE)
//       ->removeAttributes()
//       ->addAttribute(
//         Attribute::XORRelayedAddress($relay->getAddress(), $reply),
//         Attribute::Lifetime(600),
//         Attribute::Software('php-webrtc'),
//         Attribute::XORMappedAddress($addr, $reply)
//       );

//       $sockets[] = [
//         'socket'=>$relay,
//         'user_address'=>$addr,
//         'expires'=>time() + 600,
//         'pair'=>null
//       ];

//       logger("[Allocate] ".$relay->getAddress()."[R] allocated to $addr");
//       $socket->send($reply, $addr);

//       break;
//       #endregion alloc

//       case Method::CREATE_PERMISSION : 
//         $reply = clone $msg;
//         $reply
//         ->removeAttributes()
//         ->addAttribute(Attribute::Software('php-webrtc'));

//         $peer_addr = $msg->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($msg);
//         $remote = find($peer_addr['address']);
//         $local = find($addr);
//         var_dump($remote, $local);

//         $sockets[$local]['pair'] = [...$sockets[$remote]];
//         $sockets[$remote]['pair'] = [...$sockets[$local]];


//         // logger("[Permission] Peer: ".$local['socket']->getAddress().'[R] paired with '.$peer['socket']->getAddress().'[R]');

//         break;
//       case Method::SEND : 
//         $sock = find($addr);
//         $data = $msg->getAttribute(Attr::DATA)->getData($msg);
//         $peer = $msg->getAttribute(Attr::XOR_PEER_ADDRESS)->getData($msg)['address'];


//         // if($log !== "[Send] Data sent from $addr to ".$sock['pair']['user_address']." PEER $peer"){
//         //   echo $log = "[Send] Data sent from $addr to ".$sock['pair']['user_address']." PEER $peer";
//         //   echo PHP_EOL;
//         // }

//         // $sock['socket']->send($data, $sock['pair']['socket']->getAddress());
//         break;
//   }
// }
