<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('can update profile photo', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $user->updateProfilePhoto($file);

    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('updating profile photo deletes previous photo', function () {
    $user       = User::factory()->create();
    $firstFile  = UploadedFile::fake()->image('avatar1.jpg');
    $secondFile = UploadedFile::fake()->image('avatar2.jpg');

    $user->updateProfilePhoto($firstFile);
    $firstPath = $user->profile_photo_path;

    $user->updateProfilePhoto($secondFile);

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);
    expect($user->profile_photo_path)->not->toBe($firstPath);
});

test('can delete profile photo', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $user->updateProfilePhoto($file);
    $photoPath = $user->profile_photo_path;

    $user->deleteProfilePhoto();

    expect($user->fresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($photoPath);
});

test('deleting profile photo when none exists does nothing', function () {
    $user = User::factory()->create(['profile_photo_path' => null]);

    $user->deleteProfilePhoto();

    expect($user->profile_photo_path)->toBeNull();
});

test('can get profile photo URL when photo exists', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $user->updateProfilePhoto($file);

    $url = $user->profilePhotoUrl();

    expect($url)->toBeString();
    expect($url)->toContain($user->profile_photo_path);
});

test('can get default profile photo URL when no photo exists', function () {
    $user = User::factory()->create([
        'name'               => 'John Doe',
        'profile_photo_path' => null,
    ]);

    $url = $user->profilePhotoUrl();

    expect($url)->toBeString();
    expect($url)->toContain('ui-avatars.com');
    expect($url)->toContain(urlencode('J D'));
});

test('default profile photo URL handles single name', function () {
    $user = User::factory()->create([
        'name'               => 'John',
        'profile_photo_path' => null,
    ]);

    $url = $user->profilePhotoUrl();

    expect($url)->toContain(urlencode('J'));
});

test('default profile photo URL handles multiple names', function () {
    $user = User::factory()->create([
        'name'               => 'John Michael Doe',
        'profile_photo_path' => null,
    ]);

    $url = $user->profilePhotoUrl();

    expect($url)->toContain(urlencode('J M D'));
});

test('can update profile photo with custom storage path', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $user->updateProfilePhoto($file, 'custom-photos');

    expect($user->profile_photo_path)->toContain('custom-photos');
    Storage::disk('public')->assertExists($user->profile_photo_path);
});
