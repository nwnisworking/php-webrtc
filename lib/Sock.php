<?php
class Sock{
  private Socket $socket;

  private Address $address;

  public function __construct(Address $address, string $type){
    $this->address = $address;
    $this->socket = socket_create(
      $address->isIPV4() ? AF_INET : AF_INET6, 
      $type === 'udp' ? SOCK_DGRAM : SOCK_STREAM,
      $type === 'udp' ? SOL_UDP : SOL_TCP
    );
  }

  public function getAddress(): Address{
    return $this->address;
  }

  public function setReuseAddr(bool $value): self{
    socket_setopt($this->socket, SOL_SOCKET, SO_REUSEADDR, $value);

    return $this;
  }

  public function setBlock(?bool $value = null): self{
    if($value)
      socket_set_block($this->socket);
    else
      socket_set_nonblock($this->socket);

    return $this;
  }

  public function bind(): self{
    socket_bind($this->socket, $this->address->ip, $this->address->port);
    socket_getsockname($this->socket, $addr, $port);
    $this->address->ip = $addr;
    $this->address->port = $port;
    return $this;
  }

  public function listen(): self{
    socket_listen($this->socket);

    return $this;
  }

  public function close(): void{
    socket_close($this->socket);
  }

  public function toSocket(): Socket{
    return $this->socket;
  }

  public function __destruct(){
    $this->close();
  }

  public function read(?Address &$addr = null): ?string{
    @socket_recvfrom($this->socket, $data, 1024 * 2, 0, $address, $port);

    if(!$data)
      return null;

    $addr = new Address($address, $port);
    return $data;
  }

  public function send(string $data, Address $addr): int|bool{
    return socket_sendto($this->socket, $data, strlen($data), 0, $addr->ip, $addr->port);
  }

}