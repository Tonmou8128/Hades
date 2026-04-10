<?php

namespace hades\menu;

use Closure;
use Exception;
use hades\Hades;
use hades\session\HadesSessionManager;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class HadesMenu
{
    private ?string $name = null;
    private int $size;
    private HadesInventory $inventory;
    private ?Closure $listener = null;
    private ?Closure $closeListener = null;

    /**
     * @throws Exception
     */
    public function __construct(int $size = Hades::SIMPLE_CHEST)
    {
        $this->size = $size;
        $this->inventory = new HadesInventory($this->size);
    }

    /**
     * Give a custom name to the menu. The name can't be changed while showing the menu.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): HadesMenu
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getInventory(): HadesInventory
    {
        return $this->inventory;
    }

    /**
     * Call this method to show the menu to a player
     *
     * @param Player $player
     * @return void
     * @throws Exception
     */
    public function show(Player $player): void
    {
        $session = HadesSessionManager::getPlayerSession($player);
        $currentMenu = $session->getCurrentMenu();

        if (($currentMenu !== null && $currentMenu !== $this)) {
            $session->addMenuToQueue($this);
            return;
        }
        if (($currentMenu === $this) && ($session->isOpening() || $session->isClosing())) {
            $session->addMenuToQueue($this);
            return;
        }
        if ($currentMenu === null) $session->setCurrentMenu($this);

        $position = $this->getVectorBehindPlayer($player);
        if ($position->getY() <= -64 || $position->getY() >= 320) return;

        $session->setOpening(true);
        $this->inventory->setHolder($position, $player->getWorld());
        $this->createFakeChest($player, $position);
        Hades::getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $session) {
            $player->setCurrentWindow($this->inventory);
            $session->setOpening(false);
        }), 1);
    }

    /**
     * Important: do not use HadesMenu close() method during a HadesAction callback, use $transaction->closeMenu() instead
     *
     * @param Player $player
     * @return void
     * @throws Exception
     * @see HadesAction::closeMenu()
     */
    public function close(Player $player): void
    {
        $session = HadesSessionManager::getPlayerSession($player);

        if ($session->getCurrentMenu() !== $this) return;
        if ($session->isOpening() || $session->isClosing()) return;

        $session->setClosing(true);
        if (isset($this->closeListener)) $this->getCloseListener()($player, $this->getInventory());
        $this->removeFakeChest($player, $this->getInventory()->getHolder()->asVector3());
        $player->removeCurrentWindow();
        Hades::getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $session) {
            $session->closingFinished();
        }), 1);
    }

    private function createFakeChest(Player $player, Vector3 $vector, bool $secondChest = false): void
    {
        $position = BlockPosition::fromVector3($vector);
        $nbt = CompoundTag::create()->setString(Tile::TAG_ID, "Chest");

        if ($this->name) $nbt->setString(Nameable::TAG_CUSTOM_NAME, $this->name);

        if ($this->getInventory()->getSize() === Hades::DOUBLE_CHEST) {
            $nbt ->setInt(Chest::TAG_PAIRX, $position->getX() + ($secondChest ? -1 : 1));
            $nbt->setInt(Chest::TAG_PAIRZ, $position->getZ());
        }

        $chestNetworkId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::CHEST()->getStateId());
        $player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create($position, $chestNetworkId, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL));
        $player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create($position, new CacheableNbt($nbt)));

        if ($this->getInventory()->getSize() === Hades::DOUBLE_CHEST && !$secondChest) $this->createFakeChest($player, $vector->addVector(new Vector3(1, 0, 0)), true);
    }

    private function removeFakeChest(Player $player, Vector3 $vector, bool $secondChest = false): void
    {
        $packets = Hades::getPlugin()->getServer()->getWorldManager()->getWorld($player->getWorld()->getId())->createBlockUpdatePackets([$vector]);
        foreach ($packets as $packet) {
            $player->getNetworkSession()->sendDataPacket($packet);
        }
        if ($this->getInventory()->getSize() === Hades::DOUBLE_CHEST && !$secondChest) $this->removeFakeChest($player, $vector->addVector(new Vector3(1, 0, 0)), true);
    }

    private function getVectorBehindPlayer(Player $player): Vector3
    {
        return $player->getEyePos()->addVector($player->getDirectionVector()->multiply(-2))->floor();
    }

    public function setItem(Item $item, int $slot): HadesMenu
    {
        $this->getInventory()->setItem($slot, $item);
        return $this;
    }

    public function removeItem(int $slot): HadesMenu
    {
        $this->getInventory()->clear($slot);
        return $this;
    }

    public function getItem(int $slot): Item
    {
        return $this->getInventory()->getItem($slot);
    }

    /**
     * $listener takes as argument the HadesAction. The boolean returned is for cancelling the transaction or not.
     *
     * @param (Closure(HadesAction): bool) $listener
     * @return void
     */
    public function addTransactionListener(Closure $listener): void
    {
        $this->listener = $listener;
    }

    public function getTransactionListener(): ?Closure
    {
        return $this->listener;
    }

    /**
     * @param (Closure(Player, Inventory): void) $listener
     * @return void
     */
    public function addCloseListener(Closure $listener): void
    {
        $this->closeListener = $listener;
    }

    public function getCloseListener(): ?Closure
    {
        return $this->closeListener;
    }
}