<?php

use App\Services\CommunicationHub\EmailTemplateBodyService;

test('email template body converts html to plain text', function () {
    $service = app(EmailTemplateBodyService::class);

    $plain = $service->toPlainText('<p>Dear {{member_name}},</p><p>Thank you for your faithful service.</p>');

    expect($plain)->toBe("Dear {{member_name}},\n\nThank you for your faithful service.");
});

test('email template body converts plain text to html for sending', function () {
    $service = app(EmailTemplateBodyService::class);

    $html = $service->toHtml("Dear {{member_name}},\n\nThank you for your faithful service.");

    expect($html)->toBe('<p>Dear {{member_name}},</p><p>Thank you for your faithful service.</p>');
});
