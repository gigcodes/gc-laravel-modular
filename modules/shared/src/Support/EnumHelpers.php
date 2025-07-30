<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Collection;

trait EnumHelpers
{
    /**
     * Gets the names of the values for an enum.
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Gets the names for an enum.
     */
    public static function keys(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * Checks if an enum is the same as the value provided.
     */
    public function is(mixed $value): bool
    {
        return $this->value === $value;
    }

    /**
     * Converts the enum into a collection
     */
    public static function asCollection(): Collection
    {
        return Collection::make(items: static::asArray());
    }

    /**
     * Converts the enum into a format the frontend can display.
     */
    public static function asArray(): array
    {
        return array_map(
            fn ($value) => $value->toArray(),
            static::cases()
        );
    }

    /**
     * Converts the enum into an array.
     */
    public function toArray(): array
    {
        return [
            // if we've got a title map we should use that otherwise generate the title based off of its value
            'label' => $this->getLabel(),
            'value' => $this->value,
        ];
    }

    public static function asKeyValueArray(): array
    {
        return array_reduce(static::cases(), function (array $carry, $case) {
            // Use the enum case value as the key and the label as the value
            $carry[$case->value] = $case->getLabel();

            return $carry;
        }, []);
    }

    public static function asSimpleKeyValueArray(): array
    {
        return array_reduce(static::cases(), function (array $carry, $case) {
            // Use the enum case value as the key and the label as the value
            $carry[$case->value] = $case->value;

            return $carry;
        }, []);
    }
}
