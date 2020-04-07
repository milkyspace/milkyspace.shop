<?

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists("milkyspace_shop"))
    return;

class milkyspace_shop extends CModule
{
    const MODULE_ID = 'milkyspace.shop';
    const MODULE_NAME = 'Магазин';
    const MODULE_DESCRIPTION = 'Магазин для редакции "Старт"';

    public $MODULE_ID = self::MODULE_ID;
    public $MODULE_NAME = self::MODULE_NAME;
    public $MODULE_DESCRIPTION = self::MODULE_DESCRIPTION;
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2020-04-07';

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
        \Bitrix\Main\Application::getConnection()->queryExecute("CREATE TABLE IF NOT EXISTS shop_basket(
ID int NOT NULL AUTO_INCREMENT,
USER varchar(255) NOT NULL,
DATE date NOT NULL,
PRIMARY KEY(ID))"
        );
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        return true;
    }

    function UnInstallDB()
    {
        \Bitrix\Main\Application::getConnection()->queryExecute("DROP TABLE IF EXISTS shop_basket");
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
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
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        $GLOBALS["errors"] = $this->errors;
    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}

?>
