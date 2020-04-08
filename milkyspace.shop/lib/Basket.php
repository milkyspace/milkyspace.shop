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

    private function userId()
    {
        global $USER;
        return $userId = \md5($USER->GetID()) ?: \md5(Manager::getRealIp());
    }

    public function addBasket($returnId = false)
    {
        $milkBask = ShopBasketTable::getList(array(
            'filter' => array('USER' => $this->userId())
        ))->fetch() ?: null;

        if ($milkBask == null):
            $result = ShopProductTable::add(array(
                'USER_ID' => $this->userId(),
            ));
            if ($returnId) return $result->getId();
            return null;
        endif;
        $basket = BasketProductUsTable::getList(array(
            'filter' => array('BASKET_ID' => $milkBask['ID']),
        ));
        if ($returnId) return $milkBask['ID'];
        return $basket->fetchAll();
    }

    public function add($productID, $count = 1)
    {
        $productID = (int)$productID;
        $count = (int)$count;

        if ($count <= 0) {
            return null;
        }
        $basket = $this->addBasket(true);
        $product = BasketProductUsTable::getList(array(
            'filter' => array('BASKET_ID' => $basket, 'PRODUCT_ID' => $productID),
        ))->fetch() ?: false;
        if (!$product)
            $product = BasketProductUsTable::add(array(
                'BASKET_ID' => $basket,
                'PRODUCT_ID' => $productID,
                'COUNT' => new \Bitrix\Main\DB\SqlExpression('?# + '.$count,'COUNT')
            ));

        $price = $this->productPrice($productID);

        return false;
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

        $saleID = $DB->Insert('milkyspace_order', [
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
            $DB->Insert('milkyspace_order_files', [
                'SALE_ID' => $saleID,
                'FILE_ID' => $fileID,
            ]);
        }

        foreach ($basket as $item) {
            $DB->Insert('milkyspace_order_basket', [
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
    `milkyspace_order`.`ID`                          AS `ID`,
    `milkyspace_order`.`USER_ID`                     AS `USER_ID`,
    `milkyspace_order`.`PRICE`                       AS `PRICE`,
    `milkyspace_order`.`USERNAME`                    AS `USERNAME`,
    `milkyspace_order`.`PHONE`                       AS `PHONE`,
    `milkyspace_order`.`EMAIL`                       AS `EMAIL`,
    `milkyspace_order`.`DETAILS`                     AS `DETAILS`,
    `milkyspace_order`.`CREATED_AT`                  AS `CREATED_AT`,
    GROUP_CONCAT(`milkyspace_order_files`.`FILE_ID`) AS `FILES`

FROM `milkyspace_order`

LEFT OUTER JOIN `milkyspace_order_files`
    ON `milkyspace_order_files`.`SALE_ID` = `milkyspace_order`.`ID`

WHERE `ID` = {$saleID}

GROUP BY `milkyspace_order`.`ID`
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
    `milkyspace_order_basket`.`ID`            AS `ID`,
    `milkyspace_order_basket`.`SALE_ID`       AS `SALE_ID`,
    `milkyspace_order_basket`.`PRODUCT_ID`    AS `PRODUCT_ID`,
    `b_iblock_element`.`IBLOCK_ID`         AS `PRODUCT_IBLOCK_ID`,
    `b_iblock_element`.`IBLOCK_SECTION_ID` AS `PRODUCT_IBLOCK_SECTION_ID`,
    `b_iblock_element`.`NAME`              AS `PRODUCT_NAME`,
    `milkyspace_order_basket`.`PRICE`         AS `PRICE`,
    `milkyspace_order_basket`.`COUNT`         AS `COUNT`,
    `milkyspace_order_basket`.`DETAILS`       AS `DETAILS`

FROM `milkyspace_order_basket`

INNER JOIN `b_iblock_element` 
    ON `b_iblock_element`.`ID` = `milkyspace_order_basket`.`PRODUCT_ID`

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