<?php


require_once("./blockchain.php");

$blockchain = new Blockchain();
$previous_block = $blockchain->print_previous_block();
$previous_proof = $previous_block["proof"];
$proof = $blockchain->proof_of_work($previous_proof);
$previous_hash = $blockchain->hashBlock($previous_block);
$block = $blockchain->createBlock($proof, $previous_hash);

echo "New Block Has Been Added.";
print_r($block);
