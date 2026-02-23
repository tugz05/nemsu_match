<?php

namespace App\Http\Controllers\Superadmin;

use App\Events\MaintenanceModeChanged;
use App\Events\NotificationSent;
use App\Events\PreRegistrationModeChanged;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Superadmin\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const BRANDING_DISK = 'public';

    private const BRANDING_DIR = 'branding';

    private const ALLOWED_MIMES = ['image/png', 'image/svg+xml'];

    private const ALLOWED_EXTENSIONS = ['png', 'svg'];

    /**
     * Show settings page
     */
    public function index(): Response
    {
        $settings = AppSetting::getAllGrouped();

        $logoPath = AppSetting::get('app_logo', '');
        $headerIconPath = AppSetting::get('header_icon', '');

        $branding = [
            'app_logo_url' => $logoPath ? asset('storage/'.ltrim($logoPath, '/')) : null,
            'header_icon_url' => $headerIconPath ? asset('storage/'.ltrim($headerIconPath, '/')) : null,
        ];

        return Inertia::render('Superadmin/Settings', [
            'settings' => $settings,
            'branding' => $branding,
        ]);
    }

    /**
     * Update a setting
     */
    public function update(Request $request, AppSetting $appSetting)
    {
        $request->validate([
            'value' => 'required',
        ]);

        $value = $request->value;

        // Handle boolean conversion
        if ($appSetting->type === 'boolean') {
            $value = $value === true || $value === 'true' || $value === '1' ? 'true' : 'false';
        }

        $appSetting->update(['value' => $value]);
        AppSetting::clearCache();

        // Broadcast events for critical mode changes
        if ($appSetting->key === 'maintenance_mode') {
            broadcast(new MaintenanceModeChanged($value === 'true'));
        }

        if ($appSetting->key === 'pre_registration_mode') {
            broadcast(new PreRegistrationModeChanged($value === 'true'));
        }

        return response()->json([
            'message' => 'Setting updated successfully',
            'setting' => [
                'id' => $appSetting->id,
                'key' => $appSetting->key,
                'value' => AppSetting::get($appSetting->key),
                'raw_value' => $appSetting->value,
                'type' => $appSetting->type,
                'description' => $appSetting->description,
            ],
        ]);
    }

    /**
     * Create a new setting
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:app_settings,key',
            'value' => 'required',
            'type' => 'required|in:string,boolean,integer,float,json',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $appSetting = AppSetting::create($request->all());
        AppSetting::clearCache();

        return response()->json([
            'message' => 'Setting created successfully',
            'setting' => $appSetting,
        ], 201);
    }

    /**
     * Delete a setting
     */
    public function destroy(AppSetting $appSetting)
    {
        // Prevent deletion of critical settings
        $criticalSettings = ['maintenance_mode', 'allow_registration'];

        if (in_array($appSetting->key, $criticalSettings)) {
            return response()->json([
                'message' => 'Cannot delete critical system settings',
            ], 422);
        }

        $key = $appSetting->key;
        $appSetting->delete();
        AppSetting::clearCache();

        return response()->json([
            'message' => 'Setting deleted successfully',
        ]);
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:app_settings,id',
            'settings.*.value' => 'required',
        ]);

        $maintenanceModeChanged = false;
        $preRegistrationModeChanged = false;
        $newMaintenanceValue = false;
        $newPreRegValue = false;

        foreach ($request->settings as $settingData) {
            $setting = AppSetting::find($settingData['id']);
            if ($setting) {
                $value = $settingData['value'];

                // Handle boolean conversion
                if ($setting->type === 'boolean') {
                    $value = $value === true || $value === 'true' || $value === '1' ? 'true' : 'false';
                }

                // Track mode changes
                if ($setting->key === 'maintenance_mode') {
                    $maintenanceModeChanged = true;
                    $newMaintenanceValue = $value === 'true';
                }

                if ($setting->key === 'pre_registration_mode') {
                    $preRegistrationModeChanged = true;
                    $newPreRegValue = $value === 'true';
                }

                $setting->update(['value' => $value]);
            }
        }

        AppSetting::clearCache();

        // Broadcast mode changes
        if ($maintenanceModeChanged) {
            broadcast(new MaintenanceModeChanged($newMaintenanceValue));
        }

        if ($preRegistrationModeChanged) {
            broadcast(new PreRegistrationModeChanged($newPreRegValue));
        }

        return response()->json([
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Upload branding asset (app logo or header icon). PNG and SVG only.
     */
    public function uploadBranding(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,header_icon',
            'file' => 'required|file|mimes:png,svg|mimetypes:image/png,image/svg+xml|max:2048',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return response()->json([
                'message' => 'Only PNG and SVG files are allowed.',
            ], 422);
        }

        $key = $request->input('type') === 'logo' ? 'app_logo' : 'header_icon';
        $filename = $request->input('type') === 'logo' ? 'logo.'.$ext : 'header-icon.'.$ext;
        $path = self::BRANDING_DIR.'/'.$filename;

        Storage::disk(self::BRANDING_DISK)->putFileAs(self::BRANDING_DIR, $file, $filename);

        AppSetting::set($key, $path);
        AppSetting::clearCache();

        $url = asset('storage/'.$path);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'url' => $url,
            'path' => $path,
        ]);
    }

    /**
     * Send a test notification via Pusher to the current user (superadmin).
     * Creates a notification and broadcasts it so the browser receives it in real time.
     */
    public function testBrowserNotification(Request $request)
    {
        $user = Auth::user();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'from_user_id' => $user->id,
            'notifiable_type' => 'user',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test from superadmin'],
        ]);
        $notification->load('fromUser:id,display_name,fullname,profile_picture');
        broadcast(new NotificationSent($notification));

        return response()->json([
            'message' => 'Test notification sent via Pusher. Allow browser notifications and enable them in Account settings, then switch to another tab or minimize to see it.',
        ]);
    }
}
