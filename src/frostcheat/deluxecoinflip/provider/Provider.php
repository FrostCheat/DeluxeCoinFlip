<?php

namespace frostcheat\deluxecoinflip\provider;

use frostcheat\deluxecoinflip\cf\CoinFlip;
use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\Loader;
use frostcheat\deluxecoinflip\provider\task\SaveCFAsyncTask;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class Provider {
    use SingletonTrait;

    private Config $coinflipsConfig;

    public function init(): void {
        $this->coinflipsConfig = new Config(Loader::getInstance()->getDataFolder() . "coinflips.yml");
    }
    
    public function getCoinflips(): array {
        $coinflips = [];

        foreach ($this->coinflipsConfig->getAll() as $coinflip) {
            $coinflips[$coinflip['player']] = new CoinFlip($coinflip['player'], (int) $coinflip['amount'], Serialize::deserialize($coinflip['item']));
        }

        return $coinflips;
    }

    public function saveCoinflips(): void {
        $coinflips = [];

        foreach (CoinFlipManager::getInstance()->getCoinflips() as $coinflip) {
            $coinflips[$coinflip->getPlayer()] = [
                "player" => $coinflip->getPlayer(),
                "amount" => $coinflip->getAmount(),
                "item" => Serialize::serialize($coinflip->getItem()),
            ];
        }

        $file = Loader::getInstance()->getDataFolder() . "coinflips.yml";
        Loader::getInstance()->getServer()->getAsyncPool()->submitTask(new SaveCFAsyncTask($file, $coinflips));
    }
}