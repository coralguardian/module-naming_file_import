<?php

namespace D4rk0snet\NamingFileImport\API;

use D4rk0snet\NamingFileImport\Service\NamingFileService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use WP_REST_Request;
use WP_REST_Response;

class ReceiveNamingFileEndPoint extends APIEnpointAbstract
{
    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), false, 512, JSON_THROW_ON_ERROR);
        if (!isset($_FILES['adoption_file'])) {
            return new WP_REST_Response("missing file", 400);
        }

        if ($payload === null) {
            return APIManagement::APIError("Invalid body content", 400);
        }

        try {
            $filename = tempnam("/tmp", "") . ".xlsx";
            move_uploaded_file($_FILES['adoption_file']["tmp_name"], $filename);

            NamingFileService::importDataFromFile($filename);
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
        return "namingFileImport";
    }
}