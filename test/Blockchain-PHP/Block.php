<?php

class Block
{
    public int $index;
    public string $timestamp;
    public $data;
    public string $previousHash;
    public string $hash;

    public function __construct(int $index, string $timestamp, $data, string $previousHash = '')
    {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
    }

    public function calculateHash(): string
    {
        return hash(
            'sha256', 
            sprintf(
               '%d%s%s%s',
               $this->index,
               $this->timestamp,
               $this->previousHash,
               json_encode($this->data),
           )
        );
    }
}