<?php
namespace STUN\Message;

enum Method: int{
  case BINDING = 0x1;

  case ALLOCATE = 0x3;

  case REFRESH = 0x4;

  case SEND = 0x6;

  case DATA = 0x7;

  case CREATE_PERMISSION = 0x8;

  case CHANNEL_BIND = 0x9;

  case CONNECT = 0xA;

  case CONNECTION_BIND = 0xB;

  case CONNECTION_ATTEMPT = 0xC;

  case GOOG_PING = 0x80;
}