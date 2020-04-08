<?

use Bitrix\Main\Service\GeoIp\Manager;
use \Bitrix\Main\Loader;
use \milkyspace\shop\Basket;

class Showbasket extends CBitrixComponent
{
    protected function checkModules()
    {
        if (!Loader::IncludeModule('milkyspace.shop') || !Loader::IncludeModule('iblock')
            || !Loader::IncludeModule('main')):
            throw new \Bitrix\Main\LoaderException('Модуль не подключен');
        endif;
    }

    public function show($now_basket)
    {
        echo '<pre>' . print_r($now_basket, true) . '</pre>';
    }

    public function executeComponent()
    {
        try {
            $this->checkModules();
            $action = new \milkyspace\shop\Basket();
            $basket = $action->addBasket();
            $this->arResult = $basket;
            $this->show($this->arResult);
            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            ShowError($e->getMessage());
        }
    }
}