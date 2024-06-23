<?php
namespace STUN;
use Address;
use EventEmitter;
use Generator;

class Server{
  use EventEmitter;

  public int $lifetime = 600;

  /** @var Socket[] */
  public array $sockets = [];

  public function __construct(Address $address){
    $this->sockets[] = new Socket($address, null);
  }

  public function allocate(Address $address): Socket{
    return $this->sockets[] = new Socket(new Address('127.0.0.1'), $address, $this->lifetime);
  }

  public function find(Address $address): ?Generator{
    foreach($this->sockets as $socket){
      if($socket->user_address == $address || $socket->sock->getAddress() == $address)
        yield $socket;
    }

    return null;
  }
}