<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Legacy Breeze profile — redirect to the customer account profile.
     */
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route('customer.profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        return redirect()->route('customer.profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        return redirect()->route('customer.profile.edit');
    }
}
