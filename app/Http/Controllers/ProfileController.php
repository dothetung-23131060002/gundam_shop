<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(?=(?:[^0-9]*[0-9]){8,})[0-9+\s.\-()]+$/',
            ],
            'address' => 'nullable|string|max:500',
        ], [
            'phone.regex' => 'Số điện thoại phải có ít nhất 8 chữ số và chỉ gồm số cùng các ký tự +, space, ., -, (, ).',
        ]);

        // Chỉ whitelist 4 trường profile: không bao giờ mass-assign role/password.
        $request->user()->update($validated);

        return redirect()->route('profile.edit')
            ->with('success', 'Đã cập nhật thông tin cá nhân.');
    }
}
