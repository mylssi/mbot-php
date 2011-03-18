<?PHP
/*!
* @author Teemu Eskelinen
*/
//define error codes
//socket handler errors
define(SOCKET_HANDLER_CANT_FORK, 101);
//socket errors 200
define(SOCKET_CANT_CREATE, 201);
define(SOCKET_CANT_CONNECT, 202);
//server errors 300
define(SERVER_NO_SOCKET, 301);
define(SERVER_NO_CHANNEL, 302);
define(SERVER_DUPE_CHANNEL, 303);
define(SERVER_TOO_MANY_RECONNECTIONS, 304);
//channel errors 400
define(CHANNEL_NO_SERVER, 401);
define(CHANNEL_DUPE_USER, 402);
//user errors 500
define(USER_NO_CHANNEL, 501);
//settings errors 600
define(SETTINGS_CANT_LOAD, 601);
define(SETTINGS_BADLY_FORMATTED, 602);
//plugin/service errors 700
define(PLUGIN_ERROR, 701);
define(SERVICE_ERROR, 702);

/*!
* IrcException just a different name for default exception implementation.
* This enables us to catch them separetly from other exceptions.
*/
class IrcException extends Exception {
}
?>
