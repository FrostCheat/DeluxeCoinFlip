<?php

namespace frostcheat\deluxecoinflip\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;

use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\language\TranslationMessages;
use frostcheat\deluxecoinflip\Loader;

use pocketmine\command\CommandSender;

use JsonException;

class SetLanguageSubCommand extends BaseSubCommand {

    public function __construct() {
        parent::__construct("setlanguage", "Sets the plugin language");
        $this->setPermission("deluxecoinflip.command.setlanguage");
    }

    public function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("language"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $language = $args["language"];

        if (!in_array($language, LanguageManager::SUPPORTED_LANGUAGES)) {
            $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_SETLANGUAGE_ERROR));
            return;
        }

        Loader::getInstance()->getConfig()->set("language", $language);
        try {
            Loader::getInstance()->getConfig()->save();
        } catch (JsonException $e) {
            Loader::getInstance()->getLogger()->error($e->getMessage());
        }
        LanguageManager::getInstance()->setLanguage($language);
        $sender->sendMessage(LanguageManager::getInstance()->getPrefix() . LanguageManager::getInstance()->getTranslation(TranslationMessages::COMMAND_SETLANGUAGE_SUCCESS));
    }
}