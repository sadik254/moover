<?php

namespace App\Mail;

use App\Models\Affiliate;
use App\Models\Company;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateCreatedPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public Affiliate $affiliate;
    public string $generatedPassword;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Affiliate $affiliate, string $generatedPassword)
    {
        $company = Company::first();

        $this->affiliate = $affiliate;
        $this->generatedPassword = $generatedPassword;
        $this->platformName = (string) (SystemConfig::query()->value('platform_name') ?: 'Moover');
        $this->companyEmail = $company?->email;
        $this->companyPhone = $company?->phone;
        $this->companyAddress = $company?->address;
        $this->companyLogo = $company?->logo;
    }

    public function build(): self
    {
        return $this
            ->subject("Your {$this->platformName} affiliate account credentials")
            ->view('emails.affiliate_created_password');
    }
}

