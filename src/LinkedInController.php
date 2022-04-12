<?php

namespace Cdbeaton\Linkedin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LinkedInController extends Controller
{
    public function authorization(Request $request){
        // Check if we already have a valid access token
        $authorized = Cache::get('access_token');

        if(!$authorized){
            // We will continue with the authentication process
            $authorization_url = 'https://www.linkedin.com/oauth/v2/authorization';

            $query_parts = array(
                'response_type' => 'code',
                'client_id' => config('linkedin.client_id'),
                'redirect_uri' => config('linkedin.redirect_uri'),
                'state' => config('linkedin.state'),
                'scope' => $request->get('scope')
            );

            $query = http_build_query($query_parts);
            return redirect($authorization_url.'?'.$query);
        } else {
            // We can skip the authentication process
            return redirect(config('linkedin.post_callback_uri'));
        }
    }

    public function deauthorization(){
        Cache::forget('access_token');
        return redirect(config('linkedin.post_callback_uri'));
    }

    public function callback(Request $request){
        $error = $request->query('error', false);

        if (!$error) {
            $code = $request->query('code');
            $state = $request->query('state');

            // Get access token
            $access_token_url = 'https://www.linkedin.com/oauth/v2/accessToken';

            $response = Http::asForm()->post($access_token_url, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('linkedin.redirect_uri'),
                'client_id' => config('linkedin.client_id'),
                'client_secret' => config('linkedin.client_secret'),
            ]);

            if($response->successful()) {
                $access_token = $response['access_token'];
                $expires_in = $response['expires_in'];

                // Cache the access_token for expires_in seconds and then redirect
                Cache::put('access_token', $access_token, $expires_in);
                return redirect(config('linkedin.post_callback_uri'));
            }
        } else {
            // Log error message and then redirect
            $error = $request->query('error_description');
            Log::error($error);
            return redirect(config('linkedin.post_callback_uri'));
        }
    }
}
