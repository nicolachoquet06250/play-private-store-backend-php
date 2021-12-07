<?php

namespace PPS\app;

use \PPS\enums\ChannelType;

class Websocket {
    public function __construct(
        private string $socket
    ) {}

    public function send(string $channel, ChannelType $type, array $data) {
        \Ratchet\Client\connect($this->socket)
            ->then(function($conn) use($channel, $type, $data) {
                $conn->send(\json_encode([
                    'channel' => $channel,
                    'type' => $type,
                    'data' => $data
                ]));

                $conn->close();
            }, function ($e) {
                throw new \Exception("Could not connect: {$e->getMessage()}");
            });
    }
}