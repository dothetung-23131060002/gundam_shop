<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [];

        foreach (Setting::DEFAULTS as $key => $default) {
            $settings[$key] = old($key, Setting::get($key, $default));
        }

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:100',
            'default_deposit_amount' => 'required|integer|min:1000',
            'default_deadline_days' => 'required|integer|min:1|max:365',
            'contact_address' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return back()->with('success', 'Đã lưu cấu hình chung.');
    }
}
