<?php

declare(strict_types=1);

namespace kenzeve\SimpleBossBar;

use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
use pocketmine\entity\AttributeMap;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\player\Player;

class BossBar{

    protected Player $player;
    protected string $text;
    protected AttributeMap $attributeMap;
    protected bool $spawned = false;

    public function __construct(Player $player, string $text = ""){
        $this->player = $player;
        $this->text = $text;
        $this->attributeMap = new AttributeMap();
        $this->attributeMap->add(AttributeFactory::getInstance()
                ->mustGet(Attribute::HEALTH)
                ->setMaxValue(100.0)
                ->setMinValue(0.0)
                ->setDefaultValue(100.0)
        );
        $this->show();
    }

    public function getText() : string{
        return $this->text;
    }

    public function setText(string $text = "") : void{
        $this->text = $text;
        $this->sendBossTextPacket();
    }

    public function getPercentage() : float{
        return $this->attributeMap->get(Attribute::HEALTH)->getValue() / 100;
    }

    public function setPercentage(float $percentage) : void{
        $percentage = (float) min(1.0, max(0.0, $percentage));
        $this->attributeMap->get(Attribute::HEALTH)->setValue($percentage * $this->attributeMap->get(Attribute::HEALTH)->getMaxValue(), true, true);
        $this->sendBossHealthPacket();
    }

    public function hide() : void{
        if(!$this->spawned){
            return;
        }
        $this->sendRemoveBossPacket();
        $this->spawned = false;
    }

    public function show() : void{
        if($this->spawned){
            return;
        }
        $this->sendBossPacket();
        $this->spawned = true;
    }

    protected function sendBossPacket() : void{
        $pk = new BossEventPacket();
        $pk->title = $this->text;
        $pk->healthPercent = $this->getPercentage();
        $pk->unknownShort = 1;
        $pk->color = 0;
        $pk->overlay = 0;
        $pk->eventType = BossEventPacket::TYPE_SHOW;
        $pk->bossActorUniqueId = $this->player->getId();
        $this->player->getNetworkSession()->sendDataPacket($pk);
    }

    protected function sendRemoveBossPacket() : void{
        $pk = new BossEventPacket();
        $pk->bossActorUniqueId = $this->player->getId();
        $pk->eventType = BossEventPacket::TYPE_HIDE;
        $this->player->getNetworkSession()->sendDataPacket($pk);
    }

    protected function sendBossTextPacket() : void{
        $pk = new BossEventPacket();
        $pk->eventType = BossEventPacket::TYPE_TITLE;
        $pk->title = $this->text;
        $pk->bossActorUniqueId = $this->player->getId();
        $this->player->getNetworkSession()->sendDataPacket($pk);
    }

    protected function sendBossHealthPacket() : void{
        $pk = new BossEventPacket();
        $pk->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
        $pk->healthPercent = $this->getPercentage();
        $pk->bossActorUniqueId = $this->player->getId();
        $this->player->getNetworkSession()->sendDataPacket($pk);
    }
}
