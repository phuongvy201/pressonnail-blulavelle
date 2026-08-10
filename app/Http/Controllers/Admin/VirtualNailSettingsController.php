<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use App\Support\VirtualNailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualNailSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = VirtualNailSettings::allForAdmin();

        $providerStatus = [
            'chatgpt2api_configured' => trim((string) config('virtual_nail.chatgpt2api.auth_key', '')) !== ''
                && trim((string) config('virtual_nail.chatgpt2api.base_url', '')) !== '',
            'chatgpt2api_base_url' => (string) config('virtual_nail.chatgpt2api.base_url'),
            'chatgpt2api_model' => (string) config('virtual_nail.chatgpt2api.model'),
            'image_api_configured' => trim((string) config('virtual_nail.image_api.api_key', '')) !== ''
                && trim((string) config('virtual_nail.image_api.base_url', '')) !== '',
            'image_api_model' => (string) config('virtual_nail.image_api.model'),
            'image_api_base_url' => (string) config('virtual_nail.image_api.base_url'),
        ];

        return view('admin.settings.virtual-nail', [
            'settings' => $settings,
            'providerStatus' => $providerStatus,
            'defaultPrompt' => VirtualNailSettings::defaultPromptTemplate(),
            'storefrontUrl' => route('virtual-nail.index'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'prefer_browser_pool' => ['nullable', 'boolean'],
            'shapes_text' => ['required', 'string', 'max:2000'],
            'lengths_text' => ['required', 'string', 'max:2000'],
            'default_shape' => ['required', 'string', 'max:64'],
            'default_length' => ['required', 'string', 'max:64'],
            'prompt_template' => ['required', 'string', 'max:20000'],
            'notice_title' => ['required', 'string', 'max:120'],
            'notice_text' => ['required', 'string', 'max:1000'],
            'capture_tips_text' => ['required', 'string', 'max:3000'],
            'browse_rate_limit' => ['required', 'integer', 'min:30', 'max:600'],
            'rate_limit' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $shapes = $this->parseLines($validated['shapes_text']);
        $lengths = $this->parseLines($validated['lengths_text']);
        $tips = $this->parseLines($validated['capture_tips_text']);

        if ($shapes === []) {
            return back()->withInput()->withErrors(['shapes_text' => 'Add at least one nail shape.']);
        }
        if ($lengths === []) {
            return back()->withInput()->withErrors(['lengths_text' => 'Add at least one nail length.']);
        }
        if ($tips === []) {
            return back()->withInput()->withErrors(['capture_tips_text' => 'Add at least one capture tip.']);
        }

        $defaultShape = trim($validated['default_shape']);
        $defaultLength = trim($validated['default_length']);

        if (! in_array($defaultShape, $shapes, true)) {
            $defaultShape = $shapes[0];
        }
        if (! in_array($defaultLength, $lengths, true)) {
            $defaultLength = $lengths[0];
        }

        Settings::set(VirtualNailSettings::KEY_ENABLED, $request->boolean('enabled') ? '1' : '0');
        Settings::set(VirtualNailSettings::KEY_PREFER_BROWSER_POOL, $request->boolean('prefer_browser_pool') ? '1' : '0');
        Settings::set(VirtualNailSettings::KEY_SHAPES, json_encode($shapes));
        Settings::set(VirtualNailSettings::KEY_LENGTHS, json_encode($lengths));
        Settings::set(VirtualNailSettings::KEY_DEFAULT_SHAPE, $defaultShape);
        Settings::set(VirtualNailSettings::KEY_DEFAULT_LENGTH, $defaultLength);
        Settings::set(VirtualNailSettings::KEY_PROMPT_TEMPLATE, trim($validated['prompt_template']));
        Settings::set(VirtualNailSettings::KEY_NOTICE_TITLE, trim($validated['notice_title']));
        Settings::set(VirtualNailSettings::KEY_NOTICE_TEXT, trim($validated['notice_text']));
        Settings::set(VirtualNailSettings::KEY_CAPTURE_TIPS, json_encode($tips));
        Settings::set(VirtualNailSettings::KEY_BROWSE_RATE_LIMIT, (string) (int) $validated['browse_rate_limit']);
        Settings::set(VirtualNailSettings::KEY_RATE_LIMIT, (string) (int) $validated['rate_limit']);

        return redirect()
            ->route('admin.settings.virtual-nail.edit')
            ->with('success', 'Virtual Nail settings updated successfully.');
    }

    public function resetPrompt(): RedirectResponse
    {
        Settings::set(VirtualNailSettings::KEY_PROMPT_TEMPLATE, VirtualNailSettings::defaultPromptTemplate());

        return redirect()
            ->route('admin.settings.virtual-nail.edit')
            ->with('success', 'Prompt template reset to default.');
    }

    /**
     * @return list<string>
     */
    private function parseLines(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $lines))));
    }
}
