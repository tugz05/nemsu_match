<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Superadmin\AppSetting;
use App\Events\MaintenanceModeChanged;
use App\Events\PreRegistrationModeChanged;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index(): Response
    {
        $settings = AppSetting::getAllGrouped();

        return Inertia::render('Superadmin/Settings', [
            'settings' => $settings,
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
}
