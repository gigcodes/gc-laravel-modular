<?php

use Modules\Shared\Support\EnumHelpers;

// Create test enums for testing the trait
enum TestStringEnum: string
{
    use EnumHelpers;

    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
    case PENDING  = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Active Status',
            self::INACTIVE => 'Inactive Status',
            self::PENDING  => 'Pending Status',
        };
    }
}

enum TestIntEnum: int
{
    use EnumHelpers;

    case ONE   = 1;
    case TWO   = 2;
    case THREE = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::ONE   => 'Number One',
            self::TWO   => 'Number Two',
            self::THREE => 'Number Three',
        };
    }
}

test('enum helpers values method returns all values', function () {
    $values = TestStringEnum::values();

    expect($values)->toBe(['active', 'inactive', 'pending']);
});

test('enum helpers keys method returns all names', function () {
    $keys = TestStringEnum::keys();

    expect($keys)->toBe(['ACTIVE', 'INACTIVE', 'PENDING']);
});

test('enum helpers is method checks value equality', function () {
    $enum = TestStringEnum::ACTIVE;

    expect($enum->is('active'))->toBeTrue();
    expect($enum->is('inactive'))->toBeFalse();
    expect($enum->is('pending'))->toBeFalse();
});

test('enum helpers as collection method returns collection', function () {
    $collection = TestStringEnum::asCollection();

    expect($collection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($collection)->toHaveCount(3);
});

test('enum helpers as array method returns formatted array', function () {
    $array = TestStringEnum::asArray();

    expect($array)->toHaveCount(3);
    expect($array[0])->toBe(['label' => 'Active Status', 'value' => 'active']);
    expect($array[1])->toBe(['label' => 'Inactive Status', 'value' => 'inactive']);
    expect($array[2])->toBe(['label' => 'Pending Status', 'value' => 'pending']);
});

test('enum helpers to array method returns formatted single item', function () {
    $array = TestStringEnum::ACTIVE->toArray();

    expect($array)->toBe(['label' => 'Active Status', 'value' => 'active']);
});

test('enum helpers as key value array method returns key value pairs', function () {
    $array = TestStringEnum::asKeyValueArray();

    expect($array)->toBe([
        'active'   => 'Active Status',
        'inactive' => 'Inactive Status',
        'pending'  => 'Pending Status',
    ]);
});

test('enum helpers as simple key value array method returns simple pairs', function () {
    $array = TestStringEnum::asSimpleKeyValueArray();

    expect($array)->toBe([
        'active'   => 'active',
        'inactive' => 'inactive',
        'pending'  => 'pending',
    ]);
});

test('enum helpers works with integer enums', function () {
    $values = TestIntEnum::values();
    $keys   = TestIntEnum::keys();
    $array  = TestIntEnum::asKeyValueArray();

    expect($values)->toBe([1, 2, 3]);
    expect($keys)->toBe(['ONE', 'TWO', 'THREE']);
    expect($array)->toBe([
        1 => 'Number One',
        2 => 'Number Two',
        3 => 'Number Three',
    ]);
});

test('enum helpers is method works with integer values', function () {
    $enum = TestIntEnum::TWO;

    expect($enum->is(2))->toBeTrue();
    expect($enum->is(1))->toBeFalse();
    expect($enum->is(3))->toBeFalse();
});
