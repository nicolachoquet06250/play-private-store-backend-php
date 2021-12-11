<?php

namespace PPS\app;

use PPS\enums\ChannelType;
use \Psr\Http\Message\{
    ServerRequestInterface as Request
};

class No {
    private ?string $socket = null;
    
    public function setRequest(Request $request): void {
        $query = $request->getQueryParams();
        $this->socket = in_array('socket', array_keys($query)) ? $query['socket'] : null;
    }

    public function tify(string $channel, ChannelType $type, array $message): void {
        if (!is_null($this->socket)) {
            (new Websocket(static::$socket))->send(
                channel: $channel, 
                type: $type, 
                data: $message
            );
        }
    }

    public function hasSocket(): bool {
        return is_null($this->socket);
    }
}