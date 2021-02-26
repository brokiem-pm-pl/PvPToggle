<?php

declare(strict_types=1);

namespace brokiem\pvptoggle;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class PvPToggle extends PluginBase implements Listener
{

    /** @var array $config */
    public $config = [];

    /**
     * @var Config $data
     */
    private $data;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();

        $this->data = new Config($this->getDataFolder() . "pvptoggleData", Config::YAML, [
            "list" => []
        ]);

        $this->config = $this->data->getAll();

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->saveAllData();
        }), 20 * 600);
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (strtolower($command->getName()) === "pvp") {
            if ($sender instanceof Player) {
                if ($this->isPvpToggle($sender)) {
                    $sender->sendMessage(TF::colorize($this->getConfig()->get("pvp.deactivated")));
                    unset($this->config["list"][array_search(strtolower($sender->getName()), $this->config["list"])]);
                } else {
                    $this->config["list"] = strtolower($sender->getName());
                    $sender->sendMessage(TF::colorize($this->getConfig()->get("pvp.activated")));
                }
            }
        }

        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isPvpToggle(Player $player): bool
    {
        if (in_array(strtolower($player->getName()), $this->config["list"])) {
            return true;
        }

        return false;
    }

    public function saveAllData(): void {
        $this->data->setAll($this->config);
        $this->data->save();
    }

    public function onHit(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof Player and $damager instanceof Player) {
            if ($this->isPvpToggle($damager)) {
                $damager->sendMessage(TF::colorize($this->getConfig()->get("pvp.is.activated.damager")));
                $event->setCancelled();
                return;
            }

            if ($this->isPvpToggle($entity)) {
                $damager->sendMessage(str_replace($entity->getDisplayName(), "{name}", TF::colorize($this->getConfig()->get("pvp.is.activated.entity"))));
                $event->setCancelled();
            }
        }
    }

    public function onDisable(): void
    {
        $this->saveAllData();
    }
}