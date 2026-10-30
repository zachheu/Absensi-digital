<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\QrToken;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QrTokenService
{
    /**
     * Terbitkan token QR baru untuk sesi absensi.
     *
     * Token mentah hanya dikembalikan sekali untuk ditampilkan, sedangkan
     * database hanya menyimpan hash SHA-256 dari token tersebut.
     *
     * @return array{token: string, expires_at: Carbon, svg: string}
     */
    public function issue(AttendanceSession $session): array
    {
        $ttl = min((int) config('himatif.qr_ttl_seconds'), (int) config('himatif.qr_max_ttl_seconds'));
        $now = now();
        $plainToken = bin2hex(random_bytes(32));

        $session->qrTokens()->create([
            'token_hash' => $this->hash($plainToken),
            'issued_at' => $now,
            'expires_at' => $now->copy()->addSeconds($ttl),
        ]);

        return [
            'token' => $plainToken,
            'expires_at' => $now->copy()->addSeconds($ttl),
            'svg' => $this->renderSvg($plainToken),
        ];
    }

    /**
     * Cabut token lama lalu terbitkan token pengganti (rotasi QR).
     *
     * @return array{token: string, expires_at: Carbon, svg: string}
     */
    public function rotate(AttendanceSession $session): array
    {
        $this->revokeForSession($session);

        return $this->issue($session);
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function findUsable(string $plainToken): ?QrToken
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            return null;
        }

        $token = QrToken::query()
            ->where('token_hash', $this->hash($plainToken))
            ->first();

        if ($token === null || ! $token->isUsable()) {
            return null;
        }

        return $token;
    }

    public function revokeForSession(AttendanceSession $session): void
    {
        $session->qrTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function renderSvg(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(320, 2),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($content);
    }

    /**
     * Token contoh untuk kebutuhan tampilan/uji tanpa menyimpan apa pun.
     */
    public function dummyToken(): string
    {
        return Str::random(64);
    }
}
