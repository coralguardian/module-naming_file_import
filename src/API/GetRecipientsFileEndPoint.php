<?php

namespace D4rk0snet\NamingFileImport\API;

use D4rk0snet\NamingFileImport\Service\RecipientFileService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use WP_REST_Request;
use WP_REST_Response;

class GetRecipientsFileEndPoint extends APIEnpointAbstract
{
    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $filename = RecipientFileService::fillFileAccordingToAdoption($request->get_param('adoption_uuid'));

            return APIManagement::APIClientDownloadWithURL($filename, 'recipient_file.xlsx');
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), $exception->getCode());
        }
    }

    public static function getMethods(): array
    {
        return ['GET'];
    }

    public static function getPermissions(): string
    {
        return "__return_true";
    }

    public static function getEndpoint(): string
    {
        return "recipientsFile";
    }
}