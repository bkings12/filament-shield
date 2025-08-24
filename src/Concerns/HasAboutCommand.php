<?php

declare(strict_types=1);

namespace BezhanSalleh\FilamentShield\Concerns;

use BezhanSalleh\FilamentShield\Support\Utils;

trait HasAboutCommand
{
    public function initAboutCommand()
    {
        // For now, we'll just log that Shield is configured
        // The about command in newer Laravel versions doesn't support custom sections the same way
        logger()->info('Shield is configured and ready', [
            'auth_provider' => Utils::getAuthProviderFQCN(),
            'tenancy_enabled' => Utils::isTenancyEnabled(),
            'tenant_model' => config()->get('filament-shield.tenant_model'),
            'translations_published' => is_dir(resource_path('lang/vendor/filament-shield')),
            'views_published' => is_dir(resource_path('views/vendor/filament-shield')),
        ]);
    }

    protected static function authProviderConfigured(): string
    {
        if (class_exists(Utils::getAuthProviderFQCN())) {
            return Utils::isAuthProviderConfigured()
                ? '<fg=green;options=bold>CONFIGURED</>'
                : '<fg=red;options=bold>NOT CONFIGURED</>';
        }

        return '';
    }
}
