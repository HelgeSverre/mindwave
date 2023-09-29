<?php

namespace Mindwave\Mindwave\Message;

enum Role: string
{
    case system = 'system';
    case user = 'user';
    case ai = 'ai';
    case function = 'function';
}
