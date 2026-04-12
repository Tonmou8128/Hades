Le truc est pas stable en multijoueurs je fixerai plus tard

# 🐦‍🔥 - Hades

## 🔥 - Présentation

Hades est un projet personnel visant à intégrer des menus de coffre (Chest-UI) à un serveur PocketMine-MP. Largement inspiré de InvMenu (par Muqsit), il permet la création ainsi que la gestion de ces menus. Pas aussi poussé que InvMenu, il est cependant suffisant pour la grande majorité des menus de ce type.

## 🔥 - Création de menu

Avant d'utiliser quoi que ce soit, il faut appeler `Hades::register()` dans la base du plugin.

```php
// class OwnPlugin extends PluginBase

public function onEnable(): void
{
    // ...
    if (!Hades::isRegistered()) Hades::register($this);
}
```

Pour créer un menu, il faut appeler `Hades::createMenu(<size>)`, en passant en argument la taille (`Hades::SIMPLE_CHEST` (coffre simple) ou `Hades::DOUBLE_CHEST` (coffre double)).
Cette fonction renvoie un objet HadesMenu, personnalisable:

| Méthode                   | Effet                                  |
|---------------------------|----------------------------------------|
| `setName(<name>)`         | Met le nom <name> au coffre.           |
| `setItem(<Item>, <slot>)` | Met l'objet <Item> dans le slot <slot> |
Ainsi que d'autres expliquées plus amplement ensuite.

La méthode `HadesMenu::show(<player>)` permet d'afficher le menu personnalisé au joueur.

Exemple:

```php
$crazyMenu = Hades::createMenu(Hades::SIMPLE_CHEST);
$crazyMenu->setName("Un menu tah les ouf");
$crazyMenu->setItem(VanillaItems::DIAMOND(), 4);

$crazyMenu->show($player);
```

Ce code crée un menu avec un titre personnalisé contenant un diamant dans le slot 4, et le montre à $player.


## 🔥 - Gestion de menu

Dans cette section, on va apprendre comment exécuter du code en fonction de l'interaction du joueur avec le menu.

### 1. Lors d'une transaction

Il est possible d'exécuter une fonction à chaque fois que le joueur déplace ou change un item dans un slot de l'inventaire.

La fonction à exécuter est de la forme suivante: `function ($action) use (...) { ... }`. La fonction là est à passer dans `HadesMenu::addTransactionListener(<function>)`.
Cette fonction doit renvoyer `true` ou `false`. Ce booléen décrit si la transaction peut avoir lieu (`true`) ou pas (`false`).

En gros, comme j'arrive pas bien à décrire, concrètement voici un exemple:

```php
$sword = ... // mon épée

$menu = Hades::createMenu(Hades::DOUBLE_CHEST);
$menu->addTransactionListener(function ($action) use ($sword) { // mettre dans le use () toutes les variables nécessaires
    // ici on va mettre le code pour faire en sorte que quand le joueur interagit il reçoit l'épée
});

$menu->show($player);
```

Dans cette fonction, l'argument `$action` est une HadesAction (une HadesAction = un changement entre un ancien objet et un nouvel dans un unique slot, par exemple "dans le slot 3 le totem devient de la terre", ou alors "dans le slot 10 de l'air devient un diamant"), et peut donner toutes les informations de l'interaction:

| Méthode             | Effet                                               | 
|---------------------|-----------------------------------------------------| 
| `getPlayer()`       | Renvoie le joueur faisant la transaction            |
| `getSlot()`         | Renvoie le slot dans lequel l'action a lieu         |
| `getSourceItem()`   | Renvoie l'objet ayant été remplacé                  |
| `getTargetItem()`   | Renvoie l'objet ayant remplacé                      |
| `getItems()`        | Renvoie une liste contenant les 2 objets précédents |
| `getItemsTypeIds()` | Renvoie une liste contenant les IDs des 2 objets    |
| `getInventory()`    | Renvoie l'inventaire du menu                        | 

Exemples d'utilisation:

```php
$menu->addTransactionListener(function ($action) {
    if ($action->getSlot() === 3) return false; // si un objet du slot 3 a été modifié (pris, placé ou modifié on s'en fout), alors la transaction est annulée.
    
    if (in_array(VanillaItems::APPLE()->getTypeId(), $action->getItemsTypeIds())) return false; // si l'un des deux objets est une pomme, c'est annulé aussi
    
    /*
     * Attention !
     * 
     * Dans le cas d'un échange entre 2 objets du HadesMenu, l'objet source et l'objet cible est confondu. En effet, il y aura 2 HadesAction: l'un pour la
     * transformation de Objet A vers Objet B dans un slot, puis un second pour la transformation de Objet B en Objet A dans le second slot.
     * 
     * Or, dans le cas où la transaction est entre l'inventaire du joueur et le HadesMenu, l'objet source et l'objet cible ne sont pas les mêmes. Comme il
     * n'y a pas de HadesActon pour l'inventaire du joueur, vous ne pouvez que observer l'Objet A en Objet B dans le HadesMenu. A est donc uniquement source,
     * et B uniquement cible.
     */
     
     if ($action->getSourceItem()->getTypeId() === VanillaItem::COAL()->getTypeId()) {
        /*
         * Le code ici sera exécuté s'il y a un échange entre un charbon dans le HadesMenu et un autre objet dans le HadesMenu, si un charbon du HadesMenu
         * est remplacé par un objet de l'inventaire (ou juste pris), mais pas si le joueur échange un charbon de son inventaire vers le HadesMenu (ou le
         * dépose juste), car seule la transformation dans le HadesMenu Objet A (ou air) -> Charbon est observée.
         */
     }
});
```

On peut utiliser `$action->closeMenu()` dans la fonction de `HadesMenu->addTransactionListener()` pour fermer le menu suite à une transaction. `HadesMenu::close($player)` est à éviter, c'est moins sécurisé.

On peut également directement appeler `HadesMenu::show($player)` pour un autre menu dans la transaction d'un menu, pour afficher immédiatement ce second menu. Il n'est pas nécessaire de fermer le premier menu, `show()` s'en chargera.

Dernière note importante: pour modifier le menu via un listener (comme ici ou comme le point 2. ci-dessous), il faut impérativement modifier `$action->getInventory()`, qui renvoie un inventaire pouvant être modifié comme un inventaire vanilla. Ne SURTOUT pas utiliser `$menu->setItem()` autre part que juste après avoir créé le menu.

### 2. Lors de la fermeture

Enfin, on peut exécuter une fonction lorsque le joueur ferme le menu. Pour ce faire, il faut appeler `HadesMenu::addCloseListener()`, et lui passer en argument une fonction du type `function ($player, $inventory) use (...) { .. }`, où `$player` représente le joueur et `$inventory` l'inventaire du menu.

Exemple:

```php
$menu->addCloseListener(function ($player, $inventory) {
    // fonction qui redonne tous les objets du menu au joueur lors de la fermeture
    $items = $inventory->getContents();
    foreach ($items as $item) {
        $player->getInventory()->addItem($item);
    }
});
```
