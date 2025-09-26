<?php

namespace tizzi;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\entity\Villager;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase implements Listener {

    public $loggers = [];

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TF::GREEN . "Loader habilitado!");
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        if($player->isAlive()){
            $level = $player->getLevel();
            $nbt = clone $player->namedtag;
            $villager = new Villager($player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), $nbt);
            $villager->setPosition($player->asVector3());
            $villager->setNameTag(TF::RED . $player->getName());
            $villager->setNameTagVisible(true);
            $villager->setMaxHealth(10);
            $villager->setHealth(10);
            $villager->spawnToAll();
            $this->loggers[spl_object_hash($villager)] = [
                "items" => $player->getInventory()->getContents(),
                "playerName" => $player->getName()
            ];
            $this->getServer()->broadcastMessage(TF::RED . "[CombatLogger] " . TF::GRAY . $player->getName() . " desconectó en combate. Cuidado!");
        }
    }

    public function onDamage(EntityDamageByEntityEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof Villager && isset($this->loggers[spl_object_hash($entity)])){
        }
    }

    public function onDeath(EntityDeathEvent $event) {
        $entity = $event->getEntity();
        $hash = spl_object_hash($entity);
        if($entity instanceof Villager && isset($this->loggers[$hash])){
            $data = $this->loggers[$hash];
            foreach($data["items"] as $item){
                $entity->getLevel()->dropItem($entity, $item);
            }
            $this->getServer()->broadcastMessage(TF::RED . "[CombatLogger] " . TF::GRAY . $data["playerName"] . " fue combatido mientras estaba desconectado y perdió su inventario!");
            unset($this->loggers[$hash]);
        }
    }
}
