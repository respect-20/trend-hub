<?php

namespace App\Http\Controllers;

use App\Models\UserPlatformPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:'.implode(',', array_keys(config('trending.sources')))],
            'enabled' => ['required', 'boolean'],
        ]);

        UserPlatformPreference::updateOrCreate(
            ['user_id' => $request->user()->id, 'source' => $validated['source']],
            ['enabled' => $validated['enabled']]
        );

        return back();
    }
}
