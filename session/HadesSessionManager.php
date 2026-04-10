<?php

namespace hades\session;

use pocketmine\player\Player;

class HadesSessionManager
{
    private static array $manager = [];

    /**
     * Better not touching that.
     *
     * @internal
     */
    public static function addSession(HadesSession $session): void
    {
        self::$manager[$session->getPlayer()->getId()] = $session;
    }

    /**
     * Better not touching that.
     *
     * @internal
     */
    public static function removeSession(HadesSession $session): void
    {
        unset(self::$manager[$session->getPlayer()->getId()]);
    }

    public static function getPlayerSession(Player $player): ?HadesSession
    {
        return self::$manager[$player->getId()] ?? null;
    }
}