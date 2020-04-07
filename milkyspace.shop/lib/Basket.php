<?php

namespace milkyspace\shop;

use \Bitrix\Main\Service\GeoIp\Manager;

class Basket
{
    const SESSION_KEY = 'milkyspace_BASKET';

    private $_basket;
    private $_price_code = 'PRICE';

    private function key($productID)
    {
        return \md5($productID);
    }

    public function productPrice($productID)
    {
        if (empty($productID)) {
            return null;
        }

        $product = \CIBlockElement::GetList([], [
            'ID' => $productID,
        ], false, false, [
            'ID',
            'IBLOCK_ID',
            'PROPERTY_' . $this->_price_code,
        ])->GetNext(false, false) ?: null;

        if ($product === null) {
            return null;
        }

        $price = (float)$product["PROPERTY_{$this->_price_code}_VALUE"];

        return $price;
    }

    public function get()
    {
        if ($this->_basket === null) {
            $this->_basket = $_SESSION[self::SESSION_KEY] ?: [];

            foreach ($this->_basket as $key => &$item) {
                $item['PRICE'] = $this->productPrice($item['PRODUCT_ID'], $item['DETAILS'] ?: []);
            }

            unset($item);

            $this->set($this->_basket);
        }

        return $this->_basket;
    }

    private function set($basket = [])
    {
        $_SESSION[self::SESSION_KEY] = $this->_basket = $basket;
    }

    public function add($productID, $count = 1)
    {
        global $USER;
        $userId = \md5($USER->GetID()) ?: \md5(Manager::getRealIp());

        $milkBask = ShopBasketTable::getList(array(
            'filter' => array('USER' => $userId)
        ));
        while ($row = $milkBask->fetch())
        {
            $milkBasket[] = $row;
        }

        if ($milkBasket == null):
            ShopBasketTable::add(array(
                'USER' => $userId,
                'DATE' => new \Bitrix\Main\Type\Date('2020-04-07', 'Y-m-d')
            ));
        endif;

        $productID = (int)$productID;
        $count = (int)$count;

        if ($count <= 0) {
            return null;
        }

//        $basket = $this->get();
        $key = $this->key($productID);

        $basket[$key] = [
            'PRODUCT_ID' => $productID,
            'PRICE' => $this->productPrice($productID),
            'COUNT' => $count,
        ];

        $this->set($basket);

        return $key;
    }

    public function remove($key)
    {
        $basket = $this->get();

        unset($basket[$key]);

        $this->set($basket);
    }

    public function drop()
    {
        $this->set([]);
    }

}