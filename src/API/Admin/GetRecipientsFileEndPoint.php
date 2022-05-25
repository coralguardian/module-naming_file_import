<?php

namespace D4rk0snet\NamingFileImport\API\Admin;

use D4rk0snet\Coralguardian\Enums\Language;
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
            $filename = RecipientFileService::fillFileAccordingToAdoption(null, Language::FR);

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
        if(current_user_can('manage_options')) {
            return "__return_true";
        }

        return "__return_false";
    }

    public static function getEndpoint(): string
    {
        return "admin/recipientsFile";
    }
}