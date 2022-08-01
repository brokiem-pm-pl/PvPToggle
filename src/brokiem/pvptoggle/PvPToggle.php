<?php

declare(strict_types=1);

namespace brokiem\pvptoggle;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class PvPToggle extends PluginBase implements Listener {

    private Config $data;

    private array $allData = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();

        $this->data = new Config($this->getDataFolder() . "pvptoggleData.yml", Config::YAML, ["list" => []]);
        $this->allData = $this->data->getAll();

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            $this->saveAllData();
        }), 20 * (int)$this->getConfig()->get("save.data.delay"));

        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) === "pvptoggle") {
            if (isset($args[0]) and $sender->hasPermission("pvptoggle.staff")) {
                $player = $this->getServer()->getPlayerByPrefix($args[0]);

                if ($player === null) {
                    $sender->sendMessage("Player " . $args[0] . " doesn't exits!");
                    return true;
                }

                if ($this->isPvpToggle($player)) {
                    $sender->sendMessage(str_replace("{name}", $player->getDisplayName(), TF::colorize($this->getConfig()->get("staff.pvp.activated"))));
                    unset($this->allData["list"][array_search(strtolower($player->getName()), $this->allData["list"], true)]);
                } else {
                    $this->allData["list"][] = strtolower($player->getName());
                    $sender->sendMessage(str_replace("{name}", $player->getDisplayName(), TF::colorize($this->getConfig()->get("staff.pvp.deactivated"))));
                }

                return true;
            }

            if ($sender instanceof Player) {
                if ($this->isPvpToggle($sender)) {
                    $sender->sendMessage(TF::colorize($this->getConfig()->get("pvp.activated")));
                    unset($this->allData["list"][array_search(strtolower($sender->getName()), $this->allData["list"], true)]);
                } else {
                    $this->allData["list"][] = strtolower($sender->getName());
                    $sender->sendMessage(TF::colorize($this->getConfig()->get("pvp.deactivated")));
                }
            }
        }

        return true;
    }

    public function getAllData(): array {
        return $this->allData;
    }

    public function isPvpToggle(Player $player): bool {
        if (in_array(strtolower($player->getName()), $this->allData["list"], true)) {
            return true;
        }

        return false;
    }

    public function saveAllData(): void {
        $this->data->setAll($this->allData);
        $this->data->save();
    }

    public function onHit(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof Player and $damager instanceof Player) {
            if ($this->isPvpToggle($damager) and $this->getConfig()->get("default.pvp") === "on") {
                $damager->sendMessage(TF::colorize($this->getConfig()->get("pvp.is.activated.damager")));
                $event->cancel();
                return;
            }

            if ($this->isPvpToggle($entity) and $this->getConfig()->get("default.pvp") === "on") {
                $damager->sendMessage(str_replace("{name}", $entity->getDisplayName(), TF::colorize($this->getConfig()->get("pvp.is.activated.entity"))));
                $event->cancel();
            }
        }
    }

    public function onDisable(): void {
        $this->saveAllData();
    }
}