<?php

namespace frostcheat\deluxecoinflip;

use frostcheat\deluxecoinflip\cf\CoinFlipManager;
use frostcheat\deluxecoinflip\commands\CoinFlipCommand;
use frostcheat\deluxecoinflip\language\LanguageManager;
use frostcheat\deluxecoinflip\npc\CoinFlipNPC;
use frostcheat\deluxecoinflip\provider\Provider;
use frostcheat\deluxecoinflip\utils\Utils;

use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;

use muqsit\invmenu\InvMenuHandler;

use CortexPE\Commando\PacketHooker;
use CortexPE\Commando\exception\HookAlreadyRegistered;

use pocketmine\command\Command;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Loader extends PluginBase {
    use SingletonTrait;
    private const CONFIG_VERSION = 1;

    public function onLoad(): void {
        self::setInstance($this);
        Provider::getInstance()->init();
        LanguageManager::getInstance()->init($this, $this->getConfig()->get("language"));
        CoinFlipManager::getInstance()->init();
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void {
        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
        if (ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", self::CONFIG_VERSION)) {
            $this->reloadConfig();
        }

        if (!PacketHooker::isRegistered())
            PacketHooker::register($this);

        if (!InvMenuHandler::isRegistered())
            InvMenuHandler::register($this);

        $this->saveDefaultConfig();
        $this->saveResource("language/de-DE.yml");
        $this->saveResource("language/en-US.yml");
        $this->saveResource("language/es-ES.yml");
        $this->saveResource("language/fr-FR.yml");
        $this->saveResource("language/pr-BR.yml");
        $this->saveResource("language/ru-RU.yml");

        $this->registerCommands([
            new CoinFlipCommand($this),
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            Provider::getInstance()->saveCoinflips();
        }), Utils::getInstance()->strToTime($this->getConfig()->get("auto-save", "5m")) * 20);

        EntityFactory::getInstance()->register(CoinFlipNPC::class, function (World $world, CompoundTag $nbt) : CoinFlipNPC {
            return new CoinFlipNPC(EntityDataHelper::parseLocation($nbt, $world), CoinFlipNPC::parseSkinNBT($nbt), $nbt);
        }, ['CoinFlipNPC']);

        $this->getLogger()->info("Default Language: " . LanguageManager::getInstance()->getLanguage());
        $this->getLogger()->info(count(CoinFlipManager::getInstance()->getCoinflips()) . " coinflips saved");
    }

    private function registerCommands(array $commands): void {
        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $this->getServer()->getCommandMap()->register("coinflip", $command);
            }
        }
    }

    protected function onDisable(): void
    {
        Provider::getInstance()->saveCoinflips();
    }
}
?>