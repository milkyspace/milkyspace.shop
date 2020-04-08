<?php

if (!check_bitrix_sessid()) return;

?>
<form action="<?=$APPLICATION->GetCurPage()?>">
<?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="hidden" name="id" value="milkyspace.shop">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <label for="table">Удалить таблицы модуля в базе данных?</label>
    <input type="checkbox" id="table" name="table" checked>
    <input type="submit" value="Удалить">
</form>
