<?php

namespace hades\menu;

use Exception;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class HadesInventory extends SimpleInventory implements BlockInventory
{
    protected Position $holder;

    /**
     * @internal
     */
    public function setHolder(Vector3 $vector, World $world): void
    {
        $this->holder = new Position($vector->getX(), $vector->getY(), $vector->getZ(), $world);
    }

    /**
     * @internal
     * @throws Exception
     */
    public function getHolder(): Position
    {
        if (!isset($this->holder)) throw new Exception("Holder isn't set.");
        return $this->holder;
    }
}