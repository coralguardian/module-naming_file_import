<?php

namespace D4rk0snet\NamingFileImport\API;

use D4rk0snet\NamingFileImport\Service\RecipientFileService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use WP_REST_Request;
use WP_REST_Response;

class ReceiveRecipientsFileEndPoint extends APIEnpointAbstract
{
    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        if (!isset($_FILES['recipient_file'])) {
            return new WP_REST_Response("missing file", 400);
        }

        try {
            $filename = tempnam("/tmp", "") . ".xlsx";
            move_uploaded_file($_FILES['recipient_file']["tmp_name"], $filename);

            RecipientFileService::importDataFromFile($filename);
            return APIManagement::APIOk();
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), $exception->getCode());
        }
    }

    public static function getMethods(): array
    {
        return ['POST'];
    }

    public static function getPermissions(): string
    {
        return "__return_true";
    }

    public static function getEndpoint(): string
    {
        return "recipientFileImport";
    }
}