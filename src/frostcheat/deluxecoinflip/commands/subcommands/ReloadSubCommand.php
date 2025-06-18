<?php

namespace frostcheat\deluxecoinflip\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;

use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\Loader;

use pocketmine\command\CommandSender;

class ReloadSubCommand extends BaseSubCommand {

    public function __construct() {
        parent::__construct("reload", "Reloads the CoinFlip config");
        $this->setPermission("deluxecoinflip.command.reload");
    }

    public function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        Loader::getInstance()->reloadConfig();
        $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_RELOAD_SUCCESS));
    }
}