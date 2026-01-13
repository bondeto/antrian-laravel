<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;

        if ($setting->type === 'json') {
            return json_decode($setting->value, true);
        }

        return $setting->value;
    }

    public static function set($key, $value, $type = 'string', $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $type === 'json' ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group
            ]
        );
    }
}
