<?php

namespace Cdbeaton\Linkedin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class LinkedIn
{
    static function postShare($content, $owner, $text=null)
    {
        // Check if we already have a valid access token
        $authorized = Cache::get('access_token');

        if($authorized){
            $post_share_url = 'https://api.linkedin.com/v2/shares';

            if(!is_array($content)){
                // If $content is not an array, assume it is a URL
                $content = array([
                    'contentEntities' => array([
                        'entityLocation' => $content
                    ])
                ]);
            }

            $response = Http::withToken($authorized)->asForm()->post($post_share_url, [
                'content' => $content,
                'owner' => $owner,
                'text' => array(['text' => $text])
            ]);

            if($response->successful()) {
                return true;
            } else {
                // Log error message and return it
                $error = $response->getBody();
                Log::error($error);
                return $error;
            }
        } else {
            // TODO: Prompt for authorization
        }
    }
}
