<?php

namespace milkyspace\shop;

class Event
{
    static function event(\Bitrix\Main\Entity\Event $event)
    {
        $fields = $event->getParameter("fields");
        echo 'Сработало событие OnBeforeAdd';
        echo '<pre>' . print_r($fields, true) . '</pre>';
    }
}
