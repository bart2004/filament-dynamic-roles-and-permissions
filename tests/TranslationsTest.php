<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Tests;

use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Schemas\RoleForm;
use BartStrik\FilamentDynamicRolesAndPermissions\FilamentDynamicRolesAndPermissionsServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class TranslationsTest extends TestCase
{
    private const NAMESPACE = 'filament-dynamic-roles-and-permissions';

    /**
     * @return array<int, string>
     */
    private function translationFiles(): array
    {
        return array_map(
            fn (string $path): string => basename($path, '.php'),
            glob(__DIR__.'/../resources/lang/en/*.php'),
        );
    }

    public function test_every_english_translation_file_exists_in_dutch(): void
    {
        foreach ($this->translationFiles() as $file) {
            $this->assertFileExists(__DIR__."/../resources/lang/nl/{$file}.php");
        }
    }

    public function test_dutch_has_exactly_the_same_keys_as_english(): void
    {
        foreach ($this->translationFiles() as $file) {
            $english = array_keys(Arr::dot(require __DIR__."/../resources/lang/en/{$file}.php"));
            $dutch = array_keys(Arr::dot(require __DIR__."/../resources/lang/nl/{$file}.php"));

            $this->assertEqualsCanonicalizing($english, $dutch, "Keys of {$file}.php differ between en and nl");
        }
    }

    public function test_translations_are_loaded_under_the_package_namespace(): void
    {
        app()->setLocale('en');
        $this->assertSame('Roles', __(self::NAMESPACE.'::resource.plural_label'));

        app()->setLocale('nl');
        $this->assertSame('Rollen', __(self::NAMESPACE.'::resource.plural_label'));
    }

    public function test_translations_can_be_overridden_from_the_application_lang_directory(): void
    {
        $directory = $this->app->langPath('vendor/'.self::NAMESPACE.'/nl');
        $file = $directory.'/resource.php';

        mkdir($directory, 0777, true);
        file_put_contents($file, "<?php\n\nreturn ['plural_label' => 'Groepen'];\n");

        try {
            app()->setLocale('nl');

            $this->assertSame('Groepen', __(self::NAMESPACE.'::resource.plural_label'));
            $this->assertSame('Rol', __(self::NAMESPACE.'::resource.label'));
        } finally {
            unlink($file);
            rmdir($directory);
        }
    }

    public function test_translations_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            FilamentDynamicRolesAndPermissionsServiceProvider::class,
            self::NAMESPACE.'-translations',
        );

        $this->assertCount(1, $paths);
        $this->assertStringEndsWith('vendor/'.self::NAMESPACE, array_values($paths)[0]);
    }

    public function test_the_permission_level_formatter_is_safe_inside_a_double_quoted_attribute(): void
    {
        app()->setLocale('nl');

        $javascript = (string) (new \ReflectionMethod(RoleForm::class, 'levelsFormatter'))->invoke(null);

        $this->assertStringNotContainsString('"', $javascript);
        $this->assertStringStartsWith("['Geen','Lijst'", $javascript);
        $this->assertStringContainsString("'Definitief']", $javascript);
        $this->assertStringEndsWith('[$value] ?? String($value)', $javascript);
    }
}
