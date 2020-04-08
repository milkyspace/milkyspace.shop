<?php

namespace milkyspace\shop;

use Bitrix\Main\Entity;

class BasketProductUsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return "shop_bask_prod";
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\IntegerField('PRODUCT_ID'),
            new Entity\ReferenceField(
                'PRODUCT',
                '\milkyspace\shop\ShopProductTable',
                array('=this.PRODUCT_ID' => 'ref.ID')
            ),
            new Entity\IntegerField('BASKET_ID'),
            new Entity\ReferenceField(
                'BASKET',
                '\milkyspace\shop\ShopBasketTable',
                array('=this.BASKET_ID' => 'ref.ID')
            ),
            new Entity\IntegerField('COUNT'),
        );
    }
}