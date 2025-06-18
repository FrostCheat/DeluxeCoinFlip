<?php

namespace frostcheat\deluxecoinflip\cf;

use pocketmine\item\Item;

class CoinFlip {

    private string $player;
    private int $amount;
    private Item $item;

    public function __construct(string $player, int $amount, Item $item) {
        $this->player = $player;
        $this->amount = $amount;
        $this->item = $item;
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getAmount(): int {
        return $this->amount;
    }

    public function getItem(): Item {
        return $this->item;
    }
}