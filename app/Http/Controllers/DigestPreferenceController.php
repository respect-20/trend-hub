<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DigestPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'receive_digest' => ['required', 'boolean'],
        ]);

        $request->user()->update($validated);

        return back();
    }
}
