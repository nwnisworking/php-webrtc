<?php
namespace STUN;

use Address;
use STUN\Message\Attr;

readonly class Attribute{
  private int $type;

  private int $length;

  private string $raw_data;

  public function __construct(?string $attribute = null){
    if(!$attribute) return;

    [
      'type'=>$type,
      'length'=>$length,
      'data'=>$data
    ] = unpack('ntype/nlength/a*data', $attribute);

    $this->type = $type;
    $this->length = $length;
    $this->raw_data = $data;
  }

  public function getType(): ?Attr{
    return Attr::tryFrom($this->type);
  }

  public function getRaw(): string{
    return $this->raw_data;
  }

  public function getLength(): int{
    return strlen($this);
  }

  public function setType(Attr $type): self{
    $this->type = $type->value;
    return $this;
  }

  public function setRaw(string $data): self{
    $this->raw_data = $data;
    $this->length = strlen($data);
    return $this;
  }

  public function getData(?Message $msg = null): mixed{
    switch($this->getType()){
      case Attr::REQUESTED_TRANSPORT : 
        return $this->raw_data[0];

      case Attr::XOR_MAPPED_ADDRESS : 
      case Attr::XOR_RELAYED_ADDRESS : 
      case Attr::XOR_PEER_ADDRESS : 
        [
          'protocol'=>$protocol,
          'port'=>$port,
          'ip'=>$ip
        ] = unpack('a_/Cprotocol/a2port/a*ip', $this->raw_data);

        $mask = $msg->getCookie().$msg->getId();
        $ip^= $mask;
        $port^= $mask;

        return [
          'protocol'=>$protocol, 
          'address'=>new Address(inet_ntop($ip), ord($port[0]) << 8 | ord($port[1]))
        ];
      case Attr::DATA : 
        return new Message($this->raw_data);

      case Attr::GOOG_NETWORK_INFO : 
        return unpack('ng_network_Id/ng_network_cost', $this->raw_data);
      
      // case Attr::USERNAME : 
      // case Attr::REALM : 
      // case Attr::MESSAGE_INTEGRITY : 
      // case Attr::FINGERPRINT : 
      // case Attr::NONCE : 
      // case Attr::SOFTWARE : 
      // case Attr::ICE_CONTROLLED : 
      //   return $this->raw_data;

      case Attr::PRIORITY : 
        return unpack('N', $this->raw_data)[1];

      default : 
        return $this->raw_data;
    }
  }

  public static function XORMappedAddress(Address $address, Message $message): self{
    $data = self::XORAddress($address, $message);
    
    return new self(pack('nna*', Attr::XOR_MAPPED_ADDRESS->value, strlen($data), $data));
  }

  public static function XORRelayedAddress(Address $address, Message $message): self{
    $data = self::XORAddress($address, $message);
    
    return new self(pack('nna*', Attr::XOR_RELAYED_ADDRESS->value, strlen($data), $data));
  }

  public static function XORPeerAddress(Address $address, Message $message): self{
    $data = self::XORAddress($address, $message);
    
    return new self(pack('nna*', Attr::XOR_PEER_ADDRESS->value, strlen($data), $data));
  }

  public static function MappedAddress(Address $address): self{
    $data = chr(0);
    $data.= chr($address->isIPV4() ? 1 : 2);
    $data.= chr($address->port >> 8 & 0xff).chr($address->port & 0xff);
    $data.= inet_pton($address->ip);

    return new self(pack('nna*', Attr::MAPPED_ADDRESS->value, strlen($data), $data));
  }

  public static function Lifetime(int $lifetime): self{
    return new self(pack('nnN', Attr::LIFETIME->value, 4, $lifetime));
  }

  public static function Software(string $name): self{
    return new self(pack('nna*', Attr::SOFTWARE->value, strlen($name), $name));
  }

  public static function MessageIntegrity(string $integrity): self{
    return new self(pack('nna*', Attr::MESSAGE_INTEGRITY->value, strlen($integrity), $integrity));
  }

  public static function Fingerprint(string $fingerprint): self{
    return new self(pack('nna*', Attr::FINGERPRINT->value, strlen($fingerprint), $fingerprint));
  }

  private static function XORAddress(Address $address, Message $message): string{
    $mask = $message->getCookie().$message->getId();
    $data = chr(0);
    $data.= chr($address->isIPV4() ? 1 : 2);
    $data.= chr($address->port >> 8 & 0xff).chr($address->port & 0xff) ^ $mask;
    $data.= inet_pton($address->ip) ^ $mask;

    return $data;
  }

  private function pad(string $str): string{
    while(strlen($str) % 4 !== 0)
      $str.= chr(0);

    return $str;
  }

  public function __toString(){
    $data = pack('nna*', $this->type, $this->length, $this->pad($this->raw_data));

    return $data;
  }
}