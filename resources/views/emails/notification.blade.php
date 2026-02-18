@php
    $templatePath = \Zynqa\FilamentNotifications\Services\MailTemplateService::getTemplatePath($templateName);
    $templateContent = file_get_contents($templatePath);

    // Render the template with variables
    echo \Illuminate\Support\Facades\Blade::render($templateContent, [
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'notification_type' => $notification_type,
        'icon' => $icon,
        'icon_color' => $icon_color,
    ]);
@endphp
