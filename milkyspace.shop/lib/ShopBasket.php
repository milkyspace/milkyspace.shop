<?php

namespace milkyspace\shop;

use Bitrix\Main\Entity;

class ShopBasketTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return "shop_basket";
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('USER'),
            new Entity\DateField('DATE')
        );
    }
}