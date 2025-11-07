<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePassportKeys();
        $this->ensurePassportClients();
    }

    protected function ensurePassportClients(): void
    {
        $repository = app(ClientRepository::class);
        $provider = config('auth.guards.api.provider');

        $personalClient = Client::query()->get()->first(function (Client $client): bool {
            return in_array('personal_access', $client->grant_types ?? [], true);
        });

        if (! $personalClient) {
            $personalClient = $repository->createPersonalAccessGrantClient('Test Personal Access Client', $provider);
        }

        config([
            'passport.personal_access_client.id' => $personalClient->id,
            'passport.personal_access_client.secret' => $personalClient->secret,
        ]);

        $passwordClientExists = Client::query()->get()->contains(function (Client $client): bool {
            return in_array('password', $client->grant_types ?? [], true);
        });

        if (! $passwordClientExists) {
            $repository->createPasswordGrantClient('Test Password Grant Client', $provider);
        }
    }

    protected function ensurePassportKeys(): void
    {
        $privateKeyPath = Passport::keyPath('oauth-private.key');
        $publicKeyPath = Passport::keyPath('oauth-public.key');

        $directory = dirname($privateKeyPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($privateKeyPath) || ! File::exists($publicKeyPath)) {
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            openssl_pkey_export($resource, $privateKey);
            $details = openssl_pkey_get_details($resource);
            $publicKey = $details['key'] ?? null;

            if ($privateKey && $publicKey) {
                File::put($privateKeyPath, $privateKey);
                File::put($publicKeyPath, $publicKey);
            }
        }
    }
}
