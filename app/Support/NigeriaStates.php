<?php

namespace App\Support;

final class NigeriaStates
{
    /** @return list<string> */
    public static function all(): array
    {
        return [
            'Abia',
            'Adamawa',
            'Akwa Ibom',
            'Anambra',
            'Bauchi',
            'Bayelsa',
            'Benue',
            'Borno',
            'Cross River',
            'Delta',
            'Ebonyi',
            'Edo',
            'Ekiti',
            'Enugu',
            'Federal Capital Territory',
            'Gombe',
            'Imo',
            'Jigawa',
            'Kaduna',
            'Kano',
            'Katsina',
            'Kebbi',
            'Kogi',
            'Kwara',
            'Lagos',
            'Nasarawa',
            'Niger',
            'Ogun',
            'Ondo',
            'Osun',
            'Oyo',
            'Plateau',
            'Rivers',
            'Sokoto',
            'Taraba',
            'Yobe',
            'Zamfara',
        ];
    }

    public static function normalize(string $value): ?string
    {
        $needle = strtolower(trim($value));
        if ($needle === '') {
            return null;
        }

        foreach (self::all() as $state) {
            if (strtolower($state) === $needle) {
                return $state;
            }
        }

        $aliases = [
            'fct' => 'Federal Capital Territory',
            'abuja' => 'Federal Capital Territory',
            'imo state' => 'Imo',
            'rivers state' => 'Rivers',
            'lagos state' => 'Lagos',
            'abia state' => 'Abia',
        ];

        return $aliases[$needle] ?? null;
    }
}
