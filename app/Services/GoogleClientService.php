<?php

namespace App\Services;

use Google_Client;
use App\Models\User;
use Carbon\Carbon;

class GoogleClientService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->setupClient();
    }

    protected function setupClient()
    {
        info('GoogleClientService: setupClient');
        info(base_path(env('GOOGLE_APPLICATION_CREDENTIALS'))); // /var/www/html/laravel-app/storage/credentials.json
        $this->client->setAuthConfig(base_path(env('GOOGLE_APPLICATION_CREDENTIALS')));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    public function refreshToken(User $user)
    {
        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now()),
        ]);

        if ($this->client->isAccessTokenExpired()) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
            $user->update([
                'google_access_token' => $newToken['access_token'],
                'google_token_expires_at' => Carbon::now()->addSeconds($newToken['expires_in']),
            ]);
        }
    }
}
