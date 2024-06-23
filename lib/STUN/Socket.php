<?php
namespace STUN;
use Address;
use Sock;

class Socket{
  public Sock $sock;

  public ?int $expires;

  public ?Address $user_address;

  public ?self $pair = null;
  
  public function __construct(Address $sock, ?Address $user_address = null, ?int $expires = null){
    $this->user_address = $user_address;
    $this->expires = $expires;
    $this->sock = new Sock($sock, 'udp');
    
    $this->sock
    ->setReuseAddr(true)
    ->setBlock()
    ->bind();    
  }

  public function relayAddress(): Address{
    return $this->sock->getAddress();
  }

  public function read(?Address &$address = null){
    return $this->sock->read($address);
  }

  public function send(string $data, Address $address){
    return $this->sock->send($data, $address);
  }
}