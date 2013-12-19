#!/usr/bin/php -d display_errors=yes
<?PHP
//example config
require_once('inc/ircbot.php');
require_once('inc/ircserverdebughandler.php');
require_once('inc/ircpluginsystemhandler.php');
require_once('inc/ircadminmsghandler.php');
require_once('inc/ircsettingsfilebackend.php');
require_once('inc/ircautoop.php');
require_once('services/vfs.php');
require_once('services/download_vfs.php');
IrcSettings::GetInstance()->SetBackend(new IrcSettingsFileBackend("settings.dat"));
$bot = new IrcBot;
$server = new IrcServer('ircnet.eversible.com', 6667, 'IrcNet');
$chan = new IrcChannel('#mbot');
$plugins = new IrcPluginSystemHandler(getcwd().'/plugins');
$vfs = new MbotVfs();
$download_vfs = new MbotDownloadVfs();
$download_vfs->AddChannel('IrcNet', '#mbot');
$admin_msg->AddAdmin('*!*@mylssi.tontut.fi');
$chan->AddHandler($admin_msg);
$chan->AddHandler($plugins);
$server->AddChannel($asema);
$server->SetNick('mbot');
//debug
$server->AddHandler(new IrcServerDebugHandler);
$server->AddHandler(new IrcAutoOp);
$bot->AddServer($server);
$bot->AddService($vfs);
$bot->AddService($download_vfs);
//run bot
$bot->MainLoop();
?>
