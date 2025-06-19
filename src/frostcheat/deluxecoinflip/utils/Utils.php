<?php

namespace frostcheat\deluxecoinflip\utils;

use Closure;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\database\cache\GlobalCache;
use cooldogedev\BedrockEconomy\database\constant\Search;

use frostcheat\deluxecoinflip\cf\CoinFlip;
use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationKeys;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\Loader;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

class Utils {
    use SingletonTrait;

    public function getBalance(string $player): int {
        $cacheEntry = GlobalCache::ONLINE()->get($player);
        return $cacheEntry !== null ? $cacheEntry->amount : 0;
    }

    public function formatBalance(int $amount): string {
        return BedrockEconomy::getInstance()->getCurrency()->formatter->format($amount, 0);
    }

    public function addBalance(string $player, int $amount, Closure $callback): void {
        BedrockEconomyAPI::CLOSURE()->add(
            Search::EMPTY,
            $player,
            $amount,
            0,
            function () use ($player, $amount, $callback): void {
                $p = Loader::getInstance()->getServer()->getPlayerExact($player);
                if ($p !== null && $p->isOnline()) {
                    $p->sendMessage(LanguageManager::getInstance()->getPrefix() . "§a+" . $this->formatBalance($amount));
                }
                $callback(true);
            },
            function () use ($callback): void {
                $callback(false);
            }
        );
    }
    
    public function removeBalance(string $player, int $amount, Closure $callback): void {
        BedrockEconomyAPI::CLOSURE()->subtract(
            Search::EMPTY,
            $player,
            $amount,
            0,
            function () use ($player, $amount, $callback): void {
                $p = Loader::getInstance()->getServer()->getPlayerExact($player);
                if ($p !== null && $p->isOnline()) {
                    $p->sendMessage(LanguageManager::getInstance()->getPrefix() . "§c-" . $this->formatBalance($amount));
                }
                $callback(true);
            },
            function () use ($callback): void {
                $callback(false);
            }
        );
    }    

    public function strToTime(string $input): int {
        $units = [
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
            'w' => 604800,
            'mo' => 2592000,
            'y' => 31536000
        ];

        if (preg_match('/^(\d+)(mo|[smhdwy])$/', strtolower($input), $matches)) {
            $value = (int)$matches[1];
            $unit = $matches[2];

            if (isset($units[$unit])) {
                return $value * $units[$unit];
            }
        }
        return 0;
    }

    public function getHeadItem(string $playerUUID, string $playerName, string $skinData) : Item {
        $item = VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER())->asItem();
    
        $nbt = $item->getNamedTag();
        $nbt->setString("PlayerUUID", $playerUUID);
        $nbt->setString("PlayerName", $playerName);
        $nbt->setByteArray("SkinData", $skinData);
        $item->setNamedTag($nbt);
        $item->setCustomName($playerName);
        return $item;
    }

    public function play(Player $p, string $soundName, float $volume = 1, float $pitch = 1):void {
		$pk = new PlaySoundPacket();
		$pk->soundName = $soundName;
		$pk->pitch = $pitch;
		$pk->volume =$volume;
		$pos = $p->getEyePos();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$p->getNetworkSession()->sendDataPacket($pk);
	}
    
    public function playMenuAnimation(CoinFlip $cf, Player $opponent): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
            return $transaction->discard();
        });
        $inventory = $menu->getInventory();
        $player1 = Loader::getInstance()->getServer()->getPlayerExact($cf->getPlayer());
    
        foreach ([$player1, $opponent] as $pl) {
            if ($pl !== null && $pl->isOnline()) {
                if ($pl->getCurrentWindow() !== null) $pl->removeCurrentWindow();
                $menu->send($pl);
            }
        }
    
        $centerSlot = 13;
        $interval = 20;
        $cycles = 7;
        $state = 0;
    
        $glassColors = [
            VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BLACK())->asItem(),
            VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::YELLOW())->asItem(),
        ];
    
        $itemA = $cf->getItem();
        $itemB = $this->getHeadItem($opponent->getXuid(), $opponent->getName(), $opponent->getSkin()->getSkinData());
    
        $centerItems = [$itemA, $itemB];
    
        $handler = null;
    
        $task = new ClosureTask(function() use (&$cycles, &$state, $cf, $inventory, $centerItems, $glassColors, $centerSlot, $player1, $opponent, &$handler): void {
            if ($cycles-- <= 0) {
                $resultItem = $centerItems[array_rand($centerItems)];
    
                $inventory->clearAll();
                $inventory->setItem($centerSlot, $resultItem);
    
                $tax = Loader::getInstance()->getConfig()->get("cf-tax", 15) / 100;
                $gross = $cf->getAmount() * 2;
                $finalAmount = round($gross * (1 - $tax));
    
                Utils::getInstance()->addBalance($resultItem->getCustomName(), $finalAmount, function (bool $success) use ($finalAmount, $tax, $player1, $opponent, $resultItem, $cf) {
                    if ($success) {
                        $winnerName = $resultItem->getCustomName();
                        $opponentName = $opponent->getName();
                        $player1Name = $player1 !== null ? $player1->getName() : $cf->getPlayer();
    
                        $isOpponentWinner = $winnerName === $opponentName;
                        $loserName = $isOpponentWinner ? $player1Name : $opponentName;
    
                        Loader::getInstance()->getServer()->broadcastMessage(
                            LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COINFLIP_WINNER, [
                                TranslationKeys::PLAYER => $loserName,
                                TranslationKeys::WINNER => $winnerName,
                                TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($finalAmount),
                                TranslationKeys::TAX => "-" . ($tax * 100) . "%",
                            ])
                        );
    
                        CoinFlipManager::getInstance()->removeCoinflip($cf);
                    }
                });
    
                Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player1, $opponent): void {
                    foreach ([$player1, $opponent] as $pl) {
                        if ($pl !== null && $pl->isOnline() && $pl->getCurrentWindow() !== null) {
                            $pl->removeCurrentWindow();
                        }
                    }
                }), 20);
    
                if ($handler !== null) {
                    $handler->cancel();
                }
                return;
            }
    
            if ($opponent->isOnline()) {
                $inventory->setItem($centerSlot, $centerItems[$state % 2]);
                $this->play($opponent, "block.click");
            }
            if ($player1 !== null && $player1->isOnline()) {
                $this->play($player1, "block.click");
            }
    
            for ($i = 0; $i < $inventory->getSize(); $i++) {
                if ($i === $centerSlot) continue;
    
                if ($opponent->isOnline()) {
                    $inventory->setItem($i, $glassColors[$state % 2]);
                }
            }
    
            $state++;
        });
    
        $handler = Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, $interval);
    }    
}
?>