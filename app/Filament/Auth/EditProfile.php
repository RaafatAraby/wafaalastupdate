<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Custom profile page that lets each user update their own account name,
 * email, WhatsApp number and password. Password changes require the
 * current password (enforced by Filament's CurrentPassword rule).
 */
class EditProfile extends BaseEditProfile
{
    public static function getLabel(): string
    {
        return 'الحساب الشخصي';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات الحساب بنجاح';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الحساب')
                    ->description('تعديل اسم المستخدم والبريد الإلكتروني ورقم الواتساب.')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getWhatsappFormComponent(),
                    ])
                    ->columns(1),

                Section::make('تغيير كلمة المرور')
                    ->description('اترك الحقول فارغة إذا كنت لا ترغب بتغيير كلمة المرور.')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('الاسم الكامل')
            ->required()
            ->maxLength(255);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('البريد الإلكتروني')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getWhatsappFormComponent(): Component
    {
        return TextInput::make('whatsapp_number')
            ->label('رقم واتساب')
            ->helperText('بالصيغة الدولية بدون + ولا 00. مثال: 966512345678')
            ->tel()
            ->maxLength(32)
            ->rule('regex:/^[0-9]{8,15}$/')
            ->dehydrateStateUsing(fn ($state) => $state ? preg_replace('/\D+/', '', $state) : null);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('كلمة المرور الجديدة')
            ->password()
            ->revealable()
            ->rule(Password::default())
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('تأكيد كلمة المرور الجديدة')
            ->password()
            ->revealable()
            ->autocomplete('new-password')
            ->required(fn ($get) => filled($get('password')))
            ->visible(fn ($get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label('كلمة المرور الحالية')
            ->belowContent('مطلوبة فقط عند تغيير كلمة المرور أو البريد الإلكتروني.')
            ->password()
            ->autocomplete('current-password')
            ->currentPassword(guard: 'web')
            ->revealable()
            ->required(fn ($get) => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->visible(fn ($get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false);
    }
}
