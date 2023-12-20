<?php
require_once("./Blockchain.php");
require_once("./Block.php");


$myBlockchain = new Blockchain();
$myBlockchain->addBlock(new Block(1, '10/03/2023', ['amount' => 50]));
$myBlockchain->addBlock(new Block(2, '15/03/2023', ['amount' => 100]));

// var_dump($myBlockchain->allChain());
echo 'Is blockchain valid? ' . ($myBlockchain->isChainValid() ? 'Yes' : 'No') . "\n ". PHP_EOL.'</br>';

function displayBlockchain(Blockchain $blockchain): void
{
    foreach ($blockchain->chain as $block) {
        echo "Index: " . $block->index . "\n" .'</br>';
        echo "Timestamp: " . $block->timestamp . "\n" .'</br>';
        echo "Data: " . json_encode($block->data) . "\n" .'</br>';
        echo "Previous Hash: " . $block->previousHash . "\n" .'</br>';
        echo "Hash: " . $block->hash . "\n\n" .'</br></br>';
    }
}

displayBlockchain($myBlockchain);

// Tamper with the second block's data
$myBlockchain->chain[1]->data = ['amount' => 75];

// Check if the blockchain is still valid
echo "Is blockchain valid after tampering? " . ($myBlockchain->isChainValid() ? "Yes" : "No") . "\n";
