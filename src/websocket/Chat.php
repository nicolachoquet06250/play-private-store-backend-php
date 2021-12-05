<?php
namespace PPS\websocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use \SplObjectStorage;
use PPS\enums\ChannelType;

class Chat implements MessageComponentInterface {
    protected SplObjectStorage $clients;
    protected array $users = [];
    protected ?ConnectionInterface $from = null;

    public function __construct() {
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        $id = $conn->resourceId;

        $this->from = $conn;
        $this->sendAll([
            'message' => "Connected {$id}"
        ]);

        $this->send([
            'channel' => 'identity',
            'type' => 'ask',
            'data' => [
                'id' => $id
            ]
        ]);

        echo "New connection! ({$id})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $iNumRecv = count($this->clients) - 1;
        $numRecv = $iNumRecv === 1 ? '' : 's';

        $data = json_decode($msg, true);
        ['channel' => $channel, 'type' => $type, 'data' => $data] = $data; 

        if (in_array("{$type}_{$channel}", get_class_methods($this::class))) {
            $this->{"{$type}_{$channel}"}($channel, $type, $data, $from);
        } else {
            echo "Connection {$from->resourceId} sending message \"{$msg}\" to {$iNumRecv} other connection{$numRecv}\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, 
        // as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function send(string|array $msg) {
        if (is_array($msg)) {
            $this->from->send(json_encode($msg));
        } else {
            $this->from->send($msg);
        }
    }

    public function broadcast(string|array $msg) {
        foreach ($this->clients as $client) {
            if ($this->from !== $client) {
                // The sender is not the receiver, 
                // send to each client connected
                if (is_array($msg)) {
                    $client->send(json_encode($msg));
                } else {
                    $client->send($msg);
                }
            }
        }
    }

    public function sendAll(string|array $msg) {
        foreach ($this->clients as $client) {
            if (is_array($msg)) {
                $client->send(json_encode($msg));
            } else {
                $client->send($msg);
            }
        }
    }

    public function give_identity(string $channel, string $type, array $data, \Ratchet\WebSocket\WsConnection $from) {
       [ 'user' => $user, 'id' => $id ] = $data;
       
       if (!$this->users[$from->resourceId]) {
            $this->users[$from->resourceId] = [
                'user' => $user,
                'socket' => $from
            ];
       }

       dump($user, $from->resourceId);

       $this->from = $from;
       $this->send([
           'channel' => $channel,
           'type' => ChannelType::RECEIVED
       ]);
    }

    public function give_notify(string $channel, string $type, array $data, \Ratchet\WebSocket\WsConnection $from) {
       [ 'appId' => $appId ] = $data;
       
       $this->from = $from;
       $this->broadcast([
           'channel' => $channel,
           'data' => [
               'appId' => $appId
           ]
       ]);
    }
}