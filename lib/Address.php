<?php
final class Address{
  public string $ip;
  
  public ?int $port;

  public function __construct(string $ip, ?int $port = null){
    if(!filter_var($ip, FILTER_VALIDATE_IP))
      throw new InvalidArgumentException("IP is not valid");

    $this->ip = $ip;
    $this->port = $port;
  }

  public function isIPV4(): bool{
    return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
  }

  public function isIPV6(): bool{
    return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
  }

  public function __tostring(){
    return "$this->ip:$this->port";
  }
}