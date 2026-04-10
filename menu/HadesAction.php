<?php

namespace hades\menu;

use hades\session\HadesSessionManager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\player\Player;

class HadesAction
{
    private Player $player;
    private Item $source;
    private Item $target;
    private int $slot;
    private bool $shouldClose = false;

    public function __construct(Player $player, SlotChangeAction $action)
    {
        $this->player = $player;
        $this->source = $action->getSourceItem();
        $this->target = $action->getTargetItem();
        $this->slot = $action->getSlot();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * Return the item(s) of the action
     *
     * @return Item[]
     */
    public function getItems(): array
    {
        return [$this->source, $this->target];
    }

    /**
     * Give the item replaced in the slot of the action
     *
     * @return Item
     */
    public function getSourceItem(): Item
    {
        return $this->source;
    }

    /**
     * Give the item replacing in the slot of the action
     *
     * @return Item
     */
    public function getTargetItem(): Item
    {
        return $this->target;
    }

    /**
     * @return int[]
     */
    public function getItemsTypeIds(): array
    {
        return [$this->source->getTypeId(), $this->target->getTypeId()];
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    /**
     * Important: Use this method in a TransactionListener instead of hadesMenu::close().
     *
     * @return void
     */
    public function closeMenu(): void
    {
        $this->shouldClose = true;
    }

    /**
     * @internal
     */
    public function getShouldClose(): bool
    {
        return $this->shouldClose;
    }
}