<?php

namespace hades;

use Exception;
use hades\listener\HadesListener;
use hades\menu\HadesMenu;
use pocketmine\plugin\PluginBase;

class Hades
{
    const SIMPLE_CHEST = 27;
    const DOUBLE_CHEST = 54;
    const ALLOWED_SIZES = [
        self::SIMPLE_CHEST,
        self::DOUBLE_CHEST
    ];

    private static PluginBase $plugin;

    /**
     * This function must be called in order for Hades to work
     * @param PluginBase $plugin
     * @return void
     */
    public static function register(PluginBase $plugin): void
    {
        self::$plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents(new HadesListener(), $plugin);
    }

    /**
     * @internal
     */
    public static function getPlugin(): PluginBase
    {
        return self::$plugin;
    }

    /**
     * @internal
     */
    public static function isRegistered(): bool
    {
        return isset(self::$plugin);
    }

    /**
     * Use this function to create a new HadesMenu. Do NOT call directly new HadesMenu().
     *
     * @param int $size
     * @return HadesMenu
     * @throws Exception
     */
    public static function createMenu(int $size): HadesMenu
    {
        if (!self::isRegistered()) throw new Exception("Plugin not registered");
        if (!in_array($size, self::ALLOWED_SIZES)) throw new Exception("Size not allowed: $size");
        return new HadesMenu($size);
    }
}