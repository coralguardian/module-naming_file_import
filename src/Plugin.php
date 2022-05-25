<?php

namespace D4rk0snet\NamingFileImport;

use D4rk0snet\NamingFileImport\API\GetNamingFileEndPoint;
use D4rk0snet\NamingFileImport\API\ReceiveNamingFileEndPoint;

class Plugin
{
    public static function launchActions()
    {
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new GetNamingFileEndPoint());
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new ReceiveNamingFileEndPoint());
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new \D4rk0snet\NamingFileImport\API\Admin\GetRecipientsFileEndPoint());
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new \D4rk0snet\NamingFileImport\API\Admin\GetNamingFileEndPoint());
    }
}