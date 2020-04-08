<?

use Bitrix\Main\Service\GeoIp\Manager;
use milkyspace\shop as milkyspaceShop;
use \Bitrix\Main\Loader;

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
            $basket = new \milkyspace\shop\Basket();
            $resultAdd = $basket->add(23);
            if ($resultAdd != null):
                if ($resultAdd->isSuccess()) echo 'Товар добавлен с id: ' . $resultAdd->getId().' Количество: ';
                print_r($resultAdd->getData()['COUNT']);
            endif;
            global $USER;
            $userId = \md5($USER->GetID()) ?: \md5(Manager::getRealIp());
            $now_basket = milkyspaceShop\ShopBasketTable::getList(array(
                'filter' => array('USER' => $userId)
            ))->fetchAll() ?: null;

            $this->show($now_basket);
            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            ShowError($e->getMessage());
        }
    }
}