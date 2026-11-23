<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessBoundaryTest extends TestCase
{
    public function test_guest_cannot_open_private_pages(): void
    {
        $urls = [
            '/dashboard',
            '/users',
            '/ulp',
            '/profil',
            '/target-kpi',
            '/realisasi-kpi',
            '/master-kpi',
            '/target-kpi/import',
            '/realisasi-kpi/import',
        ];

        foreach ($urls as $url) {
            $this->get($url)
                ->assertRedirect(route('login'));
        }
    }

    public function test_api_data_requires_authentication(): void
    {
        $paths = [
            'dashboard',
            'ulp',
            'master-kpi',
            'kpi-ulp',
            'target-kpi',
            'target-kpi-bulanan',
            'realisasi-kpi',
        ];

        foreach ($paths as $path) {
            $this->getJson('/api/' . $path)
                ->assertUnauthorized();
        }
    }

    public function test_browser_data_requires_login(): void
    {
        $urls = [
            '/data/dashboard',
            '/data/dashboard/periods',
            '/data/target/units',
        ];

        foreach ($urls as $url) {
            $this->getJson($url)
                ->assertUnauthorized();
        }
    }

    public function test_api_login_rejects_array_credentials(): void
    {
        $this->postJson('/api/login', [
            'username' => ['admin'],
            'password' => ['invalid'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'username',
                'password',
            ]);
    }

    public function test_dashboard_template_exists(): void
    {
        $this->assertTrue(
            view()->exists('dashboard')
        );
    }
}