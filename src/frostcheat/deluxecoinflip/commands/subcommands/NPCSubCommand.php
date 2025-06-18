<?php

namespace frostcheat\deluxecoinflip\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;

use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\npc\CoinFlipNPC;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class NPCSubCommand extends BaseSubCommand
{

    public function __construct()
    {
        parent::__construct("npc", "For spawn a CoinFlip NPC");
        $this->setPermission("deluxecoinflip.command.npc");
    }

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_NO_PLAYER));
            return;
        }

        $npc = new CoinFlipNPC($sender->getLocation(), $sender->getSkin());
        $npc->spawnToAll();
        $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::NPC_SPAWN));
    }
}