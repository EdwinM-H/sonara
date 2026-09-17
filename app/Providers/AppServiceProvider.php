<?php

namespace App\Providers;

use App\Models\AssistanceRequest;
use App\Models\Business;
use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Policies\AssistanceRequestPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\PublicationPolicy;
use App\Policies\RequestPolicy;
use App\Policies\UserPolicy;
use App\Policies\VerificationDocumentPolicy;
use App\Services\AI\ImageGenerationService;
use App\Services\Assistant\VoiceAssistantService;
use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use App\Services\Voice\VoiceManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditService::class);
        $this->app->singleton(VerificationService::class);
        $this->app->singleton(ImageGenerationService::class);
        $this->app->singleton(VoiceManager::class);
        $this->app->bind(VoiceAssistantService::class, function () {
            return new VoiceAssistantService('voice_registration.'.session()->getId());
        });
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Publication::class, PublicationPolicy::class);
        Gate::policy(CustomerRequest::class, RequestPolicy::class);
        Gate::policy(VerificationDocument::class, VerificationDocumentPolicy::class);
        Gate::policy(AssistanceRequest::class, AssistanceRequestPolicy::class);
    }
}