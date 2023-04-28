<?php

namespace D4rk0snet\NamingFileImport\API;

use D4rk0snet\NamingFileImport\Service\RecipientFileService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use WP_REST_Request;
use WP_REST_Response;

class ReceiveRecipientsFileEndPoint extends APIEnpointAbstract
{
    private const ADOPTION_UUID_PARAM = 'uuid';

    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        if (!isset($_FILES['recipient_file'])) {
            return new WP_REST_Response("missing file", 400);
        }

        $adoptionUuid = $request->get_param(self::ADOPTION_UUID_PARAM);

        try {
            $filename = tempnam("/tmp", "") . ".xlsx";
            move_uploaded_file($_FILES['recipient_file']["tmp_name"], $filename);
            $sendOn = $_POST['sendOn'] ? new \DateTime($_POST['sendOn']) : null;
            RecipientFileService::importDataFromFile($adoptionUuid, $filename, $sendOn, $_POST['message'] ?? null);

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
        return "adoption/(?P<".self::ADOPTION_UUID_PARAM.">[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12})/recipientsFile";
    }
}