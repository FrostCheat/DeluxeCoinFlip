<?php

namespace frostcheat\deluxecoinflip\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;

use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationKeys;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\utils\Utils;

use pocketmine\command\CommandSender;

class DeleteSubCommand extends BaseSubCommand {
    public function __construct() {
        parent::__construct("delete", "Delete your CoinFlip");
        $this->setPermission("deluxecoinflip.command.delete");
    }

    public function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_NO_PLAYER));
            return;
        }

        $cf = CoinFlipManager::getInstance()->getCoinflip($sender->getName());
        if ($cf === null) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_DELETE_ERROR));
            return;
        }

        Utils::getInstance()->addBalance($sender->getName(), $cf->getAmount(), function (bool $success) use ($cf, $sender) {
            if ($success) {
                CoinFlipManager::getInstance()->removeCoinflip($cf);
                $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_DELETE_SUCCESS, [
                    TranslationKeys::AMOUNT => Utils::getInstance()->formatBalance($cf->getAmount())
                ]));
            }
        });
    }
}