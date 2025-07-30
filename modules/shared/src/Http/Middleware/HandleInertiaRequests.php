<?php

namespace Modules\Shared\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen'  => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'translations' => function () {
                $locale   = app()->getLocale();
                $cacheKey = "translations.{$locale}";

                $cacheDuration = app()->environment('production') ? 86400 : 300;

                return cache()->remember($cacheKey, $cacheDuration, function () use ($locale) {
                    $translations = [];

                    $routeName    = request()->route()?->getName() ?? '';
                    $routeParts   = explode('::', $routeName);
                    $activeModule = count($routeParts) > 1 ? $routeParts[0] : null;

                    $essentialFiles = ['validation', 'pagination', 'auth'];
                    $appLangPath    = base_path("lang/{$locale}");

                    if (File::exists($appLangPath)) {
                        foreach ($essentialFiles as $file) {
                            $filePath = "{$appLangPath}/{$file}.php";
                            if (File::exists($filePath)) {
                                $content = require $filePath;
                                if (is_array($content)) {
                                    $translations = array_merge(
                                        $translations,
                                        Arr::dot($content, "{$file}.")
                                    );
                                }
                            }
                        }
                    }

                    if ($activeModule) {
                        $moduleLangPath = base_path("modules/{$activeModule}/resources/lang/{$locale}");
                        if (File::exists($moduleLangPath)) {
                            foreach (File::files($moduleLangPath) as $file) {
                                if ($file->getExtension() === 'php') {
                                    $content = require $file->getRealPath();
                                    if (is_array($content)) {
                                        $group        = $file->getBasename('.'.$file->getExtension());
                                        $translations = array_merge(
                                            $translations,
                                            Arr::dot($content, "{$activeModule}.{$group}.")
                                        );
                                    }
                                }
                            }
                        }
                    }

                    return $translations;
                });
            },
        ];
    }
}
