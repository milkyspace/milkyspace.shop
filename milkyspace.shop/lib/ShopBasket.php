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
            new Entity\StringField('USER', array(
                'required' => true,
            )),
            new Entity\StringField('NAME', array(
                'required' => true,
            )),
            new Entity\DateTimeField('DATE',array(
                'default_value' => new \Bitrix\Main\Type\DateTime(),
            )),
            new Entity\IntegerField('PRODUCT', array(
                'required' => true,
            )),
            new Entity\IntegerField('COUNT', array(
                'required' => true,
            )),
        );
    }
}