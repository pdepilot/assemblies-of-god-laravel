<?php

use App\Services\CommunicationHub\EmailTemplateBodyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /** @return array<string, string> */
  private function plainTemplates(): array
    {
        return [
            'donation_thank_you' => <<<'TEXT'
Dear {{donor_name}},

Thank you for your generous gift of {{amount}} to {{church_name}}.

Category: {{donation_category}}
Receipt Number: {{receipt_number}}
Reference: {{transaction_reference}}
Date: {{donation_date}}

Your PDF receipt is attached for your records. We are deeply grateful for your partnership in the gospel.

With appreciation,
{{pastor_name}}
{{church_name}}
TEXT,
            'donation_appreciation' => <<<'TEXT'
Dear {{donor_name}},

We wanted to follow up and say thank you again for your recent {{donation_category}} gift of {{amount}}. Your generosity is making a difference in our church and community.

Blessings,
{{pastor_name}}
{{church_name}}
TEXT,
            'church_update' => <<<'TEXT'
Dear {{donor_name}},

Because of partners like you, {{church_name}} continues to reach souls, support families, and expand ministry. Thank you for your faithful {{donation_category}} support.

We are grateful for you.

{{pastor_name}}
{{church_name}}
TEXT,
            'recurring_appreciation' => <<<'TEXT'
Dear {{donor_name}},

Your recurring commitment of {{amount}} strengthens our mission every month. We are honored to partner with you in advancing the work of the kingdom.

Thank you for your faithfulness.

{{pastor_name}}
{{church_name}}
TEXT,
            'welcome_email' => <<<'TEXT'
Dear {{member_name}},

Welcome to our church family! We are delighted to have you worship with us at {{church_name}}.

May the Lord bless you richly as you grow with us in faith and fellowship.

Blessings,
{{pastor_name}}
{{church_name}}
TEXT,
            'event_invitation' => <<<'TEXT'
Dear {{member_name}},

You are warmly invited to join us for {{event_name}} on {{event_date}}.

We would be honored to have you with us. Please let us know if you plan to attend.

See you there,
{{church_name}}
TEXT,
            'partnership_invitation' => <<<'TEXT'
Dear {{donor_name}},

We invite you to join our Kingdom Partnership program and advance the vision of {{church_name}} through faithful giving and prayer.

Your partnership makes a lasting difference.

Blessings,
{{pastor_name}}
{{church_name}}
TEXT,
            'prayer_response' => <<<'TEXT'
Dear {{member_name}},

Thank you for sharing your prayer request with us. Our pastoral team is standing with you in faith and lifting your needs before the Lord.

You are not alone. We are praying with you.

In Christ,
{{pastor_name}}
{{church_name}}
TEXT,
            'birthday_message' => <<<'TEXT'
Dear {{member_name}},

Happy Birthday! May God's favor, wisdom, and joy surround you on this special day. We celebrate you and pray abundant blessings over your new year of life.

With love,
{{pastor_name}}
{{church_name}}
TEXT,
            'anniversary_message' => <<<'TEXT'
Dear {{member_name}},

Congratulations on your wedding anniversary! May God continue to strengthen your union and fill your home with peace, love, and joy.

Celebrating with you,
{{pastor_name}}
{{church_name}}
TEXT,
            'follow_up' => <<<'TEXT'
Dear {{member_name}},

We wanted to check in and let you know that you are valued at {{church_name}}. If there is any way we can support or pray with you, please reach out.

You matter to us.

Blessings,
{{pastor_name}}
{{church_name}}
TEXT,
            'new_convert_welcome' => <<<'TEXT'
Dear {{member_name}},

Congratulations on your decision for Christ! We rejoice with you and are here to walk with you as you grow in your new life in Him.

Welcome to the family of God.

{{pastor_name}}
{{church_name}}
TEXT,
            'membership_approval' => <<<'TEXT'
Dear {{member_name}},

Your membership application with {{church_name}} has been approved. Welcome home! We are excited to journey with you as part of our church family.

Blessings,
{{pastor_name}}
{{church_name}}
TEXT,
            'volunteer_appreciation' => <<<'TEXT'
Dear {{member_name}},

Thank you for your faithful service. You are a blessing to this ministry, and your dedication does not go unnoticed.

We are grateful for you.

{{pastor_name}}
{{church_name}}
TEXT,
            'fundraising_campaign' => <<<'TEXT'
Dear {{donor_name}},

Partner with us to advance {{church_name}} through this special campaign. Your support helps us reach more lives for Christ.

Thank you for considering how you can be part of what God is doing.

{{pastor_name}}
{{church_name}}
TEXT,
            'general_announcement' => <<<'TEXT'
Dear {{member_name}},

Please read this important update from {{church_name}}.

[Add your announcement details here.]

Thank you for your attention.

{{pastor_name}}
{{church_name}}
TEXT,
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $formatter = app(EmailTemplateBodyService::class);
        $templates = $this->plainTemplates();

        foreach (DB::table('email_templates')->get(['id', 'slug', 'body_html']) as $row) {
            $slug = (string) ($row->slug ?? '');
            $plain = $templates[$slug] ?? $formatter->toPlainText((string) ($row->body_html ?? ''));
            if ($plain === '') {
                continue;
            }

            DB::table('email_templates')->where('id', $row->id)->update([
                'body_html' => $plain,
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('communication_templates')) {
            foreach (DB::table('communication_templates')->where('channel', 'email')->get(['id', 'body_html', 'body_text']) as $row) {
                $source = trim((string) ($row->body_text ?? ''));
                if ($source === '') {
                    $source = (string) ($row->body_html ?? '');
                }
                $plain = $formatter->toPlainText($source);
                if ($plain === '') {
                    continue;
                }

                DB::table('communication_templates')->where('id', $row->id)->update([
                    'body_text' => $plain,
                    'body_html' => null,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Plain-text bodies cannot be reliably restored to the original HTML layouts.
    }
};
