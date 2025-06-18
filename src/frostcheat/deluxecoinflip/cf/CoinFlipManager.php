<?php

namespace frostcheat\deluxecoinflip\cf;

use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationKeys;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\provider\Provider;
use frostcheat\deluxecoinflip\utils\Utils;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class CoinFlipManager {
    use SingletonTrait;

    private array $coinflips = [];
    private array $pages = [];

    public function init(): void {
        $this->coinflips = Provider::getInstance()->getCoinflips();
    }

    public function addCoinflip(Coinflip $coinflip): void {
        $this->coinflips[$coinflip->getPlayer()] = $coinflip;
    }

    public function getCoinflips(): array {
        return $this->coinflips;
    }

    public function removeCoinflip(Coinflip $coinflip): void {
        if ($this->getCoinflip($coinflip->getPlayer()) !== null) {
            unset($this->coinflips[$coinflip->getPlayer()]);
        }
    }

    public function getCoinflip(string $symbol): ?CoinFlip {
        return $this->coinflips[$symbol] ?? null;
    }

    public function sendCoinFlipMenu(Player $player, int $page = 1): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName(LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_TITLE, [
            TranslationKeys::CURRENT_PAGE => $page,
        ]));

        $coinflips = array_values($this->coinflips);
        $maxPerPage = 18;
        $totalPages = max(1, ceil(count($coinflips) / $maxPerPage));
        $page = max(1, min($page, $totalPages));
        
        $start = ($page - 1) * $maxPerPage;
        $pageItems = array_slice($coinflips, $start, $maxPerPage);

        $inventory = $menu->getInventory();
        $inventory->clearAll();

        foreach ($pageItems as $index => $cf) {
            $inventory->setItem($index, $this->prepareItem($cf));
        }

        $menu->getInventory()->setItem(22, VanillaItems::GOLD_INGOT()->setCustomName(
            LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_ITEM_BALANCE, [
                TranslationKeys::BALANCE => Utils::getInstance()->formatBalance(Utils::getInstance()->getBalance($player->getName())),
            ])
        ));

        $menu->getInventory()->setItem(25,
            VanillaItems::ARROW()->setNamedTag(CompoundTag::create()->setInt("action", 1))->setCustomName(
                LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_ITEM_PREVIOUS_PAGE)
            )
        );
        $menu->getInventory()->setItem(26,
            VanillaItems::ARROW()->setNamedTag(CompoundTag::create()->setInt("action", 2))->setCustomName(
                LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_ITEM_NEXT_PAGE)
            )
        );

        $menu->setListener(function(InvMenuTransaction $transaction) use ($menu, $page, $totalPages): InvMenuTransactionResult {
            $item = $transaction->getItemClicked();
            $player = $transaction->getPlayer();

            if ($item->getNamedTag() !== null) {
                $tag = $item->getNamedTag();

                if ($tag->getTag("action") !== null) {
                    $action = $tag->getInt("action");
                    if ($action === 1 && $page > 1) {
                        $this->sendCoinFlipMenu($player, $page - 1);
                    } elseif ($action === 2 && $page < $totalPages) {
                        $this->sendCoinFlipMenu($player, $page + 1);
                    }
                    return $transaction->discard();
                } elseif ($tag->getTag("player") !== null) {
                    $cf = $this->getCoinflip($tag->getString("player"));
                    if ($cf !== null) {
                        $balance = Utils::getInstance()->getBalance($player->getName());
                        if ($balance < $cf->getAmount()) {
                            $player->removeCurrentWindow();
                            $player->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::PLAYER_INSUFFICIENT_FUNDS));
                            return $transaction->discard();
                        }

                        if ($player->getName() !== $cf->getPlayer()) {
                            $this->confirmMenu($player, $cf, $menu);
                        }
                    }
                }
            }
            return $transaction->discard();
        });

        $menu->send($player);
    }

    public function confirmMenu(Player $player, CoinFlip $cf, InvMenu $menu): void {
        $menu->setName(LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_CONFIRM_TITLE));

        $inventory = $menu->getInventory();
        $inventory->clearAll();

        $cancelItem = VanillaBlocks::REDSTONE()->asItem()->setNamedTag(CompoundTag::create()->setInt("action", 1))->setCustomName(LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_ITEM_CANCEL_BUY));
        $confirmItem = VanillaBlocks::EMERALD()->asItem()->setNamedTag(CompoundTag::create()->setInt("action", 2))->setCustomName(LanguageManager::getInstance()->getTranslation(TranslationMessages::MENU_ITEM_CONFIRM_BUY));
        
        $cancelSlots = [0, 1, 2, 9, 10, 11, 18, 19, 20];
        $confirmSlots = [6, 7, 8, 15, 16, 17, 24, 25, 26];
        
        foreach ($cancelSlots as $slot) {
            $inventory->setItem($slot, clone $cancelItem);
        }
        
        foreach ($confirmSlots as $slot) {
            $inventory->setItem($slot, clone $confirmItem);
        }
        
        $inventory->setItem(13, $this->prepareItem($cf));

        $menu->setListener(function(InvMenuTransaction $transaction) use ($cf, $menu): InvMenuTransactionResult {
            $item = $transaction->getItemClicked();
            $player = $transaction->getPlayer();

            if ($item->getNamedTag() !== null) {
                $tag = $item->getNamedTag();

                if ($this->getCoinflip($cf->getPlayer()) === null) {
                    $player->removeCurrentWindow();
                    return $transaction->discard();
                }

                if ($tag->getTag("action") !== null) {
                    $action = $tag->getInt("action");
                    if ($action === 1) {
                        $player->removeCurrentWindow();
                        $this->sendCoinFlipMenu($player);
                    } elseif ($action === 2) {
                        Utils::getInstance()->removeBalance($player->getName(), $cf->getAmount(), function (bool $success) use ($cf, $player, $menu) {
                            if ($success) {
                                CoinFlipManager::getInstance()->removeCoinflip($cf);
                                $player->removeCurrentWindow();
                                Utils::getInstance()->playMenuAnimation($cf, $player);
                            }
                        });
                    }
                    return $transaction->discard();
                }
            }
            return $transaction->discard();
        });
    }

    public function prepareItem(CoinFlip $coinFlip): Item {
        $i = clone $coinFlip->getItem();
        $i->setLore(LanguageManager::getInstance()->getTranslation(TranslationMessages::ITEM_LORE, [
            TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($coinFlip->getAmount()),
        ]));

        $namedtag = $i->getNamedTag();
        $namedtag->setString("player", $coinFlip->getPlayer());
        $i->setNamedTag($namedtag);

        return $i;
    }
}
