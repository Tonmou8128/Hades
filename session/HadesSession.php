<?php

namespace hades\session;

use Exception;
use hades\menu\HadesInventory;
use hades\menu\HadesMenu;
use pocketmine\player\Player;

class HadesSession
{
    private Player  $player;
    private ?HadesMenu $currentMenu = null;

    private ?HadesInventory $inventory = null;

    private array $queue = [];

    private bool $isOpening = false;
    private bool $isClosing = false;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @internal
     */
    public function setCurrentMenu(HadesMenu $menu): void
    {
        $this->currentMenu = $menu;
        $this->inventory = new HadesInventory($menu->getSize());
        foreach ($menu->getContent() as $slot => $item) {
            $this->inventory->setItem($slot, $item);
        }
    }

    public function getCurrentMenu(): ?HadesMenu
    {
        return $this->currentMenu;
    }

    public function getCurrentInventory(): ?HadesInventory
    {
        return $this->inventory;
    }

    public function isOpening(): bool
    {
        return $this->isOpening;
    }

    /**
     * @internal
     */
    public function setOpening(bool $state): void
    {
        $this->isOpening = $state;
    }

    public function isClosing(): bool
    {
        return $this->isClosing;
    }

    /**
     * @internal
     */
    public function setClosing(bool $state): void
    {
        $this->isClosing = $state;
    }

    /**
     * @internal
     * @throws Exception
     */
    public function addMenuToQueue(HadesMenu $menu): void
    {
        $this->queue[] = $menu;
        $this->getCurrentMenu()?->close($this->getPlayer());
    }

    /**
     * @internal
     * @throws Exception
     */
    public function closingFinished(): void
    {
        if (isset($this->queue[0])) $this->shiftToNextMenu();
        else {
            $this->currentMenu = null;
            $this->inventory = null;
            $this->setClosing(false);
        }
    }

    /**
     * @throws Exception
     */
    private function shiftToNextMenu(): void
    {
        $this->setClosing(false);
        $this->setCurrentMenu($this->queue[0]);
        array_shift($this->queue);
        $this->currentMenu->show($this->player);
    }
}