<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Customer;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerCreatedPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;
    public string $generatedPassword;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Customer $customer, string $generatedPassword)
    {
        $company = Company::first();

        $this->customer = $customer;
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
            ->subject("Your {$this->platformName} account credentials")
            ->view('emails.customer_created_password');
    }
}

