<?php

namespace PPS\models;
use \DateTime;

class Comment {
    public DateTime $date;

    public function __construct(
        public int $author,
        public string $comment,
        public float $note,
        string $date,
    ) {
        $this->date = new DateTime($date);
    }
}