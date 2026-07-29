<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Email &amp; SMS Settings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">SMTP delivery, SMS gateway, and birthday automation defaults.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => true])

            <form method="POST" action="{{ route('communication-hub.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-lg">General</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium" for="default_channel">Default channel</label>
                            <select id="default_channel" name="default_channel" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach (['email' => 'Email', 'sms' => 'SMS', 'both' => 'Both'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_channel', $settings['hub']['default_channel'] ?? 'email') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="rate_limit_per_minute">Rate limit / minute</label>
                            <input id="rate_limit_per_minute" type="number" min="1" max="10000" name="rate_limit_per_minute"
                                   value="{{ old('rate_limit_per_minute', $settings['hub']['rate_limit_per_minute'] ?? 60) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="birthday_auto_email_enabled" value="0">
                            <input type="checkbox" name="birthday_auto_email_enabled" value="1" @checked(old('birthday_auto_email_enabled', $settings['hub']['birthday_auto_email_enabled'] ?? true))>
                            Auto birthday email
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="birthday_auto_sms_enabled" value="0">
                            <input type="checkbox" name="birthday_auto_sms_enabled" value="1" @checked(old('birthday_auto_sms_enabled', $settings['hub']['birthday_auto_sms_enabled'] ?? true))>
                            Auto birthday SMS
                        </label>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-lg">Email (SMTP)</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium" for="from_email">From email</label>
                            <input id="from_email" type="email" name="from_email"
                                   value="{{ old('from_email', $settings['email']['from_email'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="from_name">From name</label>
                            <input id="from_name" type="text" name="from_name"
                                   value="{{ old('from_name', $settings['email']['from_name'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="reply_to">Reply-to</label>
                            <input id="reply_to" type="email" name="reply_to"
                                   value="{{ old('reply_to', $settings['email']['reply_to'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="smtp_host">SMTP host</label>
                            <input id="smtp_host" type="text" name="smtp_host"
                                   value="{{ old('smtp_host', $settings['email']['smtp_host'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="smtp.example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="smtp_port">SMTP port</label>
                            <input id="smtp_port" type="number" min="1" max="65535" name="smtp_port"
                                   value="{{ old('smtp_port', $settings['email']['smtp_port'] ?? 587) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="smtp_encryption">Encryption</label>
                            <select id="smtp_encryption" name="smtp_encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('smtp_encryption', $settings['email']['smtp_encryption'] ?? 'tls') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="smtp_user">SMTP username</label>
                            <input id="smtp_user" type="text" name="smtp_user"
                                   value="{{ old('smtp_user', $settings['email']['smtp_user'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="smtp_pass">SMTP password</label>
                            <input id="smtp_pass" type="password" name="smtp_pass" value=""
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="{{ ! empty($settings['email']['smtp_pass']) ? 'Leave blank to keep current' : '' }}"
                                   autocomplete="new-password">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium" for="church_email">Church email</label>
                            <input id="church_email" type="email" name="church_email"
                                   value="{{ old('church_email', $settings['email']['church_email'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="church_phone">Church phone</label>
                            <input id="church_phone" type="text" name="church_phone"
                                   value="{{ old('church_phone', $settings['email']['church_phone'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium" for="church_address">Church address</label>
                            <input id="church_address" type="text" name="church_address"
                                   value="{{ old('church_address', $settings['email']['church_address'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="church_website">Church website</label>
                            <input id="church_website" type="text" name="church_website"
                                   value="{{ old('church_website', $settings['email']['church_website'] ?? '') }}"
                                   placeholder="agcikenegbu.org"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <p class="mt-1 text-xs text-gray-500">Domain only is fine — we add https:// automatically.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="pastor_name">Pastor name</label>
                            <input id="pastor_name" type="text" name="pastor_name"
                                   value="{{ old('pastor_name', $settings['email']['pastor_name'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-lg">SMS gateway</h3>
                    <div class="flex flex-wrap gap-6 mb-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="sms_enabled" value="0">
                            <input type="checkbox" name="sms_enabled" value="1" @checked(old('sms_enabled', $settings['hub']['sms_enabled'] ?? false))>
                            Enable SMS sending
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium" for="sms_provider">Provider</label>
                            <select id="sms_provider" name="sms_provider" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach (['termii' => 'Termii', 'twilio' => 'Twilio', 'africastalking' => "Africa's Talking"] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('sms_provider', $settings['hub']['sms_provider'] ?? 'termii') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="sms_sender_id">Sender ID</label>
                            <input id="sms_sender_id" type="text" name="sms_sender_id"
                                   value="{{ old('sms_sender_id', $settings['hub']['sms_sender_id'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="sms_api_key">API key</label>
                            <input id="sms_api_key" type="password" name="sms_api_key" value=""
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="{{ ! empty($settings['hub']['sms_api_key']) ? 'Leave blank to keep current' : '' }}"
                                   autocomplete="new-password">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="sms_api_secret">API secret</label>
                            <input id="sms_api_secret" type="password" name="sms_api_secret" value=""
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="{{ ! empty($settings['hub']['sms_api_secret']) ? 'Leave blank to keep current' : '' }}"
                                   autocomplete="new-password">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium" for="sms_base_url">API base URL (optional)</label>
                            <input id="sms_base_url" type="url" name="sms_base_url"
                                   value="{{ old('sms_base_url', $settings['hub']['sms_base_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                   placeholder="https://api.ng.termii.com">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium">Save settings</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
