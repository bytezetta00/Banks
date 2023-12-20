<?php

class Blockchain
{
    public array $chain;

    public function __construct()
    {
        $this->chain = [$this->createGenesisBlock()];
    }

    private function createGenesisBlock(): Block
    {
        return new Block(0, '01/01/2023', 'Genesis Block', '0');
    }

    public function getLatestBlock(): Block
    {
        return $this->chain[count($this->chain) - 1];
    }

    public function addBlock(Block $newBlock): void
    {
        $newBlock->previousHash = $this->getLatestBlock()->hash;
        $newBlock->hash = $newBlock->calculateHash();
        $this->chain[] = $newBlock;
    }

    public function isChainValid(): bool
    {
        for ($i = 1, $chainLength = count($this->chain); $i < $chainLength; $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];

            if ($currentBlock->hash !== $currentBlock->calculateHash()) {
                return false;
            }

            if ($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }
        }

        return true;
    }

    public function allChain()
    {
        return $this->chain;
    }
}