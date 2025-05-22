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

    static function postShare($author, $commentary=null, $url=null, $image=null, $title=null)
    {
        if(LinkedIn::isAuthorized()){
            $post_share_url = 'https://api.linkedin.com/rest/posts';

            $data['author'] = $author;
            if($commentary) { $data['commentary'] = $commentary; }
            $data['visibility'] = 'PUBLIC';
            $data['distribution'] = [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => []
            ];

            // Upload any thumbnail to LinkedIn using the Images API
            if ($image) {
                $initialise_image_url = 'https://api.linkedin.com/rest/images?action=initializeUpload';
                $initialise_image_response = Http::withToken(LinkedIn::getToken())->post(
                    $initialise_image_url,
                    ['initializeUploadRequest' => ['owner' => $author]]
                );
                $initialise_image_data = json_decode($initialise_image_response->getBody());

                if (property_exists($initialise_image_data, 'value')) {
                    $image_upload_url = $initialise_image_data->uploadUrl;
                    $image_urn = $initialise_image_data->image;
                    $image_get = Http::get($image);
                    if ($image_get->successful()) {
                        $image_body = (string) $image_get->body();
                        $image_upload = Http::withBody($image_body, 'application/zip')
                            ->withToken(LinkedIn::getToken())
                            ->put($image_upload_url);

                        if (!$image_upload->successful()) {
                            $image_urn = null;
                            Log::error('[LinkedIn] Could not successfully upload image: '.$image_upload->body());
                        }
                    } else {
                        Log::error('[LinkedIn] Could not get image data from URL: '.$image);
                    }
                } else {
                    Log::error('[LinkedIn] Received unexpected response to image upload initialisation: '.var_dump($initialise_image_data));
                }
            }

            // Add link title and description
            if($url) {
                $article['source'] = $url;
                if ($image_urn) { $article['thumbnail'] = $image_urn; }
                $article['title'] = $title;
                $article['description'] = $commentary;
                $data['content'] = ['article' => $article];
            }

            // Make API call
            $response = Http::withHeaders(['X-Restli-Protocol-Version' => '2.0.0', 'LinkedIn-Version' => '202505'])
                ->withToken(LinkedIn::getToken())
                ->post($post_share_url, $data);

            // Check if successful
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
            Log::error('[LinkedIn] Cannot post shares without being authorised.');
        }
    }
}
