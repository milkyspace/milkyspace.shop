<?php

if (!check_bitrix_sessid()) return;

if ($error = $APPLICATION->GetException())
    echo CAdminMessage::ShowMessage(array(
        "TYPE" => "ERROR",
        "MESSAGE" => "Есть ошибки",
        "DETAILS" => $ex->GetString(),
        "HTML" => true
    ));

else
    CAdminMessage::ShowNote("Модуль удален");