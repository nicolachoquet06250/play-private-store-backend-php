<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute]
class ApplyMethodAfterinstanciate {

    const BODY = [
        'method' => 'getParsedBody',
        'target' => ['this', 'request']
    ];

    const NOTIFIER = [
        'method' => 'setRequest',
        '_this' => ['request']
    ];

    public function __construct(
        public ?array $type = null,
        public ?string $method = null,
        public ?array $data = null,
        public ?array $_this = null,
        public array $target = []
    ) {
        if (!empty($this->type)) {
            foreach ($this->type as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }
}