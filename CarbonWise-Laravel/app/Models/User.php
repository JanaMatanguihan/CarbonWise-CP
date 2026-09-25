<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Services\BrevoMailService;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'sr_code',
        'campus',
        'year_level',
        'profile_picture',
        'faculty_type',
        'office',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification()
{
    $url = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->addMinutes(60),
        [
            'id' => $this->getKey(),
            'hash' => sha1($this->getEmailForVerification()),
        ]
    );

    app(BrevoMailService::class)->send(
        $this->email,
        'Verify Your CarbonWise Email',
        '
        <html>
        <body>
            <h2>Verify Your CarbonWise Email</h2>

            <p>Hello ' . e($this->name) . ',</p>

            <p>
                Thank you for registering with CarbonWise.
                Please click the button below to verify your email address.
            </p>

            <p>
                <a href="' . e($url) . '"
                   style="display:inline-block;
                          padding:12px 20px;
                          background:#2e7d32;
                          color:white;
                          text-decoration:none;
                          border-radius:6px;">
                    Verify Email Address
                </a>
            </p>

            <p>
                This verification link will expire in 60 minutes.
            </p>

            <p>
                If you did not create a CarbonWise account, you can ignore this email.
            </p>

            <p>
                — CarbonWise
            </p>
        </body>
        </html>
        ',
        $this->name
    );
}

public function sendPasswordResetNotification($token)
{
    $url = url(
        '/reset-password/' .
        $token .
        '?email=' .
        urlencode($this->email)
    );

    app(BrevoMailService::class)->send(
        $this->email,
        'Reset Your CarbonWise Password',
        '
        <html>
        <body>
            <h2>Reset Your CarbonWise Password</h2>

            <p>Hello ' . e($this->name) . ',</p>

            <p>
                We received a request to reset your CarbonWise password.
            </p>

            <p>
                <a href="' . e($url) . '"
                   style="display:inline-block;
                          padding:12px 20px;
                          background:#2e7d32;
                          color:white;
                          text-decoration:none;
                          border-radius:6px;">
                    Reset Password
                </a>
            </p>

            <p>
                This password reset link will expire according to your
                CarbonWise password reset settings.
            </p>

            <p>
                If you did not request a password reset, you can ignore this email.
            </p>

            <p>
                — CarbonWise
            </p>
        </body>
        </html>
        ',
        $this->name
    );
}

}