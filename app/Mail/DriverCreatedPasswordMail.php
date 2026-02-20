<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Driver;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DriverCreatedPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public Driver $driver;
    public string $generatedPassword;
    public string $platformName;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(Driver $driver, string $generatedPassword)
    {
        $company = Company::first();

        $this->driver = $driver;
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
            ->subject("Your {$this->platformName} driver account credentials")
            ->view('emails.driver_created_password');
    }
}

