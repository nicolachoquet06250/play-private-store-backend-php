<?php
namespace PPS\enums;

enum ChannelType: string {
    case ASK = 'ask';
    case GIVE = 'give';
    case RECEIVED = 'received';

    public static function getLabel(self $value): string {
        return match($value) {
            ChannelType::ASK => 'ask',
            ChannelType::GIVE => 'give',
            ChannelType::RECEIVED => 'received',
            default => null
        };
    }

    public static function fromString(string $value) {
        return match($value) {
            'ask' => ChannelType::ASK,
            'give' => ChannelType::GIVE,
            'received' => ChannelType::RECEIVED,
            default => null
        };
    }
};