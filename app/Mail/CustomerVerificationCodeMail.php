<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Customer;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;
    public string $verificationCode;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Customer $customer, string $verificationCode)
    {
        $company = Company::first();

        $this->customer = $customer;
        $this->verificationCode = $verificationCode;
        $this->platformName = (string) (SystemConfig::query()->value('platform_name') ?: 'Moover');
        $this->companyEmail = $company?->email;
        $this->companyPhone = $company?->phone;
        $this->companyAddress = $company?->address;
        $this->companyLogo = $company?->logo;
    }

    public function build(): self
    {
        return $this
            ->subject("Verify your email - {$this->platformName}")
            ->view('emails.customer_verification_code');
    }
}

