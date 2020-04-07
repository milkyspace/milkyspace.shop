<?

use Bitrix\Main\Service\GeoIp\Manager;
use milkyspace\shop as milkyspaceShop;
use \Bitrix\Main\Loader;

if (Loader::IncludeModule('milkyspace.shop') === false) {
    $this->__showError("'milkyspace.shop' module is not included");
    return;
}
if (Loader::IncludeModule('iblock') === false || Loader::IncludeModule('main') === false) {
    return;
}

class Showbasket extends CBitrixComponent
{
    public function show($now_basket)
    {
        echo '<pre>' . print_r($now_basket, true) . '</pre>';
    }

    public function executeComponent()
    {
        $basket = new \milkyspace\shop\Basket();
        $basket->add(23);
        $now_basket = $basket->get();

        global $USER;
        $userId = \md5($USER->GetID()) ?: \md5(Manager::getRealIp());
        $now_basket = milkyspaceShop\ShopBasketTable::getList(array(
            'filter' => array('USER' => $userId)
        ))->fetchAll() ?: null;

        $this->show($now_basket);
    }
}







