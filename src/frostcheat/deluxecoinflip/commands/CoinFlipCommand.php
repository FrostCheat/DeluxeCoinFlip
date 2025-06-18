<?php

namespace frostcheat\deluxecoinflip\commands;

use CortexPE\Commando\BaseCommand;

use frostcheat\deluxecoinflip\commands\subcommands\NPCSubCommand;
use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\commands\subcommands\CreateSubCommand;
use frostcheat\deluxecoinflip\commands\subcommands\ReloadSubCommand;
use frostcheat\deluxecoinflip\commands\subcommands\SetLanguageSubCommand;
use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationMessages;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

class CoinFlipCommand extends BaseCommand {

    public function __construct(Plugin $plugin) {
        parent::__construct($plugin, "coinflip", "Open CoinFlip Menu", ["cf"]);
        $this->setPermission("coinflip.command");
    }

    public function prepare(): void {
        $this->registerSubCommand(new CreateSubCommand());
        $this->registerSubCommand(new NPCSubCommand());
        $this->registerSubCommand(new ReloadSubCommand());
        $this->registerSubCommand(new SetLanguageSubCommand());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_NO_PLAYER));
            return;
        }

        CoinFlipManager::getInstance()->sendCoinFlipMenu($sender);
    }
}