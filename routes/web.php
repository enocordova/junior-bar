<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| ROTAS DE AUTENTICAÇÃO (Login/Logout)
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;
        return match ($role) {
            'admin' => redirect('/gerente'),
            'garcom' => redirect('/garcom'),
            'cozinha' => redirect('/cozinha'),
            default => redirect('/login'),
        };
    }
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $role = Auth::user()->role;

        return match ($role) {
            'admin' => redirect()->intended('/gerente'),
            'garcom' => redirect()->intended('/garcom'),
            'cozinha' => redirect()->intended('/cozinha'),
            default => redirect('/'),
        };
    }

    return back()->withErrors([
        'email' => 'As credenciais fornecidas estão incorretas.',
    ])->onlyInput('email');
})->middleware('throttle:5,1')->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');


/*
|--------------------------------------------------------------------------
| ROTAS PROTEGIDAS (Com redirecionamento inteligente)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    // Rota do GARÇOM
    Route::get('/garcom', function () {
        // Se não for Garçom nem Admin...
        if (Auth::user()->role !== 'garcom' && Auth::user()->role !== 'admin') {
            // ...Desloga o usuário atual e manda para o login
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect('/login')->withErrors(['email' => 'Acesso restrito. Faça login como Garçom.']);
        }
        return view('garcom');
    })->name('garcom');

    // Rota da COZINHA
    Route::get('/cozinha', function () {
        if (Auth::user()->role !== 'cozinha' && Auth::user()->role !== 'admin') {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect('/login')->withErrors(['email' => 'Acesso restrito. Faça login como Cozinha.']);
        }
        return view('cozinha');
    })->name('cozinha');

    // Rota do GERENTE
    Route::get('/gerente', function () {
        if (Auth::user()->role !== 'admin') {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect('/login')->withErrors(['email' => 'Acesso restrito. Faça login como Gerente.']);
        }
        return view('gerente');
    })->name('gerente');

});