<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Zynqa\FilamentNotifications\Filament\Pages\ManageEntityTypeSettings;
use Zynqa\FilamentNotifications\Filament\Pages\MySubscriptions;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;
use Zynqa\FilamentNotifications\Models\AdminNotification;
use Zynqa\FilamentNotifications\Models\EntityTypeSetting;
use Zynqa\FilamentNotifications\Policies\AdminNotificationPolicy;

class FilamentNotificationsPlugin implements Plugin
{
    use EvaluatesClosures;

    protected array $subscribableEntities = [];

    protected array $systemEmailTypes = [];

    protected ?string $mySubscriptionsNavigationGroup = null;

    protected ?int $mySubscriptionsNavigationSort = null;

    public function getId(): string
    {
        return 'filament-notifications';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AdminNotificationResource::class,
        ]);

        $panel->pages([
            MySubscriptions::class,
            ManageEntityTypeSettings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Register policy
        Gate::policy(
            AdminNotification::class,
            AdminNotificationPolicy::class
        );

        // Inject the invisible Livewire component that syncs notification_recipients.read_at
        // when Filament's markedNotificationAsRead event fires (raw query bypasses model events)
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(Blade::render('<livewire:notification-read-sync />')),
        );

        // Seed entity_type_settings for registered entities
        $this->seedEntityTypeSettings();
    }

    /**
     * Register a subscribable entity type.
     *
     * @param  string  $modelClass  The model class (e.g., \App\Models\Workitem::class)
     * @param  string  $label  Human-readable label (e.g., 'Workitem')
     * @param  string  $defaultChannel  Default notification channel ('database', 'email', 'both')
     */
    public function registerSubscribableEntity(string $modelClass, string $label, string $defaultChannel = 'database'): static
    {
        $alias = method_exists($modelClass, 'getSubscribableType')
            ? $modelClass::getSubscribableType()
            : strtolower(class_basename($modelClass));

        $this->subscribableEntities[$alias] = [
            'class' => $modelClass,
            'label' => $label,
            'defaultChannel' => $defaultChannel,
        ];

        return $this;
    }

    /**
     * Get all registered entity types.
     *
     * @return array<string, array{class: string, label: string, defaultChannel: string}>
     */
    public function getRegisteredEntityTypes(): array
    {
        return $this->subscribableEntities;
    }

    /**
     * Register a system/transactional email type (e.g. new_user, password_changed).
     */
    public function registerSystemEmailType(string $alias, string $label): static
    {
        $this->systemEmailTypes[$alias] = ['label' => $label];

        return $this;
    }

    /**
     * Get all registered system email types.
     *
     * @return array<string, array{label: string}>
     */
    public function getRegisteredSystemEmailTypes(): array
    {
        return $this->systemEmailTypes;
    }

    public function mySubscriptionsNavigationGroup(string $group): static
    {
        $this->mySubscriptionsNavigationGroup = $group;

        return $this;
    }

    public function mySubscriptionsNavigationSort(int $sort): static
    {
        $this->mySubscriptionsNavigationSort = $sort;

        return $this;
    }

    public function getMySubscriptionsNavigationGroup(): ?string
    {
        return $this->mySubscriptionsNavigationGroup;
    }

    public function getMySubscriptionsNavigationSort(): ?int
    {
        return $this->mySubscriptionsNavigationSort;
    }

    protected function seedEntityTypeSettings(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('entity_type_settings')) {
                foreach ($this->subscribableEntities as $alias => $config) {
                    EntityTypeSetting::firstOrCreate(
                        ['entity_type' => $alias],
                        ['label' => $config['label']]
                    );
                }

                foreach ($this->systemEmailTypes as $alias => $config) {
                    EntityTypeSetting::firstOrCreate(
                        ['entity_type' => $alias],
                        ['label' => $config['label']]
                    );
                }
            }
        } catch (\Throwable) {
            // Silently fail on fresh installs before migrations run
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
