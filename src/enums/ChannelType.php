<?php
namespace PPS\enums;

enum ChannelType: string {
    case ASK = 'ask';
    case GIVE = 'give';
    case RECEIVED = 'received';
};