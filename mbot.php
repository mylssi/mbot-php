#!/usr/bin/php -d display_errors=yes
<?PHP
require_once('inc/ircbot.php');
require_once('inc/ircserverdebughandler.php');
require_once('inc/ircpluginsystemhandler.php');
require_once('inc/ircadminmsghandler.php');
require_once('inc/ircsettingsfilebackend.php');
require_once('inc/ircautoop.php');
#require_once('services/epicannouncer.php');
require_once('services/vfs.php');
require_once('services/download_vfs.php');
$bot = new IrcBot;
$server = new IrcServer('ircnet.eversible.com', 6667, 'IrcNet');
$asema = new IrcChannel('#mylssi');
$plugins = new IrcPluginSystemHandler(getcwd().'/plugins');
$vfs = new MbotVfs();
$download_vfs = new MbotDownloadVfs();
$download_vfs->AddChannel('IrcNet', '#mylssi');
$admin_msg = new IrcAdminMsgHandler;
$admin_msg->AddAdmin('*!taimy@*kapsi.fi');
$admin_msg->AddAdmin('*!*@mylssi.tontut.fi');
$admin_msg->AddAdmin('ZauGe!*@*dnainternet.fi');
$settings = new IrcSettings;
$settings->SetBackend(new IrcSettingsFileBackend("settings.dat"));
$asema->AddHandler($admin_msg);
$asema->AddHandler($plugins);
$server->AddChannel($asema);
$server->SetNick('perspillu');
//debug
$server->AddHandler(new IrcServerDebugHandler);
$server->AddHandler(new IrcAutoOp);
$bot->AddServer($server);
$bot->AddService($vfs);
$bot->AddService($download_vfs);
$bot->MainLoop();
?>
