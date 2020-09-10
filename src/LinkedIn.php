<?php

namespace Cdbeaton\Linkedin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LinkedIn
{
    static function isAuthorized()
    {
        if(LinkedIn::getToken()) { return true; }
        return false;
    }

    static function getToken()
    {
        return Cache::get('access_token');
    }

    static function postShare($url, $owner, $text=null, $image=null)
    {
        if(LinkedIn::isAuthorized()){
            $post_share_url = 'https://api.linkedin.com/v2/shares';

            if($image) {
                $resolvedUrl = $image;
                $thumbnails['resolvedUrl'] = $resolvedUrl;
                $contentEntities['thumbnails'] = [$thumbnails];
            }

            $entityLocation = $url;
            $contentEntities['entityLocation'] = $entityLocation;
            $content['$contentEntities'] = [$contentEntities];
            $distribution['linkedInDistributionTarget'] = (object) null;
            $data['content'] = $content;
            $data['owner'] = $owner;
            $data['distribution'] = $distribution;

            if($text) {
                $t['text'] = $text;
                $data['text'] = $t;
            }

            Log::info($data);
            $response = Http::withToken(LinkedIn::getToken())->post($post_share_url, $data);

            if($response->successful()) {
                return true;
            } else {
                // Log error message and return it
                $error = $response->getBody();
                Log::error($error);
                return false;
            }
        } else {
            // TODO: Prompt for authorization
        }
    }
}
