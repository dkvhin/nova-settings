<?php

namespace OptimistDigital\NovaSettings;

use Setting;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use OptimistDigital\NovaSettings\Models\Settings;

class NovaSettings extends Tool
{
    protected static $cache = [];
    protected static $fields = [];
    protected static $casts = [];

    public function boot()
    {
        Nova::script('nova-settings', __DIR__ . '/../dist/js/tool.js');
        
        Setting::setExtraColumns(array(
            'user_id' => Auth::user()->id
        ));
    }

    public function renderNavigation()
    {
        return view('nova-settings::navigation', ['fields' => static::$fields]);
    }

    /**
     * Define settings fields and an optional casts.
     *
     * @param array|callable $fields Array of fields/panels to be displayed or callable that returns an array.
     * @param array $casts Associative array same as Laravel's $casts on models.
     **/
    public static function addSettingsFields($fields = [], $casts = [], $path = 'general')
    {
        $path = Str::lower(Str::slug($path));

        static::$fields[$path] = static::$fields[$path] ?? [];
        if (is_callable($fields)) $fields = [$fields];
        static::$fields[$path] = array_merge(static::$fields[$path], $fields ?? []);

        static::$casts = array_merge(static::$casts, $casts ?? []);
    }

    /**
     * Define casts.
     *
     * @param array $casts Casts same as Laravel's casts on a model.
     **/
    public static function addCasts($casts = [])
    {
        static::$casts = array_merge(static::$casts, $casts);
    }

    public static function getFields($path = 'general')
    {
        $rawFields = array_map(function ($fieldItem) {
            return is_callable($fieldItem) ? call_user_func($fieldItem) : $fieldItem;
        }, static::$fields[$path] ?? static::$fields);

        $fields = [];
        foreach ($rawFields as $rawField) {
            if (is_array($rawField)) $fields = array_merge($fields, $rawField);
            else $fields[] = $rawField;
        }

        return $fields;
    }

    public static function clearFields()
    {
        static::$fields = [];
        static::$casts = [];
        static::$cache = [];
    }

    public static function getCasts()
    {
        return static::$casts;
    }

    public static function getSetting($settingKey, $default = null)
    {
        return Setting::get($settingKey, $default);
    }

    public static function getSettings(array $settingKeys = null)
    {
        return  Setting::all();
    }

    public static function getSettingsModel(): string
    {
        return config('nova-settings.models.settings', Settings::class);
    }

    public static function doesPathExist($path)
    {
        return array_key_exists($path, static::$fields);
    }
}
