<?php

namespace App\Mail;

use App\Models\SystemConfig;
use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $platformName;
    public ?Company $company;
    public ?string $companyEmail;
    public ?string $companyPhone;
    public ?string $companyAddress;
    public ?string $companyLogo;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->company = Company::first();
        $this->platformName = (string) (SystemConfig::query()->value('platform_name') ?: 'Moover');
        $this->companyEmail = $this->company?->email;
        $this->companyPhone = $this->company?->phone;
        $this->companyAddress = $this->company?->address;
        $this->companyLogo = $this->company?->logo;
    }

    public function build(): self
    {
        return $this
            ->subject("Welcome to {$this->platformName}")
            ->view('emails.user_registered');
    }
}
