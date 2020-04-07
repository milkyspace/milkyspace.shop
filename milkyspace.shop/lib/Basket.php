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

    public function addProductFields(&$item)
    {
        $item['IMG'] = $item['IMG'] ?: null;
        $item['NAME'] = $item['NAME'] ?: null;
        $item['DETAIL_PAGE_URL'] = $item['DETAIL_PAGE_URL'] ?: null;

        $productID = $item['PRODUCT_ID'];

        if (empty($productID)) {
            return null;
        }

        $product = \CIBlockElement::GetList([], [
            'ID' => $productID,
        ], false, false, [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'DETAIL_PICTURE',
            'DETAIL_PAGE_URL',
            'PROPERTY_TYPE',
        ])->GetNext(false, false) ?: null;

        $item['NAME'] = $product['NAME'];
        $item['DETAIL_PAGE_URL'] = $product['DETAIL_PAGE_URL'];

        if (!empty($product['DETAIL_PICTURE'])) {
            $file = \CFile::GetFileArray($product['DETAIL_PICTURE']);
            $item['IMG'] = $file['SRC'];

            if (\mb_stripos($file['SRC'], '.gif') === false) {
                $thumb = \CFile::ResizeImageGet($file, ['width' => 240, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                $item['IMG'] = $thumb['src'];
            }
        }
    }

    public $last_error;
    
    public function save($userID = null, $username = null, $phone = null, $email = null, $details = [], $fileList = [])
    {
        global $DB;

        $this->last_error = null;

        $basket = $this->get();

        if (empty($basket)) {
            $this->last_error = 'Корзина пуста';

            return null;
        }

        $saleID = $DB->Insert('original_sale', [
            'USER_ID' => $userID ?: 'NULL',
            'PRICE' => $this->price(),
            'USERNAME' => $username ? "'" . $DB->db_Conn->escape_string($username) . "'" : 'NULL',
            'PHONE' => $phone ? "'" . $DB->db_Conn->escape_string($phone) . "'" : 'NULL',
            'EMAIL' => $email ? "'" . $DB->db_Conn->escape_string($email) . "'" : 'NULL',
            'DETAILS' => "'" . $DB->db_Conn->escape_string(\json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "'",
        ]);

        if (empty($saleID)) {
            $this->last_error = 'Ошибка при сохранении заказа';

            return null;
        }

        foreach ($fileList as $fileID) {
            $DB->Insert('original_sale_files', [
                'SALE_ID' => $saleID,
                'FILE_ID' => $fileID,
            ]);
        }

        foreach ($basket as $item) {
            $DB->Insert('original_sale_basket', [
                'SALE_ID' => $saleID,
                'PRODUCT_ID' => $item['PRODUCT_ID'],
                'PRICE' => $item['PRICE'],
                'COUNT' => $item['COUNT'],
                'DETAILS' => "'" . $DB->db_Conn->escape_string(\json_encode($item['DETAILS'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "'",
            ]);
        }

        $this->drop();

        $events = GetModuleEvents(\Original_SimpleShop::MODULE_ID, 'OnSaleCreate', true) ?: [];

        foreach ($events as $event) {
            ExecuteModuleEventEx($event, [$saleID]);
        }

        return $saleID;
    }

    public function find($saleID, $prepareForRender = false)
    {
        global $DB;

        $saleID = (int)$saleID;
        $sql = <<<SQL
SELECT 
    `original_sale`.`ID`                          AS `ID`,
    `original_sale`.`USER_ID`                     AS `USER_ID`,
    `original_sale`.`PRICE`                       AS `PRICE`,
    `original_sale`.`USERNAME`                    AS `USERNAME`,
    `original_sale`.`PHONE`                       AS `PHONE`,
    `original_sale`.`EMAIL`                       AS `EMAIL`,
    `original_sale`.`DETAILS`                     AS `DETAILS`,
    `original_sale`.`CREATED_AT`                  AS `CREATED_AT`,
    GROUP_CONCAT(`original_sale_files`.`FILE_ID`) AS `FILES`

FROM `original_sale`

LEFT OUTER JOIN `original_sale_files`
    ON `original_sale_files`.`SALE_ID` = `original_sale`.`ID`

WHERE `ID` = {$saleID}

GROUP BY `original_sale`.`ID`
SQL;

        $sale = $DB->Query($sql)->GetNext(true, true) ?: null;

        if ($sale === null) {
            return null;
        }

        $sale['DETAILS'] = \json_decode($sale['~DETAILS'] ?: '[]', true) ?: [];
        $sale['BASKET'] = [];

        $files = \array_unique(\array_map('\intval', \array_filter(\explode(',', $sale['FILES']))));
        $sale['FILES'] = [];

        if (\count($files) > 0) {
            foreach ($files as $fileID) {
                $sale['FILES'][] = \CFile::GetFileArray($fileID);
            }
        }

        $sale['FILES'] = \array_filter($sale['FILES']);

        $sql = <<<SQL
SELECT
    `original_sale_basket`.`ID`            AS `ID`,
    `original_sale_basket`.`SALE_ID`       AS `SALE_ID`,
    `original_sale_basket`.`PRODUCT_ID`    AS `PRODUCT_ID`,
    `b_iblock_element`.`IBLOCK_ID`         AS `PRODUCT_IBLOCK_ID`,
    `b_iblock_element`.`IBLOCK_SECTION_ID` AS `PRODUCT_IBLOCK_SECTION_ID`,
    `b_iblock_element`.`NAME`              AS `PRODUCT_NAME`,
    `original_sale_basket`.`PRICE`         AS `PRICE`,
    `original_sale_basket`.`COUNT`         AS `COUNT`,
    `original_sale_basket`.`DETAILS`       AS `DETAILS`

FROM `original_sale_basket`

INNER JOIN `b_iblock_element` 
    ON `b_iblock_element`.`ID` = `original_sale_basket`.`PRODUCT_ID`

WHERE `SALE_ID` = {$saleID}
SQL;

        $result = $DB->Query($sql);

        while ($row = $result->GetNext(true, true)) {
            $row['DETAILS'] = \json_decode($row['~DETAILS'] ?: '[]', true) ?: [];

            if ($prepareForRender) {
                $events = GetModuleEvents(\Original_SimpleShop::MODULE_ID, 'OnRenderSaleProductDetails', true);

                foreach ($events as $event) {
                    ExecuteModuleEventEx($event, [(int)$row['PRODUCT_ID'], (int)$row['PRODUCT_IBLOCK_ID'], &$row['DETAILS']]);
                }
            }

            $sale['BASKET'][] = $row;
        }

        return $sale;
    }

}