@php
    $templatePath = \Zynqa\FilamentNotifications\Services\MailTemplateService::getTemplatePath($templateName);
    $templateContent = file_get_contents($templatePath);

    // Render the template with variables. entity_label/event/context are only supplied by
    // subscription notifications; admin broadcasts fall back to title/body alone, so they
    // are passed as nulls rather than omitted (Blade::render only sees what is passed here).
    echo \Illuminate\Support\Facades\Blade::render($templateContent, [
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'notification_type' => $notification_type,
        'icon' => $icon,
        'icon_color' => $icon_color,
        'entity_label' => $entity_label ?? null,
        'event' => $event ?? null,
        'context' => $context ?? [],
        // Null unless a notification names its own button. Templates fall back to a generic
        // label, which suits the "something changed, go and look" notifications but not
        // action emails like choosing a password.
        'cta_label' => $cta_label ?? null,
    ]);
@endphp
