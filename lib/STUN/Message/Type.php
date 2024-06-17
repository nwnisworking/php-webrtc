<?php
namespace STUN\Message;

enum Type: int{
  case REQUEST = 0b00;

  case INDICATION = 0b01;

  case RESPONSE = 0b10;

  case ERROR = 0b11;
}