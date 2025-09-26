<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        // If user is already authenticated, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        // Attempt to authenticate the user
        if (Auth::attempt($credentials, $remember)) {
            // Regenerate session ID for security
            $request->session()->regenerate();
            
            // Set remember token if remember me is checked
            if ($remember) {
                $user = Auth::user();
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back! You have been successfully logged in.');
        }

        // Authentication failed
        return redirect()->back()
            ->withErrors(['email' => 'Invalid credentials. Please check your email and password.'])
            ->withInput($request->except('password'));
    }

    /**
     * Show the dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        return view('auth.dashboard', compact('user'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // Clear remember token
        if ($user) {
            $user->setRememberToken(null);
            $user->save();
        }

        // Logout the user
        Auth::logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Clear all session data
        Session::flush();

        return redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');
    }
}
