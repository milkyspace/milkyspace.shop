<?php

namespace milkyspace\shop;

use Bitrix\Main\Entity;

class ShopProductTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return "shop_product";
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('NAME', array(
                'required' => true,
            )),
            new Entity\DateTimeField('DATE', array(
                'default_value' => new \Bitrix\Main\Type\DateTime(),
            )),
            new Entity\IntegerField('PRODUCT', array(
                'required' => true,
                'validation' => function () {
                    return array(
                        new Entity\Validator\Unique,
                    );
                }
            )),
            new Entity\IntegerField('COUNT', array(
                'required' => true,
            )),
            new Entity\IntegerField('PRICE', array(
                'required' => true,
            )),
            new Entity\StringField('USER'),
            new Entity\ReferenceField(
                'BASKET',
                '\milkyspace\shop\ShopBasketTable',
                array('=this.USER' => 'ref.USER')
            )
        );
    }
}