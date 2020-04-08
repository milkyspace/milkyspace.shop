<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

if (class_exists("milkyspace_shop"))
    return;

class milkyspace_shop extends CModule
{
    const MODULE_ID = 'milkyspace.shop';
    const MODULE_NAME = 'Магазин milkyspace';
    const MODULE_DESCRIPTION = 'Магазин для редакции "Старт"';

    public $MODULE_ID = self::MODULE_ID;
    public $MODULE_NAME = self::MODULE_NAME;
    public $MODULE_DESCRIPTION = self::MODULE_DESCRIPTION;
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2020-04-07';

    private static function isVersionD7()
    {
        return CheckVersion(Bitrix\Main\ModuleManager::getVersion("main"), "14.00.00");
    }

    private static function isLocal()
    {
        $moduleID = self::MODULE_ID;

        return is_file($_SERVER['DOCUMENT_ROOT'] . "/local/modules/{$moduleID}/install/index.php");
    }

    private function links()
    {
        $mid = self::MODULE_ID;

        if (self::isLocal()) {
            @\mkdir($_SERVER['DOCUMENT_ROOT'] . '/local/components', BX_DIR_PERMISSIONS, true);

            return [
                "/local/components/{$mid}" => "../modules/{$mid}/install/components/milkyspace",
            ];
        }

        return [
            "/bitrix/components/{$mid}" => "../modules/{$mid}/install/components/milkyspace",
        ];
    }

    function InstallDB()
    {
        Loader::includeModule(self::MODULE_ID);
        if (!\Bitrix\Main\Application::getConnection(milkyspace\shop\ShopBasketTable::getConnectionName())->isTableExists(
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\ShopBasketTable')->getDBTableName())):
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\ShopBasketTable')->createDbTable();
        endif;
        if (!\Bitrix\Main\Application::getConnection(milkyspace\shop\ShopProductTable::getConnectionName())->isTableExists(
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\ShopProductTable')->getDBTableName())):
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\ShopProductTable')->createDbTable();
        endif;
        if (!\Bitrix\Main\Application::getConnection(milkyspace\shop\ShopProductTable::getConnectionName())->isTableExists(
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\BasketProductUsTable')->getDBTableName())):
            \Bitrix\Main\Entity\Base::getInstance('milkyspace\shop\BasketProductUsTable')->createDbTable();
        endif;
    }

    function UnInstallDB()
    {
        Loader::includeModule(self::MODULE_ID);
        \Bitrix\Main\Application::getConnection(\milkyspace\shop\ShopBasketTable::getConnectionName())->queryExecute(
            "DROP TABLE IF EXISTS ".\Bitrix\Main\Entity\Base::getInstance(
                '\milkyspace\shop\ShopBasketTable')->getDBTableName());
        \Bitrix\Main\Application::getConnection(\milkyspace\shop\ShopProductTable::getConnectionName())->queryExecute(
            "DROP TABLE IF EXISTS ".\Bitrix\Main\Entity\Base::getInstance(
                '\milkyspace\shop\ShopBasketTable')->getDBTableName());
        \Bitrix\Main\Application::getConnection(\milkyspace\shop\BasketProductUsTable::getConnectionName())->queryExecute(
            "DROP TABLE IF EXISTS ".\Bitrix\Main\Entity\Base::getInstance(
                '\milkyspace\shop\BasketProductUsTable')->getDBTableName());
        \Bitrix\Main\Config\Option::delete(self::MODULE_ID);

        global $DB;
        $DB->RunSQLBatch(__DIR__ . '/uninstall.sql');
    }

    function InstallEvents()
    {
//        \Bitrix\Main\ORM\EventManager::getInstance()->registerEventHandler(self::MODULE_ID, 'milkyspace\shop\Orm::OnBeforeAdd', self::MODULE_ID, 'milkyspace\shop\Event', 'event');
        return true;
    }

    function UnInstallEvents()
    {
//        \Bitrix\Main\ORM\EventManager::getInstance()->unRegisterEventHandler(self::MODULE_ID, 'milkyspace\shop\Orm::OnBeforeAdd', self::MODULE_ID, 'milkyspace\shop\Event', 'event');
        return true;
    }

    function InstallFiles($arParams = array())
    {
        foreach ($this->links() as $link => $target) {
            @\symlink($target, $_SERVER['DOCUMENT_ROOT'] . $link);
        }
    }

    function UnInstallFiles()
    {
        foreach ($this->links() as $link => $target) {
            @\unlink($_SERVER['DOCUMENT_ROOT'] . $link);
        }
    }

    function DoInstall()
    {
        global $APPLICATION;
        if ($this->isVersionD7()) {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $GLOBALS["errors"] = $this->errors;
        } else {
            $APPLICATION->ThrowException('Ошибка. Нет поддержки D7.');
        }
        $APPLICATION->IncludeAdminFile('Установка milkyspace.shop', __DIR__ . '/step.php');
    }

    function DoUninstall()
    {
        global $APPLICATION;
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        if (!$request['step'] || $request['step'] < 2):
            $APPLICATION->IncludeAdminFile('Удаление milkyspace.shop', __DIR__ . '/unstep.php');
        elseif ($request['step'] == 2):
            $this->UnInstallEvents();
            $this->UnInstallFiles();

            //if ($request['table'] == 'Y'):
                $this->UnInstallDB();
            //endif;
            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile('Удаление milkyspace.shop', __DIR__ . '/unstep2.php');
        endif;
    }
}

?>
