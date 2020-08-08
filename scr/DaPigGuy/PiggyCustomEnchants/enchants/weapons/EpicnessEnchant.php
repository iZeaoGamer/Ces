<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCustomEnchants\enchants\weapons;

use DaPigGuy\PiggyCustomEnchants\enchants\CustomEnchant;
use DaPigGuy\PiggyCustomEnchants\enchants\ReactiveEnchantment;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class EpicnessEnchant extends ReactiveEnchantment
{
    /** @var string */
    public $name = "Epicness";
    /** @var int */
    public $cooldownDuration = 64;
    /** @var int */
    public $maxLevel = 3;
    /** @var int */
    public $itemType = CustomEnchant::ITEM_TYPE_SWORD;
    /** @var int */
    public $rarity = CustomEnchant::RARITY_SIMPLE;

    /** @var ClosureTask[] */
    public static $tasks;

    public function getDefaultExtraData(): array
    {
        return ["interval" => 20, "durationMultiplier" => 20, "base" => 1, "multiplier" => 0];
    }

    public function react(Player $player, Item $item, Inventory $inventory, int $slot, Event $event, int $level, int $stack): void
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $entity = $event->getEntity();
			if($entity instanceof Living) {
				if (!isset(self::$tasks[$entity->getId()])) {
					$endTime = time() + $this->extraData["durationMultiplier"] * $level;
					self::$tasks[$entity->getId()] = new ClosureTask(function () use ($entity, $endTime): void {
						if (!$entity->isAlive() || $entity->isClosed() || $entity->isFlaggedForDespawn() || $endTime < time()) {
							self::$tasks[$entity->getId()]->getHandler()->cancel();
							unset(self::$tasks[$entity->getId()]);
						    return;
						}
						$entity->attack(new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, $this->extraData["base"] + $entity->getHealth() * $this->extraData["multiplier"]));
                        $entity->getLevel()->addParticle(new DestroyBlockParticle($entity->add(0, 1), Block::get(Block::END_PORTAL)));
					});
					$this->plugin->getScheduler()->scheduleRepeatingTask(self::$tasks[$entity->getId()], $this->extraData["interval"]);
				}
                $player->sendMessage("§b*§d*§b* §7The Enemy Is Now Bleeding Epicness! §8(§7Cool Effects§8) §b*§d*§b*");
				$event->getEntity()->sendMessage("§c*§6*§c* §cThe Enemy Made You Bleed Epicness! §8(§7Cool Effects§8) §c*§6*§c*");
			}
		}
	}
}
