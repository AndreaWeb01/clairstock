<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingService $settings): View
    {
        return view('settings', ['settings' => $settings->all()]);
    }

    public function update(UpdateSettingsRequest $request, SettingService $settings): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $oldLogo = $settings->get('business_logo_path');
        if ($request->hasFile('logo')) $data['business_logo_path'] = $request->file('logo')->store('business', 'public');
        $settings->update($data);
        if ($request->hasFile('logo') && $oldLogo) Storage::disk('public')->delete($oldLogo);
        return redirect()->route('admin.settings')->with('status', 'Paramètres du commerce enregistrés.');
    }
}
