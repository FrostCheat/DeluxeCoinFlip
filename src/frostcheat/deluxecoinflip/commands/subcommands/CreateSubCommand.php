<?php

namespace frostcheat\deluxecoinflip\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;

use frostcheat\deluxecoinflip\cf\CoinFlip;
use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationKeys;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\Loader;
use frostcheat\deluxecoinflip\utils\Utils;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CreateSubCommand extends BaseSubCommand {

    public function __construct() {
        parent::__construct("create", "Create a new CoinFlip");
        $this->setPermission("deluxecoinflip.command.create");
    }

    public function prepare(): void {
        $this->registerArgument(0, new IntegerArgument("amount"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_NO_PLAYER));
            return;
        }

        if (CoinFlipManager::getInstance()->getCoinflip($sender->getName()) !== null) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::PLAYER_HAS_COINFLIP));
            return;
        }

        $amount = $args["amount"];
        $minAmount = Loader::getInstance()->getConfig()->get("min-amount", 1);
        $maxAmount = Loader::getInstance()->getConfig()->get("max-amount", 10000000000);

        if ($amount < $minAmount) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_CREATE_MIN_PRICE_ERROR, [
                TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($minAmount),
            ]));
            return;
        }

        if ($amount > $maxAmount) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_CREATE_MAX_PRICE_ERROR, [
                TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($maxAmount),
            ]));
            return;
        }

        if ($amount > Utils::getInstance()->getBalance($sender->getName())) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::PLAYER_INSUFFICIENT_FUNDS));
            return;
        }

        Utils::getInstance()->removeBalance($sender->getName(), $amount, function (bool $success) use ($amount, $sender): void {
            if ($success) {
                $item = Utils::getInstance()->getHeadItem($sender->getSkin(), $sender->getName());
                CoinFlipManager::getInstance()->addCoinflip(new CoinFlip(
                    $sender->getName(),
                    $amount,
                    $item
                ));
                $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_CREATE_SUCCESS, [
                    TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($amount),
                ]));

                if ($amount >= Loader::getInstance()->getConfig()->get("min-amount-broadcast", 100)) {
                    Loader::getInstance()->getServer()->broadcastMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::PLAYER_NOTIFICATION_COINFLIP, [
                        TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($amount),
                        TranslationKeys::PLAYER => $sender->getName(),
                    ]));
                }
            }
        });
    }
}