<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Customer;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;
    public string $resetCode;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Customer $customer, string $resetCode)
    {
        $company = Company::first();

        $this->customer = $customer;
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
            ->subject("Password reset code - {$this->platformName}")
            ->view('emails.customer_password_reset_code');
    }
}

