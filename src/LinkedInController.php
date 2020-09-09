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
                'client_id' => env('LINKEDIN_CLIENT_ID'),
                'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
                'state' => env('LINKEDIN_STATE'),
                'scope' => $request->get('scope')
            );

            $query = http_build_query($query_parts);
            return redirect($authorization_url.'?'.$query);
        } else {
            // We can skip the authentication process
            return redirect(env('LINKEDIN_POST_CALLBACK_URI'));
        }
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
                'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
                'client_id' => env('LINKEDIN_CLIENT_ID'),
                'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            ]);

            if($response->successful()) {
                $access_token = $response['access_token'];
                $expires_in = $response['expires_in'];

                // Cache the access_token for expires_in seconds and then redirect
                Cache::put('access_token', $access_token, $expires_in);
                return redirect(env('LINKEDIN_POST_CALLBACK_URI'));
            }
        } else {
            // Log error message and then redirect
            $error = $request->query('error_description');
            Log::error($error);
            return redirect(env('LINKEDIN_POST_CALLBACK_URI'));
        }
    }
}
