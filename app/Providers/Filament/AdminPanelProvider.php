<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\ProjectStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->maxContentWidth(Width::Full)
            
            // ─── الشعار (Logo) ───
            ->brandLogo(asset('/images/wafaa-logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('/images/wafaa-logo.png'))
            
            // ─── الخط والألوان (Font & Colors) ───
            ->font('Alexandria')
            ->colors([
                'primary' => Color::hex('#0F6E56'), 
                'gray'    => Color::Slate,
            ])
            
            // ─── التصميم المخصص (CSS للوضع النهاري والليلي) ───
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
<style>
/* ─── المتغيرات الأساسية للوضع النهاري ─── */
:root {
    --brand-light: #E1F5EE;
    --brand-main: #0F6E56;
    --bg-main: #f7f8fa;
    --surface: #ffffff;
    --border-light: #e5e7eb;
    --text-main: #111827;
    --text-muted: #6b7280;
    --topbar-bg: rgba(255, 255, 255, 0.85);
    --table-bg: #f9fafb;
    --hover-bg: #f3f4f6;
    
    --radius-lg: 14px;
    --radius-md: 10px;
    --radius-sm: 8px;
    
    --soft-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    --hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
}

/* ─── المتغيرات الأساسية للوضع الليلي ─── */
.dark {
    --brand-light: rgba(15, 110, 86, 0.2);
    --brand-main: #19a581;
    --bg-main: #0f172a;
    --surface: #1e293b;
    --border-light: #334155;
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
    --topbar-bg: rgba(30, 41, 59, 0.85);
    --table-bg: #1e293b;
    --hover-bg: #334155;
    
    --soft-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    --hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
}

/* ─── الأساسيات ─── */
html { direction: rtl; }
* { font-family: 'Alexandria', sans-serif !important; }
body { background: var(--bg-main) !important; color: var(--text-main) !important; }

/* ─── الشريط العلوي والقائمة الجانبية ─── */
.fi-sidebar {
    background: var(--surface) !important;
    border-left: 1px solid var(--border-light) !important;
}
.fi-topbar {
    background: var(--topbar-bg) !important;
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border-light) !important;
    box-shadow: none !important;
}

/* ─── أزرار القائمة الجانبية ─── */
.fi-sidebar-item-button {
    border-radius: var(--radius-md) !important;
    transition: all 0.2s ease !important;
}
.fi-sidebar-item-active > a {
    background: var(--brand-light) !important;
    color: var(--brand-main) !important;
    font-weight: 700 !important;
}
.fi-sidebar-item-active > a svg {
    color: var(--brand-main) !important;
}
.fi-sidebar-item-label { color: var(--text-main) !important; }

/* ─── الهيكل العام والمسافات ─── */
.fi-main, .fi-page, .fi-page-content { width: 100% !important; max-width: 100% !important; }
.fi-header-heading {
    font-size: 1.8rem !important;
    font-weight: 800 !important;
    color: var(--text-main) !important;
}

/* ─── البطاقات (Cards & Widgets) ─── */
.fi-section, .fi-wi-stats-overview-stat {
    background: var(--surface) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-lg) !important;
    box-shadow: var(--soft-shadow) !important;
    transition: box-shadow 0.2s ease, background-color 0.2s, border-color 0.2s;
}
.fi-section:hover, .fi-wi-stats-overview-stat:hover {
    box-shadow: var(--hover-shadow) !important;
}

/* ─── التبويبات (Tabs) ─── */
.fi-tabs {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    gap: 8px !important;
    padding: 0 !important;
}
.fi-tabs-item {
    background: var(--surface) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-md) !important;
    color: var(--text-muted) !important;
    font-weight: 600 !important;
    min-height: 42px !important;
    transition: all 0.2s;
}
.fi-tabs-item-active {
    background: var(--brand-light) !important;
    color: var(--brand-main) !important;
    border-color: var(--brand-main) !important;
    box-shadow: none !important;
}

/* ─── الجداول (Tables) ─── */
.fi-ta-ctn {
    background: var(--surface) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-lg) !important;
    box-shadow: var(--soft-shadow) !important;
    overflow: hidden;
}
.fi-ta-header-toolbar {
    background: var(--table-bg) !important;
    border-bottom: 1px solid var(--border-light) !important;
}
.fi-ta-table thead tr { background: var(--table-bg) !important; }
.fi-ta-table thead th {
    color: var(--text-muted) !important;
    font-weight: 800 !important;
    letter-spacing: 0.05em;
    font-size: 13px !important;
    border-bottom: 1px solid var(--border-light) !important;
}
.fi-ta-row td {
    border-bottom: 1px solid var(--border-light) !important;
    color: var(--text-main) !important;
}
.fi-ta-row:hover td { background: var(--hover-bg) !important; }

/* ─── المدخلات والأزرار (Inputs & Buttons) ─── */
.fi-btn {
    border-radius: var(--radius-md) !important;
    font-weight: 700 !important;
}
.fi-badge {
    border-radius: 99px !important;
    font-weight: 700 !important;
    padding: 2px 10px !important;
}
.fi-input-wrp, .fi-select-input {
    background: var(--surface) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: none !important;
    color: var(--text-main) !important;
    transition: all 0.2s;
}
.fi-input-wrp:focus-within, .fi-select-input:focus {
    border-color: var(--brand-main) !important;
    box-shadow: 0 0 0 4px var(--brand-light) !important;
}

@media (max-width: 768px) {
    .fi-header-heading { font-size: 1.5rem !important; }
    .fi-page-content-ctn { padding-inline: 0.5rem !important; }
}
</style>
HTML)
            )
            // Enable Filament's bell icon (database notifications) so users
            // see the alerts emitted by InternalNotifier as they happen.
            // Polls every 30s to refresh unread count without a hard reload.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                ProjectStatsOverview::class,
                FinanceStatsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}