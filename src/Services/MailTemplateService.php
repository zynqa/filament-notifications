<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MailTemplateService
{
    protected static string $templatesPath = 'mail-templates';

    /**
     * Get array of available email templates for dropdown
     *
     * @return array<string, string> ['filename.blade.php' => 'Display Name']
     */
    public static function getAvailableTemplates(): array
    {
        $path = storage_path('app/' . self::$templatesPath);

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
            self::createDefaultTemplate();
        }

        $files = File::glob($path . '/*.blade.php');
        $templates = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $displayName = Str::of($filename)
                ->replace('.blade.php', '')
                ->replace('-', ' ')
                ->replace('_', ' ')
                ->title()
                ->toString();

            $templates[$filename] = $displayName;
        }

        return $templates;
    }

    /**
     * Check if template exists
     */
    public static function templateExists(string $template): bool
    {
        $path = storage_path('app/' . self::$templatesPath . '/' . $template);

        return File::exists($path);
    }

    /**
     * Get full path to template file
     */
    public static function getTemplatePath(string $template): string
    {
        return storage_path('app/' . self::$templatesPath . '/' . $template);
    }

    /**
     * Create default template if it doesn't exist
     */
    public static function createDefaultTemplate(): void
    {
        // Ensure the templates directory exists
        $path = storage_path('app/' . self::$templatesPath);
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $defaultPath = storage_path('app/' . self::$templatesPath . '/default.blade.php');

        if (! File::exists($defaultPath)) {
            $stub = File::get(__DIR__ . '/../../resources/stubs/default-email-template.blade.php.stub');
            File::put($defaultPath, $stub);
        }

        // Also create README
        $readmePath = storage_path('app/' . self::$templatesPath . '/README.md');
        if (! File::exists($readmePath)) {
            $readmeStub = File::get(__DIR__ . '/../../resources/stubs/template-readme.md.stub');
            File::put($readmePath, $readmeStub);
        }
    }
}
