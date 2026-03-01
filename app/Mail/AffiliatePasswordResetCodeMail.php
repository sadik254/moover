<?php

namespace App\Mail;

use App\Models\Affiliate;
use App\Models\Company;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliatePasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Affiliate $affiliate;
    public string $resetCode;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Affiliate $affiliate, string $resetCode)
    {
        $company = Company::first();

        $this->affiliate = $affiliate;
        $this->resetCode = $resetCode;
        $this->platformName = (string) (SystemConfig::query()->value('platform_name') ?: 'Moover');
        $this->companyEmail = $company?->email;
        $this->companyPhone = $company?->phone;
        $this->companyAddress = $company?->address;
        $this->companyLogo = $company?->logo;
    }

    public function build(): self
    {
        return $this
            ->subject("Affiliate password reset code - {$this->platformName}")
            ->view('emails.affiliate_password_reset_code');
    }
}

