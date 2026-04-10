<?php

namespace hades\listener;

use Exception;
use hades\Hades;
use hades\menu\HadesAction;
use hades\session\HadesSession;
use hades\session\HadesSessionManager;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\scheduler\ClosureTask;

class HadesListener implements Listener
{
    /**
     * @throws Exception
     */
    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        $session = HadesSessionManager::getPlayerSession($event->getPlayer());
        if ($session === null || $session->isClosing()) return;
        $sessionInventory = $session->getCurrentMenu()?->getInventory();
        if ($event->getInventory() === $sessionInventory) {
            $session->getCurrentMenu()->close($session->getPlayer());
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        HadesSessionManager::addSession(new HadesSession($event->getPlayer()));
    }

    /**
     * @throws Exception
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $session = HadesSessionManager::getPlayerSession($event->getPlayer());
        $session->getCurrentMenu()?->close($session->getPlayer());
        HadesSessionManager::removeSession($session);
    }

    /**
     * @throws Exception
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $session = HadesSessionManager::getPlayerSession($player);
        $currentMenu = $session->getCurrentMenu();

        if ($currentMenu === null || $event->isCancelled()) return;
        if ($session->isClosing() || $session->isOpening()) {
            $event->cancel();
            return;
        }

        $cancel = false;
        $shouldClose = false;
        foreach ($transaction->getActions() as $action) {
            if (!$action instanceof SlotChangeAction || $action->getInventory() !== $currentMenu->getInventory()) continue;
            $hadesAction = new HadesAction($session->getPlayer(), $action);
            $listener = $currentMenu->getTransactionListener();
            if ($listener !== null) $customResult = $listener($hadesAction) ?? false;
            if (isset($customResult) && !$customResult) {
                $cancel = true;
            }
            if ($hadesAction->getShouldClose()) $shouldClose = true;
        }

        if ($cancel) $event->cancel();
        if ($shouldClose) Hades::getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($currentMenu, $player) {$currentMenu->close($player);}), 1);
    }
}