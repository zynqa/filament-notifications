<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Pages;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Zynqa\FilamentNotifications\FilamentNotificationsPlugin;
use Zynqa\FilamentNotifications\Models\EntityTypeSetting;
use Zynqa\FilamentNotifications\Services\MailTemplateService;

class ManageEntityTypeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament-notifications::pages.manage-entity-type-settings';

    protected static ?string $title = 'Notification Templates';

    protected static ?string $navigationLabel = 'Notification Templates';

    public array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    public function mount(): void
    {
        $settings = EntityTypeSetting::all()->keyBy('entity_type');
        $formData = [];

        $plugin = FilamentNotificationsPlugin::get();

        foreach ($plugin->getRegisteredEntityTypes() as $alias => $config) {
            $formData["template_{$alias}"] = $settings->get($alias)?->email_template ?? null;
        }

        foreach ($plugin->getRegisteredSystemEmailTypes() as $alias => $config) {
            $formData["template_{$alias}"] = $settings->get($alias)?->email_template ?? null;
        }

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        $plugin = FilamentNotificationsPlugin::get();
        $templateOptions = MailTemplateService::getAvailableTemplates();

        // — Subscription entity type fields —
        $entityFields = [];
        foreach ($plugin->getRegisteredEntityTypes() as $alias => $config) {
            $entityFields[] = Select::make("template_{$alias}")
                ->label($config['label'].' Email Template')
                ->options($templateOptions)
                ->placeholder('Use global default')
                ->nullable();
        }

        if (empty($entityFields)) {
            $entityFields[] = Placeholder::make('empty_entities')
                ->label('')
                ->content('No entity types registered. Register entity types via the plugin configuration.');
        }

        // — System / transactional email type fields —
        $systemFields = [];
        foreach ($plugin->getRegisteredSystemEmailTypes() as $alias => $config) {
            $systemFields[] = Select::make("template_{$alias}")
                ->label($config['label'])
                ->options($templateOptions)
                ->placeholder('Use global default')
                ->nullable();
        }

        if (empty($systemFields)) {
            $systemFields[] = Placeholder::make('empty_system')
                ->label('')
                ->content('No system email types registered. Register system email types via the plugin configuration.');
        }

        return $form
            ->schema([
                Section::make('Subscription Notification Templates')
                    ->description('Email template used when notifying subscribers of entity changes. Leave blank to use the global default.')
                    ->schema($entityFields),

                Section::make('Transactional Email Templates')
                    ->description('Email templates for system-triggered emails such as welcome emails and password change notifications. Leave blank to use the global default.')
                    ->schema($systemFields),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $plugin = FilamentNotificationsPlugin::get();

        foreach ($plugin->getRegisteredEntityTypes() as $alias => $config) {
            EntityTypeSetting::updateOrCreate(
                ['entity_type' => $alias],
                [
                    'label' => $config['label'],
                    'email_template' => $data["template_{$alias}"] ?? null,
                ]
            );
        }

        foreach ($plugin->getRegisteredSystemEmailTypes() as $alias => $config) {
            EntityTypeSetting::updateOrCreate(
                ['entity_type' => $alias],
                [
                    'label' => $config['label'],
                    'email_template' => $data["template_{$alias}"] ?? null,
                ]
            );
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }
}
