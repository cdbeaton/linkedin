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

    static function postShare($owner, $text=null, $url=null, $image=null)
    {
        if(LinkedIn::isAuthorized()){
            $post_share_url = 'https://api.linkedin.com/v2/shares';

            if($text) {
                $t['text'] = $text;
                $data['text'] = $t;
            }

            if($url) {
                $entityLocation = $url;
                $contentEntities['entityLocation'] = $entityLocation;

                if($image) {
                    $resolvedUrl = $image;
                    $thumbnails['resolvedUrl'] = $resolvedUrl;
                    $contentEntities['thumbnails'] = [$thumbnails];
                }

                $content['contentEntities'] = [$contentEntities];
                $data['content'] = $content;
            }

            $data['owner'] = $owner;
            $distribution['linkedInDistributionTarget'] = (object) null;
            $data['distribution'] = $distribution;

            $response = Http::withToken(LinkedIn::getToken())->post($post_share_url, $data);

            if($response->successful()) {
                return true;
            } else {
                // Log error message and return false
                $error = $response->getBody();
                Log::error($error);
                Log::info($data);
                return false;
            }
        } else {
            // TODO: Better error catching
            Log::error('LinkedIn: Cannot post shares without being authorized.');
        }
    }
}
