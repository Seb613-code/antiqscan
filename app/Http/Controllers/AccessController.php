<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (session('antiqscan_access_granted', false)) {
            return redirect()->route('books.index');
        }

        return view('access.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $password = (string) config('antiqscan.access_password');
        abort_if($password === '', 503, 'AntiQScan access password is not configured.');

        if (! hash_equals($password, $validated['password'])) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $request->session()->regenerate();
        $request->session()->put('antiqscan_access_granted', true);

        return redirect()->intended(route('books.index'));
    }
}
