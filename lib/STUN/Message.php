<?php
namespace STUN;

use STUN\Message\Attr;
use STUN\Message\Method;
use STUN\Message\Type;

class Message{
  private int $type;

  private int $length = 0;

  private string $cookie;

  private string $id;

  /**
   * @var Attribute[]
   */
  private array $attributes = [];

  public function __construct(?string $data = null){
    if(!$data) return;

    [
      'type'=>$type,
      'length'=>$length,
      'cookie'=>$cookie,
      'id'=>$id,
      'attributes'=>$attributes
    ] = unpack('ntype/nlength/a4cookie/a12id/a*attributes', $data);

    $this->type = $type;
    $this->length = $length;
    $this->cookie = $cookie;
    $this->id = $id;

    $i = 0;

    while($i < strlen($attributes)){
      $size = ord($attributes[2 + $i]) << 8 | ord($attributes[3 + $i]) + 4;
      $this->attributes[]= new Attribute(substr($attributes, $i, $size));

      while($size % 4 !== 0)
        $size++;

      $i+= $size;
    }
  }

  public function getId(): string{
    return $this->id;
  }

  public function getLength(): int{
    return $this->length;
  }

  public function getCookie(): string{
    return $this->cookie;
  }

  public function setClass(Type $type): self{
    $type = $type->value;
    $this->type&= 0xfeef;
    $this->type|= $type >> 1 << 8;
    $this->type|= ($type & 1) << 4;
    return $this;
  }
  
  public function setMethod(Method $method): self{
    $method = $method->value;
    $this->type = $this->type & 0x110;
    $this->type|= $method & 0xf;
    $this->type|= (($method >> 4) & 0x7) << 5;
    $this->type|= (($method >> 7) & 0x1f) << 9;

    return $this;
  }

  public function getClass(): Type{
    return Type::tryFrom(($this->type >> 4) & 0x1 | (($this->type >> 8) & 0x1) << 1);
  }

  public function getMethod(): Method{
    return Method::tryFrom(
      $this->type & 0xf | 
      ($this->type >> 5) & 0x7 | 
      ($this->type >> 9) & 0x7 | 
      ($this->type >> 12) & 0x3  
    );
  }

  public function addAttribute(Attribute ...$attrs): self{
    array_push($this->attributes, ...$attrs);
    $this->length+= strlen(join('', $attrs));

    return $this;
  }

  public function removeAttributes(): self{
    $this->attributes = [];
    $this->length = 0;

    return $this;
  }

  /**
   * @return Attribute[]
   */
  public function getAttributes(): array{
    return $this->attributes;
  }

  public function getAttribute(int|Attr $type): ?Attribute{
    if(is_int($type))
      return $this->attributes[$type];

    foreach($this->attributes as $v)
      if($v->getType() === $type)
        return $v;

    return null;
  }

  public function __tostring(){
    return pack('nna*', $this->type, $this->length, $this->cookie.$this->id.join('', $this->attributes));
  }
}